<?php

class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateRequired($value) {
        return !empty(trim($value));
    }

    public static function validatePhone($phone) {
        return preg_match('/^[0-9]{10,11}$/', $phone);
    }

    public static function getJsonInput() {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON payload', 400);
        }
        return $decoded;
    }

    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('trim', $input);
        }
        return trim($input);
    }

    public static function sanitizeFields(array $input, array $allowed) {
        return array_filter(
            $input,
            fn($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
