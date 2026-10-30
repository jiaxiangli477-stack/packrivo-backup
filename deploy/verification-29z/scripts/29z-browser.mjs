// 29z §3c 判据：12 语种 × 5 个页面 = 60 页，抽出全部站内 href/src 去重后逐个请求。
// 用法：node 29z-browser.mjs <evidence-dir>
// 依赖：本机 Chrome + playwright-core（packrivo-v2/node_modules）
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = process.argv[2] || 'E:/破甲工作区/packrivo-backup/deploy/verification-29z/evidence';
const UPSTREAM_PORT = 8091;
const UPSTREAM_HOST = 'nginx';
let PROXY_PORT = 0;

const LANGS = ['', 'de', 'fr', 'es', 'it', 'nl', 'pt', 'ar', 'ja', 'ko', 'zh-CN', 'zh-TW'];
const BLOG = '2026/09/21/jewelry-box-insert-fit-measurements/';
const PRODUCT = 'product-magnetic-jewelry-box/';
const KINDS = ['home', 'products', 'services', 'blog', 'product'];

function pageUrl(lang, kind) {
  const p = lang ? `${lang}/` : '';
  if (kind === 'home') return `/${p}`;
  if (kind === 'products') return `/${p}products/`;
  if (kind === 'services') return `/${p}services/`;
  if (kind === 'blog') return `/${p}${BLOG}`;
  return `/${p}${PRODUCT}`;
}

// —— 代理：把对 nginx 的请求转到本地 8091，Host 改成 nginx ——
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
PROXY_PORT = proxy.address().port;

function rawGet(urlPath, host) {
  return new Promise((resolve) => {
    const req = http.request(
      { host: '127.0.0.1', port: UPSTREAM_PORT, path: urlPath, method: 'GET', headers: { host: host || UPSTREAM_HOST } },
      (res) => {
        const chunks = [];
        res.on('data', (c) => chunks.push(c));
        res.on('end', () => resolve({ code: res.statusCode, type: res.headers['content-type'] || '', len: Buffer.concat(chunks).length, body: Buffer.concat(chunks).toString('utf8'), loc: res.headers['location'] || '' }));
      }
    );
    // 没超时就会永远挂在一个不回包的 URL 上
    req.setTimeout(Number(process.env.CRAWL_TIMEOUT_MS || 60000), () => { req.destroy(new Error('timeout')); });
    req.on('error', (e) => resolve({ code: 0, type: '', len: 0, body: '', loc: '', err: e.message }));
    req.end();
  });
}

/** 简单并发池：一批任务同时只跑 n 个 */
async function pool(items, n, worker) {
  const out = new Array(items.length);
  let i = 0;
  await Promise.all(
    Array.from({ length: Math.min(n, items.length) }, async () => {
      while (i < items.length) {
        const idx = i++;
        out[idx] = await worker(items[idx], idx);
      }
    })
  );
  return out;
}

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  args: [`--proxy-server=http://127.0.0.1:${PROXY_PORT}`, '--proxy-bypass-list=<-loopback>'],
  env: { ...process.env, TEMP: 'E:/破甲工作区/.tmp', TMP: 'E:/破甲工作区/.tmp' },
});
const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });

// —— 1) 抓 60 页 + 抽链接 ——
const pages = [];
const linkSet = new Map(); // url -> {foundOn:[], kind:'href'|'src'}
for (const lang of LANGS) {
  for (const kind of KINDS) {
    const url = pageUrl(lang, kind);
    const r = await rawGet(url);
    const hrefs = [...r.body.matchAll(/\b(href|src)\s*=\s*"([^"]+)"/gi)].map((m) => ({ kind: m[1].toLowerCase(), val: m[2] }));
    pages.push({ url, status: r.code, bytes: r.len, hrefs: hrefs.length });
    for (const h of hrefs) {
      let v = h.val.trim();
      if (!v || v.startsWith('#') || v.startsWith('data:') || v.startsWith('mailto:') || v.startsWith('tel:')) continue;
      if (v.startsWith('//')) v = 'http:' + v;
      if (/^https?:\/\//i.test(v)) {
        const u = new URL(v);
        if (!['nginx', 'www.packrivo.com', 'packrivo.com'].includes(u.hostname)) continue;
        v = u.pathname + u.search;
      }
      if (!v.startsWith('/')) continue;
      const key = v;
      if (!linkSet.has(key)) linkSet.set(key, { url: key, foundOn: [], kinds: new Set() });
      const rec = linkSet.get(key);
      if (rec.foundOn.length < 6) rec.foundOn.push(`/${lang ? lang + '/' : ''}:${kind}`);
      rec.kinds.add(h.kind);
    }
  }
}

// —— 2) 逐个请求去重后的链接（并发 8，单个 12 秒超时）——
const toCheck = [...linkSet.keys()].sort();
const checked = await pool(toCheck, Number(process.env.CRAWL_CONCURRENCY || 6), async (u) => {
  const r = await rawGet(u);
  let final = r.code;
  let via301 = '';
  if ([301, 302, 307, 308].includes(r.code) && r.loc) {
    const target = r.loc.replace(/^https?:\/\/[^/]+/i, '');
    const r2 = await rawGet(target);
    final = r2.code;
    via301 = `${r.code} -> ${target} (${r2.code})`;
  }
  return { url: u, first: r.code, final, via301, type: r.type };
});

// —— 3) 字体单独校验 ——
const home = await rawGet('/');
const fontPath = (home.body.match(/\/[^"']*packrivo-inter-latin-v3\.woff2/) || [''])[0];
const font = fontPath ? await rawGet(fontPath) : { code: 0, type: '', len: 0 };

// —— 4) 截图：首页 + 3 个 catalog 页 × 2 宽度 ——
const shots = [];
const shotTargets = [
  ['home', '/'],
  ['catalog-a', '/catalog-ring-box-o1cn01ahes/'],
  ['catalog-b', '/catalog-velvet-box/'],
  ['catalog-c', '/catalog-jewelry-box/'],
];
for (const [key, url] of shotTargets) {
  for (const [w, h] of [[1440, 900], [375, 812]]) {
    const page = await ctx.newPage();
    await page.setViewportSize({ width: w, height: h });
    try {
      await page.goto('http://nginx' + url, { waitUntil: 'load', timeout: Number(process.env.PAGE_TIMEOUT_MS || 90000) });
      await page.waitForTimeout(400);
      const file = path.join(OUT, 'screens', `${key}-${w}.png`);
      fs.mkdirSync(path.dirname(file), { recursive: true });
      await page.screenshot({ path: file, fullPage: false });
      shots.push({ key, url, width: w, file: path.basename(file), ok: true });
    } catch (e) {
      shots.push({ key, url, width: w, ok: false, err: String(e).slice(0, 120) });
    }
    await page.close();
  }
}

await ctx.close();
await browser.close();
proxy.close();

// —— 5) 汇总 ——
const bad = checked.filter((c) => c.final !== 200);
const redirected = checked.filter((c) => c.via301);
const pagesBad = pages.filter((p) => p.status !== 200);
const result = {
  time: new Date().toISOString(),
  pages: pages.length,
  pagesBad: pagesBad.map((p) => ({ url: p.url, status: p.status })),
  links: checked.length,
  linksBad: bad.map((b) => ({ url: b.url, final: b.final, type: b.type })),
  linksRedirected: redirected,
  font: { path: fontPath, code: font.code, type: font.type, bytes: font.len },
  shots,
  detail: checked,
  pageDetail: pages,
};
fs.mkdirSync(OUT, { recursive: true });
fs.writeFileSync(path.join(OUT, 'crawl-29z.json'), JSON.stringify(result, null, 1));

console.log(`页面 ${pages.length} 个，非 200 页面 ${pagesBad.length}`);
if (pagesBad.length) console.log('  非 200 页面:', pagesBad.map((p) => `${p.url}=${p.status}`).join(', '));
console.log(`站内链接去重后 ${checked.length} 个，非 200 = ${bad.length}，走 301 的 ${redirected.length}`);
if (redirected.length) console.log('  301 清单:', redirected.map((r) => `${r.url} ${r.via301}`).join(' | '));
if (bad.length) console.log('  非 200 清单:', bad.map((b) => `${b.url}=${b.final}`).join(', '));
console.log(`字体 ${fontPath} -> ${font.code} ${font.type} ${font.len}B`);
console.log(`截图 ${shots.filter((s) => s.ok).length}/${shots.length}`);
console.log('PASS/FAIL 待判定：链接非200=' + bad.length + '  字体=' + (font.code === 200 && font.type.includes('woff2') ? 'ok' : 'bad'));
