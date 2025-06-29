# Mopla Template Engine
Un motor ligero de plantillas para PHP.

**Mopla** es un MOtor de PLAntillas PHP ligero inspirado en sistemas como Twig o Smarty, pero simplificado y hecho a medida.

---

## 🚀 Características

- Sintaxis simple: `{$variable}`, `{$obj.propiedad}`
- Filtros personalizables: `{$nombre|mayusculas}`
- Estructuras de control: `if`, `else`, `foreach`
- Sistema de caché (compilación previa de plantillas)
- Protección automática con `htmlspecialchars`
- Soporte para includes (`{include 'header'}`)

---

## 📦 Estructura del Proyecto

```

mopla/
├── Mopla.php          # Motor principal
├── templates/         # Archivos .tpl
├── mi\_cache/          # Plantillas compiladas en PHP
├── index.php          # Ejemplo de uso
└── README.md          # Documentación

````

---

## 🛠️ Uso Básico

```php
require 'Mopla.php';

$mopla = new Mopla('templates/', 'mi_cache/');
$mopla->assign('usuario', 'Ronald');
$mopla->assign('title', 'Noticias de hoy');
$mopla->assign('noticias', [
  ['titulo' => 'PHP 8.3', 'contenido' => '¡Ya disponible!'],
  ['titulo' => 'Mopla', 'contenido' => 'Tu propio motor.']
]);

$mopla->render('inicio');
````

---

## 🧪 Sintaxis Soportada

### Variables

```tpl
{$usuario}
{$n.titulo}
```

### Filtros

```tpl
{$usuario|mayusculas}
{$texto|recortar:50}
```

### Estructuras

```tpl
{if $usuario == "Ronald"}
    <p>Bienvenido creador</p>
{else}
    <p>Bienvenido visitante</p>
{/if}

{foreach $noticias as $n}
    <li><strong>{$n.titulo}</strong><br>{$n.contenido}</li>
{/foreach}
```

### Includes

```tpl
{include 'header'}
```

---

## 🔧 Registro de Filtros

```php
$mopla->addFilter('mayusculas', function($texto) {
    return strtoupper($texto);
});
```

---

## 🧼 Caché

Las plantillas `.tpl` se convierten en `.php` dentro del directorio `mi_cache/`. Si haces cambios en la sintaxis del parser, asegúrate de limpiar los archivos allí para evitar errores.

---

## 📜 Licencia

MIT © 2025 - Ronald

 