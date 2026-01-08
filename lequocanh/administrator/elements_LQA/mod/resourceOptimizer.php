<?php

class ResourceOptimizer {

    public static function minifyCss($css) {

        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        $css = str_replace(array("\r\n", "\r", "\n", "\t"), '', $css);
        $css = preg_replace('/\s+/', ' ', $css);

        $css = preg_replace('/ *(;|\{|\}) */', '$1', $css);
        $css = preg_replace('/ *: */', ':', $css);
        $css = preg_replace('/ *, */', ',', $css);
        $css = preg_replace('/;}/', '}', $css);
        $css = trim($css);
        return $css;
    }

    public static function minifyJs($js) {

        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        $js = preg_replace('/\/\/.*$/m', '', $js);

        $js = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $js);
        $js = preg_replace('/ +/', ' ', $js);
        $js = trim($js);

        $js = preg_replace('/ *([+\-*\/=<>!&|;,\(\)\{\}\[\]]) */', '$1', $js);
        return $js;
    }

    public static function optimizeImage($imagePath) {

        return true;
    }

    public static function getLazyLoadImageHtml($src, $alt = '', $class = '') {
        return '<img data-src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" class="lazy-load ' . htmlspecialchars($class) . '">';
    }

    public static function getLazyLoadIframeHtml($src, $class = '') {
        return '<iframe data-src="' . htmlspecialchars($src) . '" class="lazy-load ' . htmlspecialchars($class) . '" frameborder="0" allowfullscreen></iframe>';
    }
}
?>