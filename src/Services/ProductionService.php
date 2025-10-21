<?php

namespace App\Services;

use App\Services\LoggerService;
use App\Services\PurissimaApiService;

class ProductionService
{
    private LoggerService $logger;
    private PurissimaApiService $purissimaApi;

    public function __construct(LoggerService $logger, PurissimaApiService $purissimaApi)
    {
        $this->logger = $logger;
        $this->purissimaApi = $purissimaApi;
    }

    public function getProductionData(array $filters = []): array
    {
        try {
            $from = $filters['from'] ?? $this->getDefaultFromDate();
            $to = $filters['to'] ?? $this->getDefaultToDate();
            $status = $filters['status'] ?? 'released';
            $limit = $filters['limit'] ?? null;

            // Get orders from API
            $orders = $this->purissimaApi->getOrders();

            // Convert to the format expected by production functions
            $activeOrders = [];
            foreach ($orders as $orderId => $orderData) {
                if (isset($orderData['order']) && isset($orderData['items'])) {
                    $activeOrders[] = [
                        'data' => $orderData['order'],
                        'items' => $orderData['items']
                    ];
                }
            }

            // Filter out removed orders
            $removedOrderIds = $this->getRemovedOrderIds();
            $activeOrders = array_filter($activeOrders, function ($order) use ($removedOrderIds) {
                $orderId = $order['data']['ord_id'] ?? '';
                return !in_array($orderId, $removedOrderIds);
            });

            // Build item aggregates
            $itemAggregates = $this->buildItemAggregates($activeOrders);

            // Get production context
            $productionContext = "range:{$from}::{$to}";
            $productionItems = $this->getProductionItems($activeOrders, $productionContext);

            return [
                'success' => true,
                'orders' => array_values($activeOrders),
                'item_aggregates' => $itemAggregates,
                'production_items' => $productionItems,
                'production_context' => $productionContext,
                'totals' => $this->calculateTotals($productionItems),
                'removed_orders' => $this->getRemovedOrders()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get production data', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function updateProduction(string $context, string $item, int $quantity): bool
    {
        try {
            if (!$context || !$item) {
                throw new \Exception('Context and item are required');
            }

            // Initialize session if not exists
            if (!isset($_SESSION['production_state'])) {
                $_SESSION['production_state'] = [];
            }

            if (!isset($_SESSION['production_state'][$context])) {
                $_SESSION['production_state'][$context] = [];
            }

            if ($quantity <= 0) {
                unset($_SESSION['production_state'][$context][$item]);
            } else {
                $_SESSION['production_state'][$context][$item] = [
                    'quantity' => $quantity,
                    'updatedAt' => date('c')
                ];
            }

            $this->logger->info('Production updated', [
                'context' => $context,
                'item' => $item,
                'quantity' => $quantity
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update production', [
                'error' => $e->getMessage(),
                'context' => $context,
                'item' => $item,
                'quantity' => $quantity
            ]);

            throw $e;
        }
    }

    public function removeOrders(array $orderIds): array
    {
        try {
            if (empty($orderIds)) {
                throw new \Exception('No orders selected');
            }

            // Initialize session if not exists
            if (!isset($_SESSION['removed_orders'])) {
                $_SESSION['removed_orders'] = [];
            }

            $removed = [];
            foreach ($orderIds as $orderId) {
                $_SESSION['removed_orders'][] = [
                    'id' => $orderId,
                    'removed_at' => date('c')
                ];
                $removed[] = $orderId;
            }

            $this->logger->info('Orders removed', [
                'removed_count' => count($removed),
                'order_ids' => $removed
            ]);

            return $removed;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove orders', [
                'error' => $e->getMessage(),
                'order_ids' => $orderIds
            ]);

            throw $e;
        }
    }

    public function restoreOrders(array $orderIds): array
    {
        try {
            if (empty($orderIds)) {
                throw new \Exception('No orders selected');
            }

            if (!isset($_SESSION['removed_orders'])) {
                $_SESSION['removed_orders'] = [];
            }

            $originalCount = count($_SESSION['removed_orders']);
            $_SESSION['removed_orders'] = array_filter($_SESSION['removed_orders'], function ($order) use ($orderIds) {
                return !in_array($order['id'], $orderIds);
            });

            $restoredCount = $originalCount - count($_SESSION['removed_orders']);

            $this->logger->info('Orders restored', [
                'restored_count' => $restoredCount,
                'order_ids' => $orderIds
            ]);

            return $orderIds;
        } catch (\Exception $e) {
            $this->logger->error('Failed to restore orders', [
                'error' => $e->getMessage(),
                'order_ids' => $orderIds
            ]);

            throw $e;
        }
    }

    private function getDefaultFromDate(): string
    {
        $now = new \DateTime();
        $now->sub(new \DateInterval('P30D')); // 30 days ago
        return $now->format('Y-m-d\TH:i');
    }

    private function getDefaultToDate(): string
    {
        return (new \DateTime())->format('Y-m-d\TH:i');
    }

    private function buildItemAggregates(array $orders): array
    {
        $aggregates = [];

        foreach ($orders as $order) {
            $items = $order['items'] ?? [];
            foreach ($items as $item) {
                $itemName = $item['itm_name'] ?? '';
                $quantity = $this->parseItemQuantity($item['quantity'] ?? '');

                if ($itemName && $quantity > 0) {
                    if (!isset($aggregates[$itemName])) {
                        $aggregates[$itemName] = [
                            'item' => $itemName,
                            'totalQuantity' => 0,
                            'orders' => []
                        ];
                    }
                    $aggregates[$itemName]['totalQuantity'] += $quantity;
                    $aggregates[$itemName]['orders'][] = $order;
                }
            }
        }

        return array_values($aggregates);
    }

    private function parseItemQuantity(string $quantity): int
    {
        $quantity = preg_replace('/[^\d]/', '', $quantity);
        return (int)$quantity;
    }

    private function getProductionItems(array $orders, string $context): array
    {
        $aggregates = $this->buildItemAggregates($orders);
        $productionState = $_SESSION['production_state'][$context] ?? [];

        $items = [];
        foreach ($aggregates as $aggregate) {
            $producedQuantity = $productionState[$aggregate['item']]['quantity'] ?? 0;
            $remainingQuantity = max(0, $aggregate['totalQuantity'] - $producedQuantity);

            $items[] = [
                'item' => $aggregate['item'],
                'totalQuantity' => $aggregate['totalQuantity'],
                'producedQuantity' => $producedQuantity,
                'remainingQuantity' => $remainingQuantity
            ];
        }

        return $items;
    }

    private function calculateTotals(array $productionItems): array
    {
        $totals = [
            'required' => 0,
            'produced' => 0,
            'remaining' => 0
        ];

        foreach ($productionItems as $item) {
            $totals['required'] += $item['totalQuantity'];
            $totals['produced'] += $item['producedQuantity'];
            $totals['remaining'] += $item['remainingQuantity'];
        }

        return $totals;
    }

    private function getRemovedOrderIds(): array
    {
        if (!isset($_SESSION['removed_orders'])) {
            return [];
        }

        return array_column($_SESSION['removed_orders'], 'id');
    }

    private function getRemovedOrders(): array
    {
        return $_SESSION['removed_orders'] ?? [];
    }
}
