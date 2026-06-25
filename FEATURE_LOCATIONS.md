# 🏠 RENT HOUSE API - VỊ TRÍ CÁC CHỨC NĂNG CHÍNH

---

## 📍 ENTRY POINT - ĐIỂM VÀO CHÍNH

| Feature | File | Line | Mô tả |
|---------|------|------|-------|
| **ENTRY POINT: API Router** | `api.php` | 1-20 | Khởi tạo tất cả service, bật CORS, điều hướng request |

---

## 🔐 FEATURE 1: USER AUTHENTICATION & REGISTRATION - XÁC THỰC NGƯỜI DÙNG

### 1A. Register User (Đăng ký người dùng)
- **File**: `src/UserRegistrationService.php` (chứa logic)
- **Handler**: `src/ApiController.php` → `registerUser()` method
- **Route**: `api.php?action=register_user` (POST)
- **Mô tả**: 
  - Tạo user mới với Tenant (tự động Verified) hoặc Owner (cần xác thực admin)
  - Hash password sử dụng PASSWORD_DEFAULT
  - Generate user_id duy nhất với prefix `USER_`

### 1B. Login User (Đăng nhập)
- **File**: `src/ApiController.php` → `loginUser()` method
- **Route**: `api.php?action=login_user` (POST)
- **Mô tả**:
  - Xác thực email & password
  - Trả về user info nếu đúng, 401 nếu sai

### 1C. Verification Check (Kiểm tra xác thực)
- **File**: `src/UserRegistrationService.php` → `isUserVerified()` method
- **Route**: Dùng nội bộ
- **Mô tả**: Kiểm tra user đã xác thực hay chưa

### 1D. Change Password (Đổi mật khẩu)
- **File**: `src/UserRegistrationService.php` → `changePassword()` method
- **Handler**: `src/ApiController.php` → `changePassword()` method
- **Route**: `api.php?action=change_password` (POST)
- **Mô tả**:
  - Xác thực mật khẩu cũ trước khi đổi
  - Hash mật khẩu mới sử dụng PASSWORD_DEFAULT

### 1E. Get User Status (Lấy trạng thái người dùng)
- **File**: `src/ApiController.php` → `getUserStatus()` method
- **Route**: `api.php?action=get_user_status` (POST)
- **Mô tả**: Trả về verification_status của user

---

## ✅ FEATURE 2: OWNER VERIFICATION SYSTEM - QUẢN LÝ XÁC THỰC CHỦ TRỌ

### 2A. Create Verification Request (Gửi yêu cầu xác thực)
- **File**: `src/OwnerVerificationService.php` → `createVerificationRequest()` method
- **Handler**: `src/ApiController.php` → `submitOwnerVerification()` method
- **Route**: `api.php?action=submit_owner_verification` (POST)
- **Yêu cầu**: 
  - user_id
  - id_card_front_url, id_card_back_url
  - full_name_on_doc, id_number
  - business_license_url (optional)
- **Mô tả**:
  - Owner gửi CMND/CCCD, ảnh khuôn mặt, giấy phép kinh doanh
  - Tạo bản ghi trong bảng `OwnerVerifications` với status = Pending

### 2B. Get Verification Status (Lấy trạng thái xác thực)
- **File**: `src/OwnerVerificationService.php` → `getVerificationStatus()` method
- **Mô tả**: Lấy bản ghi xác thực mới nhất của owner

### 2C. Update Verification Status (Cập nhật trạng thái)
- **File**: `src/OwnerVerificationService.php` → `updateVerificationStatus()` method
- **Mô tả**:
  - Cập nhật status: Pending → Approved/Rejected
  - Cập nhật user verification_status tương ứng
  - Thêm review_note từ admin

### 2D. Get Pending Verifications (Lấy danh sách chờ duyệt)
- **File**: `src/OwnerVerificationService.php` → `getPendingVerifications()` method
- **Mô tả**: Lấy danh sách owner chờ xác thực (pagination)

### 2E. Submit Owner Verification Handler
- **File**: `src/ApiController.php` → `submitOwnerVerification()` method
- **Route**: `api.php?action=submit_owner_verification` (POST)

---

## 👨‍💼 FEATURE 3: ADMIN VERIFICATION CONTROL - QUẢN LÝ DUYỆT XÁC THỰC

### 3A. Verify Owner (Phê duyệt owner)
- **File**: `src/AdminService.php` → `approveOwnerVerification()` method
- **Handler**: `src/ApiController.php` → `verifyOwner()` method
- **Route**: `api.php?action=verify_owner` (POST)
- **Yêu cầu**: 
  - admin_id (phải là admin)
  - verify_id (từ OwnerVerifications table)
  - review_note (optional)
- **Mô tả**:
  - Admin duyệt xác thực chủ trọ
  - Cập nhật Users.verification_status = Verified
  - Ghi review_note và admin ID

### 3B. Reject Owner (Từ chối owner)
- **File**: `src/AdminService.php` → `rejectOwnerVerification()` method
- **Handler**: `src/ApiController.php` → `rejectOwner()` method
- **Route**: `api.php?action=reject_owner` (POST)
- **Mô tả**:
  - Admin từ chối xác thực (yêu cầu cung cấp lại tài liệu)
  - Cập nhật Users.verification_status = Unverified

### 3C. Get Pending Owners (Danh sách owner chờ duyệt)
- **File**: `src/AdminService.php` → `getPendingOwnerVerifications()` method
- **Handler**: `src/ApiController.php` → `getPendingOwners()` method
- **Route**: `api.php?action=get_pending_owners` (POST)
- **Mô tả**:
  - Lấy danh sách owner chờ xác thực (pagination support)
  - Hiển thị thông tin CMND, email, số điện thoại

### 3D. Admin Check (Kiểm tra quyền admin)
- **File**: `src/AdminService.php` → `isAdmin()` method
- **Mô tả**: Kiểm tra user có quyền admin không

---

## 📊 FEATURE 4: TENANT DASHBOARD - BẢN TIN TENANT

- **File**: `src/ApiController.php` → `getTenantDashboard()` method
- **Route**: `api.php?action=get_tenant_dashboard` (GET)
- **Query params**: `tenant_id` (bắt buộc)
- **Trả về**:
  - Thông tin phòng hiện tại (room_number, hostel_name)
  - Hóa đơn chưa thanh toán mới nhất
  - 5 thông báo mới nhất từ hostel
- **Mô tả**:
  - Lấy room_id từ Rooms (current_tenant_id)
  - Lấy unpaid bill từ Bills (is_paid = 0)
  - Lấy 5 notifications mới nhất

---

## 🏠 FEATURE 5: ROOM MANAGEMENT - QUẢN LÝ PHÒNG

- **File**: `get_rooms.php`
- **Route**: `get_rooms.php?hostel_id=XXX` (GET)
- **Query params**: 
  - `hostel_id` (optional) - lọc theo hostel
- **Trả về**: Danh sách tất cả phòng hoặc phòng của hostel
- **Mô tả**:
  - Lấy danh sách phòng từ bảng `Rooms`
  - Hỗ trợ lọc theo hostel_id

---

## 💰 FEATURE 6: BILL MANAGEMENT - QUẢN LÝ HÓA ĐƠN

- **File**: `create_bill.php`
- **Route**: `create_bill.php` (POST)
- **Request body**:
  ```json
  {
    "room_id": "ROOM_...",
    "billing_month": "06/2026",
    "old_electric": 1000,
    "electric_index": 1050,
    "old_water": 50,
    "water_index": 60,
    "rent_amount": 3000000,
    "service_amount": 200000,
    "total_amount": 3450000
  }
  ```
- **Mô tả**:
  - Tạo hóa đơn thanh toán cho phòng
  - Lưu chỉ số điện/nước
  - Tính toán tiền thuê + tiền dịch vụ

---

## 📢 FEATURE 7: NOTIFICATION SYSTEM - HỆ THỐNG THÔNG BÁO

- **File**: `create_notification.php`
- **Route**: `create_notification.php` (POST)
- **Type**:
  - `remind_bill`: Nhắc nhở thanh toán
  - `call_tenants`: Thông báo chung cho tenant
- **Mô tả**:
  - Tạo thông báo broadcast cho hostel
  - Tất cả tenant trong hostel nhận được thông báo
  - Thông báo hiển thị trên tenant dashboard

---

## ⚠️ FEATURE 8: INCIDENT REPORTING - BÁOÁO CÁO SỰ CỐ

- **File**: `submit_incident.php`
- **Route**: `submit_incident.php` (POST)
- **Request body**:
  ```json
  {
    "tenant_id": "USER_...",
    "description": "Mô tả sự cố"
  }
  ```
- **Mô tả**:
  - Tenant báo cáo sự cố khẩn cấp
  - Tạo bản ghi `Alarms` với status = Active
  - alarm_type = Fire (có thể mở rộng cho Police, Medical)
  - Liên kết với hostel_id từ room

---

## 🛡️ FEATURE 9: SAFETY DASHBOARD - BẢN TIN AN TOÀN (CHỦ TRỌ)

- **File**: `get_safety_dashboard.php`
- **Route**: `get_safety_dashboard.php?owner_id=XXX` (GET)
- **Query params**: `owner_id` (bắt buộc)
- **Trả về**:
  1. **Sensors**: Danh sách cảm biến của owner
  2. **Incidents**: Danh sách tất cả sự cố (JOIN với Rooms, Users)
  3. **Stats**: Số lượng báo động trong tháng hiện tại
- **Mô tả**:
  - Dashboard an toàn cho chủ trọ
  - Theo dõi cảm biến, sự cố, thống kê

---

## 🚨 FEATURE 10: GLOBAL ALARM SYSTEM - HỆ THỐNG BÁO ĐỘNG TOÀN BỘ

- **File**: `trigger_global_alarm.php`
- **Route**: `trigger_global_alarm.php` (POST)
- **Request body**:
  ```json
  {
    "triggered_by": "USER_...",
    "property_id": "HOSTEL_..."
  }
  ```
- **Mô tả**:
  - Kích hoạt báo động toàn bộ hostel
  - Sử dụng cho tình huống khẩn cấp
  - Tạo bản ghi trong bảng `GlobalAlarms`
  - Status = ACTIVE

---

## 🔧 CORE INFRASTRUCTURE CLASSES

| Class | File | Mô tả |
|-------|------|-------|
| **Database** | `src/Database.php` | PDO Connection (Singleton pattern) |
| **Request** | `src/Request.php` | Xử lý HTTP request (GET, POST, JSON body) |
| **Response** | `src/Response.php` | Trả về JSON response |
| **ResourceRepository** | `src/ResourceRepository.php` | Mapping resources với database tables |
| **ApiController** | `src/ApiController.php` | Main controller - điều hướng action |
| **UserRegistrationService** | `src/UserRegistrationService.php` | Business logic đăng ký user |
| **OwnerVerificationService** | `src/OwnerVerificationService.php` | Business logic xác thực owner |
| **AdminService** | `src/AdminService.php` | Business logic admin functions |

---

## 📋 SUMMARY - BẢNG TÓM TẮT NHANH

```
USER AUTHENTICATION:
├── Register User (1A)
├── Login User (1B)
├── Get Status (1C)
├── Change Password (1D)
└── Check Status (1E)

OWNER VERIFICATION:
├── Create Request (2A)
├── Get Status (2B)
├── Update Status (2C)
├── Get Pending (2D)
└── Submit Handler (2E)

ADMIN CONTROL:
├── Approve (3A)
├── Reject (3B)
└── Get Pending (3C)

BUSINESS FEATURES:
├── Tenant Dashboard (4)
├── Room Management (5)
├── Bill Management (6)
├── Notifications (7)
├── Incident Reporting (8)
├── Safety Dashboard (9)
└── Global Alarm (10)
```

---

## 🎯 GHI CHÚ

- Tất cả code chứa comment rõ ràng: `====== [FEATURE X: ...] ======`
- Database: `rent_house` trên MySQL
- Tất cả response trả về JSON format
- CORS enabled cho frontend requests
- HTTP Status Codes:
  - 200: Success (GET, POST update)
  - 201: Created (User registered)
  - 202: Pending (Owner registered, awaiting approval)
  - 400: Bad Request
  - 401: Unauthorized
  - 403: Forbidden
  - 409: Conflict (Email already exists)
  - 500: Server Error
