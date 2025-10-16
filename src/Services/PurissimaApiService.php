<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Services\LoggerService;

class PurissimaApiService
{
    private Client $client;
    private LoggerService $logger;
    private string $baseUrl;

    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->baseUrl = $_ENV['PURISSIMA_API_URL'] ?? 'https://api.purissima.com';
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => (int)($_ENV['API_TIMEOUT'] ?? 30),
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Accept' => 'application/json; charset=utf-8',
                'User-Agent' => 'Purissima-PHP-Client/1.0'
            ],
            'verify' => false, // For development - should be true in production
            'http_errors' => false // We'll handle HTTP errors manually
        ]);
    }

    public function getOrders(): array
    {
        try {
            $this->logger->info('Fetching orders from Purissima API', [
                'url' => $this->baseUrl . '/receituario/get-orders.php'
            ]);
            
            $response = $this->client->get('/receituario/get-orders.php');
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            $this->logger->info('API Response received', [
                'status_code' => $statusCode,
                'body_length' => strlen($body)
            ]);
            
            // Check for HTTP errors
            if ($statusCode >= 400) {
                $this->logger->error('API returned error status', [
                    'status_code' => $statusCode,
                    'body' => $body
                ]);
                
                if ($statusCode === 500) {
                    throw new \Exception('Server error: `GET ' . $this->baseUrl . '/receituario/get-orders.php` resulted in a `500 Internal Server Error` response');
                } elseif ($statusCode === 404) {
                    throw new \Exception('API endpoint not found: `GET ' . $this->baseUrl . '/receituario/get-orders.php` resulted in a `404 Not Found` response');
                } elseif ($statusCode === 403) {
                    throw new \Exception('Access forbidden: `GET ' . $this->baseUrl . '/receituario/get-orders.php` resulted in a `403 Forbidden` response');
                } else {
                    throw new \Exception('API error: HTTP ' . $statusCode . ' - ' . $body);
                }
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON response', [
                    'json_error' => json_last_error_msg(),
                    'body' => $body
                ]);
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            if (!isset($data['status']) || $data['status'] !== 1) {
                $this->logger->error('API returned error status', [
                    'data' => $data
                ]);
                throw new \Exception('API returned error status: ' . ($data['message'] ?? 'Unknown error'));
            }

            $this->logger->info('Orders fetched successfully', [
                'count' => count($data['results'] ?? [])
            ]);

            // Ensure proper UTF-8 encoding of the results
            $results = $data['results'] ?? [];
            $utf8Results = $this->ensureUtf8Encoding($results);
            
            // Deduplicate items within each order
            $deduplicatedResults = $this->deduplicateItems($utf8Results);

            // Mock req field for items when in mock mode or for development testing
            $mockMode = filter_var($_ENV['MOCK_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $isDevelopment = ($_ENV['APP_ENV'] ?? '') === 'development';
            
            if ($mockMode || $isDevelopment) {
                foreach ($deduplicatedResults as $oid => &$odata) {
                    if (isset($odata['items']) && is_array($odata['items'])) {
                        // Check if all items already have req field
                        $allHaveReq = true;
                        foreach ($odata['items'] as $item) {
                            if (!isset($item['req']) || $item['req'] === '' || $item['req'] === null) {
                                $allHaveReq = false;
                                break;
                            }
                        }
                        
                        // If not all items have req, mock it for this order
                        if (!$allHaveReq) {
                            foreach ($odata['items'] as &$item) {
                                if (!isset($item['req']) || $item['req'] === '' || $item['req'] === null) {
                                    $item['req'] = '321321';
                                }
                            }
                            unset($item);
                            
                        }
                    }
                }
                unset($odata);
            }
            
            return $deduplicatedResults;

        } catch (RequestException $e) {
            $this->logger->error('Request exception occurred', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/receituario/get-orders.php'
            ]);
            throw new \Exception('Failed to fetch orders: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Error processing orders data', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getOrderById(string $orderId): ?array
    {
        $orders = $this->getOrders();
        return $orders[$orderId] ?? null;
    }

    /**
     * Deduplicate items within each order based on itm_id and var_id
     * This removes exact duplicates while preserving the first occurrence
     */
    private function deduplicateItems(array $orders): array
    {
        $deduplicatedOrders = [];
        
        foreach ($orders as $orderId => $orderData) {
            $deduplicatedOrder = $orderData;
            
            // Check if this order has items to deduplicate
            if (isset($orderData['items']) && is_array($orderData['items'])) {
                $seenItems = [];
                $deduplicatedItems = [];
                
                foreach ($orderData['items'] as $item) {
                    // Prefer strong identity when available
                    $hasStrongId = isset($item['itm_id']) && isset($item['var_id']) && $item['itm_id'] !== '' && $item['var_id'] !== '';
                    if ($hasStrongId) {
                        $itemKey = (string)$item['itm_id'] . '_' . (string)$item['var_id'];
                    } else {
                        // Fallback: derive a key from normalized content to avoid collapsing distinct items
                        $name = isset($item['itm_name']) ? trim((string)$item['itm_name']) : '';
                        $composition = isset($item['composition']) ? trim((string)$item['composition']) : '';
                        // Normalize whitespace and case for stability
                        $norm = strtolower(preg_replace('/\s+/', ' ', $name . '|' . $composition));
                        // If even fallback is empty, use a unique per-item key to disable dedup
                        $itemKey = $norm !== '' ? 'fallback:' . $norm : uniqid('no-key-', true);
                    }
                    
                    if (!isset($seenItems[$itemKey])) {
                        $seenItems[$itemKey] = true;
                        $deduplicatedItems[] = $item;
                    } else {
                        $this->logger->info('Duplicate item removed', [
                            'order_id' => $orderId,
                            'itm_id' => $item['itm_id'] ?? 'unknown',
                            'var_id' => $item['var_id'] ?? 'unknown',
                            'item_name' => $item['itm_name'] ?? 'unknown',
                            'dedup_key' => $itemKey,
                            'has_strong_id' => $hasStrongId
                        ]);
                    }
                }
                
                $deduplicatedOrder['items'] = $deduplicatedItems;
            }
            
            $deduplicatedOrders[$orderId] = $deduplicatedOrder;
        }
        
        return $deduplicatedOrders;
    }

    /**
     * Recursively ensure UTF-8 encoding for all string values in the data
     */
    private function ensureUtf8Encoding($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'ensureUtf8Encoding'], $data);
        } elseif (is_string($data)) {
            // Ensure the string is properly UTF-8 encoded
            if (!mb_check_encoding($data, 'UTF-8')) {
                // If not UTF-8, try to convert from detected encoding
                $encoding = mb_detect_encoding($data, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                if ($encoding && $encoding !== 'UTF-8') {
                    $data = mb_convert_encoding($data, 'UTF-8', $encoding);
                }
            }
            return $data;
        }
        return $data;
    }
}
