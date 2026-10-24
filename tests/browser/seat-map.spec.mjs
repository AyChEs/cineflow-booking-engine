// Prueba de navegador del mapa de butacas, solo con teclado (fuera de PHPUnit).
//
// Con la app en local (php artisan serve) y una sesión futura con butacas libres:
//   BASE=http://localhost:8000 SESION=153 node tests/browser/seat-map.spec.mjs
// Playwright se busca como «playwright»; si está instalado en otra ruta, pásala en PLAYWRIGHT_MODULE
// (por ejemplo, PLAYWRIGHT_MODULE=/opt/node-tools/node_modules/playwright/index.mjs).
import assert from 'node:assert/strict';

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const BASE = process.env.BASE ?? 'http://localhost:8000';

const browser = await chromium.launch();
const page = await (await browser.newContext({ locale: 'es-ES' })).newPage();
const paso = (m) => console.log(`ok · ${m}`);

// Sin SESION, usa la primera sesión de la primera película de la cartelera.
let SESION = process.env.SESION;
if (!SESION) {
    await page.goto(`${BASE}/peliculas`);
    await Promise.all([page.waitForURL(/\/peliculas\/\d+/), page.locator('article h3 a').first().click()]);
    const href = await page.locator('a[href*="comprar?sesion_id="]').first().getAttribute('href');
    SESION = new URL(href, BASE).searchParams.get('sesion_id');
}

try {
    // Paso 1: dos entradas de adulto con el teclado.
    await page.goto(`${BASE}/comprar?sesion_id=${SESION}`);
    const mas = page.getByRole('button', { name: 'Añadir una entrada de adulto' });
    await mas.focus();
    await page.keyboard.press('Enter');
    await page.keyboard.press('Enter');
    assert.equal(await page.inputValue('#qty_adult'), '2');
    assert.equal(await page.getAttribute('#btnSeguent', 'aria-disabled'), 'false');
    await Promise.all([page.waitForURL(/butaques/), page.click('#btnSeguent')]);
    paso('paso 1 con teclado');

    // Paso 2: una sola butaca en el orden de tabulación (foco itinerante).
    const enTab = await page.$$eval('#seat-map button.seat', (bs) => bs.filter((b) => b.tabIndex === 0).length);
    assert.equal(enTab, 1, 'solo una butaca con tabindex=0');
    await page.focus('#seat-map button.seat[tabindex="0"]');

    // Flechas: derecha, abajo; luego Espacio elige.
    const inicio = await page.evaluate(() => document.activeElement.dataset.seat);
    await page.keyboard.press('ArrowRight');
    await page.keyboard.press('ArrowDown');
    const tras = await page.evaluate(() => document.activeElement.dataset.seat);
    assert.notEqual(tras, inicio, 'las flechas mueven el foco');
    paso(`flechas: ${inicio} → ${tras}`);

    async function elegirLibre() {
        // Avanza hasta una butaca libre y la elige con Espacio.
        for (let i = 0; i < 40; i++) {
            const estado = await page.evaluate(() => document.activeElement.dataset.estado);
            if (estado === 'libre') break;
            await page.keyboard.press('ArrowRight');
        }
        const id = await page.evaluate(() => document.activeElement.dataset.seat);
        await page.keyboard.press('Space');
        await page.waitForFunction((s) => document.querySelector(`[data-seat="${s}"]`).getAttribute('aria-pressed') === 'true', id);
        return id;
    }

    const a = await elegirLibre();
    await page.waitForFunction(() => document.getElementById('seat-status').textContent.includes('1 de 2'));
    await page.keyboard.press('ArrowRight');
    const b = await elegirLibre();
    await page.waitForFunction(() => document.getElementById('seat-status').textContent.includes('2 de 2'));
    paso(`elegidas con Espacio: ${a}, ${b}; anuncio: «${await page.textContent('#seat-status')}»`);

    // Una tercera no entra: se anuncia y no cambia nada.
    await page.keyboard.press('ArrowDown');
    for (let i = 0; i < 20 && await page.evaluate(() => document.activeElement.dataset.estado) !== 'libre'; i++) await page.keyboard.press('ArrowRight');
    await page.keyboard.press('Space');
    await page.waitForFunction(() => document.getElementById('seat-status').textContent.startsWith('Ya tienes 2 butacas'));
    paso('tercera butaca rechazada con aviso');

    assert.equal(await page.getAttribute('#btnSeguent', 'aria-disabled'), 'false');
    assert.equal(await page.inputValue('#butaquesInput'), [a, b].sort().join(','));
    paso('«Continuar» activo y butacas en el formulario');

    // Inicio y Ctrl+Fin
    await page.keyboard.press('Control+End');
    const ultima = await page.evaluate(() => document.activeElement.dataset.seat);
    await page.keyboard.press('Control+Home');
    assert.equal(await page.evaluate(() => document.activeElement.dataset.seat), 'A1');
    paso(`Ctrl+Fin → ${ultima}, Ctrl+Inicio → A1`);

    // Suelta las butacas para no dejar bloqueos.
    await page.goto(`${BASE}/comprar/cancelar`);
    console.log('Todo correcto.');
} finally {
    await browser.close();
}
