# Rent House API

## Tổng quan

- Entry point: `api.php`
- Tất cả response trả về JSON.
- Đã chuyển sang cấu trúc OOP với các lớp:
  - `Database` - kết nối PDO
  - `Request` - xử lý HTTP request
  - `Response` - trả JSON
  - `ResourceRepository` - định nghĩa resources và ánh xạ bảng
  - `ApiController` - điều phối action và CRUD
  - `UserRegistrationService` - xử lý đăng ký user
  - `OwnerVerificationService` - quản lý xác thực chủ trọ
  - `AdminService` - hỗ trợ admin quản lý xác thực

## Header cần thiết

- `Content-Type: application/json`
- `Accept: application/json`

### Response mặc định

- `status`: `success` hoặc `error`
- `message`: mô tả kết quả
- `data`: trả về dữ liệu khi lấy

## Các tham số query

- `resource`: tên resource (ví dụ `users`, `rooms`, `bills`)
- `id`: id của bản ghi (dùng cho GET/PUT/PATCH/DELETE)
- `action`: action đặc biệt cho người dùng (`register_user`, `login_user`, `verify_owner`, `reject_owner`, `get_pending_owners`)

## Các API hiện có

### 1. Đăng ký user

- URL: `api.php?action=register_user`
- Method: `POST`
- Request body JSON:
  - `email`
  - `password`
  - `full_name`
  - `phone_number`
  - `role` (tùy chọn: `tenant` hoặc `owner`, mặc định `tenant`)
- Response thành công:
  - Nếu `role=tenant`: HTTP 201
    ```json
    {
      "status": "success",
      "message": "Đăng ký thành công!",
      "user_id": "USER_...",
      "verification_status": "verified"
    }
    ```
  - Nếu `role=owner`: HTTP 202
    ```json
    {
      "status": "success",
      "message": "Đăng ký thành công! Vui lòng chờ admin xác thực tài khoản chủ trọ.",
      "user_id": "USER_...",
      "verification_status": "pending_verification"
    }
    ```

### 2. Đăng nhập user

- URL: `api.php?action=login_user`
- Method: `POST`
- Request body JSON:
  - `email`
  - `password`
- Response thành công (HTTP 200):
  ```json
  {
    "status": "success",
    "message": "Đăng nhập thành công!",
    "data": {
      "user_id": "USER_...",
      "email": "user@example.com",
      "full_name": "Tên người dùng",
      "phone_number": "0123456789",
      "role": "tenant",
      "verification_status": "verified",
      "created_at": "2026-06-18 10:00:00"
    }
  }
  ```

### 3. Duyệt xác thực chủ trọ (Admin)

- URL: `api.php?action=verify_owner`
- Method: `POST`
- Quyền: chỉ admin
- Request body JSON:
  - `admin_id`: ID của admin
  - `verify_id`: ID xác thực (từ bảng OwnerVerifications)
  - `review_note` (tùy chọn): ghi chú của admin
- Response thành công (HTTP 200):
  ```json
  {
    "status": "success",
    "message": "Đã duyệt xác thực chủ trọ"
  }
  ```
- Cấp nhật user có `verification_status = verified`

### 4. Từ chối xác thực chủ trọ (Admin)

- URL: `api.php?action=reject_owner`
- Method: `POST`
- Quyền: chỉ admin
- Request body JSON:
  - `admin_id`: ID của admin
  - `verify_id`: ID xác thực
  - `review_note` (tùy chọn): lý do từ chối
- Response thành công (HTTP 200):
  ```json
  {
    "status": "success",
    "message": "Đã từ chối xác thực chủ trọ"
  }
  ```
- Cập nhật user có `verification_status = verification_failed`

### 5. Lấy danh sách chủ trọ chờ xác thực (Admin)

- URL: `api.php?action=get_pending_owners`
- Method: `POST`
- Quyền: chỉ admin
- Request body JSON:
  - `admin_id`: ID của admin
  - `limit` (tùy chọn, mặc định 50)
  - `offset` (tùy chọn, mặc định 0)
- Response thành công (HTTP 200):
  ```json
  {
    "status": "success",
    "data": [
      {
        "verify_id": "...",
        "owner_id": "USER_...",
        "id_card_front_url": "...",
        "id_card_back_url": "...",
        "status": "pending",
        "reviewed_by": null,
        "review_note": null,
        "created_at": "...",
        "email": "owner@example.com",
        "full_name": "Tên chủ trọ",
        "phone_number": "0123456789"
      }
    ]
  }
  ```

### 6. CRUD resource chung

- URL: `api.php?resource={resource}`
- Resource hiện có:
  - `users`
  - `owner_verifications`
  - `hostels`
  - `rooms`
  - `join_requests`
  - `alarms`
  - `alarm_responses`
  - `bills`
  - `notifications`

#### GET tất cả

- `GET api.php?resource=users`
- Trả về tất cả bản ghi của resource

#### GET theo id

- `GET api.php?resource=users&id=USER_123`
- Trả về một bản ghi cụ thể

#### POST tạo mới

- `POST api.php?resource=users`
- Body JSON: chỉ gửi các field hợp lệ của resource

#### PUT/PATCH cập nhật

- `PUT api.php?resource=users&id=USER_123`
- Body JSON: các field cần cập nhật

#### DELETE xóa

- `DELETE api.php?resource=users&id=USER_123`

## Quy tắc dữ liệu

- Chỉ trường nằm trong `fields` của resource mới chấp nhận
- Nếu `id` là chuỗi rỗng khi POST thì sẽ bị loại bỏ
- Email phải duy nhất khi đăng ký (lỗi 409)

## Quy trình xác thực chủ trọ

```
1. Chủ trọ gọi: POST api.php?action=register_user
   + role=owner
   + Trạng thái: pending_verification
   + Tạo request xác thực trong OwnerVerifications (status=pending)

2. Admin gọi: POST api.php?action=get_pending_owners
   + Lấy danh sách chủ trọ chờ duyệt

3. Admin xem thông tin, rồi:
   - Duyệt: POST api.php?action=verify_owner
     + OwnerVerifications.status = approved
     + Users.verification_status = verified
   
   - Từ chối: POST api.php?action=reject_owner
     + OwnerVerifications.status = rejected
     + Users.verification_status = verification_failed
```

## HTTP Status Code

- `200 OK` - Request thành công
- `201 Created` - Tạo tài nguyên mới (tenant đăng ký thành công)
- `202 Accepted` - Request được chấp nhận nhưng chờ xử lý (owner đăng ký chờ admin xác thực)
- `400 Bad Request` - Tham số/payload không hợp lệ
- `403 Forbidden` - Không có quyền (không phải admin)
- `404 Not Found` - Tài nguyên không tồn tại
- `409 Conflict` - Email đã tồn tại
- `500 Internal Server Error` - Lỗi server

## Cấu trúc file

- `api.php` - điểm vào chính
- `src/Database.php` - kết nối database
- `src/Request.php` - xử lý request
- `src/Response.php` - trả response JSON
- `src/ResourceRepository.php` - định nghĩa resources
- `src/ApiController.php` - controller chính
- `src/UserRegistrationService.php` - xử lý đăng ký
- `src/OwnerVerificationService.php` - quản lý xác thực
- `src/AdminService.php` - hỗ trợ admin

## Lưu ý khi refactor

- Giữ nguyên endpoint `api.php` và query param `resource`, `id`, `action`
- Giữ nguyên method HTTP và JSON payload
- Nếu app không đổi contract này thì không cần chỉnh app

