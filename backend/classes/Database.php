<?php
/**
 * 資料庫操作類別
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private $pdo;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->pdo = new PDO(DB_DSN, DB_USER, DB_PASS, PDO_OPTIONS);
        } catch (PDOException $e) {
            error_log("資料庫連線失敗: " . $e->getMessage());
            throw new Exception("資料庫連線失敗");
        }
    }
    
    /**
     * 取得資料庫實例 (Singleton Pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 取得PDO連線
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * 執行查詢
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("查詢執行失敗: " . $e->getMessage());
            throw new Exception("查詢執行失敗");
        }
    }
    
    /**
     * 插入資料
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("插入資料失敗: " . $e->getMessage());
            throw new Exception("插入資料失敗");
        }
    }
    
    /**
     * 更新資料
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $params = array_merge($data, $whereParams);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("更新資料失敗: " . $e->getMessage());
            throw new Exception("更新資料失敗");
        }
    }
    
    /**
     * 刪除資料
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("刪除資料失敗: " . $e->getMessage());
            throw new Exception("刪除資料失敗");
        }
    }
    
    /**
     * 取得單一記錄
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * 取得多筆記錄
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * 取得記錄數量
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * 開始交易
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * 提交交易
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * 回滾交易
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * 檢查資料表是否存在
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $result = $this->fetchOne($sql, ['table' => $table]);
        return !empty($result);
    }
    
    /**
     * 產生UUID
     */
    public function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>