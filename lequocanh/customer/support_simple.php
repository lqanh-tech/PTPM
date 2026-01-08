<?php

require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();

$isLoggedIn = isset($_SESSION['USER']);
$userId = $_SESSION['USER'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỗ Trợ Khách Hàng - Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 30px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Hỗ Trợ Khách Hàng</h1>
        
        <div class="test-box">
            <h3>1. Session Status</h3>
            <p class="<?php echo $isLoggedIn ? 'success' : 'warning'; ?>">
                <?php if ($isLoggedIn): ?>
                    ✓ Đã đăng nhập: <?php echo htmlspecialchars($userId); ?>
                <?php else: ?>
                    ⚠ Chưa đăng nhập (test mode)
                <?php endif; ?>
            </p>
            <?php if (!$isLoggedIn): ?>
                <a href="../index.php?req=login" class="btn btn-primary">Đăng nhập</a>
            <?php endif; ?>
        </div>
        
        <div class="test-box">
            <h3>2. BASE_URL Check</h3>
            <p><strong>PHP BASE_URL:</strong> <code><?php echo defined('BASE_URL') ? BASE_URL : 'NOT DEFINED'; ?></code></p>
            <p id="jsBaseUrl" class="info">Checking JavaScript...</p>
        </div>
        
        <div class="test-box">
            <h3>3. API Test</h3>
            <button onclick="testAPI()" class="btn btn-primary">Test API Call</button>
            <pre id="apiResult">Click button to test</pre>
        </div>
        
        <div class="test-box">
            <h3>4. Load Tickets</h3>
            <button onclick="loadTickets()" class="btn btn-success">Load Tickets</button>
            <pre id="ticketsResult">Click button to load</pre>
        </div>
        
        <div class="test-box">
            <h3>5. Console Logs</h3>
            <p>Mở Console (F12) để xem logs chi tiết</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        window.BASE_URL = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : 'http:
        console.log('BASE_URL injected:', window.BASE_URL);
        
        const jsBaseUrlEl = document.getElementById('jsBaseUrl');
        if (window.BASE_URL) {
            jsBaseUrlEl.innerHTML = `<span class="success">✓ window.BASE_URL = <code>${window.BASE_URL}</code></span>`;
        } else {
            jsBaseUrlEl.innerHTML = '<span class="error">✗ window.BASE_URL is undefined</span>';
        }
        
        const getApiUrl = (path) => {
            const url = window.BASE_URL 
                ? `${window.BASE_URL}/lequocanh/api/${path}`
                : `../api/${path}`;
            console.log('getApiUrl:', path, '->', url);
            return url;
        };
        
        async function testAPI() {
            const resultEl = document.getElementById('apiResult');
            resultEl.textContent = 'Testing...';
            
            try {
                const url = getApiUrl('support_tickets.php?action=user_list');
                console.log('Testing API:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    resultEl.textContent = `Error parsing JSON:\n${text.substring(0, 500)}`;
                    return;
                }
                
                resultEl.textContent = JSON.stringify(json, null, 2);
            } catch (error) {
                console.error('API test error:', error);
                resultEl.textContent = `Error: ${error.message}`;
            }
        }
        
        async function loadTickets() {
            const resultEl = document.getElementById('ticketsResult');
            resultEl.textContent = 'Loading...';
            
            try {
                const response = await fetch(getApiUrl('support_tickets.php?action=user_list'));
                const result = await response.json();
                
                console.log('Load tickets result:', result);
                
                if (!result.success) {
                    resultEl.textContent = `Error: ${result.error}`;
                    return;
                }
                
                const tickets = result.data.tickets;
                resultEl.textContent = `Found ${tickets.length} tickets:\n` + 
                                      JSON.stringify(tickets, null, 2);
            } catch (error) {
                console.error('Load tickets error:', error);
                resultEl.textContent = `Error: ${error.message}`;
            }
        }
        
        console.log('=== AUTO TEST ===');
        console.log('window.BASE_URL:', window.BASE_URL);
        console.log('Test API URL:', getApiUrl('support_tickets.php?action=user_list'));
    </script>
</body>
</html>
