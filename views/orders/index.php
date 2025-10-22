<?php
$content = ob_get_clean();
$fullWidth = true;
$title = 'Gestão de Pedidos';
ob_start();
?>

<div class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Filters Section - Above Header -->
    <div class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="px-2 sm:px-4 lg:px-8 py-6">
            <!-- Section Title -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1.994 1.994 0 013 7V4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Filtros e Busca</h3>
                </div>
                <button onclick="clearFilters()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span>Limpar Filtros</span>
                </button>
            </div>

            <div class="flex flex-col space-y-4">
                <!-- Search Bar -->
                <div class="flex-1">
                    <div class="relative">
                        <input id="searchInput" type="text" placeholder="Buscar pedidos..." class="w-full px-4 py-3 rounded-lg text-sm placeholder-gray-300 text-gray-800 focus:outline-none focus:ring-2 focus:ring-white/60 focus:bg-white/90 bg-white/80 transition-opacity duration-200" />
                        <div id="searchIcon" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                            </svg>
                        </div>
                        <div id="searchSpinner" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hidden">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- REQ Status Filter -->
                    <div class="flex flex-col sm:w-48">
                        <label class="text-white text-sm font-semibold mb-2">Status REQ</label>
                        <select id="reqStatusFilter" onchange="applyFilters()" class="bg-white/90 text-gray-800 text-sm rounded-lg px-3 py-2 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm">
                            <option value="all">Todos os pedidos</option>
                            <option value="complete">REQ completo</option>
                            <option value="incomplete">REQ incompleto</option>
                        </select>
                    </div>

                    <!-- Date From Filter -->
                    <div class="flex flex-col sm:w-48">
                        <label class="text-white text-sm font-semibold mb-2">Data inicial</label>
                        <input id="dateFromFilter" type="date" onchange="applyFilters()" class="bg-white/90 text-gray-800 text-sm rounded-lg px-3 py-2 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm" />
                    </div>

                    <!-- Date To Filter -->
                    <div class="flex flex-col sm:w-48">
                        <label class="text-white text-sm font-semibold mb-2">Data final</label>
                        <input id="dateToFilter" type="date" onchange="applyFilters()" class="bg-white/90 text-gray-800 text-sm rounded-lg px-3 py-2 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm" />
                    </div>

                    <!-- Rotulo Status Filter -->
                    <div class="flex flex-col sm:w-48">
                        <label class="text-white text-sm font-semibold mb-2">Status Rótulo</label>
                        <select id="rotuloStatusFilter" onchange="applyFilters()" class="bg-white/90 text-gray-800 text-sm rounded-lg px-3 py-2 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm">
                            <option value="all">Todos os pedidos</option>
                            <option value="generated">Rótulos gerados</option>
                            <option value="not_generated">Rótulos não gerados</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="px-2 sm:px-4 lg:px-8 py-4 sm:py-6 max-w-full">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end mb-4 sm:mb-6 gap-3 sm:gap-3">
            <!-- Last Day Receituarios Button -->
            <div class="relative inline-block text-left">
                <button onclick="toggleLastDayReceituariosDropdown()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Receituários de Ontem</span>
                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="lastDayReceituariosDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                    <div class="py-1">
                        <button onclick="generateLastDayReceituarios()" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Baixar PDF
                        </button>
                        <button onclick="previewLastDayReceituarios()" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Visualizar PDF
                        </button>
                    </div>
                </div>
            </div>
            <!-- Last Day Labels Button -->
            <button onclick="showLastDayLabelsModal()"
                class="bg-orange-600 hover:bg-orange-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <span class="text-sm sm:text-base">Rótulos de Ontem</span>
            </button>
            <!-- Action Button -->
            <button onclick="refreshOrders()"
                class="bg-primary hover:bg-secondary text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm sm:text-base">Atualizar Pedidos</span>
            </button>
        </div>
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-16">
            <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary"></div>
            </div>
            <div class="flex items-center justify-center space-x-2 mb-4">
                <svg class="w-6 h-6 text-primary animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900">Carregando pedidos...</h3>
            </div>
            <p class="text-gray-600 text-lg">Aguarde enquanto buscamos os dados dos pedidos.</p>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="bg-red-50 border-l-4 border-red-400 rounded-lg p-6 mb-8 shadow-md hidden">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-red-800 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Erro
                    </h3>
                    <div id="errorText" class="mt-2 text-red-700"></div>
                </div>
            </div>
        </div>


        <!-- Orders Table -->
        <div id="ordersTable" class="bg-white rounded-xl shadow-lg overflow-hidden hidden w-full">
            <div class="px-3 sm:px-6 py-4 bg-gradient-to-r from-primary to-secondary">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <h2 class="text-lg sm:text-xl font-bold text-white">Lista de Pedidos</h2>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-3 w-full lg:w-auto">
                        <div class="flex items-center justify-between sm:justify-end space-x-3">
                            <div class="text-white/80 text-sm" id="selectedCount">0 selecionado(s)</div>
                            <div class="flex items-center space-x-2">
                                <label class="text-white/80 text-xs sm:text-sm">Por página:</label>
                                <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="bg-white/90 text-gray-800 text-xs sm:text-sm rounded px-2 py-1 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm">
                                    <option value="10">10</option>
                                    <option value="20" selected>20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="relative inline-block text-left">
                                <button id="bulkGenerateBtn" disabled onclick="toggleBatchPrescriptionsDropdown()" class="bg-white/20 hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold flex items-center space-x-1 sm:space-x-2 transition-colors duration-200 whitespace-nowrap">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Receituário em Lote</span>
                                    <span class="sm:hidden">Lote</span>
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="batchPrescriptionsDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                    <div class="py-1">
                                        <button id="bulkPrescriptionsDownloadBtn" disabled class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Baixar PDF
                                        </button>
                                        <button id="bulkPrescriptionsPreviewBtn" disabled class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Visualizar PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="relative inline-block text-left">
                                <button id="bulkLabelsBtn" disabled onclick="toggleBatchLabelsDropdown()" class="bg-white/20 hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed text-white px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold flex items-center space-x-1 sm:space-x-2 transition-colors duration-200 whitespace-nowrap">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Rótulos em Lote</span>
                                    <span class="sm:hidden">Rótulos</span>
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div id="batchLabelsDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                    <div class="py-1">
                                        <button id="bulkLabelsDownloadBtn" disabled class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Baixar PDF
                                        </button>
                                        <button id="bulkLabelsPreviewBtn" disabled class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            Visualizar PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2 mt-3">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <p id="ordersCount" class="text-white/80 text-sm sm:text-base">0 pedido(s) encontrado(s)</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 sm:px-4 py-3 sm:py-4">
                                <input id="selectAll" type="checkbox" class="h-4 w-4 border-gray-300 rounded">
                            </th>
                            <?php
                            $columns = [
                                'ord_id' => 'ID',
                                'usr_name' => 'Cliente',
                                'usr_email' => 'E-mail',
                                'chg_status' => 'Status',
                                'items_count' => 'Itens',
                                'created_at' => 'Data'
                            ];
                            foreach ($columns as $key => $label) {
                                echo "
                                <th onclick=\"sortTable('$key')\" class='px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200 whitespace-nowrap'>
                                    <div class='flex items-center space-x-1'>
                                        <span>$label</span>
                                        <div id='sort-$key' class='flex flex-col'>
                                            <svg class='w-3 h-3 text-gray-400' fill='currentColor' viewBox='0 0 20 20'>
                                                <path d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z'></path>
                                            </svg>
                                        </div>
                                    </div>
                                </th>";
                            }
                            ?>
                            <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-1">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Ações</span>
                                    <span class="sm:hidden">Ações</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="prevPage" onclick="prevPage()" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Anterior
                    </button>
                    <button id="nextPage" onclick="nextPage()" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Próximo
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p id="paginationInfo" class="text-sm text-gray-700">
                            Mostrando 1-20 de 100 pedidos
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="prevPage" onclick="prevPage()" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">Anterior</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="pageNumbers" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                <!-- Page numbers will be inserted here -->
                            </div>
                            <button id="nextPage" onclick="nextPage()" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">Próximo</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-2 sm:top-10 mx-auto p-3 sm:p-5 border w-11/12 sm:w-10/12 md:w-3/4 lg:w-1/2 xl:w-2/5 shadow-2xl rounded-xl bg-white max-h-[95vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-gray-200">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"></path>
                </svg>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-primary truncate">Detalhes do Pedido</h3>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-lg hover:bg-gray-100 flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="orderDetails" class="space-y-4 sm:space-y-6"></div>
    </div>
</div>

<!-- Last Day Labels Modal -->
<div id="lastDayLabelsModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-2 sm:top-10 mx-auto p-3 sm:p-5 border w-11/12 sm:w-10/12 md:w-3/4 lg:w-2/3 xl:w-1/2 shadow-2xl rounded-xl bg-white max-h-[95vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-gray-200">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-orange-600 truncate">Rótulos de Ontem</h3>
            </div>
            <button onclick="closeLastDayLabelsModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-lg hover:bg-gray-100 flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="lastDayLabelsContent" class="space-y-4 sm:space-y-6">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl p-8 flex flex-col items-center space-y-4 shadow-2xl max-w-sm">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <div class="text-center">
            <div class="flex items-center justify-center space-x-2 mb-2">
                <svg class="w-6 h-6 text-primary animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span id="loadingMessage" class="text-gray-700 text-lg font-semibold">Gerando...</span>
            </div>
            <p class="text-gray-600 text-sm">Aguarde enquanto processamos o pedido...</p>
        </div>
    </div>
</div>

<!-- Rotulo Generation Warning Modal -->
<div id="rotuloWarningModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-2 sm:top-10 mx-auto p-3 sm:p-5 border w-11/12 sm:w-10/12 md:w-3/4 lg:w-1/2 xl:w-2/5 shadow-2xl rounded-xl bg-white max-h-[95vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sm:mb-6 pb-3 sm:pb-4 border-b border-gray-200">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-yellow-600 truncate">Aviso - Rótulo Já Gerado</h3>
            </div>
            <button onclick="closeRotuloWarningModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-lg hover:bg-gray-100 flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Rótulo(s) já foi(ram) gerado(s) anteriormente
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p id="rotuloWarningMessage">Os seguintes pedidos já tiveram seus rótulos gerados:</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="rotuloWarningOrders" class="space-y-2">
                <!-- Orders list will be populated here -->
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-700">
                    <strong>Deseja continuar?</strong> Gerar novamente substituirá os rótulos anteriores.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button onclick="closeRotuloWarningModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200">
                    Cancelar
                </button>
                <button onclick="confirmRotuloGeneration()" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200">
                    Continuar e Gerar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let ordersData = [];
    let currentSort = {
        column: 'ord_id',
        direction: 'desc'
    };
    let searchQuery = '';
    let isSearching = false;
    let searchTimeout = null;
    let appConfig = {
        dev_mode: false,
        app_env: 'development'
    };

    // Search performance optimization
    let searchIndex = new Map(); // Pre-built search index
    let lastSearchTime = 0;
    let searchDebounceDelay = 100; // Reduced for better responsiveness

    // Load app configuration
    async function loadAppConfig() {
        try {
            const response = await fetch('/orders/config');
            const data = await response.json();
            if (data.success) {
                appConfig = data.config;
                console.log('App config loaded:', appConfig);

                // Show dev mode indicator if enabled
                if (appConfig.dev_mode) {
                    showDevModeIndicator();
                }
            }
        } catch (error) {
            console.error('Failed to load app config:', error);
        }
    }

    // Show dev mode indicator
    function showDevModeIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'dev-mode-indicator';
        indicator.className = 'fixed top-4 right-4 bg-yellow-500 text-yellow-900 px-3 py-2 rounded-lg shadow-lg z-50 flex items-center space-x-2';
        indicator.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <span class="font-semibold text-sm">DEV MODE</span>
    `;
        document.body.appendChild(indicator);
    }

    // Pagination system
    let currentPage = 1;
    let itemsPerPage = 20; // Configurable page size
    let totalPages = 1;
    let totalItems = 0;
    let paginatedData = [];

    // Global selection state that persists across pagination
    let globalSelectedIds = new Set();

    // Pre-cache DOM elements for better performance
    let cachedElements = {
        searchInput: null,
        searchIcon: null,
        searchSpinner: null,
        loadingState: null,
        ordersTable: null,
        ordersCount: null,
        ordersTableBody: null
    };

    function initializeCachedElements() {
        cachedElements.searchInput = document.getElementById('searchInput');
        cachedElements.searchIcon = document.getElementById('searchIcon');
        cachedElements.searchSpinner = document.getElementById('searchSpinner');
        cachedElements.loadingState = document.getElementById('loadingState');
        cachedElements.ordersTable = document.getElementById('ordersTable');
        cachedElements.ordersCount = document.getElementById('ordersCount');
        cachedElements.ordersTableBody = document.getElementById('ordersTableBody');
    }

    function buildSearchIndex(orders) {
        searchIndex.clear();

        orders.forEach((entry, index) => {
            const o = entry.order || {};
            const items = entry.items || [];

            // Create searchable text for each order
            const searchableText = [
                o.ord_id,
                o.usr_name,
                o.usr_email,
                o.usr_cpf,
                o.usr_phone,
                o.chg_status,
                o.created_at,
                ...items.map(item => [
                    item.itm_name,
                    item.composition,
                    item.req
                ].filter(Boolean).join(' '))
            ].filter(Boolean).join(' ').toLowerCase();

            // Index by words for faster searching
            const words = searchableText.split(/\s+/).filter(word => word.length > 0);
            words.forEach(word => {
                if (!searchIndex.has(word)) {
                    searchIndex.set(word, new Set());
                }
                searchIndex.get(word).add(index);
            });

            // Also index the full text for partial matches
            searchIndex.set(`full_${index}`, searchableText);
        });
    }

    function refreshOrders() {
        loadOrders();
    }

    function paginateData(data) {
        totalItems = data.length;
        totalPages = Math.ceil(totalItems / itemsPerPage);

        // Ensure current page is valid
        if (currentPage > totalPages) {
            currentPage = Math.max(1, totalPages);
        }

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        paginatedData = data.slice(startIndex, endIndex);
        return paginatedData;
    }

    function updatePaginationInfo() {
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            if (totalItems === 0) {
                paginationInfo.textContent = 'Nenhum item encontrado';
            } else {
                paginationInfo.textContent = `Mostrando ${startItem}-${endItem} de ${totalItems} pedidos`;
            }
        }
    }

    function updatePaginationControls() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageNumbers = document.getElementById('pageNumbers');

        // Update prev/next buttons
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
            prevBtn.classList.toggle('opacity-50', currentPage <= 1);
            prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
            nextBtn.classList.toggle('opacity-50', currentPage >= totalPages);
            nextBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
        }

        // Update page numbers
        if (pageNumbers) {
            pageNumbers.innerHTML = '';

            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            // Adjust start page if we're near the end
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            // Add page numbers
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `px-3 py-1 text-sm rounded transition-colors duration-200 ${
                i === currentPage 
                    ? 'bg-primary text-white' 
                    : 'bg-white text-gray-700 hover:bg-gray-100'
            }`;
                pageBtn.onclick = () => goToPage(i);
                pageNumbers.appendChild(pageBtn);
            }
        }
    }

    function goToPage(page) {
        if (page < 1 || page > totalPages || page === currentPage) return;

        currentPage = page;
        displayOrders(ordersData);
    }

    function nextPage() {
        if (currentPage < totalPages) {
            goToPage(currentPage + 1);
        }
    }

    function prevPage() {
        if (currentPage > 1) {
            goToPage(currentPage - 1);
        }
    }

    function changePageSize(newSize) {
        itemsPerPage = parseInt(newSize);
        currentPage = 1; // Reset to first page
        // Selections are preserved automatically since we use globalSelectedIds
        displayOrders(ordersData);
    }

    function loadOrders(retryCount = 0) {
        const maxRetries = 3;
        const retryDelay = 2000; // 2 seconds

        console.log('=== LOAD ORDERS DEBUG ===');
        console.log('Retry count:', retryCount);
        console.log('DOM elements check:');
        console.log('- loadingState:', document.getElementById('loadingState'));
        console.log('- errorMessage:', document.getElementById('errorMessage'));
        console.log('- ordersTable:', document.getElementById('ordersTable'));

        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('errorMessage').classList.add('hidden');
        document.getElementById('ordersTable').classList.add('hidden');

        console.log(`Loading orders... (attempt ${retryCount + 1}/${maxRetries + 1})`);
        console.log('Making fetch request to /orders/api');

        // Create AbortController for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            console.log('Request timed out after 30 seconds');
            controller.abort();
        }, 30000); // 30 second timeout

        fetch('/orders/api', {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => {
                clearTimeout(timeoutId);
                console.log('API response received:', res.status, res.statusText);
                console.log('Response headers:', res.headers);

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('API data received:', data);
                console.log('Data type:', typeof data);
                console.log('Data success:', data.success);
                console.log('Data orders length:', data.orders ? data.orders.length : 'undefined');

                if (data.success) {
                    ordersData = data.orders;
                    console.log(`Loaded ${ordersData.length} orders`);

                    // Build search index for fast searching
                    buildSearchIndex(ordersData);

                    displayOrders(ordersData);
                } else {
                    console.error('API returned error:', data.error);
                    showError(data.error || 'Erro ao carregar pedidos');
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.error('Failed to load orders:', error);
                console.error('Error name:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);

                if (error.name === 'AbortError') {
                    console.error('Request timed out');
                    showError('Tempo limite excedido. Tente novamente.');
                } else if (retryCount < maxRetries) {
                    console.log(`Retrying in ${retryDelay}ms...`);
                    setTimeout(() => loadOrders(retryCount + 1), retryDelay);
                } else {
                    showError('Erro de conexão. Verifique sua internet e tente novamente.');
                }
            });
    }

    function displayOrders(orders) {
        // Use cached DOM elements for better performance
        const loadingState = cachedElements.loadingState;
        const ordersTable = cachedElements.ordersTable;
        const ordersCount = cachedElements.ordersCount;
        const tbody = cachedElements.ordersTableBody;

        loadingState.classList.add('hidden');

        if (!orders || orders.length === 0) {
            ordersTable.classList.add('hidden');
            // Reset pagination
            currentPage = 1;
            totalPages = 1;
            totalItems = 0;
            updatePaginationInfo();
            updatePaginationControls();
            return;
        }

        const filtered = applyAllFilters(orders);
        ordersCount.textContent = `${filtered.length} pedido(s) encontrado(s)`;

        // Show/hide table based on results
        if (filtered.length === 0) {
            // Keep table headers visible, just clear the body
            tbody.innerHTML = '';
            // Reset pagination
            currentPage = 1;
            totalPages = 1;
            totalItems = 0;
            updatePaginationInfo();
            updatePaginationControls();
            return;
        } else {
            ordersTable.classList.remove('hidden');
        }

        let sortedOrders = filtered;
        if (currentSort.column !== 'ord_id' || currentSort.direction !== 'desc') {
            sortedOrders = sortOrders(filtered, currentSort.column, currentSort.direction);
        }

        // Apply pagination
        const paginatedOrders = paginateData(sortedOrders);

        updateSortIndicators(currentSort.column, currentSort.direction);
        updatePaginationInfo();
        updatePaginationControls();

        // Use DocumentFragment for better performance
        const fragment = document.createDocumentFragment();

        paginatedOrders.forEach(o => {
            const order = o.order;
            const tr = document.createElement('tr');
            // Add background color based on rotulo generation status
            const rotuloGenerated = o.rotulo_generated || false;
            const baseClasses = 'hover:bg-blue-50 hover:shadow-sm transition-all duration-200 cursor-pointer';
            const rotuloClasses = rotuloGenerated ? 'bg-green-50 border-l-4 border-green-400' : '';
            tr.className = `${baseClasses} ${rotuloClasses}`;
            tr.onclick = (e) => {
                // Don't trigger if clicking on checkboxes, buttons, or dropdowns
                if (e.target.type === 'checkbox' ||
                    e.target.closest('button') ||
                    e.target.closest('.relative') ||
                    e.target.closest('input') ||
                    e.target.closest('svg')) {
                    return;
                }
                viewOrderDetails(order.ord_id);
            };
            const isSelected = globalSelectedIds.has(order.ord_id.toString());
            tr.innerHTML = `
            <td class="px-2 sm:px-4 py-3 sm:py-4">
                <input type="checkbox" class="row-select h-4 w-4 border-gray-300 rounded" data-id="${order.ord_id}" ${isSelected ? 'checked' : ''}>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900">
                <div class="flex items-center space-x-2">
                    <span>#${order.ord_id}</span>
                    ${rotuloGenerated ? 
                        `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" title="Rótulo gerado em ${o.rotulo_generated_at}">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </span>` : 
                        `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600" title="Rótulo não gerado">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </span>`
                    }
                </div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="max-w-xs truncate" title="${escapeHtml(order.usr_name)}">${escapeHtml(order.usr_name)}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 text-gray-600">
                <div class="max-w-xs truncate" title="${escapeHtml(order.usr_email)}">${escapeHtml(order.usr_email)}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <span class="px-2 sm:px-3 py-1 text-xs font-semibold rounded-full ${order.chg_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${order.chg_status === 'paid' ? 'Pago' : 'Pendente'}
                </span>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="text-xs sm:text-sm">${o.items.length}</span>
                </div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                <div class="hidden sm:block">${new Date(order.created_at).toLocaleString('pt-BR')}</div>
                <div class="sm:hidden">${new Date(order.created_at).toLocaleDateString('pt-BR')}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-1 sm:space-y-0 sm:space-x-1 sm:space-x-2">
                    <div class="relative inline-block text-left">
                        <button onclick="togglePrescriptionDropdown('${order.ord_id}')" class="bg-primary hover:bg-secondary text-white px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 transition-colors duration-200">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Receituário</span>
                            <span class="sm:hidden">REC</span>
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="prescriptionDropdown-${order.ord_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                            <div class="py-1">
                                <button onclick="generatePrescription('${order.ord_id}'); closeDropdown('prescriptionDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Baixar PDF
                                </button>
                                <button onclick="previewPrescription('${order.ord_id}'); closeDropdown('prescriptionDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Visualizar PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    ${checkAllItemsHaveReq(o.items) ? 
                        `<div class="relative inline-block text-left">
                            <button onclick="toggleStickerDropdown('${order.ord_id}')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 transition-colors duration-200">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01"></path>
                            </svg>
                            <span class="hidden sm:inline">${appConfig.dev_mode ? 'Rótulo (DEV)' : 'Rótulo'}</span>
                            <span class="sm:hidden">${appConfig.dev_mode ? 'RÓT*' : 'RÓT'}</span>
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="stickerDropdown-${order.ord_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <button onclick="generateSticker('${order.ord_id}'); closeDropdown('stickerDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Baixar PDF
                                    </button>
                                    <button onclick="previewSticker('${order.ord_id}'); closeDropdown('stickerDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Visualizar PDF
                                    </button>
                                </div>
                            </div>
                        </div>` : 
                        `<button disabled class="bg-gray-50 text-gray-400 px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 cursor-not-allowed">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01"></path>
                            </svg>
                            <span class="hidden sm:inline">Rótulo</span>
                            <span class="sm:hidden">RÓT</span>
                        </button>`
                    }
                    ${order.ord_shipping_shipment_id ? 
                        `<div class="relative inline-block text-left">
                            <button onclick="toggleShippingLabelDropdown('${order.ord_id}')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 transition-colors duration-200">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <span class="hidden sm:inline">Etiqueta</span>
                                <span class="sm:hidden">ETIQ</span>
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="shippingLabelDropdown-${order.ord_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <button onclick="generateShippingLabel('${order.ord_id}'); closeDropdown('shippingLabelDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Baixar PDF
                                    </button>
                                    <button onclick="previewShippingLabel('${order.ord_id}'); closeDropdown('shippingLabelDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Visualizar PDF
                                    </button>
                                </div>
                            </div>
                        </div>` : ''
                    }
                    <div class="flex items-center justify-center ml-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </td>`;
            fragment.appendChild(tr);
        });

        // Clear and append all at once for better performance
        tbody.innerHTML = '';
        tbody.appendChild(fragment);

        attachSelectionHandlers();
    }

    function renderOrders(paginatedOrders) {
        // Get cached DOM elements
        const tbody = cachedElements.ordersTableBody;

        // Use DocumentFragment for better performance
        const fragment = document.createDocumentFragment();

        paginatedOrders.forEach(o => {
            const order = o.order;
            const tr = document.createElement('tr');
            // Add background color based on rotulo generation status
            const rotuloGenerated = o.rotulo_generated || false;
            const baseClasses = 'hover:bg-blue-50 hover:shadow-sm transition-all duration-200 cursor-pointer';
            const rotuloClasses = rotuloGenerated ? 'bg-green-50 border-l-4 border-green-400' : '';
            tr.className = `${baseClasses} ${rotuloClasses}`;
            tr.onclick = (e) => {
                // Don't trigger if clicking on checkboxes, buttons, or dropdowns
                if (e.target.type === 'checkbox' ||
                    e.target.closest('button') ||
                    e.target.closest('.relative') ||
                    e.target.closest('input') ||
                    e.target.closest('svg')) {
                    return;
                }
                viewOrderDetails(order.ord_id);
            };
            const isSelected = globalSelectedIds.has(order.ord_id.toString());
            tr.innerHTML = `
            <td class="px-2 sm:px-4 py-3 sm:py-4">
                <input type="checkbox" class="row-select h-4 w-4 border-gray-300 rounded" data-id="${order.ord_id}" ${isSelected ? 'checked' : ''}>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 font-semibold text-gray-900">
                <div class="flex items-center space-x-2">
                    <span>#${order.ord_id}</span>
                    ${rotuloGenerated ? 
                        `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800" title="Rótulo gerado em ${o.rotulo_generated_at}">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </span>` : 
                        `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600" title="Rótulo não gerado">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </span>`
                    }
                </div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="max-w-xs truncate" title="${escapeHtml(order.usr_name)}">${escapeHtml(order.usr_name)}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 text-gray-600">
                <div class="max-w-xs truncate" title="${escapeHtml(order.usr_email)}">${escapeHtml(order.usr_email)}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <span class="px-2 sm:px-3 py-1 text-xs font-semibold rounded-full ${order.chg_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${order.chg_status === 'paid' ? 'Pago' : 'Pendente'}
                </span>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="text-xs sm:text-sm">${o.items.length}</span>
                </div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                <div class="hidden sm:block">${new Date(order.created_at).toLocaleString('pt-BR')}</div>
                <div class="sm:hidden">${new Date(order.created_at).toLocaleDateString('pt-BR')}</div>
            </td>
            <td class="px-3 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-1 sm:space-y-0 sm:space-x-1 sm:space-x-2">
                    <div class="relative inline-block text-left">
                        <button onclick="togglePrescriptionDropdown('${order.ord_id}')" class="bg-primary hover:bg-secondary text-white px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 transition-colors duration-200">
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="hidden sm:inline">Receituário</span>
                            <span class="sm:hidden">REC</span>
                            <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="prescriptionDropdown-${order.ord_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                            <div class="py-1">
                                <button onclick="generatePrescription('${order.ord_id}'); closeDropdown('prescriptionDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Baixar PDF
                                </button>
                                <button onclick="previewPrescription('${order.ord_id}'); closeDropdown('prescriptionDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Visualizar PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    ${checkAllItemsHaveReq(o.items) ? 
                        `<div class="relative inline-block text-left">
                            <button onclick="toggleStickerDropdown('${order.ord_id}')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 sm:px-4 py-1 sm:py-2 rounded text-xs sm:text-sm font-semibold flex items-center justify-center space-x-1 sm:space-x-2 transition-colors duration-200">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01"></path>
                                </svg>
                                <span class="hidden sm:inline">Rótulo</span>
                                <span class="sm:hidden">ROT</span>
                                <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="stickerDropdown-${order.ord_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <button onclick="generateSticker('${order.ord_id}'); closeDropdown('stickerDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Baixar Rótulo
                                    </button>
                                    <button onclick="previewSticker('${order.ord_id}'); closeDropdown('stickerDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Visualizar Rótulo
                                    </button>
                                </div>
                            </div>
                        </div>` : ''
                    }
                    <div class="flex items-center justify-center ml-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </td>`;
            fragment.appendChild(tr);
        });

        // Clear and append all at once for better performance
        tbody.innerHTML = '';
        tbody.appendChild(fragment);

        attachSelectionHandlers();
    }

    function sortTable(column) {
        if (currentSort.column === column)
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        else currentSort = {
            column,
            direction: 'asc'
        };
        displayOrders(ordersData);
    }

    function sortOrders(orders, column, direction) {
        return [...orders].sort((a, b) => {
            const A = a.order,
                B = b.order;
            let valA, valB;
            switch (column) {
                case 'ord_id':
                    valA = +A.ord_id;
                    valB = +B.ord_id;
                    break;
                case 'usr_name':
                    valA = A.usr_name.toLowerCase();
                    valB = B.usr_name.toLowerCase();
                    break;
                case 'usr_email':
                    valA = A.usr_email.toLowerCase();
                    valB = B.usr_email.toLowerCase();
                    break;
                case 'chg_status':
                    valA = A.chg_status;
                    valB = B.chg_status;
                    break;
                case 'items_count':
                    valA = a.items.length;
                    valB = b.items.length;
                    break;
                case 'created_at':
                    valA = new Date(A.created_at);
                    valB = new Date(B.created_at);
                    break;
            }
            return (valA < valB ? -1 : valA > valB ? 1 : 0) * (direction === 'asc' ? 1 : -1);
        });
    }

    function filterOrders(orders, query) {
        if (!query || query.trim() === '') return orders;

        const q = query.toString().toLowerCase().trim();
        if (q.length === 0) return orders;

        const startTime = performance.now();
        const matchingIndices = new Set();

        // Split query into words for more flexible searching
        const queryWords = q.split(/\s+/).filter(word => word.length > 0);

        if (queryWords.length === 1) {
            // Single word search - use index for exact matches
            const word = queryWords[0];
            if (searchIndex.has(word)) {
                searchIndex.get(word).forEach(index => matchingIndices.add(index));
            }

            // Also search for partial matches in full text (especially important for short queries)
            for (let i = 0; i < orders.length; i++) {
                const fullText = searchIndex.get(`full_${i}`);
                if (fullText && fullText.includes(word)) {
                    matchingIndices.add(i);
                }
            }
        } else {
            // Multi-word search - find orders that contain all words
            const wordSets = queryWords.map(word => {
                const set = new Set();
                if (searchIndex.has(word)) {
                    searchIndex.get(word).forEach(index => set.add(index));
                }
                // Also check partial matches (important for short words)
                for (let i = 0; i < orders.length; i++) {
                    const fullText = searchIndex.get(`full_${i}`);
                    if (fullText && fullText.includes(word)) {
                        set.add(i);
                    }
                }
                return set;
            });

            // Find intersection of all word sets
            if (wordSets.length > 0) {
                const firstSet = wordSets[0];
                firstSet.forEach(index => {
                    if (wordSets.every(set => set.has(index))) {
                        matchingIndices.add(index);
                    }
                });
            }
        }

        // Convert indices back to orders
        const results = Array.from(matchingIndices).map(index => orders[index]);

        // Log performance (remove in production)
        const endTime = performance.now();
        if (endTime - startTime > 5) {
            console.log(`Search for "${q}" took ${(endTime - startTime).toFixed(2)}ms`);
        }

        return results;
    }

    function applyAllFilters(orders) {
        let filtered = [...orders];

        // Apply text search filter
        const searchQuery = document.getElementById('searchInput').value;
        if (searchQuery && searchQuery.trim() !== '') {
            filtered = filterOrders(filtered, searchQuery);
        }

        // Apply REQ status filter
        const reqStatus = document.getElementById('reqStatusFilter').value;
        if (reqStatus !== 'all') {
            filtered = filtered.filter(order => {
                const items = order.items || [];
                const allItemsHaveReq = items.every(item =>
                    item.req && item.req.trim() !== ''
                );

                if (reqStatus === 'complete') {
                    return allItemsHaveReq;
                } else if (reqStatus === 'incomplete') {
                    return !allItemsHaveReq;
                }
                return true;
            });
        }

        // Apply date range filter
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;

        if (dateFrom || dateTo) {
            filtered = filtered.filter(order => {
                const orderDate = new Date(order.order.created_at);
                const fromDate = dateFrom ? new Date(dateFrom) : null;
                const toDate = dateTo ? new Date(dateTo) : null;

                if (fromDate && toDate) {
                    return orderDate >= fromDate && orderDate <= toDate;
                } else if (fromDate) {
                    return orderDate >= fromDate;
                } else if (toDate) {
                    return orderDate <= toDate;
                }
                return true;
            });
        }

        // Apply rotulo generation status filter
        const rotuloStatus = document.getElementById('rotuloStatusFilter').value;
        if (rotuloStatus !== 'all') {
            filtered = filtered.filter(order => {
                const hasGeneratedRotulo = order.rotulo_generated || false;

                if (rotuloStatus === 'generated') {
                    return hasGeneratedRotulo;
                } else if (rotuloStatus === 'not_generated') {
                    return !hasGeneratedRotulo;
                }
                return true;
            });
        }

        return filtered;
    }

    function applyFilters() {
        if (!ordersData || ordersData.length === 0) return;

        // Get cached DOM elements
        const ordersTable = cachedElements.ordersTable;
        const ordersCount = cachedElements.ordersCount;
        const tbody = cachedElements.ordersTableBody;

        const filtered = applyAllFilters(ordersData);
        ordersCount.textContent = `${filtered.length} pedido(s) encontrado(s)`;

        // Show/hide table based on results
        if (filtered.length === 0) {
            tbody.innerHTML = '';
            currentPage = 1;
            totalPages = 1;
            updatePaginationInfo();
            updatePaginationControls();
            return;
        } else {
            ordersTable.classList.remove('hidden');
        }

        let sortedOrders = filtered;
        if (currentSort.column !== 'ord_id' || currentSort.direction !== 'desc') {
            sortedOrders = sortOrders(filtered, currentSort.column, currentSort.direction);
        }

        // Apply pagination
        const paginatedOrders = paginateData(sortedOrders);
        renderOrders(paginatedOrders);
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('reqStatusFilter').value = 'all';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('rotuloStatusFilter').value = 'all';
        applyFilters();
    }

    function debounce(fn, delay) {
        let t;
        return function() {
            const args = arguments;
            clearTimeout(t);
            t = setTimeout(() => fn.apply(null, args), delay);
        }
    }

    function performSearch(query) {
        if (isSearching) return;

        const currentTime = performance.now();
        lastSearchTime = currentTime;

        isSearching = true;
        searchQuery = query;

        // Reset to first page when searching
        currentPage = 1;

        // Show search spinner only for longer searches
        const searchIcon = cachedElements.searchIcon;
        const searchSpinner = cachedElements.searchSpinner;
        let showSpinner = false;

        // Use immediate execution for very short queries
        if (query.length <= 3) {
            try {
                applyFilters();
            } finally {
                isSearching = false;
            }
            return;
        }

        // Show spinner for longer searches
        if (searchIcon && searchSpinner) {
            searchIcon.classList.add('hidden');
            searchSpinner.classList.remove('hidden');
            showSpinner = true;
        }

        // Use requestAnimationFrame for smoother updates
        requestAnimationFrame(() => {
            // Check if this is still the latest search
            if (currentTime !== lastSearchTime) {
                isSearching = false;
                if (showSpinner && searchIcon && searchSpinner) {
                    searchSpinner.classList.add('hidden');
                    searchIcon.classList.remove('hidden');
                }
                return;
            }

            try {
                applyFilters();
            } finally {
                isSearching = false;

                // Hide search spinner
                if (showSpinner && searchIcon && searchSpinner) {
                    searchSpinner.classList.add('hidden');
                    searchIcon.classList.remove('hidden');
                }
            }
        });
    }

    function updateSortIndicators(column, direction) {
        const cols = ['ord_id', 'usr_name', 'usr_email', 'chg_status', 'items_count', 'created_at'];
        cols.forEach(c => {
            const el = document.getElementById(`sort-${c}`);
            if (!el) return;
            el.innerHTML = `<svg class='w-3 h-3 ${c === column ? 'text-primary' : 'text-gray-400'}' fill='currentColor' viewBox='0 0 20 20'>
            <path d='${direction === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z'}'></path></svg>`;
        });
    }

    function attachSelectionHandlers() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = Array.from(document.querySelectorAll('.row-select'));
        const bulkBtn = document.getElementById('bulkGenerateBtn');
        const bulkPrescriptionsDownloadBtn = document.getElementById('bulkPrescriptionsDownloadBtn');
        const bulkPrescriptionsPreviewBtn = document.getElementById('bulkPrescriptionsPreviewBtn');
        const bulkLabelsBtn = document.getElementById('bulkLabelsBtn');
        const bulkLabelsDownloadBtn = document.getElementById('bulkLabelsDownloadBtn');
        const bulkLabelsPreviewBtn = document.getElementById('bulkLabelsPreviewBtn');
        const selectedCount = document.getElementById('selectedCount');

        // Remove existing event listeners to prevent duplicates
        if (selectAll) {
            selectAll.replaceWith(selectAll.cloneNode(true));
        }
        checkboxes.forEach(cb => {
            cb.replaceWith(cb.cloneNode(true));
        });
        if (bulkPrescriptionsDownloadBtn) {
            bulkPrescriptionsDownloadBtn.replaceWith(bulkPrescriptionsDownloadBtn.cloneNode(true));
        }
        if (bulkPrescriptionsPreviewBtn) {
            bulkPrescriptionsPreviewBtn.replaceWith(bulkPrescriptionsPreviewBtn.cloneNode(true));
        }
        if (bulkLabelsDownloadBtn) {
            bulkLabelsDownloadBtn.replaceWith(bulkLabelsDownloadBtn.cloneNode(true));
        }
        if (bulkLabelsPreviewBtn) {
            bulkLabelsPreviewBtn.replaceWith(bulkLabelsPreviewBtn.cloneNode(true));
        }

        // Re-get elements after cloning
        const newSelectAll = document.getElementById('selectAll');
        const newCheckboxes = Array.from(document.querySelectorAll('.row-select'));
        const newBulkPrescriptionsDownloadBtn = document.getElementById('bulkPrescriptionsDownloadBtn');
        const newBulkPrescriptionsPreviewBtn = document.getElementById('bulkPrescriptionsPreviewBtn');
        const newBulkLabelsDownloadBtn = document.getElementById('bulkLabelsDownloadBtn');
        const newBulkLabelsPreviewBtn = document.getElementById('bulkLabelsPreviewBtn');

        function refreshUI() {
            const checked = newCheckboxes.filter(cb => cb.checked);
            const totalSelected = globalSelectedIds.size;
            selectedCount.textContent = `${totalSelected} selecionado(s)`;
            bulkBtn.disabled = totalSelected === 0;
            newBulkPrescriptionsDownloadBtn.disabled = totalSelected === 0;
            newBulkPrescriptionsPreviewBtn.disabled = totalSelected === 0;
            bulkLabelsBtn.disabled = totalSelected === 0;
            newBulkLabelsDownloadBtn.disabled = totalSelected === 0;
            newBulkLabelsPreviewBtn.disabled = totalSelected === 0;
            // Update select all based on current page items
            const currentPageIds = newCheckboxes.map(cb => cb.getAttribute('data-id'));
            const currentPageSelected = currentPageIds.filter(id => globalSelectedIds.has(id));

            if (currentPageSelected.length === 0) {
                newSelectAll.checked = false;
                newSelectAll.indeterminate = false;
            } else if (currentPageSelected.length === currentPageIds.length) {
                newSelectAll.checked = true;
                newSelectAll.indeterminate = false;
            } else {
                newSelectAll.checked = false;
                newSelectAll.indeterminate = true;
            }
        }

        newSelectAll.addEventListener('change', () => {
            const state = newSelectAll.checked;
            newCheckboxes.forEach(cb => {
                cb.checked = state;
                const id = cb.getAttribute('data-id');
                if (state) {
                    globalSelectedIds.add(id);
                } else {
                    globalSelectedIds.delete(id);
                }
            });
            refreshUI();
        });
        newCheckboxes.forEach(cb => {
            cb.addEventListener('change', (e) => {
                const id = e.target.getAttribute('data-id');
                if (e.target.checked) {
                    globalSelectedIds.add(id);
                } else {
                    globalSelectedIds.delete(id);
                }
                refreshUI();
            });
        });

        newBulkPrescriptionsDownloadBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            generateBatch(ids);
            closeDropdown('batchPrescriptionsDropdown');
        });

        newBulkPrescriptionsPreviewBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            previewBatchPrescriptions(ids);
            closeDropdown('batchPrescriptionsDropdown');
        });

        newBulkLabelsDownloadBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            generateBatchLabels(ids);
            closeDropdown('batchLabelsDropdown');
        });

        newBulkLabelsPreviewBtn.addEventListener('click', () => {
            const ids = getSelectedIds();
            if (ids.length === 0) return;
            previewBatchLabels(ids);
            closeDropdown('batchLabelsDropdown');
        });

        refreshUI();
    }

    // Function to get all selected IDs
    function getSelectedIds() {
        return Array.from(globalSelectedIds);
    }

    // Function to clear all selections
    function clearAllSelections() {
        globalSelectedIds.clear();
        // Update all visible checkboxes
        const checkboxes = Array.from(document.querySelectorAll('.row-select'));
        checkboxes.forEach(cb => cb.checked = false);
        // Update select all checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        // Refresh UI
        const selectedCount = document.getElementById('selectedCount');
        if (selectedCount) {
            selectedCount.textContent = '0 selecionado(s)';
        }
        // Disable bulk buttons
        const bulkButtons = [
            'bulkGenerateBtn', 'bulkPrescriptionsDownloadBtn', 'bulkPrescriptionsPreviewBtn',
            'bulkLabelsBtn', 'bulkLabelsDownloadBtn', 'bulkLabelsPreviewBtn'
        ];
        bulkButtons.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = true;
        });
    }

    function escapeHtml(t) {
        const div = document.createElement('div');
        div.textContent = t;
        return div.innerHTML;
    }

    function showError(msg) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorText').textContent = msg;
        document.getElementById('errorMessage').classList.remove('hidden');

        // Add retry button if it doesn't exist
        let retryButton = document.getElementById('retryButton');
        if (!retryButton) {
            retryButton = document.createElement('button');
            retryButton.id = 'retryButton';
            retryButton.className = 'mt-3 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200';
            retryButton.textContent = 'Tentar Novamente';
            retryButton.onclick = () => {
                document.getElementById('errorMessage').classList.add('hidden');
                loadOrders();
            };
            document.getElementById('errorMessage').appendChild(retryButton);
        }
    }

    function showErrorMessage(message) {
        // Create a temporary error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-50 border-l-4 border-red-400 rounded-lg p-4 shadow-lg z-50 max-w-md';
        errorDiv.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Erro</h3>
                <div class="mt-1 text-sm text-red-700">${message}</div>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-red-400 hover:text-red-600">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;

        document.body.appendChild(errorDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }

    function showSuccessMessage(message) {
        // Create a success message element
        const successDiv = document.createElement('div');
        successDiv.className = 'fixed top-4 right-4 bg-green-50 border-l-4 border-green-400 rounded-lg p-4 shadow-lg z-50 max-w-md';

        successDiv.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Sucesso</h3>
                <div class="mt-1 text-sm text-green-700">${message}</div>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-green-400 hover:text-green-600">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;

        document.body.appendChild(successDiv);

        // Auto-remove after 5 seconds (shorter since no action needed)
        setTimeout(() => {
            if (successDiv.parentElement) {
                successDiv.remove();
            }
        }, 5000);
    }

    function viewOrderDetails(id) {
        const o = ordersData.find(x => x.order.ord_id == id);
        if (!o) return showError('Pedido não encontrado');
        const order = o.order;
        const items = o.items;
        document.getElementById('orderDetails').innerHTML = `
        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
            <div class='bg-gray-50 rounded-lg p-6'>
                <h4 class='font-bold text-primary text-lg mb-4 flex items-center'>
                    <svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'/></svg>
                    Informações do Cliente
                </h4>
                <p><b>Nome:</b> ${order.usr_name}</p>
                <p><b>E-mail:</b> ${order.usr_email}</p>
                <p><b>CPF:</b> ${order.usr_cpf}</p>
                <p><b>Telefone:</b> ${order.usr_phone}</p>
            </div>
            <div class='bg-gray-50 rounded-lg p-6'>
                <h4 class='font-bold text-primary text-lg mb-4 flex items-center'>
                    <svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z'/></svg>
                    Informações do Pedido
                </h4>
                <p><b>ID:</b> ${order.ord_id}</p>
                <p><b>Status:</b> ${order.chg_status === 'paid' ? 'Pago' : 'Pendente'}</p>
                <p><b>Data:</b> ${new Date(order.created_at).toLocaleString('pt-BR')}</p>
                <p><b>Rótulo:</b> ${o.rotulo_generated ? 
                    `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Gerado em ${new Date(o.rotulo_generated_at).toLocaleString('pt-BR')}
                    </span>` : 
                    `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Não gerado
                    </span>`
                }</p>
            </div>
        </div>
        <div class='mt-6'>
            <h4 class='font-bold text-primary text-lg mb-4 flex items-center'>
                <svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'></path></svg>
                Itens do Pedido (${items.length})
            </h4>
            <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                ${items.map(i => `<div class='bg-white border border-gray-200 rounded-lg p-4 shadow-sm'>
                    <h5 class='font-semibold text-gray-900 mb-2'>${i.itm_name}</h5>
                    ${i.req && i.req.trim() !== '' ? `<p class='text-sm text-blue-600 font-medium mt-1'>REQ: ${i.req}</p>` : `<p class='text-sm text-gray-400 mt-1'>REQ: Não informado</p>`}
                </div>`).join('')}
            </div>
        </div>
        <div class='mt-6 pt-6 border-t border-gray-200'>
            <h4 class='font-bold text-primary text-lg mb-4 flex items-center'>
                <svg class='w-5 h-5 mr-2' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4'></path></svg>
                Ações
            </h4>
            <div class='flex flex-col sm:flex-row gap-3'>
                <div class="relative inline-block text-left">
                    <button onclick="togglePrescriptionModalDropdown('${order.ord_id}')" class='bg-primary hover:bg-secondary text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center space-x-2 transition-colors duration-200 shadow-md hover:shadow-lg'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z'></path>
                        </svg>
                        <span>Receituário</span>
                        <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'></path>
                        </svg>
                    </button>
                    <div id="prescriptionModalDropdown-${order.ord_id}" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-20 border border-gray-200">
                        <div class="py-1">
                            <button onclick="generatePrescription('${order.ord_id}'); closeDropdown('prescriptionModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Baixar Receituário
                            </button>
                            <button onclick="previewPrescription('${order.ord_id}'); closeDropdown('prescriptionModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Visualizar Receituário
                            </button>
                        </div>
                    </div>
                </div>
                ${checkAllItemsHaveReq(items) ? 
                    `<div class="relative inline-block text-left">
                        <button onclick="toggleStickerModalDropdown('${order.ord_id}')" class='bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold flex items-center justify-center space-x-2 transition-colors duration-200 shadow-md hover:shadow-lg'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01'></path>
                        </svg>
                            <span>${appConfig.dev_mode ? 'Rótulo (DEV)' : 'Rótulo'}</span>
                            <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'></path>
                            </svg>
                        </button>
                        <div id="stickerModalDropdown-${order.ord_id}" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-20 border border-gray-200">
                            <div class="py-1">
                                <button onclick="generateSticker('${order.ord_id}'); closeDropdown('stickerModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Baixar Rótulo
                                </button>
                                <button onclick="previewSticker('${order.ord_id}'); closeDropdown('stickerModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Visualizar Rótulo
                                </button>
                            </div>
                        </div>
                    </div>` : 
                    `<button disabled class='bg-gray-50 text-gray-400 px-6 py-3 rounded-lg font-semibold flex items-center justify-center space-x-2 cursor-not-allowed shadow-md'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17a4 4 0 006 0M9 7h6m-8 4h10M5 7h.01M5 11h.01M5 17h.01'></path>
                        </svg>
                        <span>Gerar Rótulo</span>
                    </button>`
                }
                ${order.ord_shipping_shipment_id ? 
                    `<div class="relative inline-block text-left">
                        <button onclick="toggleShippingLabelModalDropdown('${order.ord_id}')" class='bg-blue-100 hover:bg-blue-200 text-blue-700 px-6 py-3 rounded-lg font-semibold flex items-center justify-center space-x-2 transition-colors duration-200 shadow-md hover:shadow-lg'>
                            <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'></path>
                            </svg>
                            <span>Etiqueta</span>
                            <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'></path>
                            </svg>
                        </button>
                        <div id="shippingLabelModalDropdown-${order.ord_id}" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-20 border border-gray-200">
                            <div class="py-1">
                                <button onclick="generateShippingLabel('${order.ord_id}'); closeDropdown('shippingLabelModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Baixar Etiqueta
                                </button>
                                <button onclick="previewShippingLabel('${order.ord_id}'); closeDropdown('shippingLabelModalDropdown-${order.ord_id}')" class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Visualizar Etiqueta
                                </button>
                            </div>
                        </div>
                    </div>` : ''
                }
            </div>
        </div>`;
        document.getElementById('orderModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('orderModal').classList.add('hidden');
    }

    // Dropdown functions
    function togglePrescriptionDropdown(orderId) {
        const dropdown = document.getElementById(`prescriptionDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function togglePrescriptionModalDropdown(orderId) {
        const dropdown = document.getElementById(`prescriptionModalDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleBatchPrescriptionsDropdown() {
        const dropdown = document.getElementById('batchPrescriptionsDropdown');
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleBatchLabelsDropdown() {
        const dropdown = document.getElementById('batchLabelsDropdown');
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleLastDayReceituariosDropdown() {
        const dropdown = document.getElementById('lastDayReceituariosDropdown');
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleStickerDropdown(orderId) {
        const dropdown = document.getElementById(`stickerDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleStickerModalDropdown(orderId) {
        const dropdown = document.getElementById(`stickerModalDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleShippingLabelDropdown(orderId) {
        const dropdown = document.getElementById(`shippingLabelDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function toggleShippingLabelModalDropdown(orderId) {
        const dropdown = document.getElementById(`shippingLabelModalDropdown-${orderId}`);
        const isHidden = dropdown.classList.contains('hidden');

        // Close all other dropdowns first
        closeAllDropdowns();

        if (isHidden) {
            dropdown.classList.remove('hidden');
        }
    }

    function closeDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
    }

    function showLoadingOverlay(message = 'Gerando...') {
        document.getElementById('loadingMessage').textContent = message;
        document.getElementById('loadingOverlay').classList.remove('hidden');
    }

    function hideLoadingOverlay() {
        document.getElementById('loadingOverlay').classList.add('hidden');
    }

    function closeAllDropdowns() {
        // Close all prescription dropdowns
        const prescriptionDropdowns = document.querySelectorAll('[id^="prescriptionDropdown-"]');
        prescriptionDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close all prescription modal dropdowns
        const prescriptionModalDropdowns = document.querySelectorAll('[id^="prescriptionModalDropdown-"]');
        prescriptionModalDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close all sticker dropdowns
        const stickerDropdowns = document.querySelectorAll('[id^="stickerDropdown-"]');
        stickerDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close all sticker modal dropdowns
        const stickerModalDropdowns = document.querySelectorAll('[id^="stickerModalDropdown-"]');
        stickerModalDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close all shipping label dropdowns
        const shippingLabelDropdowns = document.querySelectorAll('[id^="shippingLabelDropdown-"]');
        shippingLabelDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close all shipping label modal dropdowns
        const shippingLabelModalDropdowns = document.querySelectorAll('[id^="shippingLabelModalDropdown-"]');
        shippingLabelModalDropdowns.forEach(dropdown => dropdown.classList.add('hidden'));

        // Close batch prescriptions dropdown
        const batchPrescriptionsDropdown = document.getElementById('batchPrescriptionsDropdown');
        if (batchPrescriptionsDropdown) {
            batchPrescriptionsDropdown.classList.add('hidden');
        }

        // Close batch labels dropdown
        const batchLabelsDropdown = document.getElementById('batchLabelsDropdown');
        if (batchLabelsDropdown) {
            batchLabelsDropdown.classList.add('hidden');
        }

        // Close last day receituarios dropdown
        const lastDayReceituariosDropdown = document.getElementById('lastDayReceituariosDropdown');
        if (lastDayReceituariosDropdown) {
            lastDayReceituariosDropdown.classList.add('hidden');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.relative')) {
            closeAllDropdowns();
        }
    });

    function generatePrescription(id) {
        // Show loading overlay with appropriate message
        showLoadingOverlay('Gerando Receituário...');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Gerando...</span>
    `;

        // Make the API call
        fetch('/orders/generate-prescription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                hideLoadingOverlay();

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `receituario_${id}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage(`Receituário gerado e baixado com sucesso!`);

                            // Clear all selections (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage(`Receituário gerado com sucesso!`);

                                // Clear all selections (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar receituário');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                hideLoadingOverlay();

                console.error('Error generating prescription:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable the button
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    function previewPrescription(id) {
        // Show loading overlay
        document.getElementById('loadingOverlay').classList.remove('hidden');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Visualizando...</span>
    `;

        // Make the API call for preview
        fetch('/orders/preview-prescription', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage(`Receituário visualizado com sucesso!`);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage(`Receituário visualizado com sucesso!`);
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar receituário');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error previewing prescription:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable button and restore original content
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    function generateBatch(ids) {
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/generate-batch-prescriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_ids=${encodeURIComponent(JSON.stringify(ids))}`
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `receituario_batch_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Receituários gerados com sucesso!');

                            // Clear all selections (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Receituários gerados com sucesso!');

                                // Clear all selections (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar receituários em lote');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error generating batch prescriptions:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function generateBatchLabels(ids) {
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/generate-batch-labels', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_ids=${encodeURIComponent(JSON.stringify(ids))}`
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `rotulos_batch_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Rótulos gerados com sucesso!');

                            // Clear all selections (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Rótulos gerados com sucesso!');

                                // Clear all selections (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar rótulos em lote');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error generating batch labels:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function previewBatchPrescriptions(ids) {
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/preview-batch-prescriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_ids=${encodeURIComponent(JSON.stringify(ids))}`
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage('Receituários visualizados com sucesso!');
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Receituários visualizados com sucesso!');
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar receituários em lote');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error previewing batch prescriptions:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function previewBatchLabels(ids) {
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/preview-batch-labels', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_ids=${encodeURIComponent(JSON.stringify(ids))}`
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage('Rótulos visualizados com sucesso!');
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Rótulos visualizados com sucesso!');
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar rótulos em lote');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error previewing batch labels:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function checkAllItemsHaveReq(items) {
        if (!items || items.length === 0) return false;

        // In dev mode, always allow rótulo generation
        if (appConfig.dev_mode) {
            return true;
        }

        return items.every(item => item.req && item.req.trim() !== '');
    }

    function generateSticker(id) {
        // Check if rotulo was already generated
        const order = ordersData.find(o => o.order.ord_id == id);
        if (order && order.rotulo_generated) {
            // Show warning modal
            showRotuloWarningModal([order], () => {
                // Execute the actual generation after user confirms
                executeGenerateSticker(id);
            });
            return;
        }

        // If not generated, proceed directly
        executeGenerateSticker(id);
    }

    function executeGenerateSticker(id) {
        // Show loading overlay with appropriate message
        showLoadingOverlay('Gerando Rótulos...');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Gerando...</span>
    `;

        // Make the API call
        fetch('/orders/generate-sticker', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                hideLoadingOverlay();

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `rotulos_${id}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Rótulos gerados e baixados com sucesso!');

                            // Refresh the orders data to update the status
                            refreshOrders();

                            // Clear all selections after refresh (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Rótulos gerados com sucesso!');

                                // Refresh the orders data to update the status
                                refreshOrders();

                                // Clear all selections after refresh (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar rótulos');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                hideLoadingOverlay();
                console.error('Error generating sticker:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable the button
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    function generateShippingLabel(id) {
        // Show loading overlay with appropriate message
        showLoadingOverlay('Gerando Etiqueta de Envio...');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Gerando...</span>
    `;

        // Make the API call
        fetch('/orders/generate-shipping-label', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                hideLoadingOverlay();

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `etiqueta_envio_${id}_${new Date().toISOString().slice(0,19).replace(/:/g, '-')}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Etiqueta de envio gerada e baixada com sucesso!');

                            // Clear all selections (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Etiqueta de envio gerada com sucesso!');

                                // Clear all selections (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar etiqueta de envio');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                hideLoadingOverlay();

                console.error('Error generating shipping label:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable the button
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    function previewShippingLabel(id) {
        // Show loading overlay with appropriate message
        showLoadingOverlay('Visualizando Etiqueta de Envio...');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Visualizando...</span>
    `;

        // Make the API call for preview
        fetch('/orders/preview-shipping-label', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                hideLoadingOverlay();

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage('Etiqueta de envio visualizada com sucesso!');
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Etiqueta de envio visualizada com sucesso!');
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar etiqueta de envio');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                hideLoadingOverlay();
                console.error('Error previewing shipping label:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable button and restore original content
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    function previewSticker(id) {
        // Check if rotulo was already generated
        const order = ordersData.find(o => o.order.ord_id == id);
        if (order && order.rotulo_generated) {
            // Show warning modal
            showRotuloWarningModal([order], () => {
                // Execute the actual preview after user confirms
                executePreviewSticker(id);
            });
            return;
        }

        // If not generated, proceed directly
        executePreviewSticker(id);
    }

    function executePreviewSticker(id) {
        // Show loading overlay with appropriate message
        showLoadingOverlay('Visualizando Rótulos...');

        // Disable the button to prevent multiple clicks
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span>Visualizando...</span>
    `;

        // Make the API call for preview
        fetch('/orders/preview-sticker', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${id}`
            })
            .then(response => {
                // Hide loading overlay
                hideLoadingOverlay();

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage('Rótulos visualizados com sucesso!');

                            // Refresh the orders data to update the status
                            refreshOrders();
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Rótulos visualizados com sucesso!');
                                // Refresh the orders data to update the status
                                refreshOrders();
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar rótulos');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                // Hide loading overlay
                hideLoadingOverlay();
                console.error('Error previewing sticker:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            })
            .finally(() => {
                // Re-enable button and restore original content
                button.disabled = false;
                button.innerHTML = originalContent;
            });
    }

    document.addEventListener('DOMContentLoaded', loadOrders);

    // Initialize everything on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        // Load app configuration first
        loadAppConfig();
        // Initialize cached elements first
        initializeCachedElements();

        // Set up search input with optimized debounce
        const input = cachedElements.searchInput;
        if (!input) return;

        const handler = debounce((e) => {
            const query = e.target.value || '';
            performSearch(query);
        }, searchDebounceDelay);

        // Add immediate feedback for better UX
        input.addEventListener('input', (e) => {
            // Clear any existing timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Show immediate visual feedback
            const query = e.target.value || '';
            if (query.length > 0) {
                input.style.opacity = '0.7';
            } else {
                input.style.opacity = '1';
            }

            // Debounced search
            handler(e);
        });

        // Restore opacity when search completes
        input.addEventListener('blur', () => {
            input.style.opacity = '1';
        });
    });

    // Last Day Receituarios Functions
    function generateLastDayReceituarios() {
        // Close dropdown
        closeDropdown('lastDayReceituariosDropdown');

        // Show confirmation dialog
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.toLocaleDateString('pt-BR');

        if (!confirm(`Deseja gerar todos os receituários de ontem (${yesterdayStr})?`)) {
            return;
        }

        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/generate-last-day-receituarios', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'preview=false'
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `receituarios_ontem_${yesterday.toISOString().slice(0,10)}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Receituários de ontem gerados com sucesso!');

                            // Clear all selections (with delay to ensure DOM is updated)
                            setTimeout(() => {
                                clearAllSelections();
                            }, 100);
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage(`Receituários de ontem gerados com sucesso! (${data.orders_count} pedidos)`);

                                // Clear all selections (with delay to ensure DOM is updated)
                                setTimeout(() => {
                                    clearAllSelections();
                                }, 100);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar receituários de ontem');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error generating last day receituarios:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function previewLastDayReceituarios() {
        // Close dropdown
        closeDropdown('lastDayReceituariosDropdown');

        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/preview-last-day-receituarios', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: ''
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and open in new tab
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            window.open(url, '_blank');
                            // Don't revoke URL immediately as it's being used in new tab
                            setTimeout(() => window.URL.revokeObjectURL(url), 10000);

                            // Show success message
                            showSuccessMessage('Receituários de ontem visualizados com sucesso!');
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage('Receituários de ontem visualizados com sucesso!');
                            } else {
                                showErrorMessage(data.error || 'Erro ao visualizar receituários de ontem');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error previewing last day receituarios:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    // Last Day Labels Modal Functions
    function showLastDayLabelsModal() {
        document.getElementById('lastDayLabelsModal').classList.remove('hidden');
        loadLastDayOrdersForLabels();
    }

    function closeLastDayLabelsModal() {
        document.getElementById('lastDayLabelsModal').classList.add('hidden');
    }

    // Rotulo Warning Modal Functions
    let pendingRotuloAction = null;
    let pendingRotuloOrders = [];

    function showRotuloWarningModal(orders, action) {
        pendingRotuloAction = action;
        pendingRotuloOrders = orders;

        const modal = document.getElementById('rotuloWarningModal');
        const ordersList = document.getElementById('rotuloWarningOrders');

        // Populate the orders list
        ordersList.innerHTML = orders.map(order => `
            <div class="bg-white border border-gray-200 rounded-lg p-3 flex items-center justify-between">
                <div>
                    <span class="font-semibold text-gray-900">#${order.ord_id}</span>
                    <span class="text-gray-600 ml-2">${order.usr_name}</span>
                </div>
                <div class="text-sm text-gray-500">
                    Gerado em: ${new Date(order.rotulo_generated_at).toLocaleString('pt-BR')}
                </div>
            </div>
        `).join('');

        modal.classList.remove('hidden');
    }

    function closeRotuloWarningModal() {
        document.getElementById('rotuloWarningModal').classList.add('hidden');
        pendingRotuloAction = null;
        pendingRotuloOrders = [];
    }

    function confirmRotuloGeneration() {
        if (pendingRotuloAction) {
            // Execute the pending action
            pendingRotuloAction();
        }
        closeRotuloWarningModal();
    }

    function loadLastDayOrdersForLabels() {
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/last-day-orders-for-labels', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (data.success) {
                    displayLastDayOrdersData(data);
                } else {
                    showErrorMessage(data.error || 'Erro ao carregar pedidos de ontem');
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error loading last day orders:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }

    function displayLastDayOrdersData(data) {
        const content = document.getElementById('lastDayLabelsContent');

        content.innerHTML = `
            <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Resumo dos Pedidos de Ontem (${data.date})</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Total de pedidos: ${data.total_orders}</p>
                            <p>Pedidos com REQ preenchido: ${data.orders_with_req.length}</p>
                            <p>Pedidos sem REQ: ${data.orders_without_req.length}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${data.orders_with_req.length > 0 ? `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <h4 class="text-lg font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Pedidos Prontos para Rótulos (${data.orders_with_req.length})
                    </h4>
                    <div class="space-y-2">
                        ${data.orders_with_req.map(order => `
                            <div class="bg-white border border-green-200 rounded-lg p-3 flex justify-between items-center">
                                <div>
                                    <span class="font-semibold text-gray-900">#${order.order_id}</span>
                                    <span class="text-gray-600 ml-2">${order.customer_name}</span>
                                    <span class="text-sm text-gray-500 ml-2">(${order.items_count} itens)</span>
                                </div>
                                <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">REQ OK</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            ${data.orders_without_req.length > 0 ? `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h4 class="text-lg font-semibold text-red-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Pedidos com REQ Incompleto (${data.orders_without_req.length})
                    </h4>
                    <div class="space-y-2">
                        ${data.orders_without_req.map(order => `
                            <div class="bg-white border border-red-200 rounded-lg p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-semibold text-gray-900">#${order.order_id}</span>
                                        <span class="text-gray-600 ml-2">${order.customer_name}</span>
                                        <span class="text-sm text-gray-500 ml-2">(${order.items_count} itens)</span>
                                    </div>
                                    <span class="text-xs text-red-600 bg-red-100 px-2 py-1 rounded-full">REQ INCOMPLETO</span>
                                </div>
                                <div class="text-sm text-red-700">
                                    <p class="font-medium">Itens sem REQ:</p>
                                    <ul class="list-disc list-inside mt-1">
                                        ${order.missing_req_items.map(item => `<li>${item}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button onclick="closeLastDayLabelsModal()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold transition-colors duration-200">
                    Cancelar
                </button>
                ${data.can_generate ? `
                    <button onclick="generateLastDayLabels()" class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Gerar Rótulos (${data.orders_with_req.length} pedidos)</span>
                    </button>
                ` : `
                    <button disabled class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg font-semibold cursor-not-allowed">
                        Nenhum pedido válido
                    </button>
                `}
            </div>
        `;
    }

    function generateLastDayLabels() {
        closeLastDayLabelsModal();

        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch('/orders/generate-last-day-labels', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'preview=false'
            })
            .then(response => {
                document.getElementById('loadingOverlay').classList.add('hidden');

                if (response.ok) {
                    // Check if response is PDF (content-type)
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        // Create blob and download
                        return response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `rotulos_ontem_${new Date().toISOString().slice(0,10)}.pdf`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            // Show success message
                            showSuccessMessage('Rótulos de ontem gerados com sucesso!');
                        });
                    } else {
                        // Try to parse as JSON for error messages
                        return response.json().then(data => {
                            if (data.success) {
                                showSuccessMessage(`Rótulos de ontem gerados com sucesso! (${data.orders_count} pedidos)`);
                            } else {
                                showErrorMessage(data.error || 'Erro ao gerar rótulos de ontem');
                            }
                        });
                    }
                } else {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').classList.add('hidden');
                console.error('Error generating last day labels:', error);
                showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
            });
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
include __DIR__ . '/../layout.php';
?>