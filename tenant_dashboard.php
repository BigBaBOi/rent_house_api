<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tenantId = isset($_GET['tenant_id']) ? $_GET['tenant_id'] : null;
    
    if (!$tenantId) {
        echo json_encode(["status" => "error", "message" => "Missing tenant_id"]);
        exit;
    }
    
    $response = [];
    
    // 1. Lấy thông tin phòng
    $stmtRoom = $conn->prepare("SELECT room_number FROM Rooms WHERE current_tenant_id = :tenant_id LIMIT 1");
    $stmtRoom->execute([':tenant_id' => $tenantId]);
    $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);
    $response['room_number'] = $room ? "Phòng " . $room['room_number'] : "Chưa thuê phòng";
    
    // 2. Lấy thông tin hóa đơn chưa thanh toán
    $response['unpaid_bill'] = null;
    try {
        $stmtBill = $conn->prepare("
            SELECT b.bill_id, b.room_id, r.room_number, h.hostel_name, u.full_name as tenant_name, b.billing_month, b.old_electric, b.electric_index, b.old_water, b.water_index, b.rent_amount, b.service_amount, b.total_amount, b.is_paid, b.created_at, b.paid_at 
            FROM bills b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN hostels h ON r.hostel_id = h.hostel_id
            LEFT JOIN users u ON r.current_tenant_id = u.user_id
            WHERE r.current_tenant_id = :tenant_id
            ORDER BY b.created_at DESC LIMIT 1
        ");
        $stmtBill->execute([':tenant_id' => $tenantId]);
        $bill = $stmtBill->fetch(PDO::FETCH_ASSOC);
        if ($bill) {
            $bill['is_paid'] = (bool)$bill['is_paid'];
            $response['unpaid_bill'] = $bill;
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    // 3. Lấy notifications từ bảng
    $stmtNoti = $conn->prepare("
        SELECT title, content, created_at as time
        FROM notifications
        WHERE hostel_id = (
            SELECT hostel_id FROM Rooms WHERE current_tenant_id = :tenant_id LIMIT 1
        )
        ORDER BY created_at DESC LIMIT 5
    ");
    $stmtNoti->execute([':tenant_id' => $tenantId]);
    $notifications = $stmtNoti->fetchAll(PDO::FETCH_ASSOC);
    $response['notifications'] = $notifications;
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
