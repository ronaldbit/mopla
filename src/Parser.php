<?php
require_once __DIR__ . '/ErrorHandler.php';
 
/***Agregar soporte para helpers - ex: {call saludo:"Ronald"} -> Hola, Ronald */

class Parser {
    public static function parse($tpl, $vars, $filters, $lang, $fileName = '', &$bloquesEspeciales = null) {

        if (!is_array($bloquesEspeciales)) { $bloquesEspeciales = ['styles' => [],'scripts' => [],'json' => []]; }
        
        $tpl = self::procesarHerencia($tpl, $vars,$fileName = '');

        $tpl = self::procesarIncludes($tpl, $vars, $filters, $lang, $fileName, $bloquesEspeciales);

        self::procesarBloquesEspeciales($tpl, $bloquesEspeciales);
 
        $warnings = [];
        
        // Detectar variables definidas por {set $var = ...}
        preg_match_all('/\{set\s+\$([a-zA-Z_][\w]*)\s*=\s*(.+?)\}/', $tpl, $setMatches);
        foreach ($setMatches[1] as $setVar) {
            $vars[$setVar] = true; // marcamos como definidas
        }
        
        // 0. Variables locales en foreach (detectar y validar redeclaraciones)
        preg_match_all('/\{foreach\s+[^\}]*?\s+as\s+\$([a-zA-Z_][\w]*)\}/', $tpl, $foreachMatches);
        $localVars = [];
        foreach ($foreachMatches[1] as $localVar) {
            if (in_array($localVar, $localVars)) {
                $warnings[] = "Variable local '{$localVar}' redeclarada en foreach.";
            } else {
                $localVars[] = $localVar;
            }
        }

        // 1. Detectar todas las variables usadas {$var} con offset
        preg_match_all('/\{\$([a-zA-Z_][\w\.]*)(\|[^\}]+)?\}/', $tpl, $varMatches, PREG_OFFSET_CAPTURE);

        // Para detectar variables usadas fuera de su scope (las locales deben usarse solo dentro del foreach)
        // Detectar posición del foreach para cada variable local
        preg_match_all('/\{foreach\s+([^\}]+?)\s+as\s+\$([a-zA-Z_][\w]*)\}/', $tpl, $foreachInfo, PREG_OFFSET_CAPTURE);

        // Construir array para saber el rango de cada foreach local variable (inicio y fin)
        // Esto ayuda a detectar uso fuera de scope local var.
        $foreachRanges = [];
        foreach ($foreachInfo[2] as $idx => $localVarMatch) {
            $localVar = $localVarMatch[0];
            $start = $localVarMatch[1]; // posición inicio foreach
            $endTagPos = strpos($tpl, '{/foreach}', $start);
            if ($endTagPos === false) {
                // foreach mal cerrado (lo detectamos más adelante)
                $end = strlen($tpl);
            } else {
                $end = $endTagPos + strlen('{/foreach}');
            }
            $foreachRanges[$localVar][] = ['start' => $start, 'end' => $end];
        }

        // 2. Verificar variables globales no definidas y uso de locales fuera de su foreach
        $registradas = [];
        foreach ($varMatches[1] as $i => $varMatch) {
            $varFull = $varMatch[0];              // 'noExiste' o 'n.titulo'
            $offset = $varMatch[1];               // posición en el template
            $linea = substr_count(substr($tpl, 0, $offset), "\n") + 1;

            $path = explode('.', $varFull);
            $varBase = $path[0];

            $esLocal = in_array($varBase, $localVars);
            $definida = array_key_exists($varBase, $vars);
            $yaRegistrado = in_array($varBase . ':' . $linea, $registradas);

            if (!$esLocal && !$definida && !$yaRegistrado) {
                $tpl = self::mensaje($tpl, "Variable no definida: '{$varBase}'", $fileName, $linea, $offset);
                $registradas[] = $varBase . ':' . $linea;
            }

            // Uso de variable local fuera del foreach
            if ($esLocal) {
                // Verificar si está dentro del rango de algún foreach para esa variable
                $estaDentro = false;
                if (isset($foreachRanges[$varBase])) {
                    foreach ($foreachRanges[$varBase] as $range) {
                        if ($offset >= $range['start'] && $offset <= $range['end']) {
                            $estaDentro = true;
                            break;
                        }
                    }
                }
                if (!$estaDentro) {
                    $tpl = self::mensaje($tpl, "Variable local '{$varBase}' usada fuera del foreach correspondiente", $fileName, $linea, $offset);
                }
            }
        }

        // 3. Detectar filtros inexistentes y sintaxis incorrecta en filtros
        preg_match_all('/\{\$[a-zA-Z_][\w\.]*(\|[^\}]+)?\}/', $tpl, $filterMatches, PREG_OFFSET_CAPTURE);
        foreach ($filterMatches[1] as $filterMatch) {
            $filtrosRaw = $filterMatch[0]; // Ej: "|uppercase|escape:html"
            if (empty($filtrosRaw)) continue;

            $offset = $filterMatch[1];
            $linea = substr_count(substr($tpl, 0, $offset), "\n") + 1;

            $filtros = explode('|', trim($filtrosRaw, '|'));
            foreach ($filtros as $filtro) {
                $filtro = trim($filtro);
                if (empty($filtro)) {
                    $tpl = self::mensaje($tpl, "Filtro vacío detectado", $fileName, $linea, $offset);
                    continue;
                }
                if (strpos($filtro, ':') !== false) {
                    $partes = explode(':', $filtro);
                    if (count($partes) != 2 || strlen(trim($partes[1])) === 0) {
                        $tpl = self::mensaje($tpl, "Filtro con sintaxis incorrecta: '{$filtro}'", $fileName, $linea, $offset);
                    }
                    $func = $partes[0];
                } else {
                    $func = $filtro;
                }
                if (!array_key_exists($func, $filters)) {
                    $tpl = self::mensaje($tpl, "Filtro no definido: '{$func}'", $fileName, $linea, $offset);
                }
            }
        }

        $tpl = preg_replace_callback('/\{\$([a-zA-Z_][\w\.]*)(\|[^\}]+)?\}/', function ($m) use ($vars, $filters) {
            $nombre = $m[1];
            $valor = self::obtenerValorVariable($nombre, $vars); // deberías tener esta función ya

            if (!empty($m[2])) {
                $filtros = explode('|', trim($m[2], '|'));
                foreach ($filtros as $filtro) {
                    $filtro = trim($filtro);
                    if (strpos($filtro, ':') !== false) {
                        [$func, $param] = explode(':', $filtro, 2);
                        $param = trim($param);
                        if (isset($filters[$func])) {
                            $valor = call_user_func($filters[$func], $valor, $param);
                        }
                    } else {
                        if (isset($filters[$filtro])) {
                            $valor = call_user_func($filters[$filtro], $valor);
                        }
                    }
                }
            }

            return $valor;
        }, $tpl);


        // 4. Detectar includes y langs con variables (no permitidos)
        preg_match_all('/\{include\s+"([^"]*?)"\}/', $tpl, $includes, PREG_OFFSET_CAPTURE);
        foreach ($includes[1] as $inc) {
            if (strpos($inc[0], '$') !== false) {
                $linea = substr_count(substr($tpl, 0, $inc[1]), "\n") + 1;
                $tpl = self::mensaje($tpl, "Uso de variable en include no permitido", $fileName, $linea, $inc[1]);
            }
        }
        preg_match_all('/\{lang\s+"([^"]*?)"\}/', $tpl, $langs, PREG_OFFSET_CAPTURE);
        foreach ($langs[1] as $lng) {
            if (strpos($lng[0], '$') !== false) {
                $linea = substr_count(substr($tpl, 0, $lng[1]), "\n") + 1;
                $tpl = self::mensaje($tpl, "Uso de variable en lang no permitido", $fileName, $linea, $lng[1]);
            }
        }

        // 5. Detectar expresiones mal formadas en condicionales {if ...}
        preg_match_all('/\{if\s+([^\}]+)\}/', $tpl, $ifs, PREG_OFFSET_CAPTURE);
        foreach ($ifs[1] as $exp) {
            $expr = trim($exp[0]);
            $pos = $exp[1];
            $linea = substr_count(substr($tpl, 0, $pos), "\n") + 1;

            // Simple chequeo: no vacío, no terminar en operador
            if ($expr === '' ||
                preg_match('/(==|!=|>=|<=|>|<|&&|\|\|)$/', $expr) ||
                preg_match('/^[=!&|]/', $expr)) {
                $tpl = self::mensaje($tpl, "Expresión if mal formada: '{$expr}'", $fileName, $linea, $pos);
            }
        }

        // 6. Detectar acceso a propiedades con sintaxis inválida {$var..prop} o {$var.}
        preg_match_all('/\{\$([a-zA-Z_][\w]*\.(\.|\$|\.{2,}|$))/m', $tpl, $badProp, PREG_OFFSET_CAPTURE);
        foreach ($badProp[1] as $bad) {
            $pos = $bad[1];
            $linea = substr_count(substr($tpl, 0, $pos), "\n") + 1;
            $tpl = self::mensaje($tpl, "Acceso inválido a propiedad: '{$bad[0]}'", $fileName, $linea, $pos);
        }

        // 7. Detectar comentarios mal cerrados {# ... (sin #})
        preg_match_all('/\{#.*$/m', $tpl, $comentariosMalCerrados, PREG_OFFSET_CAPTURE);
        foreach ($comentariosMalCerrados[0] as $com) {
            // Sólo si no tiene cierre #}
            if (strpos($com[0], '#}') === false) {
                $pos = $com[1];
                $linea = substr_count(substr($tpl, 0, $pos), "\n") + 1;
                $tpl = self::mensaje($tpl, "Comentario mal cerrado", $fileName, $linea, $pos);
            }
        }

        // 8. Detectar estructuras mal cerradas {if}, {foreach}
        /***        
        preg_match_all('/\{\s*if\s+[^\}]+\}/', $tpl, $openIfMatches);
        preg_match_all('/\{\s*(\/if|endif)\s*\}/', $tpl, $closeIfMatches);

        $openIf = count($openIfMatches[0]);
        $closeIf = count($closeIfMatches[0]);

        if ($openIf !== $closeIf) {
            $tpl = self::mensaje($tpl, "Hay $openIf {if} y $closeIf {endif o /if}. Verifica los cierres. e".$fileName.$linea, $fileName, 0, 0);
        } **/

        $openForeach = preg_match_all('/\{\s*foreach\s+[^\}]+\}/', $tpl);
        $closeForeach = preg_match_all('/\{\s*\/foreach\s*\}/', $tpl);
        if ($openForeach !== $closeForeach) {
            $tpl = self::mensaje($tpl, "Número de {foreach} y {/foreach} no coinciden", $fileName, 0, 0);
        }

        // 9. Reemplazo de comentarios
        $tpl = preg_replace('/\{#.*?#\}/s', '', $tpl);

        // Includes
        $tpl = preg_replace_callback('/\{include "(.+?)"\}/', function ($m) use ($vars) {
            $path = $vars['__templateDir'] . '/' . $m[1] . '.mopla';
            return file_exists($path) ? file_get_contents($path) : '';
        }, $tpl);

        // Traducciones
        $tpl = preg_replace_callback('/\{lang "(.+?)"\}/', function ($m) use ($lang) {
            return $lang[$m[1]] ?? $m[1];
        }, $tpl);

        // Soporte para {set $var = valor}
        $tpl = preg_replace_callback('/\{set\s+\$([a-zA-Z_][\w]*)\s*=\s*(.+?)\}/', function ($m) {
            $varName = $m[1];
            $valor = $m[2];

            // Convertir $d.total a $d["total"]
            $valor = preg_replace_callback('/\$([a-zA-Z_][\w]*)(?:\.([a-zA-Z_][\w]*))*/', function($vm) {
                $base = '$' . $vm[1];
                $resto = '';
                if (isset($vm[0])) {
                    $piezas = explode('.', $vm[0]);
                    array_shift($piezas); // quitamos el $
                    foreach ($piezas as $p) {
                        $resto .= '["' . $p . '"]';
                    }
                }
                return $base . $resto;
            }, $valor);

            return "<?php \$$varName = $valor; ?>";
        }, $tpl);


        $tpl = preg_replace_callback('/\{\$(.+?)\}/', function ($m) use ($filters, $vars, $fileName) {
            $exp = trim($m[1]);

            if (preg_match('/^\_(GET|POST|SESSION|COOKIE|REQUEST)(\[.+\])?$/', $exp)) { return '<?= ' . '$' . $exp . ' ?>'; }

            // Separar expresión base y filtros
            if (strpos($exp, '|') !== false) {
                [$core, $rawFiltros] = explode('|', $exp, 2);
                $core = trim($core);
                $rawFiltros = trim($rawFiltros);
            } else {
                $core = $exp;
                $rawFiltros = '';
            }

            // Si es acceso a propiedad tipo $obj.prop1.prop2
            if (preg_match('/^[a-zA-Z_][\w\.]*$/', $core)) {
                $path = explode('.', $core);
                $var = '$' . array_shift($path);
                foreach ($path as $segment) { $var .= '["' . $segment . '"]'; }
            } else { $var = $core; }

            // Aplicar filtros si existen
            if (!empty($rawFiltros)) {
                $filtros = explode('|', $rawFiltros);
                foreach ($filtros as $filtro) {
                    $filtro = trim($filtro);
                    if (strpos($filtro, ':') !== false) {
                        [$func, $argsRaw] = explode(':', $filtro, 2);
                        $args = array_map('trim', explode(',', $argsRaw));
                        $quotedArgs = array_map(function ($a) {
                            $a = trim($a, '"\'');
                            return is_numeric($a) ? $a : '"' . addslashes($a) . '"';
                        }, $args);
                        $var = '$filters["' . trim($func) . '"](' . $var . ', ' . implode(', ', $quotedArgs) . ')';
                    } else {
                        $var = '$filters["' . $filtro . '"](' . $var . ')';
                    }
                }
            }

            // Escapar si es variable simple (seguridad)
            if (preg_match('/^[\$a-zA-Z_][\w\[\]"\']*$/', $var)) {
                return '<?= htmlspecialchars(' . $var . ') ?>';
            }

            // No escapar si es expresión compleja
            return '<?= (' . $var . ') ?>';
        }, $tpl); 

        // Condicionales y bucles
        $tpl = preg_replace_callback('/\{\s*if\s+(.+?)\}/', function($m) {
            return "<?php if(" . Parser::protegerGlobals($m[1]) . "): ?>";
        }, $tpl);

        $tpl = preg_replace_callback('/\{\s*elseif\s+(.+?)\}/', function($m) {
            return "<?php elseif(" . Parser::protegerGlobals($m[1]) . "): ?>";
        }, $tpl);

        $tpl = preg_replace('/\{\s*else\s*\}/', '<?php else: ?>', $tpl);
        $tpl = preg_replace('/\{\s*\/if\s*\}/', '<?php endif; ?>', $tpl);
        $tpl = preg_replace('/\{\s*endif\s*\}/', '<?php endif; ?>', $tpl);
        $tpl = preg_replace('/\{\s*foreach\s+(.+?)\s+as\s+(.+?)\}/', '<?php foreach ($1 as $2): ?>', $tpl);
        $tpl = preg_replace('/\{\s*\/foreach\s*\}/', '<?php endforeach; ?>', $tpl);

        return $tpl;
        //echo "<pre>" . htmlspecialchars($tpl) . "</pre>";
        file_put_contents(__DIR__ . '/../debug_final_template.php', $tpl);
    }
    
protected static function obtenerValorVariable(string $nombre, array $vars) {
    $partes = explode('.', $nombre);
    $valor = $vars;

    foreach ($partes as $parte) {
        if (is_array($valor) && isset($valor[$parte])) {
            $valor = $valor[$parte];
        } else {
            return ''; // Devuelve vacío si no existe
        }
    }

    return $valor;
}


private static function protegerGlobals(string $cond): string {
    return preg_replace_callback(
        '/\$_(GET|POST|SESSION|COOKIE|REQUEST)\s*\[\s*[\'"](.+?)[\'"]\s*\]/',
        function($m) {
            $global = $m[1];
            $clave = $m[2];
            return "(isset(\$_{$global}['{$clave}']) && \$_{$global}['{$clave}'])";
        },
        $cond
    );
}


private static function procesarBloquesEspeciales(&$tpl, array &$bloquesEspeciales): void {
    foreach (['styles', 'scripts', 'json'] as $tipo) {
        preg_match_all('/\{' . $tipo . '(?:\s+([a-zA-Z_][\w]*))?\}(.*?)\{\/' . $tipo . '\}/s', $tpl, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $nombre = $m[1] ?? '__global';
            $contenido = trim($m[2]);

            if (!isset($bloquesEspeciales[$tipo][$nombre])) {
                $bloquesEspeciales[$tipo][$nombre] = [];
            }

            $bloquesEspeciales[$tipo][$nombre][] = $contenido;
            $tpl = str_replace($m[0], '', $tpl);
        }
    }

    foreach ($bloquesEspeciales as $tipo => $grupo) {
        foreach ($grupo as $nombre => $bloques) {
            $etiqueta = '{print_' . $tipo . ($nombre !== '__global' ? '_' . $nombre : '') . '}';

            if ($tipo === 'styles') {
                $contenido = "<style>\n" . implode("\n", $bloques) . "\n</style>";
            } elseif ($tipo === 'scripts') {
                $contenido = "<script>\n" . implode("\n", $bloques) . "\n</script>";
            } elseif ($tipo === 'json') {
                $contenido = '<script type="application/json" id="json_' . $nombre . "\">\n" . implode("\n", $bloques) . "\n</script>";
            } else {
                $contenido = implode("\n", $bloques);
            }

            $tpl = str_replace($etiqueta, $contenido, $tpl);
        }

        // Print all (por ejemplo: {print_styles})
        $etiquetaTodos = '{print_' . $tipo . '}';
        $todos = [];
        foreach ($grupo as $bloques) {
            $todos = array_merge($todos, $bloques);
        }

        if ($tipo === 'styles') {
            $contenidoTodos = "<style>\n" . implode("\n", $todos) . "\n</style>";
        } elseif ($tipo === 'scripts') {
            $contenidoTodos = "<script>\n" . implode("\n", $todos) . "\n</script>";
        } elseif ($tipo === 'json') {
            $contenidoTodos = '<script type="application/json" id="json_all">' . "\n" . implode("\n", $todos) . "\n</script>";
        } else {
            $contenidoTodos = implode("\n", $todos);
        }

        $tpl = str_replace($etiquetaTodos, $contenidoTodos, $tpl);
    }
}


public static function procesarHerencia(string $tpl, array $vars, $fileName): string {
    // Detectar si la plantilla extiende otra
    if (!preg_match('/\{extends [\'"](.+?)[\'"]( with (.+?))?\}/', $tpl, $match)) {
        return $tpl; // No hay herencia
    }

    $baseName = $match[1];
    $tpl = str_replace($match[0], '', $tpl); // Eliminar {extends ...} del hijo

    // Procesar variables pasadas con 'with'
    if (isset($match[2])) {
        $rawVars = explode(',', $match[2]);
        foreach ($rawVars as $v) {
            [$clave, $valor] = array_map('trim', explode('=', $v));
            if (isset($vars[trim($valor, '$')])) {
                $vars[$clave] = $vars[trim($valor, '$')];
            }
        }
    }

    // Cargar plantilla base
    $templateDir = $vars['__templateDir'] ?? 'templates';
    $basePath = $templateDir . '/' . $baseName;

    if (!preg_match('/\.(tpl|mp)$/', $baseName)) {
        $tryTpl = $basePath . '.tpl';
        $tryMp  = $basePath . '.mp';

        if (file_exists($tryTpl)) {
            $basePath = $tryTpl;
        } elseif (file_exists($tryMp)) {
            $basePath = $tryMp;
        } else {
            return self::mensaje($tpl, "Plantilla base '{$baseName}' no encontrada (.tpl ni .mp)", $fileName, 0, 0);
        }
    }

    if (!file_exists($basePath)) {
        return self::mensaje($tpl, "Plantilla base '{$baseName}' no encontrada", $fileName, 0, 0);
    }

    $baseContent = file_get_contents($basePath);

    // Extraer bloques del hijo
    preg_match_all("/\{block '(.+?)'\}(.*?)\{\/block\}/s", $tpl, $hijoMatches, PREG_SET_ORDER);
    $bloquesHijo = [];
    foreach ($hijoMatches as $m) {
        $bloquesHijo[$m[1]] = $m[2];
    }

    // Reemplazar bloques en el padre
    $resultado = preg_replace_callback("/\{block '(.+?)'\}(.*?)\{\/block\}/s", function($m) use ($bloquesHijo) {
        $nombre = $m[1];
        return $bloquesHijo[$nombre] ?? $m[2];
    }, $baseContent);

    $resultado = preg_replace("/\{block '(.+?)'\}(.*?)\{\/block\}/s", '$2', $resultado);

    return $resultado;
}

public static function procesarIncludes(string $tpl, array $vars, array $filters, array $lang, string $fileName, array &$bloquesEspeciales): string {
    return preg_replace_callback('/\{include [\'"](.+?)[\'"](?: with (.+?))?\}/', function($m) use ($vars, $filters, $lang, $fileName, &$bloquesEspeciales) {
        $ruta = $m[1];
        $templateDir = $vars['__templateDir'] ?? 'templates';

        $path = $templateDir . '/' . $ruta;

        // Detectar extensión automáticamente
        if (!preg_match('/\.(tpl|mp)$/', $ruta)) {
            $tryTpl = $path . '.tpl';
            $tryMp  = $path . '.mp';

            if (file_exists($tryTpl)) {
                $path = $tryTpl;
            } elseif (file_exists($tryMp)) {
                $path = $tryMp;
            } else {
                return ErrorHandler::handleError(E_USER_WARNING, "Archivo incluido no encontrado: '$ruta(.tpl|.mp)'", $fileName, 0);
            }
        } else {
            if (!file_exists($path)) {
                return ErrorHandler::handleError(E_USER_WARNING, "Archivo incluido no encontrado: '$ruta'", $fileName, 0);
            }
        }

        if (!file_exists($path)) {
            return ErrorHandler::handleError(E_USER_WARNING, "Archivo incluido no encontrado: '$ruta'", $fileName, 0);
        }

        $contenido = file_get_contents($path);

        // Preparar variables para el include
        $varsIncluidas = $vars;

        if (!empty($m[2])) {
            $pares = explode(',', $m[2]);
            foreach ($pares as $par) {
                [$clave, $valor] = array_map('trim', explode('=', $par));
                if (isset($vars[trim($valor, '$')])) {
                    $varsIncluidas[$clave] = $vars[trim($valor, '$')];
                } else {
                    ErrorHandler::addWarning("Variable '{$valor}' no encontrada para include '{$ruta}'", $fileName, 0);
                }
            }
        }

        // Volver a procesar internamente el include pasando $bloquesEspeciales por referencia
        return self::parse($contenido, $varsIncluidas, $filters, $lang, $ruta, $bloquesEspeciales);
    }, $tpl);
}



    private static function mensaje($tpl, $msg, $fileName, $linea, $offset) {
        $warningHtml = ErrorHandler::handleError(E_USER_WARNING, $msg, $fileName, $linea);
        $startPos = $offset;
        while ($startPos > 0 && $tpl[$startPos] !== '<') {
            $startPos--;
        }
        $endPos = strpos($tpl, '>', $offset);
        if ($endPos === false) {
            $endPos = strlen($tpl) - 1;
        }
        $length = $endPos - $startPos + 1;
        return substr_replace($tpl, $warningHtml, $startPos, $length);
    }
}
