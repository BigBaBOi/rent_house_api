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
- `action`: action đặc biệt cho người dùng (`register_user`, `login_user`)

## Các API hiện có

### 1. Đăng ký user

- URL: `api.php?action=register_user`
- Method: `POST`
- Request body JSON:
  - `email`
  - `password`
  - `full_name`
  - `phone_number`
  - `role` (tùy chọn, mặc định `tenant`)
- Response thành công:
  - `status: success`
  - `message`
  - `user_id`

### 2. Đăng nhập user

- URL: `api.php?action=login_user`
- Method: `POST`
- Request body JSON:
  - `email`
  - `password`
- Response thành công:
  - `status: success`
  - `message`
  - `data`: object user (không có `password_hash`)

### 3. CRUD resource chung

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

## Cấu trúc file

- `api.php` - điểm vào chính
- `src/Database.php`
- `src/Request.php`
- `src/Response.php`
- `src/ResourceRepository.php`
- `src/ApiController.php`

## Lưu ý khi refactor

- Giữ nguyên endpoint `api.php` và query param `resource`, `id`, `action`
- Giữ nguyên method HTTP và JSON payload
- Nếu app không đổi contract này thì không cần chỉnh app
