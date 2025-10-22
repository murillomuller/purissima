<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\PurissimaApiService;
use App\Services\LoggerService;
use App\Services\ProductionService;

class ProductionController extends BaseController
{
    private PurissimaApiService $purissimaApi;
    private ProductionService $productionService;

    public function __construct()
    {
        parent::__construct();
        $this->purissimaApi = new PurissimaApiService($this->logger);
        $this->productionService = new ProductionService($this->logger, $this->purissimaApi);
    }

    public function index(Request $request)
    {
        return $this->view('production/index', [
            'title' => 'Controle de Produção - Purissima',
            'production_items' => [],
            'production_context' => ''
        ]);
    }

    public function getProductionData(Request $request)
    {
        try {
            $filters = [
                'from' => $request->get('from'),
                'to' => $request->get('to'),
                'status' => $request->get('status'),
                'limit' => $request->get('limit') ? (int)$request->get('limit') : null
            ];

            $data = $this->productionService->getProductionData($filters);
            return $this->json($data);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get production data', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erro ao carregar dados de produção: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateProduction(Request $request)
    {
        try {
            $data = $request->getBody();
            $context = $data['context'] ?? '';
            $item = $data['item'] ?? '';
            $quantity = (int)($data['quantity'] ?? 0);

            $this->productionService->updateProduction($context, $item, $quantity);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update production', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erro ao atualizar produção: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeOrders(Request $request)
    {
        try {
            $data = $request->getBody();
            $orderIds = $data['order_ids'] ?? [];

            $removed = $this->productionService->removeOrders($orderIds);
            return $this->json(['success' => true, 'removed' => $removed]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove orders', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erro ao remover pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restoreOrders(Request $request)
    {
        try {
            $data = $request->getBody();
            $orderIds = $data['order_ids'] ?? [];

            $restored = $this->productionService->restoreOrders($orderIds);
            return $this->json(['success' => true, 'restored' => $restored]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to restore orders', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erro ao restaurar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRemovedOrders(Request $request)
    {
        try {
            $removedOrders = $this->productionService->getRemovedOrdersData();
            return $this->json([
                'success' => true,
                'orders' => $removedOrders
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get removed orders', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erro ao carregar pedidos removidos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function debugOrders(Request $request)
    {
        try {
            $orders = $this->purissimaApi->getOrders();
            $orderIds = array_keys($orders);
            $sampleIds = array_slice($orderIds, 0, 10);
            
            return $this->json([
                'success' => true,
                'total_orders' => count($orders),
                'sample_ids' => $sampleIds,
                'has_716' => in_array('716', $orderIds) || in_array(716, $orderIds),
                'all_ids' => $orderIds
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
