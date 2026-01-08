<?php

class AssetMinifier
{
    private static $instance = null;
    private $cacheDir;
    private $cssFiles = [];
    private $jsFiles = [];
    private $inlineCSS = [];
    private $inlineJS = [];
    
    private function __construct()
    {
        $this->cacheDir = __DIR__ . '/../cache/assets/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function addCSS($file)
    {
        $this->cssFiles[] = $file;
        return $this;
    }
    
    public function addJS($file)
    {
        $this->jsFiles[] = $file;
        return $this;
    }
    
    public function addInlineCSS($css)
    {
        $this->inlineCSS[] = $css;
        return $this;
    }
    
    public function addInlineJS($js)
    {
        $this->inlineJS[] = $js;
        return $this;
    }
    
    public function minifyCSS($css)
    {
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        $css = preg_replace('/;}/', '}', $css);
        $css = trim($css);
        
        return $css;
    }
    
    public function minifyJS($js)
    {
        $js = preg_replace('/\/\/[^\n]*/', '', $js);
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*([{}:;,=\(\)\[\]<>!&|+\-\*\/])\s*/', '$1', $js);
        $js = trim($js);
        
        return $js;
    }
    
    public function getCombinedCSS()
    {
        $hash = md5(implode('|', $this->cssFiles) . implode('|', $this->inlineCSS));
        $cacheFile = $this->cacheDir . 'combined_' . $hash . '.css';
        
        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
            return '/cache/assets/combined_' . $hash . '.css';
        }
        
        $combined = '';
        
        foreach ($this->cssFiles as $file) {
            $fullPath = __DIR__ . '/../' . ltrim($file, '/');
            if (file_exists($fullPath)) {
                $combined .= file_get_contents($fullPath) . "\n";
            }
        }
        
        foreach ($this->inlineCSS as $css) {
            $combined .= $css . "\n";
        }
        
        $minified = $this->minifyCSS($combined);
        file_put_contents($cacheFile, $minified);
        
        return '/cache/assets/combined_' . $hash . '.css';
    }
    
    public function getCombinedJS()
    {
        $hash = md5(implode('|', $this->jsFiles) . implode('|', $this->inlineJS));
        $cacheFile = $this->cacheDir . 'combined_' . $hash . '.js';
        
        if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
            return '/cache/assets/combined_' . $hash . '.js';
        }
        
        $combined = '';
        
        foreach ($this->jsFiles as $file) {
            $fullPath = __DIR__ . '/../' . ltrim($file, '/');
            if (file_exists($fullPath)) {
                $combined .= file_get_contents($fullPath) . ";\n";
            }
        }
        
        foreach ($this->inlineJS as $js) {
            $combined .= $js . ";\n";
        }
        
        $minified = $this->minifyJS($combined);
        file_put_contents($cacheFile, $minified);
        
        return '/cache/assets/combined_' . $hash . '.js';
    }
    
    public function renderCSSTag()
    {
        if (empty($this->cssFiles) && empty($this->inlineCSS)) {
            return '';
        }
        
        $url = $this->getCombinedCSS();
        return '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">';
    }
    
    public function renderJSTag($defer = true)
    {
        if (empty($this->jsFiles) && empty($this->inlineJS)) {
            return '';
        }
        
        $url = $this->getCombinedJS();
        $deferAttr = $defer ? ' defer' : '';
        return '<script src="' . htmlspecialchars($url) . '"' . $deferAttr . '></script>';
    }
    
    public function getCriticalCSS()
    {
        return <<<'CSS'
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#333}
.container{max-width:1200px;margin:0 auto;padding:0 15px}
.row{display:flex;flex-wrap:wrap;margin:0 -15px}
.col{flex:1;padding:0 15px}
img{max-width:100%;height:auto}
a{color:#007bff;text-decoration:none}
.btn{display:inline-block;padding:10px 20px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer}
.card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden}
.navbar{background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.1);padding:10px 0}
.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
</style>
CSS;
    }
    
    public function getPreloadTags($resources)
    {
        $html = '';
        foreach ($resources as $resource) {
            $type = $resource['type'] ?? 'script';
            $as = $type === 'css' ? 'style' : ($type === 'font' ? 'font' : 'script');
            $crossorigin = $type === 'font' ? ' crossorigin' : '';
            
            $html .= '<link rel="preload" href="' . htmlspecialchars($resource['url']) . '" as="' . $as . '"' . $crossorigin . ">\n";
        }
        return $html;
    }
    
    public function getDNSPrefetchTags($domains)
    {
        $html = '';
        foreach ($domains as $domain) {
            $html .= '<link rel="dns-prefetch" href="' . htmlspecialchars($domain) . "\">\n";
            $html .= '<link rel="preconnect" href="' . htmlspecialchars($domain) . "\">\n";
        }
        return $html;
    }
}

function asset_minifier() {
    return AssetMinifier::getInstance();
}
