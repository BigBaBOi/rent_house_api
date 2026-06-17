<?php

require_once __DIR__ . '/../models/Hostel.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../config/Database.php';

class DashboardController {
    private $hostelModel;
    private $roomModel;
    private $billModel;

    public function __construct() {
        $this->hostelModel = new Hostel();
        $this->roomModel = new Room();
        $this->billModel = new Bill();
    }

    public function ownerDashboard($ownerId) {
        try {
            if (!$ownerId) {
                Response::error('owner_id là bắt buộc', 400);
            }

            $hostels = $this->hostelModel->getHostelsByOwner($ownerId);
            $rooms = [];
            $stats = ['empty' => 0, 'occupied' => 0, 'issues' => 0];
            $totalRevenue = 0;

            if (!empty($hostels)) {
                $hostelIds = array_column($hostels, 'hostel_id');
                
                // Lấy tất cả rooms
                foreach ($hostelIds as $hostelId) {
                    $hostelRooms = $this->roomModel->getRoomsByHostel($hostelId);
                    $rooms = array_merge($rooms, $hostelRooms);
                }

                // Tính doanh thu
                $now = new DateTime();
                $month = (int)$now->format('m');
                $year = (int)$now->format('Y');
                $totalRevenue = $this->billModel->getTotalRevenue($hostelIds, $month, $year);

                // Xử lý trạng thái phòng
                foreach ($rooms as &$room) {
                    $ui_status = 'TRỐNG';
                    if (!empty($room['current_tenant_id'])) {
                        $unpaidCount = $this->roomModel->getUnpaidBills($room['room_id']);
                        if ($unpaidCount > 0) {
                            $ui_status = 'NỢ TIỀN';
                            $stats['issues'] += 1;
                        } else {
                            $ui_status = 'ĐÃ THUÊ';
                            $stats['occupied'] += 1;
                        }
                    } else {
                        $stats['empty'] += 1;
                    }
                    
                    $room['ui_status'] = $ui_status;
                    unset($room['status']);
                    unset($room['hostel_id']);
                }
            }

            Response::success([
                'total_revenue' => $totalRevenue,
                'stats' => $stats,
                'rooms' => $rooms
            ]);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function tenantDashboard($tenantId) {
        try {
            if (!$tenantId) {
                Response::error('tenant_id là bắt buộc', 400);
            }

            $room = $this->roomModel->getRoomByTenant($tenantId);
            if (!$room) {
                Response::error('Không tìm thấy phòng cho tenant này', 404);
            }

            $unpaidBill = $this->billModel->getUnpaidBill($room['room_id']);

            $notifications = [
                ['title' => 'Bảo trì hệ thống nước', 'time' => '2 GIỜ TRƯỚC'],
                ['title' => 'Nhắc nhở nội quy PCCC', 'time' => 'HÔM QUA']
            ];

            Response::success([
                'room_number' => $room['room_number'],
                'start_date' => null,
                'unpaid_bill' => $unpaidBill,
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
