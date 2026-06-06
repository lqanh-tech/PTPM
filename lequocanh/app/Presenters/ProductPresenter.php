<?php

declare(strict_types=1);

namespace App\Presenters;

/**
 * Pure view-layer formatting for Product status.
 *
 * Status strings are the display values produced by Product::getProductStatus()
 * (Vietnamese). This class is pure: no state, no DB, no DI. Safe to call from
 * any view layer.
 */
class ProductPresenter
{
    /**
     * Map a display status string to a CSS class name.
     */
    public static function cssClass(string $displayStatus): string
    {
        return match ($displayStatus) {
            'Đang bán' => 'status-active',
            'Ngừng bán' => 'status-discontinued',
            'Hết hàng' => 'status-outofstock',
            default => 'status-unknown',
        };
    }

    /**
     * Map a display status string to a hex color.
     */
    public static function color(string $displayStatus): string
    {
        return match ($displayStatus) {
            'Đang bán' => '#27ae60',
            'Ngừng bán' => '#e74c3c',
            'Hết hàng' => '#95a5a6',
            default => '#34495e',
        };
    }
}
