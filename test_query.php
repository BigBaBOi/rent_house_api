<?php
require 'src/Database.php';
$db = new Database();
$conn = $db->getConnection();
$sql = "SELECT b.bill_id, b.room_id, r.room_number, h.hostel_name, u.user_id as tenant_id, u.full_name as tenant_name, b.billing_month, b.old_electric, b.electric_index, b.old_water, b.water_index, b.rent_amount, b.service_amount, b.total_amount, b.is_paid, b.created_at, b.paid_at 
        FROM bills b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN hostels h ON r.hostel_id = h.hostel_id
        LEFT JOIN users u ON r.current_tenant_id = u.user_id
        ORDER BY b.created_at DESC";
$stmt = $conn->query($sql);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
