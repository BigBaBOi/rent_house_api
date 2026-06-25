<?php
require 'c:/xampp/htdocs/rent_house_api/src/Database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("DESCRIBE bills");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
