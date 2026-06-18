<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

$roomId = $_GET['room_id'] ?? null;

if (!$roomId) {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the latest bill for this room to get the old indices
    $stmt = $conn->prepare("SELECT electric_index, water_index FROM bills WHERE room_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$roomId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode(["status" => "success", "data" => [
            "old_electric" => (double)$row['electric_index'],
            "old_water" => (double)$row['water_index']
        ]]);
    } else {
        // If no previous bill, return 0
        echo json_encode(["status" => "success", "data" => [
            "old_electric" => 0,
            "old_water" => 0
        ]]);
    }
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
