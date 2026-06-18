<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
$conn->query("UPDATE Users SET role='admin' WHERE role='' OR role IS NULL");
echo 'Updated roles successfully';
?>
