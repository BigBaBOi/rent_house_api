<?php
$conn = new PDO('mysql:host=localhost;dbname=rent_house;charset=utf8','root','');
$ownerId = 'USER_6a335d10c3b20';

$conn->query("UPDATE Users SET verification_status='Pending' WHERE user_id='$ownerId'");
echo "Updated Owner verification_status to Pending.\n";

$stmt = $conn->query("SELECT * FROM OwnerVerifications WHERE owner_id='$ownerId'");
if ($stmt->rowCount() == 0) {
    $conn->query("INSERT INTO OwnerVerifications (owner_id, id_card_front_url, id_card_back_url, status, created_at) VALUES ('$ownerId', 'front.jpg', 'back.jpg', 'Pending', NOW())");
    echo "Inserted mock OwnerVerification request.\n";
} else {
    $conn->query("UPDATE OwnerVerifications SET status='Pending' WHERE owner_id='$ownerId'");
    echo "Updated existing OwnerVerification to Pending.\n";
}

require_once 'C:\xampp\htdocs\rent_house_api\src\OwnerVerificationService.php';
require_once 'C:\xampp\htdocs\rent_house_api\src\AdminService.php';

$ownerVerifService = new OwnerVerificationService($conn);
$adminService = new AdminService($conn, $ownerVerifService);

$pending = $adminService->getPendingOwnerVerifications(50, 0);
echo "Pending owners count: " . count($pending) . "\n";
print_r($pending);
?>
