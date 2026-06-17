<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/AlarmController.php';

AuthHelper::setUpCors();

$controller = new AlarmController();
$controller->createAlarm();
