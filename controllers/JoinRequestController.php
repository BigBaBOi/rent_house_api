<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class JoinRequestController {
    public function sendRequest() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['tenant_id'] ?? '')) {
                Response::error('tenant_id là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['hostel_id'] ?? '')) {
                Response::error('hostel_id là bắt buộc', 400);
            }

            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("INSERT INTO JoinRequests (tenant_id, hostel_id, status) VALUES (?, ?, 'Pending')");
            $stmt->execute([$data['tenant_id'], $data['hostel_id']]);
            
            $requestId = $conn->lastInsertId();
            Response::success(['request_id' => $requestId], 'Gửi yêu cầu gia nhập thành công', 201);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '23000') !== false) {
                Response::error('Yêu cầu này đã tồn tại', 409);
            }
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getOwnerRequests($ownerId) {
        try {
            if (!$ownerId) {
                Response::error('owner_id là bắt buộc', 400);
            }

            $conn = Database::getInstance()->getConnection();
            $sql = "SELECT jr.request_id, jr.tenant_id, u.full_name, u.phone_number, jr.requested_at
                    FROM JoinRequests jr
                    JOIN Hostels h ON jr.hostel_id = h.hostel_id
                    JOIN Users u ON jr.tenant_id = u.user_id
                    WHERE h.owner_id = ? AND jr.status = 'Pending'
                    ORDER BY jr.requested_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ownerId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success($rows);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function handleRequest() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['request_id'] ?? '')) {
                Response::error('request_id là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['action_status'] ?? '')) {
                Response::error('action_status là bắt buộc', 400);
            }

            $actionStatus = $data['action_status'];
            if (!in_array($actionStatus, ['Accepted', 'Rejected'])) {
                Response::error('action_status phải là "Accepted" hoặc "Rejected"', 400);
            }

            if ($actionStatus === 'Accepted' && !Validator::validateRequired($data['room_id'] ?? '')) {
                Response::error('Khi phê duyệt yêu cầu, phải gửi room_id', 400);
            }

            $conn = Database::getInstance()->getConnection();
            $conn->beginTransaction();

            // Update JoinRequests
            $stmt = $conn->prepare("UPDATE JoinRequests SET status = ? WHERE request_id = ?");
            $stmt->execute([$actionStatus, $data['request_id']]);

            if ($stmt->rowCount() === 0) {
                $conn->rollBack();
                Response::error('request_id không tồn tại', 404);
            }

            if ($actionStatus === 'Accepted') {
                $stmt2 = $conn->prepare("UPDATE Rooms SET current_tenant_id = ?, status = 'Occupied' WHERE room_id = ?");
                $stmt2->execute([$data['tenant_id'] ?? null, $data['room_id']]);

                if ($stmt2->rowCount() === 0) {
                    $conn->rollBack();
                    Response::error('room_id không tồn tại hoặc không khả dụng', 404);
                }
            }

            $conn->commit();
            Response::success(null, 'Xử lý yêu cầu gia nhập thành công');
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
