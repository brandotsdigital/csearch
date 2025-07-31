<?php
class EbayScraper extends BaseScraper {
    private $baseUrl = 'https://www.ebay.com';
    
    public function scrapeProduct($url) {
        try {
            $html = $this->makeRequest($url);
            return $this->parseProductData($html, $url);
        } catch (Exception $e) {
            error_log("eBay scraping error: " . $e->getMessage());
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
            "//h1[@id='x-title-label-lbl']",
            "//h1[contains(@class, 'x-title-label')]",
            "//h1[@class='it-ttl']"
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
            "//span[@class='notranslate']",
            "//span[contains(@class, 'u-flL condText')]",
            "//span[@id='prcIsum']",
            "//span[@id='mm-saleDscPrc']"
        ];
        
        foreach ($priceSelectors as $selector) {
            $priceNodes = $xpath->query($selector);
            if ($priceNodes->length > 0) {
                $priceText = $priceNodes->item(0)->textContent;
                $data['price'] = $this->extractPrice($priceText);
                if ($data['price'] > 0) break;
            }
        }
        
        // Original price (for auctions or buy-it-now with discounts)
        $originalPriceSelectors = [
            "//span[contains(@class, 'u-strike')]",
            "//span[@id='orgPrc']",
            "//span[contains(text(), 'Was:')]//following-sibling::span"
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
        
        // Availability (eBay specific - check for auction end time or buy-it-now availability)
        $availabilitySelectors = [
            "//div[contains(@class, 'u-flL condText')]",
            "//div[@id='vi_acc_del_range']",
            "//span[contains(text(), 'Available')]"
        ];
        
        foreach ($availabilitySelectors as $selector) {
            $availNodes = $xpath->query($selector);
            if ($availNodes->length > 0) {
                $availText = strtolower($availNodes->item(0)->textContent);
                if (strpos($availText, 'ended') !== false || 
                    strpos($availText, 'sold') !== false) {
                    $data['availability'] = false;
                }
                break;
            }
        }
        
        // Product image
        $imageSelectors = [
            "//img[@id='icImg']/@src",
            "//div[@id='PictPanel']//img/@src",
            "//img[contains(@class, 'img img400')]/@src"
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
        $searchUrl = $this->baseUrl . '/sch/i.html?_nkw=' . urlencode($keywords);
        
        if ($category) {
            // eBay category mapping
            $categoryMap = [
                'electronics' => '293',
                'fashion' => '11450',
                'home' => '11700',
                'books' => '267',
                'sports' => '888'
            ];
            
            if (isset($categoryMap[strtolower($category)])) {
                $searchUrl .= '&_sacat=' . $categoryMap[strtolower($category)];
            }
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
