<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthHelper::setUpCors();

$controller = new AuthController();
$controller->forgotPassword();
