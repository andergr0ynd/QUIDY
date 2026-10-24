<?php
require_once 'OAuth/config.php';

echo "<h2>🔍 Проверка базы данных</h2>";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к БД: OK<br>";

    // Проверяем таблицы
    $tables = ['users', 'oauth_connections', 'sessions', 'auth_logs'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Таблица $table: существует<br>";
            
            // Показываем структуру
            $stmt = $conn->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "&nbsp;&nbsp;Структура:<br>";
            foreach ($columns as $column) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;{$column['Field']} - {$column['Type']}<br>";
            }
        } else {
            echo "❌ Таблица $table: НЕ СУЩЕСТВУЕТ<br>";
        }
    }
    
    // Проверяем пользователей
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "<br>👥 Пользователей в БД: $count<br>";
    
    // Проверяем OAuth подключения
    $stmt = $conn->query("SELECT provider, COUNT(*) as count FROM oauth_connections GROUP BY provider");
    $oauth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<br>🔗 OAuth подключения:<br>";
    foreach ($oauth as $row) {
        echo "&nbsp;&nbsp;{$row['provider']}: {$row['count']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "<br>";
}

echo "<br><h3>🧪 Тест Telegram авторизации:</h3>";
echo "<p><a href='test_telegram.php'>Открыть тест Telegram</a></p>";
?>
