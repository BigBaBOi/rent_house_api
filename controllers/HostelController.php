<?php

require_once __DIR__ . '/../models/Hostel.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class HostelController {
    private $hostelModel;
    private $roomModel;

    public function __construct() {
        $this->hostelModel = new Hostel();
        $this->roomModel = new Room();
    }

    public function getHostels($ownerId) {
        try {
            if (!$ownerId) {
                Response::error('ownerId là bắt buộc', 400);
            }
            $hostels = $this->hostelModel->getHostelsByOwner($ownerId);
            Response::success($hostels);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getHostelDetail($hostelId) {
        try {
            if (!$hostelId) {
                Response::error('hostelId là bắt buộc', 400);
            }
            $hostel = $this->hostelModel->getHostelById($hostelId);
            if (!$hostel) {
                Response::error('Không tìm thấy hostel', 404);
            }
            Response::success($hostel);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
