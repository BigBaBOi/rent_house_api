<?php
require_once __DIR__ . '/OwnerVerificationService.php';

class UserRegistrationService {
    private $conn;
    private $ownerVerificationService;

    public function __construct(PDO $conn, OwnerVerificationService $ownerVerificationService) {
        $this->conn = $conn;
        $this->ownerVerificationService = $ownerVerificationService;
    }

    /**
     * Đăng ký user mới
     */
    public function register(array $data): array {
        try {
            // Sinh user_id duy nhất
            $userId = uniqid('USER_');
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = $data['role'] ?? 'tenant';
            
            // Trạng thái xác thực mặc định
            $verificationStatus = 'Verified'; // Tenant tự động xác thực
            
            // Nếu đăng ký là chủ trọ, cần xác thực từ admin
            if (strtolower($role) === 'owner') {
                $verificationStatus = 'Pending';
            }

            // Thêm user vào database
            $stmt = $this->conn->prepare(
                'INSERT INTO Users (user_id, email, password_hash, full_name, phone_number, role, verification_status, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            
            $stmt->execute([
                $userId,
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['phone_number'],
                $role,
                $verificationStatus
            ]);

            $responseData = [
                'user_id' => $userId,
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'role' => $role,
                'verification_status' => $verificationStatus
            ];

            // Nếu là chủ trọ, báo về cần xác thực
            if (strtolower($role) === 'owner') {
                return [
                    'status' => 'success',
                    'message' => 'Đăng ký thành công! Vui lòng chờ admin xác thực tài khoản chủ trọ.',
                    'data' => $responseData
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Đăng ký thành công!',
                'data' => $responseData
            ];

        } catch (PDOException $e) {
            // Lỗi UNIQUE constraint (email trùng)
            if ($e->getCode() == 23000) {
                throw new InvalidArgumentException('Email này đã được sử dụng!');
            }
            throw $e;
        }
    }

    /**
     * Kiểm tra user đã xác thực chưa
     */
    public function isUserVerified(string $userId): bool {
        $stmt = $this->conn->prepare('SELECT verification_status FROM Users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && $user['verification_status'] === 'Verified';
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(string $userId, string $oldPassword, string $newPassword): array {
        try {
            $stmt = $this->conn->prepare('SELECT password_hash FROM Users WHERE user_id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Người dùng không tồn tại!'];
            }

            if (!password_verify($oldPassword, $user['password_hash'])) {
                return ['status' => 'error', 'message' => 'Mật khẩu cũ không chính xác!'];
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->conn->prepare('UPDATE Users SET password_hash = ? WHERE user_id = ?');
            $updateStmt->execute([$newHash, $userId]);

            return ['status' => 'success', 'message' => 'Đổi mật khẩu thành công!'];
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
