<?php
require 'src/Database.php';

$db = new Database();
$conn = $db->getConnection();

$passwordHash = password_hash('123', PASSWORD_BCRYPT);
$created_at = date('Y-m-d H:i:s');

// Update all existing accounts to password 123
$stmt = $conn->prepare("UPDATE Users SET password_hash = ?");
$stmt->execute([$passwordHash]);
echo "Updated all existing accounts password to '123'.\n";

$owners = [
    ['email' => 'mock_owner1@smartrent.com', 'name' => 'Mock Owner 1', 'phone' => '0901234561'],
    ['email' => 'mock_owner2@smartrent.com', 'name' => 'Mock Owner 2', 'phone' => '0901234562']
];

$ownerIds = [];
$accounts = [];

foreach ($owners as $o) {
    // Check if exists
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
    $stmt->execute([$o['email']]);
    if ($stmt->fetch()) {
        continue;
    }
    
    $stmt = $conn->prepare("INSERT INTO Users (user_id, email, password_hash, full_name, phone_number, role, verification_status, created_at) VALUES (?, ?, ?, ?, ?, 'Owner', 'Verified', ?)");
    $id = uniqid('ow_');
    $stmt->execute([$id, $o['email'], $passwordHash, $o['name'], $o['phone'], $created_at]);
    $ownerIds[] = $id;
    $accounts[] = ['email' => $o['email'], 'role' => 'Owner', 'password' => '123'];
    
    $hostel_id = uniqid('h_');
    $stmt = $conn->prepare("INSERT INTO Hostels (hostel_id, owner_id, hostel_name, address, is_verified, created_at) VALUES (?, ?, ?, ?, 1, ?)");
    $stmt->execute([$hostel_id, $id, 'Khu Trọ ' . $o['name'], 'Địa chỉ ' . $o['name'], $created_at]);
    
    $room1_id = uniqid('r_');
    $stmt = $conn->prepare("INSERT INTO Rooms (room_id, hostel_id, room_number, price, status) VALUES (?, ?, ?, 2000000, 'Available')");
    $stmt->execute([$room1_id, $hostel_id, '101']);
    
    $room2_id = uniqid('r_');
    $stmt = $conn->prepare("INSERT INTO Rooms (room_id, hostel_id, room_number, price, status) VALUES (?, ?, ?, 2500000, 'Available')");
    $stmt->execute([$room2_id, $hostel_id, '102']);
}

$tenants = [
    ['email' => 'mock_tenant1@smartrent.com', 'name' => 'Mock Tenant 1', 'phone' => '0909876541'],
    ['email' => 'mock_tenant2@smartrent.com', 'name' => 'Mock Tenant 2', 'phone' => '0909876542'],
    ['email' => 'mock_tenant3@smartrent.com', 'name' => 'Mock Tenant 3', 'phone' => '0909876543']
];

foreach ($tenants as $t) {
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
    $stmt->execute([$t['email']]);
    if ($stmt->fetch()) {
        continue;
    }
    
    $stmt = $conn->prepare("INSERT INTO Users (user_id, email, password_hash, full_name, phone_number, role, verification_status, created_at) VALUES (?, ?, ?, ?, ?, 'Tenant', 'Verified', ?)");
    $id = uniqid('te_');
    $stmt->execute([$id, $t['email'], $passwordHash, $t['name'], $t['phone'], $created_at]);
    $accounts[] = ['email' => $t['email'], 'role' => 'Tenant', 'password' => '123'];
}

echo "Created the following NEW accounts:\n";
foreach ($accounts as $acc) {
    echo "Email: " . $acc['email'] . " | Role: " . $acc['role'] . " | Password: " . $acc['password'] . "\n";
}

?>
