/**
 * Виджет времени для отображения статистики активного времени
 */

class TimeWidget {
    constructor() {
        this.isOpen = false;
        this.data = null;
        this.updateInterval = null;
        this.init();
    }

    init() {
        this.createWidget();
        this.bindEvents();
        this.loadData();
    }


    createWidget() {
        // Создаем кнопку-триггер (внешняя кнопка справа на странице)
        const trigger = document.createElement('button');
        trigger.className = 'time-widget-trigger';
        trigger.innerHTML = '<span class="time-widget-trigger-icon">⏱️</span>Время';
        trigger.setAttribute('title', 'Показать статистику активного времени');
        document.body.appendChild(trigger);

        // Создаем оверлей
        const overlay = document.createElement('div');
        overlay.className = 'time-widget-overlay';
        document.body.appendChild(overlay);

        // Создаем контейнер виджета
        const container = document.createElement('div');
        container.className = 'time-widget-container';
        container.innerHTML = `
            <div class="time-widget-header">
                <div class="time-widget-title">
                    <span>⏱️</span>
                    Моё активное время
                </div>
                <button class="time-widget-close" title="Закрыть">✖️</button>
            </div>
            <div class="time-widget-content">
                <div class="time-widget-loading">
                    <div class="time-widget-loading-spinner"></div>
                    <div>Загрузка данных...</div>
                </div>
            </div>
        `;
        document.body.appendChild(container);

        // Сохраняем ссылки на элементы
        this.trigger = trigger;
        this.overlay = overlay;
        this.container = container;
        this.content = container.querySelector('.time-widget-content');
    }

    bindEvents() {
        // Открытие виджета
        this.trigger.addEventListener('click', () => {
            this.open();
        });

        // Закрытие виджета
        this.overlay.addEventListener('click', () => {
            this.close();
        });

        this.container.querySelector('.time-widget-close').addEventListener('click', () => {
            this.close();
        });

        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Предотвращение закрытия при клике на контент
        this.container.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    async loadData() {
        try {
            this.showLoading();
            
            const response = await fetch('/OAuth/api_user_time.php', {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Ошибка загрузки данных');
            }

            this.data = result.data;
            this.renderData();
            
            // Если таймер активен, обновляем данные каждую минуту
            if (this.data.isTimerActive) {
                this.startAutoUpdate();
            }

        } catch (error) {
            console.error('Ошибка загрузки данных времени:', error);
            this.showError(error.message);
        }
    }

    showLoading() {
        this.content.innerHTML = `
            <div class="time-widget-loading">
                <div class="time-widget-loading-spinner"></div>
                <div>Загрузка данных...</div>
            </div>
        `;
    }

    showError(message) {
        this.content.innerHTML = `
            <div class="time-widget-error">
                <div class="time-widget-error-icon">⚠️</div>
                <div>Ошибка загрузки данных</div>
                <div style="font-size: 12px; color: #8f98a0; margin-top: 8px;">${message}</div>
                <button class="time-widget-retry-btn" onclick="timeWidget.loadData()">
                    Повторить
                </button>
            </div>
        `;
    }

    renderData() {
        if (!this.data) return;

        const { totalAccumulatedTime, timeFormatted, isTimerActive, channels } = this.data;
        
        // Если есть каналы, показываем селектор
        let channelSelectorHtml = '';
        let selectedChannel = null;
        
        if (channels && channels.length > 0) {
            // Выбираем первый канал по умолчанию (с наибольшим временем)
            selectedChannel = channels[0];
            
            if (channels.length > 1) {
                channelSelectorHtml = `
                    <div class="time-widget-channel-selector">
                        <label for="channel-select" class="time-widget-channels-title">Выберите канал:</label>
                        <select id="channel-select" class="time-widget-channel-select">
                            ${channels.map(channel => `
                                <option value="${this.escapeHtml(channel.channel)}" data-channel='${JSON.stringify(channel)}'>
                                    ${this.escapeHtml(channel.channel)} (${this.escapeHtml(channel.timeFormatted)})
                                </option>
                            `).join('')}
                        </select>
                    </div>
                `;
            }
        }

        // Показываем статистику выбранного канала или общую
        const displayData = selectedChannel || {
            totalAccumulatedTime: totalAccumulatedTime,
            timeFormatted: timeFormatted,
            isTimerActive: isTimerActive,
            channel: 'Все каналы'
        };

        this.content.innerHTML = `
            ${channelSelectorHtml}
            
            <div class="time-widget-main-stat">
                <div class="time-widget-total-time">${this.escapeHtml(displayData.timeFormatted)}</div>
                <div class="time-widget-channel-name" style="margin-bottom: 8px; font-size: 14px;">
                    <span>📺</span>
                    ${this.escapeHtml(displayData.channel)}
                </div>
                <div class="time-widget-status ${displayData.isTimerActive ? 'active' : 'inactive'}">
                    <span>●</span>
                    ${displayData.isTimerActive ? 'Таймер активен' : 'Таймер остановлен'}
                </div>
            </div>
            
            ${this.renderChannelsList(channels, selectedChannel)}
            
            <div style="margin-top: 20px; padding: 15px; background: rgba(102, 192, 244, 0.05); border-radius: 6px; border: 1px solid rgba(102, 192, 244, 0.1);">
                <div style="font-size: 12px; color: #8f98a0; line-height: 1.4;">
                    💡 <strong>Как работает:</strong><br>
                    Время накапливается только при активности в чате каждые 10 минут. 
                    Используйте команду <code>!starttime</code> в чате Twitch для запуска таймера.
                </div>
            </div>
        `;

        // Привязываем обработчик изменения селектора
        if (channels && channels.length > 1) {
            const select = this.container.querySelector('#channel-select');
            select.addEventListener('change', (e) => {
                const selectedOption = e.target.selectedOptions[0];
                const channelData = JSON.parse(selectedOption.dataset.channel);
                this.updateDisplayForChannel(channelData);
            });
        }
    }

    renderChannelsList(channels, selectedChannel) {
        if (!channels || channels.length <= 1) return '';

        return `
            <div class="time-widget-channels">
                <div class="time-widget-channels-title">Все каналы</div>
                ${channels.map(channel => `
                    <div class="time-widget-channel ${selectedChannel && channel.channel === selectedChannel.channel ? 'selected' : ''}">
                        <div class="time-widget-channel-name">
                            <span>📺</span>
                            ${this.escapeHtml(channel.channel)}
                        </div>
                        <div class="time-widget-channel-time">
                            ${this.escapeHtml(channel.timeFormatted)}
                        </div>
                        <div class="time-widget-channel-status ${channel.isActive ? 'active' : 'inactive'}">
                            <span>●</span>
                            ${channel.isActive ? 'Активен' : 'Остановлен'}
                        </div>
                        ${channel.activeSessions > 0 ? `
                            <div style="font-size: 11px; color: #8f98a0; margin-top: 4px;">
                                Сессий: ${channel.activeSessions}
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    updateDisplayForChannel(channelData) {
        const mainStat = this.container.querySelector('.time-widget-main-stat');
        const channelName = mainStat.querySelector('.time-widget-channel-name');
        const totalTime = mainStat.querySelector('.time-widget-total-time');
        const status = mainStat.querySelector('.time-widget-status');

        // Обновляем отображаемые данные
        totalTime.textContent = this.escapeHtml(channelData.timeFormatted);
        channelName.innerHTML = `<span>📺</span> ${this.escapeHtml(channelData.channel)}`;
        status.className = `time-widget-status ${channelData.isTimerActive ? 'active' : 'inactive'}`;
        status.innerHTML = `<span>●</span> ${channelData.isTimerActive ? 'Таймер активен' : 'Таймер остановлен'}`;

        // Обновляем выделение в списке каналов
        const channelItems = this.container.querySelectorAll('.time-widget-channel');
        channelItems.forEach(item => {
            item.classList.remove('selected');
            if (item.querySelector('.time-widget-channel-name').textContent.includes(channelData.channel)) {
                item.classList.add('selected');
            }
        });
    }

    startAutoUpdate() {
        // Обновляем данные каждую минуту, если таймер активен
        this.updateInterval = setInterval(() => {
            if (this.isOpen && this.data && this.data.isTimerActive) {
                this.loadData();
            }
        }, 60000); // 60 секунд
    }

    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    open() {
        if (this.isOpen) return;
        
        console.log('Открываем виджет...');
        this.isOpen = true;
        this.overlay.classList.add('open');
        this.container.classList.add('open');
        
        // Скрываем внешнюю кнопку-триггер (не кнопку внутри виджета!)
        if (this.trigger) {
            console.log('Скрываем внешнюю кнопку-триггер:', this.trigger);
            this.trigger.classList.add('hidden');
            console.log('Классы кнопки после скрытия:', this.trigger.className);
        } else {
            console.error('Внешняя кнопка-триггер не найдена!');
        }
        
        // Загружаем данные при открытии
        this.loadData();
        
        // Предотвращаем скролл body
        document.body.style.overflow = 'hidden';
    }

    close() {
        if (!this.isOpen) return;
        
        console.log('Закрываем виджет...');
        this.isOpen = false;
        this.overlay.classList.remove('open');
        this.container.classList.remove('open');
        
        // Показываем внешнюю кнопку-триггер обратно
        if (this.trigger) {
            console.log('Показываем внешнюю кнопку-триггер:', this.trigger);
            this.trigger.classList.remove('hidden');
            console.log('Классы кнопки после показа:', this.trigger.className);
        } else {
            console.error('Внешняя кнопка-триггер не найдена!');
        }
        
        // Восстанавливаем скролл body
        document.body.style.overflow = '';
        
        // Останавливаем автообновление
        this.stopAutoUpdate();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Публичный метод для обновления данных
    refresh() {
        this.loadData();
    }
}

// Инициализируем виджет при загрузке страницы
let timeWidget;

function initWidget() {
    console.log('Инициализация виджета, проверяем путь:', window.location.pathname);
    console.log('Полный URL:', window.location.href);
    
    // Проверяем, что мы на странице профиля или тестовой странице
    const isProfilePage = window.location.pathname.includes('/profile/') || 
                         window.location.pathname.includes('dashboard.php') || 
                         window.location.pathname.includes('test_button.html') ||
                         window.location.pathname.includes('widget_test.html') ||
                         window.location.pathname.includes('simple_css_test.html') ||
                         window.location.pathname.includes('debug_widget.html') ||
                         window.location.pathname.includes('simple_widget_test.html') ||
                         window.location.pathname.includes('profile/dashboard.php');
    
    console.log('isProfilePage:', isProfilePage);
    
    if (isProfilePage) {
        console.log('Инициализируем виджет времени...');
        timeWidget = new TimeWidget();
        // Экспортируем для глобального доступа
        window.timeWidget = timeWidget;
        console.log('Виджет создан:', timeWidget);
    } else {
        console.log('Страница не подходит для виджета времени:', window.location.pathname);
    }
}

// Добавляем стили сразу при загрузке скрипта
function addGlobalStyles() {
    if (document.getElementById('time-widget-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'time-widget-styles';
    style.textContent = `
        /* Кнопка-триггер */
        .time-widget-trigger {
            position: fixed;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #66c0f4, #67c1f5);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 5px 20px rgba(102, 192, 244, 0.3);
            transition: all 0.3s ease;
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
            opacity: 1;
            visibility: visible;
        }
        
        .time-widget-trigger:hover {
            background: linear-gradient(135deg, #5bb0e4, #5cc0e5);
            transform: translateY(-50%) scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 192, 244, 0.4);
        }
        
        .time-widget-trigger.hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(-50%) translateX(100px) !important;
            pointer-events: none;
        }
        
        /* Оверлей */
        .time-widget-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1002;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .time-widget-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        
        /* Контейнер виджета */
        .time-widget-container {
            position: fixed;
            top: 0;
            right: 0;
            width: 400px;
            height: 100%;
            background: #1e2836;
            border-left: 1px solid #2a3441;
            z-index: 1003;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .time-widget-container.open {
            transform: translateX(0);
        }
        
        /* Заголовок */
        .time-widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #2a3441;
            background: #252f3f;
        }
        
        .time-widget-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #c7d5e0;
        }
        
        .time-widget-close {
            background: none;
            border: none;
            color: #8f98a0;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .time-widget-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #c7d5e0;
        }
        
        /* Контент */
        .time-widget-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        /* Загрузка */
        .time-widget-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            color: #8f98a0;
        }
        
        .time-widget-loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #2a3441;
            border-top: 3px solid #66c0f4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Ошибка */
        .time-widget-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            text-align: center;
            color: #8f98a0;
        }
        
        .time-widget-error-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .time-widget-retry-btn {
            background: #66c0f4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            transition: background 0.2s ease;
        }
        
        .time-widget-retry-btn:hover {
            background: #5bb0e4;
        }
        
        /* Основная статистика */
        .time-widget-main-stat {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .time-widget-total-time {
            font-size: 36px;
            font-weight: 700;
            color: #66c0f4;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(102, 192, 244, 0.3);
        }
        
        .time-widget-channel-name {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
            color: #c7d5e0;
            margin-bottom: 15px;
        }
        
        .time-widget-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .time-widget-status.active {
            background: rgba(46, 160, 67, 0.2);
            color: #2ea043;
            border: 1px solid rgba(46, 160, 67, 0.3);
        }
        
        .time-widget-status.inactive {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        /* Селектор каналов */
        .time-widget-channel-selector {
            margin-bottom: 25px;
        }
        
        .time-widget-channels-title {
            display: block;
            font-size: 14px;
            color: #8f98a0;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .time-widget-channel-select {
            width: 100%;
            padding: 10px 12px;
            background: #252f3f;
            border: 1px solid #2a3441;
            border-radius: 6px;
            color: #c7d5e0;
            font-size: 14px;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }
        
        .time-widget-channel-select:focus {
            outline: none;
            border-color: #66c0f4;
        }
        
        /* Список каналов */
        .time-widget-channels {
            margin-top: 20px;
        }
        
        .time-widget-channel {
            background: #252f3f;
            border: 1px solid #2a3441;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .time-widget-channel:hover {
            background: #2a3441;
            border-color: #66c0f4;
        }
        
        .time-widget-channel.selected {
            background: rgba(102, 192, 244, 0.1);
            border-color: #66c0f4;
        }
        
        .time-widget-channel-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #c7d5e0;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .time-widget-channel-time {
            font-size: 18px;
            font-weight: 600;
            color: #66c0f4;
            margin-bottom: 8px;
        }
        
        .time-widget-channel-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .time-widget-channel-status.active {
            color: #2ea043;
        }
        
        .time-widget-channel-status.inactive {
            color: #ffc107;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .time-widget-container {
                width: 90%;
            }
            
            .time-widget-trigger {
                right: 10px;
                padding: 12px 16px;
                font-size: 13px;
                min-width: 100px;
            }
            
            .time-widget-total-time {
                font-size: 28px;
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Добавляем стили сразу
addGlobalStyles();

// Проверяем готовность DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWidget);
} else {
    // DOM уже загружен
    initWidget();
}

// Дополнительная инициализация через 1 секунду (на случай, если DOM загрузился раньше)
setTimeout(() => {
    console.log('=== Дополнительная проверка через 1 секунду ===');
    console.log('timeWidget существует:', !!timeWidget);
    console.log('window.timeWidget существует:', !!window.timeWidget);
    
    const isProfilePage = window.location.pathname.includes('/profile/') || 
                         window.location.pathname.includes('dashboard.php') || 
                         window.location.pathname.includes('test_button.html') ||
                         window.location.pathname.includes('widget_test.html') ||
                         window.location.pathname.includes('simple_css_test.html') ||
                         window.location.pathname.includes('debug_widget.html') ||
                         window.location.pathname.includes('simple_widget_test.html') ||
                         window.location.pathname.includes('profile/dashboard.php');
    
    console.log('isProfilePage (повторная проверка):', isProfilePage);
    
    if (!timeWidget && isProfilePage) {
        console.log('Принудительная инициализация виджета...');
        timeWidget = new TimeWidget();
        window.timeWidget = timeWidget;
        console.log('Виджет создан принудительно:', timeWidget);
    }
}, 1000);
