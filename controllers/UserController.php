<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function getStats() {
        try {
            $activeLandlords = $this->userModel->getCountVerifiedOwners();
            
            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) FROM OwnerVerifications WHERE status = 'Pending'");
            $stmt->execute();
            $pendingVerifications = (int)$stmt->fetchColumn();

            Response::success([
                'active_landlords' => $activeLandlords,
                'pending_verifications' => $pendingVerifications
            ]);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getPendingKYC() {
        try {
            $conn = Database::getInstance()->getConnection();
            $sql = "SELECT ov.verify_id, ov.owner_id, u.full_name, ov.created_at
                    FROM OwnerVerifications ov
                    JOIN Users u ON ov.owner_id = u.user_id
                    WHERE ov.status = 'Pending'
                    ORDER BY ov.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success($rows);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function approveKYC() {
        try {
            $data = Validator::getJsonInput();
            $verifyId = $data['verify_id'] ?? null;
            $ownerId = $data['owner_id'] ?? null;

            if (!$verifyId || !$ownerId) {
                Response::error('Thiếu verify_id hoặc owner_id', 400);
            }

            $this->userModel->beginTransaction();

            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("UPDATE OwnerVerifications SET status = 'Approved' WHERE verify_id = ?");
            $stmt->execute([$verifyId]);

            if ($stmt->rowCount() === 0) {
                $this->userModel->rollback();
                Response::error('verify_id không tồn tại', 404);
            }

            $this->userModel->updateVerificationStatus($ownerId, 'Verified');
            $this->userModel->commit();

            Response::success(null, 'Phê duyệt KYC thành công');
        } catch (Exception $e) {
            if ($this->userModel->inTransaction()) {
                $this->userModel->rollback();
            }
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getUserProfile($userId) {
        try {
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                Response::error('Không tìm thấy user', 404);
            }
            unset($user['password_hash']);
            Response::success($user);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
