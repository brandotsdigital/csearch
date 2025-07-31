<?php
/**
 * Setup Script - Initialize Database and Add Sample Data
 * Run this once to set up your product scraper system
 * 
 * Usage: php setup.php
 */

// Include database configuration
require_once '../private/config/database.php';

echo "===============================================\n";
echo "Product Discount Scraper - Setup Script\n";
echo "===============================================\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $db = new Database();
    $conn = $db->getConnection();
    echo "   ✅ Database connection successful\n\n";
    
    // Check if tables exist
    echo "2. Checking database tables...\n";
    $tables = ['products', 'price_history', 'categories', 'notifications', 'settings', 'scraping_logs'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            $existingTables[] = $table;
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    }
    
    if (count($existingTables) < count($tables)) {
        echo "\n   ⚠️  Some tables are missing. Please run the database_setup.sql file first.\n";
        echo "   You can find it in the starter_code directory.\n\n";
    } else {
        echo "   ✅ All required tables exist\n\n";
    }
    
    // Check settings
    echo "3. Checking system settings...\n";
    $settingsStmt = $conn->prepare("SELECT COUNT(*) FROM settings");
    $settingsStmt->execute();
    $settingsCount = $settingsStmt->fetchColumn();
    
    if ($settingsCount == 0) {
        echo "   🔧 Adding default settings...\n";
        
        $defaultSettings = [
            ['min_discount_threshold', '20', 'Minimum discount percentage to trigger notification'],
            ['scraping_interval', '60', 'Minutes between scraping runs'],
            ['email_notifications', '1', 'Enable email notifications (1=yes, 0=no)'],
            ['max_products_per_run', '50', 'Maximum products to scrape in one run'],
            ['notification_email', 'admin@yoursite.com', 'Email address for notifications'],
            ['site_title', 'Product Discount Monitor', 'Website title'],
            ['request_delay', '2', 'Seconds to wait between requests'],
            ['max_retries', '3', 'Maximum number of retry attempts for failed requests']
        ];
        
        $insertStmt = $conn->prepare("
            INSERT INTO settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        
        foreach ($defaultSettings as $setting) {
            $insertStmt->execute($setting);
            echo "      Added: {$setting[0]}\n";
        }
        echo "   ✅ Default settings added\n\n";
    } else {
        echo "   ✅ Settings already configured ($settingsCount settings found)\n\n";
    }
    
    // Check categories
    echo "4. Checking product categories...\n";
    $categoriesStmt = $conn->prepare("SELECT COUNT(*) FROM categories");
    $categoriesStmt->execute();
    $categoriesCount = $categoriesStmt->fetchColumn();
    
    if ($categoriesCount == 0) {
        echo "   📂 Adding default categories...\n";
        
        $defaultCategories = [
            ['Electronics', 'laptop,smartphone,tablet,headphones,camera,tv,computer,gaming', 25],
            ['Fashion', 'clothing,shoes,accessories,jewelry,watch,bag,dress,shirt', 30],
            ['Home & Garden', 'furniture,decor,appliances,tools,kitchen,bedroom,bathroom', 20],
            ['Books', 'books,ebooks,audiobooks,magazines,textbooks', 15],
            ['Sports & Outdoors', 'fitness,outdoor,sports equipment,exercise,camping,hiking', 25],
            ['Health & Beauty', 'skincare,makeup,vitamins,supplements,health,beauty', 20],
            ['Toys & Games', 'toys,games,puzzles,board games,video games,kids', 30],
            ['Automotive', 'car,auto,parts,accessories,tools,maintenance', 15]
        ];
        
        $insertCatStmt = $conn->prepare("
            INSERT INTO categories (name, keywords, min_discount_threshold) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE keywords = VALUES(keywords)
        ");
        
        foreach ($defaultCategories as $category) {
            $insertCatStmt->execute($category);
            echo "      Added: {$category[0]}\n";
        }
        echo "   ✅ Default categories added\n\n";
    } else {
        echo "   ✅ Categories already configured ($categoriesCount categories found)\n\n";
    }
    
    // Add sample products
    echo "5. Adding sample products for testing...\n";
    $sampleProducts = [
        [
            'name' => 'Amazon Echo Dot (5th Gen)',
            'category' => 'Electronics',
            'brand' => 'Amazon',
            'url' => 'https://www.amazon.com/dp/B09B8V1LZ3',
            'platform' => 'amazon',
            'description' => 'Smart speaker with Alexa - Charcoal'
        ],
        [
            'name' => 'Apple AirPods Pro (2nd Generation)',
            'category' => 'Electronics', 
            'brand' => 'Apple',
            'url' => 'https://www.amazon.com/dp/B0BDHWDR12',
            'platform' => 'amazon',
            'description' => 'Wireless earbuds with MagSafe Charging Case'
        ],
        [
            'name' => 'Instant Pot Duo 7-in-1 Electric Pressure Cooker',
            'category' => 'Home & Garden',
            'brand' => 'Instant Pot',
            'url' => 'https://www.amazon.com/dp/B00FLYWNYQ',
            'platform' => 'amazon',
            'description' => '6 Quart, 14 One-Touch Programs'
        ],
        [
            'name' => 'Nike Air Force 1 \'07',
            'category' => 'Fashion',
            'brand' => 'Nike',
            'url' => 'https://www.ebay.com/sch/i.html?_nkw=nike+air+force+1+07',
            'platform' => 'ebay',
            'description' => 'Classic white sneakers'
        ],
        [
            'name' => 'Canon EOS Rebel T100 DSLR Camera',
            'category' => 'Electronics',
            'brand' => 'Canon', 
            'url' => 'https://www.ebay.com/sch/i.html?_nkw=canon+eos+rebel+t100',
            'platform' => 'ebay',
            'description' => 'Digital SLR camera with 18-55mm lens'
        ],
        [
            'name' => 'Atomic Habits by James Clear',
            'category' => 'Books',
            'brand' => '',
            'url' => 'https://www.amazon.com/dp/0735211299',
            'platform' => 'amazon',
            'description' => 'An Easy & Proven Way to Build Good Habits & Break Bad Ones'
        ]
    ];
    
    $productsStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $productsStmt->execute();
    $existingProducts = $productsStmt->fetchColumn();
    
    if ($existingProducts == 0) {
        echo "   📦 Adding sample products...\n";
        
        $addProductStmt = $conn->prepare("
            INSERT INTO products (name, category, brand, url, platform, description, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($sampleProducts as $product) {
            $addProductStmt->execute([
                $product['name'],
                $product['category'],
                $product['brand'],
                $product['url'],
                $product['platform'],
                $product['description']
            ]);
            
            $productId = $conn->lastInsertId();
            echo "      Added: {$product['name']} (ID: $productId)\n";
            
            // Add sample price data
            $dummyPrice = rand(1999, 9999) / 100; // Random price between $19.99 - $99.99
            $dummyOriginalPrice = $dummyPrice * rand(120, 180) / 100; // 20-80% higher original price
            $discount = round((($dummyOriginalPrice - $dummyPrice) / $dummyOriginalPrice) * 100);
            
            $priceStmt = $conn->prepare("
                INSERT INTO price_history (product_id, price, original_price, discount_percentage, availability, scraped_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            
            $priceStmt->execute([$productId, $dummyPrice, $dummyOriginalPrice, $discount]);
            echo "         Sample price: $" . number_format($dummyPrice, 2) . " ({$discount}% off)\n";
        }
        echo "   ✅ Sample products added\n\n";
    } else {
        echo "   ✅ Products already exist ($existingProducts active products found)\n\n";
    }
    
    // Create database views
    echo "6. Creating database views...\n";
    try {
        $conn->exec("
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
            WHERE p.is_active = 1
        ");
        echo "   ✅ Database views created\n\n";
    } catch (Exception $e) {
        echo "   ⚠️  Could not create views: " . $e->getMessage() . "\n\n";
    }
    
    // Display final statistics
    echo "7. Final system status:\n";
    $stats = $db->getStats();
    echo "   📊 Total Products: " . number_format($stats['total_products']) . "\n";
    echo "   📊 Active Deals: " . number_format($stats['active_deals']) . "\n";
    echo "   📊 Price Records: " . number_format($stats['total_price_records']) . "\n";
    echo "   📊 Pending Notifications: " . number_format($stats['pending_notifications']) . "\n\n";
    
    // Configuration instructions
    echo "===============================================\n";
    echo "🚀 SETUP COMPLETE!\n";
    echo "===============================================\n\n";
    
    echo "Next Steps:\n";
    echo "1. 📧 Update your notification email in the admin panel\n";
    echo "2. 🔧 Configure your database credentials in private/config/database.php\n";
    echo "3. ⏰ Set up cron jobs in your cPanel:\n";
    echo "      Scraping: 0 * * * * /usr/bin/php /path/to/private/cron/scrape.php\n";
    echo "      Notifications: */30 * * * * /usr/bin/php /path/to/private/cron/notify.php\n";
    echo "4. 🌐 Visit your website to see the deals dashboard\n";
    echo "5. 🔒 Access admin panel at /admin.php (password: admin123)\n";
    echo "6. 🧪 Test the scraper with some real product URLs\n\n";
    
    echo "Important Files:\n";
    echo "- Main site: public_html/index.php\n";
    echo "- Admin panel: public_html/admin.php\n";
    echo "- Database config: private/config/database.php\n";
    echo "- Scrapers: private/scrapers/\n";
    echo "- Cron jobs: private/cron/\n\n";
    
    echo "🎉 Your product discount monitoring system is ready!\n";
    echo "===============================================\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
    exit(1);
}
?>
