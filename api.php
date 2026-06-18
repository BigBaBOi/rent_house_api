<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Request.php';
require_once __DIR__ . '/src/Response.php';
require_once __DIR__ . '/src/ResourceRepository.php';
require_once __DIR__ . '/src/OwnerVerificationService.php';
require_once __DIR__ . '/src/UserRegistrationService.php';
require_once __DIR__ . '/src/AdminService.php';
require_once __DIR__ . '/src/ApiController.php';

$request = new Request();
$database = new Database();
$resourceRepository = new ResourceRepository();
$apiController = new ApiController($database, $request, $resourceRepository);
$apiController->handle();
