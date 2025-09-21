// Login Form JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const loginStatus = document.getElementById('loginStatus');
    
    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        clearErrors();
        
        // Username validation
        if (!usernameInput.value.trim()) {
            showError('username-error', 'Username harus diisi');
            isValid = false;
        } else if (usernameInput.value.trim().length < 3) {
            showError('username-error', 'Username minimal 3 karakter');
            isValid = false;
        }
        
        // Password validation
        if (!passwordInput.value.trim()) {
            showError('password-error', 'Password harus diisi');
            isValid = false;
        } else if (passwordInput.value.length < 6) {
            showError('password-error', 'Password minimal 6 karakter');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Show error message
    function showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        errorElement.textContent = message;
        errorElement.classList.add('show');
        
        // Add error class to input
        const input = errorElement.previousElementSibling;
        if (input) {
            input.style.borderColor = '#e53e3e';
        }
    }
    
    // Clear all errors
    function clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => {
            element.classList.remove('show');
            element.textContent = '';
        });
        
        // Reset input borders
        const inputs = document.querySelectorAll('.form-group input');
        inputs.forEach(input => {
            input.style.borderColor = '';
        });
    }
    
    // Show status message
    function showStatus(message, type) {
        loginStatus.textContent = message;
        loginStatus.className = `login-status ${type}`;
        loginStatus.style.display = 'block';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            loginStatus.style.display = 'none';
        }, 5000);
    }
    
    // Set loading state
    function setLoadingState(isLoading) {
        if (isLoading) {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            loginBtn.querySelector('span').textContent = 'Memproses...';
        } else {
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
            loginBtn.querySelector('span').textContent = 'Masuk';
        }
    }
    
    // Real-time validation
    usernameInput.addEventListener('input', function() {
        if (this.value.trim() && this.value.trim().length >= 3) {
            clearError('username-error');
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if (this.value.length >= 6) {
            clearError('password-error');
        }
    });
    
    // Clear specific error
    function clearError(elementId) {
        const errorElement = document.getElementById(elementId);
        errorElement.classList.remove('show');
        errorElement.textContent = '';
        
        const input = errorElement.previousElementSibling;
        if (input) {
            input.style.borderColor = '';
        }
    }
    
    // Form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        setLoadingState(true);
        clearErrors();
        
        // Get form data
        const formData = new FormData(loginForm);
        
        // Make AJAX call to process_login.php
        fetch('process_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus(data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || 'dashboard.php';
                }, 1500);
            } else {
                showStatus(data.message, 'error');
                setLoadingState(false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showStatus('Terjadi kesalahan sistem. Silakan coba lagi.', 'error');
            setLoadingState(false);
        });
    });
    
    // Enter key handling
    usernameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            passwordInput.focus();
        }
    });
    
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Input focus effects
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Password visibility toggle (optional enhancement)
    const passwordToggle = document.createElement('button');
    passwordToggle.type = 'button';
    passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
    passwordToggle.className = 'password-toggle';
    passwordToggle.style.cssText = `
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #a0aec0;
        cursor: pointer;
        font-size: 16px;
        padding: 5px;
        transition: color 0.3s ease;
    `;
    
    // Add password toggle to password field
    const passwordGroup = passwordInput.parentElement;
    passwordGroup.style.position = 'relative';
    passwordGroup.appendChild(passwordToggle);
    
    passwordToggle.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'fas fa-eye-slash';
            this.style.color = '#667eea';
        } else {
            passwordInput.type = 'password';
            icon.className = 'fas fa-eye';
            this.style.color = '#a0aec0';
        }
    });
    
    // Add CSS for password toggle hover effect
    const style = document.createElement('style');
    style.textContent = `
        .password-toggle:hover {
            color: #667eea !important;
        }
        
        .form-group.focused .password-toggle {
            color: #667eea;
        }
    `;
    document.head.appendChild(style);
    
    // Auto-focus on username field
    setTimeout(() => {
        usernameInput.focus();
    }, 500);
    
    // Add smooth transitions for form elements
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const activeElement = document.activeElement;
            const inputs = Array.from(document.querySelectorAll('input, button'));
            const currentIndex = inputs.indexOf(activeElement);
            
            if (e.shiftKey && currentIndex > 0) {
                e.preventDefault();
                inputs[currentIndex - 1].focus();
            } else if (!e.shiftKey && currentIndex < inputs.length - 1) {
                e.preventDefault();
                inputs[currentIndex + 1].focus();
            }
        }
    });
    
    // Add loading animation for better UX
    function addLoadingAnimation() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            .form-group input:focus {
                animation: pulse 2s infinite;
            }
        `;
        document.head.appendChild(style);
    }
    
    addLoadingAnimation();
    
    // Error handling for network issues
    window.addEventListener('online', function() {
        showStatus('Koneksi internet tersedia', 'success');
    });
    
    window.addEventListener('offline', function() {
        showStatus('Tidak ada koneksi internet', 'error');
    });
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add smooth scroll behavior
document.documentElement.style.scrollBehavior = 'smooth';

// Add meta tag for mobile viewport
const viewport = document.querySelector('meta[name="viewport"]');
if (!viewport) {
    const meta = document.createElement('meta');
    meta.name = 'viewport';
    meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    document.head.appendChild(meta);
}
