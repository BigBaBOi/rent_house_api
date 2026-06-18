<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');

$stmt = $conn->query("UPDATE Users SET role='SuperAdmin' WHERE user_id='USER_6a33ff46d131f' OR role=''");
echo "Affected rows: " . $stmt->rowCount() . "\n";

$stmt = $conn->query("SELECT user_id, role FROM Users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
