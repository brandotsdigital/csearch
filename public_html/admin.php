<?php
/**
 * Admin Panel - Product Discount Monitor
 * Manage products, view statistics, and configure settings
 */

// Include database configuration
require_once '../private/config/database.php';

// Simple authentication (you should implement proper authentication)
session_start();
$isAuthenticated = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle login
if (isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    // Change this password to something secure
    if ($password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Invalid password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Redirect to login if not authenticated
if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Product Discount Monitor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">
    </head>
    <body>
        <div class="container d-flex align-items-center justify-content-center min-vh-100">
            <div class="card" style="width: 100%; max-width: 400px;">
                <div class="card-header text-center">
                    <h4>Admin Login</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($loginError)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Default password: admin123</div>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle actions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_product'])) {
            // Add new product
            $name = trim($_POST['name']);
            $url = trim($_POST['url']);
            $platform = trim($_POST['platform']);
            $category = trim($_POST['category']);
            $brand = trim($_POST['brand']);
            
            if (empty($name) || empty($url) || empty($platform)) {
                throw new Exception('Name, URL, and Platform are required');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO products (name, url, platform, category, brand, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $url, $platform, $category, $brand]);
            
            $message = 'Product added successfully!';
            $messageType = 'success';
            
        } elseif (isset($_POST['update_settings'])) {
            // Update settings
            $settings = [
                'min_discount_threshold' => $_POST['min_discount_threshold'],
                'scraping_interval' => $_POST['scraping_interval'],
                'email_notifications' => $_POST['email_notifications'],
                'notification_email' => $_POST['notification_email'],
                'max_products_per_run' => $_POST['max_products_per_run']
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    UPDATE settings SET setting_value = ? WHERE setting_key = ?
                ");
                $stmt->execute([$value, $key]);
            }
            
            $message = 'Settings updated successfully!';
            $messageType = 'success';
            
        } elseif (isset($_POST['delete_product'])) {
            // Delete product
            $productId = (int)$_POST['product_id'];
            $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$productId]);
            
            $message = 'Product deleted successfully!';
            $messageType = 'success';
            
        } elseif (isset($_POST['test_scraper'])) {
            // Test scraper
            $url = trim($_POST['test_url']);
            $platform = trim($_POST['test_platform']);
            
            require_once '../private/scrapers/' . $platform . '_scraper.php';
            $scraperClass = ucfirst($platform) . 'Scraper';
            $scraper = new $scraperClass();
            
            $result = $scraper->scrapeProduct($url);
            
            if ($result) {
                $message = 'Test successful! Found: ' . $result['name'] . ' - $' . $result['price'];
                $messageType = 'success';
            } else {
                $message = 'Test failed - could not extract product data';
                $messageType = 'danger';
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get statistics
$stats = $db->getStats();

// Get recent products
$recentProductsStmt = $conn->prepare("
    SELECT p.*, ph.price, ph.discount_percentage, ph.scraped_at
    FROM products p
    LEFT JOIN (
        SELECT product_id, price, discount_percentage, scraped_at,
               ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY scraped_at DESC) as rn
        FROM price_history
    ) ph ON p.id = ph.product_id AND ph.rn = 1
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recentProductsStmt->execute();
$recentProducts = $recentProductsStmt->fetchAll();

// Get current settings
$settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
$settingsStmt->execute();
$currentSettings = [];
while ($row = $settingsStmt->fetch()) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Get recent logs
$logsStmt = $conn->prepare("
    SELECT * FROM scraping_logs 
    ORDER BY scraped_at DESC 
    LIMIT 20
");
$logsStmt->execute();
$recentLogs = $logsStmt->fetchAll();

// Get pending notifications
$notificationsStmt = $conn->prepare("
    SELECT n.*, p.name as product_name
    FROM notifications n
    JOIN products p ON n.product_id = p.id
    WHERE n.is_sent = 0
    ORDER BY n.created_at DESC
    LIMIT 10
");
$notificationsStmt->execute();
$pendingNotifications = $notificationsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Product Discount Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-search-dollar me-2"></i>Deal Hunter Admin
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Public Site</a>
                <a class="nav-link" href="?logout=1"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-cogs me-2"></i>Admin Dashboard</h1>
            <p>Manage your product monitoring system</p>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="admin-stats">
            <h3><i class="fas fa-chart-bar me-2"></i>System Statistics</h3>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                        <div class="stat-label"><i class="fas fa-box me-1"></i>Total Products</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['active_deals']) ?></div>
                        <div class="stat-label"><i class="fas fa-tags me-1"></i>Active Deals</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['total_price_records']) ?></div>
                        <div class="stat-label"><i class="fas fa-history me-1"></i>Price Records</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['pending_notifications']) ?></div>
                        <div class="stat-label"><i class="fas fa-bell me-1"></i>Pending Alerts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button">
                    <i class="fas fa-box me-1"></i>Products
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button">
                    <i class="fas fa-cog me-1"></i>Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button">
                    <i class="fas fa-file-alt me-1"></i>Logs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button">
                    <i class="fas fa-bell me-1"></i>Notifications
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button">
                    <i class="fas fa-tools me-1"></i>Tools
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- Products Tab -->
            <div class="tab-pane fade show active" id="products" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-plus me-2"></i>Add New Product</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="url" class="form-label">Product URL *</label>
                                        <input type="url" class="form-control" id="url" name="url" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="platform" class="form-label">Platform *</label>
                                        <select class="form-select" id="platform" name="platform" required>
                                            <option value="">Select Platform</option>
                                            <option value="amazon">Amazon</option>
                                            <option value="ebay">eBay</option>
                                            <option value="walmart">Walmart</option>
                                            <option value="target">Target</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <input type="text" class="form-control" id="category" name="category" 
                                               placeholder="e.g., Electronics">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="brand" class="form-label">Brand</label>
                                        <input type="text" class="form-control" id="brand" name="brand" 
                                               placeholder="e.g., Apple">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_product" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Product
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Products -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Recent Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Platform</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Discount</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentProducts as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($product['brand']) ?></small>
                                            </td>
                                            <td><?= ucfirst(htmlspecialchars($product['platform'])) ?></td>
                                            <td><?= htmlspecialchars($product['category']) ?></td>
                                            <td>
                                                <?php if ($product['price']): ?>
                                                    $<?= number_format($product['price'], 2) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No data</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($product['discount_percentage'] > 0): ?>
                                                    <span class="badge bg-danger"><?= $product['discount_percentage'] ?>% OFF</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($product['scraped_at']): ?>
                                                    <?= date('M j, g:i A', strtotime($product['scraped_at'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= htmlspecialchars($product['url']) ?>" target="_blank" 
                                                       class="btn btn-outline-primary" title="View Product">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        <button type="submit" name="delete_product" 
                                                                class="btn btn-outline-danger" 
                                                                onclick="return confirm('Are you sure?')" 
                                                                title="Delete Product">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-cog me-2"></i>System Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="min_discount_threshold" class="form-label">Minimum Discount Threshold (%)</label>
                                        <input type="number" class="form-control" id="min_discount_threshold" 
                                               name="min_discount_threshold" 
                                               value="<?= htmlspecialchars($currentSettings['min_discount_threshold'] ?? '20') ?>"
                                               min="1" max="99">
                                        <div class="form-text">Minimum discount percentage to trigger notifications</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="scraping_interval" class="form-label">Scraping Interval (minutes)</label>
                                        <input type="number" class="form-control" id="scraping_interval" 
                                               name="scraping_interval" 
                                               value="<?= htmlspecialchars($currentSettings['scraping_interval'] ?? '60') ?>"
                                               min="30" max="1440">
                                        <div class="form-text">How often to check for price updates</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="notification_email" class="form-label">Notification Email</label>
                                        <input type="email" class="form-control" id="notification_email" 
                                               name="notification_email" 
                                               value="<?= htmlspecialchars($currentSettings['notification_email'] ?? '') ?>">
                                        <div class="form-text">Email address to receive deal notifications</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="max_products_per_run" class="form-label">Max Products Per Run</label>
                                        <input type="number" class="form-control" id="max_products_per_run" 
                                               name="max_products_per_run" 
                                               value="<?= htmlspecialchars($currentSettings['max_products_per_run'] ?? '50') ?>"
                                               min="10" max="200">
                                        <div class="form-text">Maximum products to scrape in each cron run</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" 
                                           name="email_notifications" value="1"
                                           <?= ($currentSettings['email_notifications'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Enable Email Notifications
                                    </label>
                                </div>
                            </div>
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div class="tab-pane fade" id="logs" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-file-alt me-2"></i>Recent Activity Logs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Platform</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Response Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td><?= date('M j, g:i A', strtotime($log['scraped_at'])) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($log['platform'])) ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'success' => 'success',
                                                    'error' => 'danger',
                                                    'blocked' => 'warning',
                                                    'not_found' => 'secondary'
                                                ];
                                                $color = $statusColors[$log['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>"><?= ucfirst($log['status']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($log['error_message']): ?>
                                                    <small><?= htmlspecialchars($log['error_message']) ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['response_time']): ?>
                                                    <?= $log['response_time'] ?>ms
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-bell me-2"></i>Pending Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Message</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingNotifications as $notification): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($notification['product_name']) ?></td>
                                            <td>
                                                <?php
                                                $typeColors = [
                                                    'price_drop' => 'danger',
                                                    'back_in_stock' => 'success',
                                                    'new_product' => 'info'
                                                ];
                                                $color = $typeColors[$notification['notification_type']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $notification['notification_type'])) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($notification['message']) ?></td>
                                            <td><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($pendingNotifications)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No pending notifications</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tools Tab -->
            <div class="tab-pane fade" id="tools" role="tabpanel">
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>Test Scraper</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="test_url" class="form-label">Product URL</label>
                                        <input type="url" class="form-control" id="test_url" name="test_url" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="test_platform" class="form-label">Platform</label>
                                        <select class="form-select" id="test_platform" name="test_platform" required>
                                            <option value="">Select Platform</option>
                                            <option value="amazon">Amazon</option>
                                            <option value="ebay">eBay</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="test_scraper" class="btn btn-success d-block">
                                            <i class="fas fa-play me-1"></i>Test
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-sync me-2"></i>Manual Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Database Connection</h6>
                                <p>
                                    Status: 
                                    <?php if ($db->testConnection()): ?>
                                        <span class="badge bg-success">Connected</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Disconnected</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Cron Job Status</h6>
                                <p>
                                    <span class="text-muted">Check your cPanel for cron job configuration</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
