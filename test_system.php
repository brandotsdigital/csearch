<?php
/**
 * System Test Script
 * Verify all components are working correctly
 * 
 * Run this after deployment to ensure everything is functioning
 */

echo "🧪 Product Scraper System - Test Suite\n";
echo "======================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Database Connection
echo "🔌 Testing Database Connection...\n";
try {
    require_once '../private/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $tests[] = "✅ Database connection successful";
    $passed++;
} catch (Exception $e) {
    $tests[] = "❌ Database connection failed: " . $e->getMessage();
    $failed++;
}

// Test 2: Required Tables
echo "🗄️ Checking Database Tables...\n";
if (isset($conn)) {
    $requiredTables = ['products', 'price_history', 'categories', 'notifications', 'settings', 'scraping_logs'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if (!$stmt->fetch()) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        $tests[] = "✅ All required tables exist";
        $passed++;
    } else {
        $tests[] = "❌ Missing tables: " . implode(', ', $missingTables);
        $failed++;
    }
} else {
    $tests[] = "❌ Cannot check tables - no database connection";
    $failed++;
}

// Test 3: Scraper Classes
echo "🕷️ Testing Scraper Classes...\n";
$scraperFiles = [
    '../private/scrapers/base_scraper.php',
    '../private/scrapers/amazon_scraper.php', 
    '../private/scrapers/ebay_scraper.php'
];

$scraperTestPassed = true;
foreach ($scraperFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        $tests[] = "❌ Missing scraper file: " . basename($file);
        $scraperTestPassed = false;
        $failed++;
    }
}

if ($scraperTestPassed) {
    try {
        // Test if classes can be instantiated
        if (class_exists('AmazonScraper')) {
            $tests[] = "✅ Scraper classes loaded successfully";
            $passed++;
        } else {
            $tests[] = "❌ Scraper classes not found";
            $failed++;
        }
    } catch (Exception $e) {
        $tests[] = "❌ Scraper class error: " . $e->getMessage();
        $failed++;
    }
}

// Test 4: Cron Scripts
echo "⏰ Checking Cron Scripts...\n";
$cronFiles = [
    '../private/cron/scrape.php',
    '../private/cron/notify.php'
];

$cronTestPassed = true;
foreach ($cronFiles as $file) {
    if (file_exists($file)) {
        $tests[] = "✅ " . basename($file) . " exists";
    } else {
        $tests[] = "❌ Missing cron file: " . basename($file);
        $cronTestPassed = false;
        $failed++;
    }
}

if ($cronTestPassed) {
    $passed += 2;
}

// Test 5: Web Files
echo "🌐 Checking Web Interface...\n";
$webFiles = [
    '../public_html/index.php',
    '../public_html/admin.php',
    '../public_html/assets/css/style.css',
    '../public_html/assets/js/app.js'
];

$webTestPassed = true;
foreach ($webFiles as $file) {
    if (file_exists($file)) {
        $tests[] = "✅ " . str_replace('../public_html/', '', $file) . " exists";
    } else {
        $tests[] = "❌ Missing web file: " . str_replace('../public_html/', '', $file);
        $webTestPassed = false;
        $failed++;
    }
}

if ($webTestPassed) {
    $passed += 4;
}

// Test 6: Permissions
echo "🔐 Checking File Permissions...\n";
$writeDirectories = [
    '../private/logs',
    '../public_html/uploads'
];

foreach ($writeDirectories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (is_writable($dir)) {
        $tests[] = "✅ " . basename($dir) . " directory is writable";
        $passed++;
    } else {
        $tests[] = "❌ " . basename($dir) . " directory is not writable";
        $failed++;
    }
}

// Test 7: Sample Data
echo "📝 Checking Sample Data...\n";
if (isset($conn)) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM categories");
        $categoryCount = $stmt->fetchColumn();
        
        if ($categoryCount > 0) {
            $tests[] = "✅ Sample categories exist ($categoryCount found)";
            $passed++;
        } else {
            $tests[] = "⚠️ No categories found - run setup.php to add sample data";
        }
        
        $stmt = $conn->query("SELECT COUNT(*) FROM settings");
        $settingsCount = $stmt->fetchColumn();
        
        if ($settingsCount > 0) {
            $tests[] = "✅ System settings exist ($settingsCount found)";
            $passed++;
        } else {
            $tests[] = "⚠️ No settings found - run setup.php to initialize";
        }
        
    } catch (Exception $e) {
        $tests[] = "❌ Error checking sample data: " . $e->getMessage();
        $failed++;
    }
}

// Test 8: PHP Extensions
echo "🔧 Checking PHP Extensions...\n";
$requiredExtensions = ['pdo_mysql', 'curl', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $tests[] = "✅ PHP extension '$ext' is loaded";
        $passed++;
    } else {
        $tests[] = "❌ PHP extension '$ext' is missing";
        $failed++;
    }
}

// Display Results
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 TEST RESULTS\n";
echo str_repeat("=", 50) . "\n";

foreach ($tests as $test) {
    echo $test . "\n";
}

echo "\n📈 SUMMARY:\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "📊 Total: " . ($passed + $failed) . "\n";

if ($failed == 0) {
    echo "\n🎉 ALL TESTS PASSED! Your system is ready to use.\n";
    echo "\n🚀 Next Steps:\n";
    echo "1. Access your dashboard at: http://yourdomain.com/\n";
    echo "2. Access admin panel at: http://yourdomain.com/admin.php\n";
    echo "3. Add products to monitor\n";
    echo "4. Set up cron jobs for automation\n";
    echo "5. Configure email settings\n";
} else {
    echo "\n⚠️ Some tests failed. Please review and fix the issues above.\n";
    echo "\n🔧 Common Solutions:\n";
    echo "- Run database_setup.sql in phpMyAdmin\n";
    echo "- Update database credentials in database.php\n";
    echo "- Check file permissions\n";
    echo "- Install missing PHP extensions\n";
    echo "- Run setup.php for initialization\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>
