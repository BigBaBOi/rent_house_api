<?php
class OwnerVerificationService {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Tạo request xác thực cho chủ trọ
     */
    public function createVerificationRequest(string $userId, array $ownerData): bool {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO OwnerVerifications (owner_id, id_card_front_url, id_card_back_url, status, created_at) 
                 VALUES (?, ?, ?, ?, NOW())'
            );
            
            $stmt->execute([
                $userId,
                $ownerData['id_card_front_url'] ?? null,
                $ownerData['id_card_back_url'] ?? null,
                'pending'
            ]);

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Lấy trạng thái xác thực của chủ trọ
     */
    public function getVerificationStatus(string $userId): ?array {
        $stmt = $this->conn->prepare(
            'SELECT * FROM OwnerVerifications WHERE owner_id = ? ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật trạng thái xác thực (chỉ admin)
     */
    public function updateVerificationStatus(string $verifyId, string $status, string $reviewNote = null, string $reviewedBy = null): bool {
        try {
            $stmt = $this->conn->prepare(
                'UPDATE OwnerVerifications SET status = ?, review_note = ?, reviewed_by = ? WHERE verify_id = ?'
            );
            
            $stmt->execute([
                $status,
                $reviewNote,
                $reviewedBy,
                $verifyId
            ]);

            if ($stmt->rowCount() > 0) {
                // Nếu xác thực thành công, cập nhật user status
                if ($status === 'approved') {
                    $this->updateUserVerificationStatus($verifyId, 'verified');
                } elseif ($status === 'rejected') {
                    $this->updateUserVerificationStatus($verifyId, 'verification_failed');
                }
            }

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Cập nhật trạng thái xác thực của user
     */
    private function updateUserVerificationStatus(string $verifyId, string $status): bool {
        try {
            $stmt = $this->conn->prepare(
                'UPDATE Users SET verification_status = ? 
                 WHERE user_id = (SELECT owner_id FROM OwnerVerifications WHERE verify_id = ?)'
            );
            
            return $stmt->execute([$status, $verifyId]) && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Lấy danh sách chủ trọ chờ xác thực
     */
    public function getPendingVerifications(int $limit = 50, int $offset = 0): array {
        $stmt = $this->conn->prepare(
            'SELECT ov.*, u.email, u.full_name, u.phone_number 
             FROM OwnerVerifications ov
             JOIN Users u ON ov.owner_id = u.user_id
             WHERE ov.status = "pending"
             ORDER BY ov.created_at ASC
             LIMIT ? OFFSET ?'
        );
        
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
