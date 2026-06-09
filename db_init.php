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

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS services (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            short_desc TEXT,\n            content LONGTEXT,\n            image VARCHAR(255),\n            icon VARCHAR(100),\n            industry_id INT,\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS projects (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            industry_id INT,\n            service_id INT,\n            client_name VARCHAR(100),\n            short_desc TEXT,\n            content LONGTEXT,\n            thumbnail VARCHAR(255),\n            images TEXT,\n            video_url VARCHAR(255),\n            result_metrics TEXT,\n            start_date DATE,\n            end_date DATE,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (industry_id) REFERENCES industries(id) ON DELETE SET NULL,\n            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS blog_categories (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(100) NOT NULL,\n            slug VARCHAR(100) UNIQUE NOT NULL,\n            sort_order INT DEFAULT 0\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS blog_posts (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            title VARCHAR(255) NOT NULL,\n            slug VARCHAR(255) UNIQUE NOT NULL,\n            category_id INT,\n            content LONGTEXT,\n            thumbnail VARCHAR(255),\n            meta_title VARCHAR(255),\n            meta_desc TEXT,\n            views INT DEFAULT 0,\n            author_id INT,\n            is_featured TINYINT(1) DEFAULT 0,\n            status ENUM('draft','published') DEFAULT 'draft',\n            published_at DATETIME,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n            FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,\n            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS contacts (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            full_name VARCHAR(100),\n            phone VARCHAR(20),\n            email VARCHAR(100),\n            service_interest VARCHAR(255),\n            industry VARCHAR(100),\n            message TEXT,\n            status ENUM('new','processed','done') DEFAULT 'new',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS consultations (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(100) NOT NULL,\n            email VARCHAR(100) NOT NULL,\n            phone VARCHAR(20) NOT NULL,\n            service VARCHAR(255),\n            message TEXT,\n            status ENUM('new','processing','done') DEFAULT 'new',\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS clients (\n            id INT PRIMARY KEY AUTO_INCREMENT,\n            name VARCHAR(255) NOT NULL,\n            logo VARCHAR(255),\n            website_url VARCHAR(255),\n            sort_order INT DEFAULT 0,\n            status TINYINT(1) DEFAULT 1,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");

    echo 'Database tables created successfully!';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
