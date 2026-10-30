// 29z 判据 4f：/products/ 在 1440 与 375 两个宽度各一张截图（改前 / 改后各一对）。
//
// 用法：
//   node 29z-products-shots.mjs <out-dir> <before|after> [base-url] [page-path]
//
//   base-url 省略 → 演练模式：本机起一个反代打到演练 nginx（127.0.0.1:8091，Host: nginx）
//   base-url 给了 → 直接访问该地址，例如生产用 https://www.packrivo.com
//                   （本机直连生产域名，截图不经过 --resolve；缓存由负责人清完再拍）
//
// 依赖：本机 Chrome + playwright-core（packrivo-v2/node_modules）
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = process.argv[2];
const TAG = process.argv[3] || 'before';
const BASE = (process.argv[4] || '').replace(/\/+$/, '');
const PAGE = process.argv[5] || '/products/';
if (!OUT) { console.error('用法: node 29z-products-shots.mjs <out-dir> <before|after> [base-url] [page-path]'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

// 演练模式才起反代；生产模式什么都不起
const REHEARSAL_PORT = 8091;
const REHEARSAL_HOST = 'nginx';
let proxy = null;
let origin = BASE;

if (!BASE) {
  proxy = http.createServer((req, res) => {
    const u = new URL(req.url, 'http://nginx');
    const up = http.request(
      { host: '127.0.0.1', port: REHEARSAL_PORT, method: req.method, path: u.pathname + u.search, headers: { ...req.headers, host: REHEARSAL_HOST } },
      (ur) => { res.writeHead(ur.statusCode || 502, ur.headers); ur.pipe(res); }
    );
    up.on('error', () => { res.writeHead(502); res.end('proxy error'); });
    req.pipe(up);
  });
  await new Promise((r) => proxy.listen(0, '127.0.0.1', r));
  origin = `http://${REHEARSAL_HOST}`;
}

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  args: [
    ...(proxy ? [`--proxy-server=http://127.0.0.1:${proxy.address().port}`, '--proxy-bypass-list=<-loopback>'] : []),
    // 演练环境：把域名钉到 127.0.0.1，写法与宿主机 curl --resolve 等价（SHOTS_MAP=www.packrivo.com）
    ...(process.env.SHOTS_MAP ? [`--host-resolver-rules=MAP ${process.env.SHOTS_MAP} 127.0.0.1`] : []),
    // 钉了域名就必须绕开本机系统代理，否则请求会被代理截走（实测 Windows 系统代理会返回 503）
    ...(process.env.SHOTS_MAP ? ['--no-proxy-server'] : []),
  ],
});

const result = { tag: TAG, base: origin, page: PAGE, shot: [], console_errors: [] };
for (const [w, h] of [[1440, 900], [375, 812]]) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, ignoreHTTPSErrors: true });
  const page = await ctx.newPage();
  page.on('console', (m) => { if (m.type() === 'error') result.console_errors.push(`${w}: ${m.text().slice(0, 120)}`); });
  const resp = await page.goto(origin + PAGE, { waitUntil: 'networkidle', timeout: 90000 });
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
if (proxy) proxy.close();
fs.writeFileSync(path.join(OUT, `shots-${TAG}.json`), JSON.stringify(result, null, 2));
console.log(JSON.stringify(result, null, 2));
