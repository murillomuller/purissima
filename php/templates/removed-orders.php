<?php
$removed_orders = $_SESSION['removed_orders'] ?? [];
$all_orders = $active_orders ?? [];

// Get full order data for removed orders
$removed_orders_data = [];
foreach ($removed_orders as $removed) {
    $order_id = $removed['id'];
    foreach ($all_orders as $order) {
        if (($order['data']['ord_id'] ?? '') === $order_id) {
            $removed_orders_data[] = $order;
            break;
        }
    }
}

// Apply search filter
$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $removed_orders_data = searchOrders($removed_orders_data, $search_query);
}

// Sort orders
$sort_direction = $_GET['sort'] ?? 'desc';
$removed_orders_data = sortOrders($removed_orders_data, $sort_direction);
?>

<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Pedidos Removidos</h3>
            <div class="flex gap-2">
                <button onclick="selectAllRemovedOrders()" class="text-sm text-blue-600 hover:text-blue-800">
                    Selecionar Todos
                </button>
                <button onclick="restoreSelectedOrders()" class="text-sm text-green-600 hover:text-green-800">
                    Restaurar Selecionados
                </button>
            </div>
        </div>

        <?php if (empty($removed_orders_data)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>Nenhum pedido removido</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-removed" onchange="toggleSelectAllRemoved(this)">
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
                                Removido em
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($removed_orders_data as $order): ?>
                            <?php
                            $summary = buildOrderSummary($order);
                            $order_id = $summary['id'];
                            $removed_info = array_find($removed_orders, function ($r) use ($order_id) {
                                return $r['id'] === $order_id;
                            });
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="removed-order-checkbox" value="<?= htmlspecialchars($order_id) ?>">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $removed_info ? formatDateToBrazilian($removed_info['removed_at']) : '—' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>)"
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="restoreOrder('<?= htmlspecialchars($order_id) ?>')"
                                        class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-undo"></i>
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

<?php
function array_find($array, $callback)
{
    foreach ($array as $item) {
        if ($callback($item)) {
            return $item;
        }
    }
    return null;
}
?>