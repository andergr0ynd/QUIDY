<?php
require_once 'config.php';
require_once 'Database.php';

header('Content-Type: application/json');

// Проверка авторизации
$user = null;
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
}

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не разрешён']);
    exit;
}

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
$provider = $data['provider'] ?? '';

if (empty($provider) || !in_array($provider, ['discord', 'telegram', 'twitch'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Неверный провайдер']);
    exit;
}

try {
    $db->unlinkOAuth($user['id'], $provider);
    
    echo json_encode([
        'success' => true,
        'message' => 'Аккаунт успешно отвязан'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

