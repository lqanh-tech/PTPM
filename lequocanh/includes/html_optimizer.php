<?php

class HTMLOptimizer
{
    private static $instance = null;
    private $options = [
        'minify' => true,
        'remove_comments' => true,
        'remove_whitespace' => true,
        'compress_inline_css' => true,
        'compress_inline_js' => true,
        'lazy_load_images' => true,
        'add_loading_attr' => true,
        'add_decoding_attr' => true
    ];
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }
    
    public function optimize($html)
    {
        if ($this->options['remove_comments']) {
            $html = $this->removeComments($html);
        }
        
        if ($this->options['compress_inline_css']) {
            $html = $this->compressInlineCSS($html);
        }
        
        if ($this->options['compress_inline_js']) {
            $html = $this->compressInlineJS($html);
        }
        
        if ($this->options['lazy_load_images']) {
            $html = $this->addLazyLoading($html);
        }
        
        if ($this->options['remove_whitespace']) {
            $html = $this->removeWhitespace($html);
        }
        
        return $html;
    }
    
    private function removeComments($html)
    {
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
        return $html;
    }
    
    private function removeWhitespace($html)
    {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/\s+>/', '>', $html);
        $html = preg_replace('/<\s+/', '<', $html);
        
        return trim($html);
    }
    
    private function compressInlineCSS($html)
    {
        return preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function($matches) {
            $css = $matches[1];
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);
            $css = preg_replace('/\s+/', ' ', $css);
            $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
            $css = preg_replace('/;}/', '}', $css);
            return '<style>' . trim($css) . '</style>';
        }, $html);
    }
    
    private function compressInlineJS($html)
    {
        return preg_replace_callback('/<script[^>]*>(.*?)<\/script>/is', function($matches) {
            if (strpos($matches[0], 'src=') !== false) {
                return $matches[0];
            }
            
            $js = $matches[1];
            $js = preg_replace('/\/\/[^\n]*/', '', $js);
            $js = preg_replace('/\/\*.*?\*\//s', '', $js);
            $js = preg_replace('/\s+/', ' ', $js);
            
            return '<script>' . trim($js) . '</script>';
        }, $html);
    }
    
    private function addLazyLoading($html)
    {
        $html = preg_replace_callback('/<img([^>]*)>/i', function($matches) {
            $attrs = $matches[1];
            
            if (strpos($attrs, 'loading=') !== false) {
                return $matches[0];
            }
            
            if (strpos($attrs, 'data-no-lazy') !== false) {
                return $matches[0];
            }
            
            $newAttrs = $attrs;
            
            if ($this->options['add_loading_attr'] && strpos($attrs, 'loading=') === false) {
                $newAttrs .= ' loading="lazy"';
            }
            
            if ($this->options['add_decoding_attr'] && strpos($attrs, 'decoding=') === false) {
                $newAttrs .= ' decoding="async"';
            }
            
            return '<img' . $newAttrs . '>';
        }, $html);
        
        return $html;
    }
    
    public function startCapture()
    {
        ob_start();
    }
    
    public function endCapture()
    {
        $html = ob_get_clean();
        return $this->optimize($html);
    }
    
    public static function minifyHTML($html)
    {
        return self::getInstance()->optimize($html);
    }
}

function html_optimizer() {
    return HTMLOptimizer::getInstance();
}

function minify_html($html) {
    return HTMLOptimizer::minifyHTML($html);
}
