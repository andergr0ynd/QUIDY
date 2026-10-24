<?php
/**
 * Пример конфигурационного файла
 * 
 * ВАЖНО: Скопируйте этот файл в config.php и заполните своими данными
 * НЕ ЗАГРУЖАЙТЕ config.php в публичный репозиторий!
 */

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Базовый URL сайта (без trailing slash)
define('BASE_URL', 'https://yourdomain.com');

// Discord OAuth настройки
// Получите на https://discord.com/developers/applications
define('DISCORD_CLIENT_ID', 'your_discord_client_id');
define('DISCORD_CLIENT_SECRET', 'your_discord_client_secret');
define('DISCORD_REDIRECT_URI', BASE_URL . '/OAuth/discord_callback.php');

// Telegram OAuth настройки
// Создайте бота через @BotFather в Telegram
define('TELEGRAM_BOT_TOKEN', 'your_telegram_bot_token');
define('TELEGRAM_BOT_USERNAME', 'your_bot_username'); // Без @

// Twitch OAuth настройки
// Получите на https://dev.twitch.tv/console/apps
define('TWITCH_CLIENT_ID', 'your_twitch_client_id');
define('TWITCH_CLIENT_SECRET', 'your_twitch_client_secret');
define('TWITCH_REDIRECT_URI', BASE_URL . '/OAuth/twitch_callback.php');

// Настройки сессий
define('SESSION_LIFETIME', 86400 * 30); // 30 дней в секундах

// Настройки безопасности
// ВАЖНО: Сгенерируйте уникальный ключ для вашего сайта
// Можно использовать: openssl_rand_pseudo_bytes(32)
define('SITE_KEY', 'your_random_32_character_secret_key_here');

// Пароль для OAuth/setup_*.php?password=
define('SETUP_PASSWORD', 'change_this_setup_password');

// Режим отладки
// ВАЖНО: Установите false в продакшене!
define('DEBUG_MODE', false);

// Обработка ошибок
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Настройка временной зоны
date_default_timezone_set('Europe/Moscow');

// Запуск сессии с безопасными настройками
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
               || $_SERVER['SERVER_PORT'] == 443
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
