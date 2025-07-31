# Product Discount Scraper - Quick Start Guide

## 🚀 Quick Implementation Steps

### Step 1: Set Up Your Database
1. Access your cPanel and open phpMyAdmin
2. Create a new database called `product_scraper`
3. Import the `database_setup.sql` file to create all tables and initial data
4. Note down your database credentials

### Step 2: Upload Files to Your Server
Upload the following structure to your hosting:

```
your_domain.com/
├── public_html/
│   ├── index.php              # Main dashboard
│   ├── api.php                # API endpoints  
│   ├── admin.php              # Admin interface
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── private/                   # Above web root (secure)
│   ├── config/
│   │   └── database.php       # Database connection
│   ├── scrapers/
│   │   ├── base_scraper.php
│   │   ├── amazon_scraper.php
│   │   └── ebay_scraper.php
│   └── cron/
│       ├── scrape.php         # Main scraping script
│       └── notify.php         # Notification script
```

### Step 3: Configure Database Connection
Edit `private/config/database.php`:

```php
private $host = 'localhost';           // Usually localhost
private $username = 'your_db_user';    // Your database username
private $password = 'your_db_pass';    // Your database password  
private $database = 'product_scraper'; // Your database name
```

### Step 4: Set Up Cron Jobs
In cPanel, add these cron jobs:

```bash
# Scrape products every hour
0 * * * * /usr/bin/php /home/yourusername/private/cron/scrape.php

# Send notifications every 30 minutes  
*/30 * * * * /usr/bin/php /home/yourusername/private/cron/notify.php
```

### Step 5: Add Your First Products
Visit `your_domain.com/admin.php` and add some products to monitor:

**Amazon Examples:**
- Electronics: https://www.amazon.com/dp/B08N5WRWNW (Echo Dot)
- Books: https://www.amazon.com/dp/1234567890

**eBay Examples:**  
- Fashion: https://www.ebay.com/itm/123456789
- Electronics: https://www.ebay.com/itm/987654321

### Step 6: Test the System
1. Run the scraper manually: `/usr/bin/php /path/to/cron/scrape.php`
2. Check if data appears in your database
3. Visit your website to see the results

## 📧 Email Configuration

### Option 1: Use cPanel Mail (Recommended)
Update notification settings in your database:

```sql
UPDATE settings SET setting_value = 'your@email.com' 
WHERE setting_key = 'notification_email';
```

### Option 2: Use External SMTP (Gmail, etc.)
Modify the mail function in `notify.php` to use SMTP instead of PHP mail().

## 🎯 Platforms You Can Scrape

### E-commerce Sites
- **Amazon** ✅ (Implemented)
- **eBay** ✅ (Implemented) 
- **Walmart** (Easy to add)
- **Target** (Easy to add)
- **Best Buy** (Easy to add)
- **Newegg** (Easy to add)

### Deal Sites
- **Woot**
- **Slickdeals**
- **DealNews**
- **RetailMeNot**

### Fashion Sites
- **Zappos**
- **6pm**
- **Nordstrom Rack**
- **TJ Maxx**

## 🔧 Customization Options

### 1. Discount Thresholds
```sql
-- Set minimum discount percentage per category
UPDATE categories SET min_discount_threshold = 30 WHERE name = 'Fashion';
UPDATE categories SET min_discount_threshold = 15 WHERE name = 'Books';
```

### 2. Scraping Frequency
```sql
-- Change scraping interval (in minutes)
UPDATE settings SET setting_value = '30' WHERE setting_key = 'scraping_interval';
```

### 3. Add New Categories
```sql
INSERT INTO categories (name, keywords, min_discount_threshold) VALUES
('Baby & Kids', 'baby,kids,toys,stroller,crib', 25);
```

## 📊 Admin Dashboard Features

### Product Management
- Add/edit/delete products to monitor
- Bulk import from CSV
- Set custom discount thresholds per product

### Analytics
- Price trend charts
- Best deals summary  
- Platform performance comparison
- Scraping success rates

### Notifications
- Email alert settings
- Notification history
- Custom notification rules

## 🛠 Troubleshooting

### Common Issues

**1. Scraper Not Working**
- Check if cron jobs are running
- Verify file permissions (755 for directories, 644 for files)
- Check error logs in cPanel

**2. No Price Data**
- Website might have changed their HTML structure
- IP might be blocked (try using proxies)
- Check scraping logs for error messages

**3. Email Notifications Not Sending**
- Verify SMTP settings
- Check spam folder
- Ensure email address is correct in settings

### Debug Mode
Add this to your scraper files for debugging:

```php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log scraping attempts
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Scraping: $url\n", FILE_APPEND);
```

## 🔒 Security Best Practices

### 1. Protect Your Scripts
- Keep scraper files outside web root (`/private/` folder)
- Use strong database passwords
- Limit file permissions

### 2. Respectful Scraping
- Don't overwhelm target servers
- Use appropriate delays between requests
- Respect robots.txt files
- Rotate user agents

### 3. Error Handling
```php
try {
    $data = $scraper->scrapeProduct($url);
    // Process data
} catch (Exception $e) {
    error_log("Scraping failed: " . $e->getMessage());
    // Continue with next product
}
```

## 📈 Performance Optimization

### 1. Database Indexes
Already included in setup, but monitor query performance:

```sql
-- Check slow queries
SHOW PROCESSLIST;

-- Analyze table performance  
ANALYZE TABLE products, price_history;
```

### 2. Caching
Add simple caching to reduce database load:

```php
// Cache recent products for 5 minutes
$cacheFile = 'cache/recent_products.json';
if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 300) {
    $products = json_decode(file_get_contents($cacheFile), true);
} else {
    // Fetch from database
    // Save to cache
    file_put_contents($cacheFile, json_encode($products));
}
```

### 3. Image Optimization
Optimize product images for faster loading:

```php
// Resize and compress images
function optimizeImage($imageUrl, $maxWidth = 300) {
    // Download and resize image
    // Save locally for faster serving
}
```

## 🚀 Advanced Features to Add

### 1. Price Alerts
- Set custom price targets
- Alert when price drops below threshold
- Historical price charts

### 2. Social Features  
- Share good deals
- User ratings and reviews
- Deal voting system

### 3. Mobile App
- React Native or PWA
- Push notifications
- Barcode scanning

### 4. AI Integration
- Predict price drops
- Recommend products
- Sentiment analysis of reviews

## 💰 Monetization Ideas

### 1. Affiliate Links
- Amazon Associates
- eBay Partner Network
- Commission-based earnings

### 2. Premium Features
- Real-time alerts
- Advanced filtering
- API access

### 3. Sponsored Deals
- Featured product placements
- Brand partnerships
- Advertising revenue

## 📞 Support & Resources

### Documentation
- PHP Manual: https://php.net/manual/
- MySQL Reference: https://dev.mysql.com/doc/
- cPanel User Guide: Your hosting provider

### Community
- Stack Overflow for technical questions
- Reddit r/webdev and r/php communities
- GitHub for open source scraping tools

Remember: Always respect website terms of service and implement rate limiting to be a good citizen of the web! 🌐
