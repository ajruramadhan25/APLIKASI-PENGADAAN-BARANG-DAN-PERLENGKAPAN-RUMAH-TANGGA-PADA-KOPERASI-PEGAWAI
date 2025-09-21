<?php
// Item Class - Object Oriented Programming
class Item {
    private $pdo;
    
    public function __construct($database) {
        $this->pdo = $database;
    }
    
    // Create new item
    public function create($data) {
        try {
            $sql = "INSERT INTO item (nama_item, uom, harga_beli, harga_jual) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['nama_item'],
                $data['uom'],
                $data['harga_beli'],
                $data['harga_jual']
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Item berhasil ditambahkan',
                    'id' => $this->pdo->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menambahkan item'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Read all items
    public function readAll($search = '', $limit = 10, $offset = 0) {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nama_item LIKE ? OR uom LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT * FROM item $whereClause ORDER BY id_item DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
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
    
    // Read single item
    public function readById($id) {
        try {
            $sql = "SELECT * FROM item WHERE id_item = ?";
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
    
    // Update item
    public function update($id, $data) {
        try {
            $sql = "UPDATE item SET 
                    nama_item = ?,
                    uom = ?,
                    harga_beli = ?,
                    harga_jual = ?
                    WHERE id_item = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['nama_item'],
                $data['uom'],
                $data['harga_beli'],
                $data['harga_jual'],
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Item berhasil diupdate'
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
    
    // Delete item
    public function delete($id) {
        try {
            // Check if item has transactions
            $checkSql = "SELECT COUNT(*) as count FROM transaction WHERE id_item = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Item tidak dapat dihapus karena memiliki data transaksi'
                ];
            }
            
            $sql = "DELETE FROM item WHERE id_item = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Item berhasil dihapus'
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
    
    // Get total count
    public function getTotalCount($search = '') {
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE nama_item LIKE ? OR uom LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql = "SELECT COUNT(*) as total FROM item $whereClause";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    // Validate item data
    public function validate($data, $id = null) {
        $errors = [];
        
        if (empty($data['nama_item'])) {
            $errors[] = 'Nama item harus diisi';
        }
        
        if (empty($data['uom'])) {
            $errors[] = 'Satuan (UOM) harus diisi';
        }
        
        if (!is_numeric($data['harga_beli']) || $data['harga_beli'] < 0) {
            $errors[] = 'Harga beli harus berupa angka positif';
        }
        
        if (!is_numeric($data['harga_jual']) || $data['harga_jual'] < 0) {
            $errors[] = 'Harga jual harus berupa angka positif';
        }
        
        if (is_numeric($data['harga_jual']) && is_numeric($data['harga_beli']) && $data['harga_jual'] < $data['harga_beli']) {
            $errors[] = 'Harga jual tidak boleh lebih kecil dari harga beli';
        }
        
        return $errors;
    }
    
    // Get UOM options
    public function getUOMOptions() {
        return [
            'kg' => 'Kilogram (kg)',
            'liter' => 'Liter (l)',
            'pcs' => 'Pieces (pcs)',
            'box' => 'Box',
            'pack' => 'Pack',
            'botol' => 'Botol',
            'kaleng' => 'Kaleng',
            'bungkus' => 'Bungkus'
        ];
    }
    
    // Get stock report
    public function getStockReport() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, 
                       COALESCE(SUM(t.quantity), 0) as total_sold,
                       (100 - COALESCE(SUM(t.quantity), 0)) as remaining_stock
                FROM item i
                LEFT JOIN transaction t ON i.id_item = t.id_item
                GROUP BY i.id_item
                ORDER BY i.nama_item ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting stock report: " . $e->getMessage());
            return [];
        }
    }
}
?>
