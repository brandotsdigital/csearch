# 🎯 YOUR PERSONALIZED SETUP GUIDE

## ✅ **Database Configuration Complete!**

Your database credentials have been configured:
- **Database**: `pyramid_new`
- **Username**: `pyramad_new` 
- **Password**: `omnamo@@333`
- **Host**: `localhost`

---

## 🚀 **Next Steps for Your Live Setup:**

### **STEP 1: Import Database Schema** ⏱️ 2 minutes
1. **Access phpMyAdmin** in your cPanel
2. **Select database**: `pyramid_new`
3. **Click Import tab**
4. **Upload file**: `database_setup.sql`
5. **Click Go** to import

✅ **Expected Result**: 6 tables created (products, price_history, categories, notifications, settings, scraping_logs)

---

### **STEP 2: Run System Setup** ⏱️ 1 minute
1. **Visit**: `http://yourdomain.com/setup.php`
2. **Complete setup wizard** (will use your database automatically)
3. **Create admin account** when prompted

---

### **STEP 3: Test Your Installation** ⏱️ 1 minute
1. **Visit**: `http://yourdomain.com/test_system.php`
2. **Verify all tests pass**:
   - ✅ Database connection to `pyramid_new`
   - ✅ File structure valid
   - ✅ PHP environment ready

---

### **STEP 4: Access Your Dashboard** ⏱️ 30 seconds
1. **Main Dashboard**: `http://yourdomain.com/`
2. **Admin Panel**: `http://yourdomain.com/admin.php`

---

### **STEP 5: Set Up Automation** ⏱️ 3 minutes

**Add these Cron Jobs in cPanel:**

1. **Hourly Scraping:**
   ```bash
   0 * * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/scrape.php
   ```

2. **30-Minute Notifications:**
   ```bash
   */30 * * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/notify.php
   ```

3. **Daily Cleanup (2 AM):**
   ```bash
   0 2 * * * /usr/local/bin/php /home/yourusername/public_html/private/cron/cleanup.php
   ```

---

### **STEP 6: Configure Email Notifications** ⏱️ 2 minutes

**In Admin Panel → Settings, update:**
- **Admin Email**: your-email@domain.com
- **SMTP Host**: (your hosting SMTP server)
- **SMTP Port**: 587 or 465
- **SMTP Username**: your-email@domain.com
- **SMTP Password**: your-email-password

---

### **STEP 7: Add Your First Products** ⏱️ 2 minutes

**In Admin Panel → Add Product:**

1. **Amazon Product Example:**
   - Name: "Amazon Echo Dot"
   - URL: [paste Amazon product URL]
   - Category: Electronics
   - Platform: Amazon
   - Discount Threshold: 20%

2. **eBay Product Example:**
   - Name: "iPhone Case"
   - URL: [paste eBay item URL]
   - Category: Electronics
   - Platform: eBay
   - Discount Threshold: 15%

---

## 🎉 **You're All Set!**

### **Your System Will Now:**
- ✅ **Monitor prices** every hour automatically
- ✅ **Send email alerts** for discounts ≥ threshold
- ✅ **Track price history** for trend analysis
- ✅ **Display deals** on your beautiful dashboard
- ✅ **Work on mobile** devices perfectly

### **Bookmark These URLs:**
- 🏠 **Dashboard**: `http://yourdomain.com/`
- ⚙️ **Admin**: `http://yourdomain.com/admin.php`
- 🗄️ **Database**: cPanel → phpMyAdmin → `pyramid_new`

---

## 🆘 **Quick Troubleshooting**

**If you see "Database connection failed":**
1. Verify database `pyramid_new` exists in cPanel
2. Confirm user `pyramad_new` has ALL PRIVILEGES
3. Check password `omnamo@@333` is correct

**If dashboard shows no products:**
1. Import `database_setup.sql` via phpMyAdmin
2. Run `http://yourdomain.com/setup.php`
3. Add products via admin panel

**If emails don't send:**
1. Configure SMTP settings in admin panel
2. Test email function in cPanel
3. Check hosting provider email limits

---

## 💰 **Start Saving Money!**

Your automated discount monitoring system is now ready to help you:
- **Never miss deals** on products you want
- **Track price drops** across Amazon & eBay
- **Get instant notifications** when prices drop
- **Monitor multiple products** simultaneously
- **Save hundreds of dollars** on purchases!

**Total setup time: ~10 minutes** ⏱️

**Happy deal hunting!** 🛍️💰
