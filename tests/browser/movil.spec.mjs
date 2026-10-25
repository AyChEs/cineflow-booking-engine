// Prueba del movimiento y los espacios en móvil (sesión 13, plan/PLAN.md), a 390×844 y 360×780 con pantalla táctil.
// Comprueba: sin desplazamiento horizontal, sin pins ni Lenis, la tira de celuloide y las ilustraciones de «Cómo se compra»
// como carruseles táctiles con scroll-snap, el haz y el grano quietos, y que el hueco antes del pie sigue la escala de espaciado.
//
//   BASE=http://localhost:8000 node tests/browser/movil.spec.mjs
// Si Playwright está en otra ruta, PLAYWRIGHT_MODULE la indica.
import assert from 'node:assert/strict';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const BASE = process.env.BASE ?? 'http://localhost:8000';
const browser = await chromium.launch();
const paso = (m) => console.log(`ok · ${m}`);
const paginas = ['/', '/peliculas', '/cines', '/marca', '/login', '/register'];

async function recorrer(page) {
    const max = await page.evaluate(() => document.documentElement.scrollHeight);
    for (let y = 0; y < max; y += 500) {
        await page.evaluate((v) => window.scrollTo(0, v), y);
        await page.waitForTimeout(90);
    }
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(300);
}

// Hueco entre el final del contenido de la página y el pie, en píxeles.
const huecoAlPie = (page) => page.evaluate(() => {
    const pie = document.querySelector('.site-footer').getBoundingClientRect().top + scrollY;
    let fin = 0;
    for (const el of document.querySelectorAll('main *')) {
        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden' || +cs.opacity === 0) continue;
        const r = el.getBoundingClientRect();
        if (r.width < 2 || r.height < 2) continue;
        const contenido = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim()) || ['IMG', 'SVG', 'BUTTON', 'INPUT', 'SELECT', 'CANVAS'].includes(el.tagName.toUpperCase())
            || parseFloat(cs.borderBottomWidth) > 0 || cs.backgroundColor !== 'rgba(0, 0, 0, 0)';
        if (contenido && r.bottom + scrollY <= pie + 1) fin = Math.max(fin, r.bottom + scrollY);
    }
    return Math.round(pie - fin);
});

try {
    for (const [w, h] of [[390, 844], [360, 780]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h }, screen: { width: w, height: h }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'es-ES' });
        const page = await ctx.newPage();
        const errores = [];
        page.on('pageerror', (e) => errores.push(e.message));

        // 1 · Sin desplazamiento horizontal en ninguna página pública
        for (const url of paginas) {
            await page.goto(`${BASE}${url}`, { waitUntil: 'load' });
            await page.waitForTimeout(700);
            await recorrer(page);
            const hs = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
            assert.ok(hs <= 0, `${url} @${w}: desborda ${hs}px`);
        }
        paso(`@${w}: sin desplazamiento horizontal en ${paginas.join(', ')}`);

        // 2 · Portada: sin pins ni Lenis, coreografía de móvil
        await page.goto(`${BASE}/`, { waitUntil: 'load' });
        await page.waitForSelector('html.mov-listo', { timeout: 20000 });
        await recorrer(page);
        assert.equal(await page.locator('.pin-spacer').count(), 0, 'sin pin-spacer de GSAP en móvil');
        assert.ok(!(await page.evaluate(() => document.documentElement.classList.contains('lenis'))), 'sin Lenis en táctil');
        assert.ok(!(await page.evaluate(() => document.documentElement.classList.contains('mov-escena'))), 'sin escena fijada en móvil');
        paso(`@${w}: sin pins ni Lenis`);

        const grano = await page.evaluate(() => getComputedStyle(document.querySelector('.hero-grano')).animationName);
        assert.equal(grano, 'none', 'el grano no se anima en móvil');
        const haz = await page.evaluate(() => {
            const img = document.querySelector('.hero-haz-img');
            return img && getComputedStyle(img.closest('.hero-haz')).display !== 'none' ? img : null;
        });
        if (haz !== null) {
            const t0 = await page.evaluate(() => getComputedStyle(document.querySelector('.hero-haz-img')).transform);
            await page.waitForTimeout(1800);
            const t1 = await page.evaluate(() => getComputedStyle(document.querySelector('.hero-haz-img')).transform);
            assert.equal(t0, t1, 'el haz no gira en móvil');
        }
        paso(`@${w}: grano y haz quietos`);

        // 3 · Tira de celuloide: carrusel táctil con scroll-snap
        const tira = await page.evaluate(() => {
            const l = document.querySelector('.tira-lista'); const cs = getComputedStyle(l);
            return { snap: cs.scrollSnapType, overflowX: cs.overflowX, desborda: l.scrollWidth > l.clientWidth + 20, tarjeta: l.firstElementChild.getBoundingClientRect().width };
        });
        assert.match(tira.snap, /x mandatory/);
        assert.equal(tira.overflowX, 'auto');
        assert.ok(tira.desborda, 'la tira se desliza en horizontal');
        paso(`@${w}: tira de celuloide = carrusel con scroll-snap (${tira.snap})`);

        // 4 · «Cómo se compra»: ilustraciones en carrusel, no apiladas
        const comp = await page.evaluate(() => {
            const e = document.querySelector('.compra-escena'); const cs = getComputedStyle(e);
            const vistas = [...e.querySelectorAll(':scope > .compra-vista')].filter((v) => getComputedStyle(v).display !== 'none');
            return { snap: cs.scrollSnapType, overflowX: cs.overflowX, desborda: e.scrollWidth > e.clientWidth + 20, n: vistas.length, ancho: Math.round(vistas[0].getBoundingClientRect().width), vp: innerWidth, alto: Math.round(e.getBoundingClientRect().height) };
        });
        assert.match(comp.snap, /x mandatory/);
        assert.equal(comp.overflowX, 'auto');
        assert.ok(comp.desborda, 'las ilustraciones se deslizan');
        assert.equal(comp.n, 4, 'las cuatro ilustraciones están en el carrusel');
        assert.ok(comp.ancho < comp.vp * 0.9 && comp.ancho > comp.vp * 0.6, `la tarjeta (${comp.ancho}px) deja asomar la siguiente`);
        assert.ok(comp.alto < 700, `el carrusel ocupa ${comp.alto}px de alto, no las cuatro tarjetas apiladas`);
        // Al soltar entre dos tarjetas, el scroll-snap termina en una.
        const pos = await page.evaluate(async () => {
            const e = document.querySelector('.compra-escena');
            const tarjetas = [...e.querySelectorAll(':scope > .compra-vista')].filter((v) => getComputedStyle(v).display !== 'none');
            e.scrollTo({ left: tarjetas[1].offsetLeft - e.offsetLeft - 10 + 60, behavior: 'instant' });
            await new Promise((r) => setTimeout(r, 900));
            return { x: e.scrollLeft, destinos: tarjetas.map((t) => t.offsetLeft - e.offsetLeft - parseFloat(getComputedStyle(e).scrollPaddingInlineStart || 0)) };
        });
        assert.ok(pos.destinos.some((d) => Math.abs(d - pos.x) <= 3), `snap: scrollLeft=${pos.x}, destinos=${pos.destinos.map(Math.round)}`);
        paso(`@${w}: «Cómo se compra» = carrusel con scroll-snap (${comp.alto}px de alto, tarjeta de ${comp.ancho}px)`);

        // 5 · Alto total de la portada: no vuelven las cuatro tarjetas apiladas
        const alto = await page.evaluate(() => document.documentElement.scrollHeight);
        assert.ok(alto < 4100, `la portada mide ${alto}px a ${w}`);
        paso(`@${w}: portada de ${alto}px de alto`);

        // 6 · Escala de espaciado: hueco antes del pie
        for (const url of ['/', '/peliculas', '/cines', '/peliculas/1']) {
            await page.goto(`${BASE}${url}`, { waitUntil: 'load' });
            await page.waitForTimeout(600);
            const hueco = await huecoAlPie(page);
            assert.ok(hueco <= 112, `${url} @${w}: ${hueco}px de hueco antes del pie`);
        }
        paso(`@${w}: hueco antes del pie ≤ 112 px`);

        assert.deepEqual(errores, [], `sin errores de página: ${errores.join(' | ')}`);
        await ctx.close();
    }

    // Escritorio: la misma escala (hueco antes del pie ≤ 112 px)
    {
        const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'es-ES', reducedMotion: 'reduce' });
        const page = await ctx.newPage();
        for (const url of ['/', '/peliculas', '/cines', '/peliculas/1']) {
            await page.goto(`${BASE}${url}`, { waitUntil: 'load' });
            await page.waitForTimeout(500);
            const hueco = await huecoAlPie(page);
            assert.ok(hueco <= 112, `${url} @1440: ${hueco}px de hueco antes del pie`);
        }
        paso('@1440: hueco antes del pie ≤ 112 px');
        await ctx.close();
    }
} finally {
    await browser.close();
}
console.log('Móvil: todo en orden');
