<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
$stmt = $conn->query('SELECT user_id, role FROM Users');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
