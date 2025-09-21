// Transaction Management JavaScript
let currentTransactionId = null;
let currentSalesId = null;

// Make functions globally available
window.openAddModal = openAddModal;
window.editTransaction = editTransaction;
window.deleteTransaction = deleteTransaction;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addEventListeners();
    initializeFormValidation();
    setupAmountCalculator();
    loadPOSCart();
});

function addEventListeners() {
    // Form submission
    const transactionForm = document.getElementById('transactionForm');
    if (transactionForm) {
        transactionForm.addEventListener('submit', handleFormSubmit);
    }
    
    // POS form submission
    const posForm = document.getElementById('posForm');
    if (posForm) {
        posForm.addEventListener('submit', handlePOSFormSubmit);
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTransactions();
            }
        });
    }
    
    // Modal close on outside click
    const modal = document.getElementById('transactionModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    const posModal = document.getElementById('posModal');
    if (posModal) {
        posModal.addEventListener('click', function(e) {
            if (e.target === posModal) {
                closePOSModal();
            }
        });
    }
    
    // Close button click handler
    const closeBtn = document.querySelector('#transactionModal .close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }
    
    const posCloseBtn = document.querySelector('#posModal .close');
    if (posCloseBtn) {
        posCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closePOSModal();
        });
    }
    
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal) {
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                closeConfirmModal();
            }
        });
    }
}

function initializeFormValidation() {
    // Real-time validation and auto-save
    const form = document.getElementById('transactionForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
            autoSaveDraft(); // Auto-save on input
        });
    });
}

function setupAmountCalculator() {
    const quantityInput = document.getElementById('quantityTransaction');
    const priceInput = document.getElementById('priceTransaction');
    const amountInput = document.getElementById('amountTransaction');
    
    if (quantityInput && priceInput && amountInput) {
        [quantityInput, priceInput].forEach(input => {
            input.addEventListener('input', calculateAmount);
        });
    }
    
    // POS form calculator
    const posQuantityInput = document.getElementById('posQuantity');
    const posPriceInput = document.getElementById('posPrice');
    
    if (posQuantityInput && posPriceInput) {
        [posQuantityInput, posPriceInput].forEach(input => {
            input.addEventListener('input', calculatePOSAmount);
        });
    }
}

function calculateAmount() {
    const quantity = parseFloat(document.getElementById('quantityTransaction').value) || 0;
    const price = parseFloat(document.getElementById('priceTransaction').value) || 0;
    const amount = quantity * price;
    
    document.getElementById('amountTransaction').value = amount.toFixed(2);
}

function calculatePOSAmount() {
    const quantity = parseFloat(document.getElementById('posQuantity').value) || 0;
    const price = parseFloat(document.getElementById('posPrice').value) || 0;
    const amount = quantity * price;
    
    // Update amount display if needed
    return amount;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';

    // Clear previous error
    clearFieldError(field);

    // Validation rules
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'Field ini harus diisi';
    } else if (field.type === 'number') {
        const numValue = parseFloat(value);
        if (isNaN(numValue) || numValue < 0) {
            isValid = false;
            message = 'Harus berupa angka positif';
        }
    }

    if (!isValid) {
        showFieldError(field, message);
    }

    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorDiv = field.parentNode.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function clearFormErrors() {
    const form = document.getElementById('transactionForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        clearFieldError(input);
    });
}

// CRUD Operations
function openAddModal() {
    currentTransactionId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Transaction';
    document.getElementById('transactionForm').reset();
    clearFormErrors();
    document.getElementById('transactionModal').style.display = 'block';
    
    // Load draft if available
    setTimeout(() => {
        loadDraft();
        document.getElementById('salesTransaction').focus();
    }, 100);
}

function editTransaction(id) {
    currentTransactionId = id;
    document.getElementById('modalTitle').textContent = 'Edit Transaction';
    
    // Show modal first
    document.getElementById('transactionModal').style.display = 'block';
    
    // Show loading state
    const form = document.getElementById('transactionForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
    submitBtn.disabled = true;
    
    // Fetch transaction data
    fetch('transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && !data.error) {
            document.getElementById('transactionId').value = data.id_transaction;
            document.getElementById('salesTransaction').value = data.id_sales || '';
            document.getElementById('itemTransaction').value = data.id_item || '';
            document.getElementById('quantityTransaction').value = data.quantity || 0;
            document.getElementById('priceTransaction').value = data.price || 0;
            document.getElementById('amountTransaction').value = data.amount || 0;
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('salesTransaction').focus();
            }, 100);
        } else {
            showNotification('Gagal mengambil data transaction', 'error');
            closeModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
        closeModal();
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function deleteTransaction(id, itemName) {
    currentTransactionId = id;
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus transaction untuk item "${itemName}"?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete() {
    if (!currentTransactionId) return;
    
    // Show loading state on delete button
    const deleteBtn = document.querySelector('#confirmModal .btn-danger');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentTransactionId);
    
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('=== TRANSACTIONS CRUD RESPONSE ===');
        console.log('Data:', data);
        console.log('Success:', data.success);
        
        if (data.success) {
            console.log('✅ SUCCESS - Starting reload process...');
            showNotification(data.message, 'success');
            closeConfirmModal();
            
            // Multiple reload methods to ensure it works
            console.log('🔄 METHOD 1: window.location.href');
            window.location.href = window.location.href;
            
            setTimeout(() => {
                console.log('🔄 METHOD 2: location.reload()');
                location.reload();
            }, 50);
            
            setTimeout(() => {
                console.log('🔄 METHOD 3: window.location.reload()');
                window.location.reload();
            }, 100);
            
            setTimeout(() => {
                console.log('🔄 METHOD 4: location.assign()');
                location.assign(location.href);
            }, 150);
        } else {
            console.log('❌ FAILED:', data.message);
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    })
    .finally(() => {
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
        deleteBtn.style.opacity = '1';
    });
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    // Validate all fields
    const form = e.target;
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showNotification('Mohon perbaiki error pada form', 'error');
        return;
    }
    
    // Prepare form data
    const formData = new FormData(form);
    formData.append('action', currentTransactionId ? 'update' : 'create');
    
    // Add transaction ID for update
    if (currentTransactionId) {
        formData.append('id', currentTransactionId);
    }
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    // Submit form
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('=== TRANSACTIONS SAVE RESPONSE ===');
        console.log('Data:', data);
        console.log('Success:', data.success);
        
        if (data.success) {
            console.log('✅ SAVE SUCCESS - Starting reload process...');
            showNotification(data.message, 'success');
            clearDraft(); // Clear draft after successful save
            closeModal();
            
            // Multiple reload methods to ensure it works
            console.log('🔄 METHOD 1: window.location.href');
            window.location.href = window.location.href;
            
            setTimeout(() => {
                console.log('🔄 METHOD 2: location.reload()');
                location.reload();
            }, 50);
            
            setTimeout(() => {
                console.log('🔄 METHOD 3: window.location.reload()');
                window.location.reload();
            }, 100);
            
            setTimeout(() => {
                console.log('🔄 METHOD 4: location.assign()');
                location.assign(location.href);
            }, 150);
        } else {
            console.log('❌ SAVE FAILED:', data.message);
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    })
    .finally(() => {
        // Reset button state
        if (submitBtn) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
        }
    });
}

// POS Functions
function openPOSModal() {
    document.getElementById('posModal').style.display = 'block';
    loadPOSCart();
    setTimeout(() => {
        document.getElementById('posItem').focus();
    }, 100);
}

function closePOSModal() {
    document.getElementById('posModal').style.display = 'none';
}

function handlePOSFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'create_temp');
    
    // Calculate amount
    const quantity = parseFloat(formData.get('quantity')) || 0;
    const price = parseFloat(formData.get('price')) || 0;
    const amount = quantity * price;
    formData.set('amount', amount);
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
    submitBtn.disabled = true;
    
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.reset();
            loadPOSCart();
            location.reload(); // Full page refresh like F5
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function loadPOSCart() {
    fetch('transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_temp_data'
    })
    .then(response => response.json())
    .then(data => {
        const cartContainer = document.getElementById('posCart');
        const totalAmount = document.getElementById('posTotalAmount');
        
        if (data.transactions && data.transactions.length > 0) {
            let html = '';
            data.transactions.forEach((item, index) => {
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="item-name">${item.nama_item}</div>
                            <div class="item-details">
                                ${item.quantity} ${item.uom} × Rp ${parseFloat(item.price).toLocaleString('id-ID')}
                            </div>
                            ${item.remark ? `<div class="item-remark">${item.remark}</div>` : ''}
                        </div>
                        <div class="cart-item-actions">
                            <span class="item-amount">Rp ${parseFloat(item.amount).toLocaleString('id-ID')}</span>
                            <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id_transaction})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            cartContainer.innerHTML = html;
        } else {
            cartContainer.innerHTML = '<div class="empty-cart">Keranjang kosong</div>';
        }
        
        totalAmount.textContent = 'Rp ' + parseFloat(data.totalAmount || 0).toLocaleString('id-ID');
    })
    .catch(error => {
        console.error('Error loading cart:', error);
    });
}

function removeFromCart(id) {
    const formData = new FormData();
    formData.append('action', 'delete_temp');
    formData.append('id', id);
    
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadPOSCart();
            location.reload(); // Full page refresh like F5
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    });
}

function clearCart() {
    if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
        const formData = new FormData();
        formData.append('action', 'clear_temp');
        
        fetch('transactions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                loadPOSCart();
                location.reload(); // Full page refresh like F5
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan sistem', 'error');
        });
    }
}

function finalizeTransaction() {
    // Get sales ID from user
    const salesId = prompt('Masukkan ID Sales untuk finalisasi transaksi:');
    if (!salesId || isNaN(salesId)) {
        showNotification('ID Sales tidak valid', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'finalize');
    formData.append('sales_id', salesId);
    
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            loadPOSCart();
            location.reload(); // Full page refresh like F5
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    });
}

// Auto-fill price when item is selected
document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('itemTransaction');
    const priceInput = document.getElementById('priceTransaction');
    
    if (itemSelect && priceInput) {
        itemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price) {
                priceInput.value = price;
                calculateAmount();
            }
        });
    }
    
    // POS form auto-fill
    const posItemSelect = document.getElementById('posItem');
    const posPriceInput = document.getElementById('posPrice');
    
    if (posItemSelect && posPriceInput) {
        posItemSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            if (price) {
                posPriceInput.value = price;
            }
        });
    }
});

function searchTransactions() {
    const searchTerm = document.getElementById('searchInput').value;
    const url = new URL(window.location);
    url.searchParams.set('search', searchTerm);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function closeModal() {
    const modal = document.getElementById('transactionModal');
    const form = document.getElementById('transactionForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (form) {
        form.reset();
        clearFormErrors();
    }
    
    currentTransactionId = null;
    
    // Clear draft when closing modal
    clearDraft();
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentTransactionId = null;
}

// Refresh table without page reload
function refreshTable() {
    const searchTerm = document.getElementById('searchInput').value;
    const currentPage = getCurrentPage();
    
    // Show loading state
    showTableLoading();
    
    // Prepare form data for AJAX request
    const formData = new FormData();
    formData.append('action', 'get_table_data');
    formData.append('search', searchTerm);
    formData.append('page', currentPage);
    
    // Fetch updated data
    fetch('transactions.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            // Update table body
            const tableBody = document.querySelector('#transactionsTableBody');
            if (tableBody && data.transactions) {
                let html = '';
                if (data.transactions.length > 0) {
                    data.transactions.forEach((transaction, index) => {
                        const doNumber = transaction.do_number || `SALE-${transaction.id_sales}`;
                        const customerName = transaction.nama_customer || 'Customer Umum';
                        const tglSales = new Date(transaction.tgl_sales);
                        const formattedDate = tglSales.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        html += `
                            <tr data-id="${transaction.id_transaction}">
                                <td>${data.offset + index + 1}</td>
                                <td>${escapeHtml(doNumber)}</td>
                                <td>${escapeHtml(customerName)}</td>
                                <td>${escapeHtml(transaction.nama_item)}</td>
                                <td>${parseFloat(transaction.quantity).toFixed(3)} ${escapeHtml(transaction.uom)}</td>
                                <td>Rp ${parseFloat(transaction.price).toLocaleString('id-ID')}</td>
                                <td>Rp ${parseFloat(transaction.amount).toLocaleString('id-ID')}</td>
                                <td>${formattedDate}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editTransaction(${transaction.id_transaction})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteTransaction(${transaction.id_transaction}, '${escapeHtml(transaction.nama_item)}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="9" class="text-center">Tidak ada data transaction</td></tr>';
                }
                tableBody.innerHTML = html;
            }
            
            // Update results count
            const resultsCount = document.querySelector('.results-count');
            if (resultsCount) {
                resultsCount.textContent = `Total: ${data.totalCount} transaction`;
            }
            
            // Update pagination
            updatePagination(data.currentPage, data.totalPages, searchTerm);
            
            hideTableLoading();
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
            hideTableLoading();
            showNotification('Gagal memperbarui tabel', 'error');
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Update pagination
function updatePagination(currentPage, totalPages, searchTerm) {
    const paginationContainer = document.querySelector('.pagination');
    if (!paginationContainer) return;
    
    let html = '';
    
    // Previous button
    if (currentPage > 1) {
        html += `<a href="?page=${currentPage - 1}&search=${encodeURIComponent(searchTerm)}" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                 </a>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<a href="?page=${i}&search=${encodeURIComponent(searchTerm)}" 
                    class="page-btn ${i === currentPage ? 'active' : ''}">
                    ${i}
                 </a>`;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<a href="?page=${currentPage + 1}&search=${encodeURIComponent(searchTerm)}" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                 </a>`;
    }
    
    paginationContainer.innerHTML = html;
}

function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('page') || 1;
}

function showTableLoading() {
    const tableBody = document.querySelector('#transactionsTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-spinner fa-spin"></i>
                        Memperbarui data...
                    </div>
                </td>
            </tr>
        `;
    }
}

function hideTableLoading() {
    // Loading will be replaced by actual data in refreshTable
}

function exportData() {
    // Create CSV content
    const table = document.getElementById('transactionsTable');
    const rows = table.querySelectorAll('tr');
    
    let csv = 'No,DO Number,Customer,Item,Quantity,Price,Amount,Tanggal\n';
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].querySelectorAll('td');
        if (cells.length > 0) {
            const row = [];
            for (let j = 0; j < cells.length - 1; j++) { // Exclude action column
                let cellText = cells[j].textContent.trim();
                // Clean up price and amount data
                if (j === 5 || j === 6) {
                    cellText = cellText.replace(/Rp\s*/g, '').replace(/\./g, '');
                }
                row.push(`"${cellText}"`);
            }
            csv += row.join(',') + '\n';
        }
    }
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'transactions_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-save functionality
function autoSaveDraft() {
    const form = document.getElementById('transactionForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Save to localStorage
    localStorage.setItem('transaction_draft', JSON.stringify(data));
}

function loadDraft() {
    const form = document.getElementById('transactionForm');
    if (!form) return;
    
    const draft = localStorage.getItem('transaction_draft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key];
                }
            });
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
}

// Clear draft when form is submitted successfully
function clearDraft() {
    localStorage.removeItem('transaction_draft');
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    // Create notification container if it doesn't exist
    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    // Set colors based on type
    const colors = {
        success: { bg: '#f0fff4', border: '#48bb78', text: '#22543d', icon: 'check-circle' },
        error: { bg: '#fed7d7', border: '#e53e3e', text: '#742a2a', icon: 'exclamation-circle' },
        info: { bg: '#ebf8ff', border: '#667eea', text: '#2a4365', icon: 'info-circle' },
        warning: { bg: '#fef5e7', border: '#ed8936', text: '#744210', icon: 'exclamation-triangle' }
    };
    
    const color = colors[type] || colors.info;
    
    notification.style.cssText = `
        background: ${color.bg};
        border-left: 4px solid ${color.border};
        color: ${color.text};
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
    `;
    
    // Add icon
    const icon = document.createElement('i');
    icon.className = `fas fa-${color.icon}`;
    icon.style.fontSize = '16px';
    
    // Add message
    const messageSpan = document.createElement('span');
    messageSpan.textContent = message;
    messageSpan.style.flex = '1';
    
    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        opacity: 0.7;
        transition: opacity 0.2s;
    `;
    closeBtn.addEventListener('click', () => {
        removeNotification(notification);
    });
    closeBtn.addEventListener('mouseenter', () => {
        closeBtn.style.opacity = '1';
    });
    closeBtn.addEventListener('mouseleave', () => {
        closeBtn.style.opacity = '0.7';
    });
    
    notification.appendChild(icon);
    notification.appendChild(messageSpan);
    notification.appendChild(closeBtn);
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove after 4 seconds
    const autoRemoveTimer = setTimeout(() => {
        removeNotification(notification);
    }, 4000);
    
    // Store timer reference for manual close
    notification._autoRemoveTimer = autoRemoveTimer;
}

function removeNotification(notification) {
    if (notification._autoRemoveTimer) {
        clearTimeout(notification._autoRemoveTimer);
    }
    
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}
