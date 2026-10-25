<?php

// Marca (docs/MARCA.md). Lo que se puede cambiar sin tocar las vistas.
return [

    // Frase de marca: firma del pie y de la imagen para redes.
    'lema' => 'Elige la butaca. El resto es cine.',

    // Imágenes generadas con IA por Ayman Charoui (public/img/marca/fotos/, plan/herramientas/optimizar-imagenes.mjs).
    // Cada una existe en AVIF y WebP en estos anchos: NOMBRE-ANCHO.avif y NOMBRE-ANCHO.webp.
    'fotos' => [
        'sala' => ['ancho' => 1672, 'alto' => 941, 'anchos' => [640, 960, 1280, 1672]],
        'haz' => ['ancho' => 1672, 'alto' => 941, 'anchos' => [640, 960, 1280, 1672]],
        'butaca' => ['ancho' => 1122, 'alto' => 1402, 'anchos' => [480, 720, 1122]],
        'vestibulo' => ['ancho' => 1672, 'alto' => 941, 'anchos' => [640, 960, 1280, 1672]],
    ],

];
