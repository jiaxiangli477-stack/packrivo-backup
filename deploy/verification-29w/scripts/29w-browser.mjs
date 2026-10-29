// 29w 4.6e：演练站 /products/ 三宽度横向溢出（真实 Chrome）
// 站点 siteurl=http://nginx，宿主机解析不到 → 本脚本起一个本地 HTTP 代理（127.0.0.1:8899），
// 把 Host 原样带给 127.0.0.1:8091 的 nginx 发布口，这样 WP 看到的 Host 仍是 nginx，不会触发 canonical 跳转。
// 用法: node 29w-browser.mjs <outDir>
import http from 'node:http';
import fs from 'node:fs';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = process.argv[2] || 'E:/破甲工作区/packrivo-backup/deploy/verification-29w/evidence';
fs.mkdirSync(OUT, { recursive: true });
const SITE = 'http://nginx';
const UP = { host: '127.0.0.1', port: 8091 };
const PROXY_PORT = 8899;
const CB = Date.now();

const proxy = http.createServer((req, res) => {
  const u = new URL(req.url, SITE);
  const headers = { ...req.headers, host: 'nginx' };
  const up = http.request({ ...UP, method: req.method, path: u.pathname + u.search, headers }, (ur) => {
    res.writeHead(ur.statusCode || 502, ur.headers);
    ur.pipe(res);
  });
  up.on('error', (e) => { res.writeHead(502, { 'content-type': 'text/plain' }); res.end('upstream error: ' + e.message); });
  req.pipe(up);
});
await new Promise((r) => proxy.listen(PROXY_PORT, '127.0.0.1', r));

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  args: [`--proxy-server=http://127.0.0.1:${PROXY_PORT}`, '--proxy-bypass-list=<-loopback>'],
  env: { ...process.env, TEMP: 'E:/破甲工作区/.tmp', TMP: 'E:/破甲工作区/.tmp' },
});
const ctx = await browser.newContext();
// 万一有 https://www.packrivo.com 的 canonical 跳转（CONNECT 走不了 http 代理），直接改写成演练站的内网地址
await ctx.route('https://www.packrivo.com/**', (r) => r.fulfill({ status: 301, headers: { location: SITE + new URL(r.request().url()).pathname + new URL(r.request().url()).search } }));
await ctx.route('https://packrivo.com/**', (r) => r.fulfill({ status: 301, headers: { location: SITE + new URL(r.request().url()).pathname + new URL(r.request().url()).search } }));

const measure = () => {
  const de = document.documentElement;
  const inScroller = (el) => {
    for (let q = el.parentElement; q && q !== document.body; q = q.parentElement) {
      const ox = getComputedStyle(q).overflowX;
      if (['auto', 'scroll', 'hidden'].includes(ox)) return true;
    }
    return false;
  };
  const bad = [...document.querySelectorAll('body *')].filter((e) => {
    const r = e.getBoundingClientRect();
    return r.width > 0 && r.height > 0 && !inScroller(e) && (r.right > window.innerWidth + 1 || r.left < -1);
  }).map((e) => {
    const r = e.getBoundingClientRect();
    return { tag: e.tagName.toLowerCase(), cls: (e.className || '').toString().slice(0, 60), right: Math.round(r.right), w: Math.round(r.width) };
  });
  const cards = document.querySelector('.pk-cards');
  return {
    url: location.href,
    bodyClass: document.body.className,
    everyStructure: (document.documentElement.innerHTML.match(/Every structure we can source/g) || []).length,
    chips: document.querySelectorAll('.pk-chip').length,
    cards: cards ? cards.children.length : 0,
    scrollWidth: de.scrollWidth,
    innerWidth: window.innerWidth,
    bodyScrollWidth: document.body.scrollWidth,
    badCount: bad.length,
    bad: bad.slice(0, 6),
  };
};

const rows = [];
async function probe(path, width, height) {
  const page = await ctx.newPage();
  await page.setViewportSize({ width, height });
  let status = 0, m = null, err = '';
  try {
    const resp = await page.goto(`${SITE}${path}?cb=${CB}-${width}`, { waitUntil: 'load', timeout: 60000 });
    status = resp ? resp.status() : 0;
    for (let i = 0; i < 5 && !m; i++) {
      try {
        await page.waitForTimeout(800);
        m = await page.evaluate(measure);
      } catch (e) {
        err = String(e).slice(0, 90);
        await page.waitForLoadState('load', { timeout: 10000 }).catch(() => {});
      }
    }
  } catch (e) {
    status = -1; err = String(e).slice(0, 120);
  }
  if (!m) m = { url: '', bodyClass: '(measure-failed ' + err + ')', everyStructure: 0, chips: 0, cards: 0, scrollWidth: -1, innerWidth: width, bodyScrollWidth: -1, badCount: -1, bad: [] };
  if (path === '/products/') await page.screenshot({ path: `${OUT}/rehearsal-products-${width}.png` }).catch(() => {});
  await page.close();
  rows.push({ path, width, status, ...m });
}

for (const [w, h] of [[375, 812], [768, 1024], [1024, 768]]) await probe('/products/', w, h);
await probe('/de/products/', 1024, 768);
await probe('/ar/products/', 1024, 768);
for (const p of ['/', '/blog/', '/get-a-quote/']) await probe(p, 1024, 768);

await browser.close();
proxy.close();

const judge = rows.map((r) => ({
  k: `${r.path} ${r.width}px`,
  status: r.status,
  template: r.path.endsWith('/products/') ? r.bodyClass.includes('page-template-template-products-v4') : null,
  text: r.path === '/products/' ? r.everyStructure > 0 : null,
  overflow: r.scrollWidth === r.innerWidth && r.badCount === 0,
  sw: r.scrollWidth, iw: r.innerWidth, bad: r.badCount, chips: r.chips, cards: r.cards,
}));
fs.writeFileSync(`${OUT}/rehearsal-browser.json`, JSON.stringify({ cb: CB, time: new Date().toISOString(), site: SITE, proxyPort: PROXY_PORT, upstream: UP, rows, judge }, null, 1));
console.log(JSON.stringify(judge, null, 1));
console.log('4.6e FAIL 数 =', rows.filter((r) => r.path === '/products/' && !(r.scrollWidth === r.innerWidth && r.badCount === 0)).length);
console.log('4.6 模板标记不对的条数 =', rows.filter((r) => r.path.endsWith('/products/') && !r.bodyClass.includes('page-template-template-products-v4')).length);
