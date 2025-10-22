<?php
$content = ob_get_clean();
$fullWidth = true;
$title = 'Controle de Produção';
ob_start();

// Start session for production state management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize session data if not exists
if (!isset($_SESSION['orders'])) {
    $_SESSION['orders'] = [];
    $_SESSION['removed_orders'] = [];
    $_SESSION['production_state'] = [];
}

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

<div>
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-primary to-secondary shadow-lg">
        <div class="px-2 sm:px-4 lg:px-8 py-6">
            <!-- Section Title -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Controle de Produção</h3>
                </div>
            </div>

            <!-- Header Controls -->
            <div class="flex flex-col space-y-4">
                <!-- Search and Controls Row -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <input id="searchInput" type="text" placeholder="Buscar por ID, CPF ou cliente..." class="w-full px-4 py-3 rounded-lg text-sm placeholder-gray-300 text-gray-800 focus:outline-none focus:ring-2 focus:ring-white/60 focus:bg-white/90 bg-white/80 transition-opacity duration-200" />
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

                    <!-- Limit and Sort Controls -->
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="flex flex-col">
                            <label class="text-white text-xs font-semibold uppercase tracking-wide mb-1">Limite</label>
                            <input id="limitFilter" type="number" placeholder="Todos" class="bg-white/90 text-gray-800 text-sm rounded-lg px-3 py-2 border border-white/50 focus:outline-none focus:ring-2 focus:ring-white/80 shadow-sm w-24" />
                        </div>
                        <button onclick="clearLimit()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 disabled:opacity-50" id="clearLimitBtn" disabled>
                            Limpar
                        </button>
                        <button onclick="toggleSortDirection()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            <span id="sortLabel">ID decrescente</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-2 sm:px-4 lg:px-8 py-4">
            <div class="grid gap-3 rounded-lg border bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Date From Filter -->
                <div class="flex flex-col gap-2">
                    <label for="dateFromFilter" class="text-xs font-semibold uppercase tracking-wide text-gray-600">De</label>
                    <input id="dateFromFilter" type="datetime-local" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>

                <!-- Date To Filter -->
                <div class="flex flex-col gap-2">
                    <label for="dateToFilter" class="text-xs font-semibold uppercase tracking-wide text-gray-600">Até</label>
                    <input id="dateToFilter" type="datetime-local" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>

                <!-- Filter Actions -->
                <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-end lg:col-span-2">
                    <button onclick="applyFilters()" id="applyFiltersBtn" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        Aplicar filtros
                    </button>
                    <button onclick="clearFilters()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Limpar filtros
                    </button>
                    <button onclick="resetFilters()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                        Restaurar padrão (30 dias)
                    </button>
                </div>

                <!-- Range Label -->
                <div id="rangeLabel" class="sm:col-span-2 lg:col-span-4 text-xs font-medium text-gray-600 hidden">
                    <!-- Range information will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="px-2 sm:px-4 lg:px-8 py-4 sm:py-6 max-w-full">
        <!-- Header Summary and Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between mb-4 sm:mb-6 gap-3">
            <!-- Summary Badge -->
            <div id="headerSummary" class="flex items-center space-x-2">
                <div class="bg-blue-50 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium">
                    <span id="summaryText">Carregando...</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Refresh Button -->
                <button onclick="refreshProduction()" id="refreshBtn"
                    class="bg-primary hover:bg-secondary text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Atualizar</span>
                </button>

                <!-- Print Button -->
                <button onclick="printSelected()" id="printBtn"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Imprimir</span>
                </button>

                <!-- Remove Selected Button (for active orders) -->
                <button onclick="removeSelectedOrders()" id="removeBtn" class="hidden bg-red-600 hover:bg-red-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Remover Selecionados</span>
                </button>

                <!-- Restore Selected Button (for removed orders) -->
                <button onclick="restoreSelectedOrders()" id="restoreBtn" class="hidden bg-green-600 hover:bg-green-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Restaurar Selecionados</span>
                </button>

                <!-- Export Items Button (for items tab) -->
                <button onclick="downloadItemsReport()" id="exportBtn" class="hidden bg-blue-600 hover:bg-blue-700 text-white px-4 sm:px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-center space-x-2 group w-full sm:w-auto">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm sm:text-base">Exportar Excel</span>
                </button>
            </div>
        </div>



        <!-- Tabs Navigation -->
        <div class="bg-white rounded-lg shadow-sm border mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button onclick="switchTab('ativos')" class="tab-button active py-4 px-1 border-b-2 border-primary text-primary font-medium text-sm transition-colors duration-200" data-tab="ativos">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Pedidos Ativos</span>
                            <span id="active-count" class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">0</span>
                        </div>
                    </button>
                    <button onclick="switchTab('removidos')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors duration-200" data-tab="removidos">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Pedidos Removidos</span>
                            <span id="removed-count" class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">0</span>
                        </div>
                    </button>
                    <button onclick="switchTab('itens')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors duration-200" data-tab="itens">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>Itens Agregados</span>
                            <span id="items-count" class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">0</span>
                        </div>
                    </button>
                    <button onclick="switchTab('producao')" class="tab-button py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors duration-200" data-tab="producao">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                            <span>Produção</span>
                        </div>
                    </button>
                </nav>
            </div>
        </div>
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-16">
            <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary"></div>
            </div>
            <div class="flex items-center justify-center space-x-2 mb-4">
                <svg class="w-6 h-6 text-primary animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900">Carregando dados de produção...</h3>
            </div>
            <p class="text-gray-600 text-lg">Aguarde enquanto buscamos os dados de produção.</p>
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
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-red-800">Erro ao carregar dados</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p id="errorText">Ocorreu um erro ao carregar os dados de produção. Tente novamente.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tab Content -->
        <div class="space-y-6">
            <!-- Active Orders Tab -->
            <div id="tab-ativos" class="tab-content">
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="p-6">
                        <!-- Active Items Filter -->
                        <div id="active-items-filter" class="mb-6 hidden">
                            <div class="grid gap-3 rounded-lg border bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="flex flex-col gap-2 sm:col-span-2 lg:col-span-2">
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Itens agregados
                                    </label>
                                    <div class="relative">
                                        <button onclick="toggleItemsFilter()" id="itemsFilterBtn" class="w-full flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20">
                                            <span id="itemsFilterText">Filtrar por item</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </button>

                                        <!-- Items Filter Dropdown -->
                                        <div id="itemsFilterDropdown" class="absolute z-50 mt-2 w-80 max-w-[calc(100vw-3rem)] rounded-lg border bg-white p-3 shadow-2xl hidden">
                                            <div class="flex items-center gap-2 mb-3">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                                </svg>
                                                <input id="itemsSearchInput" type="text" placeholder="Buscar item" class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                                <button onclick="closeItemsFilter()" class="text-gray-400 hover:text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div id="itemsFilterList" class="max-h-56 overflow-y-auto pr-2">
                                                <!-- Items will be populated here -->
                                            </div>

                                            <div class="mt-3 flex items-center justify-between gap-2">
                                                <button onclick="clearItemsFilter()" class="text-sm text-gray-600 hover:text-gray-800">
                                                    Limpar
                                                </button>
                                                <button onclick="closeItemsFilter()" class="bg-primary text-white px-3 py-1 rounded text-sm">
                                                    Concluir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Pedidos Ativos</h3>
                            <div class="flex gap-3">
                                <button onclick="selectAllOrders()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Selecionar Todos</span>
                                </button>
                                <button onclick="removeSelectedOrders()" class="bg-red-50 hover:bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span>Remover Selecionados</span>
                                </button>
                            </div>
                        </div>
                        <div id="active-orders-content">
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Carregando pedidos...</h3>
                                <p class="text-gray-600">Aguarde enquanto buscamos os dados dos pedidos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Removed Orders Tab -->
            <div id="tab-removidos" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Pedidos Removidos</h3>
                            <div class="flex gap-3">
                                <button onclick="selectAllRemovedOrders()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Selecionar Todos</span>
                                </button>
                                <button onclick="restoreSelectedOrders()" class="bg-green-50 hover:bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span>Restaurar Selecionados</span>
                                </button>
                            </div>
                        </div>
                        <div id="removed-orders-content">
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum pedido removido</h3>
                                <p class="text-gray-600">Os pedidos removidos aparecerão aqui.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Tab -->
            <div id="tab-itens" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="p-6">
                        <!-- Items Actions -->
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end mb-6">
                            <button onclick="openItemsChart()" id="chartBtn" class="bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2 w-full sm:w-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span>Visualizar evolução diária</span>
                            </button>
                            <button onclick="downloadItemsReport()" class="bg-green-50 hover:bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2 w-full sm:w-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Exportar Excel</span>
                            </button>
                        </div>

                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Itens Agregados</h3>
                            <div class="flex gap-3">
                                <button onclick="selectAllItems()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Selecionar Todos</span>
                                </button>
                            </div>
                        </div>
                        <div id="items-content">
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum item encontrado</h3>
                                <p class="text-gray-600">Os itens agregados aparecerão aqui.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production Tab -->
            <div id="tab-producao" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="p-6">
                        <!-- Production Filters -->
                        <div id="production-filters" class="mb-6 hidden">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="grid w-full min-w-0 gap-2 sm:grid-cols-[minmax(0,1fr)] lg:grid-cols-[minmax(0,280px)_minmax(0,220px)] lg:items-center lg:gap-3">
                                    <!-- Item Search -->
                                    <div class="relative">
                                        <svg class="pointer-events-none absolute left-3 top-1/2 w-4 h-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                        <input id="productionSearchInput" type="text" placeholder="Buscar por nome do item" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                    </div>

                                    <!-- Category Filter -->
                                    <div class="relative">
                                        <button onclick="toggleCategoryFilter()" id="categoryFilterBtn" class="w-full flex items-center justify-between gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20">
                                            <span id="categoryFilterText">Filtrar por categoria</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                            </svg>
                                        </button>

                                        <!-- Category Filter Dropdown -->
                                        <div id="categoryFilterDropdown" class="absolute z-50 mt-2 w-full max-w-xs rounded-lg border bg-white p-3 shadow-2xl hidden">
                                            <div class="flex items-center gap-2 mb-3">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                                                </svg>
                                                <input id="categorySearchInput" type="text" placeholder="Buscar categoria" class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
                                                <button onclick="closeCategoryFilter()" class="text-gray-400 hover:text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <div id="categoryFilterList" class="max-h-56 overflow-y-auto pr-2">
                                                <!-- Categories will be populated here -->
                                            </div>

                                            <div class="mt-3 flex items-center justify-between gap-2">
                                                <button onclick="clearCategoryFilter()" class="text-sm text-gray-600 hover:text-gray-800">
                                                    Limpar
                                                </button>
                                                <button onclick="closeCategoryFilter()" class="bg-primary text-white px-3 py-1 rounded text-sm">
                                                    Concluir
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Active Filters Display -->
                                    <div id="activeFiltersDisplay" class="flex max-w-full flex-wrap items-center gap-2 hidden">
                                        <!-- Active filters will be displayed here -->
                                    </div>
                                </div>

                                <!-- Clear Filters Button -->
                                <button onclick="clearProductionFilters()" id="clearProductionFiltersBtn" class="self-start text-sm text-gray-600 hover:text-gray-800 hidden">
                                    Limpar filtros
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Controle de Produção</h3>
                            <div class="text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                                Contexto: <span id="production-context" class="font-medium"><?= htmlspecialchars($production_context) ?></span>
                            </div>
                        </div>

                        <!-- Production Summary Cards -->
                        <div id="production-summary" class="hidden mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-2xl font-bold text-blue-900" id="total-required">0</div>
                                            <div class="text-sm text-blue-700">Total Planejado</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-2xl font-bold text-green-900" id="total-produced">0</div>
                                            <div class="text-sm text-green-700">Total Produzido</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-orange-50 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-2xl font-bold text-orange-900" id="total-remaining">0</div>
                                            <div class="text-sm text-orange-700">Total Pendente</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progresso da Produção</span>
                                    <span id="progress-percentage">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <div id="production-content">
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum item para produção</h3>
                                <p class="text-gray-600">Os itens de produção aparecerão aqui.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                    <input type="hidden" id="production-context" name="context">

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

<!-- Daily Items Chart Modal -->
<div id="items-chart-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Evolução Diária dos Itens</h3>
                    <button onclick="closeItemsChart()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="chart-container" class="h-96">
                    <div class="flex items-center justify-center h-full text-gray-500">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p>Carregando gráfico...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></script>
<script>
    // Dashboard JavaScript functionality
    let currentTab = 'ativos';
    let selectedOrders = new Set();
    let selectedRemovedOrders = new Set();
    let selectedItems = new Set();
    let currentData = null;
    let sortDirection = 'desc';
    let selectedItemFilters = new Set();
    let selectedCategoryFilters = new Set();
    let productionSearchQuery = '';
    let categorySearchQuery = '';
    let itemsSearchQuery = '';

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial tab to 'ativos' and make it active
        currentTab = 'ativos';
        switchTab('ativos');

        // Load data after a small delay to ensure DOM is ready
        setTimeout(() => {
            loadProductionData();
        }, 100);
    });

    // Load production data
    async function loadProductionData() {
        try {
            showLoadingState();

            const from = document.getElementById('dateFromFilter').value || getDefaultFromDate();
            const to = document.getElementById('dateToFilter').value || getDefaultToDate();
            const limit = document.getElementById('limitFilter').value || null;

            const params = new URLSearchParams({
                from: from,
                to: to,
                limit: limit || ''
            });

            const response = await fetch(`/api/production?${params}`);
            const data = await response.json();

            if (data.success) {
                currentData = data;
                renderActiveOrders(data.orders);
                renderItems(data.item_aggregates);
                renderProduction(data.production_items, data.production_context);

                // Load removed orders first
                console.log('Loading removed orders...');
                await loadRemovedOrders();

                // Update counts after loading removed orders
                updateCounts(data);

                hideLoadingState();

                // Automatically switch to the first tab (ativos) after data loads
                switchTab('ativos');
            } else {
                hideLoadingState();
                showError(data.error || 'Erro ao carregar dados');
            }
        } catch (error) {
            hideLoadingState();
            showError('Erro de conexão: ' + error.message);
            console.error('Error loading data:', error);
        }
    }

    // Load removed orders
    async function loadRemovedOrders() {
        try {
            const response = await fetch('/api/production/removed-orders');
            const data = await response.json();

            if (data.success) {
                console.log('Removed orders loaded:', data.orders.length);
                renderRemovedOrders(data.orders);
                // Update the removed orders count in the tab
                document.getElementById('removed-count').textContent = data.orders.length;
                console.log('Updated removed count to:', data.orders.length);
            } else {
                console.error('Error loading removed orders:', data.error);
                renderRemovedOrders([]);
                document.getElementById('removed-count').textContent = 0;
            }
        } catch (error) {
            console.error('Error loading removed orders:', error);
            renderRemovedOrders([]);
            document.getElementById('removed-count').textContent = 0;
        }
    }

    function showLoadingState() {
        document.getElementById('loadingState').classList.remove('hidden');
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        // Hide all action buttons during loading
        document.getElementById('removeBtn').classList.add('hidden');
        document.getElementById('restoreBtn').classList.add('hidden');
        document.getElementById('exportBtn').classList.add('hidden');
    }

    function hideLoadingState() {
        document.getElementById('loadingState').classList.add('hidden');
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        errorText.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function hideError() {
        document.getElementById('errorMessage').classList.add('hidden');
    }

    function clearFilters() {
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        document.getElementById('limitFilter').value = '';
        document.getElementById('searchInput').value = '';
        clearLimit();
        loadProductionData();
    }

    function applyFilters() {
        updateFilterButtonState();
        loadProductionData();
    }

    function resetFilters() {
        const now = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(now.getDate() - 30);

        document.getElementById('dateFromFilter').value = thirtyDaysAgo.toISOString().slice(0, 16);
        document.getElementById('dateToFilter').value = now.toISOString().slice(0, 16);
        document.getElementById('limitFilter').value = '';
        document.getElementById('searchInput').value = '';
        clearLimit();
        loadProductionData();
    }

    function clearLimit() {
        document.getElementById('limitFilter').value = '';
        document.getElementById('clearLimitBtn').disabled = true;
    }

    function toggleSortDirection() {
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        document.getElementById('sortLabel').textContent = sortDirection === 'asc' ? 'ID crescente' : 'ID decrescente';
        loadProductionData();
    }

    function updateFilterButtonState() {
        const fromValue = document.getElementById('dateFromFilter').value;
        const toValue = document.getElementById('dateToFilter').value;
        const hasFilters = fromValue || toValue;
        document.getElementById('applyFiltersBtn').disabled = !hasFilters;
    }

    function getDefaultFromDate() {
        const now = new Date();
        now.setDate(now.getDate() - 30);
        return now.toISOString().slice(0, 16);
    }

    function getDefaultToDate() {
        return new Date().toISOString().slice(0, 16);
    }

    function updateCounts(data) {
        document.getElementById('active-count').textContent = data.orders.length;
        // removed-count will be updated by loadRemovedOrders function
        document.getElementById('items-count').textContent = data.item_aggregates.length;

        // Update header summary
        updateHeaderSummary(data);
    }

    function updateHeaderSummary(data) {
        const summaryText = document.getElementById('summaryText');
        const totalOrders = data.orders.length;
        const totalItems = data.item_aggregates.length;
        const selectedCount = selectedOrders.size;

        if (currentTab === 'ativos') {
            if (selectedCount > 0) {
                summaryText.textContent = `${selectedCount} de ${totalOrders} pedido${totalOrders !== 1 ? 's' : ''} selecionado${selectedCount !== 1 ? 's' : ''}`;
            } else {
                summaryText.textContent = `${totalOrders} pedido${totalOrders !== 1 ? 's' : ''} ativo${totalOrders !== 1 ? 's' : ''}`;
            }
        } else if (currentTab === 'itens') {
            summaryText.textContent = `${totalItems} item${totalItems !== 1 ? 's' : ''} agregado${totalItems !== 1 ? 's' : ''}`;
        } else if (currentTab === 'producao') {
            const totalRequired = data.production_items.reduce((sum, item) => sum + item.totalQuantity, 0);
            const totalProduced = data.production_items.reduce((sum, item) => sum + item.producedQuantity, 0);
            summaryText.textContent = `${totalProduced}/${totalRequired} produzido`;
        } else {
            summaryText.textContent = 'Carregando...';
        }
    }

    // Tab switching
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });

        // Remove active class from all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-primary', 'text-primary');
            button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700');
        });

        // Show selected tab content
        document.getElementById(`tab-${tabName}`).classList.remove('hidden');

        // Add active class to selected tab button
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        activeButton.classList.add('border-primary', 'text-primary');
        activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700');

        // Update action buttons visibility
        updateActionButtons(tabName);

        // Show/hide filters based on tab
        updateFiltersVisibility(tabName);

        currentTab = tabName;

        // Update header summary if data is available
        if (currentData) {
            updateHeaderSummary(currentData);
        }
    }

    function updateActionButtons(tabName) {
        // Hide all action buttons first
        document.getElementById('removeBtn').classList.add('hidden');
        document.getElementById('restoreBtn').classList.add('hidden');
        document.getElementById('exportBtn').classList.add('hidden');

        // Show relevant buttons based on tab
        if (tabName === 'ativos') {
            document.getElementById('removeBtn').classList.remove('hidden');
        } else if (tabName === 'removidos') {
            document.getElementById('restoreBtn').classList.remove('hidden');
        } else if (tabName === 'itens') {
            document.getElementById('exportBtn').classList.remove('hidden');
        }
    }

    function updateFiltersVisibility(tabName) {
        // Hide all filter panels
        document.getElementById('active-items-filter').classList.add('hidden');
        document.getElementById('production-filters').classList.add('hidden');

        // Show relevant filters based on tab
        if (tabName === 'ativos' && currentData && currentData.item_aggregates.length > 0) {
            document.getElementById('active-items-filter').classList.remove('hidden');
            populateItemsFilter();
        } else if (tabName === 'producao' && currentData && currentData.production_items.length > 0) {
            document.getElementById('production-filters').classList.remove('hidden');
            populateCategoryFilter();
        }
    }

    // Render functions
    function renderActiveOrders(orders) {
        const container = document.getElementById('active-orders-content');

        if (orders.length === 0) {
            container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>Nenhum pedido encontrado</p>
            </div>
        `;
            return;
        }

        let html = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;

        orders.forEach(order => {
            const orderId = order.data.ord_id || '';
            const customerName = order.data.usr_name || '';
            const customerEmail = order.data.usr_email || '';
            const isSelected = selectedOrders.has(orderId);
            const hasGeneratedRotulo = order.rotulo_generated || false;
            const rotuloGeneratedAt = order.rotulo_generated_at || '';

            // Different background colors based on rótulo generation status
            const rowClass = hasGeneratedRotulo ?
                'bg-green-50 hover:bg-green-100 border-l-4 border-green-400' :
                'hover:bg-gray-50';

            const statusIcon = hasGeneratedRotulo ?
                '<i class="fas fa-check-circle text-green-600 mr-2" title="Rótulo gerado em ' + rotuloGeneratedAt + '"></i>' :
                '';

            html += `
            <tr class="${rowClass}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="order-checkbox" value="${orderId}" ${isSelected ? 'checked' : ''} onchange="toggleOrderSelection('${orderId}')">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${statusIcon}${orderId}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${customerName}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${customerEmail}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewOrderDetails(${JSON.stringify(order).replace(/"/g, '&quot;')})" 
                        class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="removeOrder('${orderId}')" 
                        class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += `
                </tbody>
            </table>
        </div>
    `;

        container.innerHTML = html;
    }

    function renderItems(items) {
        const container = document.getElementById('items-content');

        if (items.length === 0) {
            container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-box text-4xl mb-4"></i>
                <p>Nenhum item encontrado</p>
            </div>
        `;
            return;
        }

        let html = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedidos</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;

        items.forEach(item => {
            html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.item}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${item.totalQuantity.toLocaleString()}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${item.orders.length} pedido${item.orders.length !== 1 ? 's' : ''}
                </td>
            </tr>
        `;
        });

        html += `
                </tbody>
            </table>
        </div>
    `;

        container.innerHTML = html;
    }

    function renderProduction(items, context) {
        const container = document.getElementById('production-content');
        const summaryContainer = document.getElementById('production-summary');
        document.getElementById('production-context').textContent = context;

        if (items.length === 0) {
            summaryContainer.classList.add('hidden');
            container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-industry text-4xl mb-4"></i>
                <p>Nenhum item para produção</p>
            </div>
        `;
            return;
        }

        // Show summary
        summaryContainer.classList.remove('hidden');

        // Calculate totals
        const totals = {
            required: 0,
            produced: 0,
            remaining: 0
        };

        items.forEach(item => {
            totals.required += item.totalQuantity;
            totals.produced += item.producedQuantity;
            totals.remaining += item.remainingQuantity;
        });

        const progressPercentage = totals.required > 0 ? (totals.produced / totals.required) * 100 : 0;

        // Update summary cards
        document.getElementById('total-required').textContent = totals.required.toLocaleString();
        document.getElementById('total-produced').textContent = totals.produced.toLocaleString();
        document.getElementById('total-remaining').textContent = totals.remaining.toLocaleString();
        document.getElementById('progress-percentage').textContent = progressPercentage.toFixed(1) + '%';
        document.getElementById('progress-bar').style.width = Math.min(100, progressPercentage) + '%';

        // Group items by category
        const groupedItems = {};
        items.forEach(item => {
            const category = item.categoryLabel || 'Sem categoria';
            if (!groupedItems[category]) {
                groupedItems[category] = [];
            }
            groupedItems[category].push(item);
        });

        let html = `
        <!-- Production Items by Category -->
        <div class="space-y-6">
    `;

        Object.keys(groupedItems).forEach(category => {
            const categoryItems = groupedItems[category];
            const categoryTotals = {
                required: categoryItems.reduce((sum, item) => sum + item.totalQuantity, 0),
                produced: categoryItems.reduce((sum, item) => sum + item.producedQuantity, 0),
                remaining: categoryItems.reduce((sum, item) => sum + item.remainingQuantity, 0)
            };
            const categoryProgress = categoryTotals.required > 0 ? (categoryTotals.produced / categoryTotals.required) * 100 : 0;

            html += `
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <h4 class="text-lg font-semibold text-gray-900">${category}</h4>
                        <span class="text-sm text-gray-500">${categoryItems.length} item${categoryItems.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        ${categoryTotals.produced.toLocaleString()} / ${categoryTotals.required.toLocaleString()}
            </div>
        </div>

                <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Progresso da categoria</span>
                        <span>${categoryProgress.toFixed(1)}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: ${Math.min(100, categoryProgress)}%"></div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planejado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produzido</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendente</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;

            categoryItems.forEach(item => {
                const itemProgress = item.totalQuantity > 0 ? (item.producedQuantity / item.totalQuantity) * 100 : 0;

                html += `
            <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${item.item}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${item.totalQuantity.toLocaleString()}
                    </span>
                </td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                        <div class="flex items-center space-x-2">
                            <input type="number" 
                                value="${item.producedQuantity}" 
                                min="0" 
                                max="${item.totalQuantity}"
                                onchange="updateProductionItemQuantity('${item.item}', this.value, ${item.totalQuantity})"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <span class="text-xs text-gray-500">${itemProgress.toFixed(1)}%</span>
                        </div>
                </td>
                    <td class="px-4 py-3 text-sm text-gray-900">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        ${item.remainingQuantity.toLocaleString()}
                    </span>
                </td>
                    <td class="px-4 py-3 text-sm font-medium">
                        <div class="flex space-x-2">
                            <button onclick="completeProductionItem('${item.item}', ${item.totalQuantity})" 
                                class="text-green-600 hover:text-green-900 text-xs">
                                Concluir
                            </button>
                            <button onclick="clearProductionItem('${item.item}')" 
                                class="text-red-600 hover:text-red-900 text-xs">
                                Limpar
                            </button>
                        </div>
                </td>
            </tr>
        `;
            });

            html += `
                </tbody>
            </table>
                </div>
        </div>
    `;
        });

        html += `</div>`;

        container.innerHTML = html;
    }

    function renderRemovedOrders(orders) {
        const container = document.getElementById('removed-orders-content');

        if (orders.length === 0) {
            container.innerHTML = `
            <div class="text-center py-16">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum pedido removido</h3>
                <p class="text-gray-600">Os pedidos removidos aparecerão aqui.</p>
            </div>
        `;
            return;
        }

        let html = `
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="select-all-removed" onchange="toggleSelectAllRemoved(this)">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Itens</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Removido em</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        `;

        orders.forEach(order => {
            const orderId = order.data.ord_id || '';
            const customerName = order.data.usr_name || '';
            const customerEmail = order.data.usr_email || '';
            const removedAt = order.removed_at || '';
            const removedBy = order.removed_by || 'system';
            const isSelected = selectedRemovedOrders.has(orderId);
            const hasGeneratedRotulo = order.rotulo_generated || false;
            const rotuloGeneratedAt = order.rotulo_generated_at || '';

            // Different background colors based on rótulo generation status
            const rowClass = hasGeneratedRotulo ?
                'bg-green-50 hover:bg-green-100 border-l-4 border-green-400' :
                'hover:bg-gray-50';

            const statusIcon = hasGeneratedRotulo ?
                '<i class="fas fa-check-circle text-green-600 mr-2" title="Rótulo gerado em ' + rotuloGeneratedAt + '"></i>' :
                '';

            html += `
            <tr class="${rowClass}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="removed-order-checkbox" value="${orderId}" ${isSelected ? 'checked' : ''} onchange="toggleRemovedOrderSelection('${orderId}')">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${statusIcon}${orderId}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${customerName}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${customerEmail}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${order.items_count || 0} item${(order.items_count || 0) !== 1 ? 's' : ''}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex flex-col">
                        <span>${new Date(removedAt).toLocaleDateString('pt-BR')}</span>
                        <span class="text-xs text-gray-400">${new Date(removedAt).toLocaleTimeString('pt-BR')}</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewRemovedOrderDetails(${JSON.stringify(order).replace(/"/g, '&quot;')})" 
                        class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        html += `
                </tbody>
            </table>
        </div>
    `;

        container.innerHTML = html;
    }

    // Order operations
    function removeOrder(orderId) {
        if (confirm('Tem certeza que deseja remover este pedido?')) {
            removeSelectedOrders([orderId]);
        }
    }

    async function removeSelectedOrders(orderIds = null) {
        if (!orderIds) {
            orderIds = Array.from(selectedOrders);
        }

        if (orderIds.length === 0) {
            alert('Selecione pelo menos um pedido');
            return;
        }

        if (confirm(`Tem certeza que deseja remover ${orderIds.length} pedido(s)?`)) {
            try {
                const response = await fetch('/api/production/remove-orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_ids: orderIds
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Clear selections after successful removal
                    selectedOrders.clear();
                    // Reload data to update counts and display
                    await loadProductionData();
                } else {
                    alert('Erro ao remover pedidos: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erro ao remover pedidos');
            }
        }
    }

    function clearAllSelections() {
        selectedOrders.clear();
        selectedRemovedOrders.clear();
        selectedItems.clear();

        // Uncheck all checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
        });
    }

    // Debug function to check selection state
    function debugSelectionState() {
        console.log('Selected Orders:', Array.from(selectedOrders));
        console.log('Selected Removed Orders:', Array.from(selectedRemovedOrders));
        console.log('Selected Items:', Array.from(selectedItems));
        console.log('Checkboxes checked:', document.querySelectorAll('.order-checkbox:checked').length);
    }

    // Production functions
    function updateProductionQuantity(item, totalQuantity, currentQuantity) {
        const modal = document.getElementById('production-modal');
        document.getElementById('production-item').value = item;
        document.getElementById('production-item-name').textContent = item;
        document.getElementById('production-total').textContent = totalQuantity.toLocaleString();
        document.getElementById('production-quantity').value = currentQuantity;
        document.getElementById('production-quantity').max = totalQuantity;
        document.getElementById('production-context').value = currentData?.production_context || '';
        modal.classList.remove('hidden');
    }

    function closeProductionModal() {
        document.getElementById('production-modal').classList.add('hidden');
    }

    // Production form submission
    document.getElementById('production-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const data = {
            context: formData.get('context'),
            item: formData.get('item'),
            quantity: parseInt(formData.get('quantity'))
        };

        try {
            const response = await fetch('/api/production/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                closeProductionModal();
                loadProductionData();
            } else {
                alert('Erro ao atualizar produção: ' + result.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erro ao atualizar produção');
        }
    });

    // Order details modal
    function viewOrderDetails(order) {
        const modal = document.getElementById('order-modal');
        const details = document.getElementById('order-details');

        let html = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID do Pedido</label>
                    <p class="text-sm text-gray-900">${order.data.ord_id || '—'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                    <p class="text-sm text-gray-900">${order.data.usr_name || '—'}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="text-sm text-gray-900">${order.data.usr_email || '—'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <p class="text-sm text-gray-900">${order.data.usr_phone || '—'}</p>
                </div>
            </div>
    `;

        if (order.items && order.items.length > 0) {
            html += `
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Itens do Pedido</label>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
        `;

            order.items.forEach(item => {
                html += `
                <tr>
                    <td class="px-3 py-2 text-sm text-gray-900">${item.itm_name || '—'}</td>
                    <td class="px-3 py-2 text-sm text-gray-900">${item.quantity || '—'}</td>
                </tr>
            `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        }

        html += `</div>`;

        details.innerHTML = html;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('order-modal').classList.add('hidden');
    }

    // Utility functions
    function refreshProduction() {
        loadProductionData();
    }

    function printSelected() {
        window.print();
    }

    function selectAllOrders() {
        // Clear existing selections
        selectedOrders.clear();

        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
            selectedOrders.add(cb.value);
        });
        document.getElementById('select-all').checked = true;
        document.getElementById('select-all').indeterminate = false;
    }

    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.order-checkbox');

        if (checkbox.checked) {
            // Select all
            selectedOrders.clear();
            checkboxes.forEach(cb => {
                cb.checked = true;
                selectedOrders.add(cb.value);
            });
        } else {
            // Deselect all
            selectedOrders.clear();
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
        }

        checkbox.indeterminate = false;
    }

    function toggleOrderSelection(orderId) {
        if (selectedOrders.has(orderId)) {
            selectedOrders.delete(orderId);
        } else {
            selectedOrders.add(orderId);
        }

        // Update the select-all checkbox state
        const selectAllCheckbox = document.getElementById('select-all');
        const allCheckboxes = document.querySelectorAll('.order-checkbox');
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;

        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === allCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }

        // Update header summary if we're on the active orders tab
        if (currentTab === 'ativos' && currentData) {
            updateHeaderSummary(currentData);
        }
    }

    function selectAllRemovedOrders() {
        const checkboxes = document.querySelectorAll('.removed-order-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
            selectedRemovedOrders.add(cb.value);
        });
        document.getElementById('select-all-removed').checked = true;
        document.getElementById('select-all-removed').indeterminate = false;
    }

    function toggleSelectAllRemoved(checkbox) {
        const checkboxes = document.querySelectorAll('.removed-order-checkbox');

        if (checkbox.checked) {
            // Select all
            selectedRemovedOrders.clear();
            checkboxes.forEach(cb => {
                cb.checked = true;
                selectedRemovedOrders.add(cb.value);
            });
        } else {
            // Deselect all
            selectedRemovedOrders.clear();
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
        }

        checkbox.indeterminate = false;
    }

    function toggleRemovedOrderSelection(orderId) {
        if (selectedRemovedOrders.has(orderId)) {
            selectedRemovedOrders.delete(orderId);
        } else {
            selectedRemovedOrders.add(orderId);
        }

        // Update the select-all checkbox state
        const selectAllCheckbox = document.getElementById('select-all-removed');
        const allCheckboxes = document.querySelectorAll('.removed-order-checkbox');
        const checkedCount = document.querySelectorAll('.removed-order-checkbox:checked').length;

        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === allCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    function viewRemovedOrderDetails(order) {
        // Implementation for viewing removed order details
        console.log('View removed order details:', order);
        // You can implement a modal or redirect to show order details
    }

    async function restoreSelectedOrders() {
        const orderIds = Array.from(selectedRemovedOrders);

        if (orderIds.length === 0) {
            alert('Selecione pelo menos um pedido para restaurar');
            return;
        }

        if (confirm(`Tem certeza que deseja restaurar ${orderIds.length} pedido(s)?`)) {
            try {
                const response = await fetch('/api/production/restore-orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_ids: orderIds
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Clear selections after successful restoration
                    selectedRemovedOrders.clear();
                    // Reload data to update counts and display
                    await loadProductionData();
                } else {
                    alert('Erro ao restaurar pedidos: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erro ao restaurar pedidos');
            }
        }
    }

    function selectAllItems() {
        // Implementation for items selection
    }

    async function downloadItemsReport() {
        if (!currentData || !currentData.item_aggregates || currentData.item_aggregates.length === 0) {
            alert('Nenhum item disponível para exportar');
            return;
        }

        const exportBtn = document.getElementById('exportBtn');
        const originalText = exportBtn.innerHTML;

        try {
            // Show loading state
            exportBtn.innerHTML = `
                <svg class="w-4 h-4 sm:w-5 sm:h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm sm:text-base">Gerando planilha...</span>
            `;
            exportBtn.disabled = true;

            const response = await fetch('/api/reports/items-summary', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    items: currentData.item_aggregates,
                    dateRange: {
                        from: document.getElementById('dateFromFilter').value,
                        to: document.getElementById('dateToFilter').value
                    }
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `itens-agregados-${new Date().toISOString().slice(0, 10)}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Erro ao gerar planilha');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erro ao exportar planilha: ' + error.message);
        } finally {
            // Restore button state
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }
    }

    // Items Filter Functions
    function toggleItemsFilter() {
        const dropdown = document.getElementById('itemsFilterDropdown');
        dropdown.classList.toggle('hidden');
    }

    function closeItemsFilter() {
        document.getElementById('itemsFilterDropdown').classList.add('hidden');
    }

    function clearItemsFilter() {
        selectedItemFilters.clear();
        updateItemsFilterDisplay();
        loadProductionData();
    }

    function populateItemsFilter() {
        if (!currentData || !currentData.item_aggregates) return;

        const container = document.getElementById('itemsFilterList');
        container.innerHTML = '';

        currentData.item_aggregates.forEach(item => {
            const isSelected = selectedItemFilters.has(item.item);
            const itemElement = document.createElement('div');
            itemElement.className = 'flex items-start gap-3 rounded-md border border-transparent p-2 text-left transition-colors hover:border-border hover:bg-gray-50';
            itemElement.innerHTML = `
                <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleItemFilter('${item.item.replace(/'/g, "\\'")}')" class="mt-1" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${item.item}</p>
                    <p class="text-xs text-gray-500">${item.totalQuantity} unidades</p>
                </div>
            `;
            container.appendChild(itemElement);
        });
    }

    function toggleItemFilter(itemName) {
        if (selectedItemFilters.has(itemName)) {
            selectedItemFilters.delete(itemName);
        } else {
            selectedItemFilters.add(itemName);
        }
        updateItemsFilterDisplay();
        loadProductionData();
    }

    function updateItemsFilterDisplay() {
        const count = selectedItemFilters.size;
        const text = count > 0 ? `${count} item${count === 1 ? ' selecionado' : 's selecionados'}` : 'Filtrar por item';
        document.getElementById('itemsFilterText').textContent = text;
    }

    // Category Filter Functions
    function toggleCategoryFilter() {
        const dropdown = document.getElementById('categoryFilterDropdown');
        dropdown.classList.toggle('hidden');
    }

    function closeCategoryFilter() {
        document.getElementById('categoryFilterDropdown').classList.add('hidden');
    }

    function clearCategoryFilter() {
        selectedCategoryFilters.clear();
        updateCategoryFilterDisplay();
        loadProductionData();
    }

    function populateCategoryFilter() {
        if (!currentData || !currentData.production_items) return;

        const categories = new Map();
        currentData.production_items.forEach(item => {
            if (item.categoryLabel) {
                if (!categories.has(item.categoryLabel)) {
                    categories.set(item.categoryLabel, 0);
                }
                categories.set(item.categoryLabel, categories.get(item.categoryLabel) + 1);
            }
        });

        const container = document.getElementById('categoryFilterList');
        container.innerHTML = '';

        categories.forEach((count, category) => {
            const isSelected = selectedCategoryFilters.has(category);
            const categoryElement = document.createElement('div');
            categoryElement.className = 'flex items-start gap-3 rounded-md border border-transparent p-2 text-left transition-colors hover:border-border hover:bg-gray-50';
            categoryElement.innerHTML = `
                <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleCategoryFilterItem('${category.replace(/'/g, "\\'")}')" class="mt-1" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${category}</p>
                    <p class="text-xs text-gray-500">${count} item${count === 1 ? '' : 's'}</p>
                </div>
            `;
            container.appendChild(categoryElement);
        });
    }

    function toggleCategoryFilterItem(categoryName) {
        if (selectedCategoryFilters.has(categoryName)) {
            selectedCategoryFilters.delete(categoryName);
        } else {
            selectedCategoryFilters.add(categoryName);
        }
        updateCategoryFilterDisplay();
        loadProductionData();
    }

    function updateCategoryFilterDisplay() {
        const count = selectedCategoryFilters.size;
        const text = count > 0 ? `${count} categoria${count === 1 ? ' selecionada' : 's selecionadas'}` : 'Filtrar por categoria';
        document.getElementById('categoryFilterText').textContent = text;
    }

    function clearProductionFilters() {
        productionSearchQuery = '';
        selectedCategoryFilters.clear();
        document.getElementById('productionSearchInput').value = '';
        updateCategoryFilterDisplay();
        loadProductionData();
    }

    // Chart Functions
    function openItemsChart() {
        if (!currentData || !currentData.item_aggregates || currentData.item_aggregates.length === 0) {
            alert('Nenhum dado disponível para gerar o gráfico');
            return;
        }

        document.getElementById('items-chart-modal').classList.remove('hidden');
        generateItemsChart();
    }

    function closeItemsChart() {
        document.getElementById('items-chart-modal').classList.add('hidden');
    }

    function generateItemsChart() {
        const container = document.getElementById('chart-container');

        // Simple chart implementation using basic HTML/CSS
        // In a real implementation, you would use a charting library like Chart.js or D3.js
        const chartData = currentData.item_aggregates.slice(0, 10); // Show top 10 items

        let chartHTML = `
            <div class="space-y-4">
                <div class="text-sm text-gray-600 mb-4">
                    Mostrando evolução dos ${chartData.length} principais itens
                </div>
        `;

        chartData.forEach((item, index) => {
            const percentage = Math.min(100, (item.totalQuantity / Math.max(...chartData.map(i => i.totalQuantity))) * 100);
            chartHTML += `
                <div class="flex items-center space-x-4">
                    <div class="w-32 text-sm font-medium text-gray-900 truncate">${item.item}</div>
                    <div class="flex-1 bg-gray-200 rounded-full h-4">
                        <div class="bg-blue-600 h-4 rounded-full" style="width: ${percentage}%"></div>
                    </div>
                    <div class="w-20 text-sm text-gray-600 text-right">${item.totalQuantity.toLocaleString()}</div>
                </div>
            `;
        });

        chartHTML += `</div>`;
        container.innerHTML = chartHTML;
    }

    // Production Action Functions
    async function updateProductionItemQuantity(itemName, quantity, totalQuantity) {
        const qty = parseInt(quantity) || 0;
        if (qty < 0 || qty > totalQuantity) {
            alert('Quantidade inválida');
            return;
        }

        try {
            const response = await fetch('/api/production/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    context: currentData?.production_context || '',
                    item: itemName,
                    quantity: qty
                })
            });

            const result = await response.json();
            if (result.success) {
                loadProductionData();
            } else {
                alert('Erro ao atualizar produção: ' + result.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erro ao atualizar produção');
        }
    }

    async function completeProductionItem(itemName, totalQuantity) {
        await updateProductionItemQuantity(itemName, totalQuantity, totalQuantity);
    }

    async function clearProductionItem(itemName) {
        await updateProductionItemQuantity(itemName, 0, 0);
    }

    // Search functionality
    document.getElementById('searchInput')?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        // Implement search filtering
        applyFilters();
    });

    // Production search functionality
    document.getElementById('productionSearchInput')?.addEventListener('input', function() {
        productionSearchQuery = this.value.toLowerCase();
        loadProductionData();
    });

    // Items search functionality
    document.getElementById('itemsSearchInput')?.addEventListener('input', function() {
        itemsSearchQuery = this.value.toLowerCase();
        filterItemsList();
    });

    // Category search functionality
    document.getElementById('categorySearchInput')?.addEventListener('input', function() {
        categorySearchQuery = this.value.toLowerCase();
        filterCategoryList();
    });

    // Limit filter functionality
    document.getElementById('limitFilter')?.addEventListener('input', function() {
        const hasValue = this.value.trim() !== '';
        document.getElementById('clearLimitBtn').disabled = !hasValue;
        loadProductionData();
    });

    // Date filter functionality
    document.getElementById('dateFromFilter')?.addEventListener('change', function() {
        updateFilterButtonState();
    });

    document.getElementById('dateToFilter')?.addEventListener('change', function() {
        updateFilterButtonState();
    });

    function filterItemsList() {
        const container = document.getElementById('itemsFilterList');
        const items = container.querySelectorAll('div');

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const matches = text.includes(itemsSearchQuery);
            item.style.display = matches ? 'flex' : 'none';
        });
    }

    function filterCategoryList() {
        const container = document.getElementById('categoryFilterList');
        const categories = container.querySelectorAll('div');

        categories.forEach(category => {
            const text = category.textContent.toLowerCase();
            const matches = text.includes(categorySearchQuery);
            category.style.display = matches ? 'flex' : 'none';
        });
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>