<?php
require_once 'config.php';
require_once 'Database.php';

header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_COOKIE['session_token'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$db = Database::getInstance();
$user = $db->getUserBySession($_COOKIE['session_token']);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Сессия недействительна']);
    exit;
}

// Получаем данные запроса
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'status':
            // Проверяем, подключен ли бот для пользователя
            try {
                $botChannel = $db->getBotChannelByUserId($user['id']);
            } catch (Exception $e) {
                $botChannel = null;
            }
            echo json_encode([
                'success' => true,
                'connected' => $botChannel !== null,
                'channel' => $botChannel
            ]);
            break;
            
        case 'connect':
            // Подключаем бота к каналу Twitch пользователя
            
            // Получаем полные данные OAuth подключений
            $allConnections = $db->getOAuthConnections($user['id']);
            $twitchConnection = null;
            
            foreach ($allConnections as $conn) {
                if ($conn['provider'] === 'twitch') {
                    $twitchConnection = $conn;
                    break;
                }
            }
            
            if (!$twitchConnection) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Сначала подключите Twitch аккаунт'
                ]);
                exit;
            }
            
            // Проверяем наличие необходимых данных
            if (empty($twitchConnection['provider_user_id'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка данных Twitch аккаунта. Попробуйте переподключить Twitch.'
                ]);
                exit;
            }
            
            // Проверяем, не подключен ли уже бот
            try {
                $existingChannel = $db->getBotChannelByUserId($user['id']);
                if ($existingChannel) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Бот уже подключен к вашему каналу'
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                // Таблица не создана
                echo json_encode([
                    'success' => false,
                    'error' => 'Функционал бота еще не настроен. Создайте таблицу bot_channels в базе данных. Ошибка: ' . $e->getMessage()
                ]);
                exit;
            }
            
            // Добавляем канал в базу
            try {
                $result = $db->addBotChannel(
                    $user['id'],
                    $twitchConnection['provider_user_id'],
                    $twitchConnection['provider_username'],
                    $twitchConnection['provider_username']
                );
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Бот успешно подключен к вашему каналу!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Не удалось подключить бота. Попробуйте позже.'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'disconnect':
            // Отключаем бота от канала
            $result = $db->removeBotChannel($user['id']);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Бот отключен от канала'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка отключения бота'
                ]);
            }
            break;
            
        case 'send_message':
            // Отправка сообщения от бота в чат
            
            // Проверяем, подключен ли бот
            try {
                $botChannel = $db->getBotChannelByUserId($user['id']);
                if (!$botChannel) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Бот не подключен к каналу'
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Бот не подключен к каналу'
                ]);
                exit;
            }
            
            // Получаем текст сообщения
            $message = trim($_POST['message'] ?? '');
            if (empty($message)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Сообщение не может быть пустым'
                ]);
                exit;
            }
            
            if (strlen($message) > 500) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Сообщение слишком длинное (максимум 500 символов)'
                ]);
                exit;
            }
            
            // Получаем токен доступа для Twitch
            $allConnections = $db->getOAuthConnections($user['id']);
            $twitchConnection = null;
            
            foreach ($allConnections as $conn) {
                if ($conn['provider'] === 'twitch') {
                    $twitchConnection = $conn;
                    break;
                }
            }
            
            if (!$twitchConnection || empty($twitchConnection['access_token'])) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка доступа к Twitch. Переподключите аккаунт.'
                ]);
                exit;
            }
            
            // Отправляем сообщение через Twitch API
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://api.twitch.tv/helix/chat/messages',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $twitchConnection['access_token'],
                        'Client-Id: ' . TWITCH_CLIENT_ID,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_POSTFIELDS => json_encode([
                        'broadcaster_id' => $twitchConnection['provider_user_id'],
                        'sender_id' => $twitchConnection['provider_user_id'],
                        'message' => $message
                    ])
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 204 || $httpCode === 200) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Сообщение отправлено'
                    ]);
                } else {
                    $errorData = json_decode($response, true);
                    $errorMsg = $errorData['message'] ?? 'Ошибка при отправке сообщения';
                    echo json_encode([
                        'success' => false,
                        'error' => $errorMsg
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Ошибка отправки: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Неизвестное действие'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сервера: ' . $e->getMessage()
    ]);
}

