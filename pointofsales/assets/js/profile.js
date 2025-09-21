// Profile Management JavaScript

// Edit Profile Modal Functions
function editProfile() {
    const modal = document.getElementById('editProfileModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    const modal = document.getElementById('editProfileModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('editProfileForm').reset();
    // Get original values from data attributes or hidden inputs
    const namaUser = document.querySelector('[data-nama-user]')?.getAttribute('data-nama-user') || '';
    const username = document.querySelector('[data-username]')?.getAttribute('data-username') || '';
    document.getElementById('nama_user').value = namaUser;
    document.getElementById('username').value = username;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editProfileModal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Handle form submission
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Validate password fields
    if (data.new_password && data.new_password !== data.confirm_password) {
        showNotification('Password baru dan konfirmasi password tidak sama!', 'error');
        return;
    }
    
    if (data.new_password && data.new_password.length < 6) {
        showNotification('Password baru minimal 6 karakter!', 'error');
        return;
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    submitBtn.disabled = true;
    
    // Send request
    fetch('api/update_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeEditModal();
            
            // Update profile display
            document.querySelector('.profile-info h2').textContent = data.user.nama_user;
            document.querySelector('.profile-info .profile-username').textContent = '@' + data.user.username;
            document.querySelector('.detail-item span').textContent = data.user.nama_user;
            document.querySelectorAll('.detail-item span')[1].textContent = data.user.username;
            
            // Update sidebar
            document.querySelector('.user-name').textContent = data.user.nama_user;
            
            // Reload page after 2 seconds to ensure all data is updated
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat mengupdate profil!', 'error');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Notification function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide notification after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .notification {
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
        border-left: 4px solid #2196F3;
        min-width: 300px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left-color: #4CAF50;
    }
    
    .notification-error {
        border-left-color: #f44336;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .notification-content i {
        font-size: 18px;
    }
    
    .notification-success .notification-content i {
        color: #4CAF50;
    }
    
    .notification-error .notification-content i {
        color: #f44336;
    }
    
    .notification-content span {
        color: #333;
        font-weight: 500;
    }
`;
document.head.appendChild(style);
