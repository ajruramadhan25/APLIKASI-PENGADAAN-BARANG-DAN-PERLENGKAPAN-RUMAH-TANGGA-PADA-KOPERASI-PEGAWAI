// Items Management JavaScript
console.log('Items JS file loaded');

let currentItemId = null;

// Define functions immediately
function openAddModal() {
    console.log('openAddModal called');
    currentItemId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Item';
    document.getElementById('itemForm').reset();
    clearFormErrors();
    document.getElementById('itemModal').style.display = 'block';
    
    setTimeout(() => {
        const namaItem = document.getElementById('namaItem');
        if (namaItem) namaItem.focus();
        calculateMargin();
    }, 100);
}

function editItem(id) {
    console.log('editItem called with id:', id);
    currentItemId = id;
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('itemModal').style.display = 'block';
    
    fetch(`items.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('namaItem').value = data.item.nama_item;
                document.getElementById('hargaBeli').value = data.item.harga_beli;
                document.getElementById('hargaJual').value = data.item.harga_jual;
                document.getElementById('stok').value = data.item.stok;
                document.getElementById('satuan').value = data.item.satuan;
                document.getElementById('kategori').value = data.item.kategori;
                document.getElementById('deskripsi').value = data.item.deskripsi || '';
                calculateMargin();
                setTimeout(() => {
                    const namaItem = document.getElementById('namaItem');
                    if (namaItem) namaItem.focus();
                }, 100);
            } else {
                showNotification('Gagal memuat data item', 'error');
                closeModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan saat memuat data', 'error');
            closeModal();
        });
}

function deleteItem(id, name) {
    console.log('deleteItem called with id:', id, 'name:', name);
    currentItemId = id;
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus item "${name}"?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete() {
    if (!currentItemId) return;
    
    const deleteBtn = document.querySelector('#confirmModal .btn-danger');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentItemId);
    
    fetch('items.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeConfirmModal();
            location.reload();
        } else {
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

function closeModal() {
    document.getElementById('itemModal').style.display = 'none';
    clearFormErrors();
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentItemId = null;
}

// Make functions globally available
window.openAddModal = openAddModal;
window.editItem = editItem;
window.deleteItem = deleteItem;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;

console.log('Functions defined and made global');

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Items JS DOMContentLoaded');
    console.log('openAddModal available:', typeof window.openAddModal);
    console.log('editItem available:', typeof window.editItem);
    console.log('deleteItem available:', typeof window.deleteItem);
    
    addEventListeners();
    initializeFormValidation();
    setupMarginCalculator();
});

function addEventListeners() {
    const itemForm = document.getElementById('itemForm');
    if (itemForm) {
        itemForm.addEventListener('submit', handleFormSubmit);
    }
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchItems();
            }
        });
    }
    
    const hargaBeliInput = document.getElementById('hargaBeli');
    const hargaJualInput = document.getElementById('hargaJual');
    
    if (hargaBeliInput) {
        hargaBeliInput.addEventListener('input', calculateMargin);
    }
    if (hargaJualInput) {
        hargaJualInput.addEventListener('input', calculateMargin);
    }
}

function handleFormSubmit(e) {
    e.preventDefault();
    
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
    
    const formData = new FormData(form);
    formData.append('action', currentItemId ? 'update' : 'create');
    
    if (currentItemId) {
        formData.append('id', currentItemId);
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    fetch('items.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            clearDraft();
            closeModal();
            location.reload();
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
        submitBtn.style.opacity = '1';
    });
}

function initializeFormValidation() {
    const inputs = document.querySelectorAll('#itemForm input, #itemForm select');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => clearFieldError(input));
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.getAttribute('name');
    let isValid = true;
    let errorMessage = '';
    
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Field ini wajib diisi';
    }
    
    if (value) {
        switch (fieldName) {
            case 'harga_beli':
            case 'harga_jual':
                if (isNaN(value) || parseFloat(value) < 0) {
                    isValid = false;
                    errorMessage = 'Harga harus berupa angka positif';
                }
                break;
            case 'stok':
                if (isNaN(value) || parseInt(value) < 0) {
                    isValid = false;
                    errorMessage = 'Stok harus berupa angka positif';
                }
                break;
        }
    }
    
    if (isValid) {
        clearFieldError(field);
    } else {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: #e74c3c;
        font-size: 12px;
        margin-top: 4px;
    `;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function clearFormErrors() {
    const errorFields = document.querySelectorAll('#itemForm .error');
    errorFields.forEach(field => {
        clearFieldError(field);
    });
}

function setupMarginCalculator() {
    const hargaBeliInput = document.getElementById('hargaBeli');
    const hargaJualInput = document.getElementById('hargaJual');
    
    if (hargaBeliInput && hargaJualInput) {
        hargaBeliInput.addEventListener('input', calculateMargin);
        hargaJualInput.addEventListener('input', calculateMargin);
    }
}

function calculateMargin() {
    const hargaBeli = parseFloat(document.getElementById('hargaBeli').value) || 0;
    const hargaJual = parseFloat(document.getElementById('hargaJual').value) || 0;
    
    if (hargaBeli > 0 && hargaJual > 0) {
        const margin = hargaJual - hargaBeli;
        const marginPercent = (margin / hargaBeli) * 100;
        
        const marginDisplay = document.getElementById('marginDisplay');
        if (marginDisplay) {
            marginDisplay.textContent = `Margin: Rp ${margin.toLocaleString()} (${marginPercent.toFixed(1)}%)`;
            marginDisplay.style.color = margin >= 0 ? '#27ae60' : '#e74c3c';
        }
    }
}

function searchItems() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    const tableBody = document.querySelector('#itemsTable tbody');
    
    if (!tableBody) return;
    
    const rows = tableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const itemName = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
        const category = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';
        
        if (itemName.includes(searchTerm.toLowerCase()) || category.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function clearDraft() {
    localStorage.removeItem('item_draft');
}

function showNotification(message, type = 'info') {
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
    
    setTimeout(() => {
        notification.classList.add('show');
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

console.log('Items JS file completed loading');