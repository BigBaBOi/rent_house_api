<?php
// ====== [FEATURE 8: INCIDENT REPORTING SYSTEM] ======
// Tenant báo cáo sự cố hoặc khẩn cấp (Fire, Police, Medical)
// Yêu cầu: tenant_id, description (mô tả sự cố)

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

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$tenantId = $data['tenant_id'] ?? null;
$description = $data['description'] ?? '';

if (!$tenantId || !$description) {
    echo json_encode(["status" => "error", "message" => "Missing tenant_id or description"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find room_id and hostel_id for this tenant
    $stmt = $conn->prepare("SELECT room_id, hostel_id FROM Rooms WHERE current_tenant_id = ? LIMIT 1");
    $stmt->execute([$tenantId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo json_encode(["status" => "error", "message" => "Tenant is not in any room"]);
        exit;
    }
    
    // Insert alarm
    $stmt = $conn->prepare("INSERT INTO Alarms (hostel_id, triggered_by, alarm_type, status, description) VALUES (?, ?, 'Fire', 'Active', ?)");
    $stmt->execute([$room['hostel_id'], $tenantId, $description]);
    
    echo json_encode(["status" => "success", "message" => "Incident reported"]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
