<?php
// Petugas/Users Management Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}



// Include required files
require_once 'config.php';
require_once 'classes/Petugas.php';
require_once __DIR__ . '/includes/access_control.php';

// Check if user has admin level access
requireAccess(ACCESS_ADMIN);

// Initialize database connection
try {
    $database = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Petugas class
$petugasObj = new Petugas($database);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create':
            // Validate data
            $data = [
                'nama_user' => trim($_POST['nama_user'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'level' => (int)($_POST['level'] ?? 1)
            ];
            
            $errors = $petugasObj->validate($data);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $petugasObj->create($data);
            echo json_encode($result);
            exit;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'nama_user' => trim($_POST['nama_user'] ?? ''),
                'username' => trim($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'level' => (int)($_POST['level'] ?? 1)
            ];
            
            $errors = $petugasObj->validate($data, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $petugasObj->update($id, $data);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $result = $petugasObj->delete($id);
            echo json_encode($result);
            exit;
            
        case 'get':
            $id = (int)$_POST['id'];
            $petugas = $petugasObj->readById($id);
            echo json_encode($petugas);
            exit;
            
        case 'change_password':
            $id = (int)$_POST['id'];
            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $result = $petugasObj->changePassword($id, $oldPassword, $newPassword);
            echo json_encode($result);
            exit;
            
        case 'get_table_data':
            // Return table data as JSON for AJAX refresh
            $search = $_POST['search'] ?? '';
            $page = max(1, (int)($_POST['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $petugas = $petugasObj->readAll($search, $limit, $offset);
            $totalCount = $petugasObj->getTotalCount($search);
            $totalPages = ceil($totalCount / $limit);
            
            echo json_encode([
                'petugas' => $petugas,
                'totalCount' => $totalCount,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'offset' => $offset
            ]);
            exit;
            
        case 'get_user_detail':
            // Get user detail for modal
            $userId = (int)($_POST['id'] ?? 0);
            if ($userId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID user tidak valid']);
                break;
            }
            
            $userDetail = $petugasObj->readById($userId);
            if (!$userDetail) {
                echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
                break;
            }
            
            $userStats = $petugasObj->getUserStats($userId);
            $userDetail['stats'] = $userStats;
            
            echo json_encode([
                'success' => true,
                'data' => $userDetail
            ]);
            break;
    }
}

// Get search parameter
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Get petugas data
$petugas = $petugasObj->readAll($search, $limit, $offset);
$totalCount = $petugasObj->getTotalCount($search);
$totalPages = ceil($totalCount / $limit);

// Get levels for dropdown
$levels = $petugasObj->getLevels();

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
    <title>Manajemen Pengguna - POS Penjualan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/customer.css">
    <link rel="stylesheet" href="assets/css/users.css">
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
                <li <?php echo ($page === 'users.php') ? 'class="active"' : ''; ?>>
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
                <div>
                    <h1>Manajemen Pengguna</h1>
                    <p class="header-subtitle">Kelola data admin, petugas, dan manager sistem POS</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Pengguna
                    </button>
                </div>
            </header>
            
            <!-- Search and Filter -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari pengguna..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="searchUsers()">Cari</button>
                </div>
                <div class="filter-options">
                    <span class="results-count">Total: <?php echo $totalCount; ?> pengguna</span>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama User</th>
                                <th>Username</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <?php if (is_array($petugas) && !empty($petugas)): ?>
                                <?php foreach ($petugas as $index => $p): ?>
                                <tr data-id="<?php echo $p['id_user']; ?>">
                                    <td><?php echo (int)$offset + (int)$index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_user']); ?></td>
                                    <td><?php echo htmlspecialchars($p['username']); ?></td>
                                    <td>
                                        <?php
                                        $levelNames = [
                                            1 => 'Petugas',
                                            2 => 'Manager', 
                                            3 => 'Admin'
                                        ];
                                        $levelName = $levelNames[$p['level']] ?? 'Unknown';
                                        $levelClass = [
                                            1 => 'level-petugas',
                                            2 => 'level-manager',
                                            3 => 'level-admin'
                                        ];
                                        $levelClassValue = $levelClass[$p['level']] ?? 'level-unknown';
                                        ?>
                                        <span class="level-badge <?php echo $levelClassValue; ?>">
                                            <i class="fas fa-<?php echo $p['level'] == 1 ? 'cash-register' : ($p['level'] == 2 ? 'user-tie' : 'crown'); ?>"></i>
                                            <?php echo $levelName; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle"></i>
                                            Aktif
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $stats = $petugasObj->getUserStats($p['id_user']);
                                        echo $stats['last_login'] ? date('d/m/Y H:i', strtotime($stats['last_login'])) : 'Belum pernah';
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewUser(<?php echo $p['id_user']; ?>)" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $p['id_user']; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="changePassword(<?php echo $p['id_user']; ?>, '<?php echo htmlspecialchars($p['username']); ?>')" title="Ubah Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($p['id_user'] != $user_id): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $p['id_user']; ?>, '<?php echo htmlspecialchars($p['nama_user']); ?>')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pengguna</td>
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
    
    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Pengguna</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-group">
                        <label for="namaUser">Nama User *</label>
                        <input type="text" id="namaUser" name="nama_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="usernameUser">Username *</label>
                        <input type="text" id="usernameUser" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="passwordUser">Password <span id="passwordRequired">*</span></label>
                        <input type="password" id="passwordUser" name="password">
                        <small class="form-text">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="levelUser">Level *</label>
                        <select id="levelUser" name="level" required>
                            <option value="">Pilih Level</option>
                            <?php foreach (getLevelOptions() as $option): ?>
                                <option value="<?php echo $option['value']; ?>">
                                    <?php echo $option['name']; ?> - <?php echo $option['description']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">
                            <strong>Petugas (Petugas):</strong> Bisa akses Sales, Transactions, Customers, Items<br>
                            <strong>Manager:</strong> Hanya bisa akses Laporan<br>
                            <strong>Admin:</strong> Akses penuh sistem termasuk Users
                        </small>
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
    
    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h2>Ubah Password</h2>
                <span class="close" onclick="closeChangePasswordModal()">&times;</span>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <input type="hidden" id="changePasswordUserId" name="id">
                    
                    <div class="form-group">
                        <label for="oldPassword">Password Lama *</label>
                        <input type="password" id="oldPassword" name="old_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">Password Baru *</label>
                        <input type="password" id="newPassword" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Konfirmasi Password *</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key"></i>
                        Ubah Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- User Detail Modal -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content small">
            <div class="modal-header">
                <h2>Detail Pengguna</h2>
                <span class="close" onclick="closeUserDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="userDetailContent">
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserDetailModal()">Tutup</button>
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
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus pengguna ini?</p>
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
    
    <!-- User Detail Modal -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Pengguna</h2>
                <span class="close" onclick="closeDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="userDetailContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/users.js"></script>
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
