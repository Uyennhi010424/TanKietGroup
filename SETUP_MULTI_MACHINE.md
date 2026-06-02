# Kiểm tra CSRF Token khi 2 máy dùng chung 192.168.1.242

## Bước 1: Đảm bảo truy cập cùng URL

**Trên cả 2 máy, phải truy cập:**
```
http://192.168.1.242:8001/?page=admin_login
```

❌ **Không được truy cập:**
- `http://localhost:8001/...` (Máy A)
- `http://192.168.1.242:8001/...` (Máy B)
- `http://192.168.1.242:8000/...` (khác port)

## Bước 2: Kiểm tra Session Cookie

### Trên Máy A (Server):
1. Mở: `http://192.168.1.242:8001/debug_session_ip.php`
2. Ghi lại **Session ID** (ví dụ: `abc123xyz`)

### Trên Máy B (Client):
1. Mở cùng URL: `http://192.168.1.242:8001/debug_session_ip.php`
2. Kiểm tra:
   - ✓ Nếu Session ID **giống** Máy A → Cookie được share đúng
   - ✗ Nếu Session ID **khác** → Cookie bị tách riêng (lỗi!)

## Bước 3: Kiểm tra Cookie trong DevTools

### Cách 1: DevTools (F12)

**Trên Máy B:**
1. Nhấn `F12` để mở DevTools
2. Chọn tab **Application** (hoặc **Storage** trong Firefox)
3. Tìm **Cookies** → `192.168.1.242:8001`
4. Tìm cookie `TKGSESSID`

**Kiểm tra giá trị:**
- ✓ **Có** → Session cookie được gửi
- ✗ **Không có** → Browser không gửi cookie (lỗi!)

### Cách 2: Kiểm tra trong Network Tab

**Trên Máy B:**
1. Nhấn `F12` → Tab **Network**
2. Reload trang
3. Click vào request GET tới `/debug_session_ip.php`
4. Scroll xuống **Request Headers**
5. Tìm `Cookie:` header
   - ✓ Nếu có `TKGSESSID=...` → Cookie được gửi
   - ✗ Nếu không có → Browser không gửi cookie

## Bước 4: Test CSRF Token Submit

### Trên Máy B:
1. Vào `http://192.168.1.242:8001/debug_session_ip.php`
2. Scroll xuống nút **"Submit Test"**
3. Click nút
4. Kiểm tra kết quả:
   - ✓ **✓ VALID** → CSRF token hoạt động
   - ✗ **✗ INVALID** → Token không khớp (xem giá trị debug)

## Nếu vẫn lỗi - Kiểm tra Firewall

Nếu Máy B không gửi cookie:

**Windows Firewall (Máy A - Server):**
```
Cài đặt → Cổng mạng & Internet → Tường lửa Windows Defender
→ Cho phép ứng dụng qua tường lửa
→ Tìm PHP hoặc port 8001 → Cho phép
```

**Hoặc kiểm tra Antivirus** có chặn không.

## Nếu vẫn lỗi - Test với Command Line

**Trên Máy B, mở Command/PowerShell:**

```powershell
# Test 1: Lấy cookie
$response1 = curl.exe -c cookies.txt -s http://192.168.1.242:8001/debug_session_ip.php
# Xem Session ID từ output
$response1 | Select-String "Session ID:"

# Test 2: Submit dengan cookie
curl.exe -b cookies.txt -X POST -d "csrf_token=test123" http://192.168.1.242:8001/debug_session_ip.php | Select-String "CSRF"
```

Nếu output là `✓ VALID`, token hoạt động được.

## Nếu vẫn lỗi - Kiểm tra PHP Session Storage

Có thể session lưu ở 2 chỗ khác nhau. Chạy:

```php
<?php
echo ini_get('session.save_path');
echo sys_get_temp_dir();
?>
```

Đảm bảo cả 2 máy dùng cùng **save_path** (hoặc cùng temp directory accessible).

## Khi đã hoạt động:

- Máy B có thể submit form bất kỳ trong admin
- Token sẽ được validate thành công
- Có thể xóa/cập nhật dữ liệu từ Máy B mà không lỗi CSRF
