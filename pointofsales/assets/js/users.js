// Users Management JavaScript
let currentUserId = null;

// Make functions globally available
window.openAddModal = openAddModal;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.confirmDelete = confirmDelete;
window.closeModal = closeModal;
window.closeConfirmModal = closeConfirmModal;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addEventListeners();
    initializeFormValidation();
    setupPasswordValidation();
});

function addEventListeners() {
    // Form submission
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Change password form submission
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handleChangePasswordSubmit);
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
    }
    
    // Modal close on outside click
    const modals = ['userModal', 'changePasswordModal', 'userDetailModal', 'confirmModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal(modalId);
                }
            });
        }
    });
    
    // Close button click handlers
    const closeButtons = document.querySelectorAll('.close');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
}

function initializeFormValidation() {
    // Real-time validation and auto-save
    const form = document.getElementById('userForm');
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

function setupPasswordValidation() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (newPasswordInput && confirmPasswordInput) {
        [newPasswordInput, confirmPasswordInput].forEach(input => {
            input.addEventListener('input', function() {
                validatePasswordMatch();
            });
        });
    }
}

function validatePasswordMatch() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (confirmPassword && newPassword !== confirmPassword) {
        showFieldError(confirmPasswordInput, 'Password tidak sesuai');
        return false;
    } else {
        clearFieldError(confirmPasswordInput);
        return true;
    }
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
    } else if (field.type === 'password' && value && value.length < 6) {
        isValid = false;
        message = 'Password minimal 6 karakter';
    } else if (field.name === 'username' && value) {
        // Username validation
        if (!/^[a-zA-Z0-9_]+$/.test(value)) {
            isValid = false;
            message = 'Username hanya boleh huruf, angka, dan underscore';
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
    const form = document.getElementById('userForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        clearFieldError(input);
    });
}

// CRUD Operations
function openAddModal() {
    currentUserId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
    document.getElementById('userForm').reset();
    clearFormErrors();
    document.getElementById('passwordRequired').textContent = '*';
    document.getElementById('userModal').style.display = 'block';
    
    // Load draft if available
    setTimeout(() => {
        loadDraft();
        document.getElementById('namaUser').focus();
    }, 100);
}

function editUser(id) {
    currentUserId = id;
    document.getElementById('modalTitle').textContent = 'Edit Pengguna';
    
    // Show modal first
    document.getElementById('userModal').style.display = 'block';
    
    // Show loading state
    const form = document.getElementById('userForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
    submitBtn.disabled = true;
    
    // Fetch user data
    fetch('users.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && !data.error) {
            document.getElementById('userId').value = data.id_user;
            document.getElementById('namaUser').value = data.nama_user || '';
            document.getElementById('usernameUser').value = data.username || '';
            document.getElementById('levelUser').value = data.level || '';
            document.getElementById('passwordUser').value = '';
            document.getElementById('passwordRequired').textContent = '';
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Focus on first input
            setTimeout(() => {
                document.getElementById('namaUser').focus();
            }, 100);
        } else {
            showNotification('Gagal mengambil data pengguna', 'error');
            closeModal('userModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan sistem', 'error');
        closeModal('userModal');
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}



// Close detail modal
function closeDetailModal() {
    const modal = document.getElementById('userDetailModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function changePassword(id, username) {
    currentUserId = id;
    document.getElementById('changePasswordUserId').value = id;
    document.getElementById('changePasswordForm').reset();
    document.getElementById('changePasswordModal').style.display = 'block';
    
    setTimeout(() => {
        document.getElementById('oldPassword').focus();
    }, 100);
}

function deleteUser(id, name) {
    currentUserId = id;
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus pengguna "${name}"?`;
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete() {
    if (!currentUserId) return;
    
    // Show loading state on delete button
    const deleteBtn = document.querySelector('#confirmModal .btn-danger');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
    deleteBtn.disabled = true;
    deleteBtn.style.opacity = '0.7';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentUserId);
    
    fetch('users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('=== USERS CRUD RESPONSE ===');
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
    
    // Special validation for password on create
    if (!currentUserId) {
        const passwordInput = document.getElementById('passwordUser');
        if (!passwordInput.value) {
            showFieldError(passwordInput, 'Password harus diisi');
            isValid = false;
        }
    }
    
    if (!isValid) {
        showNotification('Mohon perbaiki error pada form', 'error');
        return;
    }
    
    // Prepare form data
    const formData = new FormData(form);
    formData.append('action', currentUserId ? 'update' : 'create');
    
    // Add user ID for update
    if (currentUserId) {
        formData.append('id', currentUserId);
    }
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        if (key !== 'password') { // Don't log password
            console.log(key + ': ' + value);
        }
    }
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    // Submit form
    fetch('users.php', {
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
        console.log('=== USERS SAVE RESPONSE ===');
        console.log('Data:', data);
        console.log('Success:', data.success);
        
        if (data.success) {
            console.log('✅ SAVE SUCCESS - Starting reload process...');
            showNotification(data.message, 'success');
            clearDraft(); // Clear draft after successful save
            closeModal('userModal');
            
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

function handleChangePasswordSubmit(e) {
    e.preventDefault();
    
    // Validate password match
    if (!validatePasswordMatch()) {
        showNotification('Password tidak sesuai', 'error');
        return;
    }
    
    // Validate all fields
    const form = e.target;
    const inputs = form.querySelectorAll('input[required]');
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
    formData.append('action', 'change_password');
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengubah...';
    submitBtn.disabled = true;
    submitBtn.style.opacity = '0.7';
    
    // Submit form
    fetch('users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            form.reset();
            closeChangePasswordModal();
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
        submitBtn.style.opacity = '1';
    });
}

function searchUsers() {
    const searchTerm = document.getElementById('searchInput').value;
    const url = new URL(window.location);
    url.searchParams.set('search', searchTerm);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId || 'userModal');
    const form = document.getElementById('userForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (form) {
        form.reset();
        clearFormErrors();
    }
    
    currentUserId = null;
    
    // Clear draft when closing modal
    clearDraft();
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
    document.getElementById('changePasswordForm').reset();
    currentUserId = null;
}

function closeUserDetailModal() {
    document.getElementById('userDetailModal').style.display = 'none';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    currentUserId = null;
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
    fetch('users.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            // Update table body
            const tableBody = document.querySelector('#usersTableBody');
            if (tableBody && data.petugas) {
                let html = '';
                if (data.petugas.length > 0) {
                    data.petugas.forEach((user, index) => {
                        const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 'Belum pernah';
                        
                        html += `
                            <tr data-id="${user.id_user}">
                                <td>${data.offset + index + 1}</td>
                                <td>${escapeHtml(user.nama_user)}</td>
                                <td>${escapeHtml(user.username)}</td>
                                <td>
                                    <span class="level-badge level-${user.level}">
                                        ${escapeHtml(user.role_name || 'Unknown')}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-circle"></i>
                                        Aktif
                                    </span>
                                </td>
                                <td>${lastLogin}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewUser(${user.id_user})" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="editUser(${user.id_user})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="changePassword(${user.id_user}, '${escapeHtml(user.username)}')" title="Ubah Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    ${user.id_user != currentUserId ? `
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id_user}, '${escapeHtml(user.nama_user)}')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    ` : ''}
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="7" class="text-center">Tidak ada data pengguna</td></tr>';
                }
                tableBody.innerHTML = html;
            }
            
            // Update results count
            const resultsCount = document.querySelector('.results-count');
            if (resultsCount) {
                resultsCount.textContent = `Total: ${data.totalCount} pengguna`;
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
    const tableBody = document.querySelector('#usersTableBody');
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
    const table = document.getElementById('usersTable');
    const rows = table.querySelectorAll('tr');
    
    let csv = 'No,Nama User,Username,Level,Status,Terakhir Login\n';
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].querySelectorAll('td');
        if (cells.length > 0) {
            const row = [];
            for (let j = 0; j < cells.length - 1; j++) { // Exclude action column
                let cellText = cells[j].textContent.trim();
                // Clean up level data
                if (j === 3) {
                    cellText = cellText.replace(/\s+/g, ' ').trim();
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
    link.setAttribute('download', 'users_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-save functionality
function autoSaveDraft() {
    const form = document.getElementById('userForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Save to localStorage
    localStorage.setItem('user_draft', JSON.stringify(data));
}

function loadDraft() {
    const form = document.getElementById('userForm');
    if (!form) return;
    
    const draft = localStorage.getItem('user_draft');
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
    localStorage.removeItem('user_draft');
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
