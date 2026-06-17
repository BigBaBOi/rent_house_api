<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/AlarmController.php';

AuthHelper::setUpCors();

$hostelId = $_GET['hostel_id'] ?? null;
$controller = new AlarmController();
$controller->getAlarms($hostelId);
