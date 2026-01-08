<?php

function displayProductStatusBadge($trangthai, $tonkho = 0, $size = 'medium') {
    $sizeClasses = [
        'small' => 'badge-sm',
        'medium' => 'badge-md',
        'large' => 'badge-lg'
    ];
    
    $sizeClass = isset($sizeClasses[$size]) ? $sizeClasses[$size] : $sizeClasses['medium'];
    
    $statusConfig = [
        'dang_ban' => [
            'label' => 'Còn hàng',
            'class' => 'status-available',
            'icon' => '✓',
            'show' => $tonkho > 0
        ],
        'het_hang' => [
            'label' => 'Hết hàng',
            'class' => 'status-out-of-stock',
            'icon' => '✗',
            'show' => true
        ],
        'ngung_ban' => [
            'label' => 'Ngừng kinh doanh',
            'class' => 'status-discontinued',
            'icon' => '⊘',
            'show' => true
        ]
    ];
    
    if ($trangthai === 'dang_ban' && $tonkho == 0) {
        $trangthai = 'het_hang';
    }
    
    $config = isset($statusConfig[$trangthai]) ? $statusConfig[$trangthai] : $statusConfig['dang_ban'];
    
    if ($trangthai === 'dang_ban' && $tonkho > 0) {
        return '';
    }
    
    if (!$config['show']) {
        return '';
    }
    
    return sprintf(
        '<span class="product-status-badge %s %s">
            <span class="status-icon">%s</span>
            <span class="status-text">%s</span>
        </span>',
        $config['class'],
        $sizeClass,
        $config['icon'],
        $config['label']
    );
}

function displayProductStatusInfo($trangthai, $tonkho = 0) {

    if ($trangthai === 'dang_ban' && $tonkho == 0) {
        $trangthai = 'het_hang';
    }
    
    $html = '<div class="product-status-info">';
    
    switch ($trangthai) {
        case 'dang_ban':
            if ($tonkho > 0) {
                $html .= '<div class="status-available-info">';
                $html .= '<i class="fas fa-check-circle"></i>';
                $html .= '<span class="status-label">Còn hàng</span>';
                if ($tonkho <= 5) {
                    $html .= '<span class="stock-warning">Chỉ còn ' . $tonkho . ' sản phẩm</span>';
                } else {
                    $html .= '<span class="stock-count">Còn ' . $tonkho . ' sản phẩm</span>';
                }
                $html .= '</div>';
            }
            break;
            
        case 'het_hang':
            $html .= '<div class="status-out-of-stock-info">';
            $html .= '<i class="fas fa-times-circle"></i>';
            $html .= '<span class="status-label">Hết hàng</span>';
            $html .= '<p class="status-message">Sản phẩm tạm thời hết hàng. Vui lòng liên hệ để đặt hàng trước.</p>';
            $html .= '</div>';
            break;
            
        case 'ngung_ban':
            $html .= '<div class="status-discontinued-info">';
            $html .= '<i class="fas fa-ban"></i>';
            $html .= '<span class="status-label">Ngừng kinh doanh</span>';
            $html .= '<p class="status-message">Sản phẩm này đã ngừng kinh doanh.</p>';
            $html .= '</div>';
            break;
    }
    
    $html .= '</div>';
    
    return $html;
}

function canPurchaseProduct($trangthai, $tonkho = 0) {
    return $trangthai === 'dang_ban' && $tonkho > 0;
}

function getProductStatusCSS() {
    return '
    <style>

        .product-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-status-badge.badge-sm {
            padding: 2px 8px;
            font-size: 10px;
        }
        
        .product-status-badge.badge-lg {
            padding: 6px 16px;
            font-size: 14px;
        }
        
        .product-status-badge.status-available {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .product-status-badge.status-out-of-stock {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .product-status-badge.status-discontinued {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .product-status-info {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
        }
        
        .status-available-info {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
            padding: 12px;
        }
        
        .status-out-of-stock-info {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
            padding: 12px;
        }
        
        .status-discontinued-info {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
            padding: 12px;
        }
        
        .product-status-info i {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .product-status-info .status-label {
            font-weight: 700;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        
        .product-status-info .status-message {
            margin: 8px 0 0 0;
            font-size: 14px;
        }
        
        .product-status-info .stock-count {
            display: inline-block;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .product-status-info .stock-warning {
            display: inline-block;
            margin-left: 10px;
            font-size: 14px;
            color: #d9534f;
            font-weight: 600;
        }
        
        .product-unavailable .add-to-cart-btn {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .product-card.status-discontinued {
            position: relative;
            opacity: 0.7;
        }
        
        .product-card.status-discontinued::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.5);
            pointer-events: none;
        }
    </style>
    ';
}

if (!defined('PRODUCT_STATUS_CSS_LOADED')) {
    define('PRODUCT_STATUS_CSS_LOADED', true);
    echo getProductStatusCSS();
}
?>
