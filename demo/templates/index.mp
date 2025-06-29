<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{$titulo}</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

    {include 'header'}

    <h1>{$titulo}</h1>
    <p>Hola, {$usuario}. Hoy es {$fecha}</p>
    <p>Estás usando <strong>Mopla v{$version}</strong>.</p>

    {if $usuario == 'DemoUser'}
        <p><em>Este es un mensaje especial para el usuario de demostración.</em></p>
    {else}
        <p>Bienvenido, {$usuario}.</p>
    {/if}

    {set mensaje = "¡Gracias por probar Mopla!"}
    <p>{$mensaje|upper}</p>

    {include "footer.tpl"}

</body>
</html>
