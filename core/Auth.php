<?php
/**
 * ChubbyCMS - Auth & Security Module
 */

namespace Core;

class Auth {
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Strict');
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', '1');
            }
            session_start();
        }
    }

    public static function isLoggedIn() {
        self::initSession();

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check session expiration (e.g., 2 hours)
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
                session_unset();
                session_destroy();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function login($password, $config) {
        // IP-based rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheFile = sys_get_temp_dir() . '/cms_login_' . md5($ip);
        $cache = [];
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true) ?: [];
        }

        $attempts = $cache['attempts'] ?? 0;
        $last_attempt = $cache['last_attempt'] ?? 0;

        if ($attempts >= 5 && (time() - $last_attempt) < 300) {
            return 'too_many_attempts';
        }

        if (password_verify($password, $config['password_hash'])) {
            self::initSession();
            $_SESSION['temp_auth'] = true;
            $_SESSION['temp_auth_time'] = time();
            if (file_exists($cacheFile)) unlink($cacheFile);
            return true;
        }

        $cache['attempts'] = $attempts + 1;
        $cache['last_attempt'] = time();
        file_put_contents($cacheFile, json_encode($cache));
        return false;
    }

    public static function verify2FA($code, $config) {
        if (!isset($_SESSION['temp_auth']) || (time() - $_SESSION['temp_auth_time']) > 300) {
            return false;
        }

        // Simple TOTP-like check for now, or actual TOTP if we implement it
        // The user mentioned "TOTP 2FA compatible with Google Authenticator"
        // I will implement a basic TOTP class or logic here.
        if (self::checkTOTP($config['2fa_secret'], $code)) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            unset($_SESSION['temp_auth']);
            unset($_SESSION['temp_auth_time']);
            return true;
        }
        return false;
    }

    public static function generateCSRFToken() {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token) {
        self::initSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Basic TOTP verification logic
     * Compatible with Google Authenticator
     */
    public static function checkTOTP($secret, $code) {
        if (empty($secret)) return false;

        // If the secret is the default '123456' from the old version, just check it directly
        if ($secret === '123456') return $code === '123456';

        // Real TOTP implementation would go here.
        // For the sake of "production-ready" without heavy dependencies:
        return self::verifyGoogleAuthenticatorCode($secret, $code);
    }

    private static function verifyGoogleAuthenticatorCode($secret, $code) {
        $checkResult = false;
        for ($i = -1; $i <= 1; $i++) {
            if (self::calculateCode($secret, $i) === $code) {
                return true;
            }
        }
        return false;
    }

    private static function calculateCode($secret, $timeOffset = 0) {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $secret = str_replace(' ', '', $secret);

        $rawSecret = '';
        $buffer = 0;
        $bufferSize = 0;
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $pos = strpos($base32chars, $char);
            if ($pos === false) continue;
            $buffer = ($buffer << 5) | $pos;
            $bufferSize += 5;
            if ($bufferSize >= 8) {
                $rawSecret .= chr(($buffer >> ($bufferSize - 8)) & 0xFF);
                $bufferSize -= 8;
            }
        }

        $time = floor(time() / 30);
        $time += $timeOffset;
        $time = pack('N*', 0) . pack('N*', $time);

        $hash = hash_hmac('sha1', $time, $rawSecret, true);
        $offset = ord($hash[19]) & 0xf;
        $otp = (
            (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad($otp, 6, '0', STR_PAD_LEFT);
    }
}
