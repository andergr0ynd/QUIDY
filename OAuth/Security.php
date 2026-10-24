<?php
/**
 * Класс для обеспечения безопасности и валидации
 */

class Security {
    
    /**
     * Генерация CSRF токена
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Проверка CSRF токена
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Регенерация CSRF токена
     */
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting для защиты от brute-force
     * @param string $action Действие (login, 2fa, etc.)
     * @param int $maxAttempts Максимальное количество попыток
     * @param int $timeWindow Временное окно в секундах
     * @return bool True если разрешено, False если превышен лимит
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
        $key = 'rate_limit_' . $action;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $key . '_' . md5($ip);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Если прошло больше времени чем окно - сбрасываем
        if ((time() - $data['first_attempt']) > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Проверяем количество попыток
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Увеличиваем счетчик
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Получение оставшегося времени блокировки
     */
    public static function getRateLimitTimeLeft($action, $timeWindow = 300) {
        $key = 'rate_limit_' . $action;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $key . '_' . md5($ip);
        
        if (!isset($_SESSION[$key])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION[$key]['first_attempt'];
        $remaining = $timeWindow - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Сброс rate limit (после успешного действия)
     */
    public static function resetRateLimit($action) {
        $key = 'rate_limit_' . $action;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = $key . '_' . md5($ip);
        
        unset($_SESSION[$key]);
    }
    
    /**
     * Санитизация строки
     */
    public static function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Валидация email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Валидация URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Безопасное перенаправление (предотвращает Open Redirect)
     */
    public static function safeRedirect($url, $defaultUrl = '/') {
        // Разрешаем только локальные URL
        $parsedUrl = parse_url($url);
        
        // Если есть хост и он не совпадает с текущим - используем default
        if (isset($parsedUrl['host']) && $parsedUrl['host'] !== $_SERVER['HTTP_HOST']) {
            $url = $defaultUrl;
        }
        
        // Если URL начинается с // (protocol-relative) - блокируем
        if (strpos($url, '//') === 0) {
            $url = $defaultUrl;
        }
        
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Логирование попыток входа
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        // Создаём директорию если не существует
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'details' => $details
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        // Пишем в лог (если возможно)
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Проверка силы пароля (если будет использоваться)
     */
    public static function validatePasswordStrength($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return [
                'valid' => false,
                'message' => "Пароль должен содержать минимум $minLength символов"
            ];
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Пароль должен содержать хотя бы одну заглавную букву'
            ];
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Пароль должен содержать хотя бы одну строчную букву'
            ];
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return [
                'valid' => false,
                'message' => 'Пароль должен содержать хотя бы одну цифру'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Пароль соответствует требованиям'
        ];
    }
    
    /**
     * Генерация безопасного случайного токена
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

