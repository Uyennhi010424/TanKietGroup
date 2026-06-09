CREATE DATABASE IF NOT EXISTS tankietgroup 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE tankietgroup;

CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    favicon VARCHAR(100),
    meta_title VARCHAR(255),
    meta_description TEXT,
    hotline VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    facebook VARCHAR(255),
    tiktok VARCHAR(255),
    youtube VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 2. NGƯỜI DÙNG & QUẢN TRỊ
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','editor','user') DEFAULT 'user',
    status TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 3. KHOÁ HỌC (MỚI)
-- =============================================
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_desc TEXT,
    content LONGTEXT,
    thumbnail VARCHAR(255),
    price DECIMAL(15,2) DEFAULT 0,
    discount_price DECIMAL(15,2) DEFAULT NULL,
    duration VARCHAR(100),           -- ví dụ: "8 tuần"
    form_type ENUM('online','offline','hybrid') DEFAULT 'online',
    start_date DATE,
    max_students INT DEFAULT 50,
    views INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Đăng ký khoá học
CREATE TABLE course_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT,
    user_id INT,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 4. DỊCH VỤ & NGÀNH
-- =============================================
CREATE TABLE industries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0
);

INSERT INTO industries (name, slug, description, sort_order) VALUES
('Marketing cho Xây dựng (nổi bật)', 'marketing-cho-xay-dung-noi-bat', 'Giải pháp marketing cho doanh nghiệp xây dựng, vật liệu và hạ tầng.', 1),
('Marketing cho Bất động sản', 'marketing-cho-bat-dong-san', 'Giải pháp tăng trưởng cho dự án, sàn giao dịch và môi giới bất động sản.', 2),
('Marketing cho F&B', 'marketing-cho-fb', 'Giải pháp marketing cho nhà hàng, quán cafe, chuỗi F&B và thực phẩm.', 3),
('Marketing cho Beauty', 'marketing-cho-beauty', 'Giải pháp cho spa, thẩm mỹ viện, mỹ phẩm và chăm sóc cá nhân.', 4),
('Marketing cho Bán lẻ', 'marketing-cho-ban-le', 'Giải pháp cho cửa hàng, chuỗi bán lẻ và thương mại đa kênh.', 5),
('Marketing cho Nông nghiệp', 'marketing-cho-nong-nghiep', 'Giải pháp cho nông nghiệp, vật tư nông nghiệp và chuỗi cung ứng.', 6),
('Marketing cho Du lịch', 'marketing-cho-du-lich', 'Giải pháp cho lữ hành, khách sạn, điểm đến và dịch vụ du lịch.', 7)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order);

CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_desc TEXT,
    content LONGTEXT,
    image VARCHAR(255),
    icon VARCHAR(100),
    industry_id INT,
    sort_order INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL
);

-- =============================================
-- 5. DỰ ÁN & PORTFOLIO
-- =============================================
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    industry_id INT,
    service_id INT,
    client_name VARCHAR(100),
    short_desc TEXT,
    content LONGTEXT,
    thumbnail VARCHAR(255),
    images TEXT,                    -- JSON array
    video_url VARCHAR(255),
    result_metrics TEXT,            -- JSON
    start_date DATE,
    end_date DATE,
    status TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- =============================================
-- 6. BLOG & TIN TỨC
-- =============================================
CREATE TABLE blog_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0
);

CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    category_id INT,
    content LONGTEXT,
    thumbnail VARCHAR(255),
    meta_title VARCHAR(255),
    meta_desc TEXT,
    views INT DEFAULT 0,
    author_id INT,
    is_featured TINYINT(1) DEFAULT 0,     -- Tin nổi bật
    status ENUM('draft','published') DEFAULT 'draft',
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 7. KHÁC
-- =============================================
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    service_interest VARCHAR(255),
    industry VARCHAR(100),
    message TEXT,
    status ENUM('new','processed','done') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE consultations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    service VARCHAR(255),
    message TEXT,
    status ENUM('new','processing','done') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE team_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100),
    position VARCHAR(100),
    bio TEXT,
    image VARCHAR(255),
    facebook VARCHAR(255),
    sort_order INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1
);

CREATE TABLE testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100),
    position VARCHAR(100),
    content TEXT,
    image VARCHAR(255),
    rating TINYINT DEFAULT 5,
    sort_order INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1
);

-- =============================================
-- KHÁCH HÀNG TIÊU BIỂU
-- =============================================
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    website_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Gallery chung
CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150),
    image VARCHAR(255) NOT NULL,
    type ENUM('project','team','banner','course') DEFAULT 'project',
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TẠO INDEX ĐỂ TỐI ƯU TỐC ĐỘ
-- =============================================
CREATE INDEX idx_slug ON services(slug);
CREATE INDEX idx_slug ON projects(slug);
CREATE INDEX idx_slug ON blog_posts(slug);
CREATE INDEX idx_slug ON courses(slug);

CREATE INDEX idx_status ON services(status);
CREATE INDEX idx_status ON projects(status);
CREATE INDEX idx_status ON blog_posts(status);
CREATE INDEX idx_status ON courses(status);

CREATE INDEX idx_industry ON projects(industry_id);
CREATE INDEX idx_category ON blog_posts(category_id);
CREATE INDEX idx_views ON blog_posts(views DESC);
CREATE INDEX idx_featured ON blog_posts(is_featured);
