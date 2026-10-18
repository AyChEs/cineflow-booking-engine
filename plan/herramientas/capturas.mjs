import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import fs from 'fs';
const [,, name, base, pagesJson, loginJson] = process.argv;
const pages = JSON.parse(pagesJson);
const login = loginJson ? JSON.parse(loginJson) : null;
const out = `/tmp/capt/out/${name}`; fs.mkdirSync(out, { recursive: true });
const spa = process.env.SPA === '1';
async function go(page, url) {
  if (!spa) return page.goto(base + url, { waitUntil: 'networkidle', timeout: 90000 });
  if (!page.url().startsWith(base)) await page.goto(base + '/', { waitUntil: 'networkidle', timeout: 90000 });
  await page.evaluate(u => { history.pushState({}, '', u); dispatchEvent(new PopStateEvent('popstate')); }, url);
  await page.waitForLoadState('networkidle');
}
const browser = await chromium.launch();
const report = [];
for (const w of [390, 1440]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: w === 390 ? 844 : 900 }, locale: 'es-ES' });
  const page = await ctx.newPage();
  const errors = []; page.on('console', m => m.type() === 'error' && errors.push(m.text()));
  for (const p of pages) {
    if (p.login && login) { await ctx.clearCookies(); await page.goto(base + '/', {waitUntil:'networkidle'});
      await go(page, login.url); await page.waitForTimeout(1500);
      await page.fill(login.user, p.login[0]); await page.fill(login.pass, p.login[1]);
      await page.click(login.submit); await page.waitForTimeout(4000);
    }
    try { await go(page, p.url); } catch (e) { report.push({ url: p.url, w, err: String(e).slice(0,200) }); continue; }
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${out}/${p.id}-${w}.png`, fullPage: false });
    const r = await new AxeBuilder({ page }).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
    const hx = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    report.push({ url: p.url, w, hscroll: hx, violations: r.violations.map(v => `${v.impact} ${v.id} (${v.nodes.length})`) });
  }
  report.push({ w, consoleErrors: errors.slice(0, 10) });
  await ctx.close();
}
await browser.close();
fs.writeFileSync(`${out}/report.json`, JSON.stringify(report, null, 1));
console.log(JSON.stringify(report, null, 1));
