<?php
// config.php - Aplikasi Pengadaan Barang Koperasi Pegawai
// Versi ini DISESUAIKAN agar tidak bentrok dengan includes/access_control.php
// (tidak lagi mendefinisikan hasPermission/akses lain yang sudah ada di RBAC).

// ===============================
// Database Configuration
// ===============================
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_penjualan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===============================
// Application Configuration
// ===============================
define('APP_NAME', 'Aplikasi Pengadaan Barang Koperasi Pegawai');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/pointofsales');

// Muat utilitas RBAC (aman dari redeclare)
require_once __DIR__ . '/includes/access_control.php';

// ===============================
// Security Configuration
// ===============================
define('SESSION_TIMEOUT', 3600);        // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);      // 15 minutes
define('PASSWORD_MIN_LENGTH', 6);

// ===============================
// File Upload Configuration
// ===============================
define('UPLOAD_MAX_SIZE', 5242880);     // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// ===============================
// Email Configuration (opsional)
// ===============================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@koperasi.com');
define('SMTP_FROM_NAME', 'Aplikasi Pengadaan Barang');

// ===============================
// Pagination Configuration
// ===============================
define('RECORDS_PER_PAGE', 20);
define('MAX_RECORDS_PER_PAGE', 100);

// ===============================
// Date and Time Configuration
// ===============================
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set(TIMEZONE);

// ===============================
// Environment & Error Reporting
// ===============================
define('APP_ENV', 'development'); // ganti 'production' saat live
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ===============================
/* Database Connection (Singleton) */
// ===============================
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function __clone() { throw new Exception('Cannot clone singleton'); }
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}

// Helper cepat untuk ambil PDO
if (!function_exists('db')) {
    function db(): PDO { return Database::getInstance()->getConnection(); }
}

// ===============================
// Utility Functions (umum)
// ===============================
function sanitizeInput($data) { return htmlspecialchars(strip_tags(trim($data ?? '')), ENT_QUOTES, 'UTF-8'); }
function validateEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
function validatePassword($password) { return strlen($password ?? '') >= PASSWORD_MIN_LENGTH; }
function hashPassword($password) { return password_hash($password, PASSWORD_DEFAULT); }
function verifyPassword($password, $hash) { return password_verify($password, $hash); }
function generateToken($length = 32) { return bin2hex(random_bytes($length)); }
function formatCurrency($amount, $currency = 'IDR') { return 'Rp ' . number_format((float)$amount, 0, ',', '.'); }
function formatDate($date, $format = DATE_FORMAT) { return date($format, strtotime($date)); }
function formatDateTime($datetime, $format = DATETIME_FORMAT) { return date($format, strtotime($datetime)); }
function getCurrentDateTime() { return date('Y-m-d H:i:s'); }
function getCurrentDate() { return date('Y-m-d'); }

// ===============================
// Session Management
// ===============================
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        session_start();
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

function checkSessionTimeout(): bool {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin() {
    startSecureSession();
    if (!isset($_SESSION['user_id']) || !checkSessionTimeout()) {
        header('Location: index.html');
        exit;
    }
}

// ===============================
// RBAC Compatibility Helpers
// (delegasi ke includes/access_control.php)
// ===============================

// Aliases ringan agar kode lama tetap jalan tanpa duplikasi logika.
if (!function_exists('userLevel')) {
    function userLevel(): int { return isset($_SESSION['level']) ? (int)$_SESSION['level'] : 0; }
}
if (!function_exists('isAdmin')) {
    function isAdmin(): bool { return hasAccess(ACCESS_ADMIN); }
}
if (!function_exists('isManager')) {
    function isManager(): bool { return hasAccess(ACCESS_MANAGER); }
}
if (!function_exists('isPetugas')) {
    function isPetugas(): bool { return hasAccess(ACCESS_KASIR); }
}
// Catatan: JANGAN definisikan hasPermission() di sini agar tidak bentrok.
// Gunakan fungsi hasPermission() dari access_control.php (permission berbasis string).

// ===============================
// File Upload Utilities
// ===============================
function validateFileUpload($file, $maxSize = UPLOAD_MAX_SIZE, $allowedTypes = ALLOWED_FILE_TYPES) {
    $errors = [];
    if (!is_array($file) || !isset($file['error'])) {
        $errors[] = 'Tidak ada file yang diupload.';
        return $errors;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error (code ' . $file['error'] . ').';
        return $errors;
    }
    if ($file['size'] > $maxSize) $errors[] = 'Ukuran file melebihi batas.';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) $errors[] = 'Tipe file tidak diizinkan.';
    return $errors;
}

function uploadFile($file, $uploadDir = 'uploads/') {
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $safeName = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $file['name']);
    $fileName = uniqid('', true) . '_' . $safeName;
    $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    if (move_uploaded_file($file['tmp_name'], $path)) return $fileName;
    return false;
}

// ===============================
// Logging & JSON Responses
// ===============================
function logActivity($message, $level = 'info', $userId = null) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/activity_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $uid = $userId ?? ($_SESSION['user_id'] ?? 'system');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = "[$timestamp] [$level] User:$uid IP:$ip - $message" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendErrorResponse($message, $statusCode = 400) {
    sendJsonResponse(['success' => false, 'message' => $message], $statusCode);
}

function sendSuccessResponse($data = null, $message = 'Success') {
    $res = ['success' => true, 'message' => $message];
    if ($data !== null) $res['data'] = $data;
    sendJsonResponse($res);
}

// ===============================
// Bootstrap dirs
// ===============================
if (!is_dir(__DIR__ . '/logs'))    mkdir(__DIR__ . '/logs', 0755, true);
if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);

?>
