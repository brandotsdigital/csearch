<?php
/**
 * Base Scraper Class
 * Foundation for all website-specific scrapers
 */
abstract class BaseScraper {
    protected $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/121.0'
    ];
    
    protected $delay = 2; // Seconds between requests
    protected $timeout = 30;
    protected $maxRetries = 3;
    
    abstract public function scrapeProduct($url);
    abstract protected function parseProductData($html, $url);
    
    /**
     * Make HTTP request with error handling and retries
     */
    protected function makeRequest($url, $retries = 0) {
        if ($retries >= $this->maxRetries) {
            throw new Exception("Max retries exceeded for URL: $url");
        }
        
        // Add random delay to be respectful
        if ($retries > 0) {
            sleep(rand(3, 8)); // Longer delay on retries
        } else {
            sleep(rand(1, $this->delay));
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: no-cache',
                'DNT: 1'
            ]
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error for URL: $url");
        }
        
        // Handle different HTTP response codes
        if ($httpCode === 429 || $httpCode === 503) {
            // Rate limited or service unavailable - retry with longer delay
            error_log("Rate limited (HTTP $httpCode) for $url, retrying...");
            sleep(rand(10, 20));
            return $this->makeRequest($url, $retries + 1);
        }
        
        if ($httpCode === 404) {
            throw new Exception("Product not found (404) for URL: $url");
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error $httpCode for $url, retrying...");
            return $this->makeRequest($url, $retries + 1);
        }
        
        if (empty($html)) {
            throw new Exception("Empty response for URL: $url");
        }
        
        return $html;
    }
    
    /**
     * Clean and normalize text content
     */
    protected function cleanText($text) {
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    /**
     * Extract price from text using multiple patterns
     */
    protected function extractPrice($text) {
        $text = $this->cleanText($text);
        
        // Remove common currency symbols and clean
        $text = preg_replace('/[^\d\.,]/', '', $text);
        
        // Match price patterns
        $patterns = [
            '/(\d{1,3}(?:,\d{3})*\.?\d{0,2})/',  // 1,234.56 or 1234.56
            '/(\d+\.?\d{0,2})/',                 // Simple numbers
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $price = str_replace(',', '', $matches[1]);
                $price = (float)$price;
                if ($price > 0) {
                    return $price;
                }
            }
        }
        
        return 0.0;
    }
    
    /**
     * Calculate discount percentage
     */
    protected function calculateDiscount($originalPrice, $currentPrice) {
        if ($originalPrice <= 0 || $currentPrice <= 0) {
            return 0;
        }
        
        if ($currentPrice >= $originalPrice) {
            return 0;
        }
        
        return round((($originalPrice - $currentPrice) / $originalPrice) * 100);
    }
    
    /**
     * Validate product data
     */
    protected function validateProductData($data) {
        $required = ['name', 'price'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Price should be numeric and positive
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            return false;
        }
        
        // Name should be reasonable length
        if (strlen($data['name']) < 5 || strlen($data['name']) > 500) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log scraping activity
     */
    protected function logScraping($productId, $url, $status, $errorMessage = null, $responseTime = null) {
        try {
            require_once '../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO scraping_logs (product_id, platform, url, status, error_message, response_time, scraped_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $productId,
                $this->getPlatformName(),
                $url,
                $status,
                $errorMessage,
                $responseTime
            ]);
        } catch (Exception $e) {
            error_log("Failed to log scraping activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get platform name (to be overridden by child classes)
     */
    protected function getPlatformName() {
        return 'unknown';
    }
}
?>
