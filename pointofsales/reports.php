<?php
session_start();
require_once 'config.php';
require_once 'classes/Customer.php';
require_once 'classes/Item.php';
require_once 'classes/Sales.php';
require_once 'classes/Transaction.php';
require_once 'includes/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Check if user has access to reports page (Manager level and above)
requireAccess(ACCESS_MANAGER);

// Get user info
$user = [
    'id' => $_SESSION['user_id'],
    'nama_user' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
    'level' => $_SESSION['level'],
    'role_name' => $_SESSION['level'] == 1 ? 'Petugas' : ($_SESSION['level'] == 2 ? 'Manager' : 'Admin')
];

// Check if user has access to reports (level 2+)
if ($user['level'] < 2) {
    header('Location: dashboard.php');
    exit;
}

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize classes
$customer = new Customer($pdo);
$item = new Item($pdo);
$sales = new Sales($pdo);
$transaction = new Transaction($pdo);

// Get report data
$report_type = $_GET['type'] ?? 'sales';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$report_data = [];
$total_data = 0;

switch ($report_type) {
    case 'sales':
        $report_data = $sales->getSalesReport($start_date, $end_date);
        $total_data = count($report_data);
        break;
    case 'customer':
        $report_data = $customer->getCustomerReport();
        $total_data = count($report_data);
        break;
    case 'stock':
        $report_data = $item->getStockReport();
        $total_data = count($report_data);
        break;
    case 'transaction':
        $report_data = $transaction->getTransactionReport($start_date, $end_date);
        $total_data = count($report_data);
        break;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Aplikasi Pengadaan Barang Koperasi Pegawai</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .report-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .btn-filter {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            background: #2563eb;
        }
        
        .btn-export {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 0.5rem;
        }
        
        .btn-export:hover {
            background: #059669;
        }
        
        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .summary-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }
        
        .summary-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .report-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        td {
            color: #1f2937;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                min-width: 100%;
            }
        }
    </style>
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
                <?php 
                // Get accessible pages based on user level
                $accessiblePages = getAccessiblePages();
                foreach ($accessiblePages as $page => $info): 
                ?>
                <li <?php echo ($page === 'reports.php') ? 'class="active"' : ''; ?>>
                    <a href="<?php echo $page; ?>">
                        <i class="<?php echo $info['icon']; ?>"></i>
                        <span><?php echo $info['name']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="<?php echo getUserRoleIcon(); ?>"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($user['nama_user']); ?></span>
                        <span class="user-role"><?php echo ucfirst($user['role_name']); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Laporan</h1>
            </div>
            
            <!-- Report Filters -->
            <div class="report-filters">
                <form method="GET" class="filter-row">
                    <div class="filter-group">
                        <label for="type">Jenis Laporan</label>
                        <select name="type" id="type">
                            <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Laporan Penjualan</option>
                            <option value="customer" <?php echo $report_type == 'customer' ? 'selected' : ''; ?>>Laporan Customer</option>
                            <option value="stock" <?php echo $report_type == 'stock' ? 'selected' : ''; ?>>Laporan Stok</option>
                            <option value="transaction" <?php echo $report_type == 'transaction' ? 'selected' : ''; ?>>Laporan Transaksi</option>
                        </select>
                    </div>
                    
                    <?php if (in_array($report_type, ['sales', 'transaction'])): ?>
                    <div class="filter-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">Tanggal Akhir</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <button type="button" class="btn-export" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Report Summary -->
            <div class="report-summary">
                <div class="summary-card">
                    <h3>Total Data</h3>
                    <p class="value"><?php echo number_format($total_data); ?></p>
                </div>
                
                <?php if ($report_type == 'sales'): ?>
                <div class="summary-card">
                    <h3>Total Penjualan</h3>
                    <p class="value">Rp <?php echo number_format(array_sum(array_column($report_data, 'total_sales'))); ?></p>
                </div>
                <?php elseif ($report_type == 'customer'): ?>
                <div class="summary-card">
                    <h3>Total Pembelian</h3>
                    <p class="value">Rp <?php echo number_format(array_sum(array_column($report_data, 'total_purchase'))); ?></p>
                </div>
                <?php elseif ($report_type == 'stock'): ?>
                <div class="summary-card">
                    <h3>Total Terjual</h3>
                    <p class="value"><?php echo number_format(array_sum(array_column($report_data, 'total_sold'))); ?></p>
                </div>
                <?php elseif ($report_type == 'transaction'): ?>
                <div class="summary-card">
                    <h3>Total Transaksi</h3>
                    <p class="value">Rp <?php echo number_format(array_sum(array_column($report_data, 'total'))); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Report Table -->
            <div class="report-table">
                <div class="table-header">
                    <h2 class="table-title">
                        <?php 
                        $titles = [
                            'sales' => 'Laporan Penjualan',
                            'customer' => 'Laporan Customer',
                            'stock' => 'Laporan Stok',
                            'transaction' => 'Laporan Transaksi'
                        ];
                        echo $titles[$report_type];
                        ?>
                    </h2>
                </div>
                
                <div class="table-container">
                    <?php if (empty($report_data)): ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Tidak ada data</h3>
                        <p>Belum ada data untuk laporan yang dipilih.</p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <?php if ($report_type == 'sales'): ?>
                            <tr>
                                <th>No</th>
                                <th>Sales No</th>
                                <th>DO No</th>
                                <th>Tgl Sales</th>
                                <th>Customer</th>
                                <th>Total Penjualan</th>
                                <th>Status</th>
                            </tr>
                            <?php elseif ($report_type == 'customer'): ?>
                            <tr>
                                <th>No</th>
                                <th>Nama Customer</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Total Sales</th>
                            </tr>
                            <?php elseif ($report_type == 'stock'): ?>
                            <tr>
                                <th>No</th>
                                <th>Nama Item</th>
                                <th>UOM</th>
                                <th>Harga Jual</th>
                                <th>Total Terjual</th>
                                <th>Stok Tersisa</th>
                            </tr>
                            <?php elseif ($report_type == 'transaction'): ?>
                            <tr>
                                <th>No</th>
                                <th>Sales No</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <?php if ($report_type == 'sales'): ?>
                                <td><?php echo htmlspecialchars($row['do_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['do_number']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['tgl_sales'])); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_customer']); ?></td>
                                <td>Rp <?php echo number_format($row['total_sales']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['status'] == 'FINAL' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $row['status'] == 'FINAL' ? 'Lunas' : 'Belum Lunas'; ?>
                                    </span>
                                </td>
                                <?php elseif ($report_type == 'customer'): ?>
                                <td><?php echo htmlspecialchars($row['nama_customer']); ?></td>
                                <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                <td><?php echo htmlspecialchars($row['telp']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo number_format($row['total_sales']); ?></td>
                                <?php elseif ($report_type == 'stock'): ?>
                                <td><?php echo htmlspecialchars($row['nama_item']); ?></td>
                                <td><?php echo htmlspecialchars($row['uom']); ?></td>
                                <td>Rp <?php echo number_format($row['harga_jual']); ?></td>
                                <td><?php echo number_format($row['total_sold']); ?></td>
                                <td><?php echo number_format($row['remaining_stock']); ?></td>
                                <?php elseif ($report_type == 'transaction'): ?>
                                <td><?php echo htmlspecialchars($row['do_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_item']); ?></td>
                                <td><?php echo number_format($row['quantity']); ?></td>
                                <td>Rp <?php echo number_format($row['price']); ?></td>
                                <td>Rp <?php echo number_format($row['total']); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exportReport() {
            const type = document.getElementById('type').value;
            const startDate = document.getElementById('start_date')?.value || '';
            const endDate = document.getElementById('end_date')?.value || '';
            
            let url = `export_report.php?type=${type}`;
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            
            window.open(url, '_blank');
        }
    </script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
