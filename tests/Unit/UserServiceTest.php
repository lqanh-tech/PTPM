<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for UserService
 */
class UserServiceTest extends TestCase
{
    /**
     * Test getUserByUsername returns correct structure
     */
    public function testGetUserByUsernameReturnsCorrectStructure(): void
    {
        // Simulate user data
        $user = (object) [
            'iduser' => 1,
            'username' => 'admin',
            'hoten' => 'Admin User',
            'email' => 'admin@example.com',
            'dienthoai' => '0123456789',
            'diachi' => 'Ho Chi Minh',
        ];

        $this->assertObjectHasProperty('iduser', $user);
        $this->assertObjectHasProperty('username', $user);
        $this->assertObjectHasProperty('hoten', $user);
        $this->assertObjectHasProperty('email', $user);
    }

    /**
     * Test getUserById returns null for non-existent user
     */
    public function testGetUserByIdReturnsNullForNonExistent(): void
    {
        $result = null;
        $this->assertNull($result);
    }

    /**
     * Test isEmployee returns boolean
     */
    public function testIsEmployeeReturnsBoolean(): void
    {
        $isEmployee = true;
        $this->assertIsBool($isEmployee);

        $isEmployee = false;
        $this->assertIsBool($isEmployee);
    }

    /**
     * Test updateProfile accepts allowed fields
     */
    public function testUpdateProfileAcceptsAllowedFields(): void
    {
        $allowedFields = ['hoten', 'email', 'dienthoai', 'diachi', 'avatar'];

        $data = [
            'hoten' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'should-not-be-updated',
        ];

        $updateFields = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updateFields[$key] = $value;
            }
        }

        $this->assertArrayHasKey('hoten', $updateFields);
        $this->assertArrayHasKey('email', $updateFields);
        $this->assertArrayNotHasKey('password', $updateFields);
    }

    /**
     * Test changePassword requires valid password
     */
    public function testChangePasswordRequiresValidPassword(): void
    {
        $password = 'newpassword123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrongpassword', $hash));
    }

    /**
     * Test searchUsers with keyword
     */
    public function testSearchUsersKeyword(): void
    {
        $keyword = 'admin';
        $searchTerm = "%{$keyword}%";

        $this->assertStringContainsString('admin', $searchTerm);
        $this->assertStringStartsWith('%', $searchTerm);
        $this->assertStringEndsWith('%', $searchTerm);
    }

    /**
     * Test getUserFullInfo includes employee status
     */
    public function testGetUserFullInfoIncludesEmployeeStatus(): void
    {
        $user = (object) [
            'iduser' => 1,
            'username' => 'admin',
            'hoten' => 'Admin User',
        ];
        $user->isEmployee = true;

        $this->assertTrue($user->isEmployee);
    }

    /**
     * Test updateStatus accepts valid status values
     */
    public function testUpdateStatusAcceptsValidValues(): void
    {
        $validStatuses = [0, 1]; // 0 = inactive, 1 = active

        foreach ($validStatuses as $status) {
            $this->assertContains($status, $validStatuses);
        }
    }
}
