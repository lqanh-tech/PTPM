<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/csrfProtection.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        CSRFProtection::requireValidToken($_POST);
        
        $message = "Form submitted successfully with valid CSRF token!";
        $messageType = "success";
        
        Logger::info("Form submitted with valid CSRF token", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        $message = "CSRF token validation failed: " . $e->getMessage();
        $messageType = "error";
        
        Logger::warning("CSRF token validation failed", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'error' => $e->getMessage()
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Protection Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .code-example { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
    <?php echo CSRFProtection::getMetaTag(); ?>
</head>
<body>
    <h1>CSRF Protection Example</h1>
    
    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <h2>Protected Form Example</h2>
        <form method="POST" action="">
            <?php echo CSRFProtection::getHiddenField(); ?>
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="4" required></textarea>
            </div>
            
            <button type="submit">Submit Protected Form</button>
        </form>
    </div>
    
    <div class="code-example">
        <h3>How to Use CSRF Protection</h3>
        
        <h4>1. Include CSRF Protection in Forms:</h4>
        <pre><code>&lt;?php echo CSRFProtection::getHiddenField(); ?&gt;</code></pre>
        
        <h4>2. Validate CSRF Token in Processing:</h4>
        <pre><code>try {
    CSRFProtection::requireValidToken($_POST);

} catch (Exception $e) {

}</code></pre>
        
        <h4>3. For AJAX Requests:</h4>
        <pre><code>&lt;?php echo CSRFProtection::getMetaTag(); ?&gt;
&lt;?php echo CSRFProtection::getAjaxScript(); ?&gt;</code></pre>
        
        <h4>4. Manual Token Validation:</h4>
        <pre><code>$token = $_POST['csrf_token'] ?? '';
if (CSRFProtection::validateToken($token)) {

} else {

}</code></pre>
    </div>
    
    <?php echo CSRFProtection::getAjaxScript(); ?>
</body>
</html>