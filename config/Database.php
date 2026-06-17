<?php

class Database {
    private $conn;
    private static $instance = null;

    private function __construct() {
        $servername = "localhost";
        $username   = "root";
        $password   = "";
        $dbname     = "rent_house";

        try {
            $this->conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function __clone() {}

    public function __wakeup() {}
}
