<?php
class AmazonScraper extends BaseScraper {
    private $baseUrl = 'https://www.amazon.com';
    
    public function scrapeProduct($url) {
        try {
            $html = $this->makeRequest($url);
            return $this->parseProductData($html, $url);
        } catch (Exception $e) {
            error_log("Amazon scraping error: " . $e->getMessage());
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
            'description' => ''
        ];
        
        // Product name
        $nameSelectors = [
            "//span[@id='productTitle']",
            "//h1[@id='title']//span",
            "//h1[contains(@class, 'product-title')]"
        ];
        
        foreach ($nameSelectors as $selector) {
            $nameNodes = $xpath->query($selector);
            if ($nameNodes->length > 0) {
                $data['name'] = $this->cleanText($nameNodes->item(0)->textContent);
                break;
            }
        }
        
        // Current price
        $priceSelectors = [
            "//span[contains(@class, 'a-price-whole')]",
            "//span[@class='a-price a-text-price a-size-medium a-color-base']//span[@class='a-offscreen']",
            "//span[@id='priceblock_dealprice']",
            "//span[@id='priceblock_ourprice']",
            "//span[contains(@class, 'a-price a-text-price a-size-medium a-color-base')]"
        ];
        
        foreach ($priceSelectors as $selector) {
            $priceNodes = $xpath->query($selector);
            if ($priceNodes->length > 0) {
                $priceText = $priceNodes->item(0)->textContent;
                $data['price'] = $this->extractPrice($priceText);
                if ($data['price'] > 0) break;
            }
        }
        
        // Original price (for discount calculation)
        $originalPriceSelectors = [
            "//span[contains(@class, 'a-price a-text-price')]/span[@class='a-offscreen']",
            "//span[@class='a-price a-text-price a-size-base a-color-secondary']//span[@class='a-offscreen']",
            "//span[@id='listPrice']",
            "//span[@data-a-strike='true']"
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
        
        // Availability
        $availabilitySelectors = [
            "//div[@id='availability']//span",
            "//div[contains(@class, 'a-section a-spacing-medium')]//span[contains(text(), 'In Stock')]",
            "//span[contains(text(), 'Currently unavailable')]"
        ];
        
        foreach ($availabilitySelectors as $selector) {
            $availNodes = $xpath->query($selector);
            if ($availNodes->length > 0) {
                $availText = strtolower($availNodes->item(0)->textContent);
                if (strpos($availText, 'unavailable') !== false || 
                    strpos($availText, 'out of stock') !== false) {
                    $data['availability'] = false;
                }
                break;
            }
        }
        
        // Product image
        $imageSelectors = [
            "//img[@id='landingImage']/@src",
            "//div[@id='imgTagWrapperId']//img/@src",
            "//img[contains(@class, 'a-dynamic-image')]/@src"
        ];
        
        foreach ($imageSelectors as $selector) {
            $imageNodes = $xpath->query($selector);
            if ($imageNodes->length > 0) {
                $data['image_url'] = $imageNodes->item(0)->textContent;
                break;
            }
        }
        
        return $data;
    }
    
    public function searchProducts($keywords, $category = '', $maxResults = 20) {
        $searchUrl = $this->baseUrl . '/s?k=' . urlencode($keywords);
        
        if ($category) {
            $searchUrl .= '&i=' . urlencode($category);
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
                $product['url'] = $this->baseUrl . $titleNode->item(0)->getAttribute('href');
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
