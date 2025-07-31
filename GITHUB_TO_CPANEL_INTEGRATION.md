# 🔄 Complete GitHub to cPanel Integration Guide

## 🎯 **Overview**
Deploy your Product Discount Scraper directly from GitHub repository to cPanel hosting with automated setup.

## 🚀 **Method 1: One-Click GitHub to cPanel Deployment**

### **Step 1: Download from GitHub**
1. Go to: **https://github.com/brandotsdigital/csearch**
2. Click **Code** → **Download ZIP**
3. Extract the ZIP file to get all 48 project files

### **Step 2: Upload to cPanel**
1. Log into your **cPanel hosting account**
2. Open **File Manager**
3. Navigate to your domain's **public_html** folder
4. **Upload** the extracted files
5. **Extract** the archive directly in cPanel

### **Step 3: Run Auto-Deployment**
1. Visit: `http://yourdomain.com/deploy_to_cpanel.php`
2. The script will automatically:
   - ✅ Move files to correct locations
   - ✅ Set proper permissions
   - ✅ Create security configurations
   - ✅ Set up database template
   - ✅ Generate setup instructions

## 🗄️ **Method 2: Direct Database Integration**

### **Database Setup in cPanel:**

```sql
-- Step 1: Create database in cPanel MySQL Databases
-- Database name: yourusername_discount_scraper
-- User: yourusername_dbuser  
-- Password: your_secure_password

-- Step 2: Import schema via phpMyAdmin
-- Upload and import: database_setup.sql
-- This creates all 6 tables with sample data

-- Step 3: Verify import
SELECT COUNT(*) FROM products;    -- Should show sample products
SELECT COUNT(*) FROM categories;  -- Should show sample categories
```

### **Configure Database Connection:**
Edit `private/config/database.php`:

```php
private $host = 'localhost';
private $username = 'yourusername_dbuser';           // Your cPanel DB user
private $password = 'your_secure_password';          // Your DB password  
private $database = 'yourusername_discount_scraper'; // Your DB name
```

## ⚙️ **Method 3: Advanced SSH/Git Integration**

### **For Hosting with SSH Access:**

```bash
# Connect to your hosting via SSH
ssh yourusername@yourserver.com

# Navigate to web directory
cd public_html

# Clone repository directly
git clone https://github.com/brandotsdigital/csearch.git temp-repo

# Move files to proper locations
mv temp-repo/public_html/* .
mv temp-repo/private ../private
cp temp-repo/*.php .
cp temp-repo/*.sql .
cp temp-repo/*.md .

# Clean up
rm -rf temp-repo

# Set permissions
chmod -R 755 assets/
chmod -R 755 ../private/
chmod 644 *.php
chmod 644 *.sql

# Run auto-setup
php deploy_to_cpanel.php
```

## 🔧 **Method 4: Complete Automation Script**

Create this script for full automation:

```bash
#!/bin/bash
# complete_cpanel_deploy.sh - Full automation script

echo "🚀 Starting GitHub to cPanel deployment..."

# Step 1: Download from GitHub
wget https://github.com/brandotsdigital/csearch/archive/main.zip
unzip main.zip
cd csearch-main

# Step 2: Move files
cp -r public_html/* ../
cp -r private ../../private
cp *.php ../
cp *.sql ../
cp *.md ../

# Step 3: Set permissions
chmod -R 755 ../assets/
chmod -R 755 ../../private/
chmod 644 ../*.php

# Step 4: Create database
mysql -u root -p -e "CREATE DATABASE discount_scraper;"
mysql -u root -p discount_scraper < database_setup.sql

# Step 5: Run setup
cd ..
php setup.php

echo "✅ Deployment completed!"
```

## 📋 **Step-by-Step Integration Process**

### **Phase 1: Repository Extraction**
1. **Download**: Get ZIP from GitHub repository
2. **Extract**: Unzip to reveal 48 project files
3. **Upload**: Transfer all files to cPanel File Manager
4. **Organize**: Run `deploy_to_cpanel.php` for automatic organization

### **Phase 2: Database Setup**
1. **Create Database**: Use cPanel MySQL Databases
2. **Import Schema**: Upload `database_setup.sql` via phpMyAdmin
3. **Configure Connection**: Update database credentials
4. **Test Connection**: Verify database connectivity

### **Phase 3: System Initialization**
1. **Run Setup**: Visit `setup.php` for system initialization
2. **Test System**: Use `test_system.php` for validation
3. **Configure Cron**: Set up automated price monitoring
4. **Test Dashboard**: Verify main interface works

### **Phase 4: Production Configuration**
1. **Email Setup**: Configure SMTP settings
2. **Security**: Remove deployment scripts
3. **Monitoring**: Set up cron jobs for automation
4. **Testing**: Add sample products and test scraping

## 🎯 **Integration Benefits**

### **🔄 Automated Process:**
- ✅ **One-click deployment** from GitHub
- ✅ **Automatic file organization** and permissions
- ✅ **Security configuration** included
- ✅ **Database setup** with sample data
- ✅ **Testing tools** for validation

### **🚀 Production Ready:**
- ✅ **Optimized for cPanel** hosting environments
- ✅ **Security hardened** with .htaccess protection
- ✅ **Mobile responsive** interface
- ✅ **Email notifications** with HTML templates
- ✅ **Automated monitoring** with cron jobs

## 🔒 **Security Integration**

### **Automatic Security Features:**
- **File Protection**: Sensitive files protected via .htaccess
- **Directory Security**: Private folders blocked from web access
- **Input Validation**: All user inputs sanitized
- **SQL Protection**: Prepared statements prevent injection
- **Error Handling**: Secure error logging without data exposure

## 📊 **Post-Deployment Verification**

### **System Health Check:**
1. **File Structure**: Verify all 48 files deployed correctly
2. **Database**: Confirm 6 tables created with sample data
3. **Permissions**: Check file and directory permissions
4. **Connectivity**: Test database and external API connections
5. **Interface**: Verify dashboard and admin panel load properly

### **Testing Checklist:**
- [ ] Dashboard loads without errors
- [ ] Admin panel accessible  
- [ ] Database connection successful
- [ ] Sample products display correctly
- [ ] Email configuration working
- [ ] Cron jobs set up properly
- [ ] Mobile interface responsive
- [ ] Security features active

## 🎉 **Success Indicators**

### **✅ Deployment Successful When:**
- Dashboard shows product grid with sample data
- Admin panel loads and shows system statistics
- Database contains 6 tables with sample products
- Email test sends successfully
- System test passes all validations
- Cron jobs are configured and running
- Mobile interface works on all devices

## 🆘 **Troubleshooting Integration**

### **Common Issues & Solutions:**

**🔧 Database Connection Failed:**
- Verify database credentials in `private/config/database.php`
- Check database exists in cPanel MySQL Databases
- Ensure database user has all privileges

**📁 File Permission Errors:**
- Run `chmod -R 755 assets/` for asset folders
- Set `chmod 644` for PHP files
- Ensure private directory is outside web root

**🌐 Interface Not Loading:**
- Check `.htaccess` file for errors
- Verify PHP version compatibility (7.4+)
- Enable error reporting temporarily for debugging

**📧 Email Notifications Not Working:**
- Update SMTP settings in database
- Test mail function in cPanel
- Check email authentication credentials

## 🎯 **Complete Integration Summary**

Your GitHub to cPanel integration provides:

1. **📥 Simple Download**: One-click download from GitHub
2. **🚀 Auto-Deploy**: Automated file organization and setup
3. **🗄️ Database Integration**: Complete schema with sample data
4. **🔧 System Setup**: One-click initialization and testing
5. **⚙️ Production Ready**: Cron jobs, email, and monitoring
6. **🔒 Security**: Built-in protection and best practices
7. **📱 Mobile Ready**: Responsive design for all devices
8. **💰 Money Saving**: Start monitoring discounts immediately!

**Result**: Complete Product Discount Scraper deployment in under 30 minutes! 🎉
