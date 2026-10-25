<?php

// Marca (docs/MARCA.md). Lo que se puede cambiar sin tocar las vistas.
return [

    // Imagen de fondo de la portada, relativa a public/. Si el archivo no existe,
    // la portada usa la imagen de la película destacada. Para cambiarla, deja la
    // foto en public/img/hero-sala.webp (o pon otra ruta en MARCA_HERO_IMAGEN).
    'hero_imagen' => env('MARCA_HERO_IMAGEN', 'img/hero-sala.webp'),

    // Frase de marca: firma del pie y de la imagen para redes.
    'lema' => 'Elige la butaca. El resto es cine.',

];
