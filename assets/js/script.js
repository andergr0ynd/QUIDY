// Обработка кликов на кнопки авторизации
document.addEventListener('DOMContentLoaded', function() {
    const socialButtons = document.querySelectorAll('.social-btn');
    
    socialButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const social = this.getAttribute('data-social');
            handleSocialLogin(social);
        });
        
        // Добавляем эффект ripple при клике
        button.addEventListener('mousedown', function(e) {
            createRipple(e, this);
        });
    });
    
    // Проверка параметра account_deleted
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('account_deleted') === '1') {
        showSuccessNotification('Ваш аккаунт успешно удалён');
        // Убираем параметр из URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Функция для обработки авторизации через соцсети
function handleSocialLogin(socialNetwork) {
    console.log(`Авторизация через ${socialNetwork}`);
    
    const urls = {
        discord: 'profile/dashboard.html',
        telegram: 'profile/dashboard.html',
        twitch: 'profile/dashboard.html'
    };
    
    showNotification('Открываем демо-кабинет...');
    
    // Небольшая задержка для показа уведомления
    setTimeout(() => {
        window.location.href = urls[socialNetwork];
    }, 300);
}

// Создание эффекта ripple при клике
function createRipple(event, button) {
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');
    
    button.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Показ временного уведомления
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}

// Показ уведомления об ошибке
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'error-notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Показ уведомления об успехе
function showSuccessNotification(message) {
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
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Добавляем стили для ripple эффекта и уведомлений
const style = document.createElement('style');
style.textContent = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(102, 192, 244, 0.4);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, rgba(52, 73, 94, 0.95) 0%, rgba(44, 62, 80, 0.95) 100%);
        color: #c7d5e0;
        padding: 15px 25px;
        border-radius: 4px;
        border: 1px solid rgba(102, 192, 244, 0.3);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        z-index: 1000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        font-size: 14px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
`;
document.head.appendChild(style);

