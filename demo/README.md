## Configuración básica

```php
require_once 'src/Mopla.php';

$mopla = new Mopla([
    'template_dir' => __DIR__ . '/templates', // Ruta de plantillas
    'cache_dir' => __DIR__ . '/cache',        // Carpeta de caché compilada
    'debug' => true,                           // Modo desarrollo
    'cache' => false                           // Cache desactivada por defecto
]);

$data = [
    'titulo' => 'Bienvenido a Mopla',
    'usuario' => 'DemoUser',
    'version' => '1.0',
    'fecha' => date('Y-m-d'),
];

echo $mopla->render('index', $data);
```

---

## Características de Plantillas

```tpl
{set mensaje = "Hola mundo"}

<h1>{$titulo}</h1>
<p>Hola, {$usuario}. Hoy es {$fecha}</p>
<p>{$mensaje|upper}</p>

{if $usuario == "Admin"}
  <p>Bienvenido, administrador.</p>
{else}
  <p>Usuario estándar.</p>
{/if}

{include 'header'}
{include "footer.tpl"}

{extends "base.tpl"}
{block 'contenido'}
  <p>Contenido específico del hijo</p>
{/block}
```

---

## Soporte de archivos

Mopla acepta plantillas con extensiones:

- `.tpl`
- `.mp`

Si no se especifica extensión, probará primero `.mp` y luego `.tpl`.

---

## Includes con variables

```tpl
{include 'footer' with autor=$usuario, fecha=$fecha}
```

---

## Herencia de plantillas

```tpl
{extends 'base.tpl'}

{block 'contenido'}
  <h1>Contenido del hijo</h1>
{/block}
```

---

## Filtros disponibles

```tpl
{$nombre | upper}               Convierte a MAYÚSCULAS
{$nombre | lower}               Convierte a minúsculas
{$nombre | capitalize}          Convierte tipo Título ("hola mundo" → "Hola Mundo")
{$monto | number_format:2}      Formatea números decimales (ej. 1234.5 → 1,234.50)
{$comentario | escape}          Escapa HTML (<a> → &lt;a&gt;)
{$bio | truncate:60,"..."}      Acorta texto y añade ... si se pasa de límite
{$fecha | date_format:"d-m-Y"}  Da formato a fechas (Y-m-d → d/m/Y)
{$texto | strip_tags}           Elimina etiquetas HTML
{$productos | length}           Cuenta caracteres o elementos de array
```

---

## Filtros personalizados externos

También puedes registrar tus propios filtros en tiempo de ejecución:

```php
$mopla->registerFilter('slugify', function ($txt) {
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $txt), '-'));
});

$mopla->registerFilter('reverso', function ($txt) {
    return strrev($txt);
});
```