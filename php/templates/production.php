<?php
$production_items = $production_items ?? [];
$production_context = $production_context ?? '';

// Calculate totals
$totals = [
    'required' => 0,
    'produced' => 0,
    'remaining' => 0
];

foreach ($production_items as $item) {
    $totals['required'] += $item['totalQuantity'];
    $totals['produced'] += $item['producedQuantity'];
    $totals['remaining'] += $item['remainingQuantity'];
}
?>

<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold">Controle de Produção</h3>
            <div class="text-sm text-gray-500">
                Contexto: <?= htmlspecialchars($production_context) ?>
            </div>
        </div>

        <?php if (empty($production_items)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-industry text-4xl mb-4"></i>
                <p>Nenhum item para produção</p>
            </div>
        <?php else: ?>
            <!-- Production Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-list text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-blue-900"><?= number_format($totals['required']) ?></div>
                            <div class="text-sm text-blue-700">Total Necessário</div>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-green-900"><?= number_format($totals['produced']) ?></div>
                            <div class="text-sm text-green-700">Produzido</div>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold text-orange-900"><?= number_format($totals['remaining']) ?></div>
                            <div class="text-sm text-orange-700">Restante</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <?php if ($totals['required'] > 0): ?>
                <?php $progress_percentage = ($totals['produced'] / $totals['required']) * 100; ?>
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progresso da Produção</span>
                        <span><?= number_format($progress_percentage, 1) ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full transition-all duration-300"
                            style="width: <?= min(100, $progress_percentage) ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Production Items Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Necessário
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Produzido
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Restante
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Progresso
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($production_items as $item): ?>
                            <?php
                            $item_progress = $item['totalQuantity'] > 0 ? ($item['producedQuantity'] / $item['totalQuantity']) * 100 : 0;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($item['item']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= number_format($item['totalQuantity']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?= number_format($item['producedQuantity']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <?= number_format($item['remainingQuantity']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full"
                                                style="width: <?= min(100, $item_progress) ?>%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500"><?= number_format($item_progress, 1) ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="updateProductionQuantity('<?= htmlspecialchars($item['item']) ?>', <?= $item['totalQuantity'] ?>, <?= $item['producedQuantity'] ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
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

<!-- Production Update Modal -->
<div id="production-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Atualizar Produção</h3>
                    <button onclick="closeProductionModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="production-form">
                    <input type="hidden" id="production-item" name="item">
                    <input type="hidden" id="production-context" name="context" value="<?= htmlspecialchars($production_context) ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item</label>
                        <div id="production-item-name" class="text-sm text-gray-900 font-medium"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total Necessário</label>
                        <div id="production-total" class="text-sm text-gray-900"></div>
                    </div>

                    <div class="mb-4">
                        <label for="production-quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantidade Produzida
                        </label>
                        <input type="number" id="production-quantity" name="quantity" min="0"
                            class="w-full border border-gray-300 rounded px-3 py-2" required>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeProductionModal()"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Atualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>