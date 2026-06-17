<?php

require_once __DIR__ . '/../config/Database.php';

abstract class Model {
    protected $conn;
    protected $table;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    protected function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    protected function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    protected function fetchColumn($sql, $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }

    protected function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }

    public function beginTransaction() {
        $this->conn->beginTransaction();
    }

    public function commit() {
        $this->conn->commit();
    }

    public function rollback() {
        $this->conn->rollBack();
    }

    public function inTransaction() {
        return $this->conn->inTransaction();
    }
}
