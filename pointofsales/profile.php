<?php
session_start();
require_once 'config.php';
require_once 'includes/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['level'])) {
    header('Location: login.php');
    exit;
}

// Get database connection
$pdo = db();

// Get user data based on level
$user = [];
$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['level'];

if ($user_level == 1) {
    // Petugas
    $stmt = $pdo->prepare("SELECT * FROM petugas WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else if ($user_level == 2) {
    // Manager
    $stmt = $pdo->prepare("SELECT * FROM manager WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    // Admin - check both tables
    $stmt = $pdo->prepare("
        SELECT *, 'petugas' as table_source FROM petugas WHERE id_user = ? AND level = 3
        UNION ALL
        SELECT *, 'manager' as table_source FROM manager WHERE id_user = ? AND level = 3
    ");
    $stmt->execute([$user_id, $user_id]);
    $user = $stmt->fetch();
}

if (!$user) {
    header('Location: login.php');
    exit;
}

// Get identitas data
$stmt = $pdo->query("SELECT * FROM identitas LIMIT 1");
$identitas = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Aplikasi Pengadaan Barang Koperasi Pegawai</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
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
                <li>
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
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1>Profil Saya</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="editProfile()">
                        <i class="fas fa-edit"></i>
                        Edit Profil
                    </button>
                </div>
            </header>
            
            <!-- Profile Content -->
            <div class="content-section">
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="<?php echo getUserRoleIcon(); ?>"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['nama_user']); ?></h2>
                            <p class="profile-role"><?php echo getUserRole(); ?></p>
                            <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-group">
                            <h3>Informasi Pribadi</h3>
                            <div class="detail-item">
                                <label>Nama Lengkap:</label>
                                <span><?php echo htmlspecialchars($user['nama_user']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Username:</label>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Level Akses:</label>
                                <span class="level-badge level-<?php echo strtolower(getUserRole()); ?>"><?php echo getUserRole(); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($identitas): ?>
                        <div class="detail-group">
                            <h3>Informasi Perusahaan</h3>
                            <div class="detail-item">
                                <label>Nama Perusahaan:</label>
                                <span><?php echo htmlspecialchars($identitas['nama_identitas']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Badan Hukum:</label>
                                <span><?php echo htmlspecialchars($identitas['badan_hukum'] ?? '-'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>NPWP:</label>
                                <span><?php echo htmlspecialchars($identitas['npwp'] ?? '-'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($identitas['email'] ?? '-'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Alamat:</label>
                                <span><?php echo htmlspecialchars($identitas['alamat'] ?? '-'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Telepon:</label>
                                <span><?php echo htmlspecialchars($identitas['telp'] ?? '-'); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Fax:</label>
                                <span><?php echo htmlspecialchars($identitas['fax'] ?? '-'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profil</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editProfileForm" data-nama-user="<?php echo htmlspecialchars($user['nama_user']); ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_user">Nama Lengkap:</label>
                        <input type="text" id="nama_user" name="nama_user" value="<?php echo htmlspecialchars($user['nama_user']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="current_password">Password Lama:</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Masukkan password lama">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password Baru:</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Masukkan password baru">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru:</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Konfirmasi password baru">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/profile.js"></script>
</body>
</html>
