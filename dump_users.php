<?php
require 'src/Database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query('SELECT email, role FROM users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
