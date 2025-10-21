<?php
session_start();

// Configuration
$config = [
    'api_url' => $_ENV['PURISSIMA_ORDERS_URL'] ?? 'https://api.purissima.com/provisorio/pedidos-dia.php',
    'default_status' => 'released',
    'filter_min_date' => '2025-10-08T20:00',
    'default_lookback_days' => 30
];

// Include utility functions
require_once 'includes/functions.php';
require_once 'includes/order-utils.php';

// Get current date range
$from = $_GET['from'] ?? getDefaultFromDate();
$to = $_GET['to'] ?? getDefaultToDate();
$status = $_GET['status'] ?? $config['default_status'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

// Initialize session data if not exists
if (!isset($_SESSION['orders'])) {
    $_SESSION['orders'] = [];
    $_SESSION['removed_orders'] = [];
    $_SESSION['production_state'] = [];
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'fetch_orders':
            echo json_encode(fetchOrders($from, $to, $status, $limit));
            exit;

        case 'remove_orders':
            $order_ids = json_decode($_POST['order_ids'] ?? '[]', true);
            echo json_encode(removeOrders($order_ids));
            exit;

        case 'restore_orders':
            $order_ids = json_decode($_POST['order_ids'] ?? '[]', true);
            echo json_encode(restoreOrders($order_ids));
            exit;

        case 'update_production':
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateProduction($data));
            exit;
    }
}

// Get orders data
$orders_data = fetchOrders($from, $to, $status, $limit);
$active_orders = $orders_data['orders'] ?? [];
$removed_orders = $_SESSION['removed_orders'] ?? [];

// Filter out removed orders from active
$active_orders = array_filter($active_orders, function ($order) use ($removed_orders) {
    $order_id = $order['data']['ord_id'] ?? '';
    return !in_array($order_id, array_column($removed_orders, 'id'));
});

// Build item aggregates
$item_aggregates = buildItemAggregates($active_orders);

// Get production data
$production_context = "range:{$from}::{$to}";
$production_items = getProductionItems($active_orders, $production_context);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purissima Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .print-hidden {
            display: block;
        }

        @media print {
            .print-hidden {
                display: none !important;
            }

            .print-visible {
                display: block !important;
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="max-w-6xl mx-auto p-4 lg:p-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border print-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-3xl font-semibold text-gray-900">Purissima</h1>
                        <p class="text-lg text-gray-600">Pedidos Pagos</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="refreshOrders()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            <i class="fas fa-sync-alt mr-2"></i>Atualizar
                        </button>
                        <button onclick="printSelected()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            <i class="fas fa-print mr-2"></i>Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                        <input type="datetime-local" id="from-date" value="<?= htmlspecialchars($from) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                        <input type="datetime-local" id="to-date" value="<?= htmlspecialchars($to) ?>"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Limite</label>
                        <input type="number" id="limit" placeholder="Sem limite"
                            class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <input type="text" id="search" placeholder="Buscar por ID, nome, documento..."
                        class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="switchTab('ativos')" class="tab-button active py-2 px-1 border-b-2 font-medium text-sm" data-tab="ativos">
                            Pedidos Ativos (<?= count($active_orders) ?>)
                        </button>
                        <button onclick="switchTab('removidos')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm" data-tab="removidos">
                            Pedidos Removidos (<?= count($removed_orders) ?>)
                        </button>
                        <button onclick="switchTab('itens')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm" data-tab="itens">
                            Itens Agregados (<?= count($item_aggregates) ?>)
                        </button>
                        <button onclick="switchTab('producao')" class="tab-button py-2 px-1 border-b-2 font-medium text-sm" data-tab="producao">
                            Produção
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="mt-6">
            <!-- Active Orders Tab -->
            <div id="tab-ativos" class="tab-content">
                <?php include 'templates/active-orders.php'; ?>
            </div>

            <!-- Removed Orders Tab -->
            <div id="tab-removidos" class="tab-content hidden">
                <?php include 'templates/removed-orders.php'; ?>
            </div>

            <!-- Items Tab -->
            <div id="tab-itens" class="tab-content hidden">
                <?php include 'templates/items-aggregates.php'; ?>
            </div>

            <!-- Production Tab -->
            <div id="tab-producao" class="tab-content hidden">
                <?php include 'templates/production.php'; ?>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div id="order-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Detalhes do Pedido</h3>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="order-details"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>

</html>