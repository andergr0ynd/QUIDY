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

// Проверяем, не привязан ли уже Telegram
if ($db->isOAuthLinked($user['id'], 'telegram')) {
    header('Location: ' . BASE_URL . '/profile/dashboard.php?error=already_linked');
    exit;
}

// Устанавливаем режим привязки и ID пользователя
$_SESSION['link_account'] = true;
$_SESSION['user_id'] = $user['id'];

// Перенаправляем на страницу Telegram
header('Location: ' . BASE_URL . '/OAuth/telegram.php');
exit;

