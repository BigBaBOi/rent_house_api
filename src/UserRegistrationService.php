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
            $verificationStatus = 'verified'; // Tenant tự động xác thực
            
            // Nếu đăng ký là chủ trọ, cần xác thực từ admin
            if ($role === 'owner') {
                $verificationStatus = 'pending_verification';
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

            // Nếu là chủ trọ, tạo request xác thực
            if ($role === 'owner') {
                $this->ownerVerificationService->createVerificationRequest($userId, $data);
                
                return [
                    'status' => 'success',
                    'message' => 'Đăng ký thành công! Vui lòng chờ admin xác thực tài khoản chủ trọ.',
                    'user_id' => $userId,
                    'verification_status' => 'pending_verification'
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Đăng ký thành công!',
                'user_id' => $userId,
                'verification_status' => 'verified'
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
        
        return $user && $user['verification_status'] === 'verified';
    }
}
