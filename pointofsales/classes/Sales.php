<?php
// Sales Class - Object Oriented Programming
class Sales {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Create new sales
    public function create($data) {
        try {
            $sql = "INSERT INTO sales (tgl_sales, id_customer, do_number, status) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['tgl_sales'],
                $data['id_customer'] ?: null,
                $data['do_number'] ?: null,
                $data['status']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Sales berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan sales'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all sales
    public function readAll($search = '', $limit = 10, $offset = 0) {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE s.do_number LIKE ? OR c.nama_customer LIKE ? OR s.status LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT s.*, c.nama_customer, 
                           COALESCE(SUM(t.amount), 0) as total_amount
                    FROM sales s 
                    LEFT JOIN customer c ON s.id_customer = c.id_customer
                    LEFT JOIN transaction t ON s.id_sales = t.id_sales
                    $whereClause
                    GROUP BY s.id_sales
                    ORDER BY s.tgl_sales DESC 
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
    
    // Read single sales
    public function readById($id) {
        try {
            $sql = "SELECT s.*, c.nama_customer 
                    FROM sales s 
                    LEFT JOIN customer c ON s.id_customer = c.id_customer
                    WHERE s.id_sales = ?";
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
    
    // Update sales
    public function update($id, $data) {
        try {
            $sql = "UPDATE sales SET 
                    tgl_sales = ?,
                    id_customer = ?,
                    do_number = ?,
                    status = ?
                    WHERE id_sales = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['tgl_sales'],
                $data['id_customer'] ?: null,
                $data['do_number'] ?: null,
                $data['status'],
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Sales berhasil diupdate'
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
    
    // Delete sales
    public function delete($id) {
        try {
            // Check if sales has transactions
            $checkSql = "SELECT COUNT(*) as count FROM transaction WHERE id_sales = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Sales tidak dapat dihapus karena memiliki data transaksi. Hapus transaksi terlebih dahulu.'
                ];
            }
            
            $sql = "DELETE FROM sales WHERE id_sales = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Sales berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Sales tidak ditemukan'
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
                $whereClause = "WHERE s.do_number LIKE ? OR c.nama_customer LIKE ? OR s.status LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT COUNT(DISTINCT s.id_sales) as total 
                    FROM sales s 
                    LEFT JOIN customer c ON s.id_customer = c.id_customer
                    $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validate sales data
    public function validate($data, $id = null) {
        $errors = [];
        
        if (empty($data['tgl_sales'])) {
            $errors[] = 'Tanggal sales harus diisi';
        } else {
            // Validate date format - more flexible
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $data['tgl_sales']);
            if (!$date) {
                // Try alternative format
                $date = DateTime::createFromFormat('Y-m-d H:i', $data['tgl_sales']);
            }
            if (!$date) {
                $errors[] = 'Format tanggal tidak valid';
            }
        }
        
        if (!in_array($data['status'], ['DRAFT', 'FINAL', 'CANCELED'])) {
            $errors[] = 'Status harus DRAFT, FINAL, atau CANCELED';
        }
        
        // Validate customer exists if provided
        if (!empty($data['id_customer'])) {
            $checkSql = "SELECT COUNT(*) FROM customer WHERE id_customer = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$data['id_customer']]);
            if ($checkStmt->fetchColumn() == 0) {
                $errors[] = 'Customer tidak ditemukan';
            }
        }
        
        // Check duplicate DO number if provided
        if (!empty($data['do_number'])) {
            $sql = "SELECT id_sales FROM sales WHERE do_number = ?";
            $params = [$data['do_number']];
            
            if ($id) {
                $sql .= " AND id_sales != ?";
                $params[] = $id;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $errors[] = 'DO Number sudah digunakan';
            }
        }
        
        return $errors;
    }
    
    // Get customers for dropdown
    public function getCustomers() {
        try {
            $sql = "SELECT id_customer, nama_customer FROM customer ORDER BY nama_customer ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get status options
    public function getStatusOptions() {
        return [
            'DRAFT' => 'Draft',
            'FINAL' => 'Final',
            'CANCELED' => 'Dibatalkan'
        ];
    }
    
    // Generate DO Number
    public function generateDONumber() {
        try {
            $today = date('Ymd');
            $sql = "SELECT COUNT(*) as count FROM sales WHERE DATE(tgl_sales) = CURDATE()";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $sequence = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
            return "DO-$today-$sequence";
            
        } catch (PDOException $e) {
            return "DO-" . date('YmdHis');
        }
    }
    
    // Get sales report
    public function getSalesReport($start_date, $end_date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, c.nama_customer, 
                       COALESCE(SUM(t.amount), 0) as total_sales
                FROM sales s
                LEFT JOIN customer c ON s.id_customer = c.id_customer
                LEFT JOIN transaction t ON s.id_sales = t.id_sales
                WHERE DATE(s.tgl_sales) BETWEEN ? AND ?
                GROUP BY s.id_sales
                ORDER BY s.tgl_sales DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting sales report: " . $e->getMessage());
            return [];
        }
    }
}
?>
