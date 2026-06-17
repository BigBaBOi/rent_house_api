<?php

class AuthHelper {
    public static function generateUserId() {
        return uniqid('USER_');
    }

    public static function uploadFile(array $file, string $targetFolder) {
        require_once __DIR__ . '/../config/Config.php';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file thất bại: ' . $file['error']);
        }

        if (!is_dir($targetFolder) && !mkdir($targetFolder, 0755, true)) {
            throw new RuntimeException('Không tạo được thư mục upload');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('file_', true) . '.' . ($extension ?: 'jpg');
        $destination = rtrim($targetFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Không di chuyển được file upload');
        }

        return $destination;
    }

    public static function setUpCors() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Accept");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Content-Type: application/json; charset=UTF-8");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
