<?php
require_once 'securityMonitor.php';
require_once 'intrusionDetector.php';

class ThreatAnalyzer {

    public static function analyzeEvent($event) {
        $analysis = [
            'category' => 'Unknown',
            'severity' => 'Low',
            'suggested_action' => 'Monitor and investigate.'
        ];

        switch ($event['event_type']) {
            case 'Failed Login':
                $analysis['category'] = 'Authentication';
                if (isset($event['details']['username']) && IntrusionDetector::isSuspiciousIp($event['ip_address'])) {
                    $analysis['severity'] = 'High';
                    $analysis['suggested_action'] = 'Block IP address and investigate user account: ' . $event['details']['username'];
                } else if (isset($event['details']['username'])) {
                    $analysis['severity'] = 'Medium';
                    $analysis['suggested_action'] = 'Monitor user: ' . $event['details']['username'] . ' for further attempts.';
                } else {
                    $analysis['severity'] = 'Low';
                    $analysis['suggested_action'] = 'Monitor IP: ' . $event['ip_address'] . ' for further attempts.';
                }
                break;
            case 'CSRF Attack Attempt':
                $analysis['category'] = 'Web Vulnerability';
                $analysis['severity'] = 'Critical';
                $analysis['suggested_action'] = 'Review CSRF protection implementation and investigate source IP: ' . $event['ip_address'];
                break;
            case 'Suspicious User Activity':
                $analysis['category'] = 'User Behavior';
                $analysis['severity'] = 'Medium';
                $analysis['suggested_action'] = 'Investigate user behavior and activity logs for user ID: ' . ($event['details']['user_id'] ?? 'Unknown');
                break;
            case 'Suspicious IP Detected':
                $analysis['category'] = 'Network Intrusion';
                $analysis['severity'] = 'High';
                $analysis['suggested_action'] = 'Immediately block IP address: ' . $event['ip_address'] . ' and review all activity from this source.';
                break;
            case 'Unusual Traffic Pattern':
                $analysis['category'] = 'DDoS/Abuse';
                $analysis['severity'] = 'High';
                $analysis['suggested_action'] = 'Implement rate limiting and consider WAF rules for IP: ' . $event['ip_address'];
                break;
            case 'File Access':
                $analysis['category'] = 'File System';
                $analysis['severity'] = 'Medium';
                $analysis['suggested_action'] = 'Verify file permissions and integrity of file: ' . $event['details']['file_path'];
                break;
        }
        
        return $analysis;
    }

    public static function analyzeMultipleEvents($events) {
        $threats = [];
        $failedLoginIps = [];
        $csrfAttempts = 0;

        foreach ($events as $event) {
            $analysis = self::analyzeEvent($event);
            
            if ($analysis['event_type'] === 'Failed Login' && isset($event['details']['username'])) {
                if (!isset($failedLoginIps[$event['details']['username']])) {
                    $failedLoginIps[$event['details']['username']] = [];
                }
                if (!in_array($event['ip_address'], $failedLoginIps[$event['details']['username']])) {
                    $failedLoginIps[$event['details']['username']][] = $event['ip_address'];
                }
            }

            if ($analysis['event_type'] === 'CSRF Attack Attempt') {
                $csrfAttempts++;
            }
        }

        foreach ($failedLoginIps as $username => $ips) {
            if (count($ips) > 3) {
                $threats[] = [
                    'type' => 'Credential Stuffing',
                    'severity' => 'High',
                    'description' => "Multiple failed login attempts for user '{$username}' from different IP addresses.",
                    'suggested_action' => "Force password reset for '{$username}' and implement stronger brute-force protection."
                ];
            }
        }

        if ($csrfAttempts > 5) {
            $threats[] = [
                'type' => 'Persistent CSRF Attacks',
                'severity' => 'Critical',
                'description' => "High volume of CSRF attack attempts detected.",
                'suggested_action' => "Immediately review and enhance CSRF token validation and consider WAF rules."
            ];
        }

        return $threats;
    }
}
?>