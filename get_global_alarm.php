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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Failsafe
    $conn->exec("CREATE TABLE IF NOT EXISTS GlobalAlarms (id INT AUTO_INCREMENT PRIMARY KEY, property_id VARCHAR(50), status VARCHAR(20), triggered_by VARCHAR(50), timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");

    // Try to add property_id column if it doesn't exist
    try {
        $conn->exec("ALTER TABLE GlobalAlarms ADD COLUMN property_id VARCHAR(50) AFTER id");
    } catch (PDOException $e) {
        // Ignore if column already exists
    }

    $userId = $_GET['user_id'] ?? null;
    
    $whereClause = "WHERE g.status = 'ACTIVE' AND g.timestamp >= NOW() - INTERVAL 15 MINUTE";
    
    if ($userId) {
        // If user_id is provided, only return alarms for properties owned by this user or where this user is renting
        $whereClause .= " AND (g.property_id IN (SELECT hostel_id FROM Hostels WHERE owner_id = :userId) 
                             OR g.property_id IN (SELECT hostel_id FROM Rooms WHERE current_tenant_id = :userId)
                             OR g.property_id IS NULL OR g.property_id = '')";
    }

    // Get the latest active alarm within the last 15 minutes
    $stmt = $conn->prepare("
        SELECT g.*, 
               COALESCE(h.hostel_name, 'Hệ thống') as hostel_name
        FROM GlobalAlarms g
        LEFT JOIN Users u ON g.triggered_by = u.user_id
        LEFT JOIN Rooms r ON u.user_id = r.current_tenant_id
        LEFT JOIN Hostels h ON (r.hostel_id = h.hostel_id OR u.user_id = h.owner_id)
        $whereClause
        ORDER BY g.id DESC LIMIT 1
    ");
    
    if ($userId) {
        $stmt->bindParam(':userId', $userId);
    }
    $stmt->execute();
    $alarm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($alarm) {
        echo json_encode([
            "status" => "success",
            "data" => [
                "active" => true,
                "alarm_id" => $alarm['id'],
                "property_id" => $alarm['property_id'],
                "triggered_by" => $alarm['triggered_by'],
                "hostel_name" => $alarm['hostel_name'],
                "timestamp" => $alarm['timestamp']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "data" => [
                "active" => false
            ]
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
