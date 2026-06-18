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
            case 'change_password':
                $this->changePassword($data, $conn);
                break;
            case 'submit_owner_verification':
                $this->submitOwnerVerification($data, $conn);
                break;
            case 'get_tenant_dashboard':
                $this->getTenantDashboard($conn);
                break;
            case 'search_hostels':
                $this->searchHostels($conn);
                break;
            case 'get_user_status':
                $this->getUserStatus($data, $conn);
                break;
            case 'get_owner_requests':
                $this->getOwnerRequests($conn);
                break;
            case 'handle_join_request':
                $this->handleJoinRequest($data, $conn);
                break;
            case 'get_profile':
                $this->getProfile($conn);
                break;
            case 'update_profile':
                $this->updateProfile($data, $conn);
                break;
            case 'leave_room':
                $this->leaveRoom($data, $conn);
                break;
            case 'remove_tenant':
                $this->removeTenant($data, $conn);
                break;
            case 'trigger_alarm':
                $this->triggerAlarm($data, $conn);
                break;
            case 'get_hostels_by_owner':
                $this->getHostelsByOwner($conn);
                break;
            case 'mark_safe':
                $this->markSafe($data, $conn);
                break;
            case 'resolve_alarm':
                $this->resolveAlarm($data, $conn);
                break;
            case 'get_safe_tenants':
                $this->getSafeTenants($conn);
                break;
            default:
                Response::json(['status' => 'error', 'message' => 'Action không hợp lệ'], 400);
        }
    }

    private function registerUser(array $data, PDO $conn) {
        try {
            $result = $this->userRegistrationService->register($data);
            $code = (isset($result['data']['verification_status']) && $result['data']['verification_status'] === 'Pending') ? 202 : 201;
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

    private function changePassword(array $data, PDO $conn) {
        try {
            if (!isset($data['user_id']) || !isset($data['old_password']) || !isset($data['new_password'])) {
                Response::json(['status' => 'error', 'message' => 'Thiếu thông tin user_id, old_password hoặc new_password'], 400);
            }

            $result = $this->userRegistrationService->changePassword($data['user_id'], $data['old_password'], $data['new_password']);
            $code = $result['status'] === 'success' ? 200 : 400;
            Response::json($result, $code);
        } catch (Exception $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function getUserStatus(array $data, PDO $conn) {
        if (!isset($data['user_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu user_id'], 400);
        }

        try {
            $stmt = $conn->prepare('SELECT verification_status FROM Users WHERE user_id = ?');
            $stmt->execute([$data['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                Response::json(['status' => 'success', 'verification_status' => $user['verification_status']], 200);
            } else {
                Response::json(['status' => 'error', 'message' => 'Không tìm thấy user'], 404);
            }
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function getTenantDashboard(PDO $conn) {
        $tenantId = $this->request->getQuery('tenant_id');
        if (!$tenantId) {
            Response::json(['status' => 'error', 'message' => 'Thiếu tenant_id'], 400);
        }

        try {
            $dashboardData = [
                'room_number' => 'Chưa thuê phòng',
                'unpaid_bill' => null,
                'notifications' => []
            ];

            // 1. Get room info
            $stmt = $conn->prepare('SELECT room_id, hostel_id, room_number FROM Rooms WHERE current_tenant_id = ? AND status = "Occupied" LIMIT 1');
            $stmt->execute([$tenantId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($room) {
                $roomId = $room['room_id'];
                $hostelId = $room['hostel_id'];
                $dashboardData['room_number'] = 'Phòng ' . $room['room_number'];
                $dashboardData['hostel_id'] = $hostelId;
                
                // Get hostel name
                $hostelStmt = $conn->prepare('SELECT hostel_name FROM Hostels WHERE hostel_id = ? LIMIT 1');
                $hostelStmt->execute([$hostelId]);
                $hostel = $hostelStmt->fetch(PDO::FETCH_ASSOC);
                if ($hostel) {
                    $dashboardData['hostel_name'] = $hostel['hostel_name'];
                }

                // 2. Get unpaid bill
                $billStmt = $conn->prepare('SELECT bill_id, room_id, billing_month, total_amount, is_paid, created_at, paid_at FROM Bills WHERE room_id = ? AND is_paid = 0 ORDER BY created_at DESC LIMIT 1');
                $billStmt->execute([$roomId]);
                $unpaidBill = $billStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($unpaidBill) {
                    $dashboardData['unpaid_bill'] = [
                        'bill_id' => $unpaidBill['bill_id'],
                        'room_id' => $unpaidBill['room_id'],
                        'billing_month' => $unpaidBill['billing_month'],
                        'total_amount' => (float)$unpaidBill['total_amount'],
                        'is_paid' => (bool)$unpaidBill['is_paid'],
                        'created_at' => $unpaidBill['created_at'],
                        'paid_at' => $unpaidBill['paid_at']
                    ];
                }

                // 3. Get latest notifications
                $notifStmt = $conn->prepare('SELECT notification_id, hostel_id, title, content, created_at FROM Notifications WHERE hostel_id = ? ORDER BY created_at DESC LIMIT 5');
                $notifStmt->execute([$hostelId]);
                $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($notifications) {
                    $notifList = [];
                    foreach ($notifications as $notif) {
                        $notifList[] = [
                            'title' => $notif['title'],
                            'content' => $notif['content'],
                            'time' => date('d/m/Y H:i', strtotime($notif['created_at']))
                        ];
                    }
                    $dashboardData['notifications'] = $notifList;
                }
            }

            Response::json($dashboardData);
            
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    private function submitOwnerVerification(array $data, PDO $conn) {
        try {
            if (!isset($data['user_id'])) {
                Response::json(['status' => 'error', 'message' => 'Thiếu user_id'], 400);
            }

            $success = $this->ownerVerificationService->createVerificationRequest($data['user_id'], $data);
            if ($success) {
                Response::json(['status' => 'success', 'message' => 'Gửi yêu cầu xác thực thành công']);
            } else {
                Response::json(['status' => 'error', 'message' => 'Không thể gửi yêu cầu xác thực'], 500);
            }
        } catch (Exception $e) {
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

    private function searchHostels(PDO $conn) {
        $query = $this->request->getQuery('query');
        if ($query === null) {
            $query = '';
        }

        try {
            $sql = "SELECT h.hostel_id, h.owner_id, h.hostel_name, h.address, u.phone_number AS phone, u.full_name AS owner_name 
                    FROM Hostels h 
                    JOIN Users u ON h.owner_id = u.user_id 
                    WHERE (h.hostel_name LIKE :likeQuery 
                       OR h.address LIKE :likeQuery 
                       OR u.phone_number = :exactQuery)
                       AND h.is_verified = 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'likeQuery' => '%' . $query . '%',
                'exactQuery' => $query
            ]);
            
            $hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::json([
                'status' => 'success',
                'data' => $hostels
            ]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi database: ' . $e->getMessage()], 500);
        }
    }

    private function getOwnerRequests(PDO $conn) {
        $ownerId = $this->request->getQuery('owner_id');
        if (!$ownerId) {
            Response::json(['status' => 'error', 'message' => 'Thiếu owner_id'], 400);
        }

        try {
            $sql = "SELECT j.request_id, j.tenant_id, u.full_name AS tenant_name, u.phone_number AS tenant_phone, 
                           j.requested_at, j.room_id, r.room_number 
                    FROM JoinRequests j
                    JOIN Users u ON j.tenant_id = u.user_id
                    JOIN Rooms r ON j.room_id = r.room_id
                    JOIN Hostels h ON j.hostel_id = h.hostel_id
                    WHERE h.owner_id = :owner_id AND j.status = 'Pending'
                    ORDER BY j.requested_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['owner_id' => $ownerId]);
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::json(['status' => 'success', 'data' => $requests]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function handleJoinRequest(array $data, PDO $conn) {
        if (!isset($data['request_id']) || !isset($data['action'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu dữ liệu'], 400);
        }
        
        $requestId = $data['request_id'];
        $action = $data['action']; // 'Accepted' or 'Rejected'

        if (!in_array($action, ['Accepted', 'Rejected'])) {
            Response::json(['status' => 'error', 'message' => 'Action không hợp lệ'], 400);
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT tenant_id, room_id FROM JoinRequests WHERE request_id = :id AND status = 'Pending'");
            $stmt->execute(['id' => $requestId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                $conn->rollBack();
                Response::json(['status' => 'error', 'message' => 'Yêu cầu không tồn tại hoặc đã xử lý'], 404);
            }

            $updateStmt = $conn->prepare("UPDATE JoinRequests SET status = :status WHERE request_id = :id");
            $updateStmt->execute(['status' => $action, 'id' => $requestId]);

            if ($action === 'Accepted') {
                $roomStmt = $conn->prepare("UPDATE Rooms SET status = 'Occupied', current_tenant_id = :tenant_id WHERE room_id = :room_id AND status = 'Available'");
                $roomStmt->execute([
                    'tenant_id' => $req['tenant_id'],
                    'room_id' => $req['room_id']
                ]);

                if ($roomStmt->rowCount() === 0) {
                    $conn->rollBack();
                    Response::json(['status' => 'error', 'message' => 'Phòng này đã có người thuê'], 400);
                }
            }

            $conn->commit();
            Response::json(['status' => 'success', 'message' => 'Đã xử lý yêu cầu']);
        } catch (PDOException $e) {
            $conn->rollBack();
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function getProfile(PDO $conn) {
        $userId = $this->request->getQuery('user_id');
        if (!$userId) {
            Response::json(['status' => 'error', 'message' => 'Thiếu user_id'], 400);
        }
        
        try {
            $stmt = $conn->prepare("SELECT user_id, email, full_name, phone_number, role FROM Users WHERE user_id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                Response::json(['status' => 'error', 'message' => 'Người dùng không tồn tại'], 404);
            }
            
            Response::json(['status' => 'success', 'data' => $user]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function updateProfile(array $data, PDO $conn) {
        if (!isset($data['user_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu user_id'], 400);
        }
        
        $userId = $data['user_id'];
        $email = $data['email'] ?? null;
        $phone = $data['phone_number'] ?? null;
        
        try {
            $stmt = $conn->prepare("UPDATE Users SET email = :email, phone_number = :phone WHERE user_id = :id");
            $stmt->execute([
                'email' => $email,
                'phone' => $phone,
                'id' => $userId
            ]);
            
            Response::json(['status' => 'success', 'message' => 'Cập nhật thành công']);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB (có thể trùng email): ' . $e->getMessage()], 500);
        }
    }

    private function leaveRoom(array $data, PDO $conn) {
        if (!isset($data['tenant_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu tenant_id'], 400);
        }
        
        $tenantId = $data['tenant_id'];
        
        try {
            $stmt = $conn->prepare("UPDATE Rooms SET status = 'Available', current_tenant_id = NULL WHERE current_tenant_id = :id");
            $stmt->execute(['id' => $tenantId]);
            
            if ($stmt->rowCount() > 0) {
                Response::json(['status' => 'success', 'message' => 'Rời phòng thành công']);
            } else {
                Response::json(['status' => 'error', 'message' => 'Bạn hiện không có phòng nào'], 404);
            }
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function removeTenant(array $data, PDO $conn) {
        if (!isset($data['room_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu room_id'], 400);
        }
        
        $roomId = $data['room_id'];
        
        try {
            $stmt = $conn->prepare("UPDATE Rooms SET status = 'Available', current_tenant_id = NULL WHERE room_id = :id");
            $stmt->execute(['id' => $roomId]);
            
            if ($stmt->rowCount() > 0) {
                Response::json(['status' => 'success', 'message' => 'Đã xóa người thuê khỏi phòng']);
            } else {
                Response::json(['status' => 'error', 'message' => 'Không tìm thấy phòng'], 404);
            }
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
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

    private function getHostelsByOwner(PDO $conn) {
        $ownerId = $this->request->getQuery('owner_id');
        if (!$ownerId) {
            Response::json(['status' => 'error', 'message' => 'Thiếu owner_id'], 400);
        }

        try {
            $stmt = $conn->prepare('SELECT hostel_id, hostel_name FROM Hostels WHERE owner_id = ?');
            $stmt->execute([$ownerId]);
            $hostels = $stmt->fetchAll(PDO::FETCH_ASSOC);
            Response::json(['status' => 'success', 'data' => $hostels]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function triggerAlarm(array $data, PDO $conn) {
        if (!isset($data['property_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu property_id'], 400);
        }

        $propertyId = $data['property_id'];
        $triggeredBy = $data['triggered_by'] ?? 'Unknown';
        $location = $data['location'] ?? 'Khu vực chung';
        
        try {
            $stmt = $conn->prepare('SELECT hostel_name, address FROM Hostels WHERE hostel_id = ? LIMIT 1');
            $stmt->execute([$propertyId]);
            $hostel = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($hostel) {
                Response::json([
                    'status' => 'success', 
                    'message' => 'Đã xác nhận báo động',
                    'data' => [
                        'hostel_name' => $hostel['hostel_name'],
                        'address' => $hostel['address']
                    ]
                ]);
            } else {
                Response::json(['status' => 'error', 'message' => 'Không tìm thấy khu trọ'], 404);
            }
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function markSafe(array $data, PDO $conn) {
        if (!isset($data['user_id']) || !isset($data['alarm_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu user_id hoặc alarm_id'], 400);
        }

        try {
            // Check if table SafeTenants exists
            $conn->exec("CREATE TABLE IF NOT EXISTS SafeTenants (id INT AUTO_INCREMENT PRIMARY KEY, alarm_id INT, user_id VARCHAR(50), timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)");

            $stmt = $conn->prepare("INSERT INTO SafeTenants (alarm_id, user_id) VALUES (?, ?)");
            $stmt->execute([$data['alarm_id'], $data['user_id']]);

            Response::json(['status' => 'success', 'message' => 'Đã đánh dấu an toàn']);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function resolveAlarm(array $data, PDO $conn) {
        if (!isset($data['alarm_id'])) {
            Response::json(['status' => 'error', 'message' => 'Thiếu alarm_id'], 400);
        }

        try {
            $stmt = $conn->prepare("UPDATE GlobalAlarms SET status = 'RESOLVED' WHERE id = ?");
            $stmt->execute([$data['alarm_id']]);

            Response::json(['status' => 'success', 'message' => 'Đã tắt báo động']);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function getSafeTenants(PDO $conn) {
        $alarmId = $this->request->getQuery('alarm_id');
        $propertyId = $this->request->getQuery('property_id');
        
        if (!$alarmId || !$propertyId) {
            Response::json(['status' => 'error', 'message' => 'Thiếu alarm_id hoặc property_id'], 400);
        }

        try {
            // Get all tenants in the property
            $stmt = $conn->prepare("
                SELECT u.user_id, u.full_name, r.room_number,
                       CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as is_safe
                FROM Rooms r
                JOIN Users u ON r.current_tenant_id = u.user_id
                LEFT JOIN SafeTenants s ON s.user_id = u.user_id AND s.alarm_id = ?
                WHERE r.hostel_id = ? AND r.current_tenant_id IS NOT NULL
            ");
            $stmt->execute([$alarmId, $propertyId]);
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::json(['status' => 'success', 'data' => $tenants]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()], 500);
        }
    }

    private function sanitizeFields(array $input, array $allowed) {
        return array_filter(
            $input,
            fn($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
