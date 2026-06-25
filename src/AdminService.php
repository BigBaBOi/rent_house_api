<?php
class AdminService {
    private $conn;
    private $ownerVerificationService;

    public function __construct(PDO $conn, OwnerVerificationService $ownerVerificationService) {
        $this->conn = $conn;
        $this->ownerVerificationService = $ownerVerificationService;
    }

    /**
     * ====== [FEATURE 3: ADMIN VERIFICATION APPROVAL] ======
     * Duyệt xác thực chủ trọ - Admin phê duyệt yêu cầu xác thực
     */
    public function approveOwnerVerification(string $verifyId, string $adminId, string $reviewNote = ''): array {
        try {
            $updated = $this->ownerVerificationService->updateVerificationStatus(
                $verifyId,
                'Approved',
                $reviewNote,
                $adminId
            );

            if (!$updated) {
                return [
                    'status' => 'error',
                    'message' => 'Không thể cập nhật xác thực này'
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Đã duyệt xác thực chủ trọ'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ====== [FEATURE 3B: ADMIN VERIFICATION REJECTION] ======
     * Từ chối xác thực chủ trọ - Admin có thể từ chối và yêu cầu cung cấp lại
     */
    public function rejectOwnerVerification(string $verifyId, string $adminId, string $reviewNote = ''): array {
        try {
            $updated = $this->ownerVerificationService->updateVerificationStatus(
                $verifyId,
                'Rejected',
                $reviewNote,
                $adminId
            );

            if (!$updated) {
                return [
                    'status' => 'error',
                    'message' => 'Không thể cập nhật xác thực này'
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Đã từ chối xác thực chủ trọ'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách chủ trọ chờ xác thực
     */
    public function getPendingOwnerVerifications(int $limit = 50, int $offset = 0): array {
        return $this->ownerVerificationService->getPendingVerifications($limit, $offset);
    }

    /**
     * Kiểm tra quyền admin
     */
    public function isAdmin(string $userId): bool {
        $stmt = $this->conn->prepare('SELECT role FROM Users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && ($user['role'] === 'admin' || $user['role'] === 'SuperAdmin');
    }
}
