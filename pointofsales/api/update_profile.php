<?php
session_start();
require_once '../config.php';
require_once '../includes/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['level'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get database connection
$pdo = db();

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['level'];

try {
    // Validate required fields
    if (empty($input['nama_user']) || empty($input['username'])) {
        throw new Exception('Nama dan username harus diisi!');
    }
    
    // Check if username already exists (excluding current user)
    $checkUsername = '';
    if ($user_level == 1) {
        $checkUsername = "SELECT id_user FROM petugas WHERE username = ? AND id_user != ?";
    } else if ($user_level == 2) {
        $checkUsername = "SELECT id_user FROM manager WHERE username = ? AND id_user != ?";
    } else {
        // Admin - check both tables
        $checkUsername = "
            SELECT id_user FROM petugas WHERE username = ? AND id_user != ?
            UNION ALL
            SELECT id_user FROM manager WHERE username = ? AND id_user != ?
        ";
    }
    
    $stmt = $pdo->prepare($checkUsername);
    if ($user_level == 3) {
        $stmt->execute([$input['username'], $user_id, $input['username'], $user_id]);
    } else {
        $stmt->execute([$input['username'], $user_id]);
    }
    
    if ($stmt->fetch()) {
        throw new Exception('Username sudah digunakan!');
    }
    
    // Prepare update query based on user level
    $updateQuery = '';
    $params = [];
    
    if ($user_level == 1) {
        // Petugas
        $updateQuery = "UPDATE petugas SET nama_user = ?, username = ?";
        $params = [$input['nama_user'], $input['username']];
    } else if ($user_level == 2) {
        // Manager
        $updateQuery = "UPDATE manager SET nama_user = ?, username = ?";
        $params = [$input['nama_user'], $input['username']];
    } else {
        // Admin - update both tables
        $updateQuery = "
            UPDATE petugas SET nama_user = ?, username = ? WHERE id_user = ? AND level = 3;
            UPDATE manager SET nama_user = ?, username = ? WHERE id_user = ? AND level = 3;
        ";
        $params = [
            $input['nama_user'], $input['username'], $user_id,
            $input['nama_user'], $input['username'], $user_id
        ];
    }
    
    // Handle password update if provided
    if (!empty($input['current_password']) && !empty($input['new_password'])) {
        // Verify current password
        $verifyQuery = '';
        if ($user_level == 1) {
            $verifyQuery = "SELECT password FROM petugas WHERE id_user = ?";
        } else if ($user_level == 2) {
            $verifyQuery = "SELECT password FROM manager WHERE id_user = ?";
        } else {
            // Admin - check both tables
            $verifyQuery = "
                SELECT password FROM petugas WHERE id_user = ? AND level = 3
                UNION ALL
                SELECT password FROM manager WHERE id_user = ? AND level = 3
            ";
        }
        
        $stmt = $pdo->prepare($verifyQuery);
        if ($user_level == 3) {
            $stmt->execute([$user_id, $user_id]);
        } else {
            $stmt->execute([$user_id]);
        }
        
        $userData = $stmt->fetch();
        if (!$userData || !password_verify($input['current_password'], $userData['password'])) {
            throw new Exception('Password lama salah!');
        }
        
        // Add password to update query
        if ($user_level == 1) {
            $updateQuery = "UPDATE petugas SET nama_user = ?, username = ?, password = ? WHERE id_user = ?";
            $params = [$input['nama_user'], $input['username'], password_hash($input['new_password'], PASSWORD_DEFAULT), $user_id];
        } else if ($user_level == 2) {
            $updateQuery = "UPDATE manager SET nama_user = ?, username = ?, password = ? WHERE id_user = ?";
            $params = [$input['nama_user'], $input['username'], password_hash($input['new_password'], PASSWORD_DEFAULT), $user_id];
        } else {
            $updateQuery = "
                UPDATE petugas SET nama_user = ?, username = ?, password = ? WHERE id_user = ? AND level = 3;
                UPDATE manager SET nama_user = ?, username = ?, password = ? WHERE id_user = ? AND level = 3;
            ";
            $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $params = [
                $input['nama_user'], $input['username'], $hashedPassword, $user_id,
                $input['nama_user'], $input['username'], $hashedPassword, $user_id
            ];
        }
    } else {
        // No password update, add WHERE clause
        if ($user_level == 1) {
            $updateQuery .= " WHERE id_user = ?";
            $params[] = $user_id;
        } else if ($user_level == 2) {
            $updateQuery .= " WHERE id_user = ?";
            $params[] = $user_id;
        }
        // Admin already has WHERE clause in the query
    }
    
    // Execute update
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute($params);
    
    // Update session data
    $_SESSION['nama_user'] = $input['nama_user'];
    $_SESSION['username'] = $input['username'];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Profil berhasil diupdate!',
        'user' => [
            'nama_user' => $input['nama_user'],
            'username' => $input['username']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
