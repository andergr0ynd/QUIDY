<?php
/**
 * Скрипт для создания таблицы bot_channels
 * Запустите этот скрипт один раз для настройки функционала бота
 */

require_once 'config.php';
require_once 'Database.php';

if (!defined('SETUP_PASSWORD') || SETUP_PASSWORD === '' || !isset($_GET['password']) || !hash_equals((string)SETUP_PASSWORD, (string)$_GET['password'])) {
    die('Доступ запрещен. Укажите правильный пароль в параметре ?password=');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Установка таблицы bot_channels</h2>";
    
    // Создаем таблицу
    $sql = "CREATE TABLE IF NOT EXISTS `bot_channels` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `twitch_user_id` VARCHAR(255) NOT NULL,
        `twitch_username` VARCHAR(255) NOT NULL,
        `display_name` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_twitch_user` (`twitch_user_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_is_active` (`is_active`),
        INDEX `idx_twitch_username` (`twitch_username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Таблица bot_channels успешно создана!</p>";
    
    // Пытаемся добавить внешний ключ (если еще не добавлен)
    try {
        $sql = "ALTER TABLE `bot_channels` 
                ADD CONSTRAINT `fk_bot_channel_user` 
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) 
                ON DELETE CASCADE";
        $conn->exec($sql);
        echo "<p style='color: green;'>✓ Внешний ключ добавлен!</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p style='color: orange;'>⚠ Внешний ключ уже существует</p>";
        } else {
            echo "<p style='color: red;'>⚠ Не удалось добавить внешний ключ: " . $e->getMessage() . "</p>";
            echo "<p>Это не критично, таблица будет работать без него.</p>";
        }
    }
    
    // Проверяем таблицу
    $stmt = $conn->query("SHOW TABLES LIKE 'bot_channels'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Проверка прошла успешно!</p>";
        echo "<h3>Структура таблицы:</h3>";
        echo "<pre>";
        $stmt = $conn->query("DESCRIBE bot_channels");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        echo "</pre>";
    }
    
    echo "<hr>";
    echo "<p><strong>Готово!</strong> Теперь вы можете использовать функционал бота.</p>";
    echo "<p><a href='../profile/dashboard.php'>Вернуться в панель управления</a></p>";
    
    // ВАЖНО: После успешной установки удалите или переименуйте этот файл!
    echo "<hr>";
    echo "<p style='color: red;'><strong>ВАЖНО:</strong> После установки удалите этот файл (setup_bot_table.php) для безопасности!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
}

