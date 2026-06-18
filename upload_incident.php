<?php
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

$tenantId = $_POST['tenant_id'] ?? null;
$description = $_POST['description'] ?? '';
$alarmType = $_POST['alarm_type'] ?? 'Fire'; // Default if empty

if (!$tenantId || !$description) {
    echo json_encode(["status" => "error", "message" => "Missing tenant_id or description"]);
    exit;
}

$imageUrl = null;

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $fileExtension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $imageUrl = $protocol . $host . "/rent_house_api/" . $targetPath;
    }
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Find hostel_id for this tenant
    $stmt = $conn->prepare("SELECT hostel_id FROM Rooms WHERE current_tenant_id = ? LIMIT 1");
    $stmt->execute([$tenantId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        echo json_encode(["status" => "error", "message" => "Tenant is not in any room"]);
        exit;
    }
    
    // Insert alarm
    $stmt = $conn->prepare("INSERT INTO Alarms (hostel_id, triggered_by, alarm_type, status, description, image_url) VALUES (?, ?, ?, 'Active', ?, ?)");
    $stmt->execute([$room['hostel_id'], $tenantId, $alarmType, $description, $imageUrl]);
    
    echo json_encode(["status" => "success", "message" => "Incident reported successfully", "image_url" => $imageUrl]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
