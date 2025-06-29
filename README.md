# Documentación Completa de Mopla

## 1. Introducción

**Mopla** (Motor de Plantillas Liviano en PHP) es un sistema personalizado diseñado para procesar plantillas con una sintaxis flexible que permite control de flujo, filtros, inclusión de archivos y manejo de errores, todo con alto control y trazabilidad. Ideal para proyectos que requieren una capa de separación entre lógica PHP y presentación HTML.

### Objetivo

Facilitar la creación de interfaces dinámicas sin mezclar directamente PHP con HTML, permitiendo así una estructura de trabajo más mantenible y clara.

### Requisitos mínimos

* PHP >= 7.4
* Composer (para gestionar dependencias)
* Servidor local o entorno LAMP/WAMP

---

## 2. Instalación

### Paso 1: Clonar el proyecto

```bash
git clone https://github.com/usuario/mopla.git
cd mopla
```

### Paso 2: Instalar dependencias

```bash
composer install
```

### Paso 3: Probar ejecución

Puedes usar `debug_final_template.php` como punto de entrada para ver el sistema en acción.

```bash
php debug_final_template.php
```

---

## 3. Estructura del Proyecto

```
├── src/              # Código fuente del motor
│   ├── Mopla.php     # Clase principal del motor
│   ├── Parser.php    # Analiza la plantilla
│   ├── Filters.php   # Filtros aplicables a variables
│   ├── ErrorHandler.php # Manejo de errores y advertencias
│   └── Utils.php     # Utilidades generales
├── docs/             # Documentación previa
├── debug_final_template.php # Ejemplo de uso
├── composer.json     # Dependencias
```

---

## 4. Configuración

Actualmente Mopla no usa un archivo de configuración global, pero puedes personalizar:

* **Filtros:** en `Filters.php`
* **Directivas de plantilla:** en `Parser.php`
* **Estilo de errores:** en `ErrorHandler.php`

---

## 5. Uso Básico

Una plantilla puede usar sintaxis como:

```html
{set title = "Hola Mundo"}
<h1>{$title|upper}</h1>

{if $user.admin}
  <p>Bienvenido, administrador</p>
{else}
  <p>Bienvenido, usuario</p>
{/if}

{include 'footer.html'}
```

Para renderizar una plantilla:

```php
require 'src/Mopla.php';
$mopla = new Mopla();
echo $mopla->render('plantillas/home.html', ['user' => $usuario]);
```

---

## 6. Explicación del Código

### `Mopla.php`

* Clase principal que gestiona el flujo general: carga plantilla, analiza y renderiza.

### `Parser.php`

* Encargado de convertir el texto con sintaxis Mopla en PHP nativo.
* Interpreta `{if}`, `{for}`, `{set}`, `{include}`, etc.

### `Filters.php`

* Define filtros como `upper`, `lower`, `length`, etc.
* Se aplican a variables como `{$nombre|upper}`

### `ErrorHandler.php`

* Captura errores y advertencias durante el renderizado.
* Muestra línea exacta y contexto del error en la plantilla.

### `Utils.php`

* Funciones de utilidad para apoyo al procesamiento de cadenas y estructuras.

---

## 7. Ejemplos de Uso

```php
$vars = [
  'title' => 'Hola Mundo',
  'user' => [
    'admin' => true,
    'nombre' => 'Luis'
  ]
];
$mopla = new Mopla();
echo $mopla->render('home.html', $vars);
```

---

## 8. Manejo de Errores

* Las advertencias no detienen la ejecución.
* Los errores muestran el bloque, la línea y el tipo de error.
* Se puede personalizar el estilo en `ErrorHandler.php`

---

## 9. Extensiones y Personalización

Puedes:

* Añadir nuevos filtros en `Filters.php`
* Soportar nuevas directivas editando `Parser.php`
* Agregar logs, caché u optimizaciones personalizadas

---

## 10. Licencia y Créditos

Este proyecto es de código abierto. Puedes usarlo, modificarlo o extenderlo según tus necesidades. Autor: RonaldRamos.
