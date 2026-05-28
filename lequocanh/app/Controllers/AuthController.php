<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\OAuthService;

class AuthController
{
    private OAuthService $oauth;
    
    public function __construct()
    {
        $this->oauth = OAuthService::fromConfig();
    }
    
    /**
     * Redirect to Google OAuth
     */
    public function googleRedirect(): void
    {
        if (!$this->oauth->isGoogleEnabled()) {
            $_SESSION['error'] = 'Google login is not enabled';
            header('Location: /login');
            exit;
        }
        
        $url = $this->oauth->getGoogleAuthUrl();
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(): void
    {
        $code = $_GET['code'] ?? '';
        
        if (empty($code)) {
            $_SESSION['error'] = 'Google login failed';
            header('Location: /login');
            exit;
        }
        
        try {
            $userInfo = $this->oauth->handleGoogleCallback($code);
            $user = $this->findOrCreateUser($userInfo);
            
            $_SESSION['USER'] = $user->username;
            $_SESSION['login_success'] = true;
            
            header('Location: /');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Google login failed: ' . $e->getMessage();
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Redirect to Facebook OAuth
     */
    public function facebookRedirect(): void
    {
        if (!$this->oauth->isFacebookEnabled()) {
            $_SESSION['error'] = 'Facebook login is not enabled';
            header('Location: /login');
            exit;
        }
        
        $url = $this->oauth->getFacebookAuthUrl();
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Handle Facebook OAuth callback
     */
    public function facebookCallback(): void
    {
        $code = $_GET['code'] ?? '';
        
        if (empty($code)) {
            $_SESSION['error'] = 'Facebook login failed';
            header('Location: /login');
            exit;
        }
        
        try {
            $userInfo = $this->oauth->handleFacebookCallback($code);
            $user = $this->findOrCreateUser($userInfo);
            
            $_SESSION['USER'] = $user->username;
            $_SESSION['login_success'] = true;
            
            header('Location: /');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Facebook login failed: ' . $e->getMessage();
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Find or create user from OAuth
     */
    private function findOrCreateUser(array $userInfo): object
    {
        $db = \Database::getInstance()->getConnection();
        
        // Check if user exists by provider ID
        $field = $userInfo['provider'] . '_id';
        $stmt = $db->prepare("SELECT id, username, hoten, email, avatar_url, auth_provider, {$field} FROM users WHERE {$field} = ?");
        $stmt->execute([$userInfo['provider_id']]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if ($user) {
            return $user;
        }
        
        // Check if user exists by email
        if (!empty($userInfo['email'])) {
            $stmt = $db->prepare("SELECT id, username, hoten, email, avatar_url, auth_provider FROM users WHERE email = ?");
            $stmt->execute([$userInfo['email']]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($user) {
                // Link OAuth account
                $stmt = $db->prepare("UPDATE users SET {$field} = ?, auth_provider = ? WHERE id = ?");
                $stmt->execute([$userInfo['provider_id'], $userInfo['provider'], $user->id]);
                return $user;
            }
        }
        
        // Create new user
        $username = $userInfo['provider'] . '_' . $userInfo['provider_id'];
        $stmt = $db->prepare("
            INSERT INTO users (username, hoten, email, {$field}, auth_provider, avatar_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $userInfo['name'],
            $userInfo['email'],
            $userInfo['provider_id'],
            $userInfo['provider'],
            $userInfo['avatar'],
        ]);
        
        $stmt = $db->prepare("SELECT id, username, hoten, email, avatar_url, auth_provider FROM users WHERE id = ?");
        $stmt->execute([$db->lastInsertId()]);
        
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
}