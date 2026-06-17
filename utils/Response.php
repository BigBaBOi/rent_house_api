<?php

class Response {
    public static function success($data = null, $message = "Success", $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error($message = "Error", $code = 500, $data = null) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function json($status, $message, $data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
