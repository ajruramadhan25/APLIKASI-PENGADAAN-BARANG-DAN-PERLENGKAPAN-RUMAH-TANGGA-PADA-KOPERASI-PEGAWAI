// Sales Management JavaScript
let currentSalesId = null;

// Make functions globally available
window.openAddModal = openAddModal;
window.editSales = editSales;
window.deleteSales = deleteSales;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addEventListeners();
    initializeFormValidation();
    setDefaultDateTime();
});

function addEventListeners() {
    // Form submission
    const salesForm = document.getElementById('salesForm');
    if (salesForm) {
        salesForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchSales();
            }
        });
    }
    
    // Modal close on outside click
    const modal = document.getElementById('salesModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // Close button click handler
    const closeBtn = document.querySelector('#salesModal .close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
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

function setDefaultDateTime() {
    // Set default datetime to current time
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const defaultDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    
    // Set default value for datetime input
    const tglSalesInput = document.getElementById('tglSales');
    if (tglSalesInput && !tglSalesInput.value) {
        tglSalesInput.value = defaultDateTime;
    }
}

function initializeFormValidation() {
    // Real-time validation and auto-save
    const form = document.getElementById('salesForm');
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
    } else if (field.type === 'datetime-local') {
        if (value) {
            const inputDate = new Date(value);
            const now = new Date();
            
            // Check if date is in the future (more than 1 hour)
            const timeDiff = inputDate.getTime() - now.getTime();
            if (timeDiff > 3600000) { // 1 hour in milliseconds
                isValid = false;
                message = 'Tanggal tidak boleh lebih dari 1 jam ke depan';
            }
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
    const form = document.getElementById('salesForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        clearFieldError(input);
    });
}

// CRUD Operations
function openAddModal() {
    currentSalesId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Sales';
    document.getElementById('salesForm').reset();
    clearFormErrors();
    setDefaultDateTime();
    document.getElementById('salesModal').style.display = 'block';
    
    // Load draft if available
    setTimeout(() => {
        loadDraft();
        document.getElementById('tglSales').focus();
    }, 100);
}

function editSales(id) {
    currentSalesId = id;
    document.getElementById('modalTitle').textContent = 'Edit Sales';
    
    // Show modal first
    document.getElementById('salesModal').style.display = 'block';
    
    // Show loading state
    const form = document.getElementById('salesForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
    submitBtn.disabled = true;
    
    // Fetch sales data
    fetch('sales.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && !data.error) {
            document.getElementById('salesId').value = data.id_sales;
            
            // Format datetime for input
            const tglSales = new Date(data.tgl_sales);
            const year = tglSales.getFullYear();
            const month = String(tglSales.getMonth() + 1).padStart(2, '0');
            const day = String(tglSales.getDate()).padStart(2, '0');
            const hours = String(tglSales.getHours()).padStart(2, '0');
            const minutes = String(tglSales.getMinutes()).padStart(2, '0');
            const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            
            document.getElementById('tglSales').value = formattedDateTime;
            document.getElementById('customerSales').value = data.id_customer || '';
            document.getElementById('doNumber').value = data.do_number || '';
            document.getElementById('statusSales').value = data.status || 'DRAFT';
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('tglSales').focus();
            }, 100);
        } else {
            showNotification('Gagal mengambil data sales', 'error');
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

function deleteSales(id, doNumber) {
    currentSalesId = id;
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus sales "${doNumber}"?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete() {
    if (!currentSalesId) return;
    
    // Show loading state on delete button
    const deleteBtn = document.querySelector('#confirmModal .btn-danger');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentSalesId);
    
    fetch('sales.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('=== SALES CRUD RESPONSE ===');
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

function generateDONumber() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const doNumber = `DO-${year}${month}${day}-${hours}${minutes}${seconds}`;
    document.getElementById('doNumber').value = doNumber;
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
    formData.append('action', currentSalesId ? 'update' : 'create');
    
    // Add sales ID for update
    if (currentSalesId) {
        formData.append('id', currentSalesId);
    }
    
    // Convert datetime-local to proper format
    const tglSales = document.getElementById('tglSales').value;
    if (tglSales) {
        const date = new Date(tglSales);
        const formattedDate = date.getFullYear() + '-' + 
            String(date.getMonth() + 1).padStart(2, '0') + '-' + 
            String(date.getDate()).padStart(2, '0') + ' ' + 
            String(date.getHours()).padStart(2, '0') + ':' + 
            String(date.getMinutes()).padStart(2, '0') + ':00';
        formData.set('tgl_sales', formattedDate);
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    // Submit form
    fetch('sales.php', {
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
        console.log('=== SALES SAVE RESPONSE ===');
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

function searchSales() {
    const searchTerm = document.getElementById('searchInput').value;
    const url = new URL(window.location);
    url.searchParams.set('search', searchTerm);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function closeModal() {
    const modal = document.getElementById('salesModal');
    const form = document.getElementById('salesForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (form) {
        form.reset();
        clearFormErrors();
    }
    
    currentSalesId = null;
    
    // Clear draft when closing modal
    clearDraft();
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentSalesId = null;
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
    fetch('sales.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            // Update table body
            const tableBody = document.querySelector('#salesTableBody');
            if (tableBody && data.sales) {
                let html = '';
                if (data.sales.length > 0) {
                    data.sales.forEach((sale, index) => {
                        const statusClass = sale.status.toLowerCase();
                        const statusText = getStatusText(sale.status);
                        const doNumber = sale.do_number || `SALE-${sale.id_sales}`;
                        const customerName = sale.nama_customer || 'Customer Umum';
                        const tglSales = new Date(sale.tgl_sales);
                        const formattedDate = tglSales.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        html += `
                            <tr data-id="${sale.id_sales}">
                                <td>${data.offset + index + 1}</td>
                                <td>${escapeHtml(doNumber)}</td>
                                <td>${escapeHtml(customerName)}</td>
                                <td>${formattedDate}</td>
                                <td>
                                    <span class="status-badge status-${statusClass}">
                                        ${statusText}
                                    </span>
                                </td>
                                <td>Rp ${parseFloat(sale.total_amount).toLocaleString('id-ID')}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editSales(${sale.id_sales})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteSales(${sale.id_sales}, '${escapeHtml(doNumber)}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="7" class="text-center">Tidak ada data sales</td></tr>';
                }
                tableBody.innerHTML = html;
            }
            
            // Update results count
            const resultsCount = document.querySelector('.results-count');
            if (resultsCount) {
                resultsCount.textContent = `Total: ${data.totalCount} sales`;
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

function getStatusText(status) {
    const statusMap = {
        'DRAFT': 'Draft',
        'FINAL': 'Final',
        'CANCELED': 'Dibatalkan'
    };
    return statusMap[status] || status;
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
    const tableBody = document.querySelector('#salesTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center">
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
    const table = document.getElementById('salesTable');
    const rows = table.querySelectorAll('tr');
    
    let csv = 'No,DO Number,Customer,Tanggal,Status,Total\n';
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].querySelectorAll('td');
        if (cells.length > 0) {
            const row = [];
            for (let j = 0; j < cells.length - 1; j++) { // Exclude action column
                let cellText = cells[j].textContent.trim();
                // Clean up status data
                if (j === 4) {
                    cellText = cellText.replace(/\s+/g, ' ').trim();
                }
                // Clean up total data
                if (j === 5) {
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
    link.setAttribute('download', 'sales_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-save functionality
function autoSaveDraft() {
    const form = document.getElementById('salesForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Save to localStorage
    localStorage.setItem('sales_draft', JSON.stringify(data));
}

function loadDraft() {
    const form = document.getElementById('salesForm');
    if (!form) return;
    
    const draft = localStorage.getItem('sales_draft');
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
    localStorage.removeItem('sales_draft');
}

// Simple notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 16px 20px;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        border-left: 4px solid ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        min-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" 
               style="color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'}; font-size: 18px;"></i>
            <span style="color: #333; font-weight: 500;">${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Hide notification after 5 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
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

// Print sales function
function printSales(id) {
    // Open print window with sales details
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Sales - ${id}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .details { margin-bottom: 20px; }
                .table { width: 100%; border-collapse: collapse; }
                .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .table th { background-color: #f2f2f2; }
                .total { text-align: right; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE SALES</h1>
                <p>Sales ID: ${id}</p>
            </div>
            <div class="details">
                <p><strong>Tanggal:</strong> ${new Date().toLocaleDateString()}</p>
                <p><strong>Status:</strong> Draft</p>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" style="text-align: center;">Detail item akan ditampilkan di sini</td>
                    </tr>
                </tbody>
            </table>
            <div class="total">
                <p>Total: Rp 0</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
