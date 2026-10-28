// 28m 段1：卡片 From 价格 vs 报价页实算价格 对照
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const BASE = 'http://localhost:8081';
const dec = (s) => s.replace(/&#0?38;/g, '&').replace(/&amp;/g, '&');

const res = await fetch(BASE + '/');
const html = await res.text();
const strip = (s) => s.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
const cards = [...html.matchAll(/<article class="pk-product-card">([\s\S]*?)<\/article>/g)].map((m) => {
  const b = m[1];
  const title = strip((b.match(/<span class="pk-card-title">([\s\S]*?)<\/span>/) || [, ''])[1]);
  const priceTxt = strip((b.match(/<strong>([\s\S]*?)<\/strong>/) || [, ''])[1]);
  const hrefs = [...b.matchAll(/href="([^"]+)"/g)].map((x) => dec(x[1]));
  return { title, cardPrice: parseFloat((priceTxt.match(/\$([\d.]+)/) || [, ''])[1]), cta: hrefs[hrefs.length - 1] };
});
const heroFrom = parseFloat((strip((html.match(/<dt>From[^<]*<\/dt><dd>([\s\S]*?)<\/dd>/) || [, ''])[1]).match(/\$([\d.]+)/) || [, ''])[1]);

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  env: { ...process.env, TEMP: 'E:/破甲工作区/.tmp', TMP: 'E:/破甲工作区/.tmp' },
});
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
await ctx.route('**/*', (r) => {
  const u = new URL(r.request().url());
  const ok = ['GET', 'HEAD'].includes(r.request().method()) && ['localhost', '127.0.0.1'].includes(u.hostname);
  return ok ? r.continue() : r.abort();
});
const page = await ctx.newPage();

const rows = [];
for (const c of cards) {
  const r = await page.goto(c.cta, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.pkq-price', { timeout: 15000 });
  await page.waitForTimeout(300);
  const quotePrice = await page.locator('.pkq-price').first().innerText();
  const qp = parseFloat((quotePrice.replace(/\s/g, '').match(/([\d.]+)/) || [, ''])[1]);
  rows.push({ title: c.title, cardPrice: c.cardPrice, quotePrice: qp, same: c.cardPrice === qp, status: r.status(), cta: c.cta });
}

const min = Math.min(...rows.map((x) => x.cardPrice));
console.log(JSON.stringify({ cardCount: cards.length, heroFrom, minCardPrice: min, heroMatchesMin: heroFrom === min, rows }, null, 2));
console.log(rows.every((x) => x.same) ? 'PASS 8/8 卡片价与报价页实算一致' : 'FAIL 存在不一致');
await browser.close();
