<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/DashboardController.php';

AuthHelper::setUpCors();

$ownerId = $_GET['owner_id'] ?? null;
$controller = new DashboardController();
$controller->ownerDashboard($ownerId);
