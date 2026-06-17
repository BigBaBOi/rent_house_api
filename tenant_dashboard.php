<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/DashboardController.php';

AuthHelper::setUpCors();

$tenantId = $_GET['tenant_id'] ?? null;
$controller = new DashboardController();
$controller->tenantDashboard($tenantId);
