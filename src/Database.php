<?php
class Database {
    private $servername;
    private $username;
    private $password;
    private $dbname;
    private $conn = null;

    public function __construct(
        $servername = 'localhost',
        $username = 'root',
        $password = '',
        $dbname = 'rent_house'
    ) {
        $this->servername = $servername;
        $this->username = $username;
        $this->password = $password;
        $this->dbname = $dbname;
    }

    public function getConnection() {
        if ($this->conn === null) {
            $this->conn = new PDO(
                "mysql:host={$this->servername};dbname={$this->dbname};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->conn;
    }
}
