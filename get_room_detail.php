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

$roomId = $_GET['room_id'] ?? null;

if (!$roomId) {
    echo json_encode(["status" => "error", "message" => "Missing room_id"]);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Join with Users to get tenant name
    $sql = "SELECT r.*, u.full_name as tenant_name 
            FROM Rooms r 
            LEFT JOIN Users u ON r.current_tenant_id = u.user_id 
            WHERE r.room_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$roomId]);
    
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($room) {
        // cast price and area to double if needed
        $room['price'] = (double)$room['price'];
        $room['area'] = (double)$room['area'];
        echo json_encode(["status" => "success", "data" => $room]);
    } else {
        echo json_encode(["status" => "error", "message" => "Room not found"]);
    }
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
