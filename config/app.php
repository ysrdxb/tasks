<?php

class App {
    private static $config = [];
    
    public static function init() {
        self::loadEnv();
        self::setupErrorHandling();
        self::startSession();
    }
    
    private static function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
    }
    
    private static function setupErrorHandling() {
        $debug = self::config('APP_DEBUG', 'false') === 'true';
        
        if ($debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
    }
    
    private static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int) self::config('SESSION_LIFETIME', 7200);
            ini_set('session.gc_maxlifetime', $lifetime);
            session_set_cookie_params($lifetime);
            session_start();
        }
    }
    
    public static function config($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
    
    public static function url($path = '') {
        $baseUrl = rtrim(self::config('APP_URL', 'http://localhost/tasks'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    public static function asset($path) {
        return self::url('public/assets/' . ltrim($path, '/'));
    }
    
    public static function uploadPath($filename = '') {
        $uploadPath = self::config('UPLOAD_PATH', 'public/uploads/');
        $basePath = __DIR__ . '/../' . trim($uploadPath, '/');
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        return $filename ? $basePath . '/' . $filename : $basePath;
    }
    
    public static function redirect($url, $statusCode = 302) {
        header("Location: $url", true, $statusCode);
        exit();
    }
    
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
    }
    
    public static function csrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        $expiry = (int) self::config('CSRF_TOKEN_EXPIRE', 3600);
        if (time() - $_SESSION['csrf_token_time'] > $expiry) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}