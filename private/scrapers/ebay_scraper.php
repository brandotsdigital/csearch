<?php
require_once 'base_scraper.php';

/**
 * eBay Product Scraper
 * Handles scraping of eBay product pages and search results
 */
class EbayScraper extends BaseScraper {
    private $baseUrl = 'https://www.ebay.com';
    
    protected function getPlatformName() {
        return 'ebay';
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
            error_log("eBay scraping error for $url: " . $e->getMessage());
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
        
        // Product name
        $nameSelectors = [
            "//h1[@id='x-title-label-lbl']",
            "//h1[contains(@class, 'x-title-label')]",
            "//h1[@class='it-ttl']",
            "//h1[contains(@class, 'notranslate')]"
        ];
        
        foreach ($nameSelectors as $selector) {
            $nameNodes = $xpath->query($selector);
            if ($nameNodes->length > 0) {
                $data['name'] = $this->cleanText($nameNodes->item(0)->textContent);
                if (strlen($data['name']) > 10) break;
            }
        }
        
        // Current price - eBay has various price formats
        $priceSelectors = [
            "//span[@class='notranslate']",
            "//span[contains(@class, 'u-flL condText')]",
            "//span[@id='prcIsum']",
            "//span[@id='mm-saleDscPrc']",
            "//div[contains(@class, 'price')]//span[@class='notranslate']",
            "//div[@id='mainContent']//span[contains(@class, 'price')]"
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
        
        // Original price (for auctions or buy-it-now with discounts)
        $originalPriceSelectors = [
            "//span[contains(@class, 'u-strike')]",
            "//span[@id='orgPrc']",
            "//span[contains(text(), 'Was:')]//following-sibling::span",
            "//div[contains(@class, 'originalPrice')]//span"
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
            "//h2[contains(@class, 'x-item-title-label')]//span",
            "//div[@id='vi-acc-del-range']//span",
            "//span[contains(@class, 'brand')]"
        ];
        
        foreach ($brandSelectors as $selector) {
            $brandNodes = $xpath->query($selector);
            if ($brandNodes->length > 0) {
                $brandText = $this->cleanText($brandNodes->item(0)->textContent);
                if (!empty($brandText) && strlen($brandText) < 50) {
                    $data['brand'] = $brandText;
                    break;
                }
            }
        }
        
        // Availability (eBay specific - check for auction end time or buy-it-now availability)
        $availabilitySelectors = [
            "//div[contains(@class, 'u-flL condText')]",
            "//div[@id='vi_acc_del_range']",
            "//span[contains(text(), 'Available')]",
            "//div[contains(@class, 'unavailable')]"
        ];
        
        foreach ($availabilitySelectors as $selector) {
            $availNodes = $xpath->query($selector);
            if ($availNodes->length > 0) {
                $availText = strtolower($this->cleanText($availNodes->item(0)->textContent));
                if (strpos($availText, 'ended') !== false || 
                    strpos($availText, 'sold') !== false ||
                    strpos($availText, 'unavailable') !== false) {
                    $data['availability'] = false;
                    break;
                }
            }
        }
        
        // Product image
        $imageSelectors = [
            "//img[@id='icImg']/@src",
            "//div[@id='PictPanel']//img/@src",
            "//img[contains(@class, 'img img400')]/@src",
            "//div[@id='mainImgHldr']//img/@src"
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
            "//nav[@aria-label='Breadcrumb']//a",
            "//div[@id='vi-VR-brumb-lnkLst']//a"
        ];
        
        foreach ($categorySelectors as $selector) {
            $categoryNodes = $xpath->query($selector);
            if ($categoryNodes->length > 1) {
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
     * Search for products on eBay
     */
    public function searchProducts($keywords, $category = '', $maxResults = 20) {
        $searchUrl = $this->baseUrl . '/sch/i.html?_nkw=' . urlencode($keywords);
        
        // eBay category mapping
        $categoryMap = [
            'electronics' => '293',
            'fashion' => '11450',
            'home' => '11700',
            'books' => '267',
            'sports' => '888',
            'automotive' => '6000',
            'health' => '26395'
        ];
        
        if ($category && isset($categoryMap[strtolower($category)])) {
            $searchUrl .= '&_sacat=' . $categoryMap[strtolower($category)];
        }
        
        // Add buy-it-now filter for immediate purchase options
        $searchUrl .= '&LH_BIN=1';
        
        try {
            $html = $this->makeRequest($searchUrl);
            return $this->parseSearchResults($html, $maxResults);
        } catch (Exception $e) {
            error_log("eBay search error: " . $e->getMessage());
            return [];
        }
    }
    
    private function parseSearchResults($html, $maxResults) {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        
        $products = [];
        $productNodes = $xpath->query("//div[contains(@class, 's-item__wrapper')]");
        
        $count = 0;
        foreach ($productNodes as $node) {
            if ($count >= $maxResults) break;
            
            $product = [
                'name' => '',
                'url' => '',
                'price' => 0.0,
                'image_url' => '',
                'platform' => 'ebay'
            ];
            
            // Product title and URL
            $titleNode = $xpath->query(".//h3[contains(@class, 's-item__title')]//a", $node);
            if ($titleNode->length > 0) {
                $product['name'] = $this->cleanText($titleNode->item(0)->textContent);
                $product['url'] = $titleNode->item(0)->getAttribute('href');
            }
            
            // Price
            $priceNode = $xpath->query(".//span[contains(@class, 's-item__price')]", $node);
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
