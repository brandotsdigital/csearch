# 🧪 Local Testing Guide - Product Discount Scraper

## 🚀 **Quick Local Setup for Testing**

### **Option 1: XAMPP (Recommended for Windows)**

1. **Download XAMPP**: 
   - Go to https://www.apachefriends.org/
   - Download XAMPP for Windows
   - Install with Apache, MySQL, and PHP

2. **Setup Your Project**:
   ```bash
   # Copy your project to XAMPP directory
   Copy "Project Scrap" folder to: C:\xampp\htdocs\
   ```

3. **Start Services**:
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

4. **Database Setup**:
   - Open http://localhost/phpmyadmin
   - Create database: `product_scraper`
   - Import: `database_setup.sql`

5. **Configure Database**:
   - Edit: `private/config/database.php`
   - Update credentials:
     ```php
     private $host = 'localhost';
     private $username = 'root';
     private $password = '';  // Usually empty for XAMPP
     private $database = 'product_scraper';
     ```

6. **Test Your System**:
   - Dashboard: http://localhost/Project%20Scrap/public_html/
   - Admin Panel: http://localhost/Project%20Scrap/public_html/admin.php
   - Run Setup: http://localhost/Project%20Scrap/setup.php

### **Option 2: WAMP Server**

1. **Download WAMP**: https://www.wampserver.com/
2. **Follow similar setup** as XAMPP above

### **Option 3: Docker (Advanced)**

```dockerfile
# Quick Docker setup
FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html/
```

### **Option 4: Online Testing**

- **InfinityFree**: Free hosting for testing
- **000webhost**: Free PHP hosting
- **AwardSpace**: Free hosting with MySQL

## 🧪 **Testing Checklist**

### **1. Basic Functionality**
- [ ] Dashboard loads without errors
- [ ] Admin panel accessible
- [ ] Database connection works
- [ ] Sample data displays

### **2. Core Features**
- [ ] Add test product via admin
- [ ] View products on dashboard
- [ ] Filter and search work
- [ ] Responsive design on mobile

### **3. Advanced Features**
- [ ] Scraper classes load without errors
- [ ] Email configuration accessible
- [ ] Notification system setup
- [ ] Database queries optimized

## 🔧 **Local Testing Configuration**

### **Database Connection (Local)**
```php
// For XAMPP/WAMP local testing
private $host = 'localhost';
private $username = 'root';
private $password = '';  // Usually empty for local
private $database = 'product_scraper';
```

### **URLs for Testing**
- **Main Dashboard**: http://localhost/Project%20Scrap/public_html/
- **Admin Panel**: http://localhost/Project%20Scrap/public_html/admin.php  
- **Setup Script**: http://localhost/Project%20Scrap/setup.php
- **Test Suite**: http://localhost/Project%20Scrap/test_system.php
- **phpMyAdmin**: http://localhost/phpmyadmin

## 🎯 **Quick Test Commands**

### **Test Database Connection**
```php
// Add this to a test file
<?php
require_once 'private/config/database.php';
try {
    $db = new Database();
    echo "✅ Database connection successful!";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
```

### **Test Basic Functionality**
```php
// Test product display
<?php
require_once 'private/config/database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$result = $stmt->fetch();
echo "Products in database: " . $result['total'];
?>
```

## 🚨 **Common Local Testing Issues**

### **1. Permission Errors**
- Ensure XAMPP has write permissions
- Check file ownership in htdocs folder

### **2. Database Connection Failed**
- Verify MySQL is running in XAMPP
- Check database credentials
- Ensure database exists

### **3. PHP Errors**
- Check PHP error logs in XAMPP
- Verify PHP extensions (PDO, MySQL)
- Enable error reporting for debugging

### **4. Path Issues**
- Use relative paths in includes
- Check file path separators (Windows vs Linux)

## 📱 **Mobile Testing**

Test responsive design:
- **Chrome DevTools**: F12 → Device toolbar
- **Firefox DevTools**: F12 → Responsive design mode
- **Real devices**: Access via local IP

## 🎉 **Success Indicators**

Your local test is successful when:
- ✅ Dashboard loads with Bootstrap styling
- ✅ Admin panel shows management interface
- ✅ Database connection established
- ✅ No PHP errors in logs
- ✅ Sample data displays correctly
- ✅ Responsive design works on mobile
- ✅ Forms and filters function properly

## 🚀 **Next Steps After Local Testing**

1. **Fix any local issues**
2. **Test core functionality**
3. **Verify responsive design**
4. **Deploy to production hosting**
5. **Configure real scrapers**
6. **Set up email notifications**
7. **Add cron jobs for automation**

## 💡 **Pro Tips**

- **Use browser dev tools** for debugging
- **Check PHP error logs** for issues
- **Test with sample data** before real scraping
- **Verify mobile responsiveness**
- **Test email functionality** locally
- **Use version control** (Git) for changes

Your 48-file system is ready for local testing! 🎯
