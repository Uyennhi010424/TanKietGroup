<?php
// Initialize database tables for TanKiet Group
// Run this file once to set up database schema

$dbConfig = require __DIR__ . '/config/database.php';

$dbHost = $dbConfig['host'] ?? 'localhost';
$dbName = $dbConfig['dbname'] ?? 'tankietgroup';
$dbUser = $dbConfig['user'] ?? 'root';
$dbPass = $dbConfig['pass'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . str_replace('`', '``', $dbName) . '`');

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS settings (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            site_name VARCHAR(255) NOT NULL,\n            logo VARCHAR(255),\n            favicon VARCHAR(100),\n            meta_title VARCHAR(255),\n            meta_description TEXT,\n            hotline VARCHAR(20),\n            email VARCHAR(100),\n            address TEXT,\n            facebook VARCHAR(255),\n            tiktok VARCHAR(255),\n            youtube VARCHAR(255),\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS users (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            username VARCHAR(50) UNIQUE NOT NULL,\n            password VARCHAR(255) NOT NULL,\n            full_name VARCHAR(100),\n            email VARCHAR(100) UNIQUE NOT NULL,\n            phone VARCHAR(20),\n            role ENUM('admin','editor','user') DEFAULT 'user',\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS courses (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            short_desc TEXT,\n            content LONGTEXT,\n            thumbnail VARCHAR(255),\n            price DECIMAL(15,2) DEFAULT 0,\n            discount_price DECIMAL(15,2) DEFAULT NULL,\n            duration VARCHAR(100),\n            form_type ENUM('online','offline','hybrid') DEFAULT 'online',\n            start_date DATE,\n            max_students INT DEFAULT 50,\n            views INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1,\n            sort_order INT DEFAULT 0,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS industries (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(100) NOT NULL,\n            slug VARCHAR(100) UNIQUE NOT NULL,\n            description TEXT,\n            image VARCHAR(255),\n            sort_order INT DEFAULT 0\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $industrySeeds = [
        ['Marketing cho Xây dựng (nổi bật)', 'marketing-cho-xay-dung-noi-bat', 'Giải pháp marketing cho doanh nghiệp xây dựng, vật liệu và hạ tầng.', 1],
        ['Marketing cho Bất động sản', 'marketing-cho-bat-dong-san', 'Giải pháp tăng trưởng cho dự án, sàn giao dịch và môi giới bất động sản.', 2],
        ['Marketing cho F&B', 'marketing-cho-fb', 'Giải pháp marketing cho nhà hàng, quán cafe, chuỗi F&B và thực phẩm.', 3],
        ['Marketing cho Beauty', 'marketing-cho-beauty', 'Giải pháp cho spa, thẩm mỹ viện, mỹ phẩm và chăm sóc cá nhân.', 4],
        ['Marketing cho Bán lẻ', 'marketing-cho-ban-le', 'Giải pháp cho cửa hàng, chuỗi bán lẻ và thương mại đa kênh.', 5],
        ['Marketing cho Nông nghiệp', 'marketing-cho-nong-nghiep', 'Giải pháp cho nông nghiệp, vật tư nông nghiệp và chuỗi cung ứng.', 6],
        ['Marketing cho Du lịch', 'marketing-cho-du-lich', 'Giải pháp cho lữ hành, khách sạn, điểm đến và dịch vụ du lịch.', 7],
    ];

    $industryStmt = $pdo->prepare("\n        INSERT INTO industries (name, slug, description, sort_order)\n        SELECT :name, :slug, :description, :sort_order\n        FROM DUAL\n        WHERE NOT EXISTS (SELECT 1 FROM industries WHERE slug = :slug_check)\n    ");

    foreach ($industrySeeds as $industry) {
        [$name, $slug, $description, $sortOrder] = $industry;
        $industryStmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'sort_order' => $sortOrder,
            'slug_check' => $slug,
        ]);
    }

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS services (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            short_desc TEXT,\n            content LONGTEXT,\n            image VARCHAR(255),\n            icon VARCHAR(100),\n            industry_id INT,\n            service_type VARCHAR(100),\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Auto-migration: add service_type column if missing
    $col = $pdo->query("SHOW COLUMNS FROM services LIKE 'service_type'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE services ADD COLUMN service_type VARCHAR(100) AFTER industry_id");
    }

    // Service packages (pricing)
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_packages (
        id INT PRIMARY KEY AUTO_INCREMENT,
        service_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        price VARCHAR(100) NOT NULL,
        price_unit VARCHAR(50) DEFAULT '',
        features TEXT,
        is_highlighted TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_service (service_id),
        FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS projects (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            industry_id INT,\n            service_id INT,\n            client_name VARCHAR(100),\n            short_desc TEXT,\n            content LONGTEXT,\n            thumbnail VARCHAR(255),\n            images TEXT,\n            video_url VARCHAR(255),\n            result_metrics TEXT,\n            start_date DATE,\n            end_date DATE,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL,\n            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS blog_categories (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(100) NOT NULL,\n            slug VARCHAR(100) UNIQUE NOT NULL,\n            sort_order INT DEFAULT 0\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS blog_posts (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            category_id INT,\n            content LONGTEXT,\n            thumbnail VARCHAR(255),\n            meta_title VARCHAR(255),\n            meta_desc TEXT,\n            views INT DEFAULT 0,\n            author_id INT,\n            is_featured TINYINT(1) DEFAULT 0,\n            status ENUM('draft','published') DEFAULT 'draft',\n            published_at DATETIME,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,\n            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS contacts (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            full_name VARCHAR(100),\n            phone VARCHAR(20),\n            email VARCHAR(100),\n            service_interest VARCHAR(255),\n            industry VARCHAR(100),\n            message TEXT,\n            status ENUM('new','processed','done') DEFAULT 'new',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS consultations (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(100) NOT NULL,\n            email VARCHAR(100) NOT NULL,\n            phone VARCHAR(20) NOT NULL,\n            service VARCHAR(255),\n            message TEXT,\n            status ENUM('new','processing','done') DEFAULT 'new',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS clients (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(255) NOT NULL,\n            logo VARCHAR(255),\n            website_url VARCHAR(255),\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Tuyển dụng
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS recruitments (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            location VARCHAR(150),\n            salary VARCHAR(150),\n            deadline DATE,\n            description TEXT,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Đơn ứng tuyển
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS job_applications (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            recruitment_id INT NOT NULL,\n            name VARCHAR(255) NOT NULL,\n            email VARCHAR(255) NOT NULL,\n            phone VARCHAR(50) NOT NULL,\n            position VARCHAR(255),\n            message TEXT,\n            cv_file VARCHAR(500),\n            status VARCHAR(20) DEFAULT 'new',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            INDEX idx_recruitment (recruitment_id),\n            INDEX idx_status (status)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Đăng ký khoá học
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS course_enrollments (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            course_id INT,\n            user_id INT,\n            full_name VARCHAR(100),\n            phone VARCHAR(20),\n            email VARCHAR(100),\n            status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,\n            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Đội ngũ nhân sự
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS team_members (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            full_name VARCHAR(100),\n            position VARCHAR(100),\n            bio TEXT,\n            image VARCHAR(255),\n            facebook VARCHAR(255),\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Đánh giá khách hàng
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS testimonials (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            client_name VARCHAR(100),\n            position VARCHAR(100),\n            content TEXT,\n            image VARCHAR(255),\n            rating TINYINT DEFAULT 5,\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Gallery chung
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS gallery (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(150),\n            image VARCHAR(255) NOT NULL,\n            type ENUM('project','team','banner','course') DEFAULT 'project',\n            sort_order INT DEFAULT 0,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    // Tạo index để tối ưu tốc độ
    $indexes = [
        'CREATE INDEX IF NOT EXISTS idx_slug_services ON services(slug)',
        'CREATE INDEX IF NOT EXISTS idx_slug_projects ON projects(slug)',
        'CREATE INDEX IF NOT EXISTS idx_slug_blog_posts ON blog_posts(slug)',
        'CREATE INDEX IF NOT EXISTS idx_slug_courses ON courses(slug)',
        'CREATE INDEX IF NOT EXISTS idx_status_services ON services(status)',
        'CREATE INDEX IF NOT EXISTS idx_status_projects ON projects(status)',
        'CREATE INDEX IF NOT EXISTS idx_status_blog_posts ON blog_posts(status)',
        'CREATE INDEX IF NOT EXISTS idx_status_courses ON courses(status)',
        'CREATE INDEX IF NOT EXISTS idx_industry ON projects(industry_id)',
        'CREATE INDEX IF NOT EXISTS idx_category ON blog_posts(category_id)',
        'CREATE INDEX IF NOT EXISTS idx_views ON blog_posts(views DESC)',
        'CREATE INDEX IF NOT EXISTS idx_featured ON blog_posts(is_featured)',
    ];

    foreach ($indexes as $idxSql) {
        try {
            $pdo->exec($idxSql);
        } catch (PDOException $e) {
            // Index có thể đã tồn tại, bỏ qua
        }
    }

    echo 'Database tables created successfully!';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
