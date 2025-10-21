// Dashboard JavaScript functionality

let currentTab = 'ativos';
let selectedOrders = new Set();
let selectedRemovedOrders = new Set();
let selectedItems = new Set();

// Tab switching
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(`tab-${tabName}`).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
    activeButton.classList.add('active', 'border-blue-500', 'text-blue-600');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    
    currentTab = tabName;
}

// Order selection
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        if (checkbox.checked) {
            selectedOrders.add(cb.value);
        } else {
            selectedOrders.delete(cb.value);
        }
    });
}

function selectAllOrders() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = true;
        selectedOrders.add(cb.value);
    });
    document.getElementById('select-all').checked = true;
}

// Removed orders selection
function toggleSelectAllRemoved(checkbox) {
    const checkboxes = document.querySelectorAll('.removed-order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        if (checkbox.checked) {
            selectedRemovedOrders.add(cb.value);
        } else {
            selectedRemovedOrders.delete(cb.value);
        }
    });
}

function selectAllRemovedOrders() {
    const checkboxes = document.querySelectorAll('.removed-order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = true;
        selectedRemovedOrders.add(cb.value);
    });
    document.getElementById('select-all-removed').checked = true;
}

// Items selection
function toggleSelectAllItems(checkbox) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
        if (checkbox.checked) {
            selectedItems.add(cb.value);
        } else {
            selectedItems.delete(cb.value);
        }
    });
}

function selectAllItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = true;
        selectedItems.add(cb.value);
    });
    document.getElementById('select-all-items').checked = true;
}

// Order operations
function removeOrder(orderId) {
    if (confirm('Tem certeza que deseja remover este pedido?')) {
        fetch(`?action=remove_orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_ids=${encodeURIComponent(JSON.stringify([orderId]))}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.removed) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao remover pedido');
        });
    }
}

function removeSelectedOrders() {
    if (selectedOrders.size === 0) {
        alert('Selecione pelo menos um pedido');
        return;
    }
    
    if (confirm(`Tem certeza que deseja remover ${selectedOrders.size} pedido(s)?`)) {
        fetch(`?action=remove_orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_ids=${encodeURIComponent(JSON.stringify(Array.from(selectedOrders)))}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.removed) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao remover pedidos');
        });
    }
}

function restoreOrder(orderId) {
    if (confirm('Tem certeza que deseja restaurar este pedido?')) {
        fetch(`?action=restore_orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_ids=${encodeURIComponent(JSON.stringify([orderId]))}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.restored) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao restaurar pedido');
        });
    }
}

function restoreSelectedOrders() {
    if (selectedRemovedOrders.size === 0) {
        alert('Selecione pelo menos um pedido');
        return;
    }
    
    if (confirm(`Tem certeza que deseja restaurar ${selectedRemovedOrders.size} pedido(s)?`)) {
        fetch(`?action=restore_orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_ids=${encodeURIComponent(JSON.stringify(Array.from(selectedRemovedOrders)))}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.restored) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao restaurar pedidos');
        });
    }
}

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
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <p class="text-sm text-gray-900">${order.data.ord_status || '—'}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cliente</label>
                    <p class="text-sm text-gray-900">${order.details?.customerName || '—'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Documento</label>
                    <p class="text-sm text-gray-900">${order.details?.customerDocument || '—'}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="text-sm text-gray-900">${order.details?.customerEmail || '—'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <p class="text-sm text-gray-900">${order.details?.customerPhone || '—'}</p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Endereço</label>
                <p class="text-sm text-gray-900">${order.details?.customerAddress || '—'}</p>
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
                    <td class="px-3 py-2 text-sm text-gray-900">${item.item || '—'}</td>
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

// Production functions
function updateProductionQuantity(item, totalQuantity, currentQuantity) {
    const modal = document.getElementById('production-modal');
    document.getElementById('production-item').value = item;
    document.getElementById('production-item-name').textContent = item;
    document.getElementById('production-total').textContent = totalQuantity.toLocaleString();
    document.getElementById('production-quantity').value = currentQuantity;
    document.getElementById('production-quantity').max = totalQuantity;
    modal.classList.remove('hidden');
}

function closeProductionModal() {
    document.getElementById('production-modal').classList.add('hidden');
}

// Production form submission
document.getElementById('production-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        context: formData.get('context'),
        item: formData.get('item'),
        quantity: parseInt(formData.get('quantity'))
    };
    
    fetch(`?action=update_production`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeProductionModal();
            location.reload();
        } else {
            alert('Erro ao atualizar produção');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao atualizar produção');
    });
});

// Refresh orders
function refreshOrders() {
    location.reload();
}

// Print functionality
function printSelected() {
    window.print();
}

// Item details
function viewItemDetails(itemName, itemData) {
    const modal = document.getElementById('order-modal');
    const details = document.getElementById('order-details');
    
    let html = `
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Item</label>
                <p class="text-sm text-gray-900 font-medium">${itemName}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Quantidade Total</label>
                <p class="text-sm text-gray-900">${itemData.totalQuantity.toLocaleString()}</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Pedidos (${itemData.orders.length})</label>
                <div class="mt-2 space-y-2 max-h-40 overflow-y-auto">
    `;
    
    itemData.orders.forEach(order => {
        const orderId = order.data.ord_id || '—';
        const customerName = order.details?.customerName || '—';
        html += `
            <div class="text-xs bg-gray-50 p-2 rounded">
                <strong>Pedido ${orderId}</strong> - ${customerName}
            </div>
        `;
    });
    
    html += `
                </div>
            </div>
        </div>
    `;
    
    details.innerHTML = html;
    modal.classList.remove('hidden');
}

// Download items report
function downloadItemsReport() {
    // This would typically generate and download an Excel file
    alert('Funcionalidade de exportação será implementada');
}

// Search functionality
document.getElementById('search')?.addEventListener('input', function() {
    const query = this.value;
    const url = new URL(window.location);
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    window.location.href = url.toString();
});

// Date filter functionality
document.getElementById('from-date')?.addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('from', this.value);
    window.location.href = url.toString();
});

document.getElementById('to-date')?.addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('to', this.value);
    window.location.href = url.toString();
});

// Limit functionality
document.getElementById('limit')?.addEventListener('change', function() {
    const url = new URL(window.location);
    if (this.value) {
        url.searchParams.set('limit', this.value);
    } else {
        url.searchParams.delete('limit');
    }
    window.location.href = url.toString();
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Set up checkbox event listeners
    document.querySelectorAll('.order-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedOrders.add(this.value);
            } else {
                selectedOrders.delete(this.value);
            }
        });
    });
    
    document.querySelectorAll('.removed-order-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedRemovedOrders.add(this.value);
            } else {
                selectedRemovedOrders.delete(this.value);
            }
        });
    });
    
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedItems.add(this.value);
            } else {
                selectedItems.delete(this.value);
            }
        });
    });
});
