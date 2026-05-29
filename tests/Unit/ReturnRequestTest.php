<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\ReturnRequest;

class ReturnRequestTest extends TestCase
{
    // ─── Constants ────────────────────────────────────────────────

    public function testStatusConstants(): void
    {
        $this->assertEquals('pending', ReturnRequest::STATUS_PENDING);
        $this->assertEquals('approved', ReturnRequest::STATUS_APPROVED);
        $this->assertEquals('rejected', ReturnRequest::STATUS_REJECTED);
        $this->assertEquals('processing', ReturnRequest::STATUS_PROCESSING);
        $this->assertEquals('completed', ReturnRequest::STATUS_COMPLETED);
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals('return', ReturnRequest::TYPE_RETURN);
        $this->assertEquals('exchange', ReturnRequest::TYPE_EXCHANGE);
    }

    // ─── Table Configuration ──────────────────────────────────────

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(ReturnRequest::class);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        $this->assertEquals('doi_tra', $property->getValue());
    }

    public function testPrimaryKey(): void
    {
        $reflection = new \ReflectionClass(ReturnRequest::class);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $this->assertEquals('id', $property->getValue());
    }

    // ─── Fillable Attributes ──────────────────────────────────────

    public function testFillableContainsRequiredFields(): void
    {
        $reflection = new \ReflectionClass(ReturnRequest::class);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue();

        $this->assertContains('ma_don_hang', $fillable);
        $this->assertContains('ma_nguoi_dung', $fillable);
        $this->assertContains('ly_do', $fillable);
        $this->assertContains('loai', $fillable);
        $this->assertContains('trang_thai', $fillable);
    }

    // ─── Status Methods ───────────────────────────────────────────

    public function testGetStatusLabelReturnsCorrectLabels(): void
    {
        $request = new ReturnRequest();
        
        $request->trang_thai = ReturnRequest::STATUS_PENDING;
        $this->assertEquals('Chờ xử lý', $request->getStatusLabel());

        $request->trang_thai = ReturnRequest::STATUS_APPROVED;
        $this->assertEquals('Đã duyệt', $request->getStatusLabel());

        $request->trang_thai = ReturnRequest::STATUS_REJECTED;
        $this->assertEquals('Từ chối', $request->getStatusLabel());

        $request->trang_thai = ReturnRequest::STATUS_PROCESSING;
        $this->assertEquals('Đang xử lý', $request->getStatusLabel());

        $request->trang_thai = ReturnRequest::STATUS_COMPLETED;
        $this->assertEquals('Hoàn thành', $request->getStatusLabel());
    }

    public function testGetStatusLabelReturnsUnknownForInvalid(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = 'invalid';
        $this->assertEquals('Không xác định', $request->getStatusLabel());
    }

    // ─── Type Methods ─────────────────────────────────────────────

    public function testGetTypeLabelReturnsCorrectLabels(): void
    {
        $request = new ReturnRequest();
        
        $request->loai = ReturnRequest::TYPE_RETURN;
        $this->assertEquals('Trả hàng', $request->getTypeLabel());

        $request->loai = ReturnRequest::TYPE_EXCHANGE;
        $this->assertEquals('Đổi hàng', $request->getTypeLabel());
    }

    public function testGetTypeLabelReturnsUnknownForInvalid(): void
    {
        $request = new ReturnRequest();
        $request->loai = 'invalid';
        $this->assertEquals('Không xác định', $request->getTypeLabel());
    }

    // ─── Approval Logic ───────────────────────────────────────────

    public function testCanBeApprovedWhenPending(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_PENDING;
        $this->assertTrue($request->canBeApproved());
    }

    public function testCannotBeApprovedWhenApproved(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_APPROVED;
        $this->assertFalse($request->canBeApproved());
    }

    public function testCanBeRejectedWhenPending(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_PENDING;
        $this->assertTrue($request->canBeRejected());
    }

    public function testCannotBeRejectedWhenApproved(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_APPROVED;
        $this->assertFalse($request->canBeRejected());
    }

    // ─── Completion Logic ─────────────────────────────────────────

    public function testIsCompletedWhenCompleted(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_COMPLETED;
        $this->assertTrue($request->isCompleted());
    }

    public function testIsNotCompletedWhenPending(): void
    {
        $request = new ReturnRequest();
        $request->trang_thai = ReturnRequest::STATUS_PENDING;
        $this->assertFalse($request->isCompleted());
    }

    // ─── Static Methods Exist ─────────────────────────────────────

    public function testCreateRequestMethodExists(): void
    {
        $this->assertTrue(method_exists(ReturnRequest::class, 'createRequest'));
    }

    public function testGetByUserMethodExists(): void
    {
        $this->assertTrue(method_exists(ReturnRequest::class, 'getByUser'));
    }

    public function testGetAllRequestsMethodExists(): void
    {
        $this->assertTrue(method_exists(ReturnRequest::class, 'getAllRequests'));
    }

    public function testGetRequestByIdMethodExists(): void
    {
        $this->assertTrue(method_exists(ReturnRequest::class, 'getRequestById'));
    }

    public function testUpdateStatusMethodExists(): void
    {
        $this->assertTrue(method_exists(ReturnRequest::class, 'updateStatus'));
    }
}
