# Hướng dẫn kiểm tra CSRF Token lỗi khi 2 máy kết nối chung localhost

## Nguyên nhân vấn đề

Khi 2 máy kết nối cùng **localhost** hoặc cùng **IP địa chỉ** nhưng **port hoặc host khác**, mỗi máy sẽ nhận session cookie riêng. CSRF token bị lỗi vì:
- Máy A tạo token từ session `sess_123`
- Máy B truy cập lại URL form và nhận session `sess_456` (khác)
- Khi máy B submit form, token từ `sess_123` không khớp với `sess_456` → lỗi

## Giải pháp

### 1. Kiểm tra Cookie trong trình duyệt (DevTools)

**Trên Máy A:**
1. Mở trang admin (login hoặc form)
2. Nhấn `F12` → Tab **Application** (hoặc **Storage**)
3. Tìm **Cookies** → `http://localhost:8001` (hoặc URL của bạn)
4. Tìm cookie `TKGSESSID` → Ghi lại giá trị (ví dụ: `abc123xyz`)

**Trên Máy B:**
1. Mở cùng URL (phải cùng host và port)
2. Nhấn `F12` → Tab **Application**
3. Kiểm tra cookie `TKGSESSID`
   - Nếu **trống** hoặc **giá trị khác** → Đó là lỗi!
   - Nếu **giống Máy A** → Cookie được giữ đúng

### 2. Đảm bảo cùng Host/Port

Kiểm tra URL truy cập từ 2 máy:
```
Máy A: http://localhost:8001/?page=admin_blog
Máy B: http://localhost:8001/?page=admin_blog  ← PHẢI giống hệt
```

❌ **Sai:**
```
Máy A: http://localhost:8001/...
Máy B: http://localhost:8002/...  ← Khác port → Khác session
```

❌ **Sai:**
```
Máy A: http://localhost/...
Máy B: http://127.0.0.1/...  ← Khác host → Khác session
```

### 3. Kiểm tra Network Tab

Khi submit form:
1. Nhấn `F12` → Tab **Network**
2. Submit form
3. Tìm request POST
4. Tab **Cookies** → Tìm `TKGSESSID`
   - Nếu **không có** → Trình duyệt không gửi cookie → Lỗi
   - Nếu **có** → Cookie được gửi, nhưng giá trị không khớp session server

### 4. Xóa Session cũ (nếu cần)

Nếu session bị lỗi:
1. Mở DevTools → **Application** → **Cookies**
2. Xóa cookie `TKGSESSID`
3. F5 reload trang
4. Đăng nhập lại

### 5. Cấu hình cho nhiều máy (Alternative)

Nếu cần múltiple machines chia sẻ session, sửa file [config/database.php](../config/database.php) để dùng session share (Redis hoặc Database), nhưng cách đơn giản nhất là:

**Đảm bảo tất cả máy truy cập cùng URL:**
- Sử dụng **địa chỉ cố định** (IP hoặc domain) thay vì `localhost`
- Ví dụ: Tất cả máy truy cập `http://192.168.1.100:8001/` hoặc `http://tankietgroup.local/`

## Kiểm tra debug nhanh

Truy cập file debug:
```
http://localhost:8001/debug_csrf.php
```

Làm theo hướng dẫn trên trang để test token validation.

## Nếu vẫn lỗi sau khi kiểm tra trên

- Cấp dùng browser khác (Firefox thay Chrome) để loại trừ lỗi browser
- Xóa browser cache: `Ctrl+Shift+Del`
- Kiểm tra `php.ini` có `session.use_strict_mode = 1` không (nếu có, độc lập hơn nhưng khó cross-domain)
