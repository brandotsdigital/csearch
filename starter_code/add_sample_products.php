<?php
/**
 * Simple Product Management Script
 * Run this to add sample products for testing your scraper
 */

require_once '../private/config/database.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Sample products to add for testing
$sampleProducts = [
    // Amazon Products
    [
        'name' => 'Amazon Echo Dot (4th Gen)',
        'category' => 'Electronics',
        'brand' => 'Amazon',
        'url' => 'https://www.amazon.com/dp/B07FZ8S74R',
        'platform' => 'amazon',
        'description' => 'Smart speaker with Alexa'
    ],
    [
        'name' => 'Apple AirPods Pro',
        'category' => 'Electronics', 
        'brand' => 'Apple',
        'url' => 'https://www.amazon.com/dp/B07ZPC9QD4',
        'platform' => 'amazon',
        'description' => 'Wireless earbuds with noise cancellation'
    ],
    [
        'name' => 'Instant Pot Duo 7-in-1',
        'category' => 'Home & Garden',
        'brand' => 'Instant Pot',
        'url' => 'https://www.amazon.com/dp/B00FLYWNYQ',
        'platform' => 'amazon',
        'description' => 'Electric pressure cooker'
    ],
    
    // eBay Products  
    [
        'name' => 'Nike Air Max 90',
        'category' => 'Fashion',
        'brand' => 'Nike',
        'url' => 'https://www.ebay.com/sch/i.html?_nkw=nike+air+max+90',
        'platform' => 'ebay',
        'description' => 'Classic running shoes'
    ],
    [
        'name' => 'Canon EOS Rebel T7',
        'category' => 'Electronics',
        'brand' => 'Canon', 
        'url' => 'https://www.ebay.com/sch/i.html?_nkw=canon+eos+rebel+t7',
        'platform' => 'ebay',
        'description' => 'DSLR camera for beginners'
    ],
    
    // Book examples
    [
        'name' => 'The 7 Habits of Highly Effective People',
        'category' => 'Books',
        'brand' => '',
        'url' => 'https://www.amazon.com/dp/0743269519',
        'platform' => 'amazon',
        'description' => 'Self-help book by Stephen Covey'
    ]
];

// Function to add products
function addProduct($conn, $product) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO products (name, category, brand, url, platform, description, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $product['name'],
            $product['category'],
            $product['brand'],
            $product['url'],
            $product['platform'],
            $product['description']
        ]);
        
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        echo "Error adding product {$product['name']}: " . $e->getMessage() . "\n";
        return false;
    }
}

// Add sample products
echo "Adding sample products for testing...\n\n";

foreach ($sampleProducts as $product) {
    $productId = addProduct($conn, $product);
    if ($productId) {
        echo "✅ Added: {$product['name']} (ID: {$productId})\n";
        
        // Add initial price history (dummy data for testing)
        $dummyPrice = rand(1999, 9999) / 100; // Random price between $19.99 - $99.99
        $dummyOriginalPrice = $dummyPrice * 1.2; // 20% higher original price
        $discount = round((($dummyOriginalPrice - $dummyPrice) / $dummyOriginalPrice) * 100);
        
        $priceStmt = $conn->prepare("
            INSERT INTO price_history (product_id, price, original_price, discount_percentage, availability, scraped_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        
        $priceStmt->execute([$productId, $dummyPrice, $dummyOriginalPrice, $discount]);
        echo "   💰 Added sample price: $" . number_format($dummyPrice, 2) . " ({$discount}% off)\n";
    }
    echo "\n";
}

// Show summary
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1");
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();

echo "========================================\n";
echo "✅ Setup complete!\n";
echo "📊 Total products in database: {$totalProducts}\n";
echo "🌐 Visit your website to see the results\n";
echo "⚡ Run the scraper: php /path/to/cron/scrape.php\n";
echo "========================================\n";

// Optional: Show recent products
echo "\nRecent products added:\n";
$recentStmt = $conn->prepare("
    SELECT p.name, p.platform, ph.price, ph.discount_percentage 
    FROM products p
    LEFT JOIN (
        SELECT product_id, price, discount_percentage,
               ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY scraped_at DESC) as rn
        FROM price_history
    ) ph ON p.id = ph.product_id AND ph.rn = 1
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recentStmt->execute();
$recentProducts = $recentStmt->fetchAll();

foreach ($recentProducts as $product) {
    $price = $product['price'] ? '$' . number_format($product['price'], 2) : 'No price';
    $discount = $product['discount_percentage'] ? $product['discount_percentage'] . '% off' : '';
    echo "- {$product['name']} ({$product['platform']}) - {$price} {$discount}\n";
}
?>
