<?php

require_once '../src/Mopla.php';

// Instanciar el motor Mopla
$mopla = new Mopla([
    'template_dir' => __DIR__ . '/templates',  // Carpeta base donde están tus plantillas (.tpl o .mp)
    'cache_dir' => __DIR__ . '/cache',         // Carpeta donde se guardan archivos cacheados/parseados
    'debug' => true,                           // Mostrar errores con detalle en la pantalla
    'cache' => false                           // Guardar/usar archivos de cache (recomendado en producción)
]);

// Asignar variables individuales
$mopla->assign('usuario', "Ronald");

// También puedes pasar un arreglo completo al render
$data = [
    'titulo' => 'Bienvenido a Mopla',
    'version' => '1.0',
    'fecha' => date('Y-m-d'),
    'mensaje' => 'Hey! Gracias <3 '
];

// Renderiza la plantilla 'index.tpl' o 'index.mp'.
// Si no colocas extensión, Mopla probará automáticamente con .mp y luego .tpl
echo $mopla->render("index", $data);

/*
 * Notas:
 * - Si NO defines 'template_dir', deberás pasar la ruta completa al archivo en render().
 * - Puedes usar comillas simples o dobles en tus plantillas con {include}, {extends}, etc.
 * - Mopla detectará automáticamente si el archivo tiene .tpl o .mp.
 */
