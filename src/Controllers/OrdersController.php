<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\PurissimaApiService;
use App\Services\TcpdfService;

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

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createPrescriptionPdf($order['order'], $order['items']);
            
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
            
            // Check if all items have req field
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

            // This will output the PDF directly to browser and log the download
            $filename = $this->pdfService->createStickerPdf($orderData, $items);
            
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
			
			// This will output the PDF directly to browser and log the download
			$filename = $this->pdfService->createBatchPrescriptionPdf($orders);
			
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
}
