<?php
// Transaction Class - Object Oriented Programming
class Transaction {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Create new transaction
    public function create($data) {
        try {
            $sql = "INSERT INTO transaction (id_sales, id_item, quantity, price, amount) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['id_sales'],
                $data['id_item'],
                $data['quantity'],
                $data['price'],
                $data['amount']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Transaction berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan transaction'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Create transaction temp (draft)
    public function createTemp($data) {
        try {
            $sql = "INSERT INTO transaction_temp (id_item, quantity, price, amount, session_id, remark) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['id_item'],
                $data['quantity'],
                $data['price'],
                $data['amount'],
                $data['session_id'],
                $data['remark'] ?? null
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Item berhasil ditambahkan ke keranjang',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan item ke keranjang'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all transactions by sales ID
    public function readBySalesId($salesId) {
        try {
            $sql = "SELECT t.*, i.nama_item, i.uom 
                    FROM transaction t 
                    LEFT JOIN item i ON t.id_item = i.id_item 
                    WHERE t.id_sales = ? 
                    ORDER BY t.id_transaction ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$salesId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $result;
            
        } catch (PDOException $e) {
            return [
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all transactions with search and pagination
    public function readAll($search = '', $limit = 10, $offset = 0) {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE s.do_number LIKE ? OR i.nama_item LIKE ? OR c.nama_customer LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT t.*, s.do_number, s.tgl_sales, s.status as sales_status,
                           i.nama_item, i.uom, c.nama_customer
                    FROM transaction t 
                    LEFT JOIN sales s ON t.id_sales = s.id_sales
                    LEFT JOIN item i ON t.id_item = i.id_item
                    LEFT JOIN customer c ON s.id_customer = c.id_customer
                    $whereClause
                    ORDER BY t.id_transaction DESC 
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
    
    // Read transaction temp by session
    public function readTempBySession($sessionId) {
        try {
            $sql = "SELECT tt.*, i.nama_item, i.uom, i.harga_jual
                    FROM transaction_temp tt 
                    LEFT JOIN item i ON tt.id_item = i.id_item 
                    WHERE tt.session_id = ? 
                    ORDER BY tt.id_transaction ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $result;
            
        } catch (PDOException $e) {
            return [
                'error' => true,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read single transaction
    public function readById($id) {
        try {
            $sql = "SELECT t.*, s.do_number, i.nama_item, i.uom, c.nama_customer
                    FROM transaction t 
                    LEFT JOIN sales s ON t.id_sales = s.id_sales
                    LEFT JOIN item i ON t.id_item = i.id_item
                    LEFT JOIN customer c ON s.id_customer = c.id_customer
                    WHERE t.id_transaction = ?";
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
    
    // Update transaction
    public function update($id, $data) {
        try {
            $sql = "UPDATE transaction SET 
                    id_sales = ?,
                    id_item = ?,
                    quantity = ?,
                    price = ?,
                    amount = ?
                    WHERE id_transaction = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['id_sales'],
                $data['id_item'],
                $data['quantity'],
                $data['price'],
                $data['amount'],
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Transaction berhasil diupdate'
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
    
    // Update transaction temp
    public function updateTemp($id, $data) {
        try {
            $sql = "UPDATE transaction_temp SET 
                    id_item = ?,
                    quantity = ?,
                    price = ?,
                    amount = ?,
                    remark = ?
                    WHERE id_transaction = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['id_item'],
                $data['quantity'],
                $data['price'],
                $data['amount'],
                $data['remark'] ?? null,
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Item keranjang berhasil diupdate'
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
    
    // Delete transaction
    public function delete($id) {
        try {
            $sql = "DELETE FROM transaction WHERE id_transaction = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Transaction berhasil dihapus'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Transaction tidak ditemukan'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Delete transaction temp
    public function deleteTemp($id) {
        try {
            $sql = "DELETE FROM transaction_temp WHERE id_transaction = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Item berhasil dihapus dari keranjang'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Item tidak ditemukan'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Clear all temp transactions by session
    public function clearTempBySession($sessionId) {
        try {
            $sql = "DELETE FROM transaction_temp WHERE session_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$sessionId]);
            
            return [
                'success' => true,
                'message' => 'Keranjang berhasil dikosongkan'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Move temp to final transaction
    public function moveTempToFinal($sessionId, $salesId) {
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Get temp transactions
            $tempTransactions = $this->readTempBySession($sessionId);
            
            if (empty($tempTransactions)) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Tidak ada item di keranjang'
                ];
            }
            
            // Move each temp transaction to final
            foreach ($tempTransactions as $temp) {
                $sql = "INSERT INTO transaction (id_sales, id_item, quantity, price, amount) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $salesId,
                    $temp['id_item'],
                    $temp['quantity'],
                    $temp['price'],
                    $temp['amount']
                ]);
            }
            
            // Clear temp transactions
            $this->clearTempBySession($sessionId);
            
            // Commit transaction
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Transaksi berhasil disimpan'
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
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
                $whereClause = "WHERE s.do_number LIKE ? OR i.nama_item LIKE ? OR c.nama_customer LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT COUNT(*) as total 
                    FROM transaction t 
                    LEFT JOIN sales s ON t.id_sales = s.id_sales
                    LEFT JOIN item i ON t.id_item = i.id_item
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
    
    // Get total amount by sales ID
    public function getTotalAmountBySalesId($salesId) {
        try {
            $sql = "SELECT SUM(amount) as total FROM transaction WHERE id_sales = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$salesId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Get total amount by session (temp)
    public function getTotalAmountBySession($sessionId) {
        try {
            $sql = "SELECT SUM(amount) as total FROM transaction_temp WHERE session_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validate transaction data
    public function validate($data, $id = null) {
        $errors = [];
        
        if (empty($data['id_sales'])) {
            $errors[] = 'Sales ID harus diisi';
        }
        
        if (empty($data['id_item'])) {
            $errors[] = 'Item harus dipilih';
        }
        
        if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = 'Quantity harus berupa angka positif';
        }
        
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Harga harus berupa angka positif';
        }
        
        if (!is_numeric($data['amount']) || $data['amount'] < 0) {
            $errors[] = 'Amount harus berupa angka positif';
        }
        
        // Validate sales exists
        if (!empty($data['id_sales'])) {
            $checkSql = "SELECT COUNT(*) FROM sales WHERE id_sales = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$data['id_sales']]);
            if ($checkStmt->fetchColumn() == 0) {
                $errors[] = 'Sales tidak ditemukan';
            }
        }
        
        // Validate item exists
        if (!empty($data['id_item'])) {
            $checkSql = "SELECT COUNT(*) FROM item WHERE id_item = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$data['id_item']]);
            if ($checkStmt->fetchColumn() == 0) {
                $errors[] = 'Item tidak ditemukan';
            }
        }
        
        return $errors;
    }
    
    // Get items for dropdown
    public function getItems() {
        try {
            $sql = "SELECT id_item, nama_item, uom, harga_jual FROM item ORDER BY nama_item ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get sales for dropdown
    public function getSales() {
        try {
            $sql = "SELECT s.id_sales, s.do_number, s.tgl_sales, c.nama_customer 
                    FROM sales s 
                    LEFT JOIN customer c ON s.id_customer = c.id_customer 
                    ORDER BY s.tgl_sales DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Get transaction report
    public function getTransactionReport($start_date, $end_date) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.*, i.nama_item, s.tgl_sales, s.do_number, c.nama_customer,
                       t.amount as total
                FROM transaction t
                LEFT JOIN item i ON t.id_item = i.id_item
                LEFT JOIN sales s ON t.id_sales = s.id_sales
                LEFT JOIN customer c ON s.id_customer = c.id_customer
                WHERE DATE(s.tgl_sales) BETWEEN ? AND ?
                ORDER BY s.tgl_sales DESC, t.id_transaction ASC
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting transaction report: " . $e->getMessage());
            return [];
        }
    }
}
?>
