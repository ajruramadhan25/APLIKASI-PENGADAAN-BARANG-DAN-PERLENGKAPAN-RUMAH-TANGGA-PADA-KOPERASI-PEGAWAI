<?php
// Petugas Class - Object Oriented Programming
class Petugas {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Create new petugas
    public function create($data) {
        try {
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO petugas (nama_user, username, password, level) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['nama_user'],
                $data['username'],
                $hashedPassword,
                $data['level']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Petugas berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan petugas'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all petugas
    public function readAll($search = '', $limit = 10, $offset = 0) {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE p.nama_user LIKE ? OR p.username LIKE ? OR l.level LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT p.*, l.level as role_name 
                    FROM petugas p 
                    LEFT JOIN level l ON p.level = l.id_level 
                    $whereClause
                    ORDER BY p.id_user DESC 
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                return [
                    'error' => true,
                    'message' => 'Database query failed'
                ];
            }
            
            return $result;
            
        } catch (PDOException $e) {
            return [
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read single petugas
    public function readById($id) {
        try {
            $sql = "SELECT p.*, l.level as role_name 
                    FROM petugas p 
                    LEFT JOIN level l ON p.level = l.id_level 
                    WHERE p.id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Update petugas
    public function update($id, $data) {
        try {
            $sql = "UPDATE petugas SET 
                    nama_user = ?,
                    username = ?,
                    level = ?";
            
            $params = [
                $data['nama_user'],
                $data['username'],
                $data['level']
            ];
            
            // Only update password if provided
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id_user = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Petugas berhasil diupdate'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data yang diupdate'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Delete petugas
    public function delete($id) {
        try {
            // Check if petugas is trying to delete themselves
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                return [
                    'success' => false,
                    'message' => 'Tidak dapat menghapus akun sendiri'
                ];
            }
            
            // Check if petugas has any sales records
            $checkSql = "SELECT COUNT(*) as count FROM sales WHERE id_user = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Petugas tidak dapat dihapus karena memiliki data sales'
                ];
            }
            
            $sql = "DELETE FROM petugas WHERE id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Petugas berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Petugas tidak ditemukan'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get total count
    public function getTotalCount($search = '') {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE p.nama_user LIKE ? OR p.username LIKE ? OR l.level LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT COUNT(*) as total 
                    FROM petugas p 
                    LEFT JOIN level l ON p.level = l.id_level 
                    $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validate petugas data
    public function validate($data, $id = null) {
        $errors = [];
        
        if (empty($data['nama_user'])) {
            $errors[] = 'Nama user harus diisi';
        }
        
        if (empty($data['username'])) {
            $errors[] = 'Username harus diisi';
        } else {
            // Check username uniqueness
            $sql = "SELECT id_user FROM petugas WHERE username = ?";
            $params = [$data['username']];
            
            if ($id) {
                $sql .= " AND id_user != ?";
                $params[] = $id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan';
            }
        }
        
        if (empty($data['password']) && !$id) {
            $errors[] = 'Password harus diisi';
        }
        
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        }
        
        if (empty($data['level']) || !is_numeric($data['level'])) {
            $errors[] = 'Level harus dipilih';
        } else {
            // Validate level exists
            $checkSql = "SELECT COUNT(*) FROM level WHERE id_level = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$data['level']]);
            if ($checkStmt->fetchColumn() == 0) {
                $errors[] = 'Level tidak valid';
            }
        }
        
        return $errors;
    }
    
    // Get levels for dropdown
    public function getLevels() {
        try {
            $sql = "SELECT id_level, level FROM level ORDER BY id_level ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Change password
    public function changePassword($id, $oldPassword, $newPassword) {
        try {
            // Get current password
            $sql = "SELECT password FROM petugas WHERE id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ];
            }
            
            // Verify old password
            if (!password_verify($oldPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Password lama tidak sesuai'
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE petugas SET password = ? WHERE id_user = ?";
            $updateStmt = $this->pdo->prepare($updateSql);
            $result = $updateStmt->execute([$hashedPassword, $id]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Password berhasil diubah'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal mengubah password'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get user statistics
    public function getUserStats($id) {
        try {
            $stats = [];
            
            // Count sales by this user
            $sql = "SELECT COUNT(*) as total_sales FROM sales WHERE id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_sales'] = $result['total_sales'];
            
            // Count transactions by this user
            $sql = "SELECT COUNT(*) as total_transactions 
                    FROM transaction t 
                    JOIN sales s ON t.id_sales = s.id_sales 
                    WHERE s.id_user = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_transactions'] = $result['total_transactions'];
            
            // Get last login (from login_logs if available)
            $sql = "SELECT login_time FROM login_logs 
                    WHERE username = (SELECT username FROM petugas WHERE id_user = ?) 
                    AND success = 1 
                    ORDER BY login_time DESC LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['last_login'] = $result['login_time'] ?? null;
            
            return $stats;
            
        } catch (PDOException $e) {
            return [
                'total_sales' => 0,
                'total_transactions' => 0,
                'last_login' => null
            ];
        }
    }
}
?>
