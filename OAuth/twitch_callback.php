<?php
require_once 'config.php';
require_once 'Database.php';

// Проверка state для защиты от CSRF
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Ошибка валидации state. Возможная CSRF атака.');
}

// Проверка наличия кода
if (!isset($_GET['code'])) {
    die('Код авторизации не получен');
}

$code = $_GET['code'];

try {
    // Обмен кода на токен доступа
    $tokenUrl = 'https://id.twitch.tv/oauth2/token';
    
    $params = [
        'client_id' => TWITCH_CLIENT_ID,
        'client_secret' => TWITCH_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => TWITCH_REDIRECT_URI
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Ошибка получения токена: ' . $response);
    }

    $tokenData = json_decode($response, true);
    $accessToken = $tokenData['access_token'];
    $refreshToken = $tokenData['refresh_token'] ?? null;
    $expiresIn = $tokenData['expires_in'] ?? 3600; // 1 час по умолчанию

    // Получение информации о пользователе
    $userUrl = 'https://api.twitch.tv/helix/users';
    
    $ch = curl_init($userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Client-Id: ' . TWITCH_CLIENT_ID
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Ошибка получения данных пользователя: ' . $response);
    }

    $responseData = json_decode($response, true);
    
    if (!isset($responseData['data'][0])) {
        throw new Exception('Данные пользователя не найдены');
    }
    
    $twitchUser = $responseData['data'][0];

    // Подготовка данных пользователя
    $userData = [
        'username' => $twitchUser['display_name'],
        'email' => $twitchUser['email'] ?? null,
        'avatar' => $twitchUser['profile_image_url'] ?? null,
        'provider_username' => $twitchUser['login'],
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_expires_at' => date('Y-m-d H:i:s', time() + $expiresIn)
    ];

    // Сохранение или обновление пользователя в базе данных
    $db = Database::getInstance();
    
    // Проверяем, режим привязки аккаунта или обычный вход
    $linkMode = isset($_SESSION['link_account']) && $_SESSION['link_account'] === true && isset($_SESSION['user_id']);
    
    if ($linkMode) {
        // Режим привязки к существующему аккаунту
        try {
            // Привязываем OAuth к существующему пользователю
            $db->linkOAuthToUser($_SESSION['user_id'], 'twitch', $twitchUser['id'], $userData);
            $userId = $_SESSION['user_id'];
            
            // Очищаем флаги привязки
            unset($_SESSION['link_account']);
            unset($_SESSION['oauth_state']);
            
            // Логирование
            $db->logAuth($userId, 'twitch', 'login');
            
            // Перенаправляем обратно в профиль
            header('Location: ' . BASE_URL . '/profile/dashboard.php?linked=twitch');
            exit;
        } catch (Exception $e) {
            // Ошибка привязки
            unset($_SESSION['link_account']);
            unset($_SESSION['oauth_state']);
            
            if (DEBUG_MODE) {
                die('Ошибка привязки Twitch: ' . $e->getMessage());
            } else {
                header('Location: ' . BASE_URL . '/profile/dashboard.php?error=link_failed');
                exit;
            }
        }
    }
    
    $userId = $db->findOrCreateUser('twitch', $twitchUser['id'], $userData);

    // Очистка state
    unset($_SESSION['oauth_state']);

    // Проверка 2FA
    $user = $db->getUserById($userId);
    if ($user && $user['two_factor_enabled']) {
        // Если 2FA включена, сохраняем ID пользователя и перенаправляем на страницу ввода кода
        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['auth_provider'] = 'twitch';
        header('Location: ' . BASE_URL . '/OAuth/verify_2fa.php');
        exit;
    }

    // Создание сессии (если 2FA выключена)
    $sessionToken = $db->createSession($userId);
    $db->setSecureSessionCookie($sessionToken);

    // Логирование
    $db->logAuth($userId, 'twitch', 'login');

    // Очистка провайдера
    unset($_SESSION['oauth_provider']);

    // Перенаправление на главную страницу (или страницу профиля)
    header('Location: ' . BASE_URL . '/profile/dashboard.php');
    exit;

} catch (Exception $e) {
    if (DEBUG_MODE) {
        die('Ошибка авторизации Twitch: ' . $e->getMessage());
    } else {
        header('Location: ' . BASE_URL . '/?error=auth_failed');
        exit;
    }
}

