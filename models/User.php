<?php

require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'Users';

    public function createUser($userId, $email, $password, $fullName, $phoneNumber, $role = 'Tenant') {
        $sql = "INSERT INTO {$this->table} (user_id, email, password_hash, full_name, phone_number, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $this->execute($sql, [$userId, $email, $password, $fullName, $phoneNumber, $role]);
        return $userId;
    }

    public function getUserByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        return $this->fetchOne($sql, [$email]);
    }

    public function getUserById($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? LIMIT 1";
        return $this->fetchOne($sql, [$userId]);
    }

    public function updateVerificationStatus($userId, $status) {
        $sql = "UPDATE {$this->table} SET verification_status = ? WHERE user_id = ?";
        return $this->execute($sql, [$status, $userId]);
    }

    public function updatePassword($email, $password) {
        $sql = "UPDATE {$this->table} SET password_hash = ? WHERE email = ?";
        return $this->execute($sql, [$password, $email]);
    }

    public function getCountVerifiedOwners() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE verification_status = 'Verified' AND role = 'Owner'";
        return (int)$this->fetchColumn($sql);
    }
}
