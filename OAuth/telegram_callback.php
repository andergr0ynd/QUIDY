<?php
require_once 'config.php';
require_once 'Database.php';

// Проверка данных от Telegram
function checkTelegramAuthorization($auth_data) {
    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    
    $data_check_string = implode("\n", $data_check_arr);
    $secret_key = hash('sha256', TELEGRAM_BOT_TOKEN, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    
    if (strcmp($hash, $check_hash) !== 0) {
        throw new Exception('Данные от Telegram не прошли проверку');
    }
    
    // Проверка времени (данные действительны в течение 1 дня)
    if ((time() - $auth_data['auth_date']) > 86400) {
        throw new Exception('Данные авторизации устарели');
    }
    
    return true;
}

try {
    // Получение данных от Telegram
    $auth_data = $_GET;
    
    if (!isset($auth_data['id'])) {
        throw new Exception('Отсутствуют данные авторизации');
    }
    
    // Проверка подлинности данных
    checkTelegramAuthorization($auth_data);
    
    // Подготовка данных пользователя
    $username = $auth_data['username'] ?? 'telegram_user_' . $auth_data['id'];
    $firstName = $auth_data['first_name'] ?? '';
    $lastName = $auth_data['last_name'] ?? '';
    $fullName = trim($firstName . ' ' . $lastName);
    
    if (empty($fullName)) {
        $fullName = $username;
    }
    
    $photoUrl = $auth_data['photo_url'] ?? null;
    
    $userData = [
        'username' => $fullName,
        'email' => null, // Telegram не предоставляет email
        'avatar' => $photoUrl,
        'provider_username' => $username,
        'access_token' => null, // Telegram Login Widget не использует access token
        'refresh_token' => null,
        'token_expires_at' => null
    ];
    
    // Сохранение или обновление пользователя в базе данных
    $db = Database::getInstance();
    
    // Проверяем, режим привязки аккаунта или обычный вход
    $linkMode = isset($_SESSION['link_account']) && $_SESSION['link_account'] === true && isset($_SESSION['user_id']);
    
    if ($linkMode) {
        // Режим привязки к существующему аккаунту
        try {
            // Привязываем OAuth к существующему пользователю
            $db->linkOAuthToUser($_SESSION['user_id'], 'telegram', $auth_data['id'], $userData);
            $userId = $_SESSION['user_id'];
            
            // Очищаем флаги привязки
            unset($_SESSION['link_account']);
            
            // Логирование
            $db->logAuth($userId, 'telegram', 'login');
            
            // Перенаправляем обратно в профиль
            header('Location: ' . BASE_URL . '/profile/dashboard.php?linked=telegram');
            exit;
        } catch (Exception $e) {
            // Ошибка привязки
            unset($_SESSION['link_account']);
            
            if (DEBUG_MODE) {
                die('Ошибка привязки Telegram: ' . $e->getMessage());
            } else {
                header('Location: ' . BASE_URL . '/profile/dashboard.php?error=link_failed');
                exit;
            }
        }
    }
    
    $userId = $db->findOrCreateUser('telegram', $auth_data['id'], $userData);
    
    // Проверка 2FA
    $user = $db->getUserById($userId);
    if ($user && $user['two_factor_enabled']) {
        // Если 2FA включена, сохраняем ID пользователя и перенаправляем на страницу ввода кода
        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['auth_provider'] = 'telegram';
        header('Location: ' . BASE_URL . '/OAuth/verify_2fa.php');
        exit;
    }
    
    // Создание сессии (если 2FA выключена)
    $sessionToken = $db->createSession($userId);
    $db->setSecureSessionCookie($sessionToken);
    
    // Логирование
    $db->logAuth($userId, 'telegram', 'login');
    
    // Перенаправление на главную страницу (или страницу профиля)
    header('Location: ' . BASE_URL . '/profile/dashboard.php');
    exit;
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Ошибка авторизации Telegram: ' . $e->getMessage());
    } else {
        header('Location: ' . BASE_URL . '/?error=auth_failed');
        exit;
    }
}

