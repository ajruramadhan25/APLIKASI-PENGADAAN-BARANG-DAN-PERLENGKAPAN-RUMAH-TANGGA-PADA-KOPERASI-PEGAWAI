// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Add event listeners
    addEventListeners();
    
    // Load dashboard data
    loadDashboardData();
});

function initializeDashboard() {
    // Add smooth transitions
    addSmoothTransitions();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Set up real-time updates
    setupRealTimeUpdates();
}

function addEventListeners() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    // Logout confirmation
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', confirmLogout);
    }
    
    // Quick action cards
    const quickActionCards = document.querySelectorAll('.quick-action-card');
    quickActionCards.forEach(card => {
        card.addEventListener('click', handleQuickAction);
    });
    
    // Table row clicks
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('click', handleTableRowClick);
    });
    
    // Refresh button
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshDashboard);
    }
}

function addSmoothTransitions() {
    // Add CSS for smooth transitions
    const style = document.createElement('style');
    style.textContent = `
        .stat-card,
        .content-section,
        .quick-action-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
        }
        
        .data-table tr {
            transition: background-color 0.2s ease;
        }
        
        .sidebar-menu a {
            transition: all 0.3s ease;
        }
    `;
    document.head.appendChild(style);
}

function initializeTooltips() {
    // Add tooltips to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.title = 'Klik untuk melihat detail';
        card.style.cursor = 'pointer';
        card.addEventListener('click', showStatDetails);
    });
}

function setupRealTimeUpdates() {
    // Update dashboard data every 30 seconds
    setInterval(loadDashboardData, 30000);
    
    // Add notification system
    setupNotifications();
}

function loadDashboardData() {
    // Show loading state
    showLoadingState();
    
    // Simulate API call (replace with actual AJAX call)
    setTimeout(() => {
        // Update statistics
        updateStatistics();
        
        // Update recent requests
        updateRecentRequests();
        
        // Hide loading state
        hideLoadingState();
    }, 1000);
}

function updateStatistics() {
    // Animate number counting
    const statNumbers = document.querySelectorAll('.stat-content h3');
    statNumbers.forEach(number => {
        animateNumber(number);
    });
}

function animateNumber(element) {
    const target = parseInt(element.textContent.replace(/,/g, ''));
    const duration = 1000;
    const start = 0;
    const increment = target / (duration / 16);
    
    let current = start;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}

function updateRecentRequests() {
    // Add fade-in animation to new rows
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function showLoadingState() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.classList.add('loading');
    });
}

function hideLoadingState() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.classList.remove('loading');
    });
}

function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

function confirmLogout(e) {
    e.preventDefault();
    
    if (confirm('Apakah Anda yakin ingin keluar?')) {
        // Show loading
        showNotification('Memproses logout...', 'info');
        
        // Redirect to logout
        setTimeout(() => {
            window.location.href = 'logout.php';
        }, 1000);
    }
}

function handleQuickAction(e) {
    e.preventDefault();
    const href = e.currentTarget.getAttribute('href');
    
    // Add loading animation
    e.currentTarget.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        window.location.href = href;
    }, 200);
}

function handleTableRowClick(e) {
    const row = e.currentTarget;
    const requestNumber = row.cells[0].textContent;
    
    // Add row selection
    document.querySelectorAll('.data-table tbody tr').forEach(r => {
        r.classList.remove('selected');
    });
    row.classList.add('selected');
    
    // Navigate to request detail
    setTimeout(() => {
        window.location.href = `procurement.php?view=${requestNumber}`;
    }, 300);
}

function refreshDashboard() {
    showNotification('Memperbarui data...', 'info');
    loadDashboardData();
}

function showStatDetails(e) {
    const card = e.currentTarget;
    const title = card.querySelector('.stat-content p').textContent;
    
    // Create modal or tooltip with details
    showNotification(`Detail ${title} akan ditampilkan`, 'info');
}

function setupNotifications() {
    // Create notification container
    const notificationContainer = document.createElement('div');
    notificationContainer.className = 'notification-container';
    notificationContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 300px;
    `;
    document.body.appendChild(notificationContainer);
}

function showNotification(message, type = 'info') {
    const notificationContainer = document.querySelector('.notification-container');
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: white;
        border-left: 4px solid #667eea;
        padding: 15px 20px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        font-size: 14px;
        color: #2d3748;
    `;
    
    // Set colors based on type
    const colors = {
        info: '#667eea',
        success: '#48bb78',
        warning: '#ed8936',
        error: '#e53e3e'
    };
    
    notification.style.borderLeftColor = colors[type] || colors.info;
    notification.textContent = message;
    
    notificationContainer.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + R to refresh dashboard
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshDashboard();
    }
    
    // Escape to close mobile menu
    if (e.key === 'Escape') {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.remove('open');
    }
    
    // Number keys for quick navigation
    if (e.altKey) {
        switch(e.key) {
            case '1':
                window.location.href = 'dashboard.php';
                break;
            case '2':
                // Check if user has access to procurement
                if (window.userLevel >= 3) {
                    window.location.href = 'procurement.php';
                }
                break;
            case '3':
                // Check if user has access to items
                if (window.userLevel >= 3) {
                    window.location.href = 'items.php';
                }
                break;
            case '4':
                // Check if user has access to suppliers
                if (window.userLevel >= 3) {
                    window.location.href = 'suppliers.php';
                }
                break;
        }
    }
});

// Auto-save user preferences
function saveUserPreferences() {
    const preferences = {
        sidebarCollapsed: document.querySelector('.sidebar').classList.contains('collapsed'),
        theme: localStorage.getItem('theme') || 'light'
    };
    
    localStorage.setItem('dashboardPreferences', JSON.stringify(preferences));
}

function loadUserPreferences() {
    const preferences = JSON.parse(localStorage.getItem('dashboardPreferences') || '{}');
    
    if (preferences.sidebarCollapsed) {
        document.querySelector('.sidebar').classList.add('collapsed');
    }
    
    if (preferences.theme === 'dark') {
        document.body.classList.add('dark-theme');
    }
}

// Initialize preferences on load
loadUserPreferences();

// Save preferences on changes
window.addEventListener('beforeunload', saveUserPreferences);

// Add search functionality
function addSearchFunctionality() {
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Cari...';
    searchInput.className = 'search-input';
    searchInput.style.cssText = `
        padding: 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        width: 250px;
        margin-left: auto;
    `;
    
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
        headerActions.insertBefore(searchInput, headerActions.firstChild);
        
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            filterTableRows(query);
        });
    }
}

function filterTableRows(query) {
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize search when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addSearchFunctionality);
} else {
    addSearchFunctionality();
}

// Add export functionality
function addExportButtons() {
    const exportBtn = document.createElement('button');
    exportBtn.className = 'btn btn-secondary';
    exportBtn.innerHTML = '<i class="fas fa-download"></i> Export';
    exportBtn.onclick = exportData;
    
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
        headerActions.appendChild(exportBtn);
    }
}

function exportData() {
    // Create CSV content
    const table = document.querySelector('.data-table');
    const rows = table.querySelectorAll('tr');
    
    let csvContent = '';
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim());
        csvContent += rowData.join(',') + '\n';
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dashboard_data.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showNotification('Data berhasil diekspor', 'success');
}

// Initialize export button
addExportButtons();

// Add print functionality
function addPrintButton() {
    const printBtn = document.createElement('button');
    printBtn.className = 'btn btn-secondary';
    printBtn.innerHTML = '<i class="fas fa-print"></i> Print';
    printBtn.onclick = printDashboard;
    
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
        headerActions.appendChild(printBtn);
    }
}

function printDashboard() {
    window.print();
}

// Initialize print button
addPrintButton();
