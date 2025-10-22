<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\PurissimaApiService;
use App\Services\TcpdfService;
use GuzzleHttp\Client;

class OrdersController extends BaseController
{
    private PurissimaApiService $purissimaApi;
    private TcpdfService $pdfService;

    public function __construct()
    {
        parent::__construct();
        $this->purissimaApi = new PurissimaApiService($this->logger);
        $this->pdfService = new TcpdfService($this->logger);
    }

    public function index(Request $request)
    {
        return $this->view('orders/index', [
            'title' => 'Pedidos - Purissima',
            'orders' => []
        ]);
    }

    public function getOrdersApi(Request $request)
    {
        try {
            $orders = $this->purissimaApi->getOrders();

            $processedOrders = [];
            foreach ($orders as $orderId => $orderData) {
                if (isset($orderData['order']) && isset($orderData['items'])) {
                    $processedOrders[$orderId] = $orderData;
                } else {
                    $processedOrders[$orderId] = [
                        'order' => $orderData,
                        'items' => []
                    ];
                }
            }

            // Reverse the order and reindex numerically so JSON becomes a list, not an object
            $processedOrders = array_values(array_reverse($processedOrders, true));

            return $this->json([
                'success' => true,
                'orders' => $processedOrders
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load orders via API', [
                'error' => $e->getMessage()
            ]);

            $message = match (true) {
                str_contains($e->getMessage(), '500 Internal Server Error') =>
                'Servidor temporariamente indisponível. Tente novamente em alguns minutos.',
                str_contains($e->getMessage(), 'timeout') =>
                'Tempo limite excedido. Verifique sua conexão e tente novamente.',
                str_contains($e->getMessage(), 'connection') =>
                'Erro de conexão. Verifique sua internet e tente novamente.',
                default =>
                'Erro interno do servidor. Entre em contato com o suporte técnico.'
            };

            return $this->json(['success' => false, 'error' => $message], 500);
        }
    }

    public function generatePrescription(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            // Check if preview mode is requested
            $previewMode = filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN);

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createPrescriptionPdf($order['order'], $order['items'], $previewMode);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Prescription generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate prescription', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Failed to generate prescription: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewPrescription(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            // This will output the PDF for preview in browser
            $filename = $this->pdfService->previewPrescriptionPdf($order['order'], $order['items']);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Prescription preview generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview prescription', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Failed to preview prescription: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadPrescription(Request $request)
    {
        try {
            $filename = $request->getQuery('filename');
            if (empty($filename)) {
                return $this->json(['success' => false, 'error' => 'Filename is required'], 400);
            }

            $filePath = $this->pdfService->getPdfPath($filename);
            if (!file_exists($filePath)) {
                return $this->json(['success' => false, 'error' => 'File not found'], 404);
            }

            return $this->file($filePath, $filename);
        } catch (\Exception $e) {
            $this->logger->error('Failed to download prescription', [
                'filename' => $request->getQuery('filename'),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Failed to download prescription: ' . $e->getMessage()
            ], 404);
        }
    }

    public function generateSticker(Request $request)
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order || !isset($order['order'])) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            $orderData = $order['order'];
            $items = $order['items'] ?? [];

            // Check if all items have req field (skip in dev mode)
            $devMode = filter_var($_ENV['DEV_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            if (!$devMode) {
                $allItemsHaveReq = true;
                foreach ($items as $item) {
                    if (!isset($item['req']) || trim((string)$item['req']) === '') {
                        $allItemsHaveReq = false;
                        break;
                    }
                }

                if (!$allItemsHaveReq) {
                    return $this->json(['success' => false, 'error' => 'Todos os itens devem ter campo req preenchido'], 400);
                }
            } else {
                $this->logger->info('DEV_MODE enabled: Skipping REQ field validation for rótulo generation', [
                    'order_id' => $orderId
                ]);
            }

            // Get batch options from request or use defaults
            $options = [
                'page_format' => $request->get('page_format', 'A4'),
                'orientation' => $request->get('orientation', 'P'),
                'margin' => (float) $request->get('margin', 5),
                'spacing' => (float) $request->get('spacing', 2),
                'group_by_type' => filter_var($request->get('group_by_type', 'true'), FILTER_VALIDATE_BOOLEAN),
                'optimize_layout' => filter_var($request->get('optimize_layout', 'true'), FILTER_VALIDATE_BOOLEAN),
                'preview_mode' => filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN),
            ];

            // Prepare items with order data for batch processing
            $allItemsWithOrderData = [];
            foreach ($items as $item) {
                if ($devMode || (isset($item['req']) && trim((string)$item['req']) !== '')) {
                    $allItemsWithOrderData[] = [
                        'item' => $item,
                        'order_data' => $orderData
                    ];
                }
            }

            if (count($allItemsWithOrderData) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid items found for label generation'], 404);
            }

            // Use batch function to generate labels for single order
            $filename = $this->pdfService->createBatchLabelsPdf($allItemsWithOrderData, $options);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Sticker gerado com sucesso'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate sticker', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao gerar Sticker: ' . $e->getMessage()
            ], 500);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    public function getAppConfig(Request $request)
    {
        try {
            $devMode = filter_var($_ENV['DEV_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            return $this->json([
                'success' => true,
                'config' => [
                    'dev_mode' => $devMode,
                    'app_env' => $_ENV['APP_ENV'] ?? 'development'
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get app config', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao obter configuração: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateBatchPrescriptions(Request $request)
    {
        try {
            $ids = $request->get('order_ids');
            if (empty($ids)) {
                return $this->json(['success' => false, 'error' => 'Order IDs are required'], 400);
            }

            // Accept JSON array or comma-separated string
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)));
                }
            }

            if (!is_array($ids) || count($ids) === 0) {
                return $this->json(['success' => false, 'error' => 'Invalid order IDs'], 400);
            }

            $orders = [];
            foreach ($ids as $orderId) {
                try {
                    $order = $this->purissimaApi->getOrderById($orderId);
                    if ($order && isset($order['order'])) {
                        $orders[] = [
                            'order' => $order['order'],
                            'items' => $order['items'] ?? []
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipping order during batch generation', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($orders) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid orders found'], 404);
            }

            // Check if preview mode is requested
            $previewMode = filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN);

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createBatchPrescriptionPdf($orders, $previewMode);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Batch prescription generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate batch prescriptions', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate batch prescriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewBatchPrescriptions(Request $request)
    {
        try {
            $ids = $request->get('order_ids');
            if (empty($ids)) {
                return $this->json(['success' => false, 'error' => 'Order IDs are required'], 400);
            }

            // Accept JSON array or comma-separated string
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)));
                }
            }

            if (!is_array($ids) || count($ids) === 0) {
                return $this->json(['success' => false, 'error' => 'Invalid order IDs'], 400);
            }

            $orders = [];
            foreach ($ids as $orderId) {
                try {
                    $order = $this->purissimaApi->getOrderById($orderId);
                    if ($order && isset($order['order'])) {
                        $orders[] = [
                            'order' => $order['order'],
                            'items' => $order['items'] ?? []
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipping order during batch preview', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($orders) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid orders found'], 404);
            }

            // This will output the PDF for preview in browser
            $filename = $this->pdfService->previewBatchPrescriptionPdf($orders);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Batch prescription preview generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview batch prescriptions', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Failed to preview batch prescriptions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateBatchLabels(Request $request)
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            $ids = $request->get('order_ids');
            if (empty($ids)) {
                return $this->json(['success' => false, 'error' => 'Order IDs are required'], 400);
            }

            // Accept JSON array or comma-separated string
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)));
                }
            }

            if (!is_array($ids) || count($ids) === 0) {
                return $this->json(['success' => false, 'error' => 'Invalid order IDs'], 400);
            }

            // Get batch options from request
            $options = [
                'page_format' => $request->get('page_format', 'A4'),
                'orientation' => $request->get('orientation', 'P'),
                'margin' => (float) $request->get('margin', 5),
                'spacing' => (float) $request->get('spacing', 2),
                'group_by_type' => filter_var($request->get('group_by_type', 'true'), FILTER_VALIDATE_BOOLEAN),
                'optimize_layout' => filter_var($request->get('optimize_layout', 'true'), FILTER_VALIDATE_BOOLEAN),
                'preview_mode' => filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN),
            ];

            // Collect all items with their associated order data
            $allItemsWithOrderData = [];

            foreach ($ids as $orderId) {
                try {
                    $order = $this->purissimaApi->getOrderById($orderId);
                    if ($order && isset($order['order'])) {
                        $orderData = $order['order'];
                        $items = $order['items'] ?? [];

                        // Check if all items have req field (skip in dev mode)
                        $devMode = filter_var($_ENV['DEV_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

                        if (!$devMode) {
                            foreach ($items as $item) {
                                if (!isset($item['req']) || trim((string)$item['req']) === '') {
                                    $this->logger->warning('Skipping item without REQ field', [
                                        'order_id' => $orderId,
                                        'item' => $item
                                    ]);
                                    continue;
                                }
                                // Store item with its associated order data
                                $allItemsWithOrderData[] = [
                                    'item' => $item,
                                    'order_data' => $orderData
                                ];
                            }
                        } else {
                            foreach ($items as $item) {
                                // Store item with its associated order data
                                $allItemsWithOrderData[] = [
                                    'item' => $item,
                                    'order_data' => $orderData
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipping order during batch label generation', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($allItemsWithOrderData) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid items found for batch label generation'], 404);
            }

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createBatchLabelsPdf($allItemsWithOrderData, $options);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Batch labels generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate batch labels', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao gerar rótulos em lote: ' . $e->getMessage()
            ], 500);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    public function previewBatchLabels(Request $request)
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            $ids = $request->get('order_ids');
            if (empty($ids)) {
                return $this->json(['success' => false, 'error' => 'Order IDs are required'], 400);
            }

            // Accept JSON array or comma-separated string
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_filter(array_map('trim', explode(',', $ids)));
                }
            }

            if (!is_array($ids) || count($ids) === 0) {
                return $this->json(['success' => false, 'error' => 'Invalid order IDs'], 400);
            }

            // Get batch options from request with preview mode enabled
            $options = [
                'page_format' => $request->get('page_format', 'A4'),
                'orientation' => $request->get('orientation', 'P'),
                'margin' => (float) $request->get('margin', 5),
                'spacing' => (float) $request->get('spacing', 2),
                'group_by_type' => filter_var($request->get('group_by_type', 'true'), FILTER_VALIDATE_BOOLEAN),
                'optimize_layout' => filter_var($request->get('optimize_layout', 'true'), FILTER_VALIDATE_BOOLEAN),
                'preview_mode' => true, // Always true for preview
            ];

            // Collect all items with their associated order data
            $allItemsWithOrderData = [];

            foreach ($ids as $orderId) {
                try {
                    $order = $this->purissimaApi->getOrderById($orderId);
                    if ($order && isset($order['order'])) {
                        $orderData = $order['order'];
                        $items = $order['items'] ?? [];

                        // Check if all items have req field (skip in dev mode)
                        $devMode = filter_var($_ENV['DEV_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

                        if (!$devMode) {
                            foreach ($items as $item) {
                                if (!isset($item['req']) || trim((string)$item['req']) === '') {
                                    $this->logger->warning('Skipping item without REQ field', [
                                        'order_id' => $orderId,
                                        'item' => $item
                                    ]);
                                    continue;
                                }
                                // Store item with its associated order data
                                $allItemsWithOrderData[] = [
                                    'item' => $item,
                                    'order_data' => $orderData
                                ];
                            }
                        } else {
                            foreach ($items as $item) {
                                // Store item with its associated order data
                                $allItemsWithOrderData[] = [
                                    'item' => $item,
                                    'order_data' => $orderData
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipping order during batch label preview', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($allItemsWithOrderData) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid items found for batch label preview'], 404);
            }

            // This will output the PDF for preview in browser
            $filename = $this->pdfService->previewBatchLabelsPdf($allItemsWithOrderData, $options);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Batch labels preview generated successfully'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview batch labels', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao visualizar rótulos em lote: ' . $e->getMessage()
            ], 500);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    public function generateShippingLabel(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order || !isset($order['order'])) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            $orderData = $order['order'];

            // Check if ord_shipping_shipment_id is filled
            if (empty($orderData['ord_shipping_shipment_id'])) {
                return $this->json(['success' => false, 'error' => 'Shipping shipment ID is not available for this order'], 400);
            }

            // Make secure backend call to the shipping label API
            $apiUrl = 'https://api.purissima.com/labels/lacre-preview.php';
            $params = ['ord' => $orderId];

            $client = new Client([
                'timeout' => 30,
                'verify' => false // For development - should be true in production
            ]);

            $response = $client->get($apiUrl, [
                'query' => $params,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $contentType = $response->getHeader('Content-Type')[0] ?? '';

                // Set appropriate headers for PDF response
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="etiqueta_envio_' . $orderId . '.pdf"');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');

                // Output the PDF content directly
                echo $response->getBody()->getContents();
                exit;
            } else {
                $this->logger->error('Failed to generate shipping label', [
                    'order_id' => $orderId,
                    'status_code' => $statusCode,
                    'response' => $response->getBody()->getContents()
                ]);

                return $this->json([
                    'success' => false,
                    'error' => 'Failed to generate shipping label. Status: ' . $statusCode
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate shipping label', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Falha ao gerar etiqueta de envio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewShippingLabel(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order || !isset($order['order'])) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            $orderData = $order['order'];

            // Check if ord_shipping_shipment_id is filled
            if (empty($orderData['ord_shipping_shipment_id'])) {
                return $this->json(['success' => false, 'error' => 'Shipping shipment ID is not available for this order'], 400);
            }

            // Make secure backend call to the shipping label API
            $apiUrl = 'https://api.purissima.com/labels/lacre-preview.php';
            $params = ['ord' => $orderId];

            $client = new Client([
                'timeout' => 30,
                'verify' => false // For development - should be true in production
            ]);

            $response = $client->get($apiUrl, [
                'query' => $params,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $contentType = $response->getHeader('Content-Type')[0] ?? '';

                // Set appropriate headers for PDF preview in browser
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: inline; filename="etiqueta_envio_' . $orderId . '.pdf"');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');

                // Output the PDF content directly for preview
                echo $response->getBody()->getContents();
                exit;
            } else {
                $this->logger->error('Failed to preview shipping label', [
                    'order_id' => $orderId,
                    'status_code' => $statusCode,
                    'response' => $response->getBody()->getContents()
                ]);

                return $this->json([
                    'success' => false,
                    'error' => 'Failed to preview shipping label. Status: ' . $statusCode
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview shipping label', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Falha ao visualizar etiqueta de envio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewSticker(Request $request)
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            $orderId = $request->get('order_id');
            if (empty($orderId)) {
                return $this->json(['success' => false, 'error' => 'Order ID is required'], 400);
            }

            $order = $this->purissimaApi->getOrderById($orderId);
            if (!$order || !isset($order['order'])) {
                return $this->json(['success' => false, 'error' => 'Order not found'], 404);
            }

            $orderData = $order['order'];
            $items = $order['items'] ?? [];

            // Check if all items have req field (skip in dev mode)
            $devMode = filter_var($_ENV['DEV_MODE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

            if (!$devMode) {
                $allItemsHaveReq = true;
                foreach ($items as $item) {
                    if (!isset($item['req']) || trim((string)$item['req']) === '') {
                        $allItemsHaveReq = false;
                        break;
                    }
                }

                if (!$allItemsHaveReq) {
                    return $this->json(['success' => false, 'error' => 'Todos os itens devem ter campo req preenchido'], 400);
                }
            } else {
                $this->logger->info('DEV_MODE enabled: Skipping REQ field validation for rótulo preview', [
                    'order_id' => $orderId
                ]);
            }

            // Get batch options from request or use defaults with preview mode enabled
            $options = [
                'page_format' => $request->get('page_format', 'A4'),
                'orientation' => $request->get('orientation', 'P'),
                'margin' => (float) $request->get('margin', 5),
                'spacing' => (float) $request->get('spacing', 2),
                'group_by_type' => filter_var($request->get('group_by_type', 'true'), FILTER_VALIDATE_BOOLEAN),
                'optimize_layout' => filter_var($request->get('optimize_layout', 'true'), FILTER_VALIDATE_BOOLEAN),
                'preview_mode' => true, // Always true for preview
            ];

            // Prepare items with order data for batch processing
            $allItemsWithOrderData = [];
            foreach ($items as $item) {
                if ($devMode || (isset($item['req']) && trim((string)$item['req']) !== '')) {
                    $allItemsWithOrderData[] = [
                        'item' => $item,
                        'order_data' => $orderData
                    ];
                }
            }

            if (count($allItemsWithOrderData) === 0) {
                return $this->json(['success' => false, 'error' => 'No valid items found for label preview'], 404);
            }

            // Use batch function to generate labels for single order preview
            $filename = $this->pdfService->previewBatchLabelsPdf($allItemsWithOrderData, $options);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Sticker preview gerado com sucesso'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview sticker', [
                'order_id' => $request->get('order_id'),
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao visualizar Sticker: ' . $e->getMessage()
            ], 500);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    public function generateLastDayReceituarios(Request $request)
    {
        try {
            // Get yesterday's date
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $this->logger->info('Generating receituarios for last day', [
                'date' => $yesterday
            ]);

            // Get all orders
            $allOrders = $this->purissimaApi->getOrders();

            // Filter orders from yesterday
            $yesterdayOrders = [];
            foreach ($allOrders as $orderId => $orderData) {
                if (isset($orderData['order']['created_at'])) {
                    $orderDate = date('Y-m-d', strtotime($orderData['order']['created_at']));
                    if ($orderDate === $yesterday) {
                        $yesterdayOrders[] = [
                            'order' => $orderData['order'],
                            'items' => $orderData['items'] ?? []
                        ];
                    }
                }
            }

            if (count($yesterdayOrders) === 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Nenhum pedido encontrado para ontem (' . $yesterday . ')'
                ], 404);
            }

            $this->logger->info('Found orders for yesterday', [
                'date' => $yesterday,
                'count' => count($yesterdayOrders)
            ]);

            // Check if preview mode is requested
            $previewMode = filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN);

            // Generate batch prescription PDF for yesterday's orders
            $filename = $this->pdfService->createBatchPrescriptionPdf($yesterdayOrders, $previewMode);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Receituários de ontem gerados com sucesso',
                'orders_count' => count($yesterdayOrders),
                'date' => $yesterday
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate last day receituarios', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao gerar receituários de ontem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewLastDayReceituarios(Request $request)
    {
        try {
            // Get yesterday's date
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $this->logger->info('Previewing receituarios for last day', [
                'date' => $yesterday
            ]);

            // Get all orders
            $allOrders = $this->purissimaApi->getOrders();

            // Filter orders from yesterday
            $yesterdayOrders = [];
            foreach ($allOrders as $orderId => $orderData) {
                if (isset($orderData['order']['created_at'])) {
                    $orderDate = date('Y-m-d', strtotime($orderData['order']['created_at']));
                    if ($orderDate === $yesterday) {
                        $yesterdayOrders[] = [
                            'order' => $orderData['order'],
                            'items' => $orderData['items'] ?? []
                        ];
                    }
                }
            }

            if (count($yesterdayOrders) === 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Nenhum pedido encontrado para ontem (' . $yesterday . ')'
                ], 404);
            }

            $this->logger->info('Found orders for yesterday preview', [
                'date' => $yesterday,
                'count' => count($yesterdayOrders)
            ]);

            // Generate batch prescription PDF preview for yesterday's orders
            $filename = $this->pdfService->previewBatchPrescriptionPdf($yesterdayOrders);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Pré-visualização dos receituários de ontem gerada com sucesso',
                'orders_count' => count($yesterdayOrders),
                'date' => $yesterday
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to preview last day receituarios', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao visualizar receituários de ontem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLastDayOrdersForLabels(Request $request)
    {
        try {
            // Get yesterday's date
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $this->logger->info('Getting yesterday orders for labels', [
                'date' => $yesterday
            ]);

            // Get all orders
            $allOrders = $this->purissimaApi->getOrders();

            // Filter orders from yesterday and check REQ fields
            $ordersWithReq = [];
            $ordersWithoutReq = [];

            foreach ($allOrders as $orderId => $orderData) {
                if (isset($orderData['order']['created_at'])) {
                    $orderDate = date('Y-m-d', strtotime($orderData['order']['created_at']));
                    if ($orderDate === $yesterday) {
                        $order = $orderData['order'];
                        $items = $orderData['items'] ?? [];

                        // Check if all items have REQ field filled
                        $allItemsHaveReq = true;
                        $itemsWithoutReq = [];

                        foreach ($items as $item) {
                            if (!isset($item['req']) || trim((string)$item['req']) === '') {
                                $allItemsHaveReq = false;
                                $itemsWithoutReq[] = $item['itm_name'] ?? 'Item sem nome';
                            }
                        }

                        $orderInfo = [
                            'order_id' => $order['ord_id'],
                            'customer_name' => $order['usr_name'],
                            'customer_email' => $order['usr_email'],
                            'items_count' => count($items),
                            'created_at' => $order['created_at']
                        ];

                        if ($allItemsHaveReq) {
                            $ordersWithReq[] = $orderInfo;
                        } else {
                            $orderInfo['missing_req_items'] = $itemsWithoutReq;
                            $ordersWithoutReq[] = $orderInfo;
                        }
                    }
                }
            }

            return $this->json([
                'success' => true,
                'date' => $yesterday,
                'orders_with_req' => $ordersWithReq,
                'orders_without_req' => $ordersWithoutReq,
                'total_orders' => count($ordersWithReq) + count($ordersWithoutReq),
                'can_generate' => count($ordersWithReq) > 0
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get last day orders for labels', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao obter pedidos de ontem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateLastDayLabels(Request $request)
    {
        // Suppress error output to prevent "headers already sent" error
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);

        try {
            // Get yesterday's date
            $yesterday = date('Y-m-d', strtotime('-1 day'));

            $this->logger->info('Generating labels for last day', [
                'date' => $yesterday
            ]);

            // Get all orders
            $allOrders = $this->purissimaApi->getOrders();

            // Filter orders from yesterday that have all REQ fields filled
            $validOrders = [];
            foreach ($allOrders as $orderId => $orderData) {
                if (isset($orderData['order']['created_at'])) {
                    $orderDate = date('Y-m-d', strtotime($orderData['order']['created_at']));
                    if ($orderDate === $yesterday) {
                        $order = $orderData['order'];
                        $items = $orderData['items'] ?? [];

                        // Check if all items have REQ field filled
                        $allItemsHaveReq = true;
                        foreach ($items as $item) {
                            if (!isset($item['req']) || trim((string)$item['req']) === '') {
                                $allItemsHaveReq = false;
                                break;
                            }
                        }

                        if ($allItemsHaveReq) {
                            $validOrders[] = $orderId;
                        }
                    }
                }
            }

            if (count($validOrders) === 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Nenhum pedido válido encontrado para ontem (todos os itens devem ter campo REQ preenchido)'
                ], 404);
            }

            // Get batch options from request
            $options = [
                'page_format' => $request->get('page_format', 'A4'),
                'orientation' => $request->get('orientation', 'P'),
                'margin' => (float) $request->get('margin', 5),
                'spacing' => (float) $request->get('spacing', 2),
                'group_by_type' => filter_var($request->get('group_by_type', 'true'), FILTER_VALIDATE_BOOLEAN),
                'optimize_layout' => filter_var($request->get('optimize_layout', 'true'), FILTER_VALIDATE_BOOLEAN),
                'preview_mode' => filter_var($request->get('preview', 'false'), FILTER_VALIDATE_BOOLEAN),
            ];

            // Collect all items with their associated order data
            $allItemsWithOrderData = [];

            foreach ($validOrders as $orderId) {
                try {
                    $order = $this->purissimaApi->getOrderById($orderId);
                    if ($order && isset($order['order'])) {
                        $orderData = $order['order'];
                        $items = $order['items'] ?? [];

                        foreach ($items as $item) {
                            // Store item with its associated order data
                            $allItemsWithOrderData[] = [
                                'item' => $item,
                                'order_data' => $orderData
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Skipping order during last day label generation', [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (count($allItemsWithOrderData) === 0) {
                return $this->json(['success' => false, 'error' => 'Nenhum item válido encontrado para geração de rótulos'], 404);
            }

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createBatchLabelsPdf($allItemsWithOrderData, $options);

            // This line should never be reached as the PDF is output directly
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'message' => 'Rótulos de ontem gerados com sucesso',
                'orders_count' => count($validOrders),
                'date' => $yesterday
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate last day labels', [
                'error' => $e->getMessage()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Falha ao gerar rótulos de ontem: ' . $e->getMessage()
            ], 500);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }
}
