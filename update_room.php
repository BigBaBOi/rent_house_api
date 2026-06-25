<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

include_once 'config.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->room_id) || !isset($data->room_number) || !isset($data->price)) {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin bắt buộc"]);
    exit;
}

$room_id = $data->room_id;
$room_number = $data->room_number;
$price = $data->price;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Kết nối CSDL thất bại"]);
    exit;
}

$stmt = $conn->prepare("UPDATE Rooms SET room_number = ?, price = ? WHERE room_id = ?");
$stmt->bind_param("sds", $room_number, $price, $room_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Cập nhật phòng thành công"]);
} else {
    echo json_encode(["status" => "error", "message" => "Lỗi: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
