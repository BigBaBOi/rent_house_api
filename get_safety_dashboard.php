<?php
// ====== [FEATURE 9: SAFETY DASHBOARD FOR OWNER] ======
// Hiển thị dashboard an toàn: cảm biến, sự cố, thống kê báo động trong tháng
// Query params: owner_id (bắt buộc)

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
    
    // 1. Get Sensors
    $stmt = $conn->prepare("SELECT * FROM Sensors WHERE owner_id = ?");
    $stmt->execute([$ownerId]);
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Incidents
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
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Stats (Alarms this month)
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN a.alarm_type = 'Fire' THEN 1 ELSE 0 END) as fire_count,
            SUM(CASE WHEN a.alarm_type = 'Door' THEN 1 ELSE 0 END) as door_count
        FROM Alarms a
        JOIN Hostels h ON a.hostel_id = h.hostel_id
        WHERE h.owner_id = ? AND MONTH(a.triggered_at) = MONTH(CURRENT_DATE()) AND YEAR(a.triggered_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$ownerId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $fireCount = $stats['fire_count'] ?? 0;
    $doorCount = $stats['door_count'] ?? 0;

    $systemStatus = "Bình thường";
    foreach ($incidents as $inc) {
        if ($inc['status'] === 'Active') {
            $systemStatus = "Có sự cố";
            break;
        }
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "system_status" => $systemStatus,
            "fire_count" => (int)$fireCount,
            "door_count" => (int)$doorCount,
            "sensors" => $sensors,
            "incidents" => $incidents
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
