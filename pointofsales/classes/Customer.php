<?php
// Customer Class - Object Oriented Programming
class Customer {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Create new customer
    public function create($data) {
        try {
            $sql = "INSERT INTO customer (nama_customer, alamat, telp, fax, email) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['nama_customer'],
                $data['alamat'],
                $data['telp'],
                $data['fax'],
                $data['email']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Customer berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan customer'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all customers
    public function readAll($search = '', $limit = 10, $offset = 0) {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nama_customer LIKE ? OR email LIKE ? OR telp LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT * FROM customer $whereClause ORDER BY id_customer DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug: Check if result is valid
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
    
    // Read single customer
    public function readById($id) {
        try {
            $sql = "SELECT * FROM customer WHERE id_customer = ?";
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
    
    // Update customer
    public function update($id, $data) {
        try {
            $sql = "UPDATE customer SET 
                    nama_customer = ?,
                    alamat = ?,
                    telp = ?,
                    fax = ?,
                    email = ?
                    WHERE id_customer = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['nama_customer'],
                $data['alamat'],
                $data['telp'],
                $data['fax'],
                $data['email'],
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Customer berhasil diupdate'
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
    
    // Delete customer
    public function delete($id) {
        try {
            // Check if customer has sales
            $checkSql = "SELECT COUNT(*) as count FROM sales WHERE id_customer = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Customer tidak dapat dihapus karena memiliki data penjualan'
                ];
            }
            
            $sql = "DELETE FROM customer WHERE id_customer = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Customer berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Customer tidak ditemukan'
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
                $whereClause = "WHERE nama_customer LIKE ? OR email LIKE ? OR telp LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT COUNT(*) as total FROM customer $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validate customer data
    public function validate($data, $id = null) {
        $errors = [];
        
        if (empty($data['nama_customer'])) {
            $errors[] = 'Nama customer harus diisi';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }
        
        // Check duplicate email
        if (!empty($data['email'])) {
            $sql = "SELECT id_customer FROM customer WHERE email = ?";
            $params = [$data['email']];
            
            if ($id) {
                $sql .= " AND id_customer != ?";
                $params[] = $id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah digunakan';
            }
        }
        
        return $errors;
    }
    
    // Get customer report
    public function getCustomerReport() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, 
                       COUNT(s.id_sales) as total_sales,
                       COALESCE(SUM(t.amount), 0) as total_purchase
                FROM customer c
                LEFT JOIN sales s ON c.id_customer = s.id_customer
                LEFT JOIN transaction t ON s.id_sales = t.id_sales
                GROUP BY c.id_customer
                ORDER BY c.nama_customer ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting customer report: " . $e->getMessage());
            return [];
        }
    }
}
?>
