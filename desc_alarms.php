<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $stmt = $conn->query("DESCRIBE Alarms");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
