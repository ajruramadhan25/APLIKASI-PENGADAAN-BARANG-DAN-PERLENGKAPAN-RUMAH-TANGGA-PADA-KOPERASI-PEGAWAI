<?php
// Dashboard for Aplikasi Pengadaan Barang Koperasi Pegawai
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Include access control
require_once __DIR__ . '/includes/access_control.php';


// Database configuration
$host = 'localhost';
$dbname = 'pos_penjualan';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'petugas') {
    $stmt = $pdo->prepare("
        SELECT p.*, l.level as role_name 
        FROM petugas p 
        LEFT JOIN level l ON p.level = l.id_level 
        WHERE p.id_user = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT m.*, l.level as role_name 
        FROM manager m 
        LEFT JOIN level l ON m.level = l.id_level 
        WHERE m.id_user = ?
    ");
}
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get dashboard statistics
$stats = [];

// Total sales
$stmt = $pdo->query("SELECT COUNT(*) as total FROM sales");
$stats['total_sales'] = $stmt->fetch()['total'];

// Pending sales (DRAFT status)
$stmt = $pdo->query("SELECT COUNT(*) as pending FROM sales WHERE status = 'DRAFT'");
$stats['pending_sales'] = $stmt->fetch()['pending'];

// Total items
$stmt = $pdo->query("SELECT COUNT(*) as total FROM item");
$stats['total_items'] = $stmt->fetch()['total'];

// Low stock items (assuming we add stock_quantity column later)
// $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM item WHERE stock_quantity <= minimum_stock");
// $stats['low_stock_items'] = $stmt->fetch()['low_stock'];
$stats['low_stock_items'] = 0; // Placeholder for now

// Total customers
$stmt = $pdo->query("SELECT COUNT(*) as total FROM customer");
$stats['total_customers'] = $stmt->fetch()['total'];

// Recent sales
$stmt = $pdo->prepare("
    SELECT s.*, c.nama_customer as customer_name, 
           COALESCE(SUM(t.amount), 0) as total_amount
    FROM sales s 
    LEFT JOIN customer c ON s.id_customer = c.id_customer 
    LEFT JOIN transaction t ON s.id_sales = t.id_sales
    GROUP BY s.id_sales, s.tgl_sales, s.id_customer, s.do_number, s.status, c.nama_customer
    ORDER BY s.tgl_sales DESC 
    LIMIT 5
");
$stmt->execute();
$recent_sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Pengadaan Barang Koperasi Pegawai</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                </div>
                <h2>POS Penjualan</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php 
                // Get accessible pages based on user level
                $accessiblePages = getAccessiblePages();
                foreach ($accessiblePages as $page => $info): 
                    if ($page !== 'dashboard.php'): // Skip dashboard as it's already shown
                ?>
                <li>
                    <a href="<?php echo $page; ?>">
                        <i class="<?php echo $info['icon']; ?>"></i>
                        <span><?php echo $info['name']; ?></span>
                    </a>
                </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info" onclick="location.href='profile.php'" style="cursor: pointer;">
                    <div class="user-avatar">
                        <i class="<?php echo getUserRoleIcon(); ?>"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($user['nama_user']); ?></span>
                        <span class="user-role"><?php echo getUserRole(); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1>Dashboard</h1>
                <div class="header-actions">
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can create sales ?>
                    <button class="btn btn-primary" onclick="location.href='sales.php?action=new'">
                        <i class="fas fa-plus"></i>
                        Penjualan Baru
                    </button>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_sales']); ?></h3>
                        <p>Total Penjualan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['pending_sales']); ?></h3>
                        <p>Draft Penjualan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_items']); ?></h3>
                        <p>Total Barang</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['low_stock_items']); ?></h3>
                        <p>Stok Rendah</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_customers']); ?></h3>
                        <p>Total Customer</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Sales -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Penjualan Terbaru</h2>
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can view all sales ?>
                    <a href="sales.php" class="view-all">Lihat Semua</a>
                    <?php endif; ?>
                </div>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Penjualan</th>
                                <th>Customer</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_sales)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada penjualan</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recent_sales as $sale): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['do_number'] ?? 'SALE-' . $sale['id_sales']); ?></td>
                                    <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Customer Umum'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($sale['tgl_sales'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($sale['status']); ?>">
                                            <?php echo ucfirst($sale['status']); ?>
                                        </span>
                                    </td>
                                    <td>Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Aksi Cepat</h2>
                </div>
                
                <div class="quick-actions">
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can create sales ?>
                    <a href="sales.php?action=new" class="quick-action-card">
                        <i class="fas fa-plus-circle"></i>
                        <span>Buat Penjualan</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can manage items ?>
                    <a href="items.php" class="quick-action-card">
                        <i class="fas fa-boxes"></i>
                        <span>Kelola Barang</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can manage customers ?>
                    <a href="customers.php" class="quick-action-card">
                        <i class="fas fa-users"></i>
                        <span>Kelola Customer</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($user['level'] >= 1): // Petugas, Manager, dan Admin can view transactions ?>
                    <a href="transactions.php" class="quick-action-card">
                        <i class="fas fa-receipt"></i>
                        <span>Lihat Transaksi</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($user['level'] >= 2): // Manager and Admin can view reports ?>
                    <a href="reports.php" class="quick-action-card">
                        <i class="fas fa-chart-line"></i>
                        <span>Lihat Laporan</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
