<?php
declare(strict_types=1);

namespace App\Controllers;

class JTWebhookController
{
    public function handle(): array
    {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (!$data) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }
        
        // Log webhook
        $this->logWebhook($payload);
        
        // Process webhook
        try {
            $this->processWebhook($data);
            return ['success' => true, 'message' => 'Processed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function processWebhook(array $data): void
    {
        $trackingNumber = $data['tracking_no'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $statusDesc = $data['status_desc'] ?? '';
        $location = $data['location'] ?? '';
        $eventTime = $data['event_time'] ?? date('Y-m-d H:i:s');
        
        if (empty($trackingNumber)) {
            throw new \InvalidArgumentException('Missing tracking number');
        }
        
        // Save tracking event
        $this->saveTrackingEvent($trackingNumber, $statusCode, $statusDesc, $location, $eventTime);
        
        // Update order status
        $this->updateOrderStatus($trackingNumber, $statusCode);
    }
    
    private function saveTrackingEvent(string $trackingNumber, string $statusCode, string $statusDesc, string $location, string $eventTime): void
    {
        $db = \Database::getInstance()->getConnection();
        
        // Get order ID
        $stmt = $db->prepare("SELECT id FROM don_hang WHERE tracking_number = ?");
        $stmt->execute([$trackingNumber]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$order) {
            return;
        }
        
        // Check if tracking_events table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'tracking_events'");
        if ($checkTable->rowCount() == 0) {
            // Create table if not exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS tracking_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    tracking_number VARCHAR(50) NOT NULL,
                    status_code VARCHAR(50) NOT NULL,
                    status_desc VARCHAR(255) NOT NULL,
                    location VARCHAR(255) NULL,
                    event_time DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_order_id (order_id),
                    INDEX idx_tracking_number (tracking_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        $stmt = $db->prepare("
            INSERT INTO tracking_events (order_id, tracking_number, status_code, status_desc, location, event_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$order['id'], $trackingNumber, $statusCode, $statusDesc, $location, $eventTime]);
    }
    
    private function updateOrderStatus(string $trackingNumber, string $statusCode): void
    {
        $db = \Database::getInstance()->getConnection();
        
        $newStatus = match($statusCode) {
            'PICKUP' => 'approved',
            'IN_TRANSIT' => 'delivered',
            'DELIVERED' => 'completed',
            default => null,
        };
        
        if ($newStatus) {
            $stmt = $db->prepare("UPDATE don_hang SET trang_thai = ? WHERE tracking_number = ?");
            $stmt->execute([$newStatus, $trackingNumber]);
        }
    }
    
    private function logWebhook(string $payload): void
    {
        try {
            $db = \Database::getInstance()->getConnection();
            
            // Check if webhook_logs table exists
            $checkTable = $db->query("SHOW TABLES LIKE 'webhook_logs'");
            if ($checkTable->rowCount() == 0) {
                // Create table if not exists
                $db->exec("
                    CREATE TABLE IF NOT EXISTS webhook_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider VARCHAR(50) NOT NULL,
                        payload TEXT NOT NULL,
                        status VARCHAR(20) DEFAULT 'received',
                        processed_at TIMESTAMP NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_provider (provider)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }
            
            $stmt = $db->prepare("
                INSERT INTO webhook_logs (provider, payload, status)
                VALUES ('jtexpress', ?, 'received')
            ");
            $stmt->execute([$payload]);
        } catch (\Exception $e) {
            Logger::error('Failed to log webhook', ['error' => $e->getMessage()]);
        }
    }
}