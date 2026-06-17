<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/RoomController.php';

AuthHelper::setUpCors();

$roomId = $_GET['room_id'] ?? null;
$controller = new RoomController();
$controller->getRoomDetail($roomId);
