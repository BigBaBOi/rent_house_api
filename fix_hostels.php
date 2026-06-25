<?php
require 'src/Database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("UPDATE Hostels SET is_verified = 1 WHERE is_verified = 0 OR is_verified IS NULL");
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " hostels to is_verified = 1.\n";
?>
