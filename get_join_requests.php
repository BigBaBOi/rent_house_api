<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/JoinRequestController.php';

AuthHelper::setUpCors();

$ownerId = $_GET['owner_id'] ?? null;
$controller = new JoinRequestController();
$controller->getOwnerRequests($ownerId);
