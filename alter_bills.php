<?php
require 'c:/xampp/htdocs/rent_house_api/src/Database.php';
$db = new Database();
$conn = $db->getConnection();
try {
    $conn->exec("ALTER TABLE bills ADD wifi_fee DECIMAL(10,2) DEFAULT 0");
    $conn->exec("ALTER TABLE bills ADD parking_fee DECIMAL(10,2) DEFAULT 0");
    $conn->exec("ALTER TABLE bills ADD trash_fee DECIMAL(10,2) DEFAULT 0");
    $conn->exec("ALTER TABLE bills ADD due_date DATE DEFAULT NULL");
    echo "Columns added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
