<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
$stmt = $conn->query("DESCRIBE OwnerVerifications");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $conn->query("SELECT * FROM OwnerVerifications");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
