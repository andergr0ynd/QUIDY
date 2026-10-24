<?php
/**
 * Класс для работы с TOTP (Time-based One-Time Password)
 * Совместим с Google Authenticator, Authy и другими приложениями
 * Проверенная рабочая реализация
 */

class TOTP {
    
    /**
     * Генерация секретного ключа
     */
    public static function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Генерация TOTP кода
     */
    public static function getCode($secret, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = floor(time() / 30);
        }
        
        $secret = self::base32Decode($secret);
        $time = pack('N*', 0, $timestamp);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Проверка TOTP кода с временным окном
     */
    public static function verifyCode($secret, $code, $window = 2) {
        $timestamp = floor(time() / 30);
        
        // Убираем пробелы из кода
        $code = str_replace(' ', '', trim($code));
        
        // Проверяем временные окна
        for ($i = -$window; $i <= $window; $i++) {
            if (self::getCode($secret, $timestamp + $i) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Генерация QR-кода URL для Google Authenticator
     */
    public static function getQRCodeUrl($secret, $username, $issuer = 'Quidy') {
        $otpauthUrl = 'otpauth://totp/' . $issuer . ':' . rawurlencode($username) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauthUrl);
    }
    
    /**
     * Получение otpauth URI
     */
    public static function getOtpauthUrl($secret, $username, $issuer = 'Quidy') {
        return 'otpauth://totp/' . $issuer . ':' . rawurlencode($username) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }
    
    /**
     * Декодирование Base32 (упрощенная и надежная версия)
     */
    private static function base32Decode($string) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $string = strtoupper($string);
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($string); $i < $j; $i++) {
            $v <<= 5;
            $v += stripos($chars, $string[$i]);
            $vbits += 5;
            
            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr($v >> $vbits);
                $v &= ((1 << $vbits) - 1);
            }
        }
        
        return $output;
    }
}
