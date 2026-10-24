<?php
require_once '../OAuth/config.php';
require_once '../OAuth/Database.php';
require_once '../version.php';

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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quidy - Управление командами</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        body {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .commands-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .commands-list {
            flex: 2;
            background: #1e2836;
            border-radius: 12px;
            padding: 25px;
        }
        
        .commands-sidebar {
            flex: 1;
            background: #1e2836;
            border-radius: 12px;
            padding: 25px;
            height: fit-content;
        }
        
        .command-item {
            background: #252f3f;
            border: 1px solid #2a3441;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
        }
        
        .command-item:hover {
            border-color: #66c0f4;
            background: #2a3441;
        }
        
        .command-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .command-name {
            color: #66c0f4;
            font-size: 18px;
            font-weight: 600;
        }
        
        .command-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }
        
        .status-inactive {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }
        
        .permission-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: rgba(102, 192, 244, 0.2);
            color: #66c0f4;
        }
        
        .command-text {
            color: #c7d5e0;
            margin: 10px 0;
            line-height: 1.5;
        }
        
        .command-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #8f98a0;
            margin-top: 10px;
        }
        
        .command-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: #c7d5e0;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            background: #252f3f;
            border: 1px solid #2a3441;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #66c0f4;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select {
            width: 100%;
            padding: 12px;
            background: #252f3f;
            border: 1px solid #2a3441;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #66c0f4;
        }
        
        .tts-settings {
            background: #252f3f;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .tts-title {
            color: #66c0f4;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #2a3441;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #66c0f4;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #8f98a0;
        }
        
        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid rgba(244, 67, 54, 0.2);
            margin-bottom: 20px;
            display: none;
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid rgba(76, 175, 80, 0.2);
            margin-bottom: 20px;
            display: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #8f98a0;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="background-gradient"></div>
    
    <div class="dashboard-container">
        <div class="profile-sidebar">
            <h2 class="section-title">🎮 Управление командами</h2>
            
            <div class="tts-settings">
                <div class="tts-title">
                    🔊 Настройки TTS
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="ttsEnabled" class="toggle-switch">
                        <span class="slider"></span>
                        Включить TTS для модераторов
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Голос</label>
                    <select id="ttsVoice" class="form-select">
                        <option value="Microsoft David Desktop">Microsoft David (Мужской)</option>
                        <option value="Microsoft Zira Desktop">Microsoft Zira (Женский)</option>
                        <option value="Microsoft Mark Desktop">Microsoft Mark (Мужской)</option>
                        <option value="Microsoft Hazel Desktop">Microsoft Hazel (Женский)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Скорость речи</label>
                    <input type="range" id="ttsRate" class="form-input" min="-10" max="10" value="0">
                    <small style="color: #8f98a0;">Текущее значение: <span id="ttsRateValue">0</span></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Громкость</label>
                    <input type="range" id="ttsVolume" class="form-input" min="0" max="100" value="100">
                    <small style="color: #8f98a0;">Текущее значение: <span id="ttsVolumeValue">100</span>%</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Кулдаун (секунды)</label>
                    <input type="number" id="ttsCooldown" class="form-input" min="1" max="60" value="5">
                </div>
                <div class="form-group">
                    <label class="form-label">Максимальная длина сообщения</label>
                    <input type="number" id="ttsMaxLength" class="form-input" min="50" max="500" value="200">
                </div>
                <button class="btn btn-primary" onclick="saveTtsSettings()">Сохранить настройки TTS</button>
            </div>
            
            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="showAddCommandForm()" style="width: 100%;">
                    ➕ Добавить команду
                </button>
            </div>
        </div>
        
        <div class="profile-main">
            <div class="commands-list">
                <h2 class="section-title">📋 Список команд</h2>
                
                <div class="error-message" id="errorMessage"></div>
                <div class="success-message" id="successMessage"></div>
                
                <div class="loading" id="loadingCommands">
                    <div>Загрузка команд...</div>
                </div>
                
                <div id="commandsList"></div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления/редактирования команды -->
    <div id="commandModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
        <div style="background: #1e2836; border-radius: 12px; padding: 30px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h3 style="color: #c7d5e0; margin-bottom: 20px;" id="modalTitle">Добавить команду</h3>
            
            <form id="commandForm">
                <input type="hidden" id="commandId" value="">
                
                <div class="form-group">
                    <label class="form-label">Название команды</label>
                    <input type="text" id="commandName" class="form-input" placeholder="!info" required>
                    <small style="color: #8f98a0;">Должно начинаться с ! и содержать только буквы, цифры и _</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Текст команды</label>
                    <textarea id="commandText" class="form-input form-textarea" placeholder="Введите текст, который будет отправлен в чат..." required></textarea>
                    <div style="margin-top: 8px;">
                        <small style="color: #8f98a0;">Шаблоны: </small>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="useTemplate('welcome')">Приветствие</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="useTemplate('social')">Соцсети</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="useTemplate('rules')">Правила</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="useTemplate('schedule')">Расписание</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Уровень доступа</label>
                    <select id="commandPermission" class="form-select">
                        <option value="everyone">Все пользователи</option>
                        <option value="moderator">Только модераторы</option>
                        <option value="vip">VIP и модераторы</option>
                        <option value="subscriber">Подписчики и выше</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Кулдаун (секунды)</label>
                    <input type="number" id="commandCooldown" class="form-input" min="0" max="3600" value="0">
                    <small style="color: #8f98a0;">0 = без кулдауна</small>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="hideCommandModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let commands = [];
        let ttsSettings = {};

        // Загрузка команд при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Проверяем авторизацию
            console.log('Cookies:', document.cookie);
            
            // Тестируем API
            fetch('/OAuth/api_commands.php?action=list', {
                credentials: 'include'
            }).then(r => r.json()).then(data => {
                console.log('API Test Response:', data);
                if (!data.success) {
                    showError('Проблема с авторизацией: ' + data.message);
                }
            });
            
            loadCommands();
            loadTtsSettings();
        });

        // Загрузка списка команд
        async function loadCommands() {
            try {
                document.getElementById('loadingCommands').style.display = 'block';
                
                console.log('Загружаем команды...');
                const response = await fetch('/OAuth/api_commands.php?action=list', {
                    credentials: 'include'
                });
                
                console.log('Ответ сервера:', response.status, response.statusText);
                const result = await response.json();
                console.log('Данные команд:', result);
                
                if (result.success) {
                    commands = result.data;
                    renderCommands();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Ошибка загрузки команд:', error);
                showError('Ошибка загрузки команд: ' + error.message);
            } finally {
                document.getElementById('loadingCommands').style.display = 'none';
            }
        }

        // Загрузка настроек TTS
        async function loadTtsSettings() {
            try {
                const response = await fetch('/OAuth/api_commands.php?action=tts_settings', {
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    ttsSettings = result.data;
                    updateTtsForm();
                }
            } catch (error) {
                console.error('Ошибка загрузки настроек TTS:', error);
            }
        }

        // Обновление формы TTS
        function updateTtsForm() {
            document.getElementById('ttsEnabled').checked = ttsSettings.is_enabled == 1;
            document.getElementById('ttsVoice').value = ttsSettings.voice_name;
            document.getElementById('ttsRate').value = ttsSettings.voice_rate;
            document.getElementById('ttsVolume').value = ttsSettings.voice_volume;
            document.getElementById('ttsCooldown').value = ttsSettings.cooldown_seconds;
            document.getElementById('ttsMaxLength').value = ttsSettings.max_message_length;
            
            updateTtsSliders();
        }

        // Обновление слайдеров TTS
        function updateTtsSliders() {
            document.getElementById('ttsRateValue').textContent = document.getElementById('ttsRate').value;
            document.getElementById('ttsVolumeValue').textContent = document.getElementById('ttsVolume').value;
        }

        // Обработчики слайдеров
        document.getElementById('ttsRate').addEventListener('input', updateTtsSliders);
        document.getElementById('ttsVolume').addEventListener('input', updateTtsSliders);

        // Сохранение настроек TTS
        async function saveTtsSettings() {
            try {
                const settings = {
                    is_enabled: document.getElementById('ttsEnabled').checked ? 1 : 0,
                    voice_name: document.getElementById('ttsVoice').value,
                    voice_rate: parseInt(document.getElementById('ttsRate').value),
                    voice_volume: parseInt(document.getElementById('ttsVolume').value),
                    cooldown_seconds: parseInt(document.getElementById('ttsCooldown').value),
                    max_message_length: parseInt(document.getElementById('ttsMaxLength').value)
                };

                const response = await fetch('/OAuth/api_commands.php?action=tts_settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(settings)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Настройки TTS сохранены');
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Ошибка сохранения настроек TTS: ' + error.message);
            }
        }

        // Отображение команд
        function renderCommands() {
            const container = document.getElementById('commandsList');
            
            if (commands.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📝</div>
                        <h3>Команды не найдены</h3>
                        <p>Создайте свою первую команду, нажав кнопку "Добавить команду"</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = commands.map(cmd => `
                <div class="command-item">
                    <div class="command-header">
                        <div class="command-name">${cmd.command_name}</div>
                        <div class="command-status">
                            <span class="status-badge ${cmd.is_active == 1 ? 'status-active' : 'status-inactive'}">
                                ${cmd.is_active == 1 ? 'Активна' : 'Неактивна'}
                            </span>
                            <span class="permission-badge">${getPermissionText(cmd.permission_level)}</span>
                        </div>
                    </div>
                    <div class="command-text">${cmd.command_text}</div>
                    <div class="command-meta">
                        <div>
                            Кулдаун: ${cmd.cooldown_seconds}с | 
                            Использований: ${cmd.usage_count} | 
                            Создана: ${new Date(cmd.created_at).toLocaleDateString()}
                        </div>
                        <div class="command-actions">
                            <button class="btn btn-primary btn-sm" onclick="editCommand(${cmd.id})">✏️</button>
                            <button class="btn btn-secondary btn-sm" onclick="toggleCommand(${cmd.id}, ${cmd.is_active == 1 ? 0 : 1})">
                                ${cmd.is_active == 1 ? '⏸️' : '▶️'}
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCommand(${cmd.id})">🗑️</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Получение текста уровня доступа
        function getPermissionText(level) {
            const levels = {
                'everyone': 'Все',
                'moderator': 'Модераторы',
                'vip': 'VIP',
                'subscriber': 'Подписчики'
            };
            return levels[level] || level;
        }

        // Показать форму добавления команды
        function showAddCommandForm() {
            document.getElementById('modalTitle').textContent = 'Добавить команду';
            document.getElementById('commandForm').reset();
            document.getElementById('commandId').value = '';
            document.getElementById('commandModal').style.display = 'flex';
        }

        // Редактирование команды
        function editCommand(id) {
            const cmd = commands.find(c => c.id == id);
            if (!cmd) return;

            document.getElementById('modalTitle').textContent = 'Редактировать команду';
            document.getElementById('commandId').value = cmd.id;
            document.getElementById('commandName').value = cmd.command_name;
            document.getElementById('commandText').value = cmd.command_text;
            document.getElementById('commandPermission').value = cmd.permission_level;
            document.getElementById('commandCooldown').value = cmd.cooldown_seconds;
            document.getElementById('commandModal').style.display = 'flex';
        }

        // Скрыть модальное окно
        function hideCommandModal() {
            document.getElementById('commandModal').style.display = 'none';
        }

        // Валидация имени команды
        function validateCommandName(name) {
            const regex = /^![a-zA-Z0-9_]+$/;
            return regex.test(name);
        }

        // Обработка формы команды
        document.getElementById('commandForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const commandId = document.getElementById('commandId').value;
            const isEdit = commandId !== '';
            
            const commandName = document.getElementById('commandName').value.trim();
            const commandText = document.getElementById('commandText').value.trim();
            
            // Валидация
            if (!commandName) {
                showError('Название команды обязательно');
                return;
            }
            
            if (!commandText) {
                showError('Текст команды обязателен');
                return;
            }
            
            if (!validateCommandName(commandName)) {
                showError('Название команды должно начинаться с ! и содержать только буквы, цифры и _');
                return;
            }
            
            const data = {
                command_name: commandName,
                command_text: commandText,
                permission_level: document.getElementById('commandPermission').value,
                cooldown_seconds: parseInt(document.getElementById('commandCooldown').value)
            };

            if (isEdit) {
                data.id = parseInt(commandId);
            }

            try {
                // Показываем индикатор загрузки
                const submitBtn = document.querySelector('#commandForm button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Сохранение...';
                submitBtn.disabled = true;
                
                console.log('Отправляем данные команды:', data);
                console.log('URL:', `/OAuth/api_commands.php?action=${isEdit ? 'update' : 'create'}`);
                
                const response = await fetch(`/OAuth/api_commands.php?action=${isEdit ? 'update' : 'create'}`, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });
                
                console.log('Ответ сервера:', response.status, response.statusText);
                const result = await response.json();
                console.log('Результат:', result);
                
                if (result.success) {
                    showSuccess(isEdit ? 'Команда обновлена' : 'Команда создана');
                    hideCommandModal();
                    loadCommands();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                console.error('Ошибка сохранения команды:', error);
                showError('Ошибка сохранения команды: ' + error.message);
            } finally {
                // Восстанавливаем кнопку
                const submitBtn = document.querySelector('#commandForm button[type="submit"]');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        // Переключение статуса команды
        async function toggleCommand(id, newStatus) {
            try {
                const response = await fetch('/OAuth/api_commands.php?action=toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ id: id, is_active: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Статус команды изменен');
                    loadCommands();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Ошибка изменения статуса: ' + error.message);
            }
        }

        // Удаление команды
        async function deleteCommand(id) {
            if (!confirm('Вы уверены, что хотите удалить эту команду?')) {
                return;
            }

            try {
                const response = await fetch(`/OAuth/api_commands.php?action=delete&id=${id}`, {
                    method: 'DELETE',
                    credentials: 'include'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Команда удалена');
                    loadCommands();
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Ошибка удаления команды: ' + error.message);
            }
        }

        // Показать сообщение об ошибке
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }

        // Показать сообщение об успехе
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => successDiv.style.display = 'none', 3000);
        }

        // Шаблоны команд
        function useTemplate(template) {
            const templates = {
                'welcome': '🌟 Добро пожаловать на канал! Ставь лайк, подписывайся и заказывай настройку буста! Ссылки в описании! 🌟',
                'social': '📱 Наши соцсети:\n🔗 Discord: https://discord.gg/example\n🔗 Telegram: https://t.me/example\n🔗 YouTube: https://youtube.com/example',
                'rules': '📋 Правила чата:\n1️⃣ Уважай других\n2️⃣ Не спамь\n3️⃣ Не рекламируй\n4️⃣ Наслаждайся стримом!',
                'schedule': '📅 Расписание стримов:\n🕐 Понедельник-Пятница: 19:00-23:00\n🕐 Суббота-Воскресенье: 15:00-22:00\n⏰ Время московское'
            };
            
            if (templates[template]) {
                document.getElementById('commandText').value = templates[template];
            }
        }

        // Закрытие модального окна по клику вне его
        document.getElementById('commandModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCommandModal();
            }
        });
    </script>
</body>
</html>
