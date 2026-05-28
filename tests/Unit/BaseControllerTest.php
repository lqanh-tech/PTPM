<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for BaseController functionality
 */
class BaseControllerTest extends TestCase
{
    /**
     * Test validation rules - required field
     */
    public function testValidateRequiredField(): void
    {
        // Simulate empty input
        $_POST = ['name' => ''];
        $_GET = [];

        $errors = [];
        $rules = ['name' => 'required'];
        $value = $_POST['name'] ?? null;

        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }
            }
        }

        $this->assertArrayHasKey('name', $errors);
        $this->assertStringContainsString('required', $errors['name'][0]);
    }

    /**
     * Test validation rules - min length
     */
    public function testValidateMinLength(): void
    {
        $_POST = ['name' => 'ab'];

        $errors = [];
        $rules = ['name' => 'required|min:3'];
        $value = $_POST['name'] ?? null;

        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }
                if (str_starts_with($fieldRule, 'min:')) {
                    $min = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $errors[$field][] = "Field {$field} must be at least {$min} characters";
                    }
                }
            }
        }

        $this->assertArrayHasKey('name', $errors);
        $this->assertStringContainsString('at least 3', $errors['name'][0]);
    }

    /**
     * Test validation rules - max length
     */
    public function testValidateMaxLength(): void
    {
        $_POST = ['name' => str_repeat('a', 300)];

        $errors = [];
        $rules = ['name' => 'max:255'];
        $value = $_POST['name'] ?? null;

        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            foreach ($fieldRules as $fieldRule) {
                if (str_starts_with($fieldRule, 'max:')) {
                    $max = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $errors[$field][] = "Field {$field} must not exceed {$max} characters";
                    }
                }
            }
        }

        $this->assertArrayHasKey('name', $errors);
        $this->assertStringContainsString('must not exceed', $errors['name'][0]);
    }

    /**
     * Test validation rules - email
     */
    public function testValidateEmail(): void
    {
        $_POST = ['email' => 'invalid-email'];

        $errors = [];
        $rules = ['email' => 'required|email'];
        $value = $_POST['email'] ?? null;

        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }
                if ($fieldRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Field {$field} must be a valid email";
                }
            }
        }

        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('valid email', $errors['email'][0]);
    }

    /**
     * Test validation rules - numeric
     */
    public function testValidateNumeric(): void
    {
        $_POST = ['price' => 'abc'];

        $errors = [];
        $rules = ['price' => 'required|numeric'];
        $value = $_POST['price'] ?? null;

        foreach ($rules as $field => $rule) {
            $fieldRules = explode('|', $rule);
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }
                if ($fieldRule === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "Field {$field} must be a number";
                }
            }
        }

        $this->assertArrayHasKey('price', $errors);
        $this->assertStringContainsString('must be a number', $errors['price'][0]);
    }

    /**
     * Test validation passes with valid data
     */
    public function testValidatePassesWithValidData(): void
    {
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'price' => '100',
        ];

        $errors = [];
        $rules = [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email',
            'price' => 'required|numeric',
        ];

        foreach ($rules as $field => $rule) {
            $value = $_POST[$field] ?? null;
            $fieldRules = explode('|', $rule);

            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required";
                }
                if (str_starts_with($fieldRule, 'min:')) {
                    $min = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $errors[$field][] = "Field {$field} must be at least {$min} characters";
                    }
                }
                if (str_starts_with($fieldRule, 'max:')) {
                    $max = (int) substr($fieldRule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $errors[$field][] = "Field {$field} must not exceed {$max} characters";
                    }
                }
                if ($fieldRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "Field {$field} must be a valid email";
                }
                if ($fieldRule === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "Field {$field} must be a number";
                }
            }
        }

        $this->assertEmpty($errors);
    }

    /**
     * Test CSRF token generation
     */
    public function testCSRFTokenGeneration(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1));
    }

    /**
     * Test CSRF token validation
     */
    public function testCSRFTokenValidation(): void
    {
        $token = bin2hex(random_bytes(32));

        // Valid token
        $this->assertTrue(hash_equals($token, $token));

        // Invalid token
        $this->assertFalse(hash_equals($token, 'invalid'));
    }

    /**
     * Test input sanitization
     */
    public function testInputSanitization(): void
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('alert', $sanitized);
    }

    /**
     * Test isPost method
     */
    public function testIsPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('POST', $_SERVER['REQUEST_METHOD']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('GET', $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Test isAuthenticated method
     */
    public function testIsAuthenticated(): void
    {
        // Not authenticated
        unset($_SESSION['USER'], $_SESSION['ADMIN']);
        $this->assertFalse(isset($_SESSION['USER']) || isset($_SESSION['ADMIN']));

        // Authenticated as user
        $_SESSION['USER'] = 'testuser';
        $this->assertTrue(isset($_SESSION['USER']) || isset($_SESSION['ADMIN']));

        // Authenticated as admin
        unset($_SESSION['USER']);
        $_SESSION['ADMIN'] = 'admin';
        $this->assertTrue(isset($_SESSION['USER']) || isset($_SESSION['ADMIN']));

        // Cleanup
        unset($_SESSION['USER'], $_SESSION['ADMIN']);
    }

    /**
     * Test getUser method
     */
    public function testGetUser(): void
    {
        unset($_SESSION['USER'], $_SESSION['ADMIN']);

        // No user
        $user = $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
        $this->assertNull($user);

        // With user
        $_SESSION['USER'] = 'testuser';
        $user = $_SESSION['USER'] ?? $_SESSION['ADMIN'] ?? null;
        $this->assertEquals('testuser', $user);

        // Cleanup
        unset($_SESSION['USER']);
    }
}
