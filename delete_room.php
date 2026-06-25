<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

include_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->room_id)) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin bắt buộc"]);
    exit;
}

$room_id = $data->room_id;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Kết nối CSDL thất bại"]);
    exit;
}

// Check if room is empty
$stmt = $conn->prepare("SELECT status FROM Rooms WHERE room_id = ?");
$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if ($row['status'] != 'Empty') {
        echo json_encode(["status" => "error", "message" => "Chỉ có thể xóa phòng trống"]);
        $stmt->close();
        $conn->close();
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Không tìm thấy phòng"]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("DELETE FROM Rooms WHERE room_id = ?");
$stmt->bind_param("s", $room_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Xóa phòng thành công"]);
} else {
    echo json_encode(["status" => "error", "message" => "Lỗi: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
