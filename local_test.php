<?php
/**
 * 🧪 LOCAL TESTING SCRIPT
 * Quick validation of your Product Discount Scraper system
 * Run this in your local environment to test functionality
 */

echo "🧪 LOCAL TESTING - Product Discount Scraper\n";
echo "==========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Test 1: Check if we're in the right directory
echo "📁 Testing File Structure...\n";
if (file_exists('public_html/index.php')) {
    $success[] = "✅ Main dashboard file found";
} else {
    $errors[] = "❌ public_html/index.php not found";
}

if (file_exists('private/config/database.php')) {
    $success[] = "✅ Database configuration found";
} else {
    $errors[] = "❌ private/config/database.php not found";
}

if (file_exists('database_setup.sql')) {
    $success[] = "✅ Database schema file found";
} else {
    $errors[] = "❌ database_setup.sql not found";
}

// Test 2: Check PHP version and extensions
echo "\n🔧 Testing PHP Environment...\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    $success[] = "✅ PHP version " . PHP_VERSION . " is supported";
} else {
    $warnings[] = "⚠️ PHP version " . PHP_VERSION . " may not be optimal (7.4+ recommended)";
}

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        $success[] = "✅ PHP extension '$ext' is loaded";
    } else {
        $errors[] = "❌ PHP extension '$ext' is missing";
    }
}

// Test 3: Database connection (if possible)
echo "\n🗄️ Testing Database Connection...\n";
try {
    if (file_exists('private/config/database.php')) {
        // Don't actually include it yet, just check if file exists and is readable
        if (is_readable('private/config/database.php')) {
            $success[] = "✅ Database configuration file is readable";
            
            // Check if database credentials are updated
            $dbContent = file_get_contents('private/config/database.php');
            if (strpos($dbContent, 'your_db_user') !== false) {
                $warnings[] = "⚠️ Database credentials need to be updated";
            } else {
                $success[] = "✅ Database credentials appear to be configured";
            }
        } else {
            $errors[] = "❌ Database configuration file is not readable";
        }
    }
} catch (Exception $e) {
    $warnings[] = "⚠️ Could not test database connection: " . $e->getMessage();
}

// Test 4: Check web server compatibility
echo "\n🌐 Testing Web Server Compatibility...\n";
if (isset($_SERVER['HTTP_HOST'])) {
    $success[] = "✅ Running in web server environment";
    $success[] = "✅ Server: " . $_SERVER['HTTP_HOST'];
} else {
    $warnings[] = "⚠️ Not running in web server (command line mode)";
}

// Test 5: Check file permissions
echo "\n🔐 Testing File Permissions...\n";
$test_dirs = ['private', 'public_html', 'starter_code'];
foreach ($test_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            $success[] = "✅ Directory '$dir' is readable";
        } else {
            $errors[] = "❌ Directory '$dir' is not readable";
        }
    }
}

// Test 6: Memory and execution limits
echo "\n⚡ Testing PHP Configuration...\n";
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

$success[] = "✅ Memory limit: $memory_limit";
$success[] = "✅ Max execution time: $max_execution_time seconds";

if (ini_get('allow_url_fopen')) {
    $success[] = "✅ allow_url_fopen is enabled (good for scraping)";
} else {
    $warnings[] = "⚠️ allow_url_fopen is disabled (may affect scraping)";
}

// Test 7: Check core files content
echo "\n📄 Testing Core Files...\n";
$core_files = [
    'public_html/index.php' => 'Main Dashboard',
    'public_html/admin.php' => 'Admin Panel',
    'private/scrapers/base_scraper.php' => 'Base Scraper Class',
    'private/cron/scrape.php' => 'Scraping Script',
    'private/cron/notify.php' => 'Notification Script'
];

foreach ($core_files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        if ($size > 1000) {  // Reasonable file size
            $success[] = "✅ $description ($file) - " . number_format($size) . " bytes";
        } else {
            $warnings[] = "⚠️ $description ($file) seems small - " . number_format($size) . " bytes";
        }
    } else {
        $errors[] = "❌ $description ($file) not found";
    }
}

// Display Results
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 LOCAL TESTING RESULTS\n";
echo str_repeat("=", 50) . "\n";

if (!empty($success)) {
    echo "\n✅ SUCCESS (" . count($success) . " items):\n";
    foreach ($success as $item) {
        echo "$item\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ WARNINGS (" . count($warnings) . " items):\n";
    foreach ($warnings as $item) {
        echo "$item\n";
    }
}

if (!empty($errors)) {
    echo "\n❌ ERRORS (" . count($errors) . " items):\n";
    foreach ($errors as $item) {
        echo "$item\n";
    }
}

// Overall assessment
echo "\n📈 OVERALL ASSESSMENT:\n";
if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 EXCELLENT! Your system is ready for testing!\n";
        echo "\n🚀 NEXT STEPS:\n";
        echo "1. Set up XAMPP or WAMP server\n";
        echo "2. Copy project to htdocs folder\n";
        echo "3. Create MySQL database\n";
        echo "4. Import database_setup.sql\n";
        echo "5. Update database credentials\n";
        echo "6. Access http://localhost/Project%20Scrap/public_html/\n";
    } else {
        echo "👍 GOOD! Your system should work with minor adjustments.\n";
        echo "Please address the warnings above.\n";
    }
} else {
    echo "🔧 NEEDS ATTENTION! Please fix the errors above before testing.\n";
}

echo "\n📋 TESTING URLS (once XAMPP is running):\n";
echo "🏠 Dashboard: http://localhost/Project%20Scrap/public_html/\n";
echo "⚙️ Admin: http://localhost/Project%20Scrap/public_html/admin.php\n";
echo "🔧 Setup: http://localhost/Project%20Scrap/setup.php\n";
echo "🧪 Test: http://localhost/Project%20Scrap/test_system.php\n";
echo "🗄️ Database: http://localhost/phpmyadmin\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Happy testing! 🎯\n";
?>
