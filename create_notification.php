<?php
// ====== [FEATURE 7: NOTIFICATION SYSTEM] ======
// Tạo thông báo cho hostel (broadcast cho tất cả tenant)
// Type: 'remind_bill' (nhắc nhở thanh toán) hoặc 'call_tenants' (thông báo chung)

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

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $type = $data['type'] ?? ''; // 'remind_bill' or 'call_tenants'
    
    if ($type === 'remind_bill') {
        $roomId = $data['room_id'] ?? '';
        $month = $data['month'] ?? '';
        
        // Lấy hostel_id từ room_id
        $stmt = $conn->prepare("SELECT hostel_id, room_number FROM Rooms WHERE room_id = ? LIMIT 1");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            $hostelId = $room['hostel_id'];
            $roomNumber = $room['room_number'];
            $title = "Nhắc nhở thanh toán";
            $content = "Phòng $roomNumber chưa thanh toán hóa đơn tháng $month. Vui lòng thanh toán sớm.";
            
            $stmtIns = $conn->prepare("INSERT INTO notifications (notification_id, hostel_id, title, content) VALUES (UUID(), ?, ?, ?)");
            $stmtIns->execute([$hostelId, $title, $content]);
            
            echo json_encode(["status" => "success", "message" => "Đã gửi thông báo nhắc nợ"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Không tìm thấy phòng"]);
        }
        
    } else if ($type === 'call_tenants') {
        $ownerId = $data['owner_id'] ?? '';
        
        // Lấy tất cả hostel_id của owner này
        $stmt = $conn->prepare("SELECT hostel_id FROM Hostels WHERE owner_id = ?");
        $stmt->execute([$ownerId]);
        $hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($hostels) > 0) {
            $title = "Thông báo từ Quản lý";
            $content = "Chú ý: Quản lý khu trọ đang gọi toàn bộ người thuê. Vui lòng kiểm tra ứng dụng hoặc tập trung.";
            
            foreach ($hostels as $h) {
                $stmtIns = $conn->prepare("INSERT INTO notifications (notification_id, hostel_id, title, content) VALUES (UUID(), ?, ?, ?)");
                $stmtIns->execute([$h['hostel_id'], $title, $content]);
            }
            echo json_encode(["status" => "success", "message" => "Đã gửi thông báo tới toàn bộ khu trọ"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Bạn chưa có khu trọ nào"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Type không hợp lệ"]);
    }
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Lỗi database: " . $e->getMessage()]);
}
?>
