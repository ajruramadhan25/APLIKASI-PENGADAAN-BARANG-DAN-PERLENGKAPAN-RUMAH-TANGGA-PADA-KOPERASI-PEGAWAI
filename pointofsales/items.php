<?php
// Item Management Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Include required files
require_once 'config.php';
require_once 'classes/Item.php';
require_once __DIR__ . '/includes/access_control.php';


// Check if user has access to items page (Petugas level and above)
requireAccess(ACCESS_KASIR);

// Initialize database connection
try {
    $database = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize Item class
$itemObj = new Item($database);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create':
            // Validate data
            $data = [
                'nama_item' => trim($_POST['nama_item'] ?? ''),
                'uom' => trim($_POST['uom'] ?? ''),
                'harga_beli' => (float)($_POST['harga_beli'] ?? 0),
                'harga_jual' => (float)($_POST['harga_jual'] ?? 0)
            ];
            
            $errors = $itemObj->validate($data);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $itemObj->create($data);
            echo json_encode($result);
            exit;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'nama_item' => trim($_POST['nama_item'] ?? ''),
                'uom' => trim($_POST['uom'] ?? ''),
                'harga_beli' => (float)($_POST['harga_beli'] ?? 0),
                'harga_jual' => (float)($_POST['harga_jual'] ?? 0)
            ];
            
            $errors = $itemObj->validate($data, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = $itemObj->update($id, $data);
            echo json_encode($result);
            exit;
            
        case 'delete':
            $id = (int)$_POST['id'];
            $result = $itemObj->delete($id);
            echo json_encode($result);
            exit;
            
        case 'get':
            $id = (int)$_POST['id'];
            $item = $itemObj->readById($id);
            echo json_encode($item);
            exit;
            
        case 'get_table_data':
            // Return table data as JSON for AJAX refresh
            $search = $_POST['search'] ?? '';
            $page = max(1, (int)($_POST['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $items = $itemObj->readAll($search, $limit, $offset);
            $totalCount = $itemObj->getTotalCount($search);
            $totalPages = ceil($totalCount / $limit);
            
            echo json_encode([
                'items' => $items,
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

// Get items data
$items = $itemObj->readAll($search, $limit, $offset);
$totalCount = $itemObj->getTotalCount($search);
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
    <title>Manajemen Item - POS Penjualan</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/customer.css">
    <link rel="stylesheet" href="assets/css/items.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        // Define functions in head to ensure they're available immediately
        console.log('Head script loaded');
        
        let currentItemId = null;
        
        function openAddModal() {
            console.log('openAddModal called');
            currentItemId = null;
            document.getElementById('modalTitle').textContent = 'Tambah Item';
            document.getElementById('itemForm').reset();
            clearFormErrors();
            document.getElementById('itemModal').style.display = 'block';
            
            setTimeout(() => {
                const namaItem = document.getElementById('namaItem');
                if (namaItem) namaItem.focus();
                calculateMargin();
            }, 100);
        }
        
        function editItem(id) {
            console.log('editItem called with id:', id);
            currentItemId = id;
            document.getElementById('modalTitle').textContent = 'Edit Item';
            document.getElementById('itemModal').style.display = 'block';
            
            fetch(`items.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('namaItem').value = data.item.nama_item;
                        document.getElementById('hargaBeli').value = data.item.harga_beli;
                        document.getElementById('hargaJual').value = data.item.harga_jual;
                        document.getElementById('stok').value = data.item.stok;
                        document.getElementById('satuan').value = data.item.satuan;
                        document.getElementById('kategori').value = data.item.kategori;
                        document.getElementById('deskripsi').value = data.item.deskripsi || '';
                        calculateMargin();
                        setTimeout(() => {
                            const namaItem = document.getElementById('namaItem');
                            if (namaItem) namaItem.focus();
                        }, 100);
                    } else {
                        showNotification('Gagal memuat data item', 'error');
                        closeModal();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan saat memuat data', 'error');
                    closeModal();
                });
        }
        
        function deleteItem(id, name) {
            console.log('deleteItem called with id:', id, 'name:', name);
            currentItemId = id;
            document.getElementById('confirmMessage').textContent = 
                `Apakah Anda yakin ingin menghapus item "${name}"?`;
            document.getElementById('confirmModal').style.display = 'block';
        }
        
        function confirmDelete() {
            if (!currentItemId) return;
            
            const deleteBtn = document.querySelector('#confirmModal .btn-danger');
            const originalText = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.style.opacity = '0.7';
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', currentItemId);
            
            fetch('items.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeConfirmModal();
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan sistem', 'error');
            })
            .finally(() => {
                deleteBtn.innerHTML = originalText;
                deleteBtn.disabled = false;
                deleteBtn.style.opacity = '1';
            });
        }
        
        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
            clearFormErrors();
        }
        
        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
            currentItemId = null;
        }
        
        function clearFormErrors() {
            const errorFields = document.querySelectorAll('#itemForm .error');
            errorFields.forEach(field => {
                field.classList.remove('error');
                const existingError = field.parentNode.querySelector('.field-error');
                if (existingError) {
                    existingError.remove();
                }
            });
        }
        
        function calculateMargin() {
            const hargaBeli = parseFloat(document.getElementById('hargaBeli').value) || 0;
            const hargaJual = parseFloat(document.getElementById('hargaJual').value) || 0;
            
            if (hargaBeli > 0 && hargaJual > 0) {
                const margin = hargaJual - hargaBeli;
                const marginPercent = (margin / hargaBeli) * 100;
                
                const marginDisplay = document.getElementById('marginDisplay');
                if (marginDisplay) {
                    marginDisplay.textContent = `Margin: Rp ${margin.toLocaleString()} (${marginPercent.toFixed(1)}%)`;
                    marginDisplay.style.color = margin >= 0 ? '#27ae60' : '#e74c3c';
                }
            }
        }
        
        function showNotification(message, type = 'info') {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 16px 20px;
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                border-left: 4px solid ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                min-width: 300px;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}" 
                       style="color: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'}; font-size: 18px;"></i>
                    <span style="color: #333; font-weight: 500;">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }
        
        // Make functions globally available
        window.openAddModal = openAddModal;
        window.editItem = editItem;
        window.deleteItem = deleteItem;
        window.confirmDelete = confirmDelete;
        window.closeModal = closeModal;
        window.closeConfirmModal = closeConfirmModal;
        
        console.log('All functions defined in head and made global');
        
        // Add event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded - adding event listeners');
            
            // Form submission
            const itemForm = document.getElementById('itemForm');
            if (itemForm) {
                itemForm.addEventListener('submit', handleFormSubmit);
                console.log('Form submit listener added');
            }
            
            // Price calculation
            const hargaBeliInput = document.getElementById('hargaBeli');
            const hargaJualInput = document.getElementById('hargaJual');
            
            if (hargaBeliInput) {
                hargaBeliInput.addEventListener('input', calculateMargin);
            }
            if (hargaJualInput) {
                hargaJualInput.addEventListener('input', calculateMargin);
            }
        });
        
        function handleFormSubmit(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            const form = e.target;
            const inputs = form.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                showNotification('Mohon perbaiki error pada form', 'error');
                return;
            }
            
            const formData = new FormData(form);
            formData.append('action', currentItemId ? 'update' : 'create');
            
            if (currentItemId) {
                formData.append('id', currentItemId);
            }
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            
            fetch('items.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    location.reload();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan sistem', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            });
        }
        
        function validateField(field) {
            const value = field.value.trim();
            const fieldName = field.getAttribute('name');
            let isValid = true;
            let errorMessage = '';
            
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'Field ini wajib diisi';
            }
            
            if (value) {
                switch (fieldName) {
                    case 'harga_beli':
                    case 'harga_jual':
                        if (isNaN(value) || parseFloat(value) < 0) {
                            isValid = false;
                            errorMessage = 'Harga harus berupa angka positif';
                        }
                        break;
                    case 'stok':
                        if (isNaN(value) || parseInt(value) < 0) {
                            isValid = false;
                            errorMessage = 'Stok harus berupa angka positif';
                        }
                        break;
                }
            }
            
            if (isValid) {
                clearFieldError(field);
            } else {
                showFieldError(field, errorMessage);
            }
            
            return isValid;
        }
        
        function showFieldError(field, message) {
            clearFieldError(field);
            
            field.classList.add('error');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = message;
            errorDiv.style.cssText = `
                color: #e74c3c;
                font-size: 12px;
                margin-top: 4px;
            `;
            
            field.parentNode.appendChild(errorDiv);
        }
        
        function clearFieldError(field) {
            field.classList.remove('error');
            
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
        }
    </script>
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
                <li <?php echo ($page === 'items.php') ? 'class="active"' : ''; ?>>
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
                <h1>Manajemen Item</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Item
                    </button>
                </div>
            </header>
            
            <!-- Search and Filter -->
            <div class="search-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari item..." value="<?php echo htmlspecialchars($search); ?>">
                    <button onclick="searchItems()">Cari</button>
                </div>
                <div class="filter-options">
                    <span class="results-count">Total: <?php echo $totalCount; ?> item</span>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="content-section">
                <div class="table-container">
                    <table class="data-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Item</th>
                                <th>Satuan</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Margin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <?php if (is_array($items) && !empty($items)): ?>
                                <?php foreach ($items as $index => $item): ?>
                                <tr data-id="<?php echo $item['id_item']; ?>">
                                    <td><?php echo (int)$offset + (int)$index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                                    <td><?php echo htmlspecialchars($item['uom']); ?></td>
                                    <td>Rp <?php echo number_format($item['harga_beli'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                        $margin = $item['harga_jual'] - $item['harga_beli'];
                                        $marginPercent = $item['harga_beli'] > 0 ? ($margin / $item['harga_beli']) * 100 : 0;
                                        ?>
                                        <span class="margin-badge <?php echo $marginPercent >= 20 ? 'good' : ($marginPercent >= 10 ? 'ok' : 'low'); ?>">
                                            Rp <?php echo number_format($margin, 0, ',', '.'); ?> 
                                            (<?php echo number_format($marginPercent, 1); ?>%)
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editItem(<?php echo $item['id_item']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['id_item']; ?>, '<?php echo htmlspecialchars($item['nama_item']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data item</td>
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
    
    <!-- Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Item</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="itemForm">
                <div class="modal-body">
                    <input type="hidden" id="itemId" name="id">
                    
                    <div class="form-group">
                        <label for="namaItem">Nama Item *</label>
                        <input type="text" id="namaItem" name="nama_item" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="uomItem">Satuan (UOM) *</label>
                        <select id="uomItem" name="uom" required>
                            <option value="">Pilih Satuan</option>
                            <?php 
                            $uomOptions = $itemObj->getUOMOptions();
                            foreach ($uomOptions as $value => $label): 
                            ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hargaBeli">Harga Beli *</label>
                            <input type="number" id="hargaBeli" name="harga_beli" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hargaJual">Harga Jual *</label>
                            <input type="number" id="hargaJual" name="harga_jual" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div id="marginPreview" class="margin-preview">
                            <span>Margin: <span id="marginValue">Rp 0 (0%)</span></span>
                        </div>
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
                <p id="confirmMessage">Apakah Anda yakin ingin menghapus item ini?</p>
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
    <script src="assets/js/profile-click.js"></script>
</body>
</html>
