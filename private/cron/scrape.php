<?php
/**
 * Main Scraping Cron Job
 * This script runs periodically to scrape product prices and detect discounts
 * 
 * Usage: php scrape.php
 * Cron: 0 * * * * /usr/bin/php /path/to/private/cron/scrape.php
 */

// Prevent running if already running
$lockFile = '/tmp/product_scraper.lock';
if (file_exists($lockFile)) {
    $pid = file_get_contents($lockFile);
    if (posix_kill($pid, 0)) {
        exit("Scraper already running (PID: $pid)\n");
    } else {
        unlink($lockFile); // Remove stale lock file
    }
}

// Create lock file
file_put_contents($lockFile, getmypid());

// Include required files
require_once '../config/database.php';
require_once '../scrapers/amazon_scraper.php';
require_once '../scrapers/ebay_scraper.php';

// Set execution time limit
set_time_limit(1800); // 30 minutes max

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting product scraping...\n";
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get scraping settings
    $settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
    $settingsStmt->execute();
    $settings = [];
    while ($row = $settingsStmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $maxProductsPerRun = isset($settings['max_products_per_run']) ? (int)$settings['max_products_per_run'] : 50;
    $minDiscountThreshold = isset($settings['min_discount_threshold']) ? (int)$settings['min_discount_threshold'] : 20;
    
    // Get products to scrape (prioritize older updates)
    echo "[" . date('Y-m-d H:i:s') . "] Getting products to scrape...\n";
    
    $stmt = $conn->prepare("
        SELECT id, name, url, platform, category, brand
        FROM products 
        WHERE is_active = 1 
        AND (updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) OR updated_at IS NULL)
        ORDER BY 
            CASE WHEN updated_at IS NULL THEN 0 ELSE 1 END,
            updated_at ASC
        LIMIT ?
    ");
    $stmt->execute([$maxProductsPerRun]);
    $products = $stmt->fetchAll();
    
    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($products) . " products to scrape\n";
    
    $successCount = 0;
    $errorCount = 0;
    $discountsFound = 0;
    
    foreach ($products as $product) {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Scraping: {$product['name']} ({$product['platform']})\n";
            
            // Determine scraper based on platform
            switch (strtolower($product['platform'])) {
                case 'amazon':
                    $scraper = new AmazonScraper();
                    break;
                case 'ebay':
                    $scraper = new EbayScraper();
                    break;
                default:
                    echo "  ⚠️  Unknown platform: {$product['platform']}\n";
                    continue 2;
            }
            
            // Scrape product data
            $data = $scraper->scrapeProduct($product['url']);
            
            if ($data) {
                // Calculate discount
                $discount = 0;
                if ($data['original_price'] > 0 && $data['price'] > 0) {
                    $discount = round((($data['original_price'] - $data['price']) / $data['original_price']) * 100);
                }
                
                // Get previous price for comparison
                $prevPriceStmt = $conn->prepare("
                    SELECT price, discount_percentage 
                    FROM price_history 
                    WHERE product_id = ? 
                    ORDER BY scraped_at DESC 
                    LIMIT 1
                ");
                $prevPriceStmt->execute([$product['id']]);
                $prevPrice = $prevPriceStmt->fetch();
                
                // Insert new price history
                $priceHistoryStmt = $conn->prepare("
                    INSERT INTO price_history 
                    (product_id, price, original_price, discount_percentage, availability, scraped_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $priceHistoryStmt->execute([
                    $product['id'],
                    $data['price'],
                    $data['original_price'],
                    $discount,
                    $data['availability'] ? 1 : 0
                ]);
                
                // Update product metadata
                $updateProductStmt = $conn->prepare("
                    UPDATE products 
                    SET 
                        name = COALESCE(NULLIF(?, ''), name),
                        image_url = COALESCE(NULLIF(?, ''), image_url),
                        brand = COALESCE(NULLIF(?, ''), brand),
                        category = COALESCE(NULLIF(?, ''), category),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updateProductStmt->execute([
                    $data['name'],
                    $data['image_url'],
                    $data['brand'],
                    $data['category'],
                    $product['id']
                ]);
                
                // Check if we should create a notification
                $shouldNotify = false;
                $notificationMessage = '';
                $notificationType = '';
                
                // Price drop notification
                if ($discount >= $minDiscountThreshold) {
                    $shouldNotify = true;
                    $notificationMessage = "Great deal! {$discount}% off - now \${$data['price']} (was \${$data['original_price']})";
                    $notificationType = 'price_drop';
                    $discountsFound++;
                }
                
                // Back in stock notification
                if ($prevPrice && !$prevPrice['availability'] && $data['availability']) {
                    $shouldNotify = true;
                    $notificationMessage = "Back in stock! Price: \${$data['price']}";
                    $notificationType = 'back_in_stock';
                }
                
                // Significant price drop (even if below threshold)
                if ($prevPrice && $data['price'] > 0) {
                    $priceDropPercent = (($prevPrice['price'] - $data['price']) / $prevPrice['price']) * 100;
                    if ($priceDropPercent >= 15) { // 15% price drop
                        $shouldNotify = true;
                        $notificationMessage = "Price dropped {$priceDropPercent}% to \${$data['price']} (was \${$prevPrice['price']})";
                        $notificationType = 'price_drop';
                    }
                }
                
                if ($shouldNotify) {
                    // Check if we already have a recent notification for this product
                    $recentNotificationStmt = $conn->prepare("
                        SELECT id FROM notifications 
                        WHERE product_id = ? 
                        AND notification_type = ?
                        AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
                        LIMIT 1
                    ");
                    $recentNotificationStmt->execute([$product['id'], $notificationType]);
                    
                    if (!$recentNotificationStmt->fetch()) {
                        $notificationStmt = $conn->prepare("
                            INSERT INTO notifications 
                            (product_id, notification_type, message, created_at) 
                            VALUES (?, ?, ?, NOW())
                        ");
                        $notificationStmt->execute([
                            $product['id'],
                            $notificationType,
                            $notificationMessage
                        ]);
                        
                        echo "  🔔 Notification created: $notificationMessage\n";
                    }
                }
                
                echo "  ✅ Price: \${$data['price']} (Discount: {$discount}%)\n";
                $successCount++;
                
            } else {
                echo "  ❌ Failed to extract product data\n";
                $errorCount++;
            }
            
            // Be respectful - delay between products
            sleep(rand(2, 5));
            
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            $errorCount++;
            
            // Log the error
            error_log("Scraping error for product {$product['id']}: " . $e->getMessage());
        }
    }
    
    // Update statistics
    $statsStmt = $conn->prepare("
        INSERT INTO scraping_logs (platform, status, error_message, scraped_at)
        VALUES ('system', 'success', ?, NOW())
    ");
    $statsStmt->execute(["Scraped $successCount products, $errorCount errors, $discountsFound discounts found"]);
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Scraping completed:\n";
    echo "  ✅ Successful: $successCount\n";
    echo "  ❌ Errors: $errorCount\n";
    echo "  🔔 Discounts found: $discountsFound\n";
    
    // Clean up old data if needed
    if (rand(1, 10) === 1) { // 10% chance
        echo "[" . date('Y-m-d H:i:s') . "] Running cleanup...\n";
        
        // Clean old price history (keep last 90 days)
        $cleanupStmt = $conn->prepare("
            DELETE FROM price_history 
            WHERE scraped_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $cleanupStmt->execute();
        $deletedRows = $cleanupStmt->rowCount();
        
        if ($deletedRows > 0) {
            echo "  🗑️  Cleaned up $deletedRows old price records\n";
        }
        
        // Clean old logs
        $logCleanupStmt = $conn->prepare("
            DELETE FROM scraping_logs 
            WHERE scraped_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $logCleanupStmt->execute();
        $deletedLogs = $logCleanupStmt->rowCount();
        
        if ($deletedLogs > 0) {
            echo "  🗑️  Cleaned up $deletedLogs old log records\n";
        }
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    error_log("Fatal scraping error: " . $e->getMessage());
} finally {
    // Remove lock file
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    echo "[" . date('Y-m-d H:i:s') . "] Scraping process ended\n";
}
?>
