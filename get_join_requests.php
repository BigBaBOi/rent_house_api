<?php
ini_set('display_errors', 0);
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
    
    $ownerId = isset($_GET['owner_id']) ? $_GET['owner_id'] : null;
    
    if (!$ownerId) {
        echo json_encode(["status" => "error", "message" => "Missing owner_id"]);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT jr.request_id, jr.tenant_id, jr.hostel_id, jr.room_id, jr.status, jr.requested_at, 
               u.full_name, u.phone_number, r.room_number 
        FROM JoinRequests jr
        JOIN Users u ON jr.tenant_id = u.user_id
        JOIN Rooms r ON jr.room_id = r.room_id
        JOIN Hostels h ON jr.hostel_id = h.hostel_id
        WHERE h.owner_id = :owner_id AND jr.status = 'Pending'
        ORDER BY jr.requested_at DESC
    ");
    $stmt->execute([':owner_id' => $ownerId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $mappedRequests = [];
    foreach ($requests as $r) {
        $mappedRequests[] = [
            "request_id" => (int)$r['request_id'],
            "tenant_id" => $r['tenant_id'],
            "hostel_id" => $r['hostel_id'],
            "room_id" => $r['room_id'],
            "status" => $r['status'],
            "requested_at" => $r['requested_at'],
            "full_name" => $r['full_name'],
            "phone_number" => $r['phone_number'],
            "room_number" => $r['room_number']
        ];
    }
    
    echo json_encode(["status" => "success", "data" => $mappedRequests]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
