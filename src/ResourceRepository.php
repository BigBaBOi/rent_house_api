<?php
class ResourceRepository {
    private $resources = [
        'users' => [
            'table' => 'Users',
            'primary' => 'user_id',
            'fields' => ['user_id', 'email', 'full_name', 'phone_number', 'role', 'verification_status', 'created_at']
        ],
        'owner_verifications' => [
            'table' => 'OwnerVerifications',
            'primary' => 'verify_id',
            'fields' => ['verify_id', 'owner_id', 'id_card_front_url', 'id_card_back_url', 'status', 'reviewed_by', 'review_note', 'created_at']
        ],
        'hostels' => [
            'table' => 'Hostels',
            'primary' => 'hostel_id',
            'fields' => ['hostel_id', 'owner_id', 'hostel_name', 'address', 'is_verified', 'created_at']
        ],
        'rooms' => [
            'table' => 'Rooms',
            'primary' => 'room_id',
            'fields' => ['room_id', 'hostel_id', 'room_number', 'price', 'status', 'current_tenant_id']
        ],
        'join_requests' => [
            'table' => 'JoinRequests',
            'primary' => 'request_id',
            'fields' => ['request_id', 'tenant_id', 'hostel_id', 'room_id', 'status', 'requested_at']
        ],
        'alarms' => [
            'table' => 'Alarms',
            'primary' => 'alarm_id',
            'fields' => ['alarm_id', 'hostel_id', 'triggered_by', 'alarm_type', 'status', 'triggered_at', 'resolved_at']
        ],
        'alarm_responses' => [
            'table' => 'AlarmResponses',
            'primary' => 'response_id',
            'fields' => ['response_id', 'alarm_id', 'tenant_id', 'room_id', 'is_safe', 'responded_at']
        ],
        'bills' => [
            'table' => 'bills',
            'primary' => 'bill_id',
            'fields' => ['bill_id', 'room_id', 'billing_month', 'electric_index', 'water_index', 'total_amount', 'is_paid', 'created_at', 'paid_at']
        ],
        'notifications' => [
            'table' => 'notifications',
            'primary' => 'notification_id',
            'fields' => ['notification_id', 'hostel_id', 'title', 'content', 'created_at']
        ]
    ];

    public function getResource($name) {
        if (!isset($this->resources[$name])) {
            return null;
        }

        return $this->resources[$name];
    }

    public function isResourceSupported($name) {
        return isset($this->resources[$name]);
    }
}
