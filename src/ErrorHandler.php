<?php

class ErrorHandler {
 
    private static array $errors = [];
    private static bool $showCallStack = true;
    
    public static function addError(string $mensaje, string $archivo = '', int $linea = 0): void {
        self::$errors[] = [
            'tipo' => 'error',
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'stack' => self::$showCallStack ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : []
        ];
    }

    public static function addWarning(string $mensaje, string $archivo = '', int $linea = 0): void {
        self::$errors[] = [
            'tipo' => 'warning',
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'stack' => self::$showCallStack ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : []
        ];
         
    }

    public static function hasErrors(): bool {
        return !empty(self::$errors);
    }
    
    // Nuevo método que genera el bloque HTML para UN error o warning
    public static function renderSingleError(array $e): string {
        ob_start(); ?>
        <div style="z-index: 1000;position: absolute;font-family: 'Segoe UI', sans-serif;padding: 1rem;margin-bottom: 1rem;border-radius: 6px;box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);width: 50%;border-left: 5px solid <?= $e['tipo'] === 'error' ? '#dc3545' : '#ffc107' ?>; background-color: <?= $e['tipo'] === 'error' ? '#f8d7da' : '#fff3cd' ?>;color: <?= $e['tipo'] === 'error' ? '#721c24' : '#856404' ?>;">
            <div style="display: flex;align-items: center;gap: 0.5rem;font-weight: bold;">
                <span style="font-size: 1rem;"><?= $e['tipo'] === 'error' ? '❌' : '⚠️' ?></span>
                <span><?= ucfirst($e['tipo']) ?>: <?= htmlspecialchars($e['mensaje']) ?></span>
            </div>
            <?php if ($e['archivo']): ?>
            <div style="margin-top: 0.25rem;font-size: 0.9rem;">En <code style="background: rgba(0, 0, 0, 0.05); padding: 0 4px; border-radius: 3px;"><?= htmlspecialchars($e['archivo']) ?></code> línea <strong><?= $e['linea'] ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($e['stack'])): ?>
            <div style="margin-top: 1rem;">
                <div style="font-weight: bold; margin-bottom: 0.5rem;">Call Stack</div>
                <div style="display: grid;grid-template-columns: 40px 0.5fr 1fr 1fr;font-size: 0.85rem;">
                    <div style="display: contents;">
                        <div style="font-weight: bold; background: #f1f1f1; padding: 6px; border-bottom: 1px solid #ccc;">#</div>
                        <div style="font-weight: bold; background: #f1f1f1; padding: 6px; border-bottom: 1px solid #ccc;">Function</div>
                        <div style="font-weight: bold; background: #f1f1f1; padding: 6px; border-bottom: 1px solid #ccc;">File</div>
                        <div style="font-weight: bold; background: #f1f1f1; padding: 6px; border-bottom: 1px solid #ccc;">Class</div>
                    </div>
                    <?php foreach (array_slice($e['stack'], 0, 10) as $i => $s): ?>
                        <div style="display: flex; align-items: center; padding: 5px; border-bottom: 1px solid #eee;"><?= $i ?></div>
                        <div style="display: flex; align-items: center; padding: 5px; border-bottom: 1px solid #eee;"><?= $s['function'] ?? '' ?></div>
                        <div style="display: flex; align-items: center; padding: 5px; border-bottom: 1px solid #eee;"><code style="font-family: monospace;background: #f8f9fa;padding: 2px 4px;font-size: 10px;border-radius: 4px;"><?= $s['file'] ?? '' ?>::<?= $s['line'] ?? '' ?></code></div>
                        <div style="display: flex; align-items: center; padding: 5px; border-bottom: 1px solid #eee;"><?= $s['class'] ?? '' ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean();
    }

    public static function showCallStack(bool $show): void {
        self::$showCallStack = $show;
    }
 
    public static function handleError(int $errno, string $mensaje, string $archivo = '', int $linea = 0): ?string {
        $warnings = [E_USER_WARNING, E_WARNING, E_USER_NOTICE, E_NOTICE];
        $tipo = in_array($errno, $warnings) ? 'warning' : 'error';

        $errorData = [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'linea' => $linea,
            'stack' => self::$showCallStack ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : []
        ];

        if ($tipo === 'warning') { 
            return self::renderSingleError($errorData);
        } else { 
            $html = self::renderSingleError($errorData);
            return $html;
        }
    }

 
}
