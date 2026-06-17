<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/BillController.php';

AuthHelper::setUpCors();

$roomId = $_GET['room_id'] ?? null;
$controller = new BillController();
$controller->getBills($roomId);
