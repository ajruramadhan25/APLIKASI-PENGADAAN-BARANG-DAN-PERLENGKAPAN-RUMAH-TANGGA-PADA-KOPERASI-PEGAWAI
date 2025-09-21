// Customer Management JavaScript
let currentCustomerId = null;

// Make functions globally available
window.openAddModal = openAddModal;
window.editCustomer = editCustomer;
window.deleteCustomer = deleteCustomer;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;

document.addEventListener('DOMContentLoaded', function() {
    initializeCustomerPage();
});

function initializeCustomerPage() {
    // Add event listeners
    addEventListeners();
    
    // Initialize form validation
    initializeFormValidation();
}

function addEventListeners() {
    // Customer form submission
    const customerForm = document.getElementById('customerForm');
    if (customerForm) {
        customerForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCustomers();
            }
        });
    }
    
    // Modal close on outside click
    const modal = document.getElementById('customerModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // Close button click handler
    const closeBtn = document.querySelector('#customerModal .close');
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

function initializeFormValidation() {
    // Real-time validation and auto-save
    const form = document.getElementById('customerForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea');
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
    let errorMessage = '';
    
    // Remove existing error
    clearFieldError(field);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Field ini harus diisi';
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Format email tidak valid';
    }
    
    // Phone validation
    if (field.name === 'telp' && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Format telepon tidak valid';
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,15}$/;
    return phoneRegex.test(phone);
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
    const form = document.getElementById('customerForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        clearFieldError(input);
    });
}

// CRUD Operations
function openAddModal() {
    currentCustomerId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Customer';
    document.getElementById('customerForm').reset();
    clearFormErrors();
    document.getElementById('customerModal').style.display = 'block';
    
    // Load draft if available
    setTimeout(() => {
        loadDraft();
        document.getElementById('namaCustomer').focus();
    }, 100);
}

function editCustomer(id) {
    currentCustomerId = id;
    document.getElementById('modalTitle').textContent = 'Edit Customer';
    
    // Fetch customer data
    fetch('customers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && !data.error) {
            document.getElementById('customerId').value = data.id_customer;
            document.getElementById('namaCustomer').value = data.nama_customer || '';
            document.getElementById('alamatCustomer').value = data.alamat || '';
            document.getElementById('telpCustomer').value = data.telp || '';
            document.getElementById('faxCustomer').value = data.fax || '';
            document.getElementById('emailCustomer').value = data.email || '';
            
            document.getElementById('customerModal').style.display = 'block';
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('namaCustomer').focus();
            }, 100);
        } else {
            showNotification('Gagal mengambil data customer', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
    });
}

function deleteCustomer(id, name) {
    currentCustomerId = id;
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus customer "${name}"?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete() {
    if (!currentCustomerId) return;
    
    // Show loading state on delete button
    const deleteBtn = document.querySelector('#confirmModal .btn-danger');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentCustomerId);
    
    fetch('customers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('=== CUSTOMER CRUD RESPONSE ===');
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
    const inputs = form.querySelectorAll('input[required], textarea[required]');
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
    formData.append('action', currentCustomerId ? 'update' : 'create');
    
    // Add customer ID for update
    if (currentCustomerId) {
        formData.append('id', currentCustomerId);
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    // Submit form
    fetch('customers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('=== CUSTOMER SAVE RESPONSE ===');
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

function searchCustomers() {
    const searchTerm = document.getElementById('searchInput').value;
    const url = new URL(window.location);
    url.searchParams.set('search', searchTerm);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function closeModal() {
    const modal = document.getElementById('customerModal');
    const form = document.getElementById('customerForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (form) {
        form.reset();
        clearFormErrors();
    }
    
    currentCustomerId = null;
    
    // Clear draft when closing modal
    clearDraft();
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentCustomerId = null;
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
    fetch('customers.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            // Update table body
            const tableBody = document.querySelector('#customersTableBody');
            if (tableBody && data.customers) {
                let html = '';
                if (data.customers.length > 0) {
                    data.customers.forEach((customer, index) => {
                        html += `
                            <tr data-id="${customer.id_customer}">
                                <td>${data.offset + index + 1}</td>
                                <td>${escapeHtml(customer.nama_customer)}</td>
                                <td>${escapeHtml(customer.alamat || '-')}</td>
                                <td>${escapeHtml(customer.telp || '-')}</td>
                                <td>${escapeHtml(customer.email || '-')}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id_customer})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${customer.id_customer}, '${escapeHtml(customer.nama_customer)}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">Tidak ada data customer</td></tr>';
                }
                tableBody.innerHTML = html;
            }
            
            // Update results count
            const resultsCount = document.querySelector('.results-count');
            if (resultsCount) {
                resultsCount.textContent = `Total: ${data.totalCount} customer`;
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
    const tableBody = document.querySelector('#customersTableBody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
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
    const table = document.getElementById('customersTable');
    const rows = table.querySelectorAll('tr');
    
    let csvContent = '';
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.textContent.trim();
            // Remove action column (last column)
            if (cell.closest('th') || cell.cellIndex < cells.length - 1) {
                return `"${text}"`;
            }
            return '';
        }).filter(data => data !== '');
        if (rowData.length > 0) {
            csvContent += rowData.join(',') + '\n';
        }
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `customers_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Data customer berhasil diekspor', 'success');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to close modals
    if (e.key === 'Escape') {
        closeModal();
        closeConfirmModal();
    }
    
    // Ctrl + N for new customer
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        openAddModal();
    }
    
    // Ctrl + F for search
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
});

// Auto-save draft (optional feature)
function autoSaveDraft() {
    const form = document.getElementById('customerForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const draft = {};
    
    for (let [key, value] of formData.entries()) {
        draft[key] = value;
    }
    
    localStorage.setItem('customerDraft', JSON.stringify(draft));
}

function loadDraft() {
    const draft = localStorage.getItem('customerDraft');
    if (draft && !currentCustomerId) {
        try {
            const data = JSON.parse(draft);
            Object.keys(data).forEach(key => {
                const field = document.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = data[key];
                }
            });
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
}

// Clear draft when form is submitted successfully
function clearDraft() {
    localStorage.removeItem('customer_draft');
}

// Auto-save functionality
function autoSaveDraft() {
    const form = document.getElementById('customerForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Save to localStorage
    localStorage.setItem('customer_draft', JSON.stringify(data));
}

function loadDraft() {
    const form = document.getElementById('customerForm');
    if (!form) return;
    
    const draft = localStorage.getItem('customer_draft');
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
