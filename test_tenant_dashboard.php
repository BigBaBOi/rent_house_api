<?php
$_GET['action'] = 'get_tenant_dashboard';
$_GET['tenant_id'] = 'U-c66a4bc4'; // Need a valid tenant_id, let's just use a dummy one or find one

// Find a tenant
$c = new PDO('mysql:host=localhost;dbname=rent_house', 'root', '');
$stmt = $c->query("SELECT user_id FROM Users WHERE role = 'Tenant' LIMIT 1");
$user = $stmt->fetch();
$tenant_id = $user ? $user['user_id'] : '123';

echo "Testing with tenant_id: $tenant_id\n";
$_GET['tenant_id'] = $tenant_id;

require 'src/Database.php';
require 'src/Request.php';
require 'src/Response.php';
require 'src/ResourceRepository.php';
require 'src/OwnerVerificationService.php';
require 'src/UserRegistrationService.php';
require 'src/AdminService.php';
require 'src/ApiController.php';

$db = new Database();
$request = new Request();
$repo = new ResourceRepository($db->getConnection());
$controller = new ApiController($db, $request, $repo);

ob_start();
$controller->handle();
$out = ob_get_clean();
echo $out;
