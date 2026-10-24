<?php
/**
 * Система версионирования Quidy.ru
 * Формат: Beta X.YYYYY.Z
 * X - мажорная версия (0 = бета)
 * YYYYY - номер сборки (5 цифр)
 * Z - минорная версия
 */

class VersionManager {
    private static $version = '0.84729.7';
    private static $build_date = '2024-12-27';
    private static $build_number = '84729';
    
    /**
     * Получить текущую версию
     */
    public static function getVersion() {
        return self::$version;
    }
    
    /**
     * Получить полную версию с префиксом
     */
    public static function getFullVersion() {
        return 'Beta ' . self::$version;
    }
    
    /**
     * Получить номер сборки
     */
    public static function getBuildNumber() {
        return self::$build_number;
    }
    
    /**
     * Получить дату сборки
     */
    public static function getBuildDate() {
        return self::$build_date;
    }
    
    /**
     * Получить информацию для футера
     */
    public static function getFooterInfo() {
        return [
            'version' => self::getFullVersion(),
            'build' => self::$build_number,
            'date' => self::$build_date,
            'year' => date('Y')
        ];
    }
    
    /**
     * Получить информацию для заголовка
     */
    public static function getHeaderInfo() {
        return [
            'version' => self::getFullVersion(),
            'build' => self::$build_number
        ];
    }
    
    /**
     * Генерация нового номера сборки
     */
    public static function generateBuildNumber() {
        // Генерируем номер на основе текущей даты и времени
        $timestamp = time();
        $date = date('md'); // Месяц и день (например: 1016)
        $time = date('Hi'); // Час и минута (например: 1430)
        
        // Создаем 5-значный номер
        $build = substr($timestamp, -5);
        
        return $build;
    }
    
    /**
     * Обновить версию (для разработки)
     */
    public static function updateVersion($new_version = null) {
        if ($new_version === null) {
            // Автоматическое обновление минорной версии
            $parts = explode('.', self::$version);
            $parts[2] = (int)$parts[2] + 1;
            $new_version = implode('.', $parts);
        }
        
        self::$version = $new_version;
        self::$build_number = self::generateBuildNumber();
        self::$build_date = date('Y-m-d');
        
        return self::$version;
    }
}

// Для обратной совместимости
function getVersion() {
    return VersionManager::getFullVersion();
}

function getBuildInfo() {
    return VersionManager::getFooterInfo();
}
?>
