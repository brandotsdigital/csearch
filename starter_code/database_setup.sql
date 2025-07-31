-- Database setup script for Product Discount Scraper
-- Run this in your MySQL database (phpMyAdmin or similar)

-- Create database (if not already created)
-- CREATE DATABASE product_scraper;
-- USE product_scraper;

-- Products table - stores basic product information
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    brand VARCHAR(100),
    url VARCHAR(500) NOT NULL,
    image_url VARCHAR(500),
    platform VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Indexes for better performance
    INDEX idx_category (category),
    INDEX idx_platform (platform),
    INDEX idx_updated (updated_at),
    INDEX idx_active (is_active)
);

-- Price history table - tracks price changes over time
CREATE TABLE IF NOT EXISTS price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    discount_percentage INT DEFAULT 0,
    availability BOOLEAN DEFAULT TRUE,
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_product_date (product_id, scraped_at),
    INDEX idx_discount (discount_percentage),
    INDEX idx_scraped_at (scraped_at)
);

-- Categories table - manage product categories and their settings
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    keywords TEXT,
    min_discount_threshold INT DEFAULT 20,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications table - manage alerts and notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    notification_type ENUM('price_drop', 'back_in_stock', 'new_product', 'clearance') NOT NULL,
    message TEXT,
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_pending (is_sent, created_at),
    INDEX idx_type (notification_type)
);

-- Settings table - store application configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User wishlist table (optional - for future enhancement)
CREATE TABLE IF NOT EXISTS user_wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_email (user_email),
    INDEX idx_product (product_id),
    UNIQUE KEY unique_user_product (user_email, product_id)
);

-- Scraping logs table - track scraping activities and errors
CREATE TABLE IF NOT EXISTS scraping_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    platform VARCHAR(50),
    url VARCHAR(500),
    status ENUM('success', 'error', 'blocked', 'not_found') NOT NULL,
    error_message TEXT,
    response_time INT, -- in milliseconds
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_status_date (status, scraped_at),
    INDEX idx_platform (platform),
    INDEX idx_product_logs (product_id)
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('min_discount_threshold', '20', 'Minimum discount percentage to trigger notification'),
('scraping_interval', '60', 'Minutes between scraping runs'),
('email_notifications', '1', 'Enable email notifications (1=yes, 0=no)'),
('max_products_per_run', '100', 'Maximum products to scrape in one run'),
('notification_email', 'your@email.com', 'Email address for notifications'),
('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'Default user agent for scraping'),
('request_delay', '2', 'Seconds to wait between requests'),
('max_retries', '3', 'Maximum number of retry attempts for failed requests'),
('enable_proxy', '0', 'Enable proxy rotation (1=yes, 0=no)'),
('site_title', 'Product Discount Monitor', 'Website title'),
('max_price_history_days', '90', 'Days to keep price history (0 = keep all)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert default categories
INSERT INTO categories (name, keywords, min_discount_threshold) VALUES
('Electronics', 'laptop,smartphone,tablet,headphones,camera,tv,computer,gaming', 25),
('Fashion', 'clothing,shoes,accessories,jewelry,watch,bag,dress,shirt', 30),
('Home & Garden', 'furniture,decor,appliances,tools,kitchen,bedroom,bathroom', 20),
('Books', 'books,ebooks,audiobooks,magazines,textbooks', 15),
('Sports & Outdoors', 'fitness,outdoor,sports equipment,exercise,camping,hiking', 25),
('Health & Beauty', 'skincare,makeup,vitamins,supplements,health,beauty', 20),
('Toys & Games', 'toys,games,puzzles,board games,video games,kids', 30),
('Automotive', 'car,auto,parts,accessories,tools,maintenance', 15)
ON DUPLICATE KEY UPDATE keywords = VALUES(keywords);

-- Create view for latest product prices (for easier querying)
CREATE OR REPLACE VIEW latest_product_prices AS
SELECT 
    p.id,
    p.name,
    p.category,
    p.brand,
    p.url,
    p.image_url,
    p.platform,
    p.updated_at,
    ph.price,
    ph.original_price,
    ph.discount_percentage,
    ph.availability,
    ph.scraped_at
FROM products p
JOIN (
    SELECT 
        product_id,
        price,
        original_price,
        discount_percentage,
        availability,
        scraped_at,
        ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY scraped_at DESC) as rn
    FROM price_history
) ph ON p.id = ph.product_id AND ph.rn = 1
WHERE p.is_active = 1;

-- Create view for top discounts (for dashboard)
CREATE OR REPLACE VIEW top_discounts AS
SELECT 
    lpp.*,
    (lpp.original_price - lpp.price) as savings_amount
FROM latest_product_prices lpp
WHERE lpp.discount_percentage > 0
ORDER BY lpp.discount_percentage DESC, savings_amount DESC;

-- Create indexes for better performance on large datasets
ALTER TABLE price_history ADD INDEX idx_price_date (price, scraped_at);
ALTER TABLE notifications ADD INDEX idx_created_sent (created_at, is_sent);
ALTER TABLE products ADD INDEX idx_name_category (name(50), category);

-- Optional: Create procedure to clean old data
DELIMITER $$
CREATE PROCEDURE CleanOldData()
BEGIN
    DECLARE max_history_days INT DEFAULT 90;
    
    -- Get the setting for how long to keep price history
    SELECT CAST(setting_value AS UNSIGNED) INTO max_history_days 
    FROM settings 
    WHERE setting_key = 'max_price_history_days' 
    LIMIT 1;
    
    -- Only clean if max_history_days > 0 (0 means keep all)
    IF max_history_days > 0 THEN
        -- Delete old price history
        DELETE FROM price_history 
        WHERE scraped_at < DATE_SUB(NOW(), INTERVAL max_history_days DAY);
        
        -- Delete old scraping logs
        DELETE FROM scraping_logs 
        WHERE scraped_at < DATE_SUB(NOW(), INTERVAL max_history_days DAY);
        
        -- Delete sent notifications older than 30 days
        DELETE FROM notifications 
        WHERE is_sent = 1 AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    END IF;
END$$
DELIMITER ;

-- Show table status
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
