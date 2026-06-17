<?php

require_once __DIR__ . '/utils/AuthHelper.php';
require_once __DIR__ . '/controllers/BillController.php';

AuthHelper::setUpCors();

$controller = new BillController();
$controller->createBill();
