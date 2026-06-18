<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

$sensorId = "sensor_" . uniqid();
$ownerId = $data['owner_id'] ?? null;
$sensorName = $data['sensor_name'] ?? null;
$sensorType = $data['sensor_type'] ?? null;
$valueText = $data['value_text'] ?? null;
$status = $data['status'] ?? null;
$battery = $data['battery'] ?? "Pin: 100%";

if (!$ownerId || !$sensorName || !$sensorType || !$valueText || !$status) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("INSERT INTO Sensors (sensor_id, owner_id, sensor_name, sensor_type, value_text, status, battery) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sensorId, $ownerId, $sensorName, $sensorType, $valueText, $status, $battery]);

    echo json_encode(["status" => "success", "message" => "Sensor added successfully"]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
