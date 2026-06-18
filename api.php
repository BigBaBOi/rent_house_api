<?php
// Bật hiển thị lỗi để dễ debug dưới local
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rent_house";

$resources = [
    'users' => [
        'table' => 'Users',
        'primary' => 'user_id',
        'fields' => ['user_id', 'email', 'full_name', 'phone_number', 'role', 'verification_status', 'created_at']
    ],
    'owner_verifications' => [
        'table' => 'OwnerVerifications',
        'primary' => 'verify_id',
        'fields' => ['verify_id', 'owner_id', 'id_card_front_url', 'id_card_back_url', 'status', 'reviewed_by', 'review_note', 'created_at']
    ],
    'hostels' => [
        'table' => 'Hostels',
        'primary' => 'hostel_id',
        'fields' => ['hostel_id', 'owner_id', 'hostel_name', 'address', 'is_verified', 'created_at']
    ],
    'rooms' => [
        'table' => 'Rooms',
        'primary' => 'room_id',
        'fields' => ['room_id', 'hostel_id', 'room_number', 'price', 'status', 'current_tenant_id']
    ],
    'join_requests' => [
        'table' => 'JoinRequests',
        'primary' => 'request_id',
        'fields' => ['request_id', 'tenant_id', 'hostel_id', 'room_id', 'status', 'requested_at']
    ],
    'alarms' => [
        'table' => 'Alarms',
        'primary' => 'alarm_id',
        'fields' => ['alarm_id', 'hostel_id', 'triggered_by', 'alarm_type', 'status', 'triggered_at', 'resolved_at']
    ],
    'alarm_responses' => [
        'table' => 'AlarmResponses',
        'primary' => 'response_id',
        'fields' => ['response_id', 'alarm_id', 'tenant_id', 'room_id', 'is_safe', 'responded_at']
    ],
    'bills' => [
        'table' => 'bills',
        'primary' => 'bill_id',
        'fields' => ['bill_id', 'room_id', 'billing_month', 'electric_index', 'water_index', 'total_amount', 'is_paid', 'created_at', 'paid_at']
    ],
    'notifications' => [
        'table' => 'notifications',
        'primary' => 'notification_id',
        'fields' => ['notification_id', 'hostel_id', 'title', 'content', 'created_at']
    ]
];

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(['status' => 'error', 'message' => 'Invalid JSON payload'], 400);
    }
    return $decoded;
}

function sanitizeFields(array $input, array $allowed) {
    return array_filter(
        $input,
        fn($key) => in_array($key, $allowed, true),
        ARRAY_FILTER_USE_KEY
    );
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['resource']) ? strtolower(trim($_GET['resource'])) : null;
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Cho phép custom actions (register_user, login_user) mà không cần resource
if (!$action && (!$resource || !isset($resources[$resource]))) {
    respond(['status' => 'error', 'message' => 'Resource không hợp lệ hoặc không được hỗ trợ'], 400);
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ==========================================
    // API: ĐĂNG KÝ (Nhận password, băm ra và lưu)
    // ==========================================
    $data = getJsonInput();

    if ($action === 'register_user') {
        try {
            // 1. Tự sinh một mã user_id duy nhất (VD: USER_64a1b2c3)
            $user_id = uniqid('USER_'); 
            
            // 2. Băm mật khẩu người dùng nhập vào bằng thuật toán mặc định an toàn nhất của PHP
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

            // 3. Lưu vào Database
            $stmt = $conn->prepare("INSERT INTO Users (user_id, email, password_hash, full_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, 
                $data['email'], 
                $hashed_password, 
                $data['full_name'], 
                $data['phone_number'], 
                $data['role'] ?? 'tenant'
            ]);
            
            respond(["status" => "success", "message" => "Đăng ký thành công!", "user_id" => $user_id], 201);
        } catch(PDOException $e) {
            // Lỗi mã 23000 thường là do trùng Email (UNIQUE constraint)
            if ($e->getCode() == 23000) {
                respond(["status" => "error", "message" => "Email này đã được sử dụng!"], 409);
            } else {
                respond(["status" => "error", "message" => "Lỗi: " . $e->getMessage()], 500);
            }
        }
    }

    // ==========================================
    // API: ĐĂNG NHẬP (Kiểm tra email và mật khẩu)
    // ==========================================
    if ($action === 'login_user') {
        try {
            $email = $data['email'];
            $password = $data['password'];

            // 1. Tìm user theo email
            $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Kiểm tra user có tồn tại và đối chiếu mật khẩu
            if ($user && password_verify($password, $user['password_hash'])) {
                // 3. Xóa cột password_hash trước khi trả JSON về Android để bảo mật
                unset($user['password_hash']);
                
                respond([
                    "status" => "success", 
                    "message" => "Đăng nhập thành công!", 
                    "data" => $user
                ], 200);
            } else {
                respond(["status" => "error", "message" => "Sai email hoặc mật khẩu!"], 401);
            }
        } catch(PDOException $e) {
            respond(["status" => "error", "message" => "Lỗi: " . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // GENERIC CRUD Operations (Resource-based)
    // ==========================================
    $meta = $resources[$resource];
    $table = $meta['table'];
    $primaryKey = $meta['primary'];
    $fields = $meta['fields'];

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $stmt = $conn->prepare("SELECT " . implode(',', $fields) . " FROM `$table` WHERE `$primaryKey` = :id LIMIT 1");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    respond(['status' => 'error', 'message' => 'Không tìm thấy bản ghi'], 404);
                }
                respond(['status' => 'success', 'data' => $row]);
            }

            $stmt = $conn->prepare("SELECT " . implode(',', $fields) . " FROM `$table`");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            respond(['status' => 'success', 'data' => $rows]);
            break;

        case 'POST':
            $input = getJsonInput();
            if (empty($input)) {
                respond(['status' => 'error', 'message' => 'Payload JSON trống'], 400);
            }
            $data = sanitizeFields($input, $fields);
            if (isset($data[$primaryKey]) && $data[$primaryKey] === '') {
                unset($data[$primaryKey]);
            }
            if (empty($data)) {
                respond(['status' => 'error', 'message' => 'Không có trường hợp lệ để thêm'], 400);
            }

            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ':' . $col, $columns);
            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (%s)",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
            $stmt = $conn->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();

            $newId = $conn->lastInsertId();
            if (!$newId && isset($data[$primaryKey])) {
                $newId = $data[$primaryKey];
            }
            respond(['status' => 'success', 'message' => 'Tạo mới thành công', 'id' => $newId], 201);
            break;

        case 'PUT':
        case 'PATCH':
            if ($id === null) {
                respond(['status' => 'error', 'message' => 'Thiếu id để cập nhật'], 400);
            }
            $input = getJsonInput();
            $data = sanitizeFields($input, $fields);
            if (isset($data[$primaryKey])) {
                unset($data[$primaryKey]);
            }
            if (empty($data)) {
                respond(['status' => 'error', 'message' => 'Không có trường hợp lệ để cập nhật'], 400);
            }

            $updates = array_map(fn($col) => "`$col` = :$col", array_keys($data));
            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE `%s` = :id",
                $table,
                implode(', ', $updates),
                $primaryKey
            );
            $stmt = $conn->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                respond(['status' => 'error', 'message' => 'Không có thay đổi hoặc bản ghi không tồn tại'], 404);
            }
            respond(['status' => 'success', 'message' => 'Cập nhật thành công']);
            break;

        case 'DELETE':
            if ($id === null) {
                respond(['status' => 'error', 'message' => 'Thiếu id để xóa'], 400);
            }
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$primaryKey` = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                respond(['status' => 'error', 'message' => 'Bản ghi không tồn tại'], 404);
            }
            respond(['status' => 'success', 'message' => 'Xóa thành công']);
            break;

        default:
            respond(['status' => 'error', 'message' => 'Phương thức HTTP không hỗ trợ'], 405);
    }

} catch (PDOException $e) {
    respond(['status' => 'error', 'message' => 'Lỗi database: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    respond(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
}

$conn = null;
?>