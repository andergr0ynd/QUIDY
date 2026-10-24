<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            } else {
                die("Ошибка подключения к базе данных");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Найти или создать пользователя по OAuth данным
    public function findOrCreateUser($provider, $providerUserId, $userData, $linkToExistingUser = null) {
        $conn = $this->connection;

        // Проверяем, существует ли OAuth подключение
        $stmt = $conn->prepare("SELECT user_id FROM oauth_connections WHERE provider = ? AND provider_user_id = ?");
        $stmt->execute([$provider, $providerUserId]);
        $oauthConnection = $stmt->fetch();

        if ($oauthConnection) {
            // OAuth уже привязан к пользователю
            $userId = $oauthConnection['user_id'];
            $this->updateUser($userId, $userData);
            $this->updateOAuthConnection($userId, $provider, $providerUserId, $userData);
            return $userId;
        } else {
            // Если указан ID пользователя для привязки (режим линковки)
            if ($linkToExistingUser !== null) {
                $this->linkOAuthToUser($linkToExistingUser, $provider, $providerUserId, $userData);
                return $linkToExistingUser;
            }
            
            // Создаем нового пользователя
            return $this->createUserWithOAuth($provider, $providerUserId, $userData);
        }
    }
    
    // Привязка OAuth к существующему пользователю
    public function linkOAuthToUser($userId, $provider, $providerUserId, $userData) {
        $conn = $this->connection;
        
        // Проверяем, не привязан ли уже этот OAuth к другому пользователю
        $stmt = $conn->prepare("SELECT user_id FROM oauth_connections WHERE provider = ? AND provider_user_id = ?");
        $stmt->execute([$provider, $providerUserId]);
        $existing = $stmt->fetch();
        
        if ($existing && $existing['user_id'] != $userId) {
            throw new Exception('Этот аккаунт уже привязан к другому пользователю');
        }
        
        // Создаем OAuth подключение
        $stmt = $conn->prepare("
            INSERT INTO oauth_connections 
            (user_id, provider, provider_user_id, provider_username, access_token, refresh_token, token_expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            provider_username = VALUES(provider_username),
            access_token = VALUES(access_token),
            refresh_token = VALUES(refresh_token),
            token_expires_at = VALUES(token_expires_at)
        ");
        $stmt->execute([
            $userId,
            $provider,
            $providerUserId,
            $userData['provider_username'] ?? null,
            $userData['access_token'] ?? null,
            $userData['refresh_token'] ?? null,
            $userData['token_expires_at'] ?? null
        ]);
        
        return true;
    }

    // Создание нового пользователя с OAuth подключением
    private function createUserWithOAuth($provider, $providerUserId, $userData) {
        $conn = $this->connection;
        
        try {
            $conn->beginTransaction();

            // Создаем пользователя
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, avatar) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $userData['username'],
                $userData['email'] ?? null,
                $userData['avatar'] ?? null
            ]);
            $userId = $conn->lastInsertId();

            // Создаем OAuth подключение
            $stmt = $conn->prepare("
                INSERT INTO oauth_connections 
                (user_id, provider, provider_user_id, provider_username, access_token, refresh_token, token_expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $provider,
                $providerUserId,
                $userData['provider_username'] ?? null,
                $userData['access_token'] ?? null,
                $userData['refresh_token'] ?? null,
                $userData['token_expires_at'] ?? null
            ]);

            $conn->commit();
            return $userId;
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // Обновление данных пользователя
    private function updateUser($userId, $userData) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, email = ?, avatar = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $userData['username'],
            $userData['email'] ?? null,
            $userData['avatar'] ?? null,
            $userId
        ]);
    }

    // Обновление OAuth подключения
    private function updateOAuthConnection($userId, $provider, $providerUserId, $userData) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            UPDATE oauth_connections 
            SET provider_username = ?, access_token = ?, refresh_token = ?, token_expires_at = ?
            WHERE user_id = ? AND provider = ?
        ");
        $stmt->execute([
            $userData['provider_username'] ?? null,
            $userData['access_token'] ?? null,
            $userData['refresh_token'] ?? null,
            $userData['token_expires_at'] ?? null,
            $userId,
            $provider
        ]);
    }

    // Создание сессии
    public function createSession($userId) {
        $conn = $this->connection;
        $sessionToken = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("
            INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
        ");
        $stmt->execute([
            $userId,
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            SESSION_LIFETIME
        ]);

        return $sessionToken;
    }
    
    // Установка безопасного cookie для сессии
    public function setSecureSessionCookie($sessionToken) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                   || $_SERVER['SERVER_PORT'] == 443
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        setcookie(
            'session_token',
            $sessionToken,
            [
                'expires' => time() + SESSION_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps, // true для HTTPS
                'httponly' => true,
                'samesite' => 'Lax' // Защита от CSRF
            ]
        );
    }

    // Получение пользователя по сессии
    public function getUserBySession($sessionToken) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            INNER JOIN sessions s ON u.id = s.user_id
            WHERE s.session_token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$sessionToken]);
        return $stmt->fetch();
    }

    // Логирование действий (отключено)
    public function logAuth($userId, $provider, $action) {
        // Логирование отключено
        return true;
    }

    // Удаление сессии (logout)
    public function deleteSession($sessionToken) {
        $conn = $this->connection;
        $stmt = $conn->prepare("DELETE FROM sessions WHERE session_token = ?");
        $stmt->execute([$sessionToken]);
    }

    // Очистка устаревших сессий
    public function cleanupExpiredSessions() {
        $conn = $this->connection;
        $stmt = $conn->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $stmt->execute();
    }

    // Получение OAuth подключений пользователя
    public function getUserOAuthConnections($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            SELECT provider, provider_username, created_at 
            FROM oauth_connections 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Включение 2FA
    public function enable2FA($userId, $secret) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            UPDATE users 
            SET two_factor_enabled = 1, two_factor_secret = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$secret, $userId]);
    }

    // Отключение 2FA
    public function disable2FA($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            UPDATE users 
            SET two_factor_enabled = 0, two_factor_secret = NULL 
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    // Получение пользователя по ID
    public function getUserById($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getAllUsers($limit = 50, $offset = 0) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            SELECT u.*, COUNT(oc.provider) as linked_accounts 
            FROM users u 
            LEFT JOIN oauth_connections oc ON u.id = oc.user_id 
            GROUP BY u.id 
            ORDER BY u.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOAuthConnections($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("SELECT * FROM oauth_connections WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getLastLoginTime($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            SELECT MAX(last_activity) as last_login 
            FROM sessions 
            WHERE user_id = ? AND last_activity IS NOT NULL
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_login'];
    }
    
    public function getUserStats() {
        $conn = $this->connection;
        
        $stats = [];
        
        // Общее количество пользователей
        $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        // Активные пользователи (за последние 30 дней)
        $stmt = $conn->query("SELECT COUNT(DISTINCT user_id) as active_users FROM sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
        
        // Пользователи с 2FA
        $stmt = $conn->query("SELECT COUNT(*) as users_with_2fa FROM users WHERE two_factor_enabled = 1");
        $stats['users_with_2fa'] = $stmt->fetch(PDO::FETCH_ASSOC)['users_with_2fa'];
        
        // Статистика по провайдерам
        $stmt = $conn->query("SELECT provider, COUNT(*) as count FROM oauth_connections GROUP BY provider");
        $stats['oauth_providers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    // Отвязка OAuth аккаунта
    public function unlinkOAuth($userId, $provider) {
        $conn = $this->connection;
        
        // Проверяем, сколько OAuth подключений у пользователя
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM oauth_connections WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetch()['count'];
        
        // Нельзя отвязать последний аккаунт
        if ($count <= 1) {
            throw new Exception('Нельзя отвязать последний способ входа. Привяжите другой аккаунт сначала.');
        }
        
        // Удаляем OAuth подключение
        $stmt = $conn->prepare("DELETE FROM oauth_connections WHERE user_id = ? AND provider = ?");
        $stmt->execute([$userId, $provider]);
        
        return true;
    }
    
    // Проверка, привязан ли OAuth к пользователю
    public function isOAuthLinked($userId, $provider) {
        $conn = $this->connection;
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM oauth_connections WHERE user_id = ? AND provider = ?");
        $stmt->execute([$userId, $provider]);
        return $stmt->fetch()['count'] > 0;
    }
    
    // Полное удаление аккаунта пользователя
    public function deleteUserAccount($userId) {
        $conn = $this->connection;
        
        try {
            $conn->beginTransaction();
            
            // Удаляем все OAuth подключения (CASCADE сделает это автоматически, но явно для надежности)
            $stmt = $conn->prepare("DELETE FROM oauth_connections WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Удаляем все сессии
            $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Удаляем все backup codes
            $stmt = $conn->prepare("DELETE FROM backup_codes WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Удаляем самого пользователя
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    
    // ========================================
    // Методы для работы с backup codes
    // ========================================
    
    /**
     * Генерация backup кодов для пользователя
     * @param int $userId ID пользователя
     * @param int $count Количество кодов (по умолчанию 8)
     * @return array Массив с сгенерированными кодами
     */
    public function generateBackupCodes($userId, $count = 8) {
        $conn = $this->connection;
        
        try {
            $conn->beginTransaction();
            
            // Удаляем старые неиспользованные коды
            $stmt = $conn->prepare("DELETE FROM backup_codes WHERE user_id = ? AND used = 0");
            $stmt->execute([$userId]);
            
            $codes = [];
            $stmt = $conn->prepare("INSERT INTO backup_codes (user_id, code) VALUES (?, ?)");
            
            for ($i = 0; $i < $count; $i++) {
                // Генерируем код формата XXXX-XXXX
                $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4) . '-' . substr(bin2hex(random_bytes(4)), 0, 4));
                $hashedCode = password_hash($code, PASSWORD_BCRYPT);
                
                $stmt->execute([$userId, $hashedCode]);
                $codes[] = $code; // Возвращаем незахешированный код для показа пользователю
            }
            
            $conn->commit();
            return $codes;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Проверка backup кода
     * @param int $userId ID пользователя
     * @param string $code Введённый код
     * @return bool True если код верный и не использован
     */
    public function verifyBackupCode($userId, $code) {
        $conn = $this->connection;
        
        // Получаем все неиспользованные коды пользователя
        $stmt = $conn->prepare("SELECT id, code FROM backup_codes WHERE user_id = ? AND used = 0");
        $stmt->execute([$userId]);
        $backupCodes = $stmt->fetchAll();
        
        foreach ($backupCodes as $backupCode) {
            if (password_verify($code, $backupCode['code'])) {
                // Код верен, помечаем как использованный
                $stmt = $conn->prepare("UPDATE backup_codes SET used = 1, used_at = NOW() WHERE id = ?");
                $stmt->execute([$backupCode['id']]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение количества неиспользованных backup кодов
     * @param int $userId ID пользователя
     * @return int Количество доступных кодов
     */
    public function getBackupCodesCount($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM backup_codes WHERE user_id = ? AND used = 0");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Проверка наличия backup кодов у пользователя
     * @param int $userId ID пользователя
     * @return bool True если есть хотя бы один неиспользованный код
     */
    public function hasBackupCodes($userId) {
        return $this->getBackupCodesCount($userId) > 0;
    }
    
    // ========================================
    // Методы для работы с ботом
    // ========================================
    
    /**
     * Получить канал бота по ID пользователя
     * @param int $userId ID пользователя
     * @return array|false Данные канала или false
     */
    public function getBotChannelByUserId($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            SELECT * FROM bot_channels 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Добавить канал для бота
     * @param int $userId ID пользователя
     * @param string $twitchUserId Twitch User ID
     * @param string $twitchUsername Twitch Username
     * @param string $displayName Display Name
     * @return bool Успешность операции
     * @throws Exception
     */
    public function addBotChannel($userId, $twitchUserId, $twitchUsername, $displayName = null) {
        $conn = $this->connection;
        
        try {
            // Проверяем, не добавлен ли уже этот канал
            $stmt = $conn->prepare("
                SELECT id, user_id FROM bot_channels 
                WHERE twitch_user_id = ? OR user_id = ?
            ");
            $stmt->execute([$twitchUserId, $userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($existing['user_id'] == $userId) {
                    throw new Exception('Бот уже подключен к вашему каналу');
                } else {
                    throw new Exception('Этот Twitch канал уже используется другим пользователем');
                }
            }
            
            // Добавляем канал
            $stmt = $conn->prepare("
                INSERT INTO bot_channels 
                (user_id, twitch_user_id, twitch_username, display_name, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $userId,
                $twitchUserId,
                $twitchUsername,
                $displayName ?? $twitchUsername
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            // Проверяем, существует ли таблица
            if (strpos($e->getMessage(), "doesn't exist") !== false || 
                strpos($e->getMessage(), "Table") !== false) {
                throw new Exception('Таблица bot_channels не создана. Выполните SQL из файла create_bot_table.sql');
            }
            throw new Exception('Ошибка базы данных: ' . $e->getMessage());
        }
    }
    
    /**
     * Удалить канал бота по ID пользователя
     * @param int $userId ID пользователя
     * @return bool Успешность операции
     */
    public function removeBotChannel($userId) {
        $conn = $this->connection;
        $stmt = $conn->prepare("DELETE FROM bot_channels WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Переключить статус активности канала
     * @param int $userId ID пользователя
     * @param bool $isActive Новый статус
     * @return bool Успешность операции
     */
    public function toggleBotChannel($userId, $isActive) {
        $conn = $this->connection;
        $stmt = $conn->prepare("
            UPDATE bot_channels 
            SET is_active = ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$isActive ? 1 : 0, $userId]);
        return $stmt->rowCount() > 0;
    }
}

