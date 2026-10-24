<?php
require_once 'OAuth/config.php';

echo "<h2>🤖 Проверка Telegram бота</h2>";

// Проверяем настройки из config.php
echo "<h3>📋 Настройки из config.php:</h3>";
echo "<ul>";
echo "<li><strong>Bot Username:</strong> " . TELEGRAM_BOT_USERNAME . "</li>";
echo "<li><strong>Bot Token:</strong> " . substr(TELEGRAM_BOT_TOKEN, 0, 10) . "...</li>";
echo "<li><strong>Base URL:</strong> " . BASE_URL . "</li>";
echo "</ul>";

// Проверяем доступность бота через API
echo "<h3>🔍 Проверка бота через Telegram API:</h3>";

$botToken = TELEGRAM_BOT_TOKEN;
$apiUrl = "https://api.telegram.org/bot{$botToken}/getMe";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "<p class='error'>❌ Не удалось подключиться к Telegram API</p>";
} else {
    $data = json_decode($response, true);
    
    if ($data && $data['ok']) {
        $bot = $data['result'];
        echo "<p class='success'>✅ Бот найден и активен!</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $bot['id'] . "</li>";
        echo "<li><strong>Username:</strong> @" . $bot['username'] . "</li>";
        echo "<li><strong>First Name:</strong> " . $bot['first_name'] . "</li>";
        echo "<li><strong>Can Join Groups:</strong> " . ($bot['can_join_groups'] ? 'Да' : 'Нет') . "</li>";
        echo "<li><strong>Can Read All Group Messages:</strong> " . ($bot['can_read_all_group_messages'] ? 'Да' : 'Нет') . "</li>";
        echo "<li><strong>Supports Inline Queries:</strong> " . ($bot['supports_inline_queries'] ? 'Да' : 'Нет') . "</li>";
        echo "</ul>";
        
        // Проверяем, совпадает ли username
        if ($bot['username'] === TELEGRAM_BOT_USERNAME) {
            echo "<p class='success'>✅ Username совпадает с настройками</p>";
        } else {
            echo "<p class='error'>❌ Username не совпадает! В config.php: " . TELEGRAM_BOT_USERNAME . ", в API: " . $bot['username'] . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Ошибка API: " . ($data['description'] ?? 'Неизвестная ошибка') . "</p>";
    }
}

// Проверяем настройки Login Widget
echo "<h3>🔗 Проверка Login Widget:</h3>";
echo "<p>Для работы Login Widget нужно:</p>";
echo "<ol>";
echo "<li>В @BotFather выбрать бота <strong>@" . TELEGRAM_BOT_USERNAME . "</strong></li>";
echo "<li>Выбрать <strong>Bot Settings</strong></li>";
echo "<li>Выбрать <strong>Domain</strong></li>";
echo "<li>Указать домен: <strong>" . parse_url(BASE_URL, PHP_URL_HOST) . "</strong></li>";
echo "<li>Сохранить настройки</li>";
echo "</ol>";

echo "<h3>🌐 Проверка HTTPS:</h3>";
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($isHttps) {
    echo "<p class='success'>✅ Сайт доступен по HTTPS</p>";
} else {
    echo "<p class='error'>❌ Сайт НЕ доступен по HTTPS. Telegram Login Widget требует HTTPS!</p>";
}

echo "<h3>🧪 Тестирование:</h3>";
echo "<p><a href='test_csp.php' style='color: #66c0f4;'>Открыть тест CSP</a></p>";
echo "<p><a href='test_telegram.php' style='color: #66c0f4;'>Открыть тест Telegram авторизации</a></p>";

echo "<style>
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #1e2836; color: #c7d5e0; }
    h2, h3 { color: #66c0f4; }
    ul, ol { line-height: 1.6; }
</style>";
?>
