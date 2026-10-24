<?php
require_once 'config.php';
require_once 'Database.php';

// Удаление сессии
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $db->deleteSession($_COOKIE['session_token']);
    
    // Удаление cookie (безопасно)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || $_SERVER['SERVER_PORT'] == 443
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    setcookie('session_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Очистка PHP сессии
session_destroy();

// Перенаправление на главную страницу
header('Location: ' . BASE_URL . '/index.html');
exit;

