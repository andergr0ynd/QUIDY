<?php
require_once '../OAuth/config.php';
require_once '../OAuth/Database.php';
require_once '../version.php';

// Проверка авторизации
$user = null;
$oauthConnections = [];
if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
    
    if ($user) {
        // Устанавливаем ID пользователя в сессию для привязки аккаунтов
        $_SESSION['user_id'] = $user['id'];
        
        // Получаем OAuth подключения пользователя
        $oauthConnections = $db->getUserOAuthConnections($user['id']);
        
        // Получаем статус бота (с проверкой на существование таблицы)
        $botChannel = null;
        try {
            $botChannel = $db->getBotChannelByUserId($user['id']);
        } catch (Exception $e) {
            // Таблица bot_channels еще не создана
            $botChannel = null;
        }
    }
}

if (!$user) {
    // Пользователь не авторизован, перенаправляем на главную
    header('Location: ../index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Личный кабинет</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px 60px 20px;
        }
        
        .profile-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .profile-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(102, 192, 244, 0.3);
        }
        
        .profile-info h2 {
            color: #66c0f4;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: #8f98a0;
            font-size: 14px;
        }
        
        .profile-details {
            display: grid;
            gap: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        .detail-label {
            color: #8f98a0;
            font-size: 14px;
        }
        
        .detail-value {
            color: #c7d5e0;
            font-size: 14px;
        }
        
        .logout-btn {
            margin-top: 30px;
            padding: 12px 30px;
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.3) 0%, rgba(91, 154, 196, 0.3) 100%);
            border: 1px solid rgba(102, 192, 244, 0.4);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .logout-btn:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.5) 0%, rgba(91, 154, 196, 0.5) 100%);
            border-color: rgba(102, 192, 244, 0.6);
        }
        
        .danger-zone {
            margin-top: 40px;
            padding: 20px;
            background: rgba(194, 58, 58, 0.05);
            border: 1px solid rgba(194, 58, 58, 0.2);
            border-radius: 8px;
        }
        
        .danger-zone-title {
            color: #ff6b6b;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .danger-zone-text {
            color: #8f98a0;
            font-size: 13px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .btn-delete-account {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.4);
            border-radius: 4px;
            color: #ff6b6b;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-delete-account:hover {
            background: rgba(194, 58, 58, 0.3);
            border-color: rgba(194, 58, 58, 0.6);
        }
        
        .oauth-connections {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .oauth-title {
            color: #8f98a0;
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .oauth-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .oauth-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            border: 1px solid rgba(102, 192, 244, 0.1);
            transition: all 0.3s ease;
            gap: 15px;
        }
        
        .oauth-item:hover {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(102, 192, 244, 0.2);
        }
        
        .oauth-item-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .oauth-item-right {
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        
        .oauth-item-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        
        .oauth-item-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .oauth-item-name {
            color: #c7d5e0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .oauth-item-status {
            font-size: 13px;
        }
        
        .status-connected {
            color: #4caf50;
        }
        
        .status-disconnected {
            color: #8f98a0;
        }
        
        .btn-link, .btn-unlink {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            min-width: 100px;
            text-align: center;
        }
        
        .btn-link {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.6) 0%, rgba(91, 154, 196, 0.6) 100%);
            border: 1px solid rgba(102, 192, 244, 0.4);
            color: #fff;
            display: inline-block;
        }
        
        .btn-link:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.8) 0%, rgba(91, 154, 196, 0.8) 100%);
            transform: translateY(-1px);
        }
        
        .btn-unlink {
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.3);
            color: #ff6b6b;
        }
        
        .btn-unlink:hover {
            background: rgba(194, 58, 58, 0.3);
            border-color: rgba(194, 58, 58, 0.5);
        }
        
        .oauth-note {
            margin-top: 15px;
            padding: 12px;
            background: rgba(102, 192, 244, 0.1);
            border-left: 3px solid rgba(102, 192, 244, 0.5);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 13px;
        }
        
        .bot-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .bot-card {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            border: 1px solid rgba(102, 192, 244, 0.1);
            transition: all 0.3s ease;
            gap: 20px;
        }
        
        .bot-card:hover {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(102, 192, 244, 0.2);
        }
        
        .bot-info-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .bot-info-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .bot-icon {
            font-size: 40px;
        }
        
        .bot-details h3 {
            color: #c7d5e0;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .bot-status {
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .bot-status.connected {
            color: #4caf50;
        }
        
        .bot-status.disconnected {
            color: #8f98a0;
        }
        
        .bot-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-bot-connect, .btn-bot-disconnect, .btn-bot-settings {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            min-width: 120px;
        }
        
        .btn-bot-connect {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.6) 0%, rgba(91, 154, 196, 0.6) 100%);
            border: 1px solid rgba(102, 192, 244, 0.4);
            color: #fff;
        }
        
        .btn-bot-connect:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.8) 0%, rgba(91, 154, 196, 0.8) 100%);
            transform: translateY(-1px);
        }
        
        .btn-bot-settings {
            background: linear-gradient(90deg, rgba(156, 39, 176, 0.6) 0%, rgba(123, 31, 162, 0.6) 100%);
            border: 1px solid rgba(156, 39, 176, 0.4);
            color: #fff;
        }
        
        .btn-bot-settings:hover {
            background: linear-gradient(90deg, rgba(156, 39, 176, 0.8) 0%, rgba(123, 31, 162, 0.8) 100%);
            transform: translateY(-1px);
        }
        
        .btn-bot-disconnect {
            background: rgba(194, 58, 58, 0.2);
            border: 1px solid rgba(194, 58, 58, 0.3);
            color: #ff6b6b;
        }
        
        .btn-bot-disconnect:hover {
            background: rgba(194, 58, 58, 0.3);
            border-color: rgba(194, 58, 58, 0.5);
        }
        
        .bot-note {
            margin-top: 15px;
            padding: 12px;
            background: rgba(102, 192, 244, 0.1);
            border-left: 3px solid rgba(102, 192, 244, 0.5);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .security-section {
            margin-top: 20px;
        }
        
        .security-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(102, 192, 244, 0.2);
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .security-btn:hover {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(102, 192, 244, 0.4);
            transform: translateX(5px);
        }
        
        .security-icon {
            font-size: 24px;
        }
        
        .security-info {
            flex: 1;
        }
        
        .security-title {
            color: #c7d5e0;
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .security-status {
            font-size: 13px;
            color: #8f98a0;
        }
        
        .security-arrow {
            color: #66c0f4;
            font-size: 20px;
        }
        
        .version-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.9) 0%, rgba(255, 193, 7, 0.9) 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .version-indicator:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }
        
        .version-tooltip {
            position: absolute;
            bottom: 100%;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            font-size: 11px;
            white-space: nowrap;
            margin-bottom: 8px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .version-indicator:hover .version-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .version-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            right: 20px;
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #4caf50;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .warning-message {
            background: rgba(255, 152, 0, 0.2);
            border: 1px solid rgba(255, 152, 0, 0.4);
            color: #ff9800;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .backup-codes-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .backup-codes-content {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.98) 0%, rgba(20, 28, 38, 0.98) 100%);
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(102, 192, 244, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .backup-codes-content h3 {
            color: #66c0f4;
            font-size: 22px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .backup-codes-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 20px 0;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }
        
        .backup-code-item {
            background: rgba(102, 192, 244, 0.1);
            border: 1px solid rgba(102, 192, 244, 0.3);
            padding: 10px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #66c0f4;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .btn-download, .btn-print {
            width: calc(50% - 5px);
            padding: 10px;
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.4) 0%, rgba(91, 154, 196, 0.4) 100%);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .btn-download:hover, .btn-print:hover {
            background: linear-gradient(90deg, rgba(102, 192, 244, 0.6) 0%, rgba(91, 154, 196, 0.6) 100%);
        }
        
        .btn-close-modal {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, rgba(76, 175, 80, 0.6) 0%, rgba(56, 142, 60, 0.6) 100%);
            border: 1px solid rgba(76, 175, 80, 0.4);
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .btn-close-modal:hover {
            background: linear-gradient(90deg, rgba(76, 175, 80, 0.8) 0%, rgba(56, 142, 60, 0.8) 100%);
        }
        
        .backup-info {
            margin-top: 15px;
            padding: 12px;
            background: rgba(102, 192, 244, 0.1);
            border-left: 3px solid rgba(102, 192, 244, 0.5);
            border-radius: 4px;
            color: #c7d5e0;
            font-size: 13px;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .backup-codes-content, .backup-codes-content * {
                visibility: visible;
            }
            .backup-codes-content {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
            .btn-download, .btn-print, .btn-close-modal {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="dashboard-container">
        <div class="profile-card">
            <?php if (isset($_GET['2fa'])): ?>
                <?php if ($_GET['2fa'] === 'enabled'): ?>
                    <div class="success-message">✓ Двухфакторная аутентификация успешно включена!</div>
                <?php elseif ($_GET['2fa'] === 'disabled'): ?>
                    <div class="success-message">Двухфакторная аутентификация отключена</div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($_GET['backup_used'])): ?>
                <div class="warning-message">⚠️ Вы использовали резервный код для входа. Рекомендуем сгенерировать новые коды.</div>
            <?php endif; ?>
            
            <?php 
            // Показ backup кодов после включения 2FA или регенерации
            if (isset($_GET['show_codes']) && isset($_SESSION['new_backup_codes'])): 
                $codes = $_SESSION['new_backup_codes'];
                unset($_SESSION['new_backup_codes']); // Показываем только один раз
            ?>
                <div class="backup-codes-modal" id="backupCodesModal">
                    <div class="backup-codes-content">
                        <h3>🔐 Резервные коды для 2FA</h3>
                        <p style="color: #8f98a0; font-size: 14px; margin: 15px 0;">
                            Сохраните эти коды в безопасном месте. Каждый код можно использовать только один раз.
                        </p>
                        <div class="backup-codes-list">
                            <?php foreach ($codes as $code): ?>
                                <div class="backup-code-item"><?php echo htmlspecialchars($code); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <button onclick="downloadBackupCodes()" class="btn-download">📥 Скачать коды</button>
                        <button onclick="printBackupCodes()" class="btn-print">🖨️ Распечатать</button>
                        <button onclick="closeBackupModal()" class="btn-close-modal">Я сохранил коды</button>
                    </div>
                </div>
                <script>
                    const backupCodes = <?php echo json_encode($codes); ?>;
                </script>
            <?php endif; ?>
            
            <div class="profile-header">
                <?php if ($user['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar" style="background: linear-gradient(135deg, #66c0f4, #8bb4d0);"></div>
                <?php endif; ?>
                
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p>ID: <?php echo $user['id']; ?></p>
                </div>
            </div>
            
            <div class="profile-details">
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo $user['email'] ? htmlspecialchars($user['email']) : 'Не указан'; ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Статус:</span>
                    <span class="detail-value"><?php echo ucfirst($user['status']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Дата регистрации:</span>
                    <span class="detail-value"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="oauth-connections">
                <div class="oauth-title">Подключенные аккаунты</div>
                
                <?php
                // Получаем список всех провайдеров
                $allProviders = ['discord' => 'Discord', 'telegram' => 'Telegram', 'twitch' => 'Twitch'];
                $linkedProviders = [];
                foreach ($oauthConnections as $connection) {
                    $linkedProviders[] = $connection['provider'];
                }
                ?>
                
                <div class="oauth-list">
                    <?php foreach ($allProviders as $provider => $name): ?>
                        <?php $isLinked = in_array($provider, $linkedProviders); ?>
                        <div class="oauth-item <?php echo $provider; ?>">
                            <div class="oauth-item-left">
                                <img src="../assets/logo/<?php echo $provider; ?>.png" alt="<?php echo $name; ?>" class="oauth-item-icon">
                                <div class="oauth-item-info">
                                    <div class="oauth-item-name"><?php echo $name; ?></div>
                                    <div class="oauth-item-status">
                                        <?php if ($isLinked): ?>
                                            <span class="status-connected">✓ Подключен</span>
                                        <?php else: ?>
                                            <span class="status-disconnected">○ Не подключен</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="oauth-item-right">
                                <?php if ($isLinked): ?>
                                    <button class="btn-unlink" onclick="unlinkAccount('<?php echo $provider; ?>')">
                                        Отвязать
                                    </button>
                                <?php else: ?>
                                    <a href="../OAuth/link_<?php echo $provider; ?>.php" class="btn-link">
                                        Привязать
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($linkedProviders) > 1): ?>
                    <div class="oauth-note">
                        💡 Вы можете войти через любой из подключенных аккаунтов
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Секция Twitch бота -->
            <div class="bot-section">
                <div class="oauth-title">Twitch Бот</div>
                
                <div class="bot-card">
                    <div class="bot-info-left">
                        <div class="bot-icon">🤖</div>
                        <div class="bot-details">
                            <h3>Quidy Bot</h3>
                            <div class="bot-status <?php echo $botChannel ? 'connected' : 'disconnected'; ?>">
                                <?php if ($botChannel): ?>
                                    ✓ Бот подключен к каналу: <strong><?php echo htmlspecialchars($botChannel['twitch_username']); ?></strong>
                                <?php else: ?>
                                    ○ Бот не подключен
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="bot-info-right">
                        <?php if ($botChannel): ?>
                            <div class="bot-buttons">
                                <a href="bot_settings.php" class="btn-bot-settings">
                                    ⚙️ Управление
                                </a>
                                <button class="btn-bot-disconnect" onclick="disconnectBot()">
                                    Отключить бота
                                </button>
                            </div>
                        <?php else: ?>
                            <button class="btn-bot-connect" onclick="connectBot()">
                                Подключить бота
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$botChannel): ?>
                    <div class="bot-note">
                        💡 <strong>Как подключить бота:</strong><br>
                        1. Убедитесь, что ваш Twitch аккаунт привязан выше<br>
                        2. Нажмите кнопку "Подключить бота"<br>
                        3. Бот автоматически присоединится к вашему каналу<br>
                        4. Используйте команду <code>!ping</code> в чате для проверки
                    </div>
                <?php else: ?>
                    <div class="bot-note">
                        ✅ Бот активен на вашем канале! Доступная команда: <code>!ping</code>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="security-section">
                <a href="2fa_setup.php" class="security-btn">
                    <span class="security-icon">🔐</span>
                    <div class="security-info">
                        <div class="security-title">Двухфакторная аутентификация</div>
                        <div class="security-status">
                            <?php if ($user['two_factor_enabled']): ?>
                                <span style="color: #4caf50;">✓ Включена</span>
                            <?php else: ?>
                                <span style="color: #ff9800;">○ Отключена</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="security-arrow">→</span>
                </a>
            </div>
            
            <button class="logout-btn" onclick="logout()">Выйти из аккаунта</button>
            
            <div class="danger-zone">
                <div class="danger-zone-title">⚠️ Опасная зона</div>
                <p class="danger-zone-text">Удаление аккаунта необратимо. Все ваши данные будут безвозвратно удалены.</p>
                <a href="delete_account.php" class="btn-delete-account">Удалить аккаунт</a>
            </div>
        </div>
    </div>
    
    <script>
        function logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                window.location.href = '../OAuth/logout.php';
            }
        }
        
        function unlinkAccount(provider) {
            const names = {
                'discord': 'Discord',
                'telegram': 'Telegram',
                'twitch': 'Twitch'
            };
            
            if (confirm(`Вы уверены, что хотите отвязать аккаунт ${names[provider]}?`)) {
                fetch('../OAuth/unlink_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ provider: provider })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Ошибка при отвязке аккаунта');
                    }
                })
                .catch(error => {
                    alert('Ошибка при отвязке аккаунта');
                    console.error(error);
                });
            }
        }
        
        // Функции для работы с ботом
        function connectBot() {
            if (confirm('Подключить бота к вашему Twitch каналу?')) {
                fetch('../OAuth/bot_api.php?action=connect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Бот успешно подключен!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Ошибка при подключении бота', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка при подключении бота', 'error');
                    console.error(error);
                });
            }
        }
        
        function disconnectBot() {
            if (confirm('Отключить бота от вашего канала?')) {
                fetch('../OAuth/bot_api.php?action=disconnect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'Бот отключен', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(data.error || 'Ошибка при отключении бота', 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка при отключении бота', 'error');
                    console.error(error);
                });
            }
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            const bgColor = type === 'success' 
                ? 'linear-gradient(135deg, rgba(76, 175, 80, 0.95) 0%, rgba(56, 142, 60, 0.95) 100%)'
                : 'linear-gradient(135deg, rgba(194, 58, 58, 0.95) 0%, rgba(153, 46, 46, 0.95) 100%)';
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
                z-index: 10000;
                font-size: 14px;
                animation: slideInRight 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        }
        
        // Проверяем URL на наличие параметров
        const urlParams = new URLSearchParams(window.location.search);
        const linked = urlParams.get('linked');
        const error = urlParams.get('error');
        
        if (linked) {
            const names = {
                'discord': 'Discord',
                'telegram': 'Telegram',
                'twitch': 'Twitch'
            };
            
            // Создаем уведомление об успехе
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, rgba(76, 175, 80, 0.95) 0%, rgba(56, 142, 60, 0.95) 100%);
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
                z-index: 10000;
                font-size: 14px;
                animation: slideInRight 0.3s ease-out;
            `;
            notification.textContent = `✓ Аккаунт ${names[linked]} успешно привязан!`;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
            
            // Убираем параметр из URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (error === 'already_linked') {
            showError('⚠️ Этот аккаунт уже привязан');
        } else if (error === 'link_failed') {
            showError('⚠️ Ошибка при привязке аккаунта. Возможно, он уже используется другим пользователем.');
        }
        
        function showError(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, rgba(194, 58, 58, 0.95) 0%, rgba(153, 46, 46, 0.95) 100%);
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
                z-index: 10000;
                font-size: 14px;
                animation: slideInRight 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
            
            // Убираем параметр из URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        // Функции для работы с backup кодами
        function downloadBackupCodes() {
            if (typeof backupCodes === 'undefined') return;
            
            const content = 'Quidy.ru - Резервные коды 2FA\n' +
                           '================================\n' +
                           'Дата: ' + new Date().toLocaleString('ru-RU') + '\n\n' +
                           'Сохраните эти коды в безопасном месте!\n' +
                           'Каждый код можно использовать только один раз.\n\n' +
                           backupCodes.join('\n') + '\n\n' +
                           '================================\n' +
                           'ВАЖНО: Не делитесь этими кодами ни с кем!';
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'quidy-backup-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function printBackupCodes() {
            window.print();
        }
        
        function closeBackupModal() {
            const modal = document.getElementById('backupCodesModal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                    // Убираем параметр show_codes из URL
                    const url = new URL(window.location);
                    url.searchParams.delete('show_codes');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }, 300);
            }
        }
        
        // Добавляем анимацию fadeOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
    
    <!-- Подключение виджета времени -->
    <script src="../assets/js/time-widget.js?v=<?php echo time(); ?>"></script>
    
    <!-- Индикатор версии -->
    <div class="version-indicator">
        <div class="version-tooltip">
            Сборка <?= VersionManager::getBuildNumber() ?><br>
            Дата: <?= VersionManager::getBuildDate() ?>
        </div>
        <?= VersionManager::getFullVersion() ?>
    </div>
</body>
</html>

