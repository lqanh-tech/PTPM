<?php
declare(strict_types=1);

namespace App\Services;

class ReturnAutomationService
{
    private ReturnDecisionEngine $decisionEngine;
    
    public function __construct()
    {
        $this->decisionEngine = ReturnDecisionEngine::fromConfig();
    }
    
    /**
     * Process return request automatically
     */
    public function processReturn(array $returnRequest): array
    {
        // 1. Check eligibility
        $eligibility = $this->checkEligibility($returnRequest);
        if (!$eligibility['eligible']) {
            return ['success' => false, 'message' => $eligibility['reason']];
        }
        
        // 2. Auto-approve if eligible
        $autoApproved = $this->shouldAutoApprove($returnRequest);
        
        // 3. Decide return method
        $decision = $this->decisionEngine->decide($returnRequest);
        
        // 4. Save to database
        $returnId = $this->saveReturn($returnRequest, $decision, $autoApproved);
        
        // 5. Send notifications
        $this->sendNotifications($returnRequest, $decision, $autoApproved);
        
        return [
            'success' => true,
            'return_id' => $returnId,
            'auto_approved' => $autoApproved,
            'method' => $decision['method'],
            'reason' => $decision['reason'],
            'estimated_time' => $decision['estimated_time'],
            'cost' => $decision['cost'],
            'pickup_date' => $decision['pickup_date'] ?? null,
            'drop_off_locations' => $decision['locations'] ?? [],
        ];
    }
    
    /**
     * Check return eligibility
     */
    private function checkEligibility(array $request): array
    {
        $config = require __DIR__ . '/../../config/return_policy.php';
        
        // Check order status
        if (!in_array($request['order_status'] ?? '', $config['eligible_statuses'])) {
            return ['eligible' => false, 'reason' => 'Đơn hàng không đủ điều kiện đổi trả'];
        }
        
        // Check return window
        if (!empty($request['order_date'])) {
            $orderDate = new \DateTime($request['order_date']);
            $now = new \DateTime();
            $diff = $now->diff($orderDate)->days;
            
            if ($diff > $config['return_window_days']) {
                return ['eligible' => false, 'reason' => 'Đã quá thời hạn đổi trả (' . $config['return_window_days'] . ' ngày)'];
            }
        }
        
        // Check payment method
        if (!empty($request['payment_method']) && 
            !in_array($request['payment_method'], $config['eligible_payment_methods'])) {
            return ['eligible' => false, 'reason' => 'Phương thức thanh toán không hỗ trợ đổi trả'];
        }
        
        return ['eligible' => true, 'reason' => ''];
    }
    
    /**
     * Check if should auto-approve
     */
    private function shouldAutoApprove(array $request): bool
    {
        $config = require __DIR__ . '/../../config/return_policy.php';
        
        if (!$config['auto_approve']['enabled']) {
            return false;
        }
        
        return ($request['order_total'] ?? 0) <= $config['auto_approve']['max_amount']
            && ($request['item_count'] ?? 1) <= $config['auto_approve']['max_items'];
    }
    
    /**
     * Save return to database
     */
    private function saveReturn(array $request, array $decision, bool $autoApproved): int
    {
        $db = \Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO doi_tra (ma_don_hang, ma_nguoi_dung, ly_do, loai, return_method, trang_thai, auto_approved, decision_factors)
            VALUES (?, ?, ?, 'return', ?, ?, ?, ?)
        ");
        
        $status = $autoApproved ? 'approved' : 'pending';
        $factors = json_encode($decision);
        
        $stmt->execute([
            $request['order_id'] ?? 0,
            $request['user_id'] ?? '',
            $request['reason'] ?? '',
            $decision['method'],
            $status,
            $autoApproved ? 1 : 0,
            $factors,
        ]);
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Send notifications
     */
    private function sendNotifications(array $request, array $decision, bool $autoApproved): void
    {
        // Build notification content
        $methodText = match($decision['method']) {
            'pickup' => 'Chúng tôi sẽ gửi đơn vị vận chuyển đến lấy hàng vào ngày ' . ($decision['pickup_date'] ?? ''),
            'drop_off' => 'Vui lòng mang hàng đến bưu cục gần nhất',
            'self_ship' => 'Vui lòng tự gửi hàng trả đến địa chỉ của chúng tôi',
            default => '',
        };
        
        $statusText = $autoApproved ? 'đã được chấp nhận' : 'đang được xem xét';
        
        // Log notification
        Logger::info('Return notification', ['order_id' => $request['order_id'], 'status' => $statusText, 'method' => $methodText]);
    }
    
    /**
     * Get return by ID
     */
    public function getReturnById(int $id): ?array
    {
        try {
            $db = \Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien
                FROM doi_tra dt
                JOIN don_hang dh ON dt.ma_don_hang = dh.id
                WHERE dt.id = ?
            ");
            $stmt->execute([$id]);
            
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            Logger::error('ReturnAutomationService::getReturnById', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get returns by user
     */
    public function getReturnsByUser(string $userId): array
    {
        try {
            $db = \Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                SELECT dt.*, dh.ma_don_hang_text, dh.tong_tien
                FROM doi_tra dt
                JOIN don_hang dh ON dt.ma_don_hang = dh.id
                WHERE dt.ma_nguoi_dung = ?
                ORDER BY dt.ngay_tao DESC
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Logger::error('ReturnAutomationService::getReturnsByUser', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        return new self();
    }
}