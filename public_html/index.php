<?php
/**
 * Main Dashboard - Product Discount Monitor
 * Displays latest deals and discounted products
 */

// Include database configuration
require_once '../private/config/database.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$category = $_GET['category'] ?? '';
$minDiscount = isset($_GET['min_discount']) ? (int)$_GET['min_discount'] : 20;
$platform = $_GET['platform'] ?? '';
$sortBy = $_GET['sort'] ?? 'discount_percentage';
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Build query conditions
$conditions = ["p.is_active = 1", "ph.discount_percentage >= :minDiscount"];
$params = [':minDiscount' => $minDiscount];

if ($category) {
    $conditions[] = "p.category = :category";
    $params[':category'] = $category;
}

if ($platform) {
    $conditions[] = "p.platform = :platform";
    $params[':platform'] = $platform;
}

if ($maxPrice > 0) {
    $conditions[] = "ph.price <= :maxPrice";
    $params[':maxPrice'] = $maxPrice;
}

// Valid sort options to prevent SQL injection
$validSorts = ['discount_percentage', 'price', 'scraped_at', 'name'];
if (!in_array($sortBy, $validSorts)) {
    $sortBy = 'discount_percentage';
}

// Build final query
$whereClause = implode(' AND ', $conditions);
$orderClause = "ph.{$sortBy} " . ($sortBy === 'price' ? 'ASC' : 'DESC');

$query = "
    SELECT 
        p.id,
        p.name,
        p.category,
        p.brand,
        p.url,
        p.image_url,
        p.platform,
        p.updated_at,
        ph.price,
        ph.original_price,
        ph.discount_percentage,
        ph.availability,
        ph.scraped_at,
        (ph.original_price - ph.price) as savings_amount
    FROM products p
    JOIN (
        SELECT 
            product_id,
            price,
            original_price,
            discount_percentage,
            availability,
            scraped_at,
            ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY scraped_at DESC) as rn
        FROM price_history
    ) ph ON p.id = ph.product_id AND ph.rn = 1
    WHERE {$whereClause}
    ORDER BY {$orderClause}
    LIMIT 48
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get filter options
$categoriesStmt = $conn->prepare("
    SELECT DISTINCT category 
    FROM products 
    WHERE is_active = 1 AND category IS NOT NULL AND category != '' 
    ORDER BY category
");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();

$platformsStmt = $conn->prepare("
    SELECT DISTINCT platform 
    FROM products 
    WHERE is_active = 1 
    ORDER BY platform
");
$platformsStmt->execute();
$platforms = $platformsStmt->fetchAll();

// Get statistics
$stats = $db->getStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Discount Monitor - Best Deals & Price Drops</title>
    <meta name="description" content="Find the best deals and price drops across major e-commerce platforms. Real-time monitoring of discounts and clearance sales.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-search-dollar me-2"></i>Deal Hunter
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php"><i class="fas fa-cog me-1"></i>Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3">Find Amazing Deals</h1>
                    <p class="lead mb-4">Discover the best discounts and price drops across major shopping platforms. Updated in real-time!</p>
                    
                    <!-- Stats Cards -->
                    <div class="row g-3 mt-4">
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format($stats['active_deals']) ?></div>
                                <div class="stat-label">Active Deals</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-number"><?= number_format(count($products)) ?></div>
                                <div class="stat-label">Showing</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-number">Live</div>
                                <div class="stat-label">Updates</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-shopping-cart hero-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" 
                                    <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="platform" class="form-label">Platform</label>
                    <select name="platform" id="platform" class="form-select">
                        <option value="">All Platforms</option>
                        <?php foreach ($platforms as $plat): ?>
                            <option value="<?= htmlspecialchars($plat['platform']) ?>" 
                                    <?= $platform === $plat['platform'] ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($plat['platform'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="min_discount" class="form-label">Min Discount %</label>
                    <input type="number" name="min_discount" id="min_discount" class="form-control" 
                           value="<?= $minDiscount ?>" min="0" max="99">
                </div>
                
                <div class="col-md-2">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" name="max_price" id="max_price" class="form-control" 
                           value="<?= $maxPrice ?>" min="0" step="0.01" placeholder="Any">
                </div>
                
                <div class="col-md-2">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="discount_percentage" <?= $sortBy === 'discount_percentage' ? 'selected' : '' ?>>Highest Discount</option>
                        <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>Lowest Price</option>
                        <option value="scraped_at" <?= $sortBy === 'scraped_at' ? 'selected' : '' ?>>Recently Updated</option>
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <div class="row mt-4">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h4>No deals found</h4>
                        <p>Try adjusting your filters or check back later for new deals!</p>
                        <a href="index.php" class="btn btn-primary">View All Deals</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="product-card h-100">
                            <?php if ($product['image_url']): ?>
                                <div class="product-image-container">
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                         class="product-image" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         loading="lazy">
                                    <?php if ($product['discount_percentage'] > 0): ?>
                                        <div class="discount-badge">
                                            <?= $product['discount_percentage'] ?>% OFF
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <h6 class="product-title mb-2"><?= htmlspecialchars($product['name']) ?></h6>
                                
                                <div class="product-meta mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-store me-1"></i><?= ucfirst(htmlspecialchars($product['platform'])) ?>
                                        <?php if ($product['brand']): ?>
                                            | <i class="fas fa-tag me-1"></i><?= htmlspecialchars($product['brand']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <?php if ($product['category']): ?>
                                    <span class="category-badge mb-2"><?= htmlspecialchars($product['category']) ?></span>
                                <?php endif; ?>
                                
                                <div class="price-section mt-auto">
                                    <div class="current-price">
                                        $<?= number_format($product['price'], 2) ?>
                                    </div>
                                    
                                    <?php if ($product['original_price'] > $product['price']): ?>
                                        <div class="original-price">
                                            Was: $<?= number_format($product['original_price'], 2) ?>
                                        </div>
                                        <div class="savings">
                                            You save: $<?= number_format($product['savings_amount'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!$product['availability']): ?>
                                        <div class="out-of-stock">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Out of Stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions mt-3">
                                    <a href="<?= htmlspecialchars($product['url']) ?>" 
                                       target="_blank" 
                                       class="btn btn-primary btn-sm w-100"
                                       rel="noopener noreferrer">
                                        <i class="fas fa-external-link-alt me-1"></i>View Deal
                                    </a>
                                </div>
                                
                                <div class="last-updated">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Updated <?= timeAgo($product['scraped_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (count($products) >= 48): ?>
            <div class="text-center mt-4">
                <p class="text-muted">Showing latest 48 deals. Use filters to narrow down results.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Deal Hunter</h5>
                    <p>Your source for the best deals and discounts across the web. Updated automatically 24/7.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><small>Last updated: <?= date('F j, Y \a\t g:i A') ?></small></p>
                    <p><small>&copy; <?= date('Y') ?> Deal Hunter. All rights reserved.</small></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>
</body>
</html>

<?php
/**
 * Helper function to display human-readable time difference
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
