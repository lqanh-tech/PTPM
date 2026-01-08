<?php

require_once __DIR__ . '/../mod/securityMiddleware.php';

SecurityMiddleware::init();

function exampleFormWithCSRF() {
    ?>
    <form method="POST" action="process_form.php">
        <?php echo SecurityMiddleware::getCsrfField(); ?>
        
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit">Submit</button>
    </form>
    
    <?php echo SecurityMiddleware::getSecurityScript(); ?>
    <?php
}

function exampleProcessForm() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $rules = [
            'username' => 'required|min_length:3|max_length:50|alpha_numeric',
            'email' => 'required|email',
            'password' => 'required|min_length:8',
            'phone' => 'phone',
            'age' => 'integer|min_value:18|max_value:120'
        ];
        
        $validation = SecurityMiddleware::validatePostRequest($_POST, $rules, true);
        
        if ($validation['valid']) {

            $sanitizedData = $validation['sanitized_data'];
            
            echo "Form processed successfully!";
            
            if (class_exists('Logger')) {
                Logger::info("Form processed", ['user' => $sanitizedData['username']]);
            }
        } else {

            foreach ($validation['errors'] as $field => $errors) {
                foreach ($errors as $error) {
                    echo "<div class='error'>$error</div>";
                }
            }
        }
    }
}

function exampleFileUpload() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {

        if (!CSRFProtection::validateRequest($_POST)) {
            die("CSRF validation failed");
        }
        
        $fileValidation = SecurityMiddleware::validateFileUpload($_FILES['upload'], [
            'max_size' => 2 * 1024 * 1024,
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
        ]);
        
        if ($fileValidation['valid']) {
            $fileInfo = $fileValidation['file_info'];
            
            $safeFilename = uniqid() . '.' . $fileInfo['extension'];
            $uploadPath = '/uploads/' . $safeFilename;
            
            if (move_uploaded_file($fileInfo['tmp_name'], $uploadPath)) {
                echo "File uploaded successfully: " . $safeFilename;
                
                if (class_exists('Logger')) {
                    Logger::info("File uploaded", [
                        'filename' => $safeFilename,
                        'original_name' => $fileInfo['original_name'],
                        'size' => $fileInfo['size']
                    ]);
                }
            } else {
                echo "Failed to move uploaded file";
            }
        } else {
            foreach ($fileValidation['errors'] as $error) {
                echo "<div class='error'>$error</div>";
            }
        }
    }
    
    ?>
    <form method="POST" enctype="multipart/form-data">
        <?php echo SecurityMiddleware::getCsrfField(); ?>

function exampleAjaxWithCSRF() {
    ?>
    <script>

    var csrfToken = '<?php echo SecurityMiddleware::getCsrfToken(); ?>';
    
    function makeSecureAjaxRequest() {
        $.ajax({
            url: 'ajax_endpoint.php',
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            data: {
                action: 'update_data',
                value: 'some_value'
            },
            success: function(response) {
                console.log('Success:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
    </script>
    <?php
}

function exampleAuthCheck() {

    if (!SecurityMiddleware::checkAuth()) {

        header('Location: /login.php');
        exit();
    }
    
    if (!SecurityMiddleware::checkAuth(['admin', 'manager'])) {

        http_response_code(403);
        echo "Access denied";
        exit();
    }
    
    echo "Welcome, admin!";
}

function exampleRateLimit() {
    $userIP = $_SERVER['REMOTE_ADDR'];
    
    if (!SecurityMiddleware::checkRateLimit("login_$userIP", 5, 300)) {
        http_response_code(429);
        echo "Too many login attempts. Please try again later.";
        exit();
    }
    
    echo "Login form";
}

function exampleInputSanitization() {
    $rawData = [
        'name' => '<script>alert("xss")</script>John Doe',
        'email' => 'john@example.com',
        'age' => '25abc',
        'website' => 'http://example.com'
    ];
    
    $sanitizationRules = [
        'name' => 'html',
        'email' => 'email',
        'age' => 'integer',
        'website' => 'url'
    ];
    
    $sanitizedData = InputValidator::sanitize($rawData, $sanitizationRules);
    
    print_r($sanitizedData);

}

function exampleCustomValidation() {
    $data = [
        'username' => 'john_doe',
        'password' => 'mypassword123',
        'confirm_password' => 'mypassword123',
        'terms' => '1'
    ];
    
    $rules = [
        'username' => 'required|min_length:3|max_length:20|regex:/^[a-zA-Z0-9_]+$/',
        'password' => 'required|min_length:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        'terms' => 'required|in:1'
    ];
    
    $validation = InputValidator::quickValidate($data, $rules);
    
    if ($validation['valid']) {
        echo "Validation passed!";
    } else {
        echo "Validation failed: " . $validation['first_error'];
    }
}
