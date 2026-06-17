<?php

require_once __DIR__ . '/Model.php';

class Room extends Model {
    protected $table = 'Rooms';

    public function getRoomsByHostel($hostelId) {
        $sql = "SELECT room_id, room_number, hostel_id, current_tenant_id, status FROM {$this->table} WHERE hostel_id = ?";
        return $this->fetchAll($sql, [$hostelId]);
    }

    public function getRoomById($roomId) {
        $sql = "SELECT * FROM {$this->table} WHERE room_id = ? LIMIT 1";
        return $this->fetchOne($sql, [$roomId]);
    }

    public function getRoomByTenant($tenantId) {
        $sql = "SELECT * FROM {$this->table} WHERE current_tenant_id = ? LIMIT 1";
        return $this->fetchOne($sql, [$tenantId]);
    }

    public function updateRoom($roomId, $data) {
        $updates = [];
        $params = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $roomId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE room_id = ?";
        return $this->execute($sql, $params);
    }

    public function setTenant($roomId, $tenantId) {
        $sql = "UPDATE {$this->table} SET current_tenant_id = ?, status = 'Occupied' WHERE room_id = ?";
        return $this->execute($sql, [$tenantId, $roomId]);
    }

    public function getUnpaidBills($roomId) {
        $sql = "SELECT COUNT(*) FROM Bills WHERE room_id = ? AND is_paid = 0";
        return (int)$this->fetchColumn($sql, [$roomId]);
    }
}
