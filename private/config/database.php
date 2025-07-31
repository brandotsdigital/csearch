<?php
/**
 * Database Configuration Class
 * Handles MySQL database connection for the product scraper
 */
class Database {
    // Database configuration - UPDATE THESE WITH YOUR ACTUAL VALUES
    private $host = 'localhost';
    private $username = 'your_db_user';      // Change this to your database username
    private $password = 'your_db_password';  // Change this to your database password  
    private $database = 'product_scraper';   // Change this to your database name
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            // Log error and show user-friendly message
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // Get product count
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
            $stats['total_products'] = $stmt->fetch()['count'];
            
            // Get price history count
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM price_history");
            $stats['total_price_records'] = $stmt->fetch()['count'];
            
            // Get active deals count (>20% discount)
            $stmt = $this->connection->query("
                SELECT COUNT(*) as count 
                FROM price_history ph
                JOIN (
                    SELECT product_id, MAX(scraped_at) as latest
                    FROM price_history 
                    GROUP BY product_id
                ) latest ON ph.product_id = latest.product_id AND ph.scraped_at = latest.latest
                WHERE ph.discount_percentage >= 20
            ");
            $stats['active_deals'] = $stmt->fetch()['count'];
            
            // Get pending notifications
            $stmt = $this->connection->query("SELECT COUNT(*) as count FROM notifications WHERE is_sent = 0");
            $stats['pending_notifications'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch(PDOException $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'total_products' => 0,
                'total_price_records' => 0,
                'active_deals' => 0,
                'pending_notifications' => 0
            ];
        }
    }
}
?>
