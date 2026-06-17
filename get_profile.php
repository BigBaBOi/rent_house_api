<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/UserController.php';

AuthHelper::setUpCors();

$userId = $_GET['user_id'] ?? null;
$controller = new UserController();
$controller->getUserProfile($userId);
