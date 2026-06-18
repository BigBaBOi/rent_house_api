<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');

$stmt = $conn->query("UPDATE Users SET verification_status='Pending' WHERE verification_status=''");
echo "Updated Users verification_status: " . $stmt->rowCount() . "\n";

$stmt = $conn->query("UPDATE OwnerVerifications SET status='Pending' WHERE status=''");
echo "Updated OwnerVerifications status: " . $stmt->rowCount() . "\n";

?>
