<?php

class AnthropicConfig {
    private static $config = [];
    
    public static function init() {
        self::loadEnv();
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
    
    public static function getApiKey() {
        return self::$config['ANTHROPIC_API_KEY'] ?? '';
    }
    
    public static function getModel() {
        return self::$config['ANTHROPIC_MODEL'] ?? 'claude-3-sonnet-20240229';
    }
    
    public static function getBaseUrl() {
        return 'https://api.anthropic.com/v1/messages';
    }
    
    public static function getHeaders() {
        return [
            'Content-Type: application/json',
            'x-api-key: ' . self::getApiKey(),
            'anthropic-version: 2023-06-01'
        ];
    }
    
    public static function isConfigured() {
        $apiKey = self::getApiKey();
        return !empty($apiKey) && $apiKey !== 'your_api_key_here';
    }
}