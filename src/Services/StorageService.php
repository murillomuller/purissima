<?php

namespace App\Services;

class StorageService
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = __DIR__ . '/../../storage/database/';
    }

    /**
     * Store removed order data in single file
     */
    public function storeRemovedOrder(array $orderData): bool
    {
        try {
            $orderId = $orderData['ord_id'] ?? uniqid();
            $filename = $this->storagePath . 'removed_orders.json';

            // Ensure directory exists
            $directory = dirname($filename);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    error_log("StorageService::storeRemovedOrder - Failed to create directory: " . $directory);
                    return false;
                }
            }

            // Load existing data
            $existingData = $this->getRemovedOrders();

            // Create optimized order entry with only essential data
            $optimizedOrder = [
                'order_id' => $orderId,
                'removed_at' => date('Y-m-d H:i:s'),
                'removed_by' => $_SESSION['user_id'] ?? 'system',
                'customer_name' => $orderData['order']['usr_name'] ?? '',
                'customer_email' => $orderData['order']['usr_email'] ?? '',
                'customer_phone' => $orderData['order']['usr_phone'] ?? '',
                'created_at' => $orderData['order']['created_at'] ?? '',
                'status' => $orderData['order']['chg_status'] ?? '',
                'items_count' => count($orderData['items'] ?? []),
                'total_value' => $orderData['order']['itm_price'] ?? '0.00'
            ];

            // Add to existing data
            $existingData[] = $optimizedOrder;

            // Save back to file
            $result = file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
            if ($result === false) {
                error_log("StorageService::storeRemovedOrder - Failed to write file: " . $filename);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log("StorageService::storeRemovedOrder error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all removed orders
     */
    public function getRemovedOrders(): array
    {
        try {
            $filename = $this->storagePath . 'removed_orders.json';

            if (!file_exists($filename)) {
                return [];
            }

            $content = file_get_contents($filename);
            if (!$content) {
                return [];
            }

            $orders = json_decode($content, true);
            if (!is_array($orders)) {
                return [];
            }

            // Sort by removal date (newest first)
            usort($orders, function ($a, $b) {
                return strtotime($b['removed_at']) - strtotime($a['removed_at']);
            });

            return $orders;
        } catch (\Exception $e) {
            error_log("StorageService::getRemovedOrders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific removed order by ID
     */
    public function getRemovedOrder(string $orderId): ?array
    {
        try {
            $orders = $this->getRemovedOrders();

            foreach ($orders as $order) {
                if ($order['order_id'] === $orderId) {
                    return $order;
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log("StorageService::getRemovedOrder error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Restore a removed order (remove from single file)
     */
    public function restoreRemovedOrder(string $orderId): bool
    {
        try {
            $filename = $this->storagePath . 'removed_orders.json';

            if (!file_exists($filename)) {
                return false;
            }

            // Load existing data
            $orders = $this->getRemovedOrders();

            // Remove the order
            $orders = array_filter($orders, function ($order) use ($orderId) {
                return $order['order_id'] !== $orderId;
            });

            // Save back to file
            $result = file_put_contents($filename, json_encode(array_values($orders), JSON_PRETTY_PRINT));
            return $result !== false;
        } catch (\Exception $e) {
            error_log("StorageService::restoreRemovedOrder error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore multiple removed orders
     */
    public function restoreMultipleRemovedOrders(array $orderIds): array
    {
        $results = [];

        foreach ($orderIds as $orderId) {
            $results[$orderId] = $this->restoreRemovedOrder($orderId);
        }

        return $results;
    }

    /**
     * Get removed orders count
     */
    public function getRemovedOrdersCount(): int
    {
        try {
            $orders = $this->getRemovedOrders();
            return count($orders);
        } catch (\Exception $e) {
            error_log("StorageService::getRemovedOrdersCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up old removed orders (older than specified days)
     */
    public function cleanupOldRemovedOrders(int $daysOld = 30): int
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
            $orders = $this->getRemovedOrders();
            $originalCount = count($orders);

            // Filter out old orders
            $orders = array_filter($orders, function ($order) use ($cutoffDate) {
                return $order['removed_at'] >= $cutoffDate;
            });

            $removedCount = $originalCount - count($orders);

            if ($removedCount > 0) {
                // Save the filtered data back to file
                $filename = $this->storagePath . 'removed_orders.json';
                file_put_contents($filename, json_encode(array_values($orders), JSON_PRETTY_PRINT));
            }

            return $removedCount;
        } catch (\Exception $e) {
            error_log("StorageService::cleanupOldRemovedOrders error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Store rótulo generation record
     */
    public function storeRotuloGeneration(string $orderId, string $generatedBy = 'system'): bool
    {
        try {
            $filename = $this->storagePath . 'rotulo_generations.json';

            // Ensure directory exists
            $directory = dirname($filename);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    error_log("StorageService::storeRotuloGeneration - Failed to create directory: " . $directory);
                    return false;
                }
            }

            // Load existing data
            $existingData = $this->getRotuloGenerations();

            // Create generation record
            $generationRecord = [
                'order_id' => $orderId,
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $generatedBy
            ];

            // Add to existing data
            $existingData[] = $generationRecord;

            // Save back to file
            $result = file_put_contents($filename, json_encode($existingData, JSON_PRETTY_PRINT));
            if ($result === false) {
                error_log("StorageService::storeRotuloGeneration - Failed to write file: " . $filename);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log("StorageService::storeRotuloGeneration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all rótulo generation records
     */
    public function getRotuloGenerations(): array
    {
        try {
            $filename = $this->storagePath . 'rotulo_generations.json';

            if (!file_exists($filename)) {
                return [];
            }

            $content = file_get_contents($filename);
            if (!$content) {
                return [];
            }

            $generations = json_decode($content, true);
            if (!is_array($generations)) {
                return [];
            }

            // Sort by generation date (newest first)
            usort($generations, function ($a, $b) {
                return strtotime($b['generated_at']) - strtotime($a['generated_at']);
            });

            return $generations;
        } catch (\Exception $e) {
            error_log("StorageService::getRotuloGenerations error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if order has generated rótulo
     */
    public function hasOrderGeneratedRotulo(string $orderId): bool
    {
        try {
            $generations = $this->getRotuloGenerations();

            foreach ($generations as $generation) {
                if ($generation['order_id'] === $orderId) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            error_log("StorageService::hasOrderGeneratedRotulo error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get generation timestamp for specific order
     */
    public function getOrderRotuloGenerationTime(string $orderId): ?string
    {
        try {
            $generations = $this->getRotuloGenerations();

            foreach ($generations as $generation) {
                if ($generation['order_id'] === $orderId) {
                    return $generation['generated_at'];
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log("StorageService::getOrderRotuloGenerationTime error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        try {
            $filename = $this->storagePath . 'removed_orders.json';

            if (!file_exists($filename)) {
                return [
                    'total_orders' => 0,
                    'total_size' => 0,
                    'oldest_order' => null,
                    'newest_order' => null
                ];
            }

            $orders = $this->getRemovedOrders();
            $totalSize = filesize($filename);
            $dates = array_column($orders, 'removed_at');

            sort($dates);

            return [
                'total_orders' => count($orders),
                'total_size' => $totalSize,
                'oldest_order' => !empty($dates) ? $dates[0] : null,
                'newest_order' => !empty($dates) ? end($dates) : null
            ];
        } catch (\Exception $e) {
            error_log("StorageService::getStorageStats error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'total_size' => 0,
                'oldest_order' => null,
                'newest_order' => null
            ];
        }
    }
}
