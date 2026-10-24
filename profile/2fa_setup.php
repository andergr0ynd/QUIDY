<?php
require_once '../OAuth/config.php';
require_once '../OAuth/Database.php';
require_once '../OAuth/TOTP.php';

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

// Обработка запросов
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'enable') {
        // Включение 2FA
        $secret = $_POST['secret'] ?? '';
        $code = $_POST['code'] ?? '';
        
        if (TOTP::verifyCode($secret, $code)) {
            if ($db->enable2FA($user['id'], $secret)) {
                // Генерируем backup коды
                $backupCodes = $db->generateBackupCodes($user['id'], 8);
                
                // Сохраняем коды в сессию для показа пользователю
                $_SESSION['new_backup_codes'] = $backupCodes;
                
                header('Location: dashboard.php?2fa=enabled&show_codes=1');
                exit;
            } else {
                $error = 'Ошибка при сохранении настроек';
            }
        } else {
            $error = 'Неверный код. Попробуйте снова.';
        }
    } elseif ($action === 'disable') {
        // Отключение 2FA
        $code = $_POST['code'] ?? '';
        
        if (TOTP::verifyCode($user['two_factor_secret'], $code)) {
            if ($db->disable2FA($user['id'])) {
                // Удаляем все backup коды при отключении 2FA
                $conn = $db->getConnection();
                $stmt = $conn->prepare("DELETE FROM backup_codes WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                
                header('Location: dashboard.php?2fa=disabled');
                exit;
            } else {
                $error = 'Ошибка при отключении 2FA';
            }
        } else {
            $error = 'Неверный код. Попробуйте снова.';
        }
    } elseif ($action === 'regenerate') {
        // Регенерация backup кодов
        if ($user['two_factor_enabled']) {
            $backupCodes = $db->generateBackupCodes($user['id'], 8);
            $_SESSION['new_backup_codes'] = $backupCodes;
            header('Location: dashboard.php?show_codes=1');
            exit;
        }
    }
}

// Генерация нового секрета для включения 2FA
$newSecret = TOTP::generateSecret();
$qrCodeUrl = TOTP::getQRCodeUrl($newSecret, $user['username']);
$otpauthUrl = TOTP::getOtpauthUrl($newSecret, $user['username']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Настройка 2FA</title>
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
            padding: 40px 20px 60px 20px;
        }
        
        .setup-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .setup-header h1 {
            color: #66c0f4;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .setup-header p {
            color: #8f98a0;
            font-size: 14px;
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
        
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        
        .qr-code {
            margin: 15px 0;
        }
        
        .qr-code img {
            max-width: 180px;
            height: auto;
        }
        
        .secret-key {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            color: #66c0f4;
            font-size: 16px;
        }
        
        .copy-button {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(102, 192, 244, 0.2);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 4px;
            color: #66c0f4;
            font-size: 12px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .copy-button:hover {
            background: rgba(102, 192, 244, 0.3);
            border-color: rgba(102, 192, 244, 0.5);
        }
        
        .instruction {
            color: #c7d5e0;
            line-height: 1.5;
            margin: 15px 0;
            font-size: 14px;
        }
        
        .instruction h3 {
            font-size: 18px;
        }
        
        .instruction ol {
            margin-left: 20px;
        }
        
        .instruction li {
            margin: 8px 0;
        }
        
        .instruction p {
            margin: 8px 0;
        }
        
        .form-group {
            margin: 15px 0;
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
            border: 1px solid rgba(102, 192, 244, 0.2);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 16px;
            text-align: center;
            letter-spacing: 4px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(102, 192, 244, 0.5);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.6) 0%, rgba(91, 154, 196, 0.6) 100%);
            color: #fff;
            border: 1px solid rgba(102, 192, 244, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.8) 0%, rgba(91, 154, 196, 0.8) 100%);
        }
        
        .btn-danger {
            background: linear-gradient(90deg, rgba(194, 58, 58, 0.6) 0%, rgba(153, 46, 46, 0.6) 100%);
            color: #fff;
            border: 1px solid rgba(194, 58, 58, 0.4);
        }
        
        .btn-danger:hover {
            background: linear-gradient(90deg, rgba(194, 58, 58, 0.8) 0%, rgba(153, 46, 46, 0.8) 100%);
        }
        
        .error-message {
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.4);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-enabled {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #4caf50;
        }
        
        .status-disabled {
            background: rgba(158, 158, 158, 0.2);
            border: 1px solid rgba(158, 158, 158, 0.4);
            color: #9e9e9e;
        }
        
        /* Адаптивность */
        @media (max-width: 600px) {
            .container {
                padding: 20px 15px 40px 15px;
            }
            
            .setup-card {
                padding: 20px;
            }
            
            .setup-header h1 {
                font-size: 24px;
            }
            
            .instruction {
                font-size: 13px;
            }
            
            .instruction h3 {
                font-size: 16px;
            }
            
            .qr-code img {
                max-width: 150px;
            }
            
            .secret-key {
                font-size: 14px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="container">
        <a href="dashboard.php" class="back-link">← Вернуться в профиль</a>
        
        <div class="setup-card">
            <div class="setup-header">
                <h1>Двухфакторная аутентификация</h1>
                <p>
                    <?php if ($user['two_factor_enabled']): ?>
                        <span class="status-badge status-enabled">ВКЛЮЧЕНА</span>
                    <?php else: ?>
                        <span class="status-badge status-disabled">ОТКЛЮЧЕНА</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!$user['two_factor_enabled']): ?>
                <!-- Включение 2FA -->
                <div class="instruction">
                    <h3 style="color: #66c0f4; margin-bottom: 15px;">Как включить 2FA:</h3>
                    <ol>
                        <li>Установите приложение Google Authenticator или Authy на ваш телефон</li>
                        <li>Отсканируйте QR-код ниже или введите секретный ключ вручную</li>
                        <li>Введите 6-значный код из приложения</li>
                        <li>Нажмите "Включить 2FA"</li>
                    </ol>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">
                        <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" loading="lazy">
                    </div>
                    <p style="color: #8f98a0; margin-bottom: 8px; font-size: 13px;">Секретный ключ:</p>
                    <div class="secret-key" id="secretKey"><?php echo chunk_split($newSecret, 4, ' '); ?></div>
                    <button type="button" class="copy-button" onclick="copySecret('<?php echo $newSecret; ?>')">
                        📋 Скопировать ключ
                    </button>
                </div>
                
                <form method="POST" action="?action=enable">
                    <input type="hidden" name="secret" value="<?php echo $newSecret; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Введите код из приложения:</label>
                        <input type="text" name="code" class="form-input" maxlength="6" pattern="[0-9]{6}" 
                               placeholder="000000" required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Включить 2FA</button>
                </form>
                
            <?php else: ?>
                <!-- Отключение 2FA -->
                <div class="instruction">
                    <h3 style="color: #ff6b6b; margin-bottom: 15px;">Отключение 2FA</h3>
                    <p>Двухфакторная аутентификация защищает ваш аккаунт от несанкционированного доступа.</p>
                    <p>Вы уверены, что хотите отключить её?</p>
                </div>
                
                <form method="POST" action="?action=disable">
                    <div class="form-group">
                        <label class="form-label">Введите код из приложения для подтверждения:</label>
                        <input type="text" name="code" class="form-input" maxlength="6" pattern="[0-9]{6}" 
                               placeholder="000000" required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Вы действительно хотите отключить 2FA?')">
                        Отключить 2FA
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copySecret(secret) {
            // Копируем секретный ключ в буфер обмена
            navigator.clipboard.writeText(secret).then(function() {
                // Визуальная обратная связь
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '✓ Скопировано!';
                button.style.background = 'rgba(76, 175, 80, 0.3)';
                button.style.borderColor = 'rgba(76, 175, 80, 0.5)';
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    button.style.borderColor = '';
                }, 2000);
            }).catch(function(err) {
                alert('Ошибка копирования: ' + err);
            });
        }
    </script>
</body>
</html>

