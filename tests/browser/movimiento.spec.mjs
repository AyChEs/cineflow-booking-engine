// Prueba del movimiento de las páginas públicas (sesión 09, plan/PLAN.md).
// Comprueba: sin desplazamiento horizontal ni a 390 ni a 1440 recorriendo la portada, sin saltos de
// diseño (CLS), estado final sin movimiento ni descarga de GSAP/Lenis con prefers-reduced-motion,
// escena fijada en escritorio, tira táctil en móvil y transición de vista entre páginas.
//
//   BASE=http://localhost:8000 node tests/browser/movimiento.spec.mjs
//
// Si Playwright está en otra ruta, PLAYWRIGHT_MODULE la indica.

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');

const BASE = process.env.BASE ?? 'http://localhost:8000';
const fallos = [];
const ok = (cond, texto) => {
    console.log(`${cond ? 'ok ' : 'MAL'} · ${texto}`);
    if (!cond) fallos.push(texto);
};

async function abrir(browser, { ancho, alto, reducir = false }) {
    const ctx = await browser.newContext({
        viewport: { width: ancho, height: alto },
        locale: 'es-ES',
        reducedMotion: reducir ? 'reduce' : 'no-preference',
        hasTouch: ancho < 700,
        isMobile: ancho < 700,
    });
    const page = await ctx.newPage();
    const peticiones = [];
    page.on('request', (r) => peticiones.push(r.url()));
    await page.addInitScript(() => {
        window.__cls = 0;
        new PerformanceObserver((lista) => {
            for (const e of lista.getEntries()) if (!e.hadRecentInput) window.__cls += e.value;
        }).observe({ type: 'layout-shift', buffered: true });
        window.addEventListener('pageswap', (e) => sessionStorage.setItem('vt', e.viewTransition ? '1' : '0'));
    });
    return { ctx, page, peticiones };
}

const sinScrollHorizontal = (page) => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);

async function recorrer(page, paso = 400) {
    const alto = await page.evaluate(() => document.documentElement.scrollHeight);
    let horizontal = true;
    for (let y = 0; y < alto; y += paso) {
        await page.evaluate((v) => window.scrollTo({ top: v, behavior: 'instant' }), y);
        await page.waitForTimeout(60);
        if (!(await sinScrollHorizontal(page))) horizontal = false;
    }
    return horizontal;
}

const browser = await chromium.launch();
try {
    // ── Escritorio con movimiento ──
    {
        const { ctx, page } = await abrir(browser, { ancho: 1440, alto: 900 });
        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2500);
        const clases = await page.evaluate(() => document.documentElement.className);
        ok(/mov-escena/.test(clases) && /mov-listo/.test(clases), 'escritorio: escena fijada activa');
        ok(await page.evaluate(() => getComputedStyle(document.querySelector('.hero-banda--sup')).transform !== 'none'), 'escritorio: las bandas panorámicas se abren al cargar');
        ok(await page.evaluate(() => document.querySelectorAll('.hero-titulo .letra').length > 5), 'escritorio: el título se divide en letras (marquesina)');

        await page.evaluate(() => window.scrollTo({ top: 1700, behavior: 'instant' }));
        await page.waitForTimeout(1800);
        const encogido = await page.evaluate(() => {
            const r = document.querySelector('[data-hero]').getBoundingClientRect();
            return r.width < window.innerWidth * 0.7;
        });
        ok(encogido, 'escritorio: con el scroll el hero se encoge hasta ser el primer fotograma');
        const fijada = await page.evaluate(() => getComputedStyle(document.querySelector('[data-escena]').parentElement).position !== undefined && document.querySelector('[data-escena]').getBoundingClientRect().top < 80);
        ok(fijada, 'escritorio: la escena se queda fijada bajo la cabecera');

        ok(await recorrer(page), 'escritorio: sin desplazamiento horizontal en toda la portada');

        // El nombre se rellena al llegar al final de la sección
        await page.evaluate(() => {
            const l = document.querySelector('.nombre-lienzo').getBoundingClientRect();
            window.scrollTo({ top: window.scrollY + l.bottom - 220, behavior: 'instant' });
        });
        await page.waitForTimeout(2200);
        const relleno = await page.evaluate(() => {
            const r = document.querySelector('[data-nombre-ventana]').getBoundingClientRect();
            const l = document.querySelector('.nombre-lienzo').getBoundingClientRect();
            return r.left >= l.left - 2;
        });
        ok(relleno, 'escritorio: el contorno del nombre se rellena con el scroll');

        await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
        await page.waitForTimeout(500);
        const cls = await page.evaluate(() => window.__cls);
        ok(cls < 0.05, `escritorio: CLS ${cls.toFixed(3)} (< 0,05)`);

        // Transición de vista entre páginas públicas
        await page.click('.site-nav-link[href$="/peliculas"]');
        await page.waitForURL('**/peliculas');
        const vt = await page.evaluate(() => sessionStorage.getItem('vt'));
        ok(vt === '1', 'escritorio: la navegación entre páginas usa View Transitions');
        await ctx.close();
    }

    // ── Móvil ──
    {
        const { ctx, page } = await abrir(browser, { ancho: 390, alto: 844 });
        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2500);
        const clases = await page.evaluate(() => document.documentElement.className);
        ok(!/mov-escena/.test(clases), 'móvil: sin escena fijada');
        ok(await sinScrollHorizontal(page), 'móvil: sin desplazamiento horizontal');
        ok(await recorrer(page, 300), 'móvil: sin desplazamiento horizontal recorriendo la portada');
        const tira = await page.evaluate(() => {
            const t = document.querySelector('.tira-lista');
            return t.scrollWidth > t.clientWidth + 50 && getComputedStyle(t).overflowX === 'auto';
        });
        ok(tira, 'móvil: la cartelera es un carrusel horizontal táctil');
        const cls = await page.evaluate(() => window.__cls);
        ok(cls < 0.05, `móvil: CLS ${cls.toFixed(3)} (< 0,05)`);
        await ctx.close();
    }

    // ── Movimiento reducido ──
    for (const [ancho, alto] of [[1440, 900], [390, 844]]) {
        const { ctx, page, peticiones } = await abrir(browser, { ancho, alto, reducir: true });
        await page.goto(BASE + '/', { waitUntil: 'networkidle' });
        await page.waitForTimeout(1500);
        const clases = await page.evaluate(() => document.documentElement.className);
        ok(!/mov-ok|mov-escena|lenis/.test(clases), `reducido @${ancho}: sin clases de movimiento ni Lenis`);
        ok(!peticiones.some((u) => /\/build\/assets\/index-.*\.js/.test(u)), `reducido @${ancho}: no se descarga el chunk de movimiento`);
        const estado = await page.evaluate(() => ({
            banda: getComputedStyle(document.querySelector('.hero-banda--sup')).transform,
            titulo: getComputedStyle(document.querySelector('[data-titulo]')).visibility,
            ventana: getComputedStyle(document.querySelector('[data-nombre-ventana]')).transform,
            grano: getComputedStyle(document.querySelector('.hero-grano')).animationName,
        }));
        ok(estado.titulo === 'visible', `reducido @${ancho}: el título se ve`);
        ok(estado.ventana === 'none', `reducido @${ancho}: el nombre ya está relleno`);
        ok(estado.grano === 'none', `reducido @${ancho}: el grano no se anima`);
        ok(await recorrer(page), `reducido @${ancho}: sin desplazamiento horizontal`);
        await ctx.close();
    }

    // ── La compra no carga el movimiento ──
    {
        const { ctx, page, peticiones } = await abrir(browser, { ancho: 1440, alto: 900 });
        await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
        ok(!(await page.evaluate(() => document.body.dataset.mov)) && !peticiones.some((u) => /movimiento/.test(u)), 'acceso: no carga el movimiento');
        await ctx.close();
    }
} finally {
    await browser.close();
}

if (fallos.length) {
    console.error(`\n${fallos.length} problemas:\n- ${fallos.join('\n- ')}`);
    process.exit(1);
}
console.log('\nTodo correcto.');
