-- Product Discount Scraping System Database Schema
-- Run this in phpMyAdmin or your cPanel database interface

-- Products table - stores all monitored products
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `image_url` VARCHAR(500),
    `current_price` DECIMAL(10,2) DEFAULT 0.00,
    `original_price` DECIMAL(10,2) DEFAULT 0.00,
    `discount_percentage` INT DEFAULT 0,
    `availability` BOOLEAN DEFAULT TRUE,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `platform` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    INDEX `idx_category` (`category`),
    INDEX `idx_platform` (`platform`),
    INDEX `idx_discount` (`discount_percentage`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Price history table - tracks price changes over time
CREATE TABLE IF NOT EXISTS `price_history` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `source` VARCHAR(50) DEFAULT 'scraper',
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_time` (`product_id`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table - predefined product categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table - stores alert history
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `type` ENUM('price_drop', 'discount', 'availability', 'system') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `email_sent` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `sent_at` TIMESTAMP NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_type_date` (`type`, `created_at`),
    INDEX `idx_email_sent` (`email_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings table - system configuration
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `description` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Scraping logs table - track scraping activities
CREATE TABLE IF NOT EXISTS `scraping_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `product_id` INT,
    `platform` VARCHAR(50) NOT NULL,
    `status` ENUM('success', 'failed', 'error') NOT NULL,
    `message` TEXT,
    `execution_time` DECIMAL(5,3),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    INDEX `idx_platform_status` (`platform`, `status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Fashion', 'Clothing, shoes, and accessories'),
('Home & Garden', 'Home improvement and garden supplies'),
('Books', 'Books and educational materials'),
('Sports & Outdoors', 'Sports equipment and outdoor gear'),
('Beauty & Health', 'Beauty products and health supplements'),
('Toys & Games', 'Toys, games, and entertainment'),
('Automotive', 'Car parts and automotive accessories')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('admin_email', 'admin@yourdomain.com', 'Administrator email for notifications'),
('notification_threshold', '20', 'Minimum discount percentage to trigger notifications'),
('scraping_interval', '60', 'Scraping interval in minutes'),
('max_products_per_page', '20', 'Maximum products to display per page'),
('smtp_host', 'localhost', 'SMTP server hostname'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP username'),
('smtp_password', '', 'SMTP password'),
('site_title', 'Product Discount Monitor', 'Website title'),
('site_description', 'Monitor product prices and get notified of discounts', 'Website description')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
