<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Telegram Login</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .telegram-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .telegram-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(102, 192, 244, 0.1);
            text-align: center;
            width: 100%;
        }
        
        .telegram-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2aabee, #229ed9);
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(42, 171, 238, 0.3);
        }
        
        .telegram-card h1 {
            color: #66c0f4;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .telegram-card p {
            color: #8f98a0;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .telegram-widget-container {
            display: flex;
            justify-content: center;
            margin: 30px 0;
            min-height: 46px;
        }
        
        .back-link {
            margin-top: 20px;
        }
        
        .back-link a {
            color: #66c0f4;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .features {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(102, 192, 244, 0.1);
            text-align: left;
        }
        
        .features h3 {
            color: #66c0f4;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #c7d5e0;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .feature-icon {
            width: 24px;
            height: 24px;
            background: rgba(42, 171, 238, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2aabee;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="telegram-container">
        <div class="telegram-card">
            <div class="telegram-logo">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18 1.897-.962 6.502-1.359 8.627-.168.9-.5 1.201-.82 1.23-.697.064-1.226-.461-1.901-.903-1.056-.693-1.653-1.124-2.678-1.8-1.185-.781-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.139-5.062 3.345-.479.329-.913.489-1.302.481-.428-.008-1.252-.241-1.865-.44-.752-.244-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.831-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.477-1.635.099-.002.321.023.465.14.12.098.153.229.169.324-.002.05.01.14.015.21z"/>
                </svg>
            </div>
            
            <h1>Вход через Telegram</h1>
            <p>Войдите через свой Telegram аккаунт для доступа к платформе</p>
            
            <div class="telegram-widget-container">
                <script async src="https://telegram.org/js/telegram-widget.js?22" 
                        data-telegram-login="<?php echo TELEGRAM_BOT_USERNAME; ?>" 
                        data-size="large" 
                        data-radius="8"
                        data-auth-url="<?php echo BASE_URL; ?>/OAuth/telegram_callback.php" 
                        data-request-access="write">
                </script>
            </div>
            
            <div class="features">
                <h3>Что вы получите:</h3>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Быстрая и безопасная авторизация</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Доступ к личному кабинету</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Управление профилем и настройками</span>
                </div>
            </div>
            
            <div class="back-link">
                <a href="../index.html">← Вернуться на главную</a>
            </div>
        </div>
    </div>
</body>
</html>

