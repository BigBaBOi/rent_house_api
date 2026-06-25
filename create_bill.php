<?php
// ====== [FEATURE 6: BILL MANAGEMENT - CREATE BILL] ======
// Tạo hóa đơn thanh toán cho phòng
// Yêu cầu: room_id, electric_index, water_index, total_amount, billing_month (optional)

header("Access-Control-Allow-Origin: *");
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

$roomId = $data['room_id'] ?? null;
$billingMonth = $data['billing_month'] ?? date('m/Y');
$oldElectric = $data['old_electric'] ?? 0;
$electricIndex = $data['electric_index'] ?? null;
$oldWater = $data['old_water'] ?? 0;
$waterIndex = $data['water_index'] ?? null;
$rentAmount = $data['rent_amount'] ?? 0;
$serviceAmount = $data['service_amount'] ?? 0;
$wifiFee = $data['wifi_fee'] ?? 0;
$parkingFee = $data['parking_fee'] ?? 0;
$trashFee = $data['trash_fee'] ?? 0;
$dueDate = $data['due_date'] ?? null;
$totalAmount = $data['total_amount'] ?? null;

if (!$roomId || $electricIndex === null || $waterIndex === null || $totalAmount === null) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("INSERT INTO bills (room_id, billing_month, old_electric, electric_index, old_water, water_index, rent_amount, service_amount, wifi_fee, parking_fee, trash_fee, due_date, total_amount, is_paid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$roomId, $billingMonth, $oldElectric, $electricIndex, $oldWater, $waterIndex, $rentAmount, $serviceAmount, $wifiFee, $parkingFee, $trashFee, $dueDate, $totalAmount]);
    
    echo json_encode(["status" => "success", "message" => "Bill created successfully"]);
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
