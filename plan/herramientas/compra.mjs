// Recorre la compra (paso 1 -> 2 -> 3 -> confirmación) en local y captura + axe en cada paso.
// Uso: node compra.mjs NN http://localhost:8000 SESION_ID [hasta=2|3]  (FULL=1 para capturas de página entera)
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import fs from 'fs';
const [,, name, base, sesionId, hasta = '3'] = process.argv;
const out = `${process.env.CAPT_OUT || '/tmp/capt/out'}/${name}`; fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch();
const report = [];
async function shot(page, id, w) {
  await page.waitForTimeout(800);
  await page.screenshot({ path: `${out}/${id}-${w}.png`, fullPage: process.env.FULL === '1' });
  const r = await new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
  const hx = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
  report.push({ url: page.url().replace(base, ''), id, w, hscroll: hx,
    violations: r.violations.map(v => `${v.impact} ${v.id} (${v.nodes.length}) ${v.nodes.slice(0,2).map(n=>n.target.join(' ')).join(' | ')}`) });
}
for (const w of [390, 1440]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: w === 390 ? 844 : 900 }, locale: 'es-ES' });
  const page = await ctx.newPage();
  const errors = []; page.on('console', m => m.type() === 'error' && errors.push(m.text()));
  await page.goto(`${base}/comprar?sesion_id=${sesionId}`, { waitUntil: 'networkidle' });
  await shot(page, 'compra1', w);
  // Dos entradas de adulto: escribe en el campo (vale para cualquier versión del paso 1).
  // Pulsa dos veces el «+» de adulto (botón con nombre accesible o el que sigue al campo).
  const mas = page.getByRole('button', { name: /añadir una entrada de adulto/i });
  for (let k = 0; k < 2; k++) {
    if (await mas.count()) await mas.first().click();
    else await page.evaluate(() => { let n = document.querySelector('input[name="entrades[adult]"]').nextElementSibling; while (n && n.tagName !== 'BUTTON') n = n.nextElementSibling; n.click(); });
  }
  await page.evaluate(() => { const b = document.querySelector('#step1form [type=submit], form[action*="entrades"] [type=submit]'); if (b) { b.disabled = false; b.removeAttribute('aria-disabled'); } });
  await Promise.all([page.waitForURL(/butaques/), page.locator('form[action*="entrades"] [type=submit]').first().click()]);
  await page.waitForLoadState('networkidle');
  await shot(page, 'compra2', w);
  if (Number(hasta) >= 3) {
    const libres = page.locator('[data-seat]:not([aria-disabled="true"]):not([disabled])');
    // Elige dos butacas libres del centro de la sala.
    const n = await libres.count();
    for (const i of [Math.floor(n / 2), Math.floor(n / 2) + 1]) { await libres.nth(i).click(); await page.waitForTimeout(500); }
    await shot(page, 'compra2-elegidas', w);
    await Promise.all([page.waitForURL(/pagament/), page.locator('form[action*="butaques"] [type=submit]').first().click()]);
    await page.waitForLoadState('networkidle');
    await shot(page, 'compra3', w);
  }
  report.push({ w, consoleErrors: errors.slice(0, 10) });
  // Suelta los bloqueos para no dejar butacas retenidas.
  await page.goto(`${base}/comprar/cancelar`).catch(() => {});
  await ctx.close();
}
await browser.close();
fs.writeFileSync(`${out}/report-compra.json`, JSON.stringify(report, null, 1));
console.log(JSON.stringify(report, null, 1));
