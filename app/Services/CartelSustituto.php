<?php

namespace App\Services;

use App\Models\Pelicula;
use Illuminate\Support\Str;

/**
 * Carteles sustitutos tipográficos (docs/MARCA.md, «Imágenes»): cuando no hay
 * póster de TMDB, se pinta uno con la marca. Título grande en Big Shoulders,
 * degradado del color del género hacia la sala, haz de proyector, grano y
 * créditos pequeños con los datos reales de la película. Nada de stock.
 *
 * Genera dos WebP en public/carteles: el cartel (2:3) y el fondo panorámico
 * (16:9) que usa la portada.
 */
class CartelSustituto
{
    public const CARPETA = 'carteles';

    // Color de cada género: tonos apagados, sin naranja (matiz 15-40°) ni morados de degradado.
    private const GENEROS = [
        'Acción' => [138, 28, 48],
        'Aventura' => [40, 92, 88],
        'Comedia' => [112, 100, 44],
        'Drama' => [74, 70, 86],
        'Terror' => [70, 18, 30],
        'Suspense' => [44, 60, 78],
        'Romance' => [122, 40, 70],
        'Ciencia Ficción' => [34, 78, 104],
        'Animación' => [52, 106, 72],
        'Fantasía' => [76, 94, 46],
        'Documental' => [56, 84, 102],
        'Musical' => [112, 36, 80],
        'Crimen' => [52, 50, 58],
        'Histórica' => [80, 84, 96],
        'Bélica' => [72, 76, 62],
    ];

    private const SALA = [14, 13, 16];

    private const TEXTO = [242, 238, 230];

    private const TELON = [224, 40, 74];

    public static function fuente(): string
    {
        return resource_path('fonts/BigShouldersDisplay-Black.ttf');
    }

    /** Crea el cartel y el fondo y devuelve la ruta relativa del cartel (para poster_path). */
    public function generar(Pelicula $pelicula): string
    {
        $dir = public_path(self::CARPETA);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $base = $pelicula->id.'-'.Str::slug($pelicula->titulo ?: 'pelicula');
        $genero = $pelicula->categorias->first()?->nombre;
        $color = self::GENEROS[$genero] ?? [58, 54, 65];

        $cartel = $this->cartel($pelicula, $color);
        imagewebp($cartel, "{$dir}/{$base}.webp", 74);
        imagedestroy($cartel);

        $fondo = $this->fondo($pelicula, $color);
        imagewebp($fondo, "{$dir}/{$base}-fondo.webp", 70);
        imagedestroy($fondo);

        return self::CARPETA."/{$base}.webp";
    }

    private function cartel(Pelicula $p, array $color): \GdImage
    {
        [$w, $h] = [600, 900];
        $img = $this->lienzo($w, $h, $color);
        $f = self::fuente();

        // Inicial gigante en contorno, cortada por arriba: el «tono sobre tono» del cartel.
        $inicial = Str::upper(Str::substr(Str::ascii($p->titulo ?: 'C'), 0, 1));
        $this->contorno($img, 760, -40, 640, $inicial, $this->alfa($img, self::TEXTO, 108), 3);

        $this->haz($img, $w, $h, (int) ($w * 0.78), 0.9);

        // Cabecera de créditos
        $this->texto($img, 15, 40, 56, 'CINEFLOW PRESENTA', $this->alfa($img, self::TEXTO, 40), 4);

        // Título: líneas en mayúsculas que llenan el ancho, apiladas desde abajo
        $lineas = $this->partir(Str::upper($p->titulo ?: 'Sin título'), $w - 80, 110, 48);
        $tam = $lineas['tam'];
        $y = $h - 170 - ($tam * 1.18) * (count($lineas['lineas']) - 1);
        foreach ($lineas['lineas'] as $linea) {
            $this->texto($img, $tam, 40, (int) $y, $linea, $this->color($img, self::TEXTO), 0);
            $y += $tam * 1.18;
        }

        // Filete de marca y bloque de créditos con datos reales
        imagefilledrectangle($img, 40, $h - 140, 112, $h - 135, $this->color($img, self::TELON));
        $meta = collect([
            $p->categorias->pluck('nombre')->take(2)->join(' / '),
            $p->duracion_min ? $p->duracion_min.' MIN' : null,
            $p->classificacio_edad,
        ])->filter()->map(fn ($t) => Str::upper($t))->join('   ');
        $this->texto($img, 17, 40, $h - 98, $meta, $this->alfa($img, self::TEXTO, 20), 2);
        $this->texto($img, 12, 40, $h - 56, 'EN CARTELERA  ·  SOLO EN SALAS  ·  ELIGE TU BUTACA EN CINEFLOW', $this->alfa($img, self::TEXTO, 70), 3);

        $this->grano($img, $w, $h);

        return $img;
    }

    private function fondo(Pelicula $p, array $color): \GdImage
    {
        [$w, $h] = [1600, 900];
        $img = $this->lienzo($w, $h, $color, true);

        // Título enorme tono sobre tono a la derecha, cortado por el borde.
        $titulo = Str::upper($p->titulo ?: 'CineFlow');
        $this->texto($img, 470, 640, 780, $titulo, $this->alfa($img, array_map(fn ($c) => min(255, $c + 30), $color), 70), 0);
        $this->contorno($img, 470, 640, 780, $titulo, $this->alfa($img, self::TEXTO, 104), 2);

        $this->haz($img, $w, $h, (int) ($w * 0.62), 0.55);
        $this->grano($img, $w, $h);

        return $img;
    }

    /** Degradado vertical del género a la sala, con viñeta. */
    private function lienzo(int $w, int $h, array $color, bool $ancho = false): \GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / ($h - 1);
            $t = $ancho ? 0.25 + 0.75 * $t : $t;
            $c = [];
            foreach ([0, 1, 2] as $i) {
                $c[$i] = (int) round($color[$i] * (1 - $t) * 0.9 + self::SALA[$i] * $t);
            }
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, ...$c));
        }
        // Viñeta: bandas oscuras semitransparentes en los bordes
        for ($i = 0; $i < 60; $i++) {
            $a = (int) (127 - (60 - $i) * 0.9);
            $col = imagecolorallocatealpha($img, ...[...self::SALA, max(0, min(127, $a + 20))]);
            imagerectangle($img, $i, $i, $w - 1 - $i, $h - 1 - $i, $col);
        }

        return $img;
    }

    /** Haz de proyector: cono de luz desde arriba, en capas de alfa. */
    private function haz(\GdImage $img, int $w, int $h, int $origen, float $fuerza): void
    {
        for ($k = 0; $k < 12; $k++) {
            $abre = 30 + $k * 26;
            $col = imagecolorallocatealpha($img, ...[...self::TEXTO, (int) round(125 - 2 * $fuerza)]);
            imagefilledpolygon($img, [
                $origen - 6, 0,
                $origen + 6, 0,
                (int) ($origen - $w * 0.15 + $abre), $h,
                (int) ($origen - $w * 0.15 - $abre), $h,
            ], $col);
        }
    }

    /** Grano de película: puntos claros y oscuros al azar, fijos por semilla. */
    private function grano(\GdImage $img, int $w, int $h): void
    {
        mt_srand($w * 31 + $h);
        $claro = imagecolorallocatealpha($img, 255, 255, 255, 100);
        $oscuro = imagecolorallocatealpha($img, 0, 0, 0, 100);
        $n = (int) ($w * $h / 28);
        for ($i = 0; $i < $n; $i++) {
            imagesetpixel($img, mt_rand(0, $w - 1), mt_rand(0, $h - 1), $i % 2 ? $claro : $oscuro);
        }
        mt_srand();
    }

    private function texto(\GdImage $img, float $tam, int $x, int $y, string $texto, int $color, int $espaciado): void
    {
        if ($espaciado === 0) {
            imagettftext($img, $tam, 0, $x, $y, $color, self::fuente(), $texto);

            return;
        }
        foreach (mb_str_split($texto) as $letra) {
            $caja = imagettftext($img, $tam, 0, $x, $y, $color, self::fuente(), $letra);
            $x = $caja[2] + $espaciado;
        }
    }

    /** Texto solo en contorno: se pinta el trazo desplazando el texto en círculo y vaciando el centro. */
    private function contorno(\GdImage $img, float $tam, int $x, int $y, string $texto, int $color, int $grosor): void
    {
        $capa = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagealphablending($capa, false);
        imagefill($capa, 0, 0, imagecolorallocatealpha($capa, 0, 0, 0, 127));
        imagealphablending($capa, true);
        $marca = imagecolorallocate($capa, 255, 255, 255);
        for ($a = 0; $a < 360; $a += 30) {
            imagettftext($capa, $tam, 0, $x + (int) round(cos(deg2rad($a)) * $grosor), $y + (int) round(sin(deg2rad($a)) * $grosor), $marca, self::fuente(), $texto);
        }
        imagealphablending($capa, false);
        imagettftext($capa, $tam, 0, $x, $y, imagecolorallocatealpha($capa, 0, 0, 0, 127), self::fuente(), $texto);
        imagealphablending($capa, true);

        // Copia el trazo con el color pedido
        $w = imagesx($img);
        $h = imagesy($img);
        for ($py = 0; $py < $h; $py += 1) {
            for ($px = 0; $px < $w; $px += 1) {
                $c = imagecolorat($capa, $px, $py);
                if ((($c >> 24) & 0x7F) < 64 && ($c & 0xFF) > 128) {
                    imagesetpixel($img, $px, $py, $color);
                }
            }
        }
        imagedestroy($capa);
    }

    /** Parte el título en líneas que caben en el ancho, con el mayor tamaño posible. */
    private function partir(string $texto, int $ancho, int $max, int $min): array
    {
        $palabras = preg_split('/\s+/', trim($texto)) ?: [$texto];
        for ($tam = $max; $tam >= $min; $tam -= 4) {
            $lineas = [];
            $actual = '';
            foreach ($palabras as $palabra) {
                $prueba = trim($actual.' '.$palabra);
                if ($this->ancho($prueba, $tam) <= $ancho) {
                    $actual = $prueba;
                } else {
                    if ($actual !== '') {
                        $lineas[] = $actual;
                    }
                    $actual = $palabra;
                }
            }
            $lineas[] = $actual;
            $cabe = collect($lineas)->every(fn ($l) => $this->ancho($l, $tam) <= $ancho);
            if ($cabe && count($lineas) <= 4) {
                return ['tam' => $tam, 'lineas' => $lineas];
            }
        }

        return ['tam' => $min, 'lineas' => $lineas ?? [$texto]];
    }

    private function ancho(string $texto, float $tam): int
    {
        $caja = imagettfbbox($tam, 0, self::fuente(), $texto);

        return abs($caja[2] - $caja[0]);
    }

    private function color(\GdImage $img, array $rgb): int
    {
        return imagecolorallocate($img, ...$rgb);
    }

    /** Color con alfa de GD (0 opaco, 127 transparente). */
    private function alfa(\GdImage $img, array $rgb, int $alfa): int
    {
        return imagecolorallocatealpha($img, ...[...$rgb, $alfa]);
    }
}
