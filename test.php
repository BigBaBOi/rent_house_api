<?php
require 'src/Database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM Rooms WHERE room_id = 'r_6a3b383d6ad20'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
