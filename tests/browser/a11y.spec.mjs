// Prueba de accesibilidad: recorre las pantallas de «Terminado significa» (plan/PLAN.md)
// a 390 y 1440 px con axe y falla si hay violaciones serious o critical o
// desplazamiento horizontal. Necesita la app en local con la base del seeder.
//
//   BASE=http://localhost:8000 node tests/browser/a11y.spec.mjs
//
// Playwright y @axe-core/playwright son devDependencies; si Playwright está en otra
// ruta, PLAYWRIGHT_MODULE la indica.

const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? 'playwright');
const { default: AxeBuilder } = await import('@axe-core/playwright');

const BASE = process.env.BASE ?? 'http://localhost:8000';
const ETIQUETAS = ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'];
const fallos = [];

async function revisar(page, nombre, ancho) {
    const r = await new AxeBuilder({ page }).withTags(ETIQUETAS).analyze();
    const graves = r.violations.filter((v) => v.impact === 'serious' || v.impact === 'critical');
    const scroll = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    const h1 = await page.locator('h1').count();
    for (const v of graves) {
        fallos.push(`${nombre} @${ancho}: ${v.impact} ${v.id} (${v.nodes.length}) ${v.nodes[0]?.target.join(' ')}`);
    }
    if (scroll) fallos.push(`${nombre} @${ancho}: desplazamiento horizontal`);
    if (h1 !== 1) fallos.push(`${nombre} @${ancho}: ${h1} elementos h1 (debe haber uno)`);
    console.log(`${graves.length || scroll || h1 !== 1 ? 'MAL' : 'ok '} · ${nombre} @${ancho}`);
}

async function entrar(page, email, clave) {
    await page.goto(`${BASE}/login`);
    await page.fill('#email', email);
    await page.fill('#password', clave);
    await Promise.all([page.waitForNavigation(), page.click('form[action$="/login"] button[type="submit"]')]);
}

const browser = await chromium.launch();
try {
    for (const ancho of [390, 1440]) {
        const contexto = await browser.newContext({ viewport: { width: ancho, height: ancho === 390 ? 844 : 900 }, locale: 'es-ES' });
        const page = await contexto.newPage();

        // Públicas
        for (const [nombre, ruta] of [['portada', '/'], ['cartelera', '/peliculas'], ['cines', '/cines'], ['login', '/login'], ['registro', '/register'], ['marca', '/marca'], ['aviso legal', '/aviso-legal'], ['privacidad', '/privacidad']]) {
            await page.goto(BASE + ruta, { waitUntil: 'networkidle' });
            await revisar(page, nombre, ancho);
        }

        // Ficha de una película con sesiones y su primera sesión
        await page.goto(`${BASE}/peliculas`, { waitUntil: 'networkidle' });
        await Promise.all([page.waitForURL(/\/peliculas\/\d+/), page.locator('article h3 a').first().click()]);
        await page.waitForLoadState('networkidle');
        await revisar(page, 'ficha', ancho);
        const compra = await page.locator('a[href*="comprar?sesion_id="]').first().getAttribute('href');

        // Ficha de cine
        await page.goto(`${BASE}/cines`, { waitUntil: 'networkidle' });
        await Promise.all([page.waitForURL(/\/cines\/\d+/), page.locator('article h2 a').first().click()]);
        await revisar(page, 'cine', ancho);

        // Compra como cliente: pasos 1, 2 y 3 y confirmación (pago simulado con Bizum)
        await entrar(page, 'cliente@cineflow.test', 'cliente1234');
        await page.goto(compra, { waitUntil: 'networkidle' });
        await revisar(page, 'compra-paso1', ancho);
        await page.getByRole('button', { name: 'Añadir una entrada de adulto' }).click();
        await Promise.all([page.waitForURL(/butaques/), page.click('#btnSeguent')]);
        await page.waitForLoadState('networkidle');
        await revisar(page, 'compra-paso2', ancho);
        const libre = page.locator('#seat-map button[data-estado="libre"]').first();
        await libre.click();
        await page.waitForFunction(() => document.getElementById('btnSeguent').getAttribute('aria-disabled') === 'false');
        await Promise.all([page.waitForURL(/pagament/), page.click('#btnSeguent')]);
        await page.waitForLoadState('networkidle');
        await revisar(page, 'compra-paso3', ancho);
        if (await page.locator('input[name="metode"][value="bizum"]').count()) {
            await page.check('input[name="metode"][value="bizum"]');
            await page.fill('#telefon_bizum', '600123456');
            await Promise.all([page.waitForURL(/confirmacio/), page.click('#payForm button[type="submit"]')]);
            await page.waitForLoadState('networkidle');
            await revisar(page, 'confirmacion', ancho);
        } else {
            console.log('aviso · Stripe activo: la confirmación no se revisa (la crea el webhook)');
        }

        for (const [nombre, ruta] of [['mis-entradas', '/mis-reservas'], ['panel-cliente', '/dashboard/client']]) {
            await page.goto(BASE + ruta, { waitUntil: 'networkidle' });
            await revisar(page, nombre, ancho);
        }
        await contexto.close();
    }
} finally {
    await browser.close();
}

if (fallos.length) {
    console.error(`\n${fallos.length} problemas:\n- ${fallos.join('\n- ')}`);
    process.exit(1);
}
console.log('\nSin violaciones serious ni critical.');
