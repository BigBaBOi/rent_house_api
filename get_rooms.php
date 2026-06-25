<?php
// ====== [FEATURE 5: ROOM MANAGEMENT - GET ROOMS] ======
// Lấy danh sách phòng (có thể lọc theo hostel_id)
// Query params: hostel_id (optional)

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

$hostelId = $_GET['hostel_id'] ?? null;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($hostelId) {
        $stmt = $conn->prepare("SELECT * FROM Rooms WHERE hostel_id = ?");
        $stmt->execute([$hostelId]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM Rooms");
        $stmt->execute();
    }
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $rooms]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
