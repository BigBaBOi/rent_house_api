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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!isset($data['bill_id']) || !isset($data['is_paid'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing bill_id or is_paid']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "UPDATE bills SET is_paid = ?, paid_at = IF(? = 1, NOW(), NULL) WHERE bill_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$data['is_paid'], $data['is_paid'], $data['bill_id']]);

    echo json_encode(['status' => 'success', 'message' => 'Updated bill successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
