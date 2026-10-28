// 28m 段1：首页 375 / 768 / 1280 截图 + 溢出检查
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
import fs from 'node:fs';

const OUT = 'E:/破甲工作区/packrivo-backup/deploy/verification-28m';
fs.mkdirSync(OUT, { recursive: true });
const tag = process.argv[2] || 'after';
const widths = [375, 768, 1280];

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  env: { ...process.env, TEMP: 'E:/破甲工作区/.tmp', TMP: 'E:/破甲工作区/.tmp' },
});
const rows = [];
for (const w of widths) {
  const ctx = await browser.newContext({ viewport: { width: w, height: w < 800 ? 900 : 1000 }, deviceScaleFactor: 1 });
  await ctx.route('**/*', (r) => {
    const u = new URL(r.request().url());
    const ok = ['GET', 'HEAD'].includes(r.request().method()) && ['localhost', '127.0.0.1', 'www.packrivo.com'].includes(u.hostname);
    return ok ? r.continue() : r.abort();
  });
  const page = await ctx.newPage();
  await page.goto('http://localhost:8081/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  const info = await page.evaluate(() => ({
    cards: document.querySelectorAll('.pk-product-card').length,
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth: window.innerWidth,
    heroFrom: (document.querySelector('.pk-stats')?.textContent || '').replace(/\s+/g, ' ').trim(),
  }));
  const file = `${OUT}/home-${tag}-${w}.png`;
  await page.screenshot({ path: file, fullPage: false });
  await page.screenshot({ path: file.replace('.png', '-full.png'), fullPage: true });
  rows.push({ width: w, ...info, file });
  await ctx.close();
}
console.log(JSON.stringify(rows, null, 2));
await browser.close();
