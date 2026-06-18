<?php
// Bật hiển thị lỗi để dễ debug dưới local
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

$owner_id = $_GET['owner_id'] ?? null;
$filter_hostel_id = $_GET['hostel_id'] ?? null;
if (!$owner_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing owner_id']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy thông tin các khu trọ của owner này
    $stmt = $conn->prepare("SELECT hostel_id, hostel_name, address FROM Hostels WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $hostelsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($hostelsDb)) {
        // Trả về dữ liệu rỗng nếu chưa có nhà trọ
        echo json_encode([
            'hostels' => [],
            'total_revenue' => 0,
            'stats' => ['empty' => 0, 'occupied' => 0, 'issues' => 0],
            'rooms' => []
        ]);
        exit;
    }

    $hostel_ids = array_column($hostelsDb, 'hostel_id');
    
    // Nếu có filter theo hostel_id và hostel_id đó thuộc về owner này
    if ($filter_hostel_id && in_array($filter_hostel_id, $hostel_ids)) {
        $hostel_ids = [$filter_hostel_id];
    }
    
    $hostel_ids_str = "'" . implode("','", $hostel_ids) . "'";

    // Tính tổng doanh thu (các bill đã thanh toán)
    $sqlRev = "SELECT COALESCE(SUM(b.total_amount), 0) as revenue 
               FROM bills b
               JOIN rooms r ON b.room_id = r.room_id
               WHERE r.hostel_id IN ($hostel_ids_str) AND b.is_paid = 1";
    $stmt = $conn->query($sqlRev);
    $totalRevenue = (double) $stmt->fetchColumn();

    // Lấy danh sách phòng
    $sqlRooms = "SELECT room_id, room_number, status FROM rooms WHERE hostel_id IN ($hostel_ids_str)";
    $stmt = $conn->query($sqlRooms);
    $roomsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh sách phòng đang nợ tiền
    $sqlDebt = "SELECT DISTINCT room_id FROM bills WHERE is_paid = 0";
    $stmt = $conn->query($sqlDebt);
    $debtRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Lấy danh sách phòng có sự cố (dựa vào bảng alarms)
    $sqlIssues = "SELECT DISTINCT r.room_id FROM alarms a 
                  JOIN rooms r ON a.triggered_by = r.current_tenant_id
                  WHERE a.status = 'Active'";
    $stmt = $conn->query($sqlIssues);
    $issueRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stats = ['empty' => 0, 'occupied' => 0, 'issues' => 0];
    $roomItems = [];

    foreach ($roomsDb as $r) {
        $uiStatus = '';
        if ($r['status'] === 'Available') {
            $uiStatus = 'Trống';
            $stats['empty']++;
        } else {
            // Thứ tự ưu tiên: Sự cố -> Nợ tiền -> Đã thuê
            if (in_array($r['room_id'], $issueRooms)) {
                $uiStatus = 'Sự cố';
                $stats['issues']++;
            } elseif (in_array($r['room_id'], $debtRooms)) {
                $uiStatus = 'Nợ tiền';
                $stats['occupied']++; 
            } else {
                $uiStatus = 'Đã thuê';
                $stats['occupied']++;
            }
        }

        $roomItems[] = [
            'room_id' => $r['room_id'],
            'room_number' => $r['room_number'],
            'ui_status' => $uiStatus
        ];
    }

    // Sắp xếp phòng theo tên (room_number) để hiển thị đẹp hơn
    usort($roomItems, function($a, $b) {
        return strcmp($a['room_number'], $b['room_number']);
    });

    echo json_encode([
        'hostels' => $hostelsDb,
        'total_revenue' => $totalRevenue,
        'stats' => $stats,
        'rooms' => $roomItems
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi database: ' . $e->getMessage()]);
}
?>
