<?php
// Sales Management Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Include required files
require_once 'config.php';
require_once 'classes/Sales.php';
require_once 'includes/access_control.php';

// Check if user has access to sales page (Petugas level and above)
requireAccess(ACCESS_KASIR);

// Initialize database connection
try {
    $database = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Sales class
$salesObj = new Sales($database);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create':
            // Validate data
            $data = [
                'tgl_sales' => $_POST['tgl_sales'] ?? date('Y-m-d H:i:s'),
                'id_customer' => !empty($_POST['id_customer']) ? (int)$_POST['id_customer'] : null,
                'do_number' => trim($_POST['do_number'] ?? ''),
                'status' => $_POST['status'] ?? 'DRAFT'
            ];
            
            $errors = $salesObj->validate($data);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $salesObj->create($data);
            echo json_encode($result);
            exit;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'tgl_sales' => $_POST['tgl_sales'] ?? date('Y-m-d H:i:s'),
                'id_customer' => !empty($_POST['id_customer']) ? (int)$_POST['id_customer'] : null,
                'do_number' => trim($_POST['do_number'] ?? ''),
                'status' => $_POST['status'] ?? 'DRAFT'
            ];
            
            $errors = $salesObj->validate($data, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $salesObj->update($id, $data);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $result = $salesObj->delete($id);
            echo json_encode($result);
            exit;
            
        case 'get':
            $id = (int)$_POST['id'];
            $sales = $salesObj->readById($id);
            echo json_encode($sales);
            exit;
            
        case 'get_table_data':
            // Return table data as JSON for AJAX refresh
            $search = $_POST['search'] ?? '';
            $page = max(1, (int)($_POST['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sales = $salesObj->readAll($search, $limit, $offset);
            $totalCount = $salesObj->getTotalCount($search);
            $totalPages = ceil($totalCount / $limit);
            
            echo json_encode([
                'sales' => $sales,
                'totalCount' => $totalCount,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'offset' => $offset
            ]);
            exit;
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Get sales data
$sales = $salesObj->readAll($search, $limit, $offset);
$totalCount = $salesObj->getTotalCount($search);
$totalPages = ceil($totalCount / $limit);

// Get customers and status options
$customers = $salesObj->getCustomers();
$statusOptions = $salesObj->getStatusOptions();

// Get user information for header
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'petugas') {
    $stmt = $database->prepare("
        SELECT p.*, l.level as role_name 
        FROM petugas p 
        LEFT JOIN level l ON p.level = l.id_level 
        WHERE p.id_user = ?
    ");
} else {
    $stmt = $database->prepare("
        SELECT m.*, l.level as role_name 
        FROM manager m 
        LEFT JOIN level l ON m.level = l.id_level 
        WHERE m.id_user = ?
    ");
}
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Sales - POS Penjualan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/customer.css">
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
                <?php 
                // Get accessible pages based on user level
                $accessiblePages = getAccessiblePages();
                foreach ($accessiblePages as $page => $info): 
                ?>
                <li <?php echo ($page === 'sales.php') ? 'class="active"' : ''; ?>>
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
            <!-- Header -->
            <header class="header">
                <h1>Manajemen Sales</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Sales
                    </button>
                </div>
            </header>
            
            <!-- Search and Filter -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari sales..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="searchSales()">Cari</button>
                </div>
                <div class="filter-options">
                    <span class="results-count">Total: <?php echo $totalCount; ?> sales</span>
                </div>
            </div>
            
            <!-- Sales Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table" id="salesTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Sales No</th>
                                <th>DO No</th>
                                <th>Tgl Sales</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <?php if (is_array($sales) && !empty($sales)): ?>
                                <?php foreach ($sales as $index => $sale): ?>
                                <tr data-id="<?php echo $sale['id_sales']; ?>">
                                    <td><?php echo (int)$offset + (int)$index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($sale['do_number'] ?? 'INV' . str_pad($sale['id_sales'], 4, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($sale['do_number'] ?? '01/DO/' . date('d/m/Y', strtotime($sale['tgl_sales']))); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($sale['tgl_sales'])); ?></td>
                                    <td><?php echo htmlspecialchars($sale['nama_customer'] ?? 'Customer Umum'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $sale['status'] == 'FINAL' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $sale['status'] == 'FINAL' ? 'Lunas' : 'Belum Lunas'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($sale['status'] != 'FINAL'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="editSales(<?php echo $sale['id_sales']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSales(<?php echo $sale['id_sales']; ?>, '<?php echo htmlspecialchars($sale['do_number'] ?? 'SALE-' . $sale['id_sales']); ?>')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa diedit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa dihapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" onclick="printSales(<?php echo $sale['id_sales']; ?>)" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data sales</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Sales Modal -->
    <div id="salesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Sales</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="salesForm">
                <div class="modal-body">
                    <input type="hidden" id="salesId" name="id">
                    
                    <div class="form-group">
                        <label for="tglSales">Tanggal Sales *</label>
                        <input type="datetime-local" id="tglSales" name="tgl_sales" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customerSales">Customer</label>
                        <select id="customerSales" name="id_customer">
                            <option value="">Pilih Customer (Opsional)</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id_customer']; ?>">
                                    <?php echo htmlspecialchars($customer['nama_customer']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="doNumber">DO Number</label>
                        <div class="input-group">
                            <input type="text" id="doNumber" name="do_number" placeholder="Otomatis generate jika kosong">
                            <button type="button" class="btn btn-secondary" onclick="generateDONumber()">
                                <i class="fas fa-sync"></i>
                                Generate
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="statusSales">Status *</label>
                        <select id="statusSales" name="status" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h2>Konfirmasi Hapus</h2>
                <span class="close" onclick="closeConfirmModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus sales ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i>
                    Hapus
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/sales.js"></script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
