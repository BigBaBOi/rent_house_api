<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
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

$ownerId = $_GET['owner_id'] ?? null;

if (!$ownerId) {
    echo json_encode(["status" => "error", "message" => "Missing owner_id"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("
        SELECT a.alarm_id, a.alarm_type as type, a.status, a.description, a.image_url, a.triggered_at as created_at, 
               COALESCE(r.room_number, 'Không rõ') as room_number, 
               h.hostel_name, u.full_name as tenant_name
        FROM Alarms a
        JOIN Hostels h ON a.hostel_id = h.hostel_id
        LEFT JOIN Rooms r ON a.triggered_by = r.current_tenant_id
        LEFT JOIN Users u ON a.triggered_by = u.user_id
        WHERE h.owner_id = ?
        ORDER BY a.triggered_at DESC
    ");
    $stmt->execute([$ownerId]);
    $alarms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $alarms]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
