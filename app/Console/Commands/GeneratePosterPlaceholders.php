<?php

namespace App\Console\Commands;

use App\Models\Pelicula;
use Illuminate\Console\Command;

class GeneratePosterPlaceholders extends Command
{
    protected $signature = 'posters:generate-placeholders';
    protected $description = 'Generar posters placeholder en SVG para todas las películas';

    // Paleta por género: tonos apagados de sala oscura (sin naranjas ni degradados morados).
    private $genreColors = [
        'Acción' => ['#7A2333', '#2A1418'],
        'Aventura' => ['#2F5D5A', '#142625'],
        'Comedia' => ['#6B6233', '#2A2716'],
        'Drama' => ['#4A4652', '#1E1C22'],
        'Terror' => ['#2B2830', '#0E0D10'],
        'Suspense' => ['#33404F', '#14181E'],
        'Romance' => ['#6E2D45', '#28141C'],
        'Ciencia Ficción' => ['#2D4F66', '#121F28'],
        'Animación' => ['#3F6B4A', '#18291D'],
        'Fantasía' => ['#4B5E2F', '#1D2414'],
        'Documental' => ['#3C5566', '#172128'],
        'Musical' => ['#6A2A4E', '#27121D'],
        'Crimen' => ['#2A2A2E', '#101012'],
        'Histórica' => ['#4A4F5A', '#1C1E22'],
        'Bélica' => ['#46483F', '#1B1C18'],
    ];

    public function handle()
    {
        $peliculas = Pelicula::with('categorias')->get();

        $this->info("Generando {$peliculas->count()} posters placeholder...\n");

        foreach ($peliculas as $pelicula) {
            try {
                // Obtener género principal
                $genero = $pelicula->categorias->first()?->nombre ?? 'Película';
                $colors = $this->genreColors[$genero] ?? ['#3A3641', '#18161C'];

                if (is_string($colors)) {
                    $colors = [$colors, '#333333'];
                }

                [$color1, $color2] = $colors;

                // Generar SVG
                $svg = $this->generatePosterSvg($pelicula->titulo, $genero, $color1, $color2);

                // Guardar como data URI
                $dataUri = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
                $pelicula->update(['poster_path' => $dataUri]);

                $this->line("✓ {$pelicula->titulo}");
            } catch (\Throwable $e) {
                $this->error("✗ {$pelicula->titulo}: {$e->getMessage()}");
            }
        }

        $this->info("\nPósters generados.");
        return Command::SUCCESS;
    }

    private function generatePosterSvg(string $title, string $genre, string $color1, string $color2): string
    {
        $safeTitle = htmlspecialchars(substr($title, 0, 40), ENT_QUOTES, 'UTF-8');
        $safeGenre = htmlspecialchars($genre, ENT_QUOTES, 'UTF-8');
        $initials = strtoupper(substr(preg_replace('/\s+/', '', $title), 0, 2));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 342 513" role="img" aria-label="{$safeTitle}">
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="{$color1}" />
            <stop offset="100%" stop-color="{$color2}" />
        </linearGradient>
        <filter id="shadow">
            <feDropShadow dx="2" dy="4" stdDeviation="3" flood-opacity="0.3"/>
        </filter>
    </defs>
    
    <!-- Fondo -->
    <rect width="342" height="513" fill="url(#grad)" rx="12" />
    
    <!-- Patrón decorativo -->
    <circle cx="171" cy="100" r="80" fill="rgba(255,255,255,0.1)" />
    <circle cx="50" cy="350" r="60" fill="rgba(255,255,255,0.08)" />
    <circle cx="300" cy="400" r="45" fill="rgba(255,255,255,0.08)" />
    
    <!-- Iniciales -->
    <text x="171" y="150" text-anchor="middle" font-size="96" font-family="Arial, Helvetica, sans-serif" fill="#FFFFFF" font-weight="900" letter-spacing="2">{$initials}</text>
    
    <!-- Línea decorativa -->
    <line x1="50" y1="200" x2="292" y2="200" stroke="rgba(255,255,255,0.3)" stroke-width="2" />
    
    <!-- Título -->
    <text x="171" y="280" text-anchor="middle" font-size="18" font-family="Arial, Helvetica, sans-serif" fill="#FFFFFF" font-weight="700" font-style="italic">{$safeTitle}</text>
    
    <!-- Género -->
    <text x="171" y="330" text-anchor="middle" font-size="13" font-family="Arial, Helvetica, sans-serif" fill="rgba(255,255,255,0.8)" font-weight="500">{$safeGenre}</text>
    
    <!-- Logo CineFlow en bajo -->
    <text x="171" y="485" text-anchor="middle" font-size="12" font-family="Arial, Helvetica, sans-serif" fill="rgba(255,255,255,0.5)" font-weight="400">CINEFLOW</text>
    
    <!-- Efecto de brillo -->
    <rect width="342" height="513" fill="rgba(255,255,255,0.1)" rx="12" style="pointer-events: none;" />
</svg>
SVG;
    }
}
