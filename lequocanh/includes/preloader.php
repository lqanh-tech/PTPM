<?php

class Preloader
{
    private static $instance = null;
    private $preloadLinks = [];
    private $prefetchLinks = [];
    private $preconnectDomains = [];
    private $criticalCSS = '';
    private $deferredJS = [];
    private $asyncJS = [];
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function preload($url, $as, $type = null, $crossorigin = false)
    {
        $this->preloadLinks[] = [
            'url' => $url,
            'as' => $as,
            'type' => $type,
            'crossorigin' => $crossorigin
        ];
        return $this;
    }
    
    public function prefetch($url)
    {
        $this->prefetchLinks[] = $url;
        return $this;
    }
    
    public function preconnect($domain)
    {
        $this->preconnectDomains[] = $domain;
        return $this;
    }
    
    public function setCriticalCSS($css)
    {
        $this->criticalCSS = $css;
        return $this;
    }
    
    public function addDeferredJS($url)
    {
        $this->deferredJS[] = $url;
        return $this;
    }
    
    public function addAsyncJS($url)
    {
        $this->asyncJS[] = $url;
        return $this;
    }
    
    public function renderHead()
    {
        $html = '';
        
        foreach ($this->preconnectDomains as $domain) {
            $html .= '<link rel="dns-prefetch" href="' . htmlspecialchars($domain) . '">' . "\n";
            $html .= '<link rel="preconnect" href="' . htmlspecialchars($domain) . '" crossorigin>' . "\n";
        }
        
        foreach ($this->preloadLinks as $link) {
            $html .= '<link rel="preload" href="' . htmlspecialchars($link['url']) . '" as="' . $link['as'] . '"';
            if ($link['type']) {
                $html .= ' type="' . $link['type'] . '"';
            }
            if ($link['crossorigin']) {
                $html .= ' crossorigin';
            }
            $html .= '>' . "\n";
        }
        
        foreach ($this->prefetchLinks as $url) {
            $html .= '<link rel="prefetch" href="' . htmlspecialchars($url) . '">' . "\n";
        }
        
        if (!empty($this->criticalCSS)) {
            $html .= '<style id="critical-css">' . $this->criticalCSS . '</style>' . "\n";
        }
        
        return $html;
    }
    
    public function renderScripts()
    {
        $html = '';
        
        foreach ($this->deferredJS as $url) {
            $html .= '<script src="' . htmlspecialchars($url) . '" defer></script>' . "\n";
        }
        
        foreach ($this->asyncJS as $url) {
            $html .= '<script src="' . htmlspecialchars($url) . '" async></script>' . "\n";
        }
        
        return $html;
    }
    
    public static function getDefaultCriticalCSS()
    {
        return <<<'CSS'
*{box-sizing:border-box}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#333;background:#fff}.container{max-width:1200px;margin:0 auto;padding:0 15px}img{max-width:100%;height:auto;display:block}.navbar{background:#fff;box-shadow:0 2px 4px rgba(0,0,0,.1);position:sticky;top:0;z-index:1000}.card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);overflow:hidden}.btn{display:inline-block;padding:10px 20px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-weight:600}.skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}.visually-hidden{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
CSS;
    }
    
    public static function getLoadingSpinner()
    {
        return <<<'HTML'
<div id="page-loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;display:flex;align-items:center;justify-content:center;z-index:99999;transition:opacity .3s">
    <div style="width:50px;height:50px;border:3px solid #f3f3f3;border-top:3px solid #007bff;border-radius:50%;animation:spin 1s linear infinite"></div>
</div>
<style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>
<script>window.addEventListener('load',function(){var l=document.getElementById('page-loader');if(l){l.style.opacity='0';setTimeout(function(){l.remove()},300)}})</script>
HTML;
    }
    
    public static function getProgressBar()
    {
        return <<<'HTML'
<div id="progress-bar" style="position:fixed;top:0;left:0;width:0;height:3px;background:linear-gradient(90deg,#007bff,#00d4ff);z-index:99999;transition:width .3s"></div>
<script>
(function(){
    var bar=document.getElementById('progress-bar'),progress=0;
    var interval=setInterval(function(){
        progress+=Math.random()*10;
        if(progress>90)progress=90;
        bar.style.width=progress+'%';
    },100);
    window.addEventListener('load',function(){
        clearInterval(interval);
        bar.style.width='100%';
        setTimeout(function(){bar.style.opacity='0';setTimeout(function(){bar.remove()},300)},200);
    });
})();
</script>
HTML;
    }
}

function preloader() {
    return Preloader::getInstance();
}
