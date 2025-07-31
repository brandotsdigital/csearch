# 🛍️ Product Discount Scraper & Monitor

A comprehensive web scraping system that monitors product prices across multiple e-commerce platforms, detects significant discounts, and provides automated notifications through a beautiful web dashboard.

## 🚀 Quick Start

### 1. **Upload to Your Hosting**
- Upload `public_html/` contents to your domain's public directory
- Upload `private/` folder outside of public_html for security
- Upload database schema and setup files

### 2. **Database Setup**
- Create MySQL database in cPanel
- Import `database_setup.sql` via phpMyAdmin
- Update database credentials in `private/config/database.php`

### 3. **Initialize System**
```bash
php setup.php
```

### 4. **Test Installation**
```bash
php test_system.php
```

### 5. **Configure Automation**
Set up cron jobs in cPanel:
```bash
# Scrape products every hour
0 * * * * php /path/to/private/cron/scrape.php

# Send notifications every 30 minutes
*/30 * * * * php /path/to/private/cron/notify.php
```

## 📁 System Architecture

```
📦 Product Scraper System
├── 🌐 Web Dashboard (index.php)
├── ⚙️ Admin Panel (admin.php)  
├── 🕷️ Web Scrapers (Amazon, eBay)
├── 🔄 Automation (Cron jobs)
├── 📧 Notifications (Email alerts)
├── 🗄️ Database (MySQL)
└── 🎨 Responsive UI (Bootstrap 5)
```

## ✨ Features

- **🔍 Multi-Platform Scraping**: Amazon, eBay, and more
- **💰 Discount Detection**: Configurable price drop alerts
- **📱 Responsive Dashboard**: Beautiful mobile-friendly interface
- **📧 Email Notifications**: Instant discount alerts
- **⚙️ Admin Panel**: Complete product and system management
- **🔄 Automation**: Scheduled price monitoring
- **📊 Analytics**: Price history and trend tracking
- **🔒 Security**: SQL injection protection and secure file structure

## 🛠 Technology Stack

- **Backend**: PHP 7.4+ (cPanel/WHM optimized)
- **Database**: MySQL with optimized schema
- **Frontend**: Bootstrap 5 + Custom CSS/JS
- **Scraping**: cURL + DOMDocument parsing
- **Automation**: Cron jobs for scheduled tasks

## 📚 Documentation

- **[PROJECT_PLAN.md](PROJECT_PLAN.md)** - Complete project roadmap
- **[IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)** - Technical details
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Step-by-step setup
- **[QUICK_START.md](QUICK_START.md)** - Fast deployment guide

## 🎯 System Status

✅ **FULLY IMPLEMENTED** - Production Ready!

### Core Components:
- ✅ Database schema and configuration
- ✅ Web scraping engine (Amazon, eBay)
- ✅ Responsive web dashboard
- ✅ Admin management panel
- ✅ Automated cron job system
- ✅ Email notification system
- ✅ Setup and testing scripts

## 🚀 Getting Started

1. **Read**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for detailed setup
2. **Deploy**: Upload files to your cPanel hosting
3. **Configure**: Update database credentials
4. **Initialize**: Run `php setup.php`
5. **Test**: Run `php test_system.php`
6. **Enjoy**: Start monitoring product discounts!

## 🔧 Customization

The system is designed to be easily extensible:

- **Add new platforms**: Extend the `BaseScraper` class
- **Custom notifications**: Modify notification rules
- **UI customization**: Update CSS/JS in assets directory
- **New features**: Follow the modular architecture

## 🆘 Support

If you encounter any issues:

1. Check the **DEPLOYMENT_GUIDE.md** for troubleshooting
2. Run **test_system.php** to diagnose problems
3. Review error logs in your hosting control panel
4. Ensure all file permissions are correct

## 📜 License

This project is designed for educational and personal use. Please respect the terms of service of websites you scrape and implement appropriate rate limiting.

---

**Happy bargain hunting!** 🛍️💰

*Start saving money with automated discount monitoring today!*
