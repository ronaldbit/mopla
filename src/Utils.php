<?php
class Utils {
    public static function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    public static function escapeJs($str) {
        return str_replace(["\n", "\r", '"', "'"], ['\\n', '\\r', '\\"', "\\'"], $str);
    }

    public static function dump($var) {
        echo "<pre>" . htmlspecialchars(print_r($var, true)) . "</pre>";
    }

    public static function cleanText($str) {
        return trim(strip_tags($str));
    }

    public static function randomId($prefix = '', $length = 8) {
        return $prefix . bin2hex(random_bytes($length / 2));
    }

    public static function timeAgo($timestamp) {
        $diff = time() - strtotime($timestamp);
        if ($diff < 60) return "$diff segundos";
        if ($diff < 3600) return floor($diff / 60) . " minutos";
        if ($diff < 86400) return floor($diff / 3600) . " horas";
        return floor($diff / 86400) . " días";
    }

    public static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function truncateWords($str, $limit = 20, $end = '...') {
        $words = explode(' ', $str);
        return count($words) > $limit ? implode(' ', array_slice($words, 0, $limit)) . $end : $str;
    }


}
