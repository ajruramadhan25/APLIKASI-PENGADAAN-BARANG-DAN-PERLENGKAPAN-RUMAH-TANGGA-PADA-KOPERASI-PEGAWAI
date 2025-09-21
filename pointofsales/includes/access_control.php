<?php
// Access Control System for POS Penjualan
// Role-based access control for admin, petugas, manager

// Define access levels
define('ACCESS_KASIR', 1);
define('ACCESS_MANAGER', 2);
define('ACCESS_ADMIN', 3);

// Define role names
define('ROLE_KASIR', 'Petugas');
define('ROLE_MANAGER', 'Manager');
define('ROLE_ADMIN', 'Admin');

// Function to check if user has required access level
function hasAccess($requiredLevel) {
    if (!isset($_SESSION['level'])) {
        return false;
    }
    
    $userLevel = (int)$_SESSION['level'];
    return $userLevel >= $requiredLevel;
}

// Function to get user role name
function getUserRole() {
    if (!isset($_SESSION['level'])) {
        return 'Unknown';
    }
    
    $level = (int)$_SESSION['level'];
    switch ($level) {
        case ACCESS_KASIR:
            return ROLE_KASIR;
        case ACCESS_MANAGER:
            return ROLE_MANAGER;
        case ACCESS_ADMIN:
            return ROLE_ADMIN;
        default:
            return 'Unknown';
    }
}

// Function to get user role icon
function getUserRoleIcon() {
    if (!isset($_SESSION['level'])) {
        return 'fas fa-question';
    }
    
    $level = (int)$_SESSION['level'];
    switch ($level) {
        case ACCESS_KASIR:
            return 'fas fa-cash-register';
        case ACCESS_MANAGER:
            return 'fas fa-user-tie';
        case ACCESS_ADMIN:
            return 'fas fa-crown';
        default:
            return 'fas fa-question';
    }
}

// Function to get user role color
function getUserRoleColor() {
    if (!isset($_SESSION['level'])) {
        return '#9E9E9E';
    }
    
    $level = (int)$_SESSION['level'];
    switch ($level) {
        case ACCESS_KASIR:
            return '#4CAF50';
        case ACCESS_MANAGER:
            return '#2196F3';
        case ACCESS_ADMIN:
            return '#FF9800';
        default:
            return '#9E9E9E';
    }
}

// Function to check if user can access a specific page
function canAccessPage($page) {
    $accessRules = [
        'dashboard.php' => ACCESS_KASIR,        // Semua level bisa akses dashboard
        'sales.php' => ACCESS_KASIR,            // Petugas, Manager, dan Admin bisa akses sales
        'transactions.php' => ACCESS_KASIR,     // Petugas, Manager, dan Admin bisa akses transactions
        'customers.php' => ACCESS_KASIR,        // Petugas, Manager, dan Admin bisa akses customers
        'items.php' => ACCESS_KASIR,            // Petugas, Manager, dan Admin bisa akses items
        'reports.php' => ACCESS_MANAGER,        // Manager dan Admin bisa akses laporan
        'users.php' => ACCESS_ADMIN,            // Hanya Admin yang bisa akses users
          // Semua level bisa akses info level
    ];
    
    $requiredLevel = $accessRules[$page] ?? ACCESS_ADMIN;
    return hasAccess($requiredLevel);
}

// Function to redirect if no access
function requireAccess($requiredLevel, $redirectTo = 'dashboard.php') {
    if (!hasAccess($requiredLevel)) {
        header("Location: $redirectTo");
        exit;
    }
}

// Function to get accessible pages for current user
function getAccessiblePages() {
    $allPages = [
        'dashboard.php' => ['name' => 'Dashboard', 'icon' => 'fas fa-home', 'level' => ACCESS_KASIR],
        'sales.php' => ['name' => 'Sales', 'icon' => 'fas fa-shopping-cart', 'level' => ACCESS_KASIR],
        'items.php' => ['name' => 'Item', 'icon' => 'fas fa-boxes', 'level' => ACCESS_KASIR],
        'customers.php' => ['name' => 'Customer', 'icon' => 'fas fa-users', 'level' => ACCESS_KASIR],
        'transactions.php' => ['name' => 'Transaction', 'icon' => 'fas fa-receipt', 'level' => ACCESS_KASIR],
        'reports.php' => ['name' => 'Laporan', 'icon' => 'fas fa-chart-bar', 'level' => ACCESS_MANAGER],
        'users.php' => ['name' => 'Pengguna', 'icon' => 'fas fa-user-cog', 'level' => ACCESS_ADMIN],
        
    ];
    
    $accessiblePages = [];
    foreach ($allPages as $page => $info) {
        if (hasAccess($info['level'])) {
            $accessiblePages[$page] = $info;
        }
    }
    
    return $accessiblePages;
}

// Function to get user permissions
function getUserPermissions() {
    if (!isset($_SESSION['level'])) {
        return [];
    }
    
    $level = (int)$_SESSION['level'];
    $permissions = [];
    
    // Petugas permissions - bisa akses transaksi, sales, customer, item
    if ($level >= ACCESS_KASIR) {
        $permissions[] = 'view_dashboard';
        $permissions[] = 'create_sales';
        $permissions[] = 'view_sales';
        $permissions[] = 'create_transactions';
        $permissions[] = 'view_transactions';
        $permissions[] = 'manage_customers';
        $permissions[] = 'manage_items';
    }
    
    // Manager permissions - hanya bisa akses laporan
    if ($level >= ACCESS_MANAGER) {
        $permissions[] = 'view_reports';
        $permissions[] = 'export_data';
    }
    
    // Admin permissions - bisa akses semua
    if ($level >= ACCESS_ADMIN) {
        $permissions[] = 'manage_users';
        $permissions[] = 'system_settings';
        $permissions[] = 'full_access';
    }
    
    return $permissions;
}

// Function to check specific permission
function hasPermission($permission) {
    $permissions = getUserPermissions();
    return in_array($permission, $permissions);
}

// Function to get user level name
function getLevelName($level) {
    switch ((int)$level) {
        case ACCESS_KASIR:
            return ROLE_KASIR;
        case ACCESS_MANAGER:
            return ROLE_MANAGER;
        case ACCESS_ADMIN:
            return ROLE_ADMIN;
        default:
            return 'Unknown';
    }
}

// Function to get level options for forms
function getLevelOptions() {
    return [
        ['value' => ACCESS_KASIR, 'name' => ROLE_KASIR, 'description' => 'Bisa akses Sales, Transactions, Customers, Items'],
        ['value' => ACCESS_MANAGER, 'name' => ROLE_MANAGER, 'description' => 'Hanya bisa akses Laporan'],
        ['value' => ACCESS_ADMIN, 'name' => ROLE_ADMIN, 'description' => 'Akses penuh sistem termasuk Users'],
    ];
}

// Function to log access attempts
function logAccessAttempt($page, $success = true) {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $logData = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'unknown',
        'page' => $page,
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log to file or database as needed
    error_log("Access attempt: " . json_encode($logData));
}

// Function to get user dashboard data based on level
function getUserDashboardData() {
    if (!isset($_SESSION['level'])) {
        return [];
    }
    
    $level = (int)$_SESSION['level'];
    $data = [];
    
    // Common data for all levels
    $data['user_role'] = getUserRole();
    $data['user_icon'] = getUserRoleIcon();
    $data['user_color'] = getUserRoleColor();
    $data['accessible_pages'] = getAccessiblePages();
    $data['permissions'] = getUserPermissions();
    
    // Level-specific data
    switch ($level) {
        case ACCESS_KASIR:
            $data['primary_action'] = 'Buat Transaksi';
            $data['primary_page'] = 'sales.php';
            $data['description'] = 'Input transaksi penjualan, kelola sales, customer, dan item';
            break;
            
        case ACCESS_MANAGER:
            $data['primary_action'] = 'Lihat Laporan';
            $data['primary_page'] = 'reports.php';
            $data['description'] = 'Hanya bisa mengakses laporan sistem';
            break;
            
        case ACCESS_ADMIN:
            $data['primary_action'] = 'Kelola Sistem';
            $data['primary_page'] = 'users.php';
            $data['description'] = 'Akses penuh sistem dan kelola pengguna';
            break;
    }
    
    return $data;
}
?>
