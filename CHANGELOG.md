# Historial de Cambios (Changelog)
Todos los cambios notables en este proyecto se documentarán en este archivo.

El formato se basa en [Mantener un registro de cambios](https://keepachangelog.com/en/1.0.0/), 
y este proyecto se adhiere al [Control de versiones semántico](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-06-30
- Implementación estable del motor de plantillas Mopla.
- Soporte completo para extensiones `.tpl` y `.mp`.
- Mejora de los mensajes de error con detalles de línea y contexto.
- Incorporación de advertencias no bloqueantes en tiempo de ejecución.
- Optimización del motor de análisis sintáctico (`Parser.php`).

## [0.9.0] - 2025-06-20

- Inclusión de nuevos filtros (`capitalize`, `default`).
- Separación del archivo de utilidades (`Utils.php`).
- Mejora del sistema de inclusión (`{include}`) con soporte relativo.

## [0.8.0] - 2025-06-15

- Rediseño del sistema de manejo de errores con niveles (error, warning).
- Incorporación de compatibilidad con `elseif` y `not` en condiciones.

## [0.7.0] - 2025-06-10

- Refactorización de `Mopla.php` para separar responsabilidades.
- Añadido sistema interno de trazabilidad de bloques.

## [0.6.0] - 2025-06-06

- Inclusión de bloques `{set}` anidados y manejo de valores booleanos.
- Compatibilidad mejorada con plantillas HTML completas.

## [0.5.0] - 2025-06-04

- Versión base funcional con renderizado básico de variables y condiciones.
- Implementación de los primeros filtros (`upper`, `lower`, `length`).
- Soporte para estructuras de control `{if}`, `{else}`, `{for}`.
