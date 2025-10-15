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
            return $this->ensureUtf8Encoding($results);

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
