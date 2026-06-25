<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

include_once 'config.php';

$roomId = $_GET['room_id'] ?? null;

if (!$roomId) {
    echo json_encode(["status" => "error", "message" => "Thiếu room_id"]);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Kết nối CSDL thất bại"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT a.description 
    FROM Alarms a
    JOIN Rooms r ON a.triggered_by = r.current_tenant_id
    WHERE r.room_id = ? AND a.status = 'Pending'
    ORDER BY a.triggered_at DESC LIMIT 1
");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Lỗi SQL: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $roomId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["status" => "success", "description" => $row['description']]);
} else {
    echo json_encode(["status" => "error", "message" => "Không có sự cố"]);
}

$stmt->close();
$conn->close();
?>
