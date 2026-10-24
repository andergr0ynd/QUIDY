<?php
/**
 * API эндпоинт для получения данных о времени пользователя
 * GET /OAuth/api_user_time.php
 */

require_once 'config.php';
require_once 'Database.php';

// Устанавливаем заголовки для JSON ответа
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Проверяем авторизацию
    $user = null;
    if (isset($_COOKIE['session_token'])) {
        $db = Database::getInstance();
        $user = $db->getUserBySession($_COOKIE['session_token']);
    }
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }
    
    // Получаем данные о времени пользователя
    $conn = $db->getConnection();
    
    // Получаем общую статистику по всем каналам
    $stmt = $conn->prepare("
        SELECT 
            SUM(total_accumulated_time) as total_time,
            COUNT(CASE WHEN is_timer_active = 1 THEN 1 END) as active_timers,
            COUNT(DISTINCT channel) as channels_count,
            MAX(last_activity_timestamp) as last_activity
        FROM user_time_tracking 
        WHERE username = ?
    ");
    $stmt->execute([$user['username']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем детальную статистику по каналам
    $stmt = $conn->prepare("
        SELECT 
            channel,
            total_accumulated_time,
            is_timer_active,
            last_activity_timestamp,
            created_at,
            (SELECT COUNT(*) FROM user_time_tracking ut2 
             WHERE ut2.username = ut1.username 
             AND ut2.channel = ut1.channel 
             AND ut2.is_timer_active = 1) as active_sessions
        FROM user_time_tracking ut1
        WHERE username = ?
        ORDER BY total_accumulated_time DESC
    ");
    $stmt->execute([$user['username']]);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Форматируем время
    function formatTime($seconds) {
        if (!$seconds) return '0 сек';
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d ч %d мин', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%d мин %d сек', $minutes, $secs);
        } else {
            return sprintf('%d сек', $secs);
        }
    }
    
    // Подготавливаем ответ
    $response = [
        'success' => true,
        'data' => [
            'totalAccumulatedTime' => (int)($stats['total_time'] ?? 0),
            'timeFormatted' => formatTime($stats['total_time'] ?? 0),
            'isTimerActive' => ($stats['active_timers'] ?? 0) > 0,
            'channelsCount' => (int)($stats['channels_count'] ?? 0),
            'lastActivity' => $stats['last_activity'],
            'channels' => []
        ]
    ];
    
    // Добавляем данные по каналам
    foreach ($channels as $channel) {
        $response['data']['channels'][] = [
            'channel' => $channel['channel'],
            'totalTime' => (int)$channel['total_accumulated_time'],
            'timeFormatted' => formatTime($channel['total_accumulated_time']),
            'isActive' => (bool)$channel['is_timer_active'],
            'lastActivity' => $channel['last_activity_timestamp'],
            'createdAt' => $channel['created_at'],
            'activeSessions' => (int)$channel['active_sessions']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("API User Time Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
