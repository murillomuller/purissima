<?php
$item_aggregates = $item_aggregates ?? [];

// Apply search filter
$search_query = $_GET['search'] ?? '';
if ($search_query) {
    $item_aggregates = array_filter($item_aggregates, function ($item) use ($search_query) {
        return stripos($item['item'], $search_query) !== false;
    });
}

// Sort by item name
usort($item_aggregates, function ($a, $b) {
    return strcmp($a['item'], $b['item']);
});

// Apply limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
if ($limit && $limit > 0) {
    $item_aggregates = array_slice($item_aggregates, 0, $limit);
}
?>

<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Itens Agregados</h3>
            <div class="flex gap-2">
                <button onclick="selectAllItems()" class="text-sm text-blue-600 hover:text-blue-800">
                    Selecionar Todos
                </button>
                <button onclick="downloadItemsReport()" class="text-sm text-green-600 hover:text-green-800">
                    <i class="fas fa-download mr-1"></i>Exportar Excel
                </button>
            </div>
        </div>

        <?php if (empty($item_aggregates)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-box text-4xl mb-4"></i>
                <p>Nenhum item encontrado</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-items" onchange="toggleSelectAllItems(this)">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantidade Total
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pedidos
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($item_aggregates as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="item-checkbox" value="<?= htmlspecialchars($item['item']) ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($item['item']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= number_format($item['totalQuantity']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= count($item['orders']) ?> pedido<?= count($item['orders']) !== 1 ? 's' : '' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewItemDetails('<?= htmlspecialchars($item['item']) ?>', <?= htmlspecialchars(json_encode($item)) ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?= count($item_aggregates) ?></div>
                        <div class="text-sm text-gray-500">Itens Únicos</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">
                            <?= number_format(array_sum(array_column($item_aggregates, 'totalQuantity'))) ?>
                        </div>
                        <div class="text-sm text-gray-500">Quantidade Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">
                            <?= number_format(array_sum(array_map(function ($item) {
                                return count($item['orders']);
                            }, $item_aggregates))) ?>
                        </div>
                        <div class="text-sm text-gray-500">Total de Pedidos</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>