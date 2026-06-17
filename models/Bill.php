<?php

require_once __DIR__ . '/Model.php';

class Bill extends Model {
    protected $table = 'Bills';

    public function createBill($roomId, $billingMonth, $totalAmount, $electricIndex = null, $waterIndex = null) {
        $sql = "INSERT INTO {$this->table} (room_id, billing_month, total_amount, electric_index, water_index, is_paid) 
                VALUES (?, ?, ?, ?, ?, 0)";
        $this->execute($sql, [$roomId, $billingMonth, $totalAmount, $electricIndex, $waterIndex]);
        return $this->conn->lastInsertId();
    }

    public function getBillsForRoom($roomId) {
        $sql = "SELECT * FROM {$this->table} WHERE room_id = ? ORDER BY created_at DESC";
        return $this->fetchAll($sql, [$roomId]);
    }

    public function getUnpaidBill($roomId) {
        $sql = "SELECT billing_month, total_amount, created_at FROM {$this->table} 
                WHERE room_id = ? AND is_paid = 0 ORDER BY created_at DESC LIMIT 1";
        return $this->fetchOne($sql, [$roomId]);
    }

    public function getTotalRevenue($hostelIds, $month, $year) {
        $placeholders = implode(', ', array_fill(0, count($hostelIds), '?'));
        $roomIds = [];
        foreach ($hostelIds as $id) {
            $sql = "SELECT room_id FROM Rooms WHERE hostel_id = ?";
            $rooms = $this->fetchAll($sql, [$id]);
            foreach ($rooms as $room) {
                $roomIds[] = $room['room_id'];
            }
        }

        if (empty($roomIds)) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($roomIds), '?'));
        $sql = "SELECT SUM(total_amount) FROM {$this->table} 
                WHERE is_paid = 1 AND MONTH(created_at) = ? AND YEAR(created_at) = ? 
                AND room_id IN ($placeholders)";
        $params = array_merge([$month, $year], $roomIds);
        $result = $this->fetchColumn($sql, $params);
        return (float)($result ?? 0);
    }

    public function markAsPaid($billId) {
        $sql = "UPDATE {$this->table} SET is_paid = 1 WHERE bill_id = ?";
        return $this->execute($sql, [$billId]);
    }
}
