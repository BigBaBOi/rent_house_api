<?php
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

$owner_id = $_GET['owner_id'] ?? null;
if (!$owner_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing owner_id']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT b.bill_id, b.room_id, r.room_number, h.hostel_name, u.full_name as tenant_name, b.billing_month, b.old_electric, b.electric_index, b.old_water, b.water_index, b.rent_amount, b.service_amount, b.total_amount, b.is_paid, b.created_at, b.paid_at 
            FROM bills b
            JOIN rooms r ON b.room_id = r.room_id
            JOIN hostels h ON r.hostel_id = h.hostel_id
            LEFT JOIN users u ON r.current_tenant_id = u.user_id
            WHERE h.owner_id = ?
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$owner_id]);
    $bills = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($row['is_paid'])) {
            $row['is_paid'] = (bool)$row['is_paid'];
        }
        $bills[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $bills], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
