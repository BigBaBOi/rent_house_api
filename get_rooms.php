<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/RoomController.php';

AuthHelper::setUpCors();

$hostelId = $_GET['hostel_id'] ?? null;
$controller = new RoomController();
$controller->getRooms($hostelId);
