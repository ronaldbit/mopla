<?php
class Filters {
    public static function registerDefaults(&$filters) {
        $filters['upper'] = 'strtoupper';
        $filters['lower'] = 'strtolower';
        $filters['number_format'] = 'number_format';
        $filters['escape'] = 'htmlspecialchars';
        $filters['truncate'] = [self::class, 'truncate'];
        $filters['date_format'] = [self::class, 'dateFormat'];
        $filters['capitalize'] = [self::class, 'capitalize'];
        $filters['strip_tags'] = 'strip_tags';
        $filters['length'] = [self::class, 'length'];
        $filters['not_empty'] = [self::class, 'notEmpty'];
        $filters['raw'] = [self::class, 'raw'];

    }

    public static function truncate(string $texto, int $longitud = 100, string $final = '...'): string {
        return mb_strlen($texto) > $longitud
            ? mb_substr($texto, 0, $longitud) . $final
            : $texto;
    }

    public static function dateFormat($fecha, string $formato = 'd/m/Y'): string {
        if (empty($fecha)) return '';
        $ts = is_numeric($fecha) ? (int)$fecha : strtotime($fecha);
        return date($formato, $ts);
    }

    public static function capitalize(string $texto): string {
        return ucwords(strtolower($texto));
    }

    public static function length($valor): int {
        if (is_array($valor)) return count($valor);
        return mb_strlen((string)$valor);
    }

    public static function notEmpty($valor): bool {
        return isset($valor) && $valor !== '' && $valor !== null;
    }

    public static function raw($valor) {
        return $valor; // sin escape
    }

}
