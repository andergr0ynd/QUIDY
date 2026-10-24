<?php
require_once 'config.php';
require_once 'Database.php';

header('Content-Type: application/json');

$user = null;
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
}

// Отладочная информация
error_log("API Commands Debug - Session token: " . ($_COOKIE['session_token'] ?? 'not set'));
error_log("API Commands Debug - User: " . ($user ? json_encode($user) : 'null'));

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - No valid session']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $conn = $db->getConnection();

    switch ($action) {
        case 'list':
            // Получить список всех команд
            $stmt = $conn->prepare("
                SELECT * FROM custom_commands 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $commands
            ]);
            break;

        case 'create':
            // Создать новую команду
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            $commandName = trim($input['command_name'] ?? '');
            $commandText = trim($input['command_text'] ?? '');
            $permissionLevel = $input['permission_level'] ?? 'everyone';
            $cooldownSeconds = (int)($input['cooldown_seconds'] ?? 0);

            if (empty($commandName) || empty($commandText)) {
                throw new Exception('Название команды и текст обязательны');
            }

            if (!preg_match('/^![a-zA-Z0-9_]+$/', $commandName)) {
                throw new Exception('Название команды должно начинаться с ! и содержать только буквы, цифры и _');
            }

            if (!in_array($permissionLevel, ['everyone', 'moderator', 'vip', 'subscriber'])) {
                throw new Exception('Неверный уровень доступа');
            }

            $stmt = $conn->prepare("
                INSERT INTO custom_commands (command_name, command_text, permission_level, cooldown_seconds, created_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $commandName,
                $commandText,
                $permissionLevel,
                $cooldownSeconds,
                $user['username']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Команда создана успешно',
                'data' => ['id' => $conn->lastInsertId()]
            ]);
            break;

        case 'update':
            // Обновить команду
            if ($method !== 'PUT') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $commandId = (int)($input['id'] ?? 0);
            
            if (!$commandId) {
                throw new Exception('ID команды обязателен');
            }

            $commandText = trim($input['command_text'] ?? '');
            $permissionLevel = $input['permission_level'] ?? 'everyone';
            $cooldownSeconds = (int)($input['cooldown_seconds'] ?? 0);
            $isActive = (int)($input['is_active'] ?? 1);

            if (empty($commandText)) {
                throw new Exception('Текст команды обязателен');
            }

            if (!in_array($permissionLevel, ['everyone', 'moderator', 'vip', 'subscriber'])) {
                throw new Exception('Неверный уровень доступа');
            }

            $stmt = $conn->prepare("
                UPDATE custom_commands 
                SET command_text = ?, permission_level = ?, cooldown_seconds = ?, is_active = ?
                WHERE id = ? AND created_by = ?
            ");
            
            $result = $stmt->execute([
                $commandText,
                $permissionLevel,
                $cooldownSeconds,
                $isActive,
                $commandId,
                $user['username']
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Команда не найдена или у вас нет прав на её редактирование');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Команда обновлена успешно'
            ]);
            break;

        case 'delete':
            // Удалить команду
            if ($method !== 'DELETE') {
                throw new Exception('Method not allowed');
            }

            $commandId = (int)($_GET['id'] ?? 0);
            
            if (!$commandId) {
                throw new Exception('ID команды обязателен');
            }

            $stmt = $conn->prepare("
                DELETE FROM custom_commands 
                WHERE id = ? AND created_by = ?
            ");
            
            $result = $stmt->execute([$commandId, $user['username']]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Команда не найдена или у вас нет прав на её удаление');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Команда удалена успешно'
            ]);
            break;

        case 'toggle':
            // Включить/выключить команду
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $commandId = (int)($input['id'] ?? 0);
            $isActive = (int)($input['is_active'] ?? 0);
            
            if (!$commandId) {
                throw new Exception('ID команды обязателен');
            }

            $stmt = $conn->prepare("
                UPDATE custom_commands 
                SET is_active = ?
                WHERE id = ? AND created_by = ?
            ");
            
            $result = $stmt->execute([$isActive, $commandId, $user['username']]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Команда не найдена или у вас нет прав на её редактирование');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Статус команды изменен'
            ]);
            break;


        default:
            throw new Exception('Неизвестное действие');
    }

} catch (Exception $e) {
    error_log("API Commands Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
