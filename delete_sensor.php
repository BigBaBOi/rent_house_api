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

$sensorId = $data['sensor_id'] ?? null;
$ownerId = $data['owner_id'] ?? null;

if (!$sensorId || !$ownerId) {
    echo json_encode(["status" => "error", "message" => "Missing sensor_id or owner_id"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check ownership
    $check = $conn->prepare("SELECT * FROM Sensors WHERE sensor_id = ? AND owner_id = ?");
    $check->execute([$sensorId, $ownerId]);
    if ($check->rowCount() == 0) {
        echo json_encode(["status" => "error", "message" => "Sensor not found or access denied"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM Sensors WHERE sensor_id = ? AND owner_id = ?");
    $stmt->execute([$sensorId, $ownerId]);

    echo json_encode(["status" => "success", "message" => "Sensor deleted successfully"]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
