<?php
require_once 'config.php';
require_once 'Database.php';
require_once 'TOTP.php';
require_once 'Security.php';

// Проверка, есть ли pending user ID в сессии
if (!isset($_SESSION['pending_user_id'])) {
    header('Location: ' . BASE_URL . '/index.html');
    exit;
}

$db = Database::getInstance();
$user = $db->getUserById($_SESSION['pending_user_id']);

if (!$user || !$user['two_factor_enabled']) {
    // Если 2FA не включена, просто создаем сессию
    $sessionToken = $db->createSession($user['id']);
    $db->setSecureSessionCookie($sessionToken);
    
    unset($_SESSION['pending_user_id']);
    header('Location: ' . BASE_URL . '/profile/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting - максимум 5 попыток в 5 минут
    if (!Security::checkRateLimit('2fa_verify', 5, 300)) {
        $timeLeft = Security::getRateLimitTimeLeft('2fa_verify', 300);
        $minutes = ceil($timeLeft / 60);
        $error = "Слишком много попыток. Попробуйте через $minutes мин.";
        Security::logSecurityEvent('2fa_rate_limit_exceeded', [
            'user_id' => $user['id']
        ]);
    } else {
        $code = $_POST['code'] ?? '';
        $code = str_replace([' ', '-'], '', $code); // Удаляем пробелы и дефисы
        
        $codeValid = false;
        $usedBackupCode = false;
        
        // Проверяем TOTP код
        if (TOTP::verifyCode($user['two_factor_secret'], $code)) {
            $codeValid = true;
        }
        // Если TOTP не прошёл, пробуем backup код
        elseif ($db->verifyBackupCode($user['id'], $code)) {
            $codeValid = true;
            $usedBackupCode = true;
        }
        
        if ($codeValid) {
            // Код верный, создаем сессию
            $sessionToken = $db->createSession($user['id']);
            $db->setSecureSessionCookie($sessionToken);
            
            // Сбрасываем rate limit после успешной аутентификации
            Security::resetRateLimit('2fa_verify');
            
            // Логирование
            $provider = $_SESSION['auth_provider'] ?? 'unknown';
            $db->logAuth($user['id'], $provider, 'login');
            Security::logSecurityEvent('2fa_success', [
                'user_id' => $user['id'],
                'provider' => $provider,
                'backup_code_used' => $usedBackupCode
            ]);
            
            // Очистка временных данных
            unset($_SESSION['pending_user_id']);
            unset($_SESSION['auth_provider']);
            
            // Если использован backup код, показываем предупреждение
            if ($usedBackupCode) {
                header('Location: ' . BASE_URL . '/profile/dashboard.php?backup_used=1');
            } else {
                header('Location: ' . BASE_URL . '/profile/dashboard.php');
            }
            exit;
        } else {
            $error = 'Неверный код. Попробуйте снова.';
            Security::logSecurityEvent('2fa_failed', [
                'user_id' => $user['id']
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Двухфакторная аутентификация</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .container {
            max-width: 450px;
            margin: 0 auto;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            width: 100%;
        }
        
        .verify-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(102, 192, 244, 0.1);
            animation: slideIn 0.5s ease-out;
            width: 100%;
        }
        
        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .verify-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .verify-header h1 {
            color: #66c0f4;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .verify-header p {
            color: #8f98a0;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .form-group {
            margin: 30px 0;
        }
        
        .form-label {
            display: block;
            color: #8f98a0;
            margin-bottom: 10px;
            font-size: 14px;
            text-align: center;
        }
        
        .form-input {
            width: 100%;
            padding: 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(102, 192, 244, 0.2);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(102, 192, 244, 0.5);
            box-shadow: 0 0 20px rgba(102, 192, 244, 0.2);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.6) 0%, rgba(91, 154, 196, 0.6) 100%);
            border: 1px solid rgba(102, 192, 244, 0.4);
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.8) 0%, rgba(91, 154, 196, 0.8) 100%);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.4);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .help-text {
            text-align: center;
            margin-top: 20px;
            color: #8f98a0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="container">
        <div class="verify-card">
            <div class="verify-header">
                <div class="verify-icon">🔐</div>
                <h1>Двухфакторная аутентификация</h1>
                <p>Введите 6-значный код из вашего приложения для аутентификации</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Код аутентификации:</label>
                    <input type="text" 
                           name="code" 
                           class="form-input" 
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           placeholder="000000" 
                           required 
                           autofocus
                           autocomplete="off">
                </div>
                
                <button type="submit" class="btn-submit">Подтвердить</button>
            </form>
            
            <div class="help-text">
                Откройте Google Authenticator или Authy на вашем телефоне<br>
                Или используйте один из резервных кодов
            </div>
        </div>
    </div>
</body>
</html>

