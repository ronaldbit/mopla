<?php
require_once __DIR__ . '/Parser.php';
require_once __DIR__ . '/Filters.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/ErrorHandler.php';

class Mopla {
    protected $vars = [];
    protected $filters = [];
    protected $templateDir;
    protected $cacheDir;
    protected $langDir;
    protected $lang = [];

    protected $debug = false;
    protected $production = false;
    protected bool $useCache = true;

    public function __construct($options = []) {
        $this->templateDir = $options['templateDir'] ?? 'templates';
        $this->cacheDir = $options['cacheDir'] ?? 'cache';
        $this->langDir = $options['langDir'] ?? 'lang';
        $this->useCache = $options['cache'] ?? true;
        Filters::registerDefaults($this->filters);
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0777, true);
        }
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function setTemplateDir($path) {
        $this->templateDir = $path;
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0777, true);
        }
    }

    public function setCacheDir($path) {
        $this->cacheDir = $path;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
 
    public function assign($key, $value) {
        $this->vars[$key] = $value;
    }

    public function registerFilter($name, $callback) {
        $this->filters[$name] = $callback;
    }

    public function loadLang($file) {
        $path = "{$this->langDir}/{$file}";
        if (file_exists($path)) {
            $this->lang = json_decode(file_get_contents($path), true);
        }
    }
    
    public function render($template, $vars = []) {
        $this->vars = array_merge($this->vars, $vars);
        $this->vars['__templateDir'] = $this->templateDir;
        $this->vars['__debug'] = $this->isDebug();
        $bloquesEspeciales = [];
        $baseDir = $this->templateDir ?? ''; 
        $extension = pathinfo($template, PATHINFO_EXTENSION);
        $finalPath = '';
        if ($extension === '') {
            $found = false;
            foreach (['.mp', '.tpl'] as $ext) {
                $tryPath = rtrim($baseDir, '/') . '/' . $template . $ext;
                if (file_exists($tryPath)) {
                    $template .= $ext;
                    $finalPath = $tryPath;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                ErrorHandler::addError("No se encontró la plantilla '$template' con extensión .mp o .tpl.");
                exit;
            }
        } else {
            $fullPath = rtrim($baseDir, '/') . '/' . $template;
            if (file_exists($fullPath)) {
                $finalPath = $fullPath;
            } elseif (file_exists($template)) { 
                $finalPath = $template;
            } else {
                ErrorHandler::addError("No se encontró la plantilla '$template'.");
                exit;
            }
        }

        $realPath = realpath($finalPath);
        $templateRoot = realpath($this->templateDir);

        if (!$realPath || !$templateRoot || strpos($realPath, $templateRoot) !== 0) {
            ErrorHandler::addError("Acceso denegado: intento de cargar una plantilla fuera del directorio permitido.");
            exit;
        }

        $content = file_get_contents($finalPath);
        $parsed = Parser::parse($content, $this->vars, $this->filters, $this->lang, $template, $bloquesEspeciales);

        if (ErrorHandler::hasErrors()) {
            if ($this->isProductionMode()) {
                echo 'un error crítico';
                exit;
            } elseif ($this->isDebug()) {
                // echo ErrorHandler::renderHtml();
            }
        }

        if ($this->useCache) {
            $cacheFile = ($this->cacheDir ?? 'cache') . '/' . md5($template . filemtime($finalPath)) . '.php';
            if (!file_exists($cacheFile)) {
                file_put_contents($cacheFile, $parsed);
            }
        }
        extract($this->vars);
        ob_start();
        if ($this->useCache && isset($cacheFile) && file_exists($cacheFile)) {
            include $cacheFile;
        } else {
            eval('?>' . $parsed);
        }
        return ob_get_clean();
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function isDebug() {
        return $this->debug;
    }

    public function setProductionMode($production) {
        $this->production = $production;
    }

    public function isProductionMode() {
        return $this->production;
    }
}
