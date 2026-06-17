# Rent House API - OOP Architecture

## 📁 Cấu Trúc Thư Mục

```
rent_house_api/
├── config/
│   ├── Database.php         # Singleton PDO connection
│   └── Config.php           # Configuration constants
├── controllers/             # Business logic layer
│   ├── AuthController.php
│   ├── UserController.php
│   ├── RoomController.php
│   ├── BillController.php
│   ├── AlarmController.php
│   ├── HostelController.php
│   ├── DashboardController.php
│   └── JoinRequestController.php
├── models/                  # Data layer (extends Model)
│   ├── Model.php           # Base abstract class
│   ├── User.php
│   ├── Room.php
│   ├── Bill.php
│   ├── Alarm.php
│   └── Hostel.php
├── utils/
│   ├── Response.php        # JSON response helper
│   ├── Validator.php       # Input validation & JSON parsing
│   └── AuthHelper.php      # File upload, CORS setup
├── uploads/
│   ├── avatars/
│   ├── cccd/
│   ├── kyc/
│   └── reports/
├── api.php                 # (DEPRECATED - Use entry points below)
├── login.php               # POST - Entry point for login
├── register.php            # POST - Entry point for register
├── forgot_password.php     # POST - Reset password
├── submit_kyc.php          # POST - KYC submission
├── owner_dashboard.php     # GET - Owner dashboard
├── tenant_dashboard.php    # GET - Tenant dashboard
├── get_rooms.php           # GET - List rooms by hostel
├── get_room_detail.php     # GET - Single room detail
├── get_profile.php         # GET - User profile
├── create_bill.php         # POST - Create bill
├── get_bills.php           # GET - List bills by room
├── create_alarm.php        # POST - Create alarm
├── get_alarms.php          # GET - List alarms by hostel
├── send_join_request.php   # POST - Send join request
├── get_join_requests.php   # GET - List pending join requests
├── handle_join_request.php # POST - Approve/Reject join request
└── README.md               # This file
```

## 🚀 API Endpoints

### Authentication

#### **POST /register.php**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "John Doe",
  "phone_number": "0123456789",
  "role": "Tenant"  // or "Owner"
}
```
Response: `{ "status": "success", "data": { "user_id": "USER_..." }, ... }`

#### **POST /login.php**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

#### **POST /forgot_password.php**
```json
{
  "email": "user@example.com",
  "new_password": "newpassword123"
}
```

#### **POST /submit_kyc.php** (multipart/form-data)
- `owner_id`: text
- `id_card_number`: text
- `front_image`: file (JPEG/PNG)
- `back_image`: file (JPEG/PNG)
- `license_image`: file (JPEG/PNG)

### Dashboard

#### **GET /owner_dashboard.php?owner_id=USER_xxx**
Response: Dashboard với tổng doanh thu, số phòng theo trạng thái, danh sách phòng

#### **GET /tenant_dashboard.php?tenant_id=USER_xxx**
Response: Thông tin phòng, hóa đơn chưa thanh toán, thông báo

### Rooms

#### **GET /get_rooms.php?hostel_id=HOSTEL_xxx**
Response: Danh sách phòng của khu trọ

#### **GET /get_room_detail.php?room_id=R_xxx**
Response: Chi tiết một phòng

### Bills

#### **POST /create_bill.php**
```json
{
  "room_id": "R_xxx",
  "billing_month": "Tháng 11",
  "total_amount": 3500000,
  "electric_index": 100,
  "water_index": 50
}
```

#### **GET /get_bills.php?room_id=R_xxx**
Response: Danh sách hóa đơn của phòng

### Alarms

#### **POST /create_alarm.php**
```json
{
  "hostel_id": "HOSTEL_xxx",
  "triggered_by": "USER_xxx",
  "alarm_type": "Fire"  // or "Security", "Medical"
}
```

#### **GET /get_alarms.php?hostel_id=HOSTEL_xxx**
Response: Danh sách báo động

### Join Requests

#### **POST /send_join_request.php**
```json
{
  "tenant_id": "USER_xxx",
  "hostel_id": "HOSTEL_xxx"
}
```

#### **GET /get_join_requests.php?owner_id=USER_xxx**
Response: Danh sách yêu cầu gia nhập chờ duyệt

#### **POST /handle_join_request.php**
```json
{
  "request_id": 1,
  "action_status": "Accepted",  // or "Rejected"
  "room_id": "R_xxx",
  "tenant_id": "USER_xxx"
}
```

## 🏗️ Architecture Overview

### Model Layer
- **Base Model**: Abstract class với methods query, fetchAll, fetchOne, transaction support
- **Specific Models**: User, Room, Bill, Alarm, Hostel extends Model

### Controller Layer
- Xử lý business logic
- Gọi Model methods để CRUD database
- Trả về JSON responses qua Response helper

### Utils
- **Response.php**: Centralized JSON response formatting
- **Validator.php**: Input validation, JSON parsing, field sanitization
- **AuthHelper.php**: File upload, CORS headers, user ID generation

### Entry Points
- Mỗi file `.php` ở root directory là một entry point
- Gọi CORS setup, instantiate controller, gọi action method

## 🔄 Request/Response Flow

```
HTTP Request
    ↓
Entry Point File (e.g., login.php)
    ↓
AuthHelper::setUpCors()
    ↓
Initialize Controller
    ↓
Call Controller Method
    ↓
Controller → Validator::getJsonInput()
    ↓
Controller → Model Methods
    ↓
Model → PDO Queries
    ↓
Response::success() or Response::error()
    ↓
JSON Response
```

## 📦 Database Setup

Sử dụng `db_structure.md` để tạo tables:
- Users
- Hostels
- Rooms
- Bills
- Alarms
- JoinRequests
- OwnerVerifications
- AlarmResponses

## 🔐 Security Notes

- Passwords lưu plaintext (DEBUG MODE - không dùng production)
- CORS cho phép all origins
- Input validation ở controller level
- Use prepared statements (PDO) để tránh SQL injection

## 🛠️ Development

### Thêm New Endpoint

1. **Tạo Model method** (nếu cần) trong `models/XxxModel.php`
2. **Tạo Controller method** trong `controllers/XxxController.php`
3. **Tạo Entry Point file** `xxx_action.php` ở root

Example:
```php
// models/User.php - thêm method
public function updateProfile($userId, $data) {
    // logic...
}

// controllers/UserController.php - thêm method
public function updateProfile() {
    $data = Validator::getJsonInput();
    $userId = $data['user_id'] ?? null;
    $this->userModel->updateProfile($userId, $data);
    Response::success(null, 'Profile updated');
}

// update_profile.php - entry point
require_once __DIR__ . '/controllers/UserController.php';
$controller = new UserController();
$controller->updateProfile();
```

## 📝 Notes

- Old `api.php` is deprecated - sử dụng individual entry points
- Transaction support trong Model base class
- Mỗi model tự quản lý bảng của nó
- Database singleton pattern để reuse connection
