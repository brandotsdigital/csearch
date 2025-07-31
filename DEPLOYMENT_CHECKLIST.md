# 📋 Deployment Checklist

## Pre-Deployment ✅

- [ ] cPanel/WHM hosting account ready
- [ ] MySQL database access available
- [ ] PHP 7.4+ confirmed on hosting
- [ ] SSH/Terminal access (optional but recommended)
- [ ] Domain/subdomain configured

## File Upload ✅

- [ ] Upload `public_html/` contents to domain's public directory
- [ ] Upload `private/` folder OUTSIDE of public_html (security)
- [ ] Verify all files transferred correctly
- [ ] Check file permissions (755 for directories, 644 for files)

## Database Setup ✅

- [ ] Create MySQL database in cPanel
- [ ] Create database user with full privileges
- [ ] Note down: database name, username, password
- [ ] Import `database_setup.sql` via phpMyAdmin
- [ ] Verify all 6 tables created successfully

## Configuration ✅

- [ ] Edit `private/config/database.php`
- [ ] Update database credentials:
  - [ ] `$host` (usually 'localhost')
  - [ ] `$username` (your cPanel DB username)
  - [ ] `$password` (your DB password)
  - [ ] `$database` (your database name)

## System Initialization ✅

- [ ] Run: `php setup.php`
- [ ] Verify success messages
- [ ] Check sample data was inserted
- [ ] Run: `php test_system.php`
- [ ] Ensure all tests pass ✅

## Web Interface Test ✅

- [ ] Access: `http://yourdomain.com/`
- [ ] Verify dashboard loads correctly
- [ ] Access: `http://yourdomain.com/admin.php`
- [ ] Verify admin panel works
- [ ] Test responsive design on mobile

## Automation Setup ✅

Set up cron jobs in cPanel:

- [ ] **Main scraping** (every hour):
  ```
  0 * * * * php /home/username/private/cron/scrape.php
  ```

- [ ] **Notifications** (every 30 minutes):
  ```
  */30 * * * * php /home/username/private/cron/notify.php
  ```

- [ ] Verify cron jobs are active
- [ ] Check cron job logs for errors

## Email Configuration ✅

Update in database `settings` table:

- [ ] SMTP host configuration
- [ ] SMTP port (usually 587 or 465)
- [ ] SMTP username and password
- [ ] Admin email address for notifications
- [ ] Test email functionality

## Security Check ✅

- [ ] Verify `private/` folder is outside web root
- [ ] Check `.htaccess` files are in place
- [ ] Ensure database credentials are secure
- [ ] Verify error logging is enabled
- [ ] Check file permissions are correct

## Content Setup ✅

- [ ] Add product categories via admin panel
- [ ] Add first test products with URLs
- [ ] Configure discount thresholds
- [ ] Set up notification preferences
- [ ] Test scraping manually

## Testing & Validation ✅

- [ ] Add a test product with known price
- [ ] Wait for scraping cycle to complete
- [ ] Verify price data appears in dashboard
- [ ] Test discount detection logic
- [ ] Confirm email notifications work

## Performance Optimization ✅

- [ ] Enable caching if available
- [ ] Optimize database queries
- [ ] Set up error monitoring
- [ ] Configure backup strategy
- [ ] Monitor resource usage

## Go Live ✅

- [ ] Remove any test/debug data
- [ ] Set appropriate scraping intervals
- [ ] Configure real product URLs
- [ ] Set up monitoring alerts
- [ ] Document admin procedures

## Post-Deployment ✅

- [ ] Monitor system logs for 24-48 hours
- [ ] Verify automated scraping is working
- [ ] Check email notifications are sent
- [ ] Monitor hosting resource usage
- [ ] Set up regular maintenance schedule

---

## 🆘 Common Issues & Solutions

**Database Connection Failed**
- ✅ Check credentials in `database.php`
- ✅ Verify database exists and user has access
- ✅ Test connection with `test_system.php`

**Cron Jobs Not Running**
- ✅ Check cron job syntax
- ✅ Verify PHP path: `which php` or `whereis php`
- ✅ Use full paths to files
- ✅ Check cron job logs

**Email Not Sending**
- ✅ Configure SMTP settings in database
- ✅ Check hosting provider's email restrictions
- ✅ Verify firewall allows SMTP connections
- ✅ Test with simple PHP mail script

**Scraping Not Working**
- ✅ Check user-agent strings
- ✅ Verify target websites are accessible
- ✅ Review rate limiting settings
- ✅ Check scraping logs in admin panel

---

## 📞 Success Confirmation

When deployment is complete, you should have:

✅ **Working dashboard** at your domain
✅ **Functional admin panel** for management  
✅ **Automated price monitoring** via cron jobs
✅ **Email notifications** for discounts
✅ **Responsive design** working on all devices
✅ **Secure file structure** with protected backend
✅ **Error logging** and monitoring in place

**🎉 Congratulations! Your Product Discount Scraper is live!**
