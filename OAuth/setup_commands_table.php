<?php
require_once 'config.php';
require_once 'Database.php';

if (!defined('SETUP_PASSWORD') || SETUP_PASSWORD === '' || !isset($_GET['password']) || !hash_equals((string)SETUP_PASSWORD, (string)$_GET['password'])) {
    die('Доступ запрещен. Укажите правильный пароль в параметре ?password=');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "<h2>Установка таблицы custom_commands</h2>";

    $sql = "CREATE TABLE IF NOT EXISTS `custom_commands` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `command_name` VARCHAR(50) NOT NULL UNIQUE,
        `command_text` TEXT NOT NULL,
        `permission_level` ENUM('everyone', 'moderator', 'vip', 'subscriber') DEFAULT 'everyone',
        `cooldown_seconds` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `usage_count` INT DEFAULT 0,
        `created_by` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_command_name` (`command_name`),
        INDEX `idx_permission_level` (`permission_level`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo "<p style='color: green;'>✓ Таблица custom_commands успешно создана!</p>";

    echo "<h2>Установка таблицы tts_settings</h2>";
    $sql_tts = "CREATE TABLE IF NOT EXISTS `tts_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `channel` VARCHAR(255) NOT NULL UNIQUE,
        `is_enabled` TINYINT(1) DEFAULT 1,
        `voice_name` VARCHAR(100) DEFAULT 'Microsoft David Desktop',
        `voice_rate` INT DEFAULT 0,
        `voice_volume` INT DEFAULT 100,
        `cooldown_seconds` INT DEFAULT 5,
        `max_message_length` INT DEFAULT 200,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql_tts);
    echo "<p style='color: green;'>✓ Таблица tts_settings успешно создана!</p>";

    echo "<h2>Добавление примеров команд</h2>";
    
    // Добавляем несколько примеров команд
    $exampleCommands = [
        [
            'command_name' => '!info',
            'command_text' => '🌟 Добро пожаловать на канал! Ставь лайк, подписывайся и заказывай настройку буста! Ссылки в описании! 🌟',
            'permission_level' => 'everyone',
            'cooldown_seconds' => 30,
            'created_by' => 'system'
        ],
        [
            'command_name' => '!discord',
            'command_text' => '💬 Присоединяйся к нашему Discord серверу: https://discord.gg/example',
            'permission_level' => 'everyone',
            'cooldown_seconds' => 60,
            'created_by' => 'system'
        ],
        [
            'command_name' => '!rules',
            'command_text' => '📋 Правила чата: 1) Уважай других 2) Не спамь 3) Не рекламируй 4) Наслаждайся стримом!',
            'permission_level' => 'everyone',
            'cooldown_seconds' => 120,
            'created_by' => 'system'
        ],
        [
            'command_name' => '!tts',
            'command_text' => 'TTS_COMMAND',
            'permission_level' => 'moderator',
            'cooldown_seconds' => 5,
            'created_by' => 'system'
        ]
    ];

    $stmt = $conn->prepare("
        INSERT INTO custom_commands (command_name, command_text, permission_level, cooldown_seconds, created_by) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        command_text = VALUES(command_text),
        permission_level = VALUES(permission_level),
        cooldown_seconds = VALUES(cooldown_seconds)
    ");

    foreach ($exampleCommands as $cmd) {
        $stmt->execute([
            $cmd['command_name'],
            $cmd['command_text'],
            $cmd['permission_level'],
            $cmd['cooldown_seconds'],
            $cmd['created_by']
        ]);
    }

    echo "<p style='color: green;'>✓ Примеры команд добавлены!</p>";

    echo "<hr>";
    echo "<p><strong>Готово!</strong> Система кастомных команд установлена.</p>";
    echo "<p><a href='../profile/commands.php'>Перейти к управлению командами</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>ВАЖНО:</strong> После установки удалите этот файл (setup_commands_table.php) для безопасности!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
}
?>
