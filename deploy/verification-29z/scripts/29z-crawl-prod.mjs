// 29z §3c 生产侧：对 https://www.packrivo.com 抓 12 语种 x 5 页 = 60 页，
// 抽全部站内 href/src 去重后逐个请求，统计非 200 并分类。纯 fetch，不渲染 JS。
// 用法：node 29z-crawl-prod.mjs <out-dir>
import fs from 'node:fs';
import path from 'node:path';

const OUT = process.argv[2];
if (!OUT) { console.error('用法: node 29z-crawl-prod.mjs <out-dir>'); process.exit(2); }
fs.mkdirSync(OUT, { recursive: true });

const BASE = 'https://www.packrivo.com';
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

async function get(url, ms = 20000) {
  const ctl = new AbortController();
  const t = setTimeout(() => ctl.abort(), ms);
  try {
    const r = await fetch(url, { signal: ctl.signal, redirect: 'follow', headers: { 'user-agent': 'packrivo-verify/29z' } });
    const body = r.status === 200 ? await r.text() : '';
    return { status: r.status, type: r.headers.get('content-type') || '', len: (r.headers.get('content-length') || '0'), body };
  } catch (e) {
    return { status: 0, type: '', len: '0', body: '', err: String(e.message || e) };
  } finally { clearTimeout(t); }
}

const pages = [];
for (const lang of LANGS) for (const kind of KINDS) pages.push({ lang: lang || 'en', kind, url: pageUrl(lang, kind) });

const result = { base: BASE, pages: [], links: { unique: 0, non200: [], by_class: {} }, fonts: [], shots_note: '' };

// 1) 60 页
for (const p of pages) {
  const r = await get(BASE + p.url);
  result.pages.push({ lang: p.lang, kind: p.kind, url: p.url, status: r.status, content_type: r.type, bytes: r.body.length });
  if (r.status === 200) {
    globalThis.__html = globalThis.__html || [];
    globalThis.__html.push({ url: p.url, body: r.body });
  }
}

// 2) 抽链接
const links = new Map();
for (const { url, body } of globalThis.__html || []) {
  const re = /(?:href|src)=["']([^"'#]+)["']/g;
  let m;
  while ((m = re.exec(body))) {
    let u = m[1].trim();
    if (!u || u.startsWith('data:') || u.startsWith('mailto:') || u.startsWith('javascript:') || u.startsWith('tel:')) continue;
    if (u.startsWith('//')) u = 'https:' + u;
    if (u.startsWith(BASE)) u = u.slice(BASE.length) || '/';
    if (!u.startsWith('/')) continue;
    links.set(u, (links.get(u) || 0) + 1);
  }
}
result.links.unique = links.size;

// 3) 并发 8 逐个请求
const queue = [...links.keys()];
const bad = [];
async function worker() {
  while (queue.length) {
    const u = queue.shift();
    const r = await get(BASE + u, 20000);
    if (r.status !== 200) bad.push({ url: u, status: r.status, type: r.type, err: r.err || '' });
  }
}
await Promise.all(Array.from({ length: 8 }, worker));
result.links.non200 = bad;
for (const b of bad) {
  const k = b.url.startsWith('/wp-content/uploads/') ? 'uploads'
    : b.url.startsWith('/assets/') ? 'assets'
    : b.url.includes('/2026/') && b.url.split('/').length > 4 ? 'blog-per-lang'
    : b.status === 0 ? 'network' : 'other';
  (result.links.by_class[k] = result.links.by_class[k] || []).push(b.url);
}
for (const k of Object.keys(result.links.by_class)) result.links.by_class[k] = result.links.by_class[k].length;

// 4) 字体
const fontUrl = '/wp-content/themes/packrivo-static-bridge-1.2.38/packrivo-static/assets/packrivo-inter-latin-v3.woff2';
const fr = await fetch(BASE + fontUrl, { headers: { 'user-agent': 'packrivo-verify/29z' } });
result.fonts.push({ url: fontUrl, status: fr.status, type: fr.headers.get('content-type'), len: fr.headers.get('content-length') });
const homeHtml = (globalThis.__html || []).find((h) => h.url === '/')?.body || '';
const fm = homeHtml.match(/https:\/\/[^"']*packrivo-inter-latin-v3\.woff2/g) || [];
result.fonts.push({ url_in_home: [...new Set(fm)].slice(0, 3) });

fs.writeFileSync(path.join(OUT, 'crawl-29z-prod.json'), JSON.stringify(result, null, 2));
console.log(JSON.stringify({
  pages: result.pages.length,
  pages_non200: result.pages.filter((p) => p.status !== 200).length,
  links_unique: result.links.unique,
  links_non200: bad.length,
  by_class: result.links.by_class,
  fonts: result.fonts,
}, null, 2));
