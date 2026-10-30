// 29z 判据 4f：/products/ 在 1440 与 375 两个宽度各一张截图（改前 / 改后各一对）。
// 用法：node 29z-products-shots.mjs <out-dir> <before|after>
// 依赖：本机 Chrome + playwright-core（packrivo-v2/node_modules）
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = process.argv[2];
const TAG = process.argv[3] || 'before';
const PAGE = process.argv[4] || '/products/';
if (!OUT) { console.error('用法: node 29z-products-shots.mjs <out-dir> <before|after>'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

const UPSTREAM_PORT = 8091;
const UPSTREAM_HOST = 'nginx';

const proxy = http.createServer((req, res) => {
  const u = new URL(req.url, 'http://nginx');
  const up = http.request(
    { host: '127.0.0.1', port: UPSTREAM_PORT, method: req.method, path: u.pathname + u.search, headers: { ...req.headers, host: UPSTREAM_HOST } },
    (ur) => { res.writeHead(ur.statusCode || 502, ur.headers); ur.pipe(res); }
  );
  up.on('error', () => { res.writeHead(502); res.end('proxy error'); });
  req.pipe(up);
});
await new Promise((r) => proxy.listen(0, '127.0.0.1', r));
const PORT = proxy.address().port;

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  args: [`--proxy-server=http://127.0.0.1:${PORT}`, '--proxy-bypass-list=<-loopback>'],
});
const result = { tag: TAG, page: PAGE, shot: [], console_errors: [] };
for (const [w, h] of [[1440, 900], [375, 812]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h } });
  const page = await ctx.newPage();
  page.on('console', (m) => { if (m.type() === 'error') result.console_errors.push(`${w}: ${m.text().slice(0, 120)}`); });
  const resp = await page.goto('http://nginx' + PAGE, { waitUntil: 'networkidle', timeout: 60000 });
  // 关掉 cookie 同意条，否则两档截图底部都被挡
  for (const label of ['Accept', 'Reject']) {
    const btn = page.locator(`button:has-text("${label}")`).first();
    try { if (await btn.count()) { await btn.click({ timeout: 2500 }); await page.waitForTimeout(400); break; } } catch { }
  }
  await page.waitForTimeout(600);
  const file = path.join(OUT, `${TAG}-${w}.png`);
  await page.screenshot({ path: file, fullPage: false });
  const info = await page.evaluate(() => ({
    cards: document.querySelectorAll('.pk-product-card').length,
    titles: Array.from(document.querySelectorAll('.pk-card-title')).map((e) => e.textContent.trim()),
    scrollW: document.documentElement.scrollWidth,
    clientW: document.documentElement.clientWidth,
    yen: (document.documentElement.innerHTML.match(/¥/g) || []).length,
  }));
  result.shot.push({
    width: w, height: h, status: resp ? resp.status() : 0,
    file: path.basename(file), bytes: fs.statSync(file).size,
    cards: info.cards, overflow: info.scrollW > info.clientW, yen_in_dom: info.yen,
    first_titles: info.titles.slice(0, 3),
  });
  await ctx.close();
}
await browser.close();
proxy.close();
fs.writeFileSync(path.join(OUT, `shots-${TAG}.json`), JSON.stringify(result, null, 2));
console.log(JSON.stringify(result, null, 2));
