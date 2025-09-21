<?php
// Customer Management Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Include required files
require_once 'config.php';
require_once 'classes/Customer.php';
require_once 'includes/access_control.php';

// Check if user has access to customers page (Petugas level and above)
requireAccess(ACCESS_KASIR);

// Initialize database connection
try {
    $database = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Customer class
$customerObj = new Customer($database);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create':
            // Validate data
            $data = [
                'nama_customer' => trim($_POST['nama_customer'] ?? ''),
                'alamat' => trim($_POST['alamat'] ?? ''),
                'telp' => trim($_POST['telp'] ?? ''),
                'fax' => trim($_POST['fax'] ?? ''),
                'email' => trim($_POST['email'] ?? '')
            ];
            
            $errors = $customerObj->validate($data);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $customerObj->create($data);
            echo json_encode($result);
            exit;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'nama_customer' => trim($_POST['nama_customer'] ?? ''),
                'alamat' => trim($_POST['alamat'] ?? ''),
                'telp' => trim($_POST['telp'] ?? ''),
                'fax' => trim($_POST['fax'] ?? ''),
                'email' => trim($_POST['email'] ?? '')
            ];
            
            $errors = $customerObj->validate($data, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $customerObj->update($id, $data);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $result = $customerObj->delete($id);
            echo json_encode($result);
            exit;
            
        case 'get':
            $id = (int)$_POST['id'];
            $customer = $customerObj->readById($id);
            echo json_encode($customer);
            exit;
            
        case 'get_table_data':
            // Return table data as JSON for AJAX refresh
            $search = $_POST['search'] ?? '';
            $page = max(1, (int)($_POST['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $customers = $customerObj->readAll($search, $limit, $offset);
            $totalCount = $customerObj->getTotalCount($search);
            $totalPages = ceil($totalCount / $limit);
            
            echo json_encode([
                'customers' => $customers,
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

// Get customers data
$customers = $customerObj->readAll($search, $limit, $offset);
$totalCount = $customerObj->getTotalCount($search);
$totalPages = ceil($totalCount / $limit);

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
    <title>Manajemen Customer - POS Penjualan</title>
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
                <li <?php echo ($page === 'customers.php') ? 'class="active"' : ''; ?>>
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
                <h1>Manajemen Customer</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Customer
                    </button>
                </div>
            </header>
            
            <!-- Search and Filter -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari customer..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="searchCustomers()">Cari</button>
                </div>
                <div class="filter-options">
                    <span class="results-count">Total: <?php echo $totalCount; ?> customer</span>
                </div>
            </div>
            
            <!-- Customers Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table" id="customersTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Customer</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <?php if (is_array($customers) && !empty($customers)): ?>
                                <?php foreach ($customers as $index => $customer): ?>
                                <tr data-id="<?php echo $customer['id_customer']; ?>">
                                    <td><?php echo (int)$offset + (int)$index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($customer['nama_customer']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['alamat'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['telp'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editCustomer(<?php echo $customer['id_customer']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCustomer(<?php echo $customer['id_customer']; ?>, '<?php echo htmlspecialchars($customer['nama_customer']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data customer</td>
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
    
    <!-- Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Customer</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    
                    <div class="form-group">
                        <label for="namaCustomer">Nama Customer *</label>
                        <input type="text" id="namaCustomer" name="nama_customer" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamatCustomer">Alamat</label>
                        <textarea id="alamatCustomer" name="alamat" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telpCustomer">Telepon</label>
                            <input type="text" id="telpCustomer" name="telp">
                        </div>
                        
                        <div class="form-group">
                            <label for="faxCustomer">Fax</label>
                            <input type="text" id="faxCustomer" name="fax">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="emailCustomer">Email</label>
                        <input type="email" id="emailCustomer" name="email">
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
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus customer ini?</p>
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
    <script src="assets/js/customer.js"></script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
