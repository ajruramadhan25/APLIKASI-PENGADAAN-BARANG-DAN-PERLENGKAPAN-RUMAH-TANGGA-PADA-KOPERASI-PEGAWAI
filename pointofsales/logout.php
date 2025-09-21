<?php
// Logout script for Aplikasi Pengadaan Barang Koperasi Pegawai
session_start();

// Log logout activity
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'unknown';
    
    // Database configuration
    $host = 'localhost';
    $dbname = 'pos_penjualan';
    $db_username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Log logout
        $userType = $_SESSION['user_type'] ?? 'petugas';
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (username, user_type, ip_address, success, user_agent, login_time) 
            VALUES (?, ?, ?, 1, 'Logout', NOW())
        ");
        $stmt->execute([$username, $userType, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        
        // Remove remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $token_hash = hash('sha256', $_COOKIE['remember_token']);
            // $deleteStmt = $pdo->prepare("DELETE FROM user_tokens WHERE token = ?");
            // $deleteStmt->execute([$token_hash]);
            
            // Clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        error_log("Logout logging failed: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header('Location: index.html');
exit;
?>
