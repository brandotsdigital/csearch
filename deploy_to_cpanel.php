<?php
/**
 * 🚀 Auto-Deploy Script for cPanel Integration
 * Automatically sets up the Product Discount Scraper after GitHub extraction
 * 
 * Usage: Upload repository files to cPanel, then visit this script
 */

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🚀 cPanel Auto-Deployment</title>
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
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 cPanel Auto-Deployment for Product Discount Scraper</h1>
        <p class='info'>This script will automatically set up your complete 48-file system for cPanel hosting.</p>
        <hr>";

// Step 1: Verify file structure
echo "<h2>📁 Step 1: Verifying File Structure</h2>";
$required_dirs = ['public_html', 'private', 'starter_code'];
$required_files = ['database_setup.sql', 'setup.php'];
$verification_passed = true;

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        echo "<p class='error'>❌ Missing directory: $dir</p>";
        $verification_passed = false;
    } else {
        echo "<p class='success'>✅ Found directory: $dir</p>";
    }
}

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        echo "<p class='error'>❌ Missing file: $file</p>";
        $verification_passed = false;
    } else {
        echo "<p class='success'>✅ Found file: $file</p>";
    }
}

if (!$verification_passed) {
    echo "<p class='error'>❌ <strong>Deployment cannot continue.</strong> Please ensure all files are uploaded correctly.</p>";
    echo "</div></body></html>";
    exit;
}

// Step 2: Move files to correct locations
echo "<h2>📦 Step 2: Moving Files to Correct Locations</h2>";

// Move public_html contents to current directory
if (is_dir('public_html')) {
    $files = glob('public_html/*');
    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename === '.' || $basename === '..') continue;
        
        if (is_dir($file)) {
            if (!file_exists($basename)) {
                rename($file, $basename);
                echo "<p class='success'>✅ Moved directory: $basename</p>";
            } else {
                echo "<p class='warning'>⚠️ Directory already exists: $basename</p>";
            }
        } else {
            if (!file_exists($basename)) {
                copy($file, $basename);
                echo "<p class='success'>✅ Moved file: $basename</p>";
            } else {
                echo "<p class='warning'>⚠️ File already exists: $basename</p>";
            }
        }
    }
    
    // Remove empty public_html directory
    $remaining_files = glob('public_html/*');
    if (empty($remaining_files)) {
        rmdir('public_html');
        echo "<p class='success'>✅ Cleaned up empty public_html directory</p>";
    }
}

// Step 3: Set proper permissions
echo "<h2>🔐 Step 3: Setting File Permissions</h2>";
if (is_dir('private')) {
    @chmod('private', 0755);
    if (is_dir('private/config')) {
        @chmod('private/config', 0755);
    }
    if (file_exists('private/config/database.php')) {
        @chmod('private/config/database.php', 0644);
    }
    echo "<p class='success'>✅ Set permissions for private directory</p>";
}

@chmod('setup.php', 0644);
@chmod('test_system.php', 0644);
echo "<p class='success'>✅ Set permissions for setup files</p>";

// Step 4: Create .htaccess for security
echo "<h2>🛡️ Step 4: Creating Security Configurations</h2>";
$htaccess_content = '# Security configurations for Product Discount Scraper
RewriteEngine On

# Protect private directory
<Files "private/*">
    Order Deny,Allow
    Deny from All
</Files>

# Protect sensitive files
<Files "*.sql">
    Order Deny,Allow
    Deny from All
</Files>

<Files "database_setup.sql">
    Order Deny,Allow
    Deny from All
</Files>

<Files "deploy_to_cpanel.php">
    Order Deny,Allow
    Deny from All
</Files>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP Security settings
php_flag display_errors Off
php_flag log_errors On
php_value date.timezone "America/New_York"
php_value max_execution_time 300
php_value memory_limit 256M
';

if (!file_exists('.htaccess')) {
    file_put_contents('.htaccess', $htaccess_content);
    echo "<p class='success'>✅ Created .htaccess security file</p>";
} else {
    echo "<p class='warning'>⚠️ .htaccess file already exists</p>";
}

// Step 5: Create database configuration template
echo "<h2>🗄️ Step 5: Database Configuration</h2>";
if (!file_exists('private/config/database.php')) {
    $db_template = '<?php
/**
 * 🗄️ Database Configuration for cPanel Hosting
 * Update these values with your cPanel database details
 */

class Database {
    // 🔧 UPDATE THESE VALUES WITH YOUR CPANEL DATABASE DETAILS
    private $host = "localhost";
    private $username = "your_cpanel_username_dbuser";  // Format: cpanelusername_dbuser
    private $password = "your_database_password";       // Your database password
    private $database = "your_cpanel_username_dbname";  // Format: cpanelusername_dbname
    
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration in private/config/database.php");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // Get database size
            $stmt = $this->connection->prepare("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS \'size_mb\'
                FROM information_schema.tables 
                WHERE table_schema = ?
            ");
            $stmt->execute([$this->database]);
            $stats[\'database_size\'] = $stmt->fetchColumn() . \' MB\';
            
            // Get table count
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ");
            $stmt->execute([$this->database]);
            $stats[\'table_count\'] = $stmt->fetchColumn();
            
            return $stats;
        } catch(PDOException $e) {
            return [\'error\' => $e->getMessage()];
        }
    }
}
?>';
    
    if (!is_dir('private/config')) {
        mkdir('private/config', 0755, true);
    }
    
    file_put_contents('private/config/database.php', $db_template);
    echo "<p class='success'>✅ Created database configuration template</p>";
} else {
    echo "<p class='warning'>⚠️ Database configuration already exists</p>";
}

// Step 6: Create setup instructions
echo "<h2>📋 Step 6: Creating Setup Instructions</h2>";
$instructions = '# 🎯 CPANEL SETUP INSTRUCTIONS

## ✅ Files Successfully Deployed!

### 🗄️ Next Steps:

1. **Create Database in cPanel:**
   - Go to MySQL Databases in cPanel
   - Create database: yourusername_discount_scraper
   - Create database user with all privileges
   - Note the database details

2. **Import Database Schema:**
   - Open phpMyAdmin in cPanel
   - Select your database
   - Import the database_setup.sql file

3. **Configure Database Connection:**
   - Edit private/config/database.php
   - Update database credentials with your cPanel details

4. **Run Setup:**
   - Visit: http://yourdomain.com/setup.php
   - Follow the setup wizard

5. **Test System:**
   - Visit: http://yourdomain.com/test_system.php
   - Verify all components are working

6. **Access Dashboard:**
   - Main site: http://yourdomain.com/
   - Admin panel: http://yourdomain.com/admin.php

7. **Set up Cron Jobs:**
   - Add cron jobs in cPanel for automation
   - See DEPLOYMENT_GUIDE.md for details

## 🎉 You\'re Ready to Start Saving Money!

### 🔧 Cron Jobs for Automation:

Add these in cPanel > Cron Jobs:

```
# Main scraping - every hour
0 * * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/scrape.php

# Send notifications - every 30 minutes  
*/30 * * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/notify.php

# Database cleanup - daily at 2 AM
0 2 * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/cleanup.php
```

### 📧 Email Configuration:

Update these settings in your database settings table:
- admin_email: your-email@domain.com
- smtp_host: your-smtp-server.com  
- smtp_port: 587
- smtp_username: your-email@domain.com
- smtp_password: your-email-password

### 🎯 Testing Your Installation:

1. Visit test_system.php to validate installation
2. Check dashboard loads properly
3. Test admin panel access
4. Verify database connection
5. Add a test product to confirm scraping works

## 🆘 Support:

If you encounter issues:
1. Check error logs in cPanel
2. Verify file permissions are correct
3. Ensure database credentials are accurate
4. Review DEPLOYMENT_GUIDE.md for troubleshooting
';

file_put_contents('CPANEL_SETUP_INSTRUCTIONS.txt', $instructions);
echo "<p class='success'>✅ Created setup instructions file</p>";

// Step 7: Final summary
echo "<h2>🎉 Deployment Complete!</h2>";
echo "<div class='step'>";
echo "<h3>✅ What Was Accomplished:</h3>";
echo "<ul>";
echo "<li>✅ All files moved to correct locations</li>";
echo "<li>✅ Proper file permissions set</li>";
echo "<li>✅ Security configurations created (.htaccess)</li>";
echo "<li>✅ Database configuration template ready</li>";
echo "<li>✅ Setup instructions generated</li>";
echo "</ul>";
echo "</div>";

echo "<div class='step'>";
echo "<h3>🚀 Your Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Create Database:</strong> Go to cPanel > MySQL Databases</li>";
echo "<li><strong>Import Schema:</strong> Use phpMyAdmin to import database_setup.sql</li>";
echo "<li><strong>Configure Database:</strong> Edit private/config/database.php</li>";
echo "<li><strong>Run Setup:</strong> Visit setup.php to complete installation</li>";
echo "<li><strong>Test System:</strong> Use test_system.php to verify everything works</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='setup.php' class='btn'>🔧 Run Setup Wizard</a>";
echo "<a href='test_system.php' class='btn'>🧪 Test System</a>";
echo "<a href='CPANEL_SETUP_INSTRUCTIONS.txt' class='btn'>📋 View Instructions</a>";
echo "</div>";

echo "<p class='info'><strong>🔒 Security Note:</strong> For security, delete this deploy_to_cpanel.php file after setup is complete.</p>";

echo "</div></body></html>";
?>
