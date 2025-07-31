<?php
/**
 * Setup Script - Initialize Database and Add Sample Data
 * Run this once to set up your product scraper system
 */

// Start output buffering and error handling
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Web interface
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
        pre { background: #f1f1f1; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
        .progress { width: 100%; background: #e9ecef; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background: #007bff; border-radius: 5px; text-align: center; color: white; line-height: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Product Discount Scraper Setup</h1>
        <p class="info">This setup will initialize your discount monitoring system.</p>
        
        <?php
        // Include database configuration with proper path handling
        $db_config_path = 'private/config/database.php';
        if (!file_exists($db_config_path)) {
            // Try alternative paths
            $alt_paths = [
                '../private/config/database.php',
                './private/config/database.php',
                'config/database.php'
            ];
            
            foreach ($alt_paths as $path) {
                if (file_exists($path)) {
                    $db_config_path = $path;
                    break;
                }
            }
        }
        
        if (!file_exists($db_config_path)) {
            echo "<div class='step'>";
            echo "<h3 class='error'>❌ Database Configuration Not Found</h3>";
            echo "<p>Could not locate database.php file. Please ensure the file structure is correct:</p>";
            echo "<pre>your-domain/
├── setup.php (this file)
├── private/
│   └── config/
│       └── database.php
└── ...</pre>";
            echo "<p class='warning'>Make sure you've uploaded all files from the repository and the private/ folder exists.</p>";
            echo "</div>";
        } else {
            echo "<p class='success'>✅ Database configuration found at: $db_config_path</p>";
            
            try {
                require_once $db_config_path;
                
                echo "<div class='step'>";
                echo "<h3>📊 Step 1: Testing Database Connection</h3>";
                
                // Test database connection
                $db = new Database();
                $conn = $db->getConnection();
                echo "<p class='success'>✅ Database connection successful!</p>";
                echo "<p><strong>Connected to database:</strong> pyramid_new</p>";
                echo "<p><strong>Using credentials:</strong> pyramad_new</p>";
                
                // Progress bar
                echo "<div class='progress'><div class='progress-bar' style='width: 20%'>20%</div></div>";
                echo "</div>";
                
                // Check if tables exist
                echo "<div class='step'>";
                echo "<h3>🗄️ Step 2: Checking Database Tables</h3>";
                
                $tables = ['products', 'price_history', 'categories', 'notifications', 'settings', 'scraping_logs'];
                $existingTables = [];
                
                foreach ($tables as $table) {
                    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    if ($stmt->fetch()) {
                        $existingTables[] = $table;
                        echo "<p class='success'>✅ Table '$table' exists</p>";
                    } else {
                        echo "<p class='error'>❌ Table '$table' missing</p>";
                    }
                }
                
                if (count($existingTables) < count($tables)) {
                    echo "<div class='warning'>";
                    echo "<h4>⚠️ Missing Tables Detected</h4>";
                    echo "<p><strong>Action Required:</strong> Please import the database schema first.</p>";
                    echo "<ol>";
                    echo "<li>Go to your cPanel → phpMyAdmin</li>";
                    echo "<li>Select database: <strong>pyramid_new</strong></li>";
                    echo "<li>Click <strong>Import</strong> tab</li>";
                    echo "<li>Upload and import: <strong>database_setup.sql</strong></li>";
                    echo "<li>Return here and refresh this page</li>";
                    echo "</ol>";
                    echo "<p><a href='?' class='btn'>🔄 Refresh After Import</a></p>";
                    echo "</div>";
                } else {
                    echo "<p class='success'>✅ All required tables exist!</p>";
                    echo "<div class='progress'><div class='progress-bar' style='width: 50%'>50%</div></div>";
                    echo "</div>";
                    
                    // Initialize default settings
                    echo "<div class='step'>";
                    echo "<h3>⚙️ Step 3: Initializing System Settings</h3>";
                    
                    // Check and insert default settings
                    $defaultSettings = [
                        ['notification_threshold', '20', 'Minimum discount percentage for notifications'],
                        ['admin_email', 'admin@pyramidci.org', 'Administrator email address'],
                        ['site_title', 'Pyramid Discount Monitor', 'Website title'],
                        ['max_products', '1000', 'Maximum number of products to monitor'],
                        ['scraping_interval', '3600', 'Scraping interval in seconds (1 hour)'],
                        ['notification_interval', '1800', 'Notification check interval in seconds (30 minutes)'],
                        ['smtp_host', 'localhost', 'SMTP server host'],
                        ['smtp_port', '587', 'SMTP server port'],
                        ['smtp_username', '', 'SMTP username'],
                        ['smtp_password', '', 'SMTP password'],
                        ['last_scrape', '', 'Last successful scrape timestamp'],
                        ['total_products_scraped', '0', 'Total products scraped counter'],
                        ['total_notifications_sent', '0', 'Total notifications sent counter']
                    ];
                    
                    foreach ($defaultSettings as $setting) {
                        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
                        $checkStmt->execute([$setting[0]]);
                        
                        if ($checkStmt->fetchColumn() == 0) {
                            $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
                            $insertStmt->execute($setting);
                            echo "<p class='success'>✅ Added setting: {$setting[0]}</p>";
                        } else {
                            echo "<p class='info'>ℹ️ Setting already exists: {$setting[0]}</p>";
                        }
                    }
                    
                    echo "<div class='progress'><div class='progress-bar' style='width: 75%'>75%</div></div>";
                    echo "</div>";
                    
                    // Add sample categories if none exist
                    echo "<div class='step'>";
                    echo "<h3>📁 Step 4: Setting Up Categories</h3>";
                    
                    $categoryStmt = $conn->prepare("SELECT COUNT(*) FROM categories");
                    $categoryStmt->execute();
                    $categoryCount = $categoryStmt->fetchColumn();
                    
                    if ($categoryCount == 0) {
                        $sampleCategories = [
                            ['Electronics', 'Phones, tablets, computers, and gadgets'],
                            ['Clothing', 'Fashion items, shoes, and accessories'],
                            ['Home & Garden', 'Furniture, appliances, and home decor'],
                            ['Sports', 'Fitness equipment and sporting goods'],
                            ['Books', 'Physical and digital books'],
                            ['Toys', 'Children toys and games'],
                            ['Health & Beauty', 'Cosmetics, supplements, and health products'],
                            ['Automotive', 'Car parts and accessories']
                        ];
                        
                        foreach ($sampleCategories as $category) {
                            $insertStmt = $conn->prepare("INSERT INTO categories (name, description, is_active) VALUES (?, ?, 1)");
                            $insertStmt->execute($category);
                            echo "<p class='success'>✅ Added category: {$category[0]}</p>";
                        }
                    } else {
                        echo "<p class='info'>ℹ️ Categories already exist ($categoryCount categories)</p>";
                    }
                    
                    echo "<div class='progress'><div class='progress-bar' style='width: 100%'>100%</div></div>";
                    echo "</div>";
                    
                    // Final success message
                    echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>";
                    echo "<h3 class='success'>🎉 Setup Complete!</h3>";
                    echo "<p>Your Product Discount Scraper is now ready to use!</p>";
                    
                    echo "<h4>🚀 What's Next:</h4>";
                    echo "<ol>";
                    echo "<li><strong>Access Dashboard:</strong> <a href='index.php'>View your main dashboard</a></li>";
                    echo "<li><strong>Admin Panel:</strong> <a href='admin.php'>Manage products and settings</a></li>";
                    echo "<li><strong>Add Products:</strong> Use the admin panel to add Amazon/eBay products</li>";
                    echo "<li><strong>Set up Cron Jobs:</strong> Configure automated scraping in cPanel</li>";
                    echo "<li><strong>Test System:</strong> <a href='test_system.php'>Run system diagnostics</a></li>";
                    echo "</ol>";
                    
                    echo "<div style='margin-top: 20px;'>";
                    echo "<a href='index.php' class='btn'>🏠 Go to Dashboard</a>";
                    echo "<a href='admin.php' class='btn'>⚙️ Open Admin Panel</a>";
                    echo "<a href='test_system.php' class='btn'>🧪 Test System</a>";
                    echo "</div>";
                    
                    echo "<h4>📋 Quick Setup Summary:</h4>";
                    echo "<ul>";
                    echo "<li>✅ Database connection established</li>";
                    echo "<li>✅ All tables verified</li>";
                    echo "<li>✅ Default settings configured</li>";
                    echo "<li>✅ Categories initialized</li>";
                    echo "<li>✅ System ready for product monitoring</li>";
                    echo "</ul>";
                    
                    echo "<p class='info'><strong>🔒 Security Note:</strong> For security, consider deleting this setup.php file after completing setup.</p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='step'>";
                echo "<h3 class='error'>❌ Database Connection Error</h3>";
                echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                
                echo "<h4>🔧 Troubleshooting Steps:</h4>";
                echo "<ol>";
                echo "<li><strong>Verify Database Exists:</strong> Check that database 'pyramid_new' exists in cPanel MySQL Databases</li>";
                echo "<li><strong>Check User Permissions:</strong> Ensure user 'pyramad_new' has ALL PRIVILEGES on the database</li>";
                echo "<li><strong>Verify Password:</strong> Confirm the password 'omnamo@@333' is correct</li>";
                echo "<li><strong>Import Schema:</strong> Make sure you've imported database_setup.sql via phpMyAdmin</li>";
                echo "</ol>";
                
                echo "<h4>📋 Current Configuration:</h4>";
                echo "<pre>";
                echo "Database: pyramid_new\n";
                echo "Username: pyramad_new\n";
                echo "Password: omnamo@@333\n";
                echo "Host: localhost\n";
                echo "</pre>";
                
                echo "<p><a href='?' class='btn'>🔄 Try Again</a></p>";
                echo "</div>";
            }
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p class="info"><strong>Need Help?</strong> Check the documentation files or contact support.</p>
        </div>
    </div>
</body>
</html><?php
// End output buffering
ob_end_flush();
?>
