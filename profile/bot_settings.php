<?php
require_once '../OAuth/config.php';
require_once '../OAuth/Database.php';

// Проверка авторизации
$user = null;
$botChannel = null;

if (isset($_COOKIE['session_token'])) {
    $db = Database::getInstance();
    $user = $db->getUserBySession($_COOKIE['session_token']);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        
        // Получаем данные бота
        try {
            $botChannel = $db->getBotChannelByUserId($user['id']);
        } catch (Exception $e) {
            $botChannel = null;
        }
    }
}

if (!$user) {
    header('Location: ../index.html');
    exit;
}

if (!$botChannel) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Управление ботом</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px 60px 20px;
        }
        
        .settings-card {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.98) 0%, rgba(20, 28, 38, 0.98) 100%);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(102, 192, 244, 0.1);
            border: 1px solid rgba(102, 192, 244, 0.2);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .settings-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #66c0f4, #9146FF, #66c0f4);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(102, 192, 244, 0.3);
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .settings-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(102, 192, 244, 0.1);
        }
        
        .settings-header h1 {
            color: #66c0f4;
            margin: 0;
            font-size: 28px;
        }
        
        .back-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, rgba(102, 192, 244, 0.2) 0%, rgba(102, 192, 244, 0.1) 100%);
            border: 1px solid rgba(102, 192, 244, 0.4);
            border-radius: 8px;
            color: #66c0f4;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 192, 244, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .back-btn:hover::before {
            left: 100%;
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, rgba(102, 192, 244, 0.3) 0%, rgba(102, 192, 244, 0.2) 100%);
            border-color: rgba(102, 192, 244, 0.6);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.3);
        }
        
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-primary {
            padding: 12px 24px;
            background: linear-gradient(135deg, #66c0f4 0%, #4a9eff 100%);
            border: 1px solid rgba(102, 192, 244, 0.5);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(102, 192, 244, 0.3);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #4a9eff 0%, #66c0f4 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.5);
        }
        
        .section-title {
            color: #c7d5e0;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            gap: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 18px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(20, 28, 38, 0.3) 100%);
            border-radius: 8px;
            border: 1px solid rgba(102, 192, 244, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-row::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 192, 244, 0.05), transparent);
            transition: left 0.5s ease;
        }
        
        .info-row:hover::before {
            left: 100%;
        }
        
        .info-row:hover {
            border-color: rgba(102, 192, 244, 0.3);
            transform: translateX(3px);
        }
        
        .info-label {
            color: #8f98a0;
            font-size: 14px;
        }
        
        .info-value {
            color: #c7d5e0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .commands-list {
            display: grid;
            gap: 12px;
        }
        
        .command-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(20, 28, 38, 0.3) 100%);
            border-radius: 8px;
            border: 1px solid rgba(102, 192, 244, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .command-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 192, 244, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .command-item:hover::before {
            left: 100%;
        }
        
        .command-item:hover {
            transform: translateX(5px);
            border-color: rgba(102, 192, 244, 0.4);
            box-shadow: 0 5px 20px rgba(102, 192, 244, 0.2);
        }
        
        .command-name {
            color: #66c0f4;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: 600;
        }
        
        .command-desc {
            color: #8f98a0;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .coming-soon {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.15) 0%, rgba(255, 152, 0, 0.05) 100%);
            border: 1px solid rgba(255, 152, 0, 0.4);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-top: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .coming-soon::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 152, 0, 0.1) 50%, transparent 70%);
            animation: shimmer-gold 3s ease-in-out infinite;
        }
        
        .coming-soon:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 152, 0, 0.2);
            border-color: rgba(255, 152, 0, 0.6);
        }
        
        @keyframes shimmer-gold {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .coming-soon-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .coming-soon-text {
            color: #ff9800;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .coming-soon-desc {
            color: #8f98a0;
            font-size: 14px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3) 0%, rgba(20, 28, 38, 0.3) 100%);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(102, 192, 244, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 192, 244, 0.05) 0%, rgba(145, 70, 255, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(102, 192, 244, 0.4);
            box-shadow: 0 10px 30px rgba(102, 192, 244, 0.2);
        }
        
        .stat-value {
            color: #66c0f4;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #8f98a0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Стили для панели чата Twitch */
        .twitch-chat-toggle {
            position: fixed;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
            background: linear-gradient(90deg, #9146FF 0%, #6B2F8C 100%);
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 20px 10px;
            color: white;
            font-size: 20px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-weight: 600;
        }
        
        .twitch-chat-toggle:hover {
            background: linear-gradient(90deg, #a052ff 0%, #7b3ba3 100%);
            transform: translateY(-50%) scale(1.05);
        }
        
        .twitch-chat-toggle.open {
            left: 400px;
        }
        
        .twitch-chat-panel {
            position: fixed;
            top: 0;
            left: -400px;
            width: 400px;
            height: 100vh;
            background: linear-gradient(180deg, rgba(20, 28, 38, 0.98) 0%, rgba(14, 22, 33, 0.98) 100%);
            border-right: 1px solid rgba(102, 192, 244, 0.2);
            z-index: 999;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.5);
        }
        
        .twitch-chat-panel.open {
            left: 0;
        }
        
        .chat-header {
            padding: 20px;
            background: linear-gradient(135deg, #9146FF 0%, #6B2F8C 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-close-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chat-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .chat-iframe-container {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        
        .chat-iframe {
            width: 100%;
            height: calc(100% - 90px);
            border: none;
        }
        
        .chat-send-container {
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(102, 192, 244, 0.2);
        }
        
        .chat-send-box {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: rgba(102, 192, 244, 0.6);
            background: rgba(0, 0, 0, 0.6);
        }
        
        .chat-input::placeholder {
            color: #8f98a0;
        }
        
        .chat-send-btn {
            padding: 10px 20px;
            background: linear-gradient(90deg, #9146FF 0%, #6B2F8C 100%);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 50px;
        }
        
        .chat-send-btn:hover {
            background: linear-gradient(90deg, #a052ff 0%, #7b3ba3 100%);
            transform: translateY(-1px);
        }
        
        .chat-send-btn:active {
            transform: translateY(0);
        }
        
        .chat-help-text {
            font-size: 12px;
            color: #8f98a0;
            text-align: center;
            line-height: 1.4;
        }
        
        .main-content {
            transition: margin-left 0.3s ease;
        }
        
        .main-content.shifted {
            margin-left: 400px;
        }
        
        /* Модальное окно для команд */
        .commands-modal {
            display: none !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 99999;
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }
        
        .commands-modal[style*="display: flex"] {
            display: flex !important;
        }
        
        .commands-modal.open {
            display: flex !important;
        }
        
        .commands-modal-content {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.98) 0%, rgba(20, 28, 38, 0.98) 100%);
            border-radius: 16px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            min-height: 400px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(102, 192, 244, 0.3);
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
        }
        
        .commands-modal-header {
            background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(102, 192, 244, 0.2);
        }
        
        .commands-modal-title {
            color: #66c0f4;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .commands-modal-close {
            background: rgba(102, 192, 244, 0.2);
            border: 1px solid rgba(102, 192, 244, 0.3);
            color: #66c0f4;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .commands-modal-close:hover {
            background: rgba(102, 192, 244, 0.3);
            border-color: rgba(102, 192, 244, 0.5);
            transform: scale(1.05);
        }
        
        .commands-modal-body {
            padding: 30px;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }
        
        .commands-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .commands-list-section {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(102, 192, 244, 0.2);
        }
        
        .commands-sidebar-section {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 25px;
            border: 1px solid rgba(102, 192, 244, 0.2);
            height: fit-content;
        }
        
        .command-item-modal {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.4) 0%, rgba(20, 28, 38, 0.4) 100%);
            border: 1px solid rgba(102, 192, 244, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .command-item-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 192, 244, 0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .command-item-modal:hover::before {
            left: 100%;
        }
        
        .command-item-modal:hover {
            border-color: rgba(102, 192, 244, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.2);
        }
        
        .command-header-modal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .command-name-modal {
            color: #66c0f4;
            font-size: 18px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        
        .command-status-modal {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge-modal {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active-modal {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .status-inactive-modal {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }
        
        .permission-badge-modal {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(102, 192, 244, 0.2);
            color: #66c0f4;
            border: 1px solid rgba(102, 192, 244, 0.3);
        }
        
        .command-text-modal {
            color: #c7d5e0;
            margin: 10px 0;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .command-meta-modal {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #8f98a0;
            margin-top: 10px;
        }
        
        .command-actions-modal {
            display: flex;
            gap: 8px;
        }
        
        .btn-modal {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-primary-modal {
            background: linear-gradient(135deg, #66c0f4 0%, #4a9eff 100%);
            color: white;
        }
        
        .btn-primary-modal:hover {
            background: linear-gradient(135deg, #4a9eff 0%, #66c0f4 100%);
            transform: translateY(-1px);
        }
        
        .btn-secondary-modal {
            background: rgba(102, 192, 244, 0.2);
            color: #66c0f4;
            border: 1px solid rgba(102, 192, 244, 0.3);
        }
        
        .btn-secondary-modal:hover {
            background: rgba(102, 192, 244, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-danger-modal {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }
        
        .btn-danger-modal:hover {
            background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
            transform: translateY(-1px);
        }
        
        .form-group-modal {
            margin-bottom: 20px;
        }
        
        .form-label-modal {
            display: block;
            color: #c7d5e0;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input-modal {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 8px;
            color: #c7d5e0;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-input-modal:focus {
            outline: none;
            border-color: #66c0f4;
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 0 2px rgba(102, 192, 244, 0.2);
        }
        
        .form-textarea-modal {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select-modal {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(102, 192, 244, 0.3);
            border-radius: 8px;
            color: #c7d5e0;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-select-modal:focus {
            outline: none;
            border-color: #66c0f4;
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 0 2px rgba(102, 192, 244, 0.2);
        }
        
        .tts-settings-modal {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(102, 192, 244, 0.2);
        }
        
        .tts-title-modal {
            color: #66c0f4;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Переключатель TTS удален */
        
        /* Переключатель TTS удален */
        
        .empty-state-modal {
            text-align: center;
            padding: 40px 20px;
            color: #8f98a0;
        }
        
        .empty-state-icon-modal {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .error-message-modal {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(244, 67, 54, 0.2);
            margin-bottom: 20px;
            display: none;
        }
        
        .success-message-modal {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(76, 175, 80, 0.2);
            margin-bottom: 20px;
            display: none;
        }
        
        .loading-modal {
            display: none;
            text-align: center;
            padding: 20px;
            color: #8f98a0;
        }
        
        /* Кнопка команд вместо виджета времени */
        .commands-widget-trigger {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #66c0f4 0%, #4a9eff 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 192, 244, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .commands-widget-trigger:hover {
            background: linear-gradient(135deg, #4a9eff 0%, #66c0f4 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.5);
        }
        
        .commands-widget-trigger:active {
            transform: translateY(0);
        }
        
        .commands-widget-icon {
            font-size: 18px;
        }
        
        /* Модальное окно команды удалено */
        
        /* Адаптивность для мобильных устройств */
        @media (max-width: 768px) {
            .twitch-chat-panel {
                width: 100%;
                left: -100%;
            }
            
            .twitch-chat-toggle.open {
                left: 0;
            }
            
            .main-content.shifted {
                margin-left: 0;
            }
            
            .chat-header h3 {
                font-size: 16px;
            }
            
            .twitch-chat-toggle {
                padding: 15px 8px;
                font-size: 16px;
            }
            
            .commands-modal-content {
                width: 98%;
                margin: 10px;
            }
            
            .commands-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .commands-modal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <!-- Кнопка открытия чата -->
    <button class="twitch-chat-toggle" id="chatToggle" onclick="toggleChat()">
        💬 Twitch Chat
    </button>
    
    <!-- Панель чата Twitch -->
    <div class="twitch-chat-panel" id="chatPanel">
        <div class="chat-header">
            <h3>
                <span>💬</span> Twitch Chat
            </h3>
            <button class="chat-close-btn" onclick="toggleChat()">✕</button>
        </div>
        <div class="chat-iframe-container">
            <iframe 
                class="chat-iframe" 
                id="twitch-chat-embed"
                src="https://www.twitch.tv/embed/<?php echo htmlspecialchars($botChannel['twitch_username']); ?>/chat?parent=<?php echo $_SERVER['HTTP_HOST']; ?>&darkpopout" 
                title="Twitch Chat"
                frameborder="0"
                scrolling="yes"
                allowtransparency="true">
            </iframe>
        </div>
        
        <!-- Поле для отправки сообщений от бота -->
        <div class="chat-send-container">
            <div class="chat-send-box">
                <input 
                    type="text" 
                    id="botMessageInput" 
                    class="chat-input" 
                    placeholder="Написать сообщение от бота..." 
                    maxlength="500"
                >
                <button id="sendBotMessage" class="chat-send-btn">
                    <span>📤</span>
                </button>
            </div>
            <div class="chat-help-text">
                💡 Сообщение будет отправлено от имени вашего бота в чат
            </div>
        </div>
    </div>
    
    <div class="settings-container main-content" id="mainContent">
        <div class="settings-card">
            <div class="settings-header">
                <h1>⚙️ Управление ботом</h1>
                <div class="header-buttons">
                    <a href="dashboard.php" class="back-btn">← Назад</a>
                </div>
            </div>
            
            <!-- Информация о боте -->
            <div class="section-title">
                <span>🤖</span> Информация о боте
            </div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Название канала:</span>
                    <span class="info-value"><?php echo htmlspecialchars($botChannel['twitch_username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Статус:</span>
                    <span class="status-badge status-active">✓ Активен</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Дата подключения:</span>
                    <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($botChannel['created_at'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Twitch User ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($botChannel['twitch_user_id']); ?></span>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="section-title" style="margin-top: 30px;">
                <span>📊</span> Статистика
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">1</div>
                    <div class="stat-label">Команд</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Работает</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">2 мин</div>
                    <div class="stat-label">Обновление</div>
                </div>
            </div>
        </div>
        
        <div class="settings-card">
            <!-- Доступные команды -->
            <div class="section-title">
                <span>💬</span> Доступные команды
            </div>
            <div class="commands-list">
                <div class="command-item">
                    <div>
                        <div class="command-name">!ping</div>
                        <div class="command-desc">Проверка работы бота</div>
                    </div>
                    <span class="status-badge status-active">Активна</span>
                </div>
            </div>
            
            <!-- Кастомные команды (скоро) -->
            <div class="coming-soon">
                <div class="coming-soon-icon">🚀</div>
                <div class="coming-soon-text">Кастомные команды - скоро!</div>
                <div class="coming-soon-desc">
                    В следующем обновлении вы сможете создавать свои команды для бота:<br>
                    • Текстовые ответы<br>
                    • Таймеры для команд<br>
                    • Счетчики<br>
                    • И многое другое!
                </div>
            </div>
        </div>
        
        <div class="settings-card">
            <!-- Помощь -->
            <div class="section-title">
                <span>❓</span> Справка
            </div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Как использовать команды?</span>
                    <span class="info-value">Напишите команду в чате Twitch</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Бот не отвечает?</span>
                    <span class="info-value">Подождите 2 минуты после подключения</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Как отключить бота?</span>
                    <span class="info-value">Нажмите "Отключить" в личном кабинете</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для управления командами -->
    <div id="commandsModal" class="commands-modal">
        <div class="commands-modal-content">
            <div class="commands-modal-header">
                <h2 class="commands-modal-title">
                    <span>🎮</span> Управление командами
                </h2>
                <button class="commands-modal-close" onclick="closeCommandsModal()">✕</button>
            </div>
            <div class="commands-modal-body">
                <div class="commands-grid">
                    <div class="commands-list-section">
                        <h3 style="color: #c7d5e0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <span>📋</span> Список команд
                        </h3>
                        
                        <div class="error-message-modal" id="errorMessageModal"></div>
                        <div class="success-message-modal" id="successMessageModal"></div>
                        
                        <div class="loading-modal" id="loadingCommandsModal">
                            <div>Загрузка команд...</div>
                        </div>
                        
                        <div id="commandsListModal"></div>
                    </div>
                    
                        
                        <div style="margin-top: 20px;">
                            <button class="btn btn-primary" onclick="console.log('Кнопка нажата!'); openAddCommandModal();" style="width: 100%; margin-bottom: 10px;">
                                ➕ Добавить команду
                            </button>
                            <button class="btn btn-secondary" onclick="createTestCommand()" style="width: 100%;">
                                🧪 Создать тестовую команду
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления/редактирования команды -->
    <div id="addCommandModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, rgba(30, 40, 54, 0.98) 0%, rgba(20, 28, 38, 0.98) 100%); border-radius: 16px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8); border: 1px solid rgba(102, 192, 244, 0.3); position: relative; z-index: 1;">
            <div style="background: linear-gradient(135deg, rgba(30, 40, 54, 0.95) 0%, rgba(20, 28, 38, 0.95) 100%); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(102, 192, 244, 0.2);">
                <h2 style="color: #66c0f4; font-size: 24px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px;">
                    <span>➕</span> Добавить команду
                </h2>
                <button onclick="closeAddCommandModal()" style="background: rgba(102, 192, 244, 0.2); border: 1px solid rgba(102, 192, 244, 0.3); color: #66c0f4; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-size: 18px; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(102, 192, 244, 0.3)'; this.style.borderColor='rgba(102, 192, 244, 0.5)'; this.style.transform='scale(1.05)'" onmouseout="this.style.background='rgba(102, 192, 244, 0.2)'; this.style.borderColor='rgba(102, 192, 244, 0.3)'; this.style.transform='scale(1)'">✕</button>
            </div>
            <div style="padding: 30px; max-height: calc(90vh - 100px); overflow-y: auto;">
                <form id="addCommandForm">
                    <div style="margin-bottom: 20px;">
                        <label style="color: #c7d5e0; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">Название команды</label>
                        <input type="text" id="addCommandName" style="width: 100%; padding: 12px 16px; border: 1px solid rgba(102, 192, 244, 0.3); border-radius: 8px; background: rgba(42, 52, 65, 0.8); color: #c7d5e0; font-size: 16px; transition: all 0.3s ease; box-sizing: border-box;" placeholder="!info" required onfocus="this.style.borderColor='rgba(102, 192, 244, 0.6)'; this.style.boxShadow='0 0 0 3px rgba(102, 192, 244, 0.1)'" onblur="this.style.borderColor='rgba(102, 192, 244, 0.3)'; this.style.boxShadow='none'">
                        <small style="color: #8f98a0; font-size: 12px; margin-top: 5px; display: block;">Должно начинаться с ! и содержать только буквы, цифры и _</small>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="color: #c7d5e0; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">Текст команды</label>
                        <textarea id="addCommandText" style="width: 100%; padding: 12px 16px; border: 1px solid rgba(102, 192, 244, 0.3); border-radius: 8px; background: rgba(42, 52, 65, 0.8); color: #c7d5e0; font-size: 16px; min-height: 100px; resize: vertical; transition: all 0.3s ease; box-sizing: border-box; font-family: inherit;" placeholder="Введите текст, который будет отправлен в чат..." required onfocus="this.style.borderColor='rgba(102, 192, 244, 0.6)'; this.style.boxShadow='0 0 0 3px rgba(102, 192, 244, 0.1)'" onblur="this.style.borderColor='rgba(102, 192, 244, 0.3)'; this.style.boxShadow='none'"></textarea>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="color: #c7d5e0; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">Уровень доступа</label>
                        <select id="addCommandPermission" style="width: 100%; padding: 12px 16px; border: 1px solid rgba(102, 192, 244, 0.3); border-radius: 8px; background: rgba(42, 52, 65, 0.8); color: #c7d5e0; font-size: 16px; transition: all 0.3s ease; box-sizing: border-box;" onfocus="this.style.borderColor='rgba(102, 192, 244, 0.6)'; this.style.boxShadow='0 0 0 3px rgba(102, 192, 244, 0.1)'" onblur="this.style.borderColor='rgba(102, 192, 244, 0.3)'; this.style.boxShadow='none'">
                            <option value="everyone">Все пользователи</option>
                            <option value="moderator">Только модераторы</option>
                            <option value="vip">VIP и модераторы</option>
                            <option value="subscriber">Подписчики и выше</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="color: #c7d5e0; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">Кулдаун (секунды)</label>
                        <input type="number" id="addCommandCooldown" style="width: 100%; padding: 12px 16px; border: 1px solid rgba(102, 192, 244, 0.3); border-radius: 8px; background: rgba(42, 52, 65, 0.8); color: #c7d5e0; font-size: 16px; transition: all 0.3s ease; box-sizing: border-box;" min="0" max="3600" value="0" onfocus="this.style.borderColor='rgba(102, 192, 244, 0.6)'; this.style.boxShadow='0 0 0 3px rgba(102, 192, 244, 0.1)'" onblur="this.style.borderColor='rgba(102, 192, 244, 0.3)'; this.style.boxShadow='none'">
                        <small style="color: #8f98a0; font-size: 12px; margin-top: 5px; display: block;">0 = без кулдауна</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" onclick="closeAddCommandModal()" style="background: linear-gradient(135deg, #8f98a0 0%, #6b7c85 100%); color: #1e2836; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(143, 152, 160, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">Отмена</button>
                        <button type="submit" style="background: linear-gradient(135deg, #66c0f4 0%, #4a9fd1 100%); color: #1e2836; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(102, 192, 244, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <script>
        // Модальное окно команды удалено
        
        // Принудительно закрываем модальные окна при загрузке
        window.addEventListener('load', function() {
            const commandsModal = document.getElementById('commandsModal');
            if (commandsModal) {
                commandsModal.classList.remove('open');
            }
            
            // Дополнительный обработчик для кнопки команд
            const commandsBtn = document.getElementById('commandsWidgetBtn');
            if (commandsBtn) {
                console.log('Кнопка команд найдена при загрузке');
                commandsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Кнопка команд нажата (обработчик загрузки)!');
                    openCommandsModal();
                });
            } else {
                console.error('Кнопка команд не найдена при загрузке!');
            }
        });
        
        function toggleChat() {
            const chatPanel = document.getElementById('chatPanel');
            const chatToggle = document.getElementById('chatToggle');
            const mainContent = document.getElementById('mainContent');
            
            const isOpen = chatPanel.classList.contains('open');
            
            if (isOpen) {
                // Закрываем панель
                chatPanel.classList.remove('open');
                chatToggle.classList.remove('open');
                mainContent.classList.remove('shifted');
            } else {
                // Открываем панель
                chatPanel.classList.add('open');
                chatToggle.classList.add('open');
                mainContent.classList.add('shifted');
            }
        }
        
        // Закрытие панели по клику вне её
        document.addEventListener('click', function(event) {
            const chatPanel = document.getElementById('chatPanel');
            const chatToggle = document.getElementById('chatToggle');
            const isOpen = chatPanel.classList.contains('open');
            
            if (isOpen && 
                !chatPanel.contains(event.target) && 
                !chatToggle.contains(event.target)) {
                toggleChat();
            }
        });
        
        // Функция отправки сообщения от бота
        function sendBotMessage() {
            const input = document.getElementById('botMessageInput');
            const message = input.value.trim();
            
            if (!message) {
                showNotification('Введите сообщение', 'error');
                return;
            }
            
            const sendBtn = document.getElementById('sendBotMessage');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span>⏳</span>';
            
            fetch('../OAuth/bot_api.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Сообщение отправлено!', 'success');
                    input.value = '';
                    
                    // Обновляем чат через 1 секунду
                    setTimeout(() => {
                        const chatIframe = document.getElementById('twitch-chat-embed');
                        chatIframe.src = chatIframe.src;
                    }, 1000);
                } else {
                    showNotification(data.error || 'Ошибка при отправке', 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка при отправке сообщения', 'error');
                console.error(error);
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<span>📤</span>';
            });
        }
        
        // Обработчики для отправки сообщения
        document.addEventListener('DOMContentLoaded', function() {
            // Убеждаемся, что модальные окна закрыты при загрузке
            const commandsModal = document.getElementById('commandsModal');
            if (commandsModal) {
                commandsModal.classList.remove('open');
            }
            
            // Добавляем обработчик для кнопки добавления команды
            const addCommandBtn = document.querySelector('button[onclick*="openAddCommandModal"]');
            if (addCommandBtn) {
                addCommandBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Кнопка добавления команды нажата через addEventListener');
                    openAddCommandModal();
                });
            }
            
            
            const sendBtn = document.getElementById('sendBotMessage');
            const messageInput = document.getElementById('botMessageInput');
            
            if (sendBtn && messageInput) {
                sendBtn.addEventListener('click', sendBotMessage);
                
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendBotMessage();
                    }
                });
            }
            
            // Обработчик для кнопки команд
            const commandsBtn = document.getElementById('commandsWidgetBtn');
            if (commandsBtn) {
                commandsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Кнопка команд нажата!');
                    openCommandsModal();
                });
            } else {
                console.error('Кнопка команд не найдена!');
            }
        });
        
        // Функция показа уведомлений
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
        
        // Функции для модального окна команд
        function openCommandsModal() {
            console.log('openCommandsModal вызвана');
            
            const modal = document.getElementById('commandsModal');
            console.log('Модальное окно найдено:', modal);
            if (modal) {
                modal.classList.add('open');
                modal.style.visibility = 'visible';
                console.log('Модальное окно открыто, класс:', modal.className);
                console.log('Модальное окно стиль:', modal.style.display);
                // Загружаем данные только при открытии модального окна
                loadCommandsModal();
            } else {
                console.error('Модальное окно команд не найдено!');
            }
        }
        
        // Функция для создания тестовой команды (для отладки)
        async function createTestCommand() {
            try {
                const testCommand = {
                    command_name: '!test',
                    command_text: 'Это тестовая команда!',
                    permission_level: 'everyone',
                    cooldown_seconds: 5
                };

                const response = await fetch('../OAuth/api_commands.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(testCommand)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Тестовая команда создана!');
                    loadCommandsModal(); // Перезагружаем список команд
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка создания тестовой команды: ' + error.message);
            }
        }
        
        function closeCommandsModal() {
            const modal = document.getElementById('commandsModal');
            if (modal) {
                modal.classList.remove('open');
                console.log('Модальное окно закрыто');
            }
        }
        
        // Загрузка команд в модальном окне
        let commandsModal = [];
        
        async function loadCommandsModal() {
            try {
                console.log('Загружаем команды...');
                const loadingEl = document.getElementById('loadingCommandsModal');
                if (loadingEl) {
                    loadingEl.style.display = 'block';
                }
                
                const response = await fetch('../OAuth/api_commands.php?action=list', {
                    credentials: 'include'
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('API Response:', result);
                
                if (result.success) {
                    commandsModal = result.data || [];
                    console.log('Загруженные команды:', commandsModal);
                    renderCommandsModal();
                } else {
                    console.error('Ошибка загрузки команд:', result.message);
                    showErrorModal(result.message || 'Ошибка загрузки команд');
                }
            } catch (error) {
                console.error('Ошибка загрузки команд:', error);
                showErrorModal('Ошибка загрузки команд: ' + error.message);
            } finally {
                const loadingEl = document.getElementById('loadingCommandsModal');
                if (loadingEl) {
                    loadingEl.style.display = 'none';
                }
            }
        }
        
        
        // Отображение команд в модальном окне
        function renderCommandsModal() {
            const container = document.getElementById('commandsListModal');
            
            if (!container) {
                console.error('Контейнер commandsListModal не найден');
                return;
            }
            
            console.log('Отображаем команды:', commandsModal);
            
            if (!commandsModal || commandsModal.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-modal">
                        <div class="empty-state-icon-modal">📝</div>
                        <h3>Команды не найдены</h3>
                        <p>Создайте свою первую команду, нажав кнопку "Добавить команду"</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = commandsModal.map(cmd => `
                <div class="command-item-modal">
                    <div class="command-header-modal">
                        <div class="command-name-modal">${cmd.command_name || 'Без названия'}</div>
                        <div class="command-status-modal">
                            <span class="status-badge-modal ${cmd.is_active == 1 ? 'status-active-modal' : 'status-inactive-modal'}">
                                ${cmd.is_active == 1 ? 'Активна' : 'Неактивна'}
                            </span>
                            <span class="permission-badge-modal">${getPermissionTextModal(cmd.permission_level)}</span>
                        </div>
                    </div>
                    <div class="command-text-modal">${cmd.command_text || 'Нет описания'}</div>
                    <div class="command-meta-modal">
                        <div>
                            Кулдаун: ${cmd.cooldown_seconds || 0}с | 
                            Использований: ${cmd.usage_count || 0} | 
                            Создана: ${cmd.created_at ? new Date(cmd.created_at).toLocaleDateString() : 'Неизвестно'}
                        </div>
                        <div class="command-actions-modal">
                            <button class="btn-modal btn-primary-modal" onclick="editCommandModal(${cmd.id})">✏️</button>
                            <button class="btn-modal btn-secondary-modal" onclick="toggleCommandModal(${cmd.id}, ${cmd.is_active == 1 ? 0 : 1})">
                                ${cmd.is_active == 1 ? '⏸️' : '▶️'}
                            </button>
                            <button class="btn-modal btn-danger-modal" onclick="deleteCommandModal(${cmd.id})">🗑️</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Получение текста уровня доступа для модального окна
        function getPermissionTextModal(level) {
            const levels = {
                'everyone': 'Все',
                'moderator': 'Модераторы',
                'vip': 'VIP',
                'subscriber': 'Подписчики'
            };
            return levels[level] || level;
        }
        
        // Функции модального окна команды удалены
        
        // Обработка формы команды удалена
        
        // Переключение статуса команды в модальном окне
        async function toggleCommandModal(id, newStatus) {
            try {
                const response = await fetch('../OAuth/api_commands.php?action=toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ id: id, is_active: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Статус команды изменен');
                    loadCommandsModal();
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка изменения статуса: ' + error.message);
            }
        }
        
        // Удаление команды в модальном окне
        async function deleteCommandModal(id) {
            if (!confirm('Вы уверены, что хотите удалить эту команду?')) {
                return;
            }

            try {
                const response = await fetch(`../OAuth/api_commands.php?action=delete&id=${id}`, {
                    method: 'DELETE',
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Команда удалена');
                    loadCommandsModal();
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка удаления команды: ' + error.message);
            }
        }
        
        // Показать сообщение об ошибке в модальном окне
        function showErrorModal(message) {
            const errorDiv = document.getElementById('errorMessageModal');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }
        
        // Показать сообщение об успехе в модальном окне
        function showSuccessModal(message) {
            const successDiv = document.getElementById('successMessageModal');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => successDiv.style.display = 'none', 3000);
        }
        
        // Функции для модального окна добавления команды
        function openAddCommandModal() {
            console.log('openAddCommandModal вызвана');
            const modal = document.getElementById('addCommandModal');
            console.log('Модальное окно найдено:', modal);
            
            if (modal) {
                // Сбрасываем заголовок для создания новой команды
                const title = document.querySelector('#addCommandModal .commands-modal-title');
                if (title) {
                    title.innerHTML = '<span>➕</span> Добавить команду';
                }
                
                // Удаляем скрытое поле ID если есть
                const idField = document.getElementById('addCommandId');
                if (idField) {
                    idField.remove();
                }
                
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.classList.add('open');
                const form = document.getElementById('addCommandForm');
                if (form) {
                    form.reset();
                }
                console.log('Модальное окно открыто, display:', modal.style.display);
                console.log('Модальное окно видимо:', modal.offsetWidth, 'x', modal.offsetHeight);
                
                // Проверяем через секунду
                setTimeout(() => {
                    console.log('Через 1 сек - display:', modal.style.display);
                    console.log('Через 1 сек - видимо:', modal.offsetWidth, 'x', modal.offsetHeight);
                }, 1000);
            } else {
                console.error('Модальное окно addCommandModal не найдено!');
            }
        }
        
        function closeAddCommandModal() {
            const modal = document.getElementById('addCommandModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('open');
            }
        }
        
        // Обработка формы добавления/редактирования команды
        document.getElementById('addCommandForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const commandName = document.getElementById('addCommandName').value.trim();
            const commandText = document.getElementById('addCommandText').value.trim();
            const permissionLevel = document.getElementById('addCommandPermission').value;
            const cooldownSeconds = parseInt(document.getElementById('addCommandCooldown').value);
            const commandId = document.getElementById('addCommandId') ? document.getElementById('addCommandId').value : null;
            const isEdit = commandId !== null && commandId !== '';
            
            // Валидация
            if (!commandName) {
                showErrorModal('Название команды обязательно');
                return;
            }
            
            if (!commandText) {
                showErrorModal('Текст команды обязателен');
                return;
            }
            
            if (!/^![a-zA-Z0-9_]+$/.test(commandName)) {
                showErrorModal('Название команды должно начинаться с ! и содержать только буквы, цифры и _');
                return;
            }
            
            const data = {
                command_name: commandName,
                command_text: commandText,
                permission_level: permissionLevel,
                cooldown_seconds: cooldownSeconds
            };
            
            if (isEdit) {
                data.id = parseInt(commandId);
            }
            
            try {
                const submitBtn = document.querySelector('#addCommandForm button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Сохранение...';
                submitBtn.disabled = true;
                
                const response = await fetch(`../OAuth/api_commands.php?action=${isEdit ? 'update' : 'create'}`, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal(isEdit ? 'Команда обновлена!' : 'Команда создана успешно!');
                    closeAddCommandModal();
                    loadCommandsModal();
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка сохранения команды: ' + error.message);
            } finally {
                const submitBtn = document.querySelector('#addCommandForm button[type="submit"]');
                submitBtn.textContent = 'Сохранить';
                submitBtn.disabled = false;
            }
        });
        
        // Функции для управления командами
        function editCommandModal(id) {
            const cmd = commandsModal.find(c => c.id == id);
            if (!cmd) return;
            
            // Изменяем заголовок модального окна
            document.querySelector('#addCommandModal .commands-modal-title').innerHTML = '<span>✏️</span> Редактировать команду';
            
            // Заполняем форму данными команды
            document.getElementById('addCommandName').value = cmd.command_name;
            document.getElementById('addCommandText').value = cmd.command_text;
            document.getElementById('addCommandPermission').value = cmd.permission_level;
            document.getElementById('addCommandCooldown').value = cmd.cooldown_seconds;
            
            // Добавляем скрытое поле с ID команды
            let idField = document.getElementById('addCommandId');
            if (!idField) {
                idField = document.createElement('input');
                idField.type = 'hidden';
                idField.id = 'addCommandId';
                document.getElementById('addCommandForm').appendChild(idField);
            }
            idField.value = cmd.id;
            
            // Открываем модальное окно
            openAddCommandModal();
        }
        
        async function toggleCommandModal(id, newStatus) {
            try {
                const response = await fetch('../OAuth/api_commands.php?action=toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ id: id, is_active: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Статус команды изменен');
                    loadCommandsModal();
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка изменения статуса: ' + error.message);
            }
        }
        
        async function deleteCommandModal(id) {
            if (!confirm('Вы уверены, что хотите удалить эту команду?')) {
                return;
            }
            
            try {
                const response = await fetch(`../OAuth/api_commands.php?action=delete&id=${id}`, {
                    method: 'DELETE',
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Команда удалена');
                    loadCommandsModal();
                } else {
                    showErrorModal(result.message);
                }
            } catch (error) {
                showErrorModal('Ошибка удаления команды: ' + error.message);
            }
        }
        
        // Закрытие модального окна команд по клику вне его
        document.addEventListener('DOMContentLoaded', function() {
            const commandsModal = document.getElementById('commandsModal');
            if (commandsModal) {
                commandsModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeCommandsModal();
                    }
                });
            }
            
            const addCommandModal = document.getElementById('addCommandModal');
            if (addCommandModal) {
                // Временно отключаем закрытие по клику для отладки
                // addCommandModal.addEventListener('click', function(e) {
                //     if (e.target === this) {
                //         closeAddCommandModal();
                //     }
                // });
            }
        });
    </script>
    
    <!-- Кнопка команд вместо виджета времени -->
    <button id="commandsWidgetBtn" class="commands-widget-trigger" onclick="openCommandsModal()">
        <span class="commands-widget-icon">🎮</span>Команды
    </button>
</body>
</html>

