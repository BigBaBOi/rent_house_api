<?php

require_once __DIR__ . '/Model.php';

class Hostel extends Model {
    protected $table = 'Hostels';

    public function getHostelsByOwner($ownerId) {
        $sql = "SELECT hostel_id FROM {$this->table} WHERE owner_id = ?";
        return $this->fetchAll($sql, [$ownerId]);
    }

    public function getHostelById($hostelId) {
        $sql = "SELECT * FROM {$this->table} WHERE hostel_id = ? LIMIT 1";
        return $this->fetchOne($sql, [$hostelId]);
    }

    public function createHostel($hostelId, $ownerId, $hostelName, $address) {
        $sql = "INSERT INTO {$this->table} (hostel_id, owner_id, hostel_name, address, is_verified) 
                VALUES (?, ?, ?, ?, 0)";
        $this->execute($sql, [$hostelId, $ownerId, $hostelName, $address]);
        return $hostelId;
    }
}
