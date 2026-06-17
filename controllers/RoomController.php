<?php

require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../models/Hostel.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class RoomController {
    private $roomModel;
    private $billModel;
    private $hostelModel;

    public function __construct() {
        $this->roomModel = new Room();
        $this->billModel = new Bill();
        $this->hostelModel = new Hostel();
    }

    public function getRooms($hostelId = null) {
        try {
            if (!$hostelId) {
                Response::error('hostelId là bắt buộc', 400);
            }
            $rooms = $this->roomModel->getRoomsByHostel($hostelId);
            Response::success($rooms);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getRoomDetail($roomId) {
        try {
            if (!$roomId) {
                Response::error('roomId là bắt buộc', 400);
            }
            $room = $this->roomModel->getRoomById($roomId);
            if (!$room) {
                Response::error('Không tìm thấy phòng', 404);
            }
            Response::success($room);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getTenantRoom($tenantId) {
        try {
            if (!$tenantId) {
                Response::error('tenantId là bắt buộc', 400);
            }
            $room = $this->roomModel->getRoomByTenant($tenantId);
            if (!$room) {
                Response::error('Không tìm thấy phòng cho tenant này', 404);
            }
            Response::success($room);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
