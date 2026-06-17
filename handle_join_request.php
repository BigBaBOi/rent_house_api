<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/JoinRequestController.php';

AuthHelper::setUpCors();

$controller = new JoinRequestController();
$controller->handleRequest();
