<?php

declare(strict_types=1);

namespace Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use App\Presenters\ProductPresenter;

class ProductPresenterTest extends TestCase
{
    // ─── cssClass ───────────────────────────────────────────────

    public function testCssClassActive(): void
    {
        $this->assertSame('status-active', ProductPresenter::cssClass('Đang bán'));
    }

    public function testCssClassDiscontinued(): void
    {
        $this->assertSame('status-discontinued', ProductPresenter::cssClass('Ngừng bán'));
    }

    public function testCssClassOutOfStock(): void
    {
        $this->assertSame('status-outofstock', ProductPresenter::cssClass('Hết hàng'));
    }

    public function testCssClassUnknownDefaultsToUnknown(): void
    {
        $this->assertSame('status-unknown', ProductPresenter::cssClass('Some Other Status'));
        $this->assertSame('status-unknown', ProductPresenter::cssClass(''));
    }

    // ─── color ──────────────────────────────────────────────────

    public function testColorActive(): void
    {
        $this->assertSame('#27ae60', ProductPresenter::color('Đang bán'));
    }

    public function testColorDiscontinued(): void
    {
        $this->assertSame('#e74c3c', ProductPresenter::color('Ngừng bán'));
    }

    public function testColorOutOfStock(): void
    {
        $this->assertSame('#95a5a6', ProductPresenter::color('Hết hàng'));
    }

    public function testColorUnknownDefaultsToDark(): void
    {
        $this->assertSame('#34495e', ProductPresenter::color('Some Other Status'));
        $this->assertSame('#34495e', ProductPresenter::color(''));
    }
}
