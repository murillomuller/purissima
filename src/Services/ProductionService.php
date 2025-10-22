<?php

namespace App\Services;

use App\Services\LoggerService;
use App\Services\PurissimaApiService;
use App\Services\StorageService;

class ProductionService
{
    private LoggerService $logger;
    private PurissimaApiService $purissimaApi;
    private StorageService $storageService;

    public function __construct(LoggerService $logger, PurissimaApiService $purissimaApi)
    {
        $this->logger = $logger;
        $this->purissimaApi = $purissimaApi;
        $this->storageService = new StorageService();
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
                    // Check if rótulo has been generated for this order
                    $hasGeneratedRotulo = $this->storageService->hasOrderGeneratedRotulo($orderId);
                    $rotuloGeneratedAt = $hasGeneratedRotulo ? $this->storageService->getOrderRotuloGenerationTime($orderId) : null;

                    $activeOrders[] = [
                        'data' => $orderData['order'],
                        'items' => $orderData['items'],
                        'rotulo_generated' => $hasGeneratedRotulo,
                        'rotulo_generated_at' => $rotuloGeneratedAt
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

            $this->logger->info('Attempting to remove orders', [
                'order_ids' => $orderIds,
                'order_ids_type' => gettype($orderIds[0] ?? null)
            ]);

            $removed = [];
            foreach ($orderIds as $orderId) {
                $this->logger->info('Processing order for removal', [
                    'order_id' => $orderId,
                    'order_id_type' => gettype($orderId)
                ]);

                // Get order data from API before removing
                $orderData = $this->getOrderDataById($orderId);

                if ($orderData) {
                    $this->logger->info('Order data retrieved, storing in file storage', [
                        'order_id' => $orderId
                    ]);

                    // Store in file-based storage
                    $success = $this->storageService->storeRemovedOrder($orderData);
                    if ($success) {
                        $removed[] = $orderId;
                        $this->logger->info('Order successfully stored in file storage', [
                            'order_id' => $orderId
                        ]);
                    } else {
                        $this->logger->error('Failed to store order in file storage', [
                            'order_id' => $orderId
                        ]);
                    }
                } else {
                    $this->logger->warning('Order data not found, skipping removal', [
                        'order_id' => $orderId
                    ]);
                }
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

            $restored = [];
            foreach ($orderIds as $orderId) {
                $success = $this->storageService->restoreRemovedOrder($orderId);
                if ($success) {
                    $restored[] = $orderId;
                }
            }

            $this->logger->info('Orders restored', [
                'restored_count' => count($restored),
                'order_ids' => $restored
            ]);

            return $restored;
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
        $removedOrders = $this->storageService->getRemovedOrders();
        return array_column($removedOrders, 'order_id');
    }

    private function getRemovedOrders(): array
    {
        return $this->storageService->getRemovedOrders();
    }

    public function getRemovedOrdersData(): array
    {
        try {
            $removedOrders = $this->storageService->getRemovedOrders();
            $formattedOrders = [];

            foreach ($removedOrders as $removedOrder) {
                // Create a simplified order structure for display
                $formattedOrders[] = [
                    'data' => [
                        'ord_id' => $removedOrder['order_id'],
                        'usr_name' => $removedOrder['customer_name'],
                        'usr_email' => $removedOrder['customer_email'],
                        'usr_phone' => $removedOrder['customer_phone'],
                        'created_at' => $removedOrder['created_at'],
                        'chg_status' => $removedOrder['status'],
                        'itm_price' => $removedOrder['total_value']
                    ],
                    'items' => [], // Items are not stored in optimized format
                    'removed_at' => $removedOrder['removed_at'],
                    'removed_by' => $removedOrder['removed_by'],
                    'items_count' => $removedOrder['items_count']
                ];
            }

            return $formattedOrders;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get removed orders data', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getOrderDataById(string $orderId): ?array
    {
        try {
            // Get all orders from API
            $orders = $this->purissimaApi->getOrders();

            $this->logger->info('Searching for order', [
                'order_id' => $orderId,
                'total_orders' => count($orders),
                'available_ids' => array_keys($orders)
            ]);

            // Find the specific order - try both string and numeric comparison
            foreach ($orders as $id => $orderData) {
                $idMatch = ($id === $orderId) || ($id == $orderId) || ($id === (string)$orderId) || ($id === (int)$orderId);

                if ($idMatch && isset($orderData['order']) && isset($orderData['items'])) {
                    $this->logger->info('Order found', [
                        'order_id' => $orderId,
                        'found_id' => $id,
                        'id_type' => gettype($id),
                        'order_id_type' => gettype($orderId),
                        'has_order_data' => isset($orderData['order']),
                        'has_items' => isset($orderData['items'])
                    ]);

                    return [
                        'ord_id' => $orderId,
                        'order' => $orderData['order'],
                        'items' => $orderData['items']
                    ];
                }
            }

            $this->logger->warning('Order not found', [
                'order_id' => $orderId,
                'searched_ids' => array_keys($orders)
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get order data', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            return null;
        }
    }
}
