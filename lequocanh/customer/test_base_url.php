<?php
/**
 * Test BASE_URL Injection
 */
require_once '../administrator/elements_LQA/mod/sessionManager.php';
SessionManager::start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test BASE_URL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🧪 Test BASE_URL Injection</h1>
    
    <div class="test-box">
        <h3>1. PHP BASE_URL Constant</h3>
        <p><strong>Value:</strong> <code><?php echo defined('BASE_URL') ? BASE_URL : 'NOT DEFINED'; ?></code></p>
        <p class="<?php echo defined('BASE_URL') ? 'success' : 'error'; ?>">
            <?php echo defined('BASE_URL') ? '✓ BASE_URL is defined' : '✗ BASE_URL is NOT defined'; ?>
        </p>
    </div>
    
    <div class="test-box">
        <h3>2. JavaScript window.BASE_URL</h3>
        <p id="jsBaseUrl" class="info">Checking...</p>
    </div>
    
    <div class="test-box">
        <h3>3. Test getApiUrl() Function</h3>
        <pre id="apiUrlTest">Testing...</pre>
    </div>
    
    <div class="test-box">
        <h3>4. Test API Call</h3>
        <button onclick="testApiCall()" style="padding: 10px 20px; cursor: pointer;">Test API Call</button>
        <pre id="apiCallResult">Click button to test</pre>
    </div>
    
    <div class="test-box">
        <h3>5. Current Page URL</h3>
        <p><code id="currentUrl"></code></p>
    </div>
    
    <script>
        // Inject BASE_URL from PHP to JavaScript
        window.BASE_URL = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : 'http://localhost:20080', '/'); ?>';
        
        // Test 2: Check window.BASE_URL
        const jsBaseUrlEl = document.getElementById('jsBaseUrl');
        if (window.BASE_URL) {
            jsBaseUrlEl.innerHTML = `<span class="success">✓ window.BASE_URL = <code>${window.BASE_URL}</code></span>`;
        } else {
            jsBaseUrlEl.innerHTML = '<span class="error">✗ window.BASE_URL is undefined</span>';
        }
        
        // Test 3: getApiUrl function
        const getApiUrl = (path) => {
            const url = window.BASE_URL 
                ? `${window.BASE_URL}/lequocanh/api/${path}`
                : `../api/${path}`;
            return url;
        };
        
        const apiUrlTestEl = document.getElementById('apiUrlTest');
        const testPath = 'support_tickets.php?action=user_list';
        const generatedUrl = getApiUrl(testPath);
        apiUrlTestEl.textContent = `Input: ${testPath}\nOutput: ${generatedUrl}`;
        
        // Test 4: API call
        async function testApiCall() {
            const resultEl = document.getElementById('apiCallResult');
            resultEl.textContent = 'Testing...';
            
            try {
                const url = getApiUrl('support_tickets.php?action=user_list');
                console.log('Testing API call to:', url);
                
                const response = await fetch(url);
                const text = await response.text();
                
                let json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    resultEl.textContent = `Error parsing JSON:\n${text.substring(0, 500)}`;
                    return;
                }
                
                resultEl.textContent = `Status: ${response.status}\n` +
                                      `Success: ${json.success}\n` +
                                      `Response:\n${JSON.stringify(json, null, 2)}`;
            } catch (error) {
                resultEl.textContent = `Error: ${error.message}`;
            }
        }
        
        // Test 5: Current URL
        document.getElementById('currentUrl').textContent = window.location.href;
        
        // Log everything to console
        console.log('=== BASE_URL TEST ===');
        console.log('window.BASE_URL:', window.BASE_URL);
        console.log('Current URL:', window.location.href);
        console.log('Test API URL:', getApiUrl('support_tickets.php?action=user_list'));
    </script>
</body>
</html>
