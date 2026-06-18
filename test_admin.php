<?php
require_once 'C:\xampp\htdocs\rent_house_api\src\AdminService.php';
require_once 'C:\xampp\htdocs\rent_house_api\src\OwnerVerificationService.php';

$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
$ownerVerifService = new OwnerVerificationService($conn);
$adminService = new AdminService($conn, $ownerVerifService);

$admin_id = 'USER_6a33ff46d131f';

echo "Is Admin? : " . ($adminService->isAdmin($admin_id) ? 'Yes' : 'No') . "\n";

$pending = $adminService->getPendingOwnerVerifications(50, 0);
echo "Pending owners:\n";
print_r($pending);
?>
