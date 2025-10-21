<?php
$active_orders = $active_orders ?? [];
$removed_order_ids = array_column($_SESSION['removed_orders'] ?? [], 'id');

// Filter out removed orders
$filtered_orders = array_filter($active_orders, function ($order) use ($removed_order_ids) {
    $order_id = $order['data']['ord_id'] ?? '';
    return !in_array($order_id, $removed_order_ids);
});

// Apply search filter
$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $filtered_orders = searchOrders($filtered_orders, $search_query);
}

// Apply date filter
$from = $_GET['from'] ?? getDefaultFromDate();
$to = $_GET['to'] ?? getDefaultToDate();
$filtered_orders = filterOrdersByDateRange($filtered_orders, $from, $to);

// Sort orders
$sort_direction = $_GET['sort'] ?? 'desc';
$filtered_orders = sortOrders($filtered_orders, $sort_direction);

// Apply limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
if ($limit && $limit > 0) {
    $filtered_orders = array_slice($filtered_orders, 0, $limit);
}
?>

<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Pedidos Ativos</h3>
            <div class="flex gap-2">
                <button onclick="selectAllOrders()" class="text-sm text-blue-600 hover:text-blue-800">
                    Selecionar Todos
                </button>
                <button onclick="removeSelectedOrders()" class="text-sm text-red-600 hover:text-red-800">
                    Remover Selecionados
                </button>
            </div>
        </div>

        <?php if (empty($filtered_orders)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>Nenhum pedido encontrado</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Cliente
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Documento
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Telefone
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Endereço
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($filtered_orders as $order): ?>
                            <?php
                            $summary = buildOrderSummary($order);
                            $order_id = $summary['id'];
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="order-checkbox" value="<?= htmlspecialchars($order_id) ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($order_id) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($summary['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($summary['document']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($summary['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($summary['phone']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($summary['address']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="removeOrder('<?= htmlspecialchars($order_id) ?>')"
                                        class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>