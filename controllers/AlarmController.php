<?php

require_once __DIR__ . '/../models/Alarm.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class AlarmController {
    private $alarmModel;

    public function __construct() {
        $this->alarmModel = new Alarm();
    }

    public function createAlarm() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['hostel_id'] ?? '')) {
                Response::error('hostel_id là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['triggered_by'] ?? '')) {
                Response::error('triggered_by là bắt buộc', 400);
            }

            $alarmId = $this->alarmModel->createAlarm(
                $data['hostel_id'],
                $data['triggered_by'],
                $data['alarm_type'] ?? 'Fire'
            );

            Response::success(['alarm_id' => $alarmId], 'Báo động đã được tạo', 201);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getAlarms($hostelId) {
        try {
            if (!$hostelId) {
                Response::error('hostelId là bắt buộc', 400);
            }
            $alarms = $this->alarmModel->getAlarmsByHostel($hostelId);
            Response::success($alarms);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function resolveAlarm($alarmId) {
        try {
            if (!$alarmId) {
                Response::error('alarmId là bắt buộc', 400);
            }
            $this->alarmModel->resolveAlarm($alarmId);
            Response::success(null, 'Báo động đã được giải quyết');
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
