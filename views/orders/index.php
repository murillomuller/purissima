<?php
$content = ob_get_clean();
$fullWidth = true;
$title = 'Gestão de Pedidos';
ob_start();
?>

<div class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Page Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-full">
        <div class="flex items-center justify-end mb-6">
            <!-- Action Button -->
            <button onclick="refreshOrders()" 
                    class="bg-primary hover:bg-secondary text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex items-center space-x-2 group">
                <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>Atualizar Pedidos</span>
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

        <!-- Empty State -->
        <div id="emptyState" class="text-center py-16 hidden">
            <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center shadow-lg">
                <svg class="w-16 h-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="flex items-center justify-center space-x-2 mb-4">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900">Nenhum pedido encontrado</h3>
            </div>
            <p class="text-gray-600 text-lg">Não há pedidos disponíveis no momento.</p>
        </div>

        <!-- Orders Table -->
        <div id="ordersTable" class="bg-white rounded-xl shadow-lg overflow-hidden hidden w-full">
            <div class="px-6 py-4 bg-gradient-to-r from-primary to-secondary">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <h2 class="text-xl font-bold text-white">Lista de Pedidos</h2>
                </div>
                <div class="flex items-center space-x-2 mt-1">
                    <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <p id="ordersCount" class="text-white/80">0 pedido(s) encontrado(s)</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php
                            $columns = [
                                'ord_id' => 'ID do Pedido',
                                'usr_name' => 'Cliente',
                                'usr_email' => 'E-mail',
                                'chg_status' => 'Status',
                                'items_count' => 'Itens',
                                'created_at' => 'Data'
                            ];
                            foreach ($columns as $key => $label) {
                                echo "
                                <th onclick=\"sortTable('$key')\" class='px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200'>
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
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
                                <div class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                    </svg>
                                    <span>Ações</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-2xl rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-primary">Detalhes do Pedido</h3>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="orderDetails" class="space-y-6"></div>
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
                <span class="text-gray-700 text-lg font-semibold">Gerando Receituário</span>
            </div>
            <p class="text-gray-600 text-sm">Aguarde enquanto processamos o pedido...</p>
        </div>
    </div>
</div>

<script>
let ordersData = [];
let currentSort = { column: 'ord_id', direction: 'desc' };

function refreshOrders() {
    loadOrders();
}

function loadOrders() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('errorMessage').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('ordersTable').classList.add('hidden');

    fetch('/orders/api')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                ordersData = data.orders;
                displayOrders(ordersData);
            } else showError(data.error || 'Erro ao carregar pedidos');
        })
        .catch(() => showError('Erro de conexão. Verifique sua internet e tente novamente.'));
}

function displayOrders(orders) {
    document.getElementById('loadingState').classList.add('hidden');
    if (!orders || orders.length === 0) {
        document.getElementById('emptyState').classList.remove('hidden');
        return;
    }

    document.getElementById('ordersCount').textContent = `${orders.length} pedido(s) encontrado(s)`;
    let sortedOrders = orders;
    if (currentSort.column !== 'ord_id' || currentSort.direction !== 'desc') {
        sortedOrders = sortOrders(orders, currentSort.column, currentSort.direction);
    }

    updateSortIndicators(currentSort.column, currentSort.direction);

    const tbody = document.getElementById('ordersTableBody');
    tbody.innerHTML = '';

    sortedOrders.forEach(o => {
        const order = o.order;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition-colors duration-200';
        tr.innerHTML = `
            <td class="px-6 py-4 font-semibold text-gray-900">#${order.ord_id}</td>
            <td class="px-6 py-4">${escapeHtml(order.usr_name)}</td>
            <td class="px-6 py-4 text-gray-600">${escapeHtml(order.usr_email)}</td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 text-xs font-semibold rounded-full ${order.chg_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    ${order.chg_status === 'paid' ? 'Pago' : 'Pendente'}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>${o.items.length} item(s)</span>
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">${new Date(order.created_at).toLocaleString('pt-BR')}</td>
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                    <button onclick="generatePrescription('${order.ord_id}')" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center space-x-2 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Receituário</span>
                    </button>
                    <button onclick="viewOrderDetails('${order.ord_id}')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold flex items-center space-x-2 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>Detalhes</span>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);
    });
    document.getElementById('ordersTable').classList.remove('hidden');
}

function sortTable(column) {
    if (currentSort.column === column)
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    else currentSort = { column, direction: 'asc' };
    displayOrders(ordersData);
}

function sortOrders(orders, column, direction) {
    return [...orders].sort((a, b) => {
        const A = a.order, B = b.order;
        let valA, valB;
        switch (column) {
            case 'ord_id': valA = +A.ord_id; valB = +B.ord_id; break;
            case 'usr_name': valA = A.usr_name.toLowerCase(); valB = B.usr_name.toLowerCase(); break;
            case 'usr_email': valA = A.usr_email.toLowerCase(); valB = B.usr_email.toLowerCase(); break;
            case 'chg_status': valA = A.chg_status; valB = B.chg_status; break;
            case 'items_count': valA = a.items.length; valB = b.items.length; break;
            case 'created_at': valA = new Date(A.created_at); valB = new Date(B.created_at); break;
        }
        return (valA < valB ? -1 : valA > valB ? 1 : 0) * (direction === 'asc' ? 1 : -1);
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

function escapeHtml(t) {
    const div = document.createElement('div');
    div.textContent = t;
    return div.innerHTML;
}

function showError(msg) {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('errorText').textContent = msg;
    document.getElementById('errorMessage').classList.remove('hidden');
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
                    <p class='text-sm text-gray-600'>Assinatura: ${i.subscription}</p>
                </div>`).join('')}
            </div>
        </div>`;
    document.getElementById('orderModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('orderModal').classList.add('hidden');
}

function generatePrescription(id) {
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
    .then(response => response.json())
    .then(data => {
        // Hide loading overlay
        document.getElementById('loadingOverlay').classList.add('hidden');
        
        if (data.success) {
            // Automatically download the PDF
            window.open(`/download-prescription?filename=${data.filename}`, '_blank');
            
            // Show success message
            showSuccessMessage(`Receituário gerado e baixado com sucesso!`);
        } else {
            // Show error message
            showErrorMessage(data.error || 'Erro ao gerar receituário');
        }
    })
    .catch(error => {
        // Hide loading overlay
        document.getElementById('loadingOverlay').classList.add('hidden');
        
        console.error('Error generating prescription:', error);
        showErrorMessage('Erro de conexão. Verifique sua internet e tente novamente.');
    })
    .finally(() => {
        // Re-enable the button
        button.disabled = false;
        button.innerHTML = originalContent;
    });
}

document.addEventListener('DOMContentLoaded', loadOrders);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
