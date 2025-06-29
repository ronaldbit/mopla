# Mopla - Tareas pendientes

## Optimizaciones del motor
- [ ] Soporte para plantillas compiladas en caché sin rutas absolutas del sistema.
- [ ] Evaluar la separación de lógica de variables `{if}`, `{foreach}`, etc. en una clase interna.
- [ ] Implementar motor de filtros para aplicar transformaciones a las variables (ej: escape, capitalize).

## Sistema de inclusión
- [ ] Revisar `{include}` para permitir inline rendering (fusionado).
- [ ] Posible fusión de archivos compilados en uno solo por rendimiento.

## Internals
- [ ] Unificar funciones de control de buffer (`ob_start`, `ob_get_clean`) en una sola clase.
- [ ] Revisar uso de variables `public static` como `_CHARSET`.

## Sistema de plugins
- [ ] Mejorar verificación de seguridad de los plugins de usuario.
- [ ] Estandarizar rutas y estructura de los plugins personalizados.

## Otros
- [ ] Revisar uso del keyword `clone`.
- [ ] Agregar soporte completo para herencia de bloques `{block}` con control más preciso.
