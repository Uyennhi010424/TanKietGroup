# TanKietGroup Website

Website chính thức của **TanKiet Group** — Giải pháp Marketing tăng trưởng toàn diện cho doanh nghiệp hiện đại.

## Yêu cầu hệ thống

| Phần mềm | Phiên bản |
|---|---|
| PHP | 8.1 trở lên |
| MySQL | 8.0 trở lên |
| Apache | 2.4 với mod_rewrite |
| Composer | (tùy chọn, hiện tại chưa dùng thư viện bên ngoài)

## Cài đặt

### 1. Clone repository

```bash
git clone <repo-url>
cd TanKietGroup
```

### 2. Cấu hình môi trường

```bash
# Copy file cấu hình mẫu
cp .env.example .env

# Sửa file .env, điền thông tin database
nano .env
```

File `.env` mẫu:

```
DB_HOST=localhost
DB_NAME=tankietgroup
DB_USER=root
DB_PASS=
APP_BASE_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true
```

### 3. Tạo database

**Cách A:** Import file SQL (khuyến nghị)

```bash
mysql -u root -p -e "CREATE DATABASE tankietgroup CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p tankietgroup < database.sql
```

**Cách B:** Chạy script PHP tự tạo bảng

```bash
php db_init.php
```

### 4. Chạy website

**Dùng PHP built-in server:**

```bash
# Trang người dùng (port 8000) — cho phép truy cập từ máy khác trong LAN
php -S 0.0.0.0:8000 router.php

# Trang quản trị (port 8001) — terminal riêng
php -S 0.0.0.0:8001 router.php
```

**Hoặc dùng XAMPP/WAMP:** Đặt thư mục vào `htdocs`, truy qua `http://localhost/TanKietGroup/`

**Truy cập từ máy khác trong LAN:**
- Trang người dùng: `http://192.168.1.242:8000`
- Trang quản trị: `http://192.168.1.242:8001`

### 5. Đăng nhập admin

Truy cập `http://localhost:8001/?page=admin_login`

Tài khoản mặc định: `admin` / `admin123` (đổi mật khẩu ngay sau khi đăng nhập lần đầu)

## Cấu trúc thư mục

```
TanKietGroup/
├── admin/              # Trang quản trị (CRUD)
│   ├── assets/         # CSS/JS riêng cho admin
│   ├── index.php       # Dashboard
│   ├── login.php       # Đăng nhập
│   ├── services.php    # Quản lý dịch vụ
│   ├── blog.php        # Quản lý blog
│   ├── projects.php    # Quản lý dự án
│   ├── courses.php     # Quản lý khóa học
│   └── ...
├── api/                # API endpoints (JSON)
│   ├── apply_job.php   # Nộp đơn ứng tuyển
│   └── save_consultation.php  # Gửi yêu cầu tư vấn
├── assets/             # Frontend assets
│   ├── css/style.css   # Stylesheet chính
│   └── js/main.js      # JavaScript chính
├── config/             # Cấu hình
│   ├── config.php      # Cấu hình chung
│   ├── constants.php   # Hằng số
│   └── database.php    # Cấu hình database
├── controllers/        # Controllers (chưa triển khai)
├── img/                # Ảnh tĩnh
├── includes/           # PHP helpers
│   ├── db.php          # Kết nối PDO
│   ├── site.php        # Helper functions
│   ├── security.php    # CSRF, session, auth
│   ├── functions.php   # Upload, sanitize
│   ├── env.php         # Đọc file .env
│   └── admin_helpers.php # Helper cho admin
├── models/             # Models (chưa triển khai)
├── uploads/            # File upload của user
│   ├── clients/
│   ├── courses/
│   ├── cv/
│   ├── projects/
│   ├── services/
│   └── settings/
├── views/              # Templates
│   ├── layouts/        # Layout chung (header, footer)
│   ├── home.php        # Trang chủ
│   ├── about.php       # Giới thiệu
│   ├── contact.php     # Liên hệ
│   ├── services/       # Dịch vụ
│   ├── courses/        # Khóa học
│   ├── projects/       # Dự án
│   └── blog/           # Blog
├── .env.example        # Mẫu file cấu hình môi trường
├── .htaccess           # Apache rewrite rules
├── database.sql        # Schema database đầy đủ
├── db_init.php         # Script tạo database
├── index.php           # Front controller (entry point)
├── robots.txt          # Khai báo cho crawler
├── router.php          # Router cho PHP built-in server
└── sitemap.php         # Sitemap động
```

## Hệ thống routing

Website sử dụng front-controller pattern, mọi request đi qua `index.php`:

| URL | Trang |
|---|---|
| `/?page=home` | Trang chủ |
| `/?page=about` | Giới thiệu |
| `/?page=services` | Danh sách dịch vụ |
| `/dich-vu/{slug}` | Chi tiết dịch vụ |
| `/?page=projects` | Danh sách dự án |
| `/du-an/{slug}` | Chi tiết dự án |
| `/?page=blog` | Danh sách bài viết |
| `/blog/{slug}` | Chi tiết bài viết |
| `/?page=courses` | Danh sách khóa học |
| `/khoa-hoc/{slug}` | Chi tiết khóa học |
| `/?page=contact` | Liên hệ |
| `/?page=recruitments` | Tuyển dụng |
| `/?page=admin_login` | Đăng nhập admin |
| `/?page=admin_index` | Dashboard admin |

## Phân quyền

| Vai trò | Quyền hạn |
|---|---|
| **Admin** | Toàn quyền: dashboard, quản lý users, settings, stats, tất cả nội dung |
| **Editor** | Quản lý nội dung: services, projects, blog, courses, clients, recruitments |

## Deployment lên production

### Yêu cầu

- VPS/Shared hosting với PHP 8.1+, MySQL 8.0+, Apache 2.4
- Domain name đã trỏ DNS về server
- SSL certificate (Let's Encrypt miễn phí)

### Các bước

1. Upload code lên server
2. Tạo `.env` với thông tin production
3. Tạo database và import `database.sql`
4. Cấu hình Apache VirtualHost (document root trỏ vào thư mục project)
5. Cài SSL: `sudo certbot --apache -d yourdomain.com`
6. Bỏ comment dòng HTTPS redirect trong `.htaccess`
7. Cấp quyền thư mục: `chmod -R 755 uploads/`

## License

Proprietary — TanKiet Group
