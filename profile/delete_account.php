<?php
require_once '../OAuth/config.php';
require_once '../OAuth/Database.php';

// Проверка авторизации
$user = null;
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
}

if (!$user) {
    header('Location: ../index.html');
    exit;
}

$error = '';
$step = $_GET['step'] ?? '1';

// Обработка подтверждения удаления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $confirmation = $_POST['confirmation'] ?? '';
    
    // Проверка подтверждающей фразы
    if (strtolower(trim($confirmation)) === 'удалить аккаунт') {
        try {
            $userId = $user['id'];
            
            // Удаляем аккаунт
            $db->deleteUserAccount($userId);
            
            // Удаляем cookie сессии (безопасно)
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                       || $_SERVER['SERVER_PORT'] == 443
                       || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            
            setcookie('session_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Очищаем PHP сессию
            session_destroy();
            
            // Перенаправляем на главную с сообщением
            header('Location: ../index.html?account_deleted=1');
            exit;
            
        } catch (Exception $e) {
            $error = 'Ошибка при удалении аккаунта. Попробуйте позже.';
            if (DEBUG_MODE) {
                $error .= ' ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Неверная подтверждающая фраза';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Удаление аккаунта</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .delete-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(194, 58, 58, 0.3);
        }
        
        .warning-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, rgba(194, 58, 58, 0.3), rgba(153, 46, 46, 0.3));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        
        .delete-card h1 {
            color: #ff6b6b;
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            color: #66c0f4;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .warning-text {
            background: rgba(194, 58, 58, 0.1);
            border-left: 3px solid #ff6b6b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #c7d5e0;
            line-height: 1.6;
        }
        
        .warning-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .warning-list li {
            padding: 10px 0;
            color: #c7d5e0;
            font-size: 15px;
        }
        
        .warning-list li:before {
            content: '⚠️';
            margin-right: 10px;
        }
        
        .form-group {
            margin: 20px 0;
        }
        
        .form-label {
            display: block;
            color: #8f98a0;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(194, 58, 58, 0.3);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 16px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(194, 58, 58, 0.6);
        }
        
        .btn-delete {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, rgba(194, 58, 58, 0.6) 0%, rgba(153, 46, 46, 0.6) 100%);
            border: 1px solid rgba(194, 58, 58, 0.4);
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: linear-gradient(90deg, rgba(194, 58, 58, 0.8) 0%, rgba(153, 46, 46, 0.8) 100%);
        }
        
        .btn-cancel {
            width: 100%;
            padding: 14px;
            background: rgba(102, 192, 244, 0.2);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 4px;
            color: #66c0f4;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .btn-cancel:hover {
            background: rgba(102, 192, 244, 0.3);
        }
        
        .error-message {
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.4);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-text {
            color: #8f98a0;
            font-size: 13px;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="container">
        <a href="dashboard.php" class="back-link">← Вернуться в профиль</a>
        
        <div class="delete-card">
            <div class="warning-icon">⚠️</div>
            <h1>Удаление аккаунта</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="warning-text">
                <strong>Внимание!</strong> Это действие необратимо. После удаления аккаунта все ваши данные будут безвозвратно удалены.
            </div>
            
            <ul class="warning-list">
                <li>Будут удалены все ваши данные профиля</li>
                <li>Будут отвязаны все OAuth аккаунты (Discord, Telegram, Twitch)</li>
                <li>Будут удалены все активные сессии</li>
                <li>История входов будет удалена</li>
                <li>Восстановление данных будет невозможно</li>
            </ul>
            
            <form method="POST" onsubmit="return confirm('Вы уверены? Это действие необратимо!');">
                <div class="form-group">
                    <label class="form-label">
                        Для подтверждения введите фразу: <strong>"удалить аккаунт"</strong>
                    </label>
                    <input type="text" 
                           name="confirmation" 
                           class="form-input" 
                           placeholder="удалить аккаунт"
                           required 
                           autocomplete="off">
                </div>
                
                <button type="submit" name="confirm_delete" class="btn-delete">
                    Удалить аккаунт навсегда
                </button>
                
                <a href="dashboard.php" class="btn-cancel">
                    Отмена, вернуться в профиль
                </a>
                
                <p class="info-text">
                    Если у вас возникли проблемы, свяжитесь с поддержкой: quidysup@gmail.com
                </p>
            </form>
        </div>
    </div>
</body>
</html>

