<?php
class ApiController {
    private $db;
    private $request;
    private $resourceRepository;

    public function __construct(Database $db, Request $request, ResourceRepository $resourceRepository) {
        $this->db = $db;
        $this->request = $request;
        $this->resourceRepository = $resourceRepository;
    }

    public function handle() {
        $method = $this->request->getMethod();
        $action = $this->request->getQuery('action');
        $resource = strtolower($this->request->getQuery('resource')); 
        $id = $this->request->getQuery('id');

        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        if (!$action && !$this->resourceRepository->isResourceSupported($resource)) {
            Response::json(['status' => 'error', 'message' => 'Resource không hợp lệ hoặc không được hỗ trợ'], 400);
        }

        try {
            $conn = $this->db->getConnection();

            if ($action) {
                $this->handleAction($action, $conn);
            } else {
                $this->handleResource($resource, $id, $method, $conn);
            }
        } catch (InvalidArgumentException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi database: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }

    private function handleAction(string $action, PDO $conn) {
        $data = $this->request->getJsonBody();

        switch ($action) {
            case 'register_user':
                $this->registerUser($data, $conn);
                break;
            case 'login_user':
                $this->loginUser($data, $conn);
                break;
            default:
                Response::json(['status' => 'error', 'message' => 'Action không hợp lệ'], 400);
        }
    }

    private function registerUser(array $data, PDO $conn) {
        try {
            $userId = uniqid('USER_');
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                'INSERT INTO Users (user_id, email, password_hash, full_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['phone_number'],
                $data['role'] ?? 'tenant'
            ]);

            Response::json(['status' => 'success', 'message' => 'Đăng ký thành công!', 'user_id' => $userId], 201);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                Response::json(['status' => 'error', 'message' => 'Email này đã được sử dụng!'], 409);
            }
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function loginUser(array $data, PDO $conn) {
        try {
            $stmt = $conn->prepare('SELECT * FROM Users WHERE email = ?');
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'], $user['password_hash'])) {
                unset($user['password_hash']);
                Response::json(['status' => 'success', 'message' => 'Đăng nhập thành công!', 'data' => $user], 200);
            }

            Response::json(['status' => 'error', 'message' => 'Sai email hoặc mật khẩu!'], 401);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function handleResource(string $resource, $id, string $method, PDO $conn) {
        $meta = $this->resourceRepository->getResource($resource);
        $table = $meta['table'];
        $primaryKey = $meta['primary'];
        $fields = $meta['fields'];

        switch ($method) {
            case 'GET':
                $this->handleGet($conn, $table, $primaryKey, $fields, $id);
                break;
            case 'POST':
                $this->handleCreate($conn, $table, $fields, $primaryKey);
                break;
            case 'PUT':
            case 'PATCH':
                $this->handleUpdate($conn, $table, $fields, $primaryKey, $id);
                break;
            case 'DELETE':
                $this->handleDelete($conn, $table, $primaryKey, $id);
                break;
            default:
                Response::json(['status' => 'error', 'message' => 'Phương thức HTTP không hỗ trợ'], 405);
        }
    }

    private function handleGet(PDO $conn, string $table, string $primaryKey, array $fields, $id) {
        if ($id !== null) {
            $stmt = $conn->prepare('SELECT ' . implode(',', $fields) . ' FROM `' . $table . '` WHERE `' . $primaryKey . '` = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                Response::json(['status' => 'error', 'message' => 'Không tìm thấy bản ghi'], 404);
            }
            Response::json(['status' => 'success', 'data' => $row]);
        }

        $stmt = $conn->prepare('SELECT ' . implode(',', $fields) . ' FROM `' . $table . '`');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json(['status' => 'success', 'data' => $rows]);
    }

    private function handleCreate(PDO $conn, string $table, array $fields, string $primaryKey) {
        $input = $this->request->getJsonBody();
        if (empty($input)) {
            Response::json(['status' => 'error', 'message' => 'Payload JSON trống'], 400);
        }

        $data = $this->sanitizeFields($input, $fields);
        if (isset($data[$primaryKey]) && $data[$primaryKey] === '') {
            unset($data[$primaryKey]);
        }

        if (empty($data)) {
            Response::json(['status' => 'error', 'message' => 'Không có trường hợp lệ để thêm'], 400);
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders));
        $stmt = $conn->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();

        $newId = $conn->lastInsertId();
        if (!$newId && isset($data[$primaryKey])) {
            $newId = $data[$primaryKey];
        }

        Response::json(['status' => 'success', 'message' => 'Tạo mới thành công', 'id' => $newId], 201);
    }

    private function handleUpdate(PDO $conn, string $table, array $fields, string $primaryKey, $id) {
        if ($id === null) {
            Response::json(['status' => 'error', 'message' => 'Thiếu id để cập nhật'], 400);
        }

        $input = $this->request->getJsonBody();
        $data = $this->sanitizeFields($input, $fields);
        if (isset($data[$primaryKey])) {
            unset($data[$primaryKey]);
        }
        if (empty($data)) {
            Response::json(['status' => 'error', 'message' => 'Không có trường hợp lệ để cập nhật'], 400);
        }

        $updates = array_map(fn($col) => "`$col` = :$col", array_keys($data));
        $sql = sprintf('UPDATE `%s` SET %s WHERE `%s` = :id', $table, implode(', ', $updates), $primaryKey);
        $stmt = $conn->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => 'Không có thay đổi hoặc bản ghi không tồn tại'], 404);
        }

        Response::json(['status' => 'success', 'message' => 'Cập nhật thành công']);
    }

    private function handleDelete(PDO $conn, string $table, string $primaryKey, $id) {
        if ($id === null) {
            Response::json(['status' => 'error', 'message' => 'Thiếu id để xóa'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM `' . $table . '` WHERE `' . $primaryKey . '` = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => 'Bản ghi không tồn tại'], 404);
        }

        Response::json(['status' => 'success', 'message' => 'Xóa thành công']);
    }

    private function sanitizeFields(array $input, array $allowed) {
        return array_filter(
            $input,
            fn($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
