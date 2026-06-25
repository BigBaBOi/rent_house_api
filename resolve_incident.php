<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['room_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
    exit;
}

$roomId = $data['room_id'];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tìm người thuê hiện tại của phòng
    $stmtRoom = $conn->prepare("SELECT current_tenant_id FROM Rooms WHERE room_id = ? LIMIT 1");
    $stmtRoom->execute([$roomId]);
    $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);
    
    if (!$room || !$room['current_tenant_id']) {
        echo json_encode(["status" => "error", "message" => "Phòng không có người thuê"]);
        exit;
    }
    
    $tenantId = $room['current_tenant_id'];
    
    // Cập nhật trạng thái các alarm của tenant này thành 'Resolved'
    $stmtUpdate = $conn->prepare("UPDATE alarms SET status = 'Resolved', resolved_at = CURRENT_TIMESTAMP WHERE triggered_by = ? AND status = 'Active'");
    $stmtUpdate->execute([$tenantId]);
    
    if ($stmtUpdate->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Đã hoàn thành sự cố!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Không có sự cố nào đang active cho phòng này"]);
    }
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi database: " . $e->getMessage()]);
}
?>
