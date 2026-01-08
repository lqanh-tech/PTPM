<?php

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/inputValidator.php';
require_once __DIR__ . '/../config/logger_config.php';

SessionManager::start();

$errors = [];
$success = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'age' => $_POST['age'] ?? '',
        'website' => $_POST['website'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'bio' => $_POST['bio'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'terms' => $_POST['terms'] ?? ''
    ];
    
    $rules = [
        'name' => 'required|min_length:2|max_length:50|alpha',
        'email' => 'required|email',
        'phone' => 'required|phone',
        'age' => 'required|integer|min_value:18|max_value:100',
        'website' => 'url',
        'password' => 'required|min_length:8',
        'bio' => 'max_length:500|no_script',
        'gender' => 'required|in:male,female,other',
        'terms' => 'required'
    ];
    
    $validator = new InputValidator();
    if ($validator->validate($formData, $rules)) {

        if ($formData['password'] !== $formData['confirm_password']) {
            $errors['confirm_password'] = ['Passwords do not match'];
        } else {

            $sanitizedData = InputValidator::sanitize($formData, [
                'name' => 'string',
                'email' => 'email',
                'phone' => 'string',
                'age' => 'int',
                'website' => 'url',
                'bio' => 'string',
                'gender' => 'string'
            ]);
            
            $success = true;
            Logger::info("Form validation successful", ['user_data' => $sanitizedData]);
        }
    } else {
        $errors = $validator->getErrors();
        Logger::warning("Form validation failed", ['errors' => $errors]);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Validation Example</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-container { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .field-error { border-color: #dc3545; }
        .error-text { color: #dc3545; font-size: 0.875em; margin-top: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .code-example { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .validation-rules { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Input Validation Example</h1>
    
    <?php if ($success): ?>
        <div class="message success">
            Form submitted successfully! All validation rules passed.
        </div>
    <?php endif; ?>
    
    <div class="validation-rules">
        <h3>Validation Rules Applied:</h3>
        <ul>
            <li><strong>Name:</strong> Required, 2-50 characters, letters only</li>
            <li><strong>Email:</strong> Required, valid email format</li>
            <li><strong>Phone:</strong> Required, valid phone format</li>
            <li><strong>Age:</strong> Required, integer between 18-100</li>
            <li><strong>Website:</strong> Optional, valid URL format</li>
            <li><strong>Password:</strong> Required, minimum 8 characters</li>
            <li><strong>Bio:</strong> Optional, maximum 500 characters, no script tags</li>
            <li><strong>Gender:</strong> Required, must be male/female/other</li>
            <li><strong>Terms:</strong> Required checkbox</li>
        </ul>
    </div>
    
    <div class="form-container">
        <h2>Validation Example Form</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Name *:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                       class="<?php echo isset($errors['name']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['name'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['name']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email *:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                       class="<?php echo isset($errors['email']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['email']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone *:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                       class="<?php echo isset($errors['phone']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['phone']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="age">Age *:</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($formData['age'] ?? ''); ?>"
                       class="<?php echo isset($errors['age']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['age'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['age']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="website">Website:</label>
                <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($formData['website'] ?? ''); ?>"
                       class="<?php echo isset($errors['website']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['website'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['website']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Password *:</label>
                <input type="password" id="password" name="password"
                       class="<?php echo isset($errors['password']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['password'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *:</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       class="<?php echo isset($errors['confirm_password']) ? 'field-error' : ''; ?>">
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['confirm_password']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender *:</label>
                <select id="gender" name="gender" class="<?php echo isset($errors['gender']) ? 'field-error' : ''; ?>">
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo ($formData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($formData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo ($formData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
                <?php if (isset($errors['gender'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['gender']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" rows="4" 
                          class="<?php echo isset($errors['bio']) ? 'field-error' : ''; ?>"><?php echo htmlspecialchars($formData['bio'] ?? ''); ?></textarea>
                <?php if (isset($errors['bio'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['bio']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="terms" value="1" <?php echo ($formData['terms'] ?? '') ? 'checked' : ''; ?>>
                    I agree to the terms and conditions *
                </label>
                <?php if (isset($errors['terms'])): ?>
                    <div class="error-text"><?php echo implode(', ', $errors['terms']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit">Submit Form</button>
        </form>
    </div>
    
    <div class="code-example">
        <h3>How to Use Input Validation</h3>
        
        <h4>1. Define Validation Rules:</h4>
        <pre><code>$rules = [
    'name' => 'required|min_length:2|max_length:50|alpha',
    'email' => 'required|email',
    'age' => 'required|integer|min_value:18|max_value:100'
];</code></pre>
        
        <h4>2. Validate Input:</h4>
        <pre><code>$validator = new InputValidator();
if ($validator->validate($data, $rules)) {

} else {
    $errors = $validator->getErrors();
}</code></pre>
        
        <h4>3. Sanitize Data:</h4>
        <pre><code>$sanitized = InputValidator::sanitize($data, [
    'name' => 'string',
    'email' => 'email',
    'age' => 'int'
]);</code></pre>
        
        <h4>4. Quick Validation:</h4>
        <pre><code>$result = InputValidator::quickValidate($data, $rules);
if ($result['valid']) {

} else {
    echo $result['first_error'];
}</code></pre>
    </div>
</body>
</html>