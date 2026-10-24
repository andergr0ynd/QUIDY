<?php
require_once 'config.php';
require_once 'Database.php';

// Проверка авторизации
$user = null;
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
}

if (!$user) {
    header('Location: ' . BASE_URL . '/index.html');
    exit;
}

// Проверяем, не привязан ли уже Twitch
if ($db->isOAuthLinked($user['id'], 'twitch')) {
    header('Location: ' . BASE_URL . '/profile/dashboard.php?error=already_linked');
    exit;
}

// Устанавливаем режим привязки и ID пользователя
$_SESSION['link_account'] = true;
$_SESSION['user_id'] = $user['id'];

// Генерация state для защиты от CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = 'twitch';

// URL для авторизации Twitch
$params = [
    'client_id' => TWITCH_CLIENT_ID,
    'redirect_uri' => TWITCH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'user:read:email user:write:chat',
    'state' => $state
];

$authUrl = 'https://id.twitch.tv/oauth2/authorize?' . http_build_query($params);

// Перенаправление на Twitch
header('Location: ' . $authUrl);
exit;

