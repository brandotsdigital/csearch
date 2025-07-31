<?php
/**
 * Setup Script - Initialize Database and Add Sample Data
 * Web-based setup for Product Discount Scraper
 */

// Start output buffering and enable error reporting for debugging
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Product Scraper Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #007bff; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        pre { background: #f1f1f1; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .progress { width: 100%; background: #e9ecef; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background: #007bff; border-radius: 5px; text-align: center; color: white; line-height: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Product Discount Scraper Setup</h1>
        <p class="info">Setting up your automated discount monitoring system...</p>
        
        <?php
        // Try to include database configuration
        $config_found = false;
        $possible_paths = [
            'private/config/database.php',
            '../private/config/database.php',
            './private/config/database.php'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $config_found = true;
                echo "<p class='success'>✅ Database configuration loaded from: $path</p>";
                break;
            }
        }
        
        if (!$config_found) {
            echo "<div class='step'>";
            echo "<h3 class='error'>❌ Database Configuration Missing</h3>";
            echo "<p>Please ensure the database configuration file exists at: <code>private/config/database.php</code></p>";
            echo "<p>Check that all files were uploaded correctly from the repository.</p>";
            echo "</div>";
        } else {
            try {
                echo "<div class='step'>";
                echo "<h3>🔌 Testing Database Connection</h3>";
                
                $db = new Database();
                $conn = $db->getConnection();
                echo "<p class='success'>✅ Connected to database: pyramid_new</p>";
                echo "<div class='progress'><div class='progress-bar' style='width: 25%'>25%</div></div>";
                echo "</div>";
                
                // Check tables
                echo "<div class='step'>";
                echo "<h3>🗄️ Verifying Database Tables</h3>";
                
                $required_tables = ['products', 'price_history', 'categories', 'notifications', 'settings', 'scraping_logs'];
                $existing_tables = [];
                
                foreach ($required_tables as $table) {
                    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    if ($stmt->fetch()) {
                        $existing_tables[] = $table;
                        echo "<p class='success'>✅ $table</p>";
                    } else {
                        echo "<p class='error'>❌ $table (missing)</p>";
                    }
                }
                
                if (count($existing_tables) < count($required_tables)) {
                    echo "<div class='warning'>";
                    echo "<h4>⚠️ Import Database Schema Required</h4>";
                    echo "<p><strong>Action needed:</strong> Import database_setup.sql first</p>";
                    echo "<ol>";
                    echo "<li>cPanel → phpMyAdmin</li>";
                    echo "<li>Select database: <strong>pyramid_new</strong></li>";
                    echo "<li>Import → Upload <strong>database_setup.sql</strong></li>";
                    echo "<li><a href='?' class='btn'>🔄 Refresh this page</a></li>";
                    echo "</ol>";
                    echo "</div>";
                } else {
                    echo "<p class='success'>✅ All tables verified</p>";
                    echo "<div class='progress'><div class='progress-bar' style='width: 60%'>60%</div></div>";
                    echo "</div>";
                    
                    // Initialize settings
                    echo "<div class='step'>";
                    echo "<h3>⚙️ Configuring System Settings</h3>";
                    
                    $settings = [
                        ['notification_threshold', '20', 'Minimum discount for alerts'],
                        ['admin_email', 'admin@pyramidci.org', 'Admin email address'],
                        ['site_title', 'Pyramid Discount Monitor', 'Site title'],
                        ['scraping_interval', '3600', 'Scraping interval (seconds)'],
                        ['smtp_host', 'localhost', 'Email server host'],
                        ['smtp_port', '587', 'Email server port']
                    ];
                    
                    foreach ($settings as $setting) {
                        $check = $conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
                        $check->execute([$setting[0]]);
                        
                        if ($check->fetchColumn() == 0) {
                            $insert = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
                            $insert->execute($setting);
                            echo "<p class='success'>✅ {$setting[0]}</p>";
                        } else {
                            echo "<p class='info'>ℹ️ {$setting[0]} (exists)</p>";
                        }
                    }
                    
                    echo "<div class='progress'><div class='progress-bar' style='width: 85%'>85%</div></div>";
                    echo "</div>";
                    
                    // Setup categories
                    echo "<div class='step'>";
                    echo "<h3>📁 Setting Up Categories</h3>";
                    
                    $cat_check = $conn->prepare("SELECT COUNT(*) FROM categories");
                    $cat_check->execute();
                    
                    if ($cat_check->fetchColumn() == 0) {
                        $categories = [
                            ['Electronics', 'Phones, tablets, computers'],
                            ['Clothing', 'Fashion and accessories'],
                            ['Home & Garden', 'Furniture and appliances'],
                            ['Sports', 'Fitness and sporting goods']
                        ];
                        
                        foreach ($categories as $cat) {
                            $insert = $conn->prepare("INSERT INTO categories (name, description, is_active) VALUES (?, ?, 1)");
                            $insert->execute($cat);
                            echo "<p class='success'>✅ {$cat[0]}</p>";
                        }
                    } else {
                        echo "<p class='info'>ℹ️ Categories already exist</p>";
                    }
                    
                    echo "<div class='progress'><div class='progress-bar' style='width: 100%'>100%</div></div>";
                    echo "</div>";
                    
                    // Success message
                    echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>";
                    echo "<h3 class='success'>🎉 Setup Complete!</h3>";
                    echo "<p>Your Product Discount Scraper is ready!</p>";
                    
                    echo "<div style='margin: 20px 0;'>";
                    echo "<a href='index.php' class='btn'>🏠 Dashboard</a>";
                    echo "<a href='admin.php' class='btn'>⚙️ Admin Panel</a>";
                    echo "<a href='test_system.php' class='btn'>🧪 Test System</a>";
                    echo "</div>";
                    
                    echo "<h4>✅ System Status:</h4>";
                    echo "<ul>";
                    echo "<li>Database connected: pyramid_new</li>";
                    echo "<li>All tables verified</li>";
                    echo "<li>Settings configured</li>";
                    echo "<li>Categories initialized</li>";
                    echo "<li>Ready for product monitoring</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='step'>";
                echo "<h3 class='error'>❌ Setup Error</h3>";
                echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<h4>🔧 Check These:</h4>";
                echo "<ul>";
                echo "<li>Database 'pyramid_new' exists in cPanel</li>";
                echo "<li>User 'pyramad_new' has full permissions</li>";
                echo "<li>Password 'omnamo@@333' is correct</li>";
                echo "<li>database_setup.sql has been imported</li>";
                echo "</ul>";
                echo "<p><a href='?' class='btn'>🔄 Try Again</a></p>";
                echo "</div>";
            }
        }
        ?>
    </div>
</body>
</html><?php ob_end_flush(); ?>

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
