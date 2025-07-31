<?php
require_once 'base_scraper.php';

/**
 * Amazon Product Scraper
 * Handles scraping of Amazon product pages and search results
 */
class AmazonScraper extends BaseScraper {
    private $baseUrl = 'https://www.amazon.com';
    
    protected function getPlatformName() {
        return 'amazon';
    }
    
    public function scrapeProduct($url) {
        $startTime = microtime(true);
        
        try {
            $html = $this->makeRequest($url);
            $data = $this->parseProductData($html, $url);
            
            if (!$this->validateProductData($data)) {
                throw new Exception("Invalid product data extracted");
            }
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logScraping(null, $url, 'success', null, $responseTime);
            
            return $data;
            
        } catch (Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logScraping(null, $url, 'error', $e->getMessage(), $responseTime);
            error_log("Amazon scraping error for $url: " . $e->getMessage());
            return null;
        }
    }
    
    protected function parseProductData($html, $url) {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        
        $data = [
            'name' => '',
            'price' => 0.0,
            'original_price' => 0.0,
            'availability' => true,
            'image_url' => '',
            'description' => '',
            'brand' => '',
            'category' => ''
        ];
        
        // Product name - try multiple selectors
        $nameSelectors = [
            "//span[@id='productTitle']",
            "//h1[@id='title']//span",
            "//h1[contains(@class, 'product-title')]",
            "//div[@id='titleSection']//h1"
        ];
        
        foreach ($nameSelectors as $selector) {
            $nameNodes = $xpath->query($selector);
            if ($nameNodes->length > 0) {
                $data['name'] = $this->cleanText($nameNodes->item(0)->textContent);
                if (strlen($data['name']) > 10) break; // Make sure we got a proper title
            }
        }
        
        // Current price - Amazon has many price formats
        $priceSelectors = [
            "//span[contains(@class, 'a-price-whole')]",
            "//span[@class='a-price a-text-price a-size-medium a-color-base']//span[@class='a-offscreen']",
            "//span[@id='priceblock_dealprice']",
            "//span[@id='priceblock_ourprice']",
            "//span[@id='price_inside_buybox']",
            "//a[@id='buybox-see-all-buying-choices']//span[@class='a-price-whole']",
            "//div[@data-feature-name='corePrice']//span[@class='a-offscreen']"
        ];
        
        foreach ($priceSelectors as $selector) {
            $priceNodes = $xpath->query($selector);
            if ($priceNodes->length > 0) {
                $priceText = $priceNodes->item(0)->textContent;
                $price = $this->extractPrice($priceText);
                if ($price > 0) {
                    $data['price'] = $price;
                    break;
                }
            }
        }
        
        // Original price (for discount calculation)
        $originalPriceSelectors = [
            "//span[contains(@class, 'a-price a-text-price')]/span[@class='a-offscreen']",
            "//span[@class='a-price a-text-price a-size-base a-color-secondary']//span[@class='a-offscreen']",
            "//span[@id='listPrice']//text()",
            "//span[@data-a-strike='true']",
            "//span[contains(@class, 'a-text-strike')]"
        ];
        
        foreach ($originalPriceSelectors as $selector) {
            $originalNodes = $xpath->query($selector);
            if ($originalNodes->length > 0) {
                $originalText = $originalNodes->item(0)->textContent;
                $originalPrice = $this->extractPrice($originalText);
                if ($originalPrice > $data['price']) {
                    $data['original_price'] = $originalPrice;
                    break;
                }
            }
        }
        
        // If no original price found, use current price
        if ($data['original_price'] == 0) {
            $data['original_price'] = $data['price'];
        }
        
        // Brand
        $brandSelectors = [
            "//a[@id='bylineInfo']",
            "//span[@class='a-size-base-plus']//a",
            "//div[@id='bylineInfo_feature_div']//span"
        ];
        
        foreach ($brandSelectors as $selector) {
            $brandNodes = $xpath->query($selector);
            if ($brandNodes->length > 0) {
                $brandText = $this->cleanText($brandNodes->item(0)->textContent);
                if (!empty($brandText) && !strpos(strtolower($brandText), 'visit')) {
                    $data['brand'] = $brandText;
                    break;
                }
            }
        }
        
        // Availability
        $availabilitySelectors = [
            "//div[@id='availability']//span",
            "//div[contains(@class, 'a-section a-spacing-medium')]//span",
            "//span[contains(text(), 'Currently unavailable')]",
            "//span[contains(text(), 'Out of Stock')]"
        ];
        
        foreach ($availabilitySelectors as $selector) {
            $availNodes = $xpath->query($selector);
            if ($availNodes->length > 0) {
                $availText = strtolower($this->cleanText($availNodes->item(0)->textContent));
                if (strpos($availText, 'unavailable') !== false || 
                    strpos($availText, 'out of stock') !== false ||
                    strpos($availText, 'currently not available') !== false) {
                    $data['availability'] = false;
                    break;
                }
            }
        }
        
        // Product image
        $imageSelectors = [
            "//img[@id='landingImage']/@src",
            "//div[@id='imgTagWrapperId']//img/@src",
            "//img[contains(@class, 'a-dynamic-image')]/@src",
            "//div[@id='main-image-container']//img/@src"
        ];
        
        foreach ($imageSelectors as $selector) {
            $imageNodes = $xpath->query($selector);
            if ($imageNodes->length > 0) {
                $imageUrl = $imageNodes->item(0)->textContent;
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $data['image_url'] = $imageUrl;
                    break;
                }
            }
        }
        
        // Category (from breadcrumb)
        $categorySelectors = [
            "//div[@id='wayfinding-breadcrumbs_feature_div']//a",
            "//nav[@aria-label='Breadcrumb']//a"
        ];
        
        foreach ($categorySelectors as $selector) {
            $categoryNodes = $xpath->query($selector);
            if ($categoryNodes->length > 1) { // Skip first breadcrumb (usually "All")
                $categoryText = $this->cleanText($categoryNodes->item(1)->textContent);
                if (!empty($categoryText) && strlen($categoryText) < 50) {
                    $data['category'] = $categoryText;
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Search for products on Amazon
     */
    public function searchProducts($keywords, $category = '', $maxResults = 20) {
        $searchUrl = $this->baseUrl . '/s?k=' . urlencode($keywords);
        
        // Add category filter if specified
        $categoryMap = [
            'electronics' => 'aps&rh=n%3A172282',
            'fashion' => 'fashion',
            'home' => 'garden',
            'books' => 'stripbooks',
            'sports' => 'sporting'
        ];
        
        if ($category && isset($categoryMap[strtolower($category)])) {
            $searchUrl .= '&i=' . $categoryMap[strtolower($category)];
        }
        
        try {
            $html = $this->makeRequest($searchUrl);
            return $this->parseSearchResults($html, $maxResults);
        } catch (Exception $e) {
            error_log("Amazon search error: " . $e->getMessage());
            return [];
        }
    }
    
    private function parseSearchResults($html, $maxResults) {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        
        $products = [];
        $productNodes = $xpath->query("//div[@data-component-type='s-search-result']");
        
        $count = 0;
        foreach ($productNodes as $node) {
            if ($count >= $maxResults) break;
            
            $product = [
                'name' => '',
                'url' => '',
                'price' => 0.0,
                'image_url' => '',
                'platform' => 'amazon'
            ];
            
            // Product title and URL
            $titleNode = $xpath->query(".//h2//a", $node);
            if ($titleNode->length > 0) {
                $product['name'] = $this->cleanText($titleNode->item(0)->textContent);
                $href = $titleNode->item(0)->getAttribute('href');
                $product['url'] = $this->baseUrl . $href;
            }
            
            // Price
            $priceNode = $xpath->query(".//span[contains(@class, 'a-price-whole')]", $node);
            if ($priceNode->length > 0) {
                $product['price'] = $this->extractPrice($priceNode->item(0)->textContent);
            }
            
            // Image
            $imageNode = $xpath->query(".//img/@src", $node);
            if ($imageNode->length > 0) {
                $product['image_url'] = $imageNode->item(0)->textContent;
            }
            
            if ($product['name'] && $product['url']) {
                $products[] = $product;
                $count++;
            }
        }
        
        return $products;
    }
}
?>
