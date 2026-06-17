<?php

require_once __DIR__ . '/../models/Bill.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class BillController {
    private $billModel;

    public function __construct() {
        $this->billModel = new Bill();
    }

    public function createBill() {
        try {
            $data = Validator::getJsonInput();
            
            if (!Validator::validateRequired($data['room_id'] ?? '')) {
                Response::error('room_id là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['billing_month'] ?? '')) {
                Response::error('billing_month là bắt buộc', 400);
            }
            if (!Validator::validateRequired($data['total_amount'] ?? '')) {
                Response::error('total_amount là bắt buộc', 400);
            }

            $billId = $this->billModel->createBill(
                $data['room_id'],
                $data['billing_month'],
                $data['total_amount'],
                $data['electric_index'] ?? null,
                $data['water_index'] ?? null
            );

            Response::success(['bill_id' => $billId], 'Tạo hóa đơn thành công', 201);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getBills($roomId) {
        try {
            if (!$roomId) {
                Response::error('roomId là bắt buộc', 400);
            }
            $bills = $this->billModel->getBillsForRoom($roomId);
            Response::success($bills);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function getUnpaidBill($roomId) {
        try {
            if (!$roomId) {
                Response::error('roomId là bắt buộc', 400);
            }
            $bill = $this->billModel->getUnpaidBill($roomId);
            Response::success($bill);
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function markAsPaid() {
        try {
            $data = Validator::getJsonInput();
            if (!Validator::validateRequired($data['bill_id'] ?? '')) {
                Response::error('bill_id là bắt buộc', 400);
            }
            $this->billModel->markAsPaid($data['bill_id']);
            Response::success(null, 'Đánh dấu thanh toán thành công');
        } catch (Exception $e) {
            Response::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
