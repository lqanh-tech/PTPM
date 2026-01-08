<?php

class AlertSystem {

    private static $alertRules = [];
    private static $triggeredAlerts = [];

    public static function defineRule($ruleName, $ruleConfig) {
        self::$alertRules[$ruleName] = $ruleConfig;
    }

    public static function evaluateRules($metrics, $errors) {
        foreach (self::$alertRules as $ruleName => $ruleConfig) {
            $alertTriggered = false;

            switch ($ruleConfig['type']) {
                case 'metric_threshold':
                    if (isset($metrics[$ruleConfig['metric']])) {
                        $value = $metrics[$ruleConfig['metric']];
                        $threshold = $ruleConfig['threshold'];
                        $operator = $ruleConfig['operator'];

                        if (($operator === '>' && $value > $threshold) ||
                            ($operator === '<' && $value < $threshold) ||
                            ($operator === '>=' && $value >= $threshold) ||
                            ($operator === '<=' && $value <= $threshold) ||
                            ($operator === '==' && $value == $threshold)) {
                            $alertTriggered = true;
                        }
                    }
                    break;
                case 'error_rate':
                    $errorCount = count($errors);
                    if ($errorCount > $ruleConfig['threshold']) {
                        $alertTriggered = true;
                    }
                    break;
                case 'custom':

                    if (isset($ruleConfig['condition']) && $ruleConfig['condition'] === true) {
                        $alertTriggered = true;
                    }
                    break;
            }

            if ($alertTriggered) {
                self::triggerAlert($ruleName, $ruleConfig);
            }
        }
    }

    public static function triggerAlert($ruleName, $ruleConfig) {
        $alert = [
            'rule_name' => $ruleName,
            'timestamp' => date('Y-m-d H:i:s'),
            'config' => $ruleConfig,
            'status' => 'triggered'
        ];
        self::$triggeredAlerts[] = $alert;

        $logMessage = "ALERT TRIGGERED: Rule '" . $ruleName . "' at " . $alert['timestamp'] . "\n";
        $logMessage .= "  Config: " . json_encode($ruleConfig) . "\n";
        file_put_contents('logs/alerts.log', $logMessage, FILE_APPEND);

    }

    public static function getTriggeredAlerts() {
        return self::$triggeredAlerts;
    }

    public static function clearTriggeredAlerts() {
        self::$triggeredAlerts = [];
    }
}
?>