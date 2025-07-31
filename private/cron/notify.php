<?php
/**
 * Notification Cron Job
 * Sends email notifications for pending alerts
 * 
 * Usage: php notify.php
 * Cron: */30 * * * * /usr/bin/php /path/to/private/cron/notify.php
 */

require_once '../config/database.php';

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting notification processing...\n";
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get notification settings
    $settingsStmt = $conn->prepare("
        SELECT setting_key, setting_value 
        FROM settings 
        WHERE setting_key IN ('notification_email', 'email_notifications', 'site_title')
    ");
    $settingsStmt->execute();
    $settings = [];
    while ($row = $settingsStmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Check if email notifications are enabled
    if (!isset($settings['email_notifications']) || $settings['email_notifications'] != '1') {
        echo "[" . date('Y-m-d H:i:s') . "] Email notifications are disabled\n";
        exit(0);
    }
    
    $notificationEmail = $settings['notification_email'] ?? '';
    $siteTitle = $settings['site_title'] ?? 'Product Discount Monitor';
    
    if (empty($notificationEmail)) {
        echo "[" . date('Y-m-d H:i:s') . "] No notification email configured\n";
        exit(0);
    }
    
    // Get pending notifications
    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.notification_type,
            n.message,
            n.created_at,
            p.name as product_name,
            p.url as product_url,
            p.image_url,
            p.platform,
            p.brand,
            p.category,
            ph.price,
            ph.original_price,
            ph.discount_percentage
        FROM notifications n
        JOIN products p ON n.product_id = p.id
        LEFT JOIN (
            SELECT 
                product_id, 
                price, 
                original_price, 
                discount_percentage,
                ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY scraped_at DESC) as rn
            FROM price_history
        ) ph ON p.id = ph.product_id AND ph.rn = 1
        WHERE n.is_sent = 0
        ORDER BY n.created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    if (empty($notifications)) {
        echo "[" . date('Y-m-d H:i:s') . "] No pending notifications\n";
        exit(0);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($notifications) . " pending notifications\n";
    
    foreach ($notifications as $notification) {
        try {
            $subject = '';
            $emailBody = '';
            
            // Create subject based on notification type
            switch ($notification['notification_type']) {
                case 'price_drop':
                    $subject = "🔥 Great Deal Alert: {$notification['discount_percentage']}% off {$notification['product_name']}";
                    break;
                case 'back_in_stock':
                    $subject = "📦 Back in Stock: {$notification['product_name']}";
                    break;
                case 'new_product':
                    $subject = "🆕 New Product Added: {$notification['product_name']}";
                    break;
                default:
                    $subject = "🔔 Deal Alert: {$notification['product_name']}";
            }
            
            // Create HTML email body
            $emailBody = createEmailTemplate($notification, $siteTitle);
            
            // Send email
            if (sendEmail($notificationEmail, $subject, $emailBody)) {
                // Mark as sent
                $updateStmt = $conn->prepare("
                    UPDATE notifications 
                    SET is_sent = 1, sent_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$notification['id']]);
                
                echo "[" . date('Y-m-d H:i:s') . "] ✅ Sent: {$notification['product_name']}\n";
            } else {
                echo "[" . date('Y-m-d H:i:s') . "] ❌ Failed to send: {$notification['product_name']}\n";
            }
            
            // Small delay between emails
            sleep(1);
            
        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ❌ Error sending notification {$notification['id']}: " . $e->getMessage() . "\n";
            error_log("Notification error: " . $e->getMessage());
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Notification processing completed\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    error_log("Fatal notification error: " . $e->getMessage());
}

/**
 * Create HTML email template
 */
function createEmailTemplate($notification, $siteTitle) {
    $productName = htmlspecialchars($notification['product_name']);
    $productUrl = htmlspecialchars($notification['product_url']);
    $platform = ucfirst($notification['platform']);
    $brand = $notification['brand'] ? htmlspecialchars($notification['brand']) : '';
    $category = $notification['category'] ? htmlspecialchars($notification['category']) : '';
    $message = htmlspecialchars($notification['message']);
    $imageUrl = $notification['image_url'] ?: 'https://via.placeholder.com/200x200?text=No+Image';
    
    $currentPrice = $notification['price'] ? '$' . number_format($notification['price'], 2) : 'N/A';
    $originalPrice = $notification['original_price'] ? '$' . number_format($notification['original_price'], 2) : '';
    $discount = $notification['discount_percentage'] ?: 0;
    $savings = '';
    
    if ($notification['original_price'] && $notification['price']) {
        $savingsAmount = $notification['original_price'] - $notification['price'];
        $savings = '$' . number_format($savingsAmount, 2);
    }
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Deal Alert</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .product-card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .product-image { width: 150px; height: 150px; object-fit: cover; border-radius: 8px; margin-right: 20px; float: left; }
            .product-info { overflow: hidden; }
            .price-section { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .current-price { font-size: 24px; font-weight: bold; color: #28a745; }
            .original-price { text-decoration: line-through; color: #6c757d; margin-left: 10px; }
            .discount-badge { background: #dc3545; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold; display: inline-block; margin: 10px 0; }
            .cta-button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            .clearfix::after { content: ''; display: table; clear: both; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔥 {$siteTitle}</h1>
                <p>Great Deal Alert!</p>
            </div>
            
            <div class='content'>
                <div class='product-card clearfix'>
                    <img src='{$imageUrl}' alt='{$productName}' class='product-image' />
                    <div class='product-info'>
                        <h2>{$productName}</h2>
                        " . ($brand ? "<p><strong>Brand:</strong> {$brand}</p>" : "") . "
                        " . ($category ? "<p><strong>Category:</strong> {$category}</p>" : "") . "
                        <p><strong>Platform:</strong> {$platform}</p>
                        
                        <div class='price-section'>
                            <div class='current-price'>{$currentPrice}</div>
                            " . ($originalPrice ? "<span class='original-price'>{$originalPrice}</span>" : "") . "
                            " . ($discount > 0 ? "<div class='discount-badge'>{$discount}% OFF</div>" : "") . "
                            " . ($savings ? "<p><strong>You save:</strong> {$savings}</p>" : "") . "
                        </div>
                        
                        <p><strong>Alert:</strong> {$message}</p>
                        
                        <a href='{$productUrl}' class='cta-button' target='_blank'>View Deal →</a>
                    </div>
                </div>
                
                <p><small>This deal was found on " . date('F j, Y \a\t g:i A', strtotime($notification['created_at'])) . ". Prices may have changed since then.</small></p>
            </div>
            
            <div class='footer'>
                <p>You're receiving this because you're subscribed to deal alerts from {$siteTitle}.</p>
                <p>Happy shopping! 🛍️</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $html;
}

/**
 * Send email using PHP mail function
 */
function sendEmail($to, $subject, $htmlBody) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: deals@yoursite.com',
        'Reply-To: deals@yoursite.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}
?>
