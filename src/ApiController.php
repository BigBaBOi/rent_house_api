<?php
class ApiController {
    private $db;
    private $request;
    private $resourceRepository;
    private $userRegistrationService;
    private $ownerVerificationService;
    private $adminService;

    public function __construct(
        Database $db,
        Request $request,
        ResourceRepository $resourceRepository,
        ?UserRegistrationService $userRegistrationService = null,
        ?OwnerVerificationService $ownerVerificationService = null,
        ?AdminService $adminService = null
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->resourceRepository = $resourceRepository;
        
        if ($userRegistrationService === null || $ownerVerificationService === null || $adminService === null) {
            $conn = $db->getConnection();
            $this->ownerVerificationService = $ownerVerificationService ?? new OwnerVerificationService($conn);
            $this->userRegistrationService = $userRegistrationService ?? new UserRegistrationService($conn, $this->ownerVerificationService);
            $this->adminService = $adminService ?? new AdminService($conn, $this->ownerVerificationService);
        } else {
            $this->userRegistrationService = $userRegistrationService;
            $this->ownerVerificationService = $ownerVerificationService;
            $this->adminService = $adminService;
        }
    }

    public function handle() {
        $method = $this->request->getMethod();
        $action = $this->request->getQuery('action');
        $resource = strtolower($this->request->getQuery('resource') ?? ''); 
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
            case 'verify_owner':
                $this->verifyOwner($data, $conn);
                break;
            case 'reject_owner':
                $this->rejectOwner($data, $conn);
                break;
            case 'get_pending_owners':
                $this->getPendingOwners($data, $conn);
                break;
            default:
                Response::json(['status' => 'error', 'message' => 'Action không hợp lệ'], 400);
        }
    }

    private function registerUser(array $data, PDO $conn) {
        try {
            $result = $this->userRegistrationService->register($data);
            $code = $result['verification_status'] === 'pending_verification' ? 202 : 201;
            Response::json($result, $code);
        } catch (InvalidArgumentException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 409);
        } catch (PDOException $e) {
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

    private function verifyOwner(array $data, PDO $conn) {
        try {
            if (!isset($data['admin_id']) || !isset($data['verify_id'])) {
                Response::json(['status' => 'error', 'message' => 'Thiếu admin_id hoặc verify_id'], 400);
            }

            if (!$this->adminService->isAdmin($data['admin_id'])) {
                Response::json(['status' => 'error', 'message' => 'Chỉ admin mới có quyền duyệt'], 403);
            }

            $result = $this->adminService->approveOwnerVerification(
                $data['verify_id'],
                $data['admin_id'],
                $data['review_note'] ?? ''
            );

            $code = $result['status'] === 'error' ? 400 : 200;
            Response::json($result, $code);
        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function rejectOwner(array $data, PDO $conn) {
        try {
            if (!isset($data['admin_id']) || !isset($data['verify_id'])) {
                Response::json(['status' => 'error', 'message' => 'Thiếu admin_id hoặc verify_id'], 400);
            }

            if (!$this->adminService->isAdmin($data['admin_id'])) {
                Response::json(['status' => 'error', 'message' => 'Chỉ admin mới có quyền từ chối'], 403);
            }

            $result = $this->adminService->rejectOwnerVerification(
                $data['verify_id'],
                $data['admin_id'],
                $data['review_note'] ?? ''
            );

            $code = $result['status'] === 'error' ? 400 : 200;
            Response::json($result, $code);
        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function getPendingOwners(array $data, PDO $conn) {
        try {
            if (!isset($data['admin_id'])) {
                Response::json(['status' => 'error', 'message' => 'Thiếu admin_id'], 400);
            }

            if (!$this->adminService->isAdmin($data['admin_id'])) {
                Response::json(['status' => 'error', 'message' => 'Chỉ admin mới có quyền xem'], 403);
            }

            $limit = $data['limit'] ?? 50;
            $offset = $data['offset'] ?? 0;
            
            $pending = $this->adminService->getPendingOwnerVerifications($limit, $offset);
            Response::json(['status' => 'success', 'data' => $pending], 200);
        } catch (Exception $e) {
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
