<?php
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

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

$triggeredBy = $data['triggered_by'] ?? 'unknown';
$propertyId = $data['property_id'] ?? null;

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table if not exists (failsafe)
    $conn->exec("CREATE TABLE IF NOT EXISTS GlobalAlarms (id INT AUTO_INCREMENT PRIMARY KEY, property_id VARCHAR(50), status VARCHAR(20), triggered_by VARCHAR(50), timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");

    // Try to add property_id column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE GlobalAlarms ADD COLUMN property_id VARCHAR(50) AFTER id");
    } catch (PDOException $e) {
        // Ignore if column already exists
    }

    // Insert new alarm
    $stmt = $conn->prepare("INSERT INTO GlobalAlarms (property_id, status, triggered_by) VALUES (?, 'ACTIVE', ?)");
    $stmt->execute([$propertyId, $triggeredBy]);

    echo json_encode(["status" => "success", "message" => "Global alarm triggered"]);
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
