<?php
// Transaction Management Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Include required files
require_once 'config.php';
require_once 'classes/Transaction.php';
require_once 'classes/Sales.php';
require_once 'classes/Item.php';
require_once 'includes/access_control.php';

// Check if user has access to transactions page (Petugas level and above)
requireAccess(ACCESS_KASIR);

// Initialize database connection
try {
    $database = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize classes
$transactionObj = new Transaction($database);
$salesObj = new Sales($database);
$itemObj = new Item($database);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create':
            // Validate data
            $data = [
                'id_sales' => (int)($_POST['id_sales'] ?? 0),
                'id_item' => (int)($_POST['id_item'] ?? 0),
                'quantity' => (float)($_POST['quantity'] ?? 0),
                'price' => (float)($_POST['price'] ?? 0),
                'amount' => (float)($_POST['amount'] ?? 0)
            ];
            
            $errors = $transactionObj->validate($data);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $transactionObj->create($data);
            echo json_encode($result);
            exit;
            
        case 'create_temp':
            // Create temp transaction
            $data = [
                'id_item' => (int)($_POST['id_item'] ?? 0),
                'quantity' => (float)($_POST['quantity'] ?? 0),
                'price' => (float)($_POST['price'] ?? 0),
                'amount' => (float)($_POST['amount'] ?? 0),
                'session_id' => session_id(),
                'remark' => trim($_POST['remark'] ?? '')
            ];
            
            $result = $transactionObj->createTemp($data);
            echo json_encode($result);
            exit;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'id_sales' => (int)($_POST['id_sales'] ?? 0),
                'id_item' => (int)($_POST['id_item'] ?? 0),
                'quantity' => (float)($_POST['quantity'] ?? 0),
                'price' => (float)($_POST['price'] ?? 0),
                'amount' => (float)($_POST['amount'] ?? 0)
            ];
            
            $errors = $transactionObj->validate($data, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $transactionObj->update($id, $data);
            echo json_encode($result);
            exit;
            
        case 'update_temp':
            $id = (int)$_POST['id'];
            $data = [
                'id_item' => (int)($_POST['id_item'] ?? 0),
                'quantity' => (float)($_POST['quantity'] ?? 0),
                'price' => (float)($_POST['price'] ?? 0),
                'amount' => (float)($_POST['amount'] ?? 0),
                'remark' => trim($_POST['remark'] ?? '')
            ];
            
            $result = $transactionObj->updateTemp($id, $data);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $result = $transactionObj->delete($id);
            echo json_encode($result);
            exit;
            
        case 'delete_temp':
            $id = (int)$_POST['id'];
            $result = $transactionObj->deleteTemp($id);
            echo json_encode($result);
            exit;
            
        case 'get':
            $id = (int)$_POST['id'];
            $transaction = $transactionObj->readById($id);
            echo json_encode($transaction);
            exit;
            
        case 'get_temp':
            $id = (int)$_POST['id'];
            $tempTransactions = $transactionObj->readTempBySession(session_id());
            $transaction = null;
            foreach ($tempTransactions as $temp) {
                if ($temp['id_transaction'] == $id) {
                    $transaction = $temp;
                    break;
                }
            }
            echo json_encode($transaction);
            exit;
            
        case 'get_table_data':
            // Return table data as JSON for AJAX refresh
            $search = $_POST['search'] ?? '';
            $page = max(1, (int)($_POST['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $transactions = $transactionObj->readAll($search, $limit, $offset);
            $totalCount = $transactionObj->getTotalCount($search);
            $totalPages = ceil($totalCount / $limit);
            
            echo json_encode([
                'transactions' => $transactions,
                'totalCount' => $totalCount,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'offset' => $offset
            ]);
            exit;
            
        case 'get_temp_data':
            // Return temp transaction data
            $tempTransactions = $transactionObj->readTempBySession(session_id());
            $totalAmount = $transactionObj->getTotalAmountBySession(session_id());
            
            echo json_encode([
                'transactions' => $tempTransactions,
                'totalAmount' => $totalAmount
            ]);
            exit;
            
        case 'clear_temp':
            $result = $transactionObj->clearTempBySession(session_id());
            echo json_encode($result);
            exit;
            
        case 'finalize':
            $salesId = (int)($_POST['sales_id'] ?? 0);
            $result = $transactionObj->moveTempToFinal(session_id(), $salesId);
            echo json_encode($result);
            exit;
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Get transactions data
$transactions = $transactionObj->readAll($search, $limit, $offset);
$totalCount = $transactionObj->getTotalCount($search);
$totalPages = ceil($totalCount / $limit);

// Get dropdown data
$items = $transactionObj->getItems();
$sales = $transactionObj->getSales();

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
    <title>Manajemen Transaction - POS Penjualan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/customer.css">
    <link rel="stylesheet" href="assets/css/transactions.css">
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
                <li <?php echo ($page === 'transactions.php') ? 'class="active"' : ''; ?>>
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
                <h1>Manajemen Transaction</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-success" onclick="openPOSModal()">
                        <i class="fas fa-cash-register"></i>
                        POS
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Transaction
                    </button>
                </div>
            </header>
            
            <!-- Search and Filter -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari transaction..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="searchTransactions()">Cari</button>
                </div>
                <div class="filter-options">
                    <span class="results-count">Total: <?php echo $totalCount; ?> transaction</span>
                </div>
            </div>
            
            <!-- Transactions Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Sales No</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Amount</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <?php if (is_array($transactions) && !empty($transactions)): ?>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                <tr data-id="<?php echo $transaction['id_transaction']; ?>">
                                    <td><?php echo (int)$offset + (int)$index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['do_number'] ?? 'INV' . str_pad($transaction['id_sales'], 4, '0', STR_PAD_LEFT)); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['nama_item']); ?></td>
                                    <td><?php echo number_format($transaction['quantity'], 3); ?> <?php echo htmlspecialchars($transaction['uom']); ?></td>
                                    <td>Rp <?php echo number_format($transaction['price'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editTransaction(<?php echo $transaction['id_transaction']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteTransaction(<?php echo $transaction['id_transaction']; ?>, '<?php echo htmlspecialchars($transaction['nama_item']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data transaction</td>
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
    
    <!-- Transaction Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Transaction</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="transactionForm">
                <div class="modal-body">
                    <input type="hidden" id="transactionId" name="id">
                    
                    <div class="form-group">
                        <label for="salesTransaction">Sales *</label>
                        <select id="salesTransaction" name="id_sales" required>
                            <option value="">Pilih Sales</option>
                            <?php foreach ($sales as $sale): ?>
                                <option value="<?php echo $sale['id_sales']; ?>">
                                    <?php echo htmlspecialchars($sale['do_number'] ?? 'SALE-' . $sale['id_sales']); ?> - 
                                    <?php echo htmlspecialchars($sale['nama_customer'] ?? 'Customer Umum'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="itemTransaction">Item *</label>
                        <select id="itemTransaction" name="id_item" required>
                            <option value="">Pilih Item</option>
                            <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['id_item']; ?>" data-price="<?php echo $item['harga_jual']; ?>">
                                    <?php echo htmlspecialchars($item['nama_item']); ?> - 
                                    Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?> / <?php echo $item['uom']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantityTransaction">Quantity *</label>
                            <input type="number" id="quantityTransaction" name="quantity" step="0.001" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="priceTransaction">Price *</label>
                            <input type="number" id="priceTransaction" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="amountTransaction">Amount *</label>
                        <input type="number" id="amountTransaction" name="amount" step="0.01" min="0" required readonly>
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
    
    <!-- POS Modal -->
    <div id="posModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>Point of Sales (POS)</h2>
                <span class="close" onclick="closePOSModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="pos-container">
                    <div class="pos-left">
                        <h3>Tambah Item</h3>
                        <form id="posForm">
                            <div class="form-group">
                                <label for="posItem">Item *</label>
                                <select id="posItem" name="id_item" required>
                                    <option value="">Pilih Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['id_item']; ?>" data-price="<?php echo $item['harga_jual']; ?>">
                                            <?php echo htmlspecialchars($item['nama_item']); ?> - 
                                            Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?> / <?php echo $item['uom']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="posQuantity">Quantity *</label>
                                    <input type="number" id="posQuantity" name="quantity" step="0.001" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="posPrice">Price *</label>
                                    <input type="number" id="posPrice" name="price" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="posRemark">Remark</label>
                                <input type="text" id="posRemark" name="remark" placeholder="Catatan khusus">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Tambah ke Keranjang
                            </button>
                        </form>
                    </div>
                    
                    <div class="pos-right">
                        <h3>Keranjang</h3>
                        <div id="posCart" class="pos-cart">
                            <!-- Cart items will be loaded here -->
                        </div>
                        
                        <div class="pos-total">
                            <div class="total-row">
                                <span>Total:</span>
                                <span id="posTotalAmount">Rp 0</span>
                            </div>
                        </div>
                        
                        <div class="pos-actions">
                            <button class="btn btn-secondary" onclick="clearCart()">
                                <i class="fas fa-trash"></i>
                                Kosongkan
                            </button>
                            <button class="btn btn-success" onclick="finalizeTransaction()">
                                <i class="fas fa-check"></i>
                                Finalisasi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus transaction ini?</p>
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
    <script src="assets/js/transactions.js"></script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
