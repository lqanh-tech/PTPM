<?php

class SEOHelper
{
    private static $instance = null;
    private $siteName = "Cửa hàng điện thoại";
    private $siteUrl = "";
    private $defaultImage = "/administrator/elements_LQA/img_LQA/logo.png";
    private $defaultDescription = "Cửa hàng điện thoại uy tín, chất lượng cao với giá tốt nhất";
    
    private function __construct()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $this->siteUrl = $protocol . "://" . $_SERVER['HTTP_HOST'];
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setSiteName($name)
    {
        $this->siteName = $name;
        return $this;
    }
    
    public function setDefaultImage($image)
    {
        $this->defaultImage = $image;
        return $this;
    }
    
    public function setDefaultDescription($description)
    {
        $this->defaultDescription = $description;
        return $this;
    }
    
    public function generateMetaTags($data = [])
    {
        $title = isset($data['title']) ? $data['title'] : $this->siteName;
        $description = isset($data['description']) ? $data['description'] : $this->defaultDescription;
        $image = isset($data['image']) ? $data['image'] : $this->defaultImage;
        $url = isset($data['url']) ? $data['url'] : $this->getCurrentUrl();
        $type = isset($data['type']) ? $data['type'] : 'website';
        $keywords = isset($data['keywords']) ? $data['keywords'] : '';
        
        if (!empty($image) && strpos($image, 'http') !== 0) {
            $image = $this->siteUrl . $image;
        }
        
        $canonical = isset($data['canonical']) ? $data['canonical'] : $url;
        
        $meta = [];
        
        $meta[] = '<meta charset="UTF-8">';
        $meta[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $meta[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        
        $meta[] = '<title>' . htmlspecialchars($title) . '</title>';
        $meta[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
        
        if (!empty($keywords)) {
            $meta[] = '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">';
        }
        
        $meta[] = '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">';
        
        $meta[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '">';
        $meta[] = '<meta property="og:description" content="' . htmlspecialchars($description) . '">';
        $meta[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';
        $meta[] = '<meta property="og:url" content="' . htmlspecialchars($url) . '">';
        $meta[] = '<meta property="og:type" content="' . htmlspecialchars($type) . '">';
        $meta[] = '<meta property="og:site_name" content="' . htmlspecialchars($this->siteName) . '">';
        $meta[] = '<meta property="og:locale" content="vi_VN">';
        
        $meta[] = '<meta name="twitter:card" content="summary_large_image">';
        $meta[] = '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">';
        $meta[] = '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">';
        $meta[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';
        
        $meta[] = '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">';
        $meta[] = '<meta name="googlebot" content="index, follow">';
        
        return implode("\n    ", $meta);
    }
    
    public function generateProductSchema($product)
    {
        $schema = [
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $product['name'],
            "description" => $product['description'],
            "image" => $product['image'],
            "sku" => isset($product['sku']) ? $product['sku'] : 'SP-' . str_pad($product['id'], 5, '0', STR_PAD_LEFT),
            "brand" => [
                "@type" => "Brand",
                "name" => isset($product['brand']) ? $product['brand'] : $this->siteName
            ],
            "offers" => [
                "@type" => "Offer",
                "url" => $product['url'],
                "priceCurrency" => "VND",
                "price" => $product['price'],
                "availability" => isset($product['in_stock']) && $product['in_stock'] ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
                "priceValidUntil" => date('Y-m-d', strtotime('+1 year'))
            ]
        ];
        
        if (isset($product['rating']) && $product['rating']['count'] > 0) {
            $schema["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $product['rating']['average'],
                "reviewCount" => $product['rating']['count'],
                "bestRating" => "5",
                "worstRating" => "1"
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    public function generateBreadcrumbSchema($breadcrumbs)
    {
        $items = [];
        $position = 1;
        
        foreach ($breadcrumbs as $crumb) {
            $items[] = [
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $crumb['name'],
                "item" => $crumb['url']
            ];
        }
        
        $schema = [
            "@context" => "https://schema.org/",
            "@type" => "BreadcrumbList",
            "itemListElement" => $items
        ];
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    public function generateOrganizationSchema($data)
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $this->siteName,
            "url" => $this->siteUrl,
            "logo" => $this->siteUrl . $this->defaultImage,
            "contactPoint" => [
                "@type" => "ContactPoint",
                "telephone" => isset($data['phone']) ? $data['phone'] : "",
                "contactType" => "customer service",
                "areaServed" => "VN",
                "availableLanguage" => "Vietnamese"
            ],
            "sameAs" => isset($data['social']) ? $data['social'] : []
        ];
        
        if (isset($data['address'])) {
            $schema["address"] = [
                "@type" => "PostalAddress",
                "streetAddress" => $data['address']['street'],
                "addressLocality" => $data['address']['city'],
                "addressCountry" => "VN"
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    public function generateWebsiteSchema()
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => $this->siteName,
            "url" => $this->siteUrl,
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $this->siteUrl . "/index.php?search={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ]
        ];
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    public function generateSitemap($pages)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($pages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d', strtotime($page['lastmod'])) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    public function generateRobotsTxt($rules)
    {
        $txt = "User-agent: *\n";
        
        if (isset($rules['disallow'])) {
            foreach ($rules['disallow'] as $path) {
                $txt .= "Disallow: $path\n";
            }
        }
        
        if (isset($rules['allow'])) {
            foreach ($rules['allow'] as $path) {
                $txt .= "Allow: $path\n";
            }
        }
        
        $txt .= "\nSitemap: " . $this->siteUrl . "/sitemap.xml\n";
        
        return $txt;
    }
    
    private function getCurrentUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    public function cleanUrl($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $text);
        $text = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $text);
        $text = preg_replace('/[ìíịỉĩ]/u', 'i', $text);
        $text = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $text);
        $text = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $text);
        $text = preg_replace('/[ỳýỵỷỹ]/u', 'y', $text);
        $text = preg_replace('/đ/u', 'd', $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
    
    public function truncateDescription($text, $length = 160)
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');
        
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        
        return $text . '...';
    }
}
