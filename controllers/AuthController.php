<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/AuthHelper.php';
require_once __DIR__ . '/../config/Config.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['email'] ?? '')) {
                Response::error('Email là bắt buộc', 400);
            }
            if (!Validator::validateEmail($data['email'])) {
                Response::error('Email không hợp lệ', 400);
            }
            if (!Validator::validateRequired($data['password'] ?? '')) {
                Response::error('Mật khẩu là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['full_name'] ?? '')) {
                Response::error('Họ tên là bắt buộc', 400);
            }

            $userId = AuthHelper::generateUserId();
            $this->userModel->createUser(
                $userId,
                $data['email'],
                $data['password'],
                $data['full_name'],
                $data['phone_number'] ?? null,
                $data['role'] ?? 'Tenant'
            );

            Response::success(['user_id' => $userId], 'Đăng ký thành công!', 201);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '23000') !== false) {
                Response::error('Email này đã được sử dụng!', 409);
            }
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function login() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['email'] ?? '')) {
                Response::error('Email là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['password'] ?? '')) {
                Response::error('Mật khẩu là bắt buộc', 400);
            }

            $user = $this->userModel->getUserByEmail($data['email']);
            
            if (!$user || $user['password_hash'] !== $data['password']) {
                Response::error('Sai email hoặc mật khẩu!', 401);
            }

            unset($user['password_hash']);
            Response::success($user, 'Đăng nhập thành công!');
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function forgotPassword() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['email'] ?? '')) {
                Response::error('Email là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['new_password'] ?? '')) {
                Response::error('Mật khẩu mới là bắt buộc', 400);
            }

            $user = $this->userModel->getUserByEmail($data['email']);
            if (!$user) {
                Response::error('Email không tồn tại', 404);
            }

            $this->userModel->updatePassword($data['email'], $data['new_password']);
            Response::success(['user_id' => $user['user_id']], 'Cập nhật mật khẩu thành công');
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function submitKYC() {
        try {
            $ownerId = trim($_POST['owner_id'] ?? '');
            $idCardNumber = trim($_POST['id_card_number'] ?? '');

            if (!$ownerId || !$idCardNumber) {
                Response::error('Thiếu owner_id hoặc id_card_number', 400);
            }

            if (!isset($_FILES['front_image']) || !isset($_FILES['back_image']) || !isset($_FILES['license_image'])) {
                Response::error('Thiếu các file ảnh', 400);
            }

            $this->userModel->beginTransaction();

            $frontPath = AuthHelper::uploadFile($_FILES['front_image'], Config::UPLOAD_DIR_KYC);
            $backPath = AuthHelper::uploadFile($_FILES['back_image'], Config::UPLOAD_DIR_KYC);
            $licensePath = AuthHelper::uploadFile($_FILES['license_image'], Config::UPLOAD_DIR_KYC);

            // INSERT OwnerVerifications
            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("INSERT INTO OwnerVerifications (owner_id, id_card_number, id_card_front_url, id_card_back_url, business_license_url, status) 
                                  VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$ownerId, $idCardNumber, $frontPath, $backPath, $licensePath]);

            $this->userModel->updateVerificationStatus($ownerId, 'Pending');
            $this->userModel->commit();

            Response::success(null, 'Nộp hồ sơ KYC thành công');
        } catch (Exception $e) {
            if ($this->userModel->inTransaction()) {
                $this->userModel->rollback();
            }
            Response::error($e->getMessage(), 500);
        }
    }
}
