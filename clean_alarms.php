<?php
require 'src/Database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("UPDATE GlobalAlarms SET status = 'RESOLVED'");
$stmt->execute();

echo "Updated " . $stmt->rowCount() . " alarms to RESOLVED.\n";
?>
