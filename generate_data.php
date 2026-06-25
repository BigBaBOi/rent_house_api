<?php
require_once __DIR__ . '/src/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Helper to generate a random ID
function generateId($prefix) {
    return $prefix . '_' . uniqid();
}

// 1. Reset all passwords to '123'
$newPasswordHash = password_hash('123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE Users SET password_hash = ?");
$stmt->execute([$newPasswordHash]);

// 2. Add sample data if needed
// Check counts
$userCount = $conn->query("SELECT COUNT(*) FROM Users")->fetchColumn();
// Insert SuperAdmin
    $stmt = $conn->prepare("INSERT IGNORE INTO Users (user_id, email, password_hash, full_name, phone_number, role, verification_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([generateId('ADM'), 'admin@smartrent.com', $newPasswordHash, 'Hệ thống Admin', '0900000000', 'SuperAdmin', 'Verified']);
    
    // Insert 2 Owners
    $owner1Id = generateId('OWN');
    $owner2Id = generateId('OWN');
    $stmt->execute([$owner1Id, 'owner1@smartrent.com', $newPasswordHash, 'Chủ trọ Nguyễn Văn A', '0901111111', 'Owner', 'Verified']);
    $stmt->execute([$owner2Id, 'owner2@smartrent.com', $newPasswordHash, 'Chủ trọ Trần Thị B', '0902222222', 'Owner', 'Verified']);
    
    // Insert 5 Tenants
    $tenantIds = [];
    for ($i=1; $i<=5; $i++) {
        $tId = generateId('TEN');
        $tenantIds[] = $tId;
        $stmt->execute([$tId, "tenant$i@smartrent.com", $newPasswordHash, "Người thuê số $i", "090333333$i", 'Tenant', 'Verified']);
    }

    // Insert Hostels
    $hostel1Id = generateId('HST');
    $hostel2Id = generateId('HST');
    $stmtH = $conn->prepare("INSERT IGNORE INTO Hostels (hostel_id, owner_id, hostel_name, address, is_verified) VALUES (?, ?, ?, ?, ?)");
    $stmtH->execute([$hostel1Id, $owner1Id, 'Khu trọ Sinh viên Khang Điền', '123 Làng Đại Học, TP Thủ Đức', 1]);
    $stmtH->execute([$hostel2Id, $owner2Id, 'Trọ cao cấp Bình Thạnh', '456 Xô Viết Nghệ Tĩnh, Bình Thạnh', 1]);

    // Insert Rooms
    $stmtR = $conn->prepare("INSERT IGNORE INTO Rooms (room_id, hostel_id, room_number, price, status, current_tenant_id) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Hostel 1 - 5 rooms
    for ($i=1; $i<=5; $i++) {
        $tenantId = ($i <= 3) ? $tenantIds[$i-1] : null;
        $status = $tenantId ? 'Occupied' : 'Available';
        $stmtR->execute([generateId('ROM'), $hostel1Id, "10$i", 2500000, $status, $tenantId]);
    }

    // Hostel 2 - 5 rooms
    for ($i=1; $i<=5; $i++) {
        $tenantId = ($i == 1) ? $tenantIds[3] : (($i == 2) ? $tenantIds[4] : null);
        $status = $tenantId ? 'Occupied' : 'Available';
        $stmtR->execute([generateId('ROM'), $hostel2Id, "20$i", 4500000, $status, $tenantId]);
    }


// 3. Output users list
echo "Đã tạo dữ liệu mẫu thành công!\n\n";
echo "=== DANH SÁCH TÀI KHOẢN (Mật khẩu tất cả đều là: 123) ===\n\n";

$users = $conn->query("SELECT email, full_name, role FROM Users ORDER BY role, email")->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    printf("Vai trò: %-12s | Email: %-25s | Tên: %s\n", $u['role'], $u['email'], $u['full_name']);
}
