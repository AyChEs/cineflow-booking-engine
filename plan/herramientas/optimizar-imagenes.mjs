// Genera las versiones AVIF y WebP de las imágenes de marca (public/img/marca/fotos/).
//
//   npm i --no-save sharp
//   node plan/herramientas/optimizar-imagenes.mjs /ruta/a/las/originales
//
// Originales esperados: cineflow-hero.jpg, cineflow-haz.webp, cineflow-butaca.jpg, cineflow-vestibulo.jpg.
// Escribe NOMBRE-ANCHO.avif y NOMBRE-ANCHO.webp. Los anchos de cada una están en
// resources/views/components/marca/imagen.blade.php: si cambian aquí, cámbialos allí.
import sharp from 'sharp';
import fs from 'node:fs';
import path from 'node:path';

const [, , origen] = process.argv;
if (!origen) {
    console.error('Uso: node optimizar-imagenes.mjs CARPETA_DE_ORIGINALES');
    process.exit(1);
}
const destino = path.resolve(import.meta.dirname, '../../public/img/marca/fotos');
fs.mkdirSync(destino, { recursive: true });

const imagenes = [
    // nombre, archivo original, anchos, calidad AVIF y WebP
    ['sala', 'cineflow-hero.jpg', [640, 960, 1280, 1672], { avif: 42, webp: 68 }],
    ['haz', 'cineflow-haz.webp', [640, 960, 1280, 1672], { avif: 40, webp: 62 }],
    ['butaca', 'cineflow-butaca.jpg', [480, 720, 1122], { avif: 42, webp: 68 }],
    ['vestibulo', 'cineflow-vestibulo.jpg', [640, 960, 1280, 1672], { avif: 42, webp: 68 }],
];

for (const [nombre, archivo, anchos, q] of imagenes) {
    const base = sharp(path.join(origen, archivo));
    const { width } = await base.metadata();
    for (const ancho of anchos) {
        const w = Math.min(ancho, width);
        const redimensionada = () => base.clone().resize({ width: w, withoutEnlargement: true });
        await redimensionada().avif({ quality: q.avif, effort: 6 }).toFile(path.join(destino, `${nombre}-${ancho}.avif`));
        await redimensionada().webp({ quality: q.webp, effort: 6 }).toFile(path.join(destino, `${nombre}-${ancho}.webp`));
    }
}
console.log(fs.readdirSync(destino).map((f) => `${f} ${(fs.statSync(path.join(destino, f)).size / 1024).toFixed(0)} KB`).join('\n'));
