<?php
/**
 * Скрипт для создания таблицы user_time_tracking
 * Запустите этот скрипт один раз для настройки функционала отслеживания времени
 */

require_once 'config.php';
require_once 'Database.php';

if (!defined('SETUP_PASSWORD') || SETUP_PASSWORD === '' || !isset($_GET['password']) || !hash_equals((string)SETUP_PASSWORD, (string)$_GET['password'])) {
    die('Доступ запрещен. Укажите правильный пароль в параметре ?password=');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>Установка таблицы user_time_tracking</h2>";
    
    // Создаем таблицу для отслеживания времени пользователей
    $sql = "CREATE TABLE IF NOT EXISTS `user_time_tracking` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(255) NOT NULL,
        `channel` VARCHAR(255) NOT NULL,
        `total_accumulated_time` INT DEFAULT 0 COMMENT 'Общее накопленное время в секундах',
        `last_activity_timestamp` TIMESTAMP NULL DEFAULT NULL COMMENT 'Время последней активности в чате',
        `is_timer_active` TINYINT(1) DEFAULT 0 COMMENT 'Активен ли таймер (0/1)',
        `captcha_code` VARCHAR(10) DEFAULT NULL COMMENT 'Код капчи для подтверждения',
        `captcha_expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Время истечения капчи',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_channel` (`username`, `channel`),
        INDEX `idx_username` (`username`),
        INDEX `idx_channel` (`channel`),
        INDEX `idx_is_timer_active` (`is_timer_active`),
        INDEX `idx_last_activity` (`last_activity_timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Таблица user_time_tracking успешно создана!</p>";
    
    // Создаем таблицу для хранения кэша статуса стримов
    $sql = "CREATE TABLE IF NOT EXISTS `stream_status_cache` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `channel` VARCHAR(255) NOT NULL,
        `is_live` TINYINT(1) DEFAULT 0,
        `stream_title` VARCHAR(500) DEFAULT NULL,
        `viewer_count` INT DEFAULT 0,
        `cached_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` TIMESTAMP NOT NULL,
        UNIQUE KEY `unique_channel` (`channel`),
        INDEX `idx_is_live` (`is_live`),
        INDEX `idx_expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Таблица stream_status_cache успешно создана!</p>";
    
    // Проверяем таблицы
    $stmt = $conn->query("SHOW TABLES LIKE 'user_time_tracking'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Проверка user_time_tracking прошла успешно!</p>";
        echo "<h3>Структура таблицы user_time_tracking:</h3>";
        echo "<pre>";
        $stmt = $conn->query("DESCRIBE user_time_tracking");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        echo "</pre>";
    }
    
    $stmt = $conn->query("SHOW TABLES LIKE 'stream_status_cache'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Проверка stream_status_cache прошла успешно!</p>";
        echo "<h3>Структура таблицы stream_status_cache:</h3>";
        echo "<pre>";
        $stmt = $conn->query("DESCRIBE stream_status_cache");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        echo "</pre>";
    }
    
    echo "<hr>";
    echo "<p><strong>Готово!</strong> Теперь вы можете использовать функционал отслеживания времени.</p>";
    echo "<p><a href='../profile/dashboard.php'>Вернуться в панель управления</a></p>";
    
    // ВАЖНО: После успешной установки удалите или переименуйте этот файл!
    echo "<hr>";
    echo "<p style='color: red;'><strong>ВАЖНО:</strong> После установки удалите этот файл (setup_user_time_table.php) для безопасности!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
}
