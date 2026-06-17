<?php

require_once __DIR__ . '/Model.php';

class Alarm extends Model {
    protected $table = 'Alarms';

    public function createAlarm($hostelId, $triggeredBy, $alarmType = 'Fire') {
        $sql = "INSERT INTO {$this->table} (hostel_id, triggered_by, alarm_type, status) 
                VALUES (?, ?, ?, 'Active')";
        $this->execute($sql, [$hostelId, $triggeredBy, $alarmType]);
        return $this->conn->lastInsertId();
    }

    public function getAlarmsByHostel($hostelId) {
        $sql = "SELECT * FROM {$this->table} WHERE hostel_id = ? ORDER BY triggered_at DESC";
        return $this->fetchAll($sql, [$hostelId]);
    }

    public function resolveAlarm($alarmId) {
        $sql = "UPDATE {$this->table} SET status = 'Resolved', resolved_at = NOW() WHERE alarm_id = ?";
        return $this->execute($sql, [$alarmId]);
    }
}
