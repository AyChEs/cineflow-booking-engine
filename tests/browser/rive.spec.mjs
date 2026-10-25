// Prueba de las piezas de Rive (sesión 11, plan/PLAN.md): logotipo animado, ilustración de «Cómo se compra»
// y anillo de la cuenta atrás. Comprueba que el runtime sale de nuestro dominio (sin CDN), que los lienzos son
// decorativos (aria-hidden), que con movimiento reducido ni se descarga, que si el runtime falla queda el SVG o
// el HTML de siempre, y que el bloque de texto de «Cómo se compra» sigue alineado con la cabecera al fijarse.
//
//   BASE=http://localhost:8000 node tests/browser/rive.spec.mjs
// Si Playwright está en otra ruta, PLAYWRIGHT_MODULE la indica.
import assert from 'node:assert/strict';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const BASE = process.env.BASE ?? 'http://localhost:8000';
const browser = await chromium.launch();
const paso = (m) => console.log(`ok · ${m}`);

async function abrir(opciones = {}) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'es-ES', ...opciones });
    const page = await ctx.newPage();
    const peticiones = [];
    const errores = [];
    page.on('request', (r) => peticiones.push(r.url()));
    page.on('pageerror', (e) => errores.push(e.message));
    return { ctx, page, peticiones, errores };
}
const deRive = (peticiones) => peticiones.filter((u) => /\/rive\/|\.riv(\?|$)|rive-[\w-]+\.js/.test(u));
const idle = (page) => page.waitForLoadState('networkidle');

async function sesionFutura(page) {
    await page.goto(`${BASE}/peliculas`);
    await Promise.all([page.waitForURL(/\/peliculas\/\d+/), page.locator('article h3 a').first().click()]);
    const href = await page.locator('a[href*="comprar?sesion_id="]').first().getAttribute('href');
    return new URL(href, BASE).searchParams.get('sesion_id');
}

try {
    // 1 · Escritorio: logotipo y escena fijada.
    {
        const { ctx, page, peticiones, errores } = await abrir();
        await page.goto(`${BASE}/`);
        await page.waitForSelector('html.mov-listo', { timeout: 20000 });
        await page.waitForSelector('.site-header .rive-logo.rive-listo', { timeout: 20000 });
        paso('logotipo animado pintado por Rive en la cabecera');

        const lienzo = page.locator('.site-header .rive-logo-lienzo');
        assert.equal(await lienzo.getAttribute('aria-hidden'), 'true', 'el lienzo del logotipo es decorativo');
        assert.equal(await page.locator('.site-header .logo').getAttribute('aria-label'), 'CineFlow, ir a la portada');
        paso('lienzo con aria-hidden y nombre accesible del enlace intacto');

        // El runtime y el WASM salen de nuestro dominio.
        const wasm = peticiones.filter((u) => u.endsWith('.wasm'));
        assert.ok(wasm.length >= 1 && wasm.every((u) => u.startsWith(BASE)), `WASM propio: ${wasm.join(', ')}`);
        assert.ok(!peticiones.some((u) => /unpkg|jsdelivr/.test(u)), 'ninguna petición a un CDN');
        paso('WASM alojado en nuestro dominio, sin CDN');

        // Al pasar el ratón, la animación vuelve a salir (el lienzo cambia).
        const huella = () => page.evaluate(() => {
            const c = document.querySelector('.site-header .rive-logo-lienzo');
            const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data;
            let h = 0; for (let i = 3; i < d.length; i += 4) h = (h * 31 + d[i]) | 0; return h;
        });
        await page.waitForTimeout(2800); // la animación de carga dura 2 s
        const reposo = await huella();
        await page.hover('.site-header .logo');
        await page.waitForTimeout(700);
        assert.notEqual(await huella(), reposo, 'al pasar el ratón el logotipo se anima');
        await page.waitForTimeout(2200);
        assert.equal(await huella(), reposo, 'y vuelve al símbolo');
        paso('el ratón repite la animación y vuelve al símbolo de reposo');

        // Escena fijada: salto brusco al pin y alineación con la cabecera (arreglo de la sesión 11).
        const xCabecera = await page.evaluate(() => Math.round(document.querySelector('.site-header-inner .logo').getBoundingClientRect().x));
        for (const salto of [900, 2500, 3700]) {
            await page.evaluate((dy) => window.scrollTo(0, document.querySelector('[data-compra]').getBoundingClientRect().top + scrollY + dy), salto);
            await page.waitForTimeout(1200);
            const x = await page.evaluate(() => Math.round(document.querySelector('.compra-texto').getBoundingClientRect().x));
            assert.ok(Math.abs(x - xCabecera) <= 1, `el texto de «Cómo se compra» (x=${x}) alineado con la cabecera (x=${xCabecera}) tras saltar ${salto}`);
        }
        paso(`texto de «Cómo se compra» alineado con la cabecera (x=${xCabecera}) al saltar al pin`);

        await page.waitForFunction(() => document.querySelector('[data-compra]').classList.contains('compra--rive'), null, { timeout: 15000 });
        const vista = page.locator('[data-vista-rive]');
        assert.equal(await page.locator('.compra-escena').getAttribute('aria-hidden'), 'true', 'la escena ilustrada es decorativa');
        const caja = await page.locator('.compra-lienzo').boundingBox();
        assert.ok(caja.width > 300 && caja.height > 200, 'el lienzo de la ilustración tiene tamaño');
        assert.ok(!(await vista.getAttribute('hidden')), 'la vista de Rive se muestra');
        paso('ilustración de «Cómo se compra» pintada por Rive y decorativa');

        assert.deepEqual(errores, [], `sin errores de página: ${errores.join(' | ')}`);
        await ctx.close();
    }

    // 2 · Movimiento reducido: ni se descarga Rive; quedan el SVG y el HTML.
    {
        const { ctx, page, peticiones } = await abrir({ reducedMotion: 'reduce' });
        await page.goto(`${BASE}/`);
        await idle(page);
        await page.waitForTimeout(1500);
        assert.deepEqual(deRive(peticiones), [], 'con movimiento reducido no se pide nada de Rive');
        assert.equal(await page.locator('.rive-listo').count(), 0);
        assert.ok(await page.locator('.site-header .marca-logo-simbolo').isVisible(), 'el SVG del logotipo sigue visible');
        paso('movimiento reducido: sin descarga de Rive y con el SVG fijo');
        await ctx.close();
    }

    // 3 · Móvil táctil: el logotipo se queda en SVG y no baja el runtime.
    {
        const { ctx, page, peticiones } = await abrir({ viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
        await page.goto(`${BASE}/`);
        await idle(page);
        await page.waitForTimeout(1500);
        assert.deepEqual(deRive(peticiones), [], 'en táctil la portada no pide Rive');
        assert.ok(await page.locator('.site-header .marca-logo-simbolo').isVisible());
        paso('móvil táctil: la portada no descarga el runtime');
        await ctx.close();
    }

    // 4 · Si el runtime no carga, se queda el SVG y la escena HTML.
    {
        const { ctx, page, errores } = await abrir();
        await page.route('**/rive/rive.wasm', (r) => r.abort());
        await page.goto(`${BASE}/`);
        await page.waitForSelector('html.mov-listo', { timeout: 20000 });
        await page.waitForTimeout(2500);
        assert.equal(await page.locator('.rive-listo').count(), 0);
        assert.ok(await page.locator('.site-header .marca-logo-simbolo').isVisible(), 'SVG fijo del logotipo');
        await page.evaluate(() => window.scrollTo(0, document.querySelector('[data-compra]').getBoundingClientRect().top + scrollY + 1500));
        await page.waitForTimeout(1500);
        assert.ok(!(await page.evaluate(() => document.querySelector('[data-compra]').classList.contains('compra--rive'))), 'sin Rive no se esconden las vistas HTML');
        assert.ok(await page.locator('[data-vista="1"]').isVisible(), 'la vista HTML del paso 2 sigue ahí');
        assert.deepEqual(errores, [], `sin errores de página: ${errores.join(' | ')}`);
        paso('sin runtime: SVG fijo y vistas HTML, sin errores');
        await ctx.close();
    }

    // 5 · Compra real: el anillo aparece al elegir butaca, es decorativo y la cuenta atrás sigue en HTML.
    {
        const { ctx, page, peticiones, errores } = await abrir();
        const sesion = await sesionFutura(page);
        await page.goto(`${BASE}/comprar?sesion_id=${sesion}`);
        await page.getByRole('button', { name: 'Añadir una entrada de adulto' }).click();
        await Promise.all([page.waitForURL(/butaques/), page.click('#btnSeguent')]);
        await idle(page);
        assert.deepEqual(deRive(peticiones), [], 'antes de elegir butaca no se descarga Rive');
        await page.locator('#seat-map button.seat[data-estado="libre"]').first().click();
        await page.waitForSelector('.anillo.rive-listo', { timeout: 20000 });
        assert.equal(await page.locator('.anillo').getAttribute('aria-hidden'), 'true');
        assert.equal(await page.locator('.anillo-lienzo').getAttribute('tabindex'), '-1');
        assert.match(await page.textContent('#cuenta-atras-valor'), /^\d:\d\d$/, 'el número sigue en HTML');
        assert.ok(await page.locator('#cuenta-atras-aviso[aria-live="polite"]').count(), 'el aviso al lector de pantalla sigue en HTML');
        const antes = await page.textContent('#cuenta-atras-valor');
        const huella = () => page.evaluate(() => {
            const c = document.querySelector('.anillo-lienzo');
            const d = c.getContext('2d').getImageData(0, 0, c.width, c.height).data;
            let h = 0; for (let i = 3; i < d.length; i += 4) h = (h * 31 + d[i]) | 0; return h;
        });
        const h0 = await huella();
        await page.waitForFunction((t) => document.querySelector('#cuenta-atras-valor').textContent !== t, antes, { timeout: 5000 });
        await page.waitForTimeout(900);
        assert.notEqual(await huella(), h0, 'el anillo se vacía con los segundos');
        assert.deepEqual(errores, [], `sin errores de página: ${errores.join(' | ')}`);
        paso('anillo de la cuenta atrás: aparece al elegir butaca, decorativo, se vacía y no toca el HTML');
        await ctx.close();
    }

    // 6 · /marca: el logotipo grande también se anima.
    {
        const { ctx, page } = await abrir();
        await page.goto(`${BASE}/marca`);
        await page.waitForSelector('.rive-logo.rive-listo', { timeout: 20000 });
        paso('/marca: logotipo animado');
        await ctx.close();
    }
} finally {
    await browser.close();
}
console.log('Rive: todo en orden');
