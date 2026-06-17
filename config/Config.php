<?php

class Config {
    const UPLOAD_DIR_AVATAR = __DIR__ . '/../uploads/avatars/';
    const UPLOAD_DIR_CCCD = __DIR__ . '/../uploads/cccd/';
    const UPLOAD_DIR_REPORTS = __DIR__ . '/../uploads/reports/';
    
    const UPLOAD_DIR_KYC = __DIR__ . '/../uploads/kyc/';

    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_CONFLICT = 409;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_INTERNAL_ERROR = 500;
}
