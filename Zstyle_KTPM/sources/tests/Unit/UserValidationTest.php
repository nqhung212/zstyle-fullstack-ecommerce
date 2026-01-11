<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test User Validation Functions
 * Kiểm tra các hàm validate user
 */
class UserValidationTest extends TestCase
{
    public function testValidEmailFormat()
    {
        $validEmails = [
            'example@gmail.com',
            'user.name@example.co.uk',
            'user_name@example.com',
            'first.last@example.org'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' phải hợp lệ"
            );
        }
    }

    public function testInvalidEmailFormat()
    {
        $invalidEmails = [
            'invalid-email',
            '@example.com',
            'user@',
            'user name@example.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '$email' phải không hợp lệ"
            );
        }
    }

    public function testPasswordLength()
    {
        $shortPassword = 'short';
        $validPassword = 'password123';
        
        $this->assertLessThan(8, strlen($shortPassword), 'Password quá ngắn');
        $this->assertGreaterThanOrEqual(8, strlen($validPassword), 'Password đủ dài');
    }

    public function testPhoneNumberValidation()
    {
        // Load function từ model/user.php
        require_once __DIR__ . '/../../model/user.php';
        
        $validPhones = [
            '0123456789',
            '0987654321',
            '01234567890'
        ];
        
        foreach ($validPhones as $phone) {
            $this->assertTrue(
                isValidPhoneNumber($phone),
                "Số điện thoại '$phone' phải hợp lệ"
            );
        }
    }

    public function testInvalidPhoneNumber()
    {
        require_once __DIR__ . '/../../model/user.php';
        
        $invalidPhones = [
            '123',           // Quá ngắn
            'abcdefghij',    // Không phải số
            '012345678901',  // Quá dài
        ];
        
        foreach ($invalidPhones as $phone) {
            $this->assertFalse(
                isValidPhoneNumber($phone),
                "Số điện thoại '$phone' phải không hợp lệ"
            );
        }
    }

    public function testSanitizeInput()
    {
        $dangerousInput = '<script>alert("XSS")</script>';
        $sanitized = htmlspecialchars($dangerousInput, ENT_QUOTES, 'UTF-8');
        
        $this->assertStringNotContainsString('<script>', $sanitized, 'Input chưa được sanitize');
        $this->assertStringContainsString('&lt;script&gt;', $sanitized, 'Input phải được escape');
    }

    public function testPasswordHashing()
    {
        $password = 'mySecurePassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertNotEquals($password, $hash, 'Password không được hash');
        $this->assertTrue(password_verify($password, $hash), 'Password hash verification thất bại');
    }

    public function testCreateRandomPassword()
    {
        require_once __DIR__ . '/../../model/user.php';
        
        $password1 = creatpass();
        $password2 = creatpass();
        
        $this->assertEquals(8, strlen($password1), 'Password phải có độ dài 8 ký tự');
        $this->assertNotEquals($password1, $password2, 'Mỗi password phải unique');
    }

    public function testCreateUsername()
    {
        require_once __DIR__ . '/../../model/user.php';
        
        $username = creatusername('Test User');
        
        $this->assertStringStartsWith('user_', $username, 'Username phải bắt đầu với user_');
        $this->assertEquals(11, strlen($username), 'Username phải có độ dài 11 ký tự (user_ + 6 số)');
    }
}
