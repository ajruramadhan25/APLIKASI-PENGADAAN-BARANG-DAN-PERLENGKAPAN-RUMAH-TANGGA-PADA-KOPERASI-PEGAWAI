<?php
// Login Processing Script for Aplikasi Pengadaan Barang Koperasi Pegawai

// Ensure session is properly configured before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// Database configuration
$host = 'localhost';
$dbname = 'pos_penjualan';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal']));
}

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to validate input
function validateInput($username, $password) {
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    return $errors;
}

// Function to log login attempts
function logLoginAttempt($pdo, $username, $ip, $success, $userAgent, $userType = 'petugas') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (username, user_type, ip_address, success, user_agent, login_time) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $userType, $ip, $success ? 1 : 0, $userAgent]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

// Function to check if user is locked
function isUserLocked($pdo, $username, $ip) {
    try {
        // Check for too many failed attempts from same IP in last 15 minutes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_logs 
            WHERE ip_address = ? AND success = 0 AND login_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= 5; // Lock after 5 failed attempts
    } catch (PDOException $e) {
        error_log("Failed to check user lock status: " . $e->getMessage());
        return false;
    }
}

// Main login processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get and sanitize input
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Get client information
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Validate input
    $errors = validateInput($username, $password);
    
    if (!empty($errors)) {
        logLoginAttempt($pdo, $username, $ip, false, $userAgent);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    // Check if user is locked
    if (isUserLocked($pdo, $username, $ip)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Terlalu banyak percobaan login yang gagal. Coba lagi dalam 15 menit.'
        ]);
        exit;
    }
    
    try {
        // Query user from petugas table first
        $stmt = $pdo->prepare("
            SELECT p.id_user as id, p.username, p.password, p.nama_user as full_name, p.level,
                   l.level as role_name
            FROM petugas p
            LEFT JOIN level l ON p.level = l.id_level
            WHERE p.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        $userType = 'petugas';
        
        // If not found in petugas, try manager table
        if (!$user) {
            $stmt = $pdo->prepare("
                SELECT m.id_user as id, m.username, m.password, m.nama_user as full_name, m.level,
                       l.level as role_name
                FROM manager m
                LEFT JOIN level l ON m.level = l.id_level
                WHERE m.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            $userType = 'manager';
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            
            // Update last login time (if you want to add last_login column later)
            // if ($userType === 'petugas') {
            //     $updateStmt = $pdo->prepare("UPDATE petugas SET last_login = NOW() WHERE id_user = ?");
            // } else {
            //     $updateStmt = $pdo->prepare("UPDATE manager SET last_login = NOW() WHERE id_user = ?");
            // }
            // $updateStmt->execute([$user['id']]);
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type'] = $userType;
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['login_time'] = time();
            
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 days
                
                // Store token in database (if you want to add user_tokens table later)
                // $tokenStmt = $pdo->prepare("
                //     INSERT INTO user_tokens (user_id, user_type, token, expires_at) 
                //     VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                // ");
                // $tokenStmt->execute([$user['id'], $userType, hash('sha256', $token)]);
            }
            
            // Log successful login
            logLoginAttempt($pdo, $username, $ip, true, $userAgent, $userType);
            
            // Return success response
            echo json_encode([
                'success' => true, 
                'message' => 'Login berhasil!',
                'redirect' => 'dashboard.php',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'user_type' => $userType,
                    'role' => $user['role_name'],
                    'level' => $user['level']
                ]
            ]);
        } else {
            // Login failed
            logLoginAttempt($pdo, $username, $ip, false, $userAgent, $userType);
            echo json_encode([
                'success' => false, 
                'message' => 'Username atau password salah!'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Login query failed: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
        ]);
    }
} else {
    // Redirect if not POST request
    header('Location: index.html');
    exit;
}
?>
