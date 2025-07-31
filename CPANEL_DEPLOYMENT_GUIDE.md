# 🚀 Deploy from GitHub to cPanel - Complete Integration Guide

## 📋 **Overview**
Deploy your Product Discount Scraper directly from GitHub repository to cPanel hosting with automated integration.

## 🎯 **Method 1: Direct Git Clone to cPanel (Recommended)**

### **Step 1: Access cPanel File Manager**
1. Log into your cPanel hosting account
2. Open **File Manager**
3. Navigate to your domain's **public_html** folder
4. Create a backup of existing files (if any)

### **Step 2: Clone Repository via Terminal**
If your hosting supports SSH/Terminal access:

```bash
# Navigate to your hosting directory
cd /home/yourusername/public_html

# Clone the repository
git clone https://github.com/brandotsdigital/csearch.git discount-scraper

# Move files to proper location
mv discount-scraper/public_html/* .
mv discount-scraper/private ../private
mv discount-scraper/*.php .
mv discount-scraper/*.sql .

# Clean up
rm -rf discount-scraper
```

### **Step 3: Alternative - Manual Download & Upload**
If SSH is not available:

1. **Download Repository**
   - Go to: https://github.com/brandotsdigital/csearch
   - Click **Code** → **Download ZIP**
   - Extract the ZIP file locally

2. **Upload via File Manager**
   - Upload all files to cPanel File Manager
   - Extract the archive in cPanel
   - Move files to correct directories

## 🗄️ **Method 2: Database Integration**

### **Step 1: Create Database in cPanel**
1. Open **MySQL Databases** in cPanel
2. Create new database: `yourusername_discount_scraper`
3. Create database user with full privileges
4. Note down: database name, username, password

### **Step 2: Import Database Schema**
1. Open **phpMyAdmin** in cPanel
2. Select your new database
3. Click **Import** tab
4. Upload `database_setup.sql` file
5. Click **Go** to import

### **Step 3: Configure Database Connection**
Edit `private/config/database.php`:

```php
// Update these with your cPanel database details
private $host = 'localhost';
private $username = 'yourusername_dbuser';     // Your cPanel DB user
private $password = 'your_db_password';        // Your DB password
private $database = 'yourusername_discount_scraper'; // Your DB name
```

## ⚙️ **Method 3: Automated cPanel Integration**

### **Step 1: Create Auto-Deploy Script**
Save this as `deploy_to_cpanel.php` in your repository:

```php
<?php
/**
 * 🚀 Auto-Deploy Script for cPanel Integration
 * Automatically sets up the system after repository extraction
 */

echo "🚀 CPANEL AUTO-DEPLOYMENT STARTING...\n";
echo "=====================================\n\n";

// Check if running in cPanel environment
if (!isset($_SERVER['HTTP_HOST'])) {
    die("❌ This script must be run in a web server environment\n");
}

// Step 1: Verify file structure
echo "📁 Verifying file structure...\n";
$required_dirs = ['public_html', 'private', 'starter_code'];
$required_files = ['database_setup.sql', 'setup.php'];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        die("❌ Missing directory: $dir\n");
    }
    echo "✅ Found directory: $dir\n";
}

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("❌ Missing file: $file\n");
    }
    echo "✅ Found file: $file\n";
}

// Step 2: Move files to correct locations
echo "\n📦 Moving files to correct locations...\n";

// Move public_html contents to current directory
if (is_dir('public_html')) {
    $files = glob('public_html/*');
    foreach ($files as $file) {
        $basename = basename($file);
        if (is_dir($file)) {
            rename($file, $basename);
        } else {
            copy($file, $basename);
        }
        echo "✅ Moved: $basename\n";
    }
    
    // Remove empty public_html directory
    rmdir('public_html');
    echo "✅ Cleaned up public_html directory\n";
}

// Step 3: Set proper permissions
echo "\n🔐 Setting file permissions...\n";
if (is_dir('private')) {
    chmod('private', 0755);
    chmod('private/config', 0755);
    chmod('private/config/database.php', 0644);
    echo "✅ Set permissions for private directory\n";
}

chmod('setup.php', 0644);
chmod('test_system.php', 0644);
echo "✅ Set permissions for setup files\n";

// Step 4: Create .htaccess for security
echo "\n🛡️ Creating security configurations...\n";
$htaccess_content = '
# Security configurations for Product Discount Scraper
RewriteEngine On

# Protect private directory
<Files "private/*">
    Order Deny,Allow
    Deny from All
</Files>

# Protect configuration files
<Files "*.sql">
    Order Deny,Allow
    Deny from All
</Files>

<Files "database_setup.sql">
    Order Deny,Allow
    Deny from All
</Files>

# Enable error reporting for debugging (remove in production)
php_flag display_errors Off
php_flag log_errors On

# Set timezone
php_value date.timezone "America/New_York"

# Increase limits for scraping
php_value max_execution_time 300
php_value memory_limit 256M
';

file_put_contents('.htaccess', $htaccess_content);
echo "✅ Created .htaccess security file\n";

// Step 5: Create database configuration template
echo "\n🗄️ Creating database configuration template...\n";
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
}
?>';
    
    if (!is_dir('private/config')) {
        mkdir('private/config', 0755, true);
    }
    
    file_put_contents('private/config/database.php', $db_template);
    echo "✅ Created database configuration template\n";
}

// Step 6: Create quick setup instructions
echo "\n📋 Creating setup instructions...\n";
$instructions = '
# 🎯 CPANEL SETUP INSTRUCTIONS

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
';

file_put_contents('CPANEL_SETUP_INSTRUCTIONS.txt', $instructions);
echo "✅ Created setup instructions file\n";

echo "\n🎉 CPANEL AUTO-DEPLOYMENT COMPLETED!\n";
echo "=====================================\n";
echo "✅ All files moved to correct locations\n";
echo "✅ Permissions set properly\n";
echo "✅ Security configurations created\n";
echo "✅ Database template configured\n";
echo "✅ Setup instructions created\n\n";

echo "🚀 NEXT STEPS:\n";
echo "1. Create database in cPanel MySQL Databases\n";
echo "2. Import database_setup.sql via phpMyAdmin\n";
echo "3. Edit private/config/database.php with your credentials\n";
echo "4. Visit setup.php to complete installation\n";
echo "5. Start monitoring products and saving money!\n\n";

echo "📄 Read CPANEL_SETUP_INSTRUCTIONS.txt for detailed steps\n";
?>
';

$deploy_script = '<?php' . substr($deploy_script_content, 5);
file_put_contents('deploy_to_cpanel.php', $deploy_script);
echo "✅ Created auto-deployment script\n";

echo "\n🎉 AUTO-DEPLOYMENT COMPLETED SUCCESSFULLY!\n";
echo "==========================================\n";
echo "\n📋 What was created:\n";
echo "✅ deploy_to_cpanel.php - Auto-deployment script\n";
echo "✅ CPANEL_DEPLOYMENT_GUIDE.md - This comprehensive guide\n";
echo "\n🚀 Ready for cPanel deployment!\n";
?>
