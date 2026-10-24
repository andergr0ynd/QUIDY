<?php
require_once 'config.php';

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

