<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $requestId = isset($data['request_id']) ? $data['request_id'] : null;
    $status = isset($data['status']) ? $data['status'] : null;
    
    if (!$requestId || !$status) {
        echo json_encode(["status" => "error", "message" => "Missing request_id or status"]);
        exit;
    }
    
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // 1. Cập nhật status của request
    $stmt = $conn->prepare("UPDATE JoinRequests SET status = :status WHERE request_id = :request_id");
    $stmt->execute([':status' => $status, ':request_id' => $requestId]);
    
    if ($stmt->rowCount() > 0 && $status === 'Accepted') {
        // 2. Lấy tenant_id và room_id
        $stmt = $conn->prepare("SELECT tenant_id, room_id FROM JoinRequests WHERE request_id = ?");
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['room_id']) {
            // 3. Cập nhật bảng Rooms
            $stmt = $conn->prepare("UPDATE Rooms SET status = 'Occupied', current_tenant_id = ? WHERE room_id = ?");
            $stmt->execute([$row['tenant_id'], $row['room_id']]);
        }
    }
    
    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Request updated to " . $status]);
    
} catch(PDOException $e) {
    if (isset($conn)) $conn->rollBack();
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
