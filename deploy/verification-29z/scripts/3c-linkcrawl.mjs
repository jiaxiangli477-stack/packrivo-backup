// 3c 验收：12 语种 × 5 页 = 60 页；页内所有站内 href/src 去重后逐个请求，
// 统计非 200（301 到最终 200 记入 redirected-ok 并列出）；字体文件 200 + font/woff2；
// 死链字符串（/resources/ 等）在 href/src 属性内残留检查。
// 运行：node 3c-linkcrawl.mjs
// 输出：evidence/3c-linkcrawl.json、evidence/3c-linkcrawl-summary.txt、evidence/3c-pages/*.html
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8091';
const HOST_HEADER = 'nginx';
const OUT_DIR = path.resolve(process.cwd(), 'evidence');
const PAGES_DIR = path.join(OUT_DIR, '3c-pages');
fs.mkdirSync(PAGES_DIR, { recursive: true });

const SAME_SITE_HOSTS = new Set(['nginx', 'www.packrivo.com', 'packrivo.com']);

function normalize(u) {
  if (!u) return null;
  u = u.trim();
  if (!u) return null;
  if (/^(#|mailto:|tel:|javascript:|data:|blob:)/i.test(u)) return null;
  let s = u.split('#')[0];
  if (!s) return null;
  if (s.startsWith('//')) s = 'https:' + s;
  if (/^https?:\/\//i.test(s)) {
    let m;
    try { m = new URL(s); } catch { return null; }
    const host = m.hostname.toLowerCase();
    if (SAME_SITE_HOSTS.has(host)) return m.pathname + m.search;
    return { external: s }; // 非本站 → 跳过
  }
  if (s.startsWith('/')) return s;
  return null;
}

function extractLinks(html) {
  const out = new Set();
  const attrRe = /(?:href|src)=["']([^"']+)["']/gi;
  let m;
  while ((m = attrRe.exec(html))) {
    const n = normalize(m[1]);
    if (n && typeof n === 'string') out.add(n);
  }
  const srcsetRe = /srcset=["']([^"']+)["']/gi;
  while ((m = srcsetRe.exec(html))) {
    for (const part of m[1].split(',')) {
      const tok = part.trim().split(/\s+/)[0];
      if (!tok) continue;
      const n = normalize(tok);
      if (n && typeof n === 'string') out.add(n);
    }
  }
  // 内联 CSS 里的 @font-face url()（字体判据用）
  const fontRe = /url\(\s*["']?([^"')\s]+\.woff2[^"')]*?)["']?\s*\)/gi;
  while ((m = fontRe.exec(html))) {
    const n = normalize(m[1]);
    if (n && typeof n === 'string') out.add(n);
  }
  return out;
}

async function fetchPage(u) {
  try {
    const res = await fetch(BASE + u, {
      headers: { Host: HOST_HEADER, 'User-Agent': '3c-linkcrawl/1.0' },
      redirect: 'manual',
      signal: AbortSignal.timeout(15000),
    });
    const ct = res.headers.get('content-type') || '';
    const body = await res.text();
    return { status: res.status, location: res.headers.get('location'), contentType: ct, body, bytes: Buffer.byteLength(body) };
  } catch (e) {
    return { status: 0, error: String((e && e.message) || e), body: '' };
  }
}

async function resolveUrl(u, chainOut) {
  const chain = [];
  let cur = u;
  for (let hop = 0; hop <= 6; hop++) {
    const r = await fetchPage(cur);
    chain.push({ url: cur, status: r.status, location: r.location, contentType: r.contentType });
    if ([301, 302, 303, 307, 308].includes(r.status) && r.location) {
      const loc = normalize(r.location);
      if (!loc || typeof loc !== 'string') { r.finalExternal = r.location; break; }
      if (hop === 6) break;
      cur = loc;
      continue;
    }
    break;
  }
  const final = chain[chain.length - 1];
  chainOut.push(chain);
  return { finalStatus: final.status, finalContentType: final.contentType || '', chain };
}

const LOCALES = [
  { code: 'en', pre: '' },
  { code: 'de', pre: '/de' },
  { code: 'fr', pre: '/fr' },
  { code: 'es', pre: '/es' },
  { code: 'it', pre: '/it' },
  { code: 'nl', pre: '/nl' },
  { code: 'pt', pre: '/pt' },
  { code: 'ar', pre: '/ar' },
  { code: 'ja', pre: '/ja' },
  { code: 'ko', pre: '/ko' },
  { code: 'zh-CN', pre: '/zh-CN' },
  { code: 'zh-TW', pre: '/zh-TW' },
];

// 博客文章与产品页在演练环境均未本地化（/de/2026/... 与 /de/catalog-... 均 404），故各语种用同一 root 路径
const BLOG_POST = '/2026/10/26/molded-pulp-tray-vs-end-supports-packout/';
const PRODUCT_PAGE = '/catalog-rigid-lid-and-base-box-652818/';

const PAGE_SET = [];
for (const l of LOCALES) {
  const b = l.pre;
  PAGE_SET.push(
    { locale: l.code, kind: 'home', url: b + '/' },
    { locale: l.code, kind: 'products', url: b + '/products/' },
    { locale: l.code, kind: 'services', url: b + '/services/' },
    { locale: l.code, kind: 'blogpost', url: BLOG_POST },
    { locale: l.code, kind: 'product', url: PRODUCT_PAGE },
  );
}

// ---------- 1) 抓 60 页 ----------
const pagesResult = [];
const linkSet = new Map(); // url -> Set(page labels)
const deadPatterns = ['/resources/', '/packaging-examples/', '/pricing/', '/rigid-gift-boxes/', '/sustainability/'];
const deadLeftover = [];
for (const p of PAGE_SET) {
  const r = await fetchPage(p.url);
  let saved = '';
  if (r.status === 200 && r.body) {
    saved = `${p.locale.replace(/[^A-Za-z0-9]/g, '_')}--${p.kind}.html`;
    fs.writeFileSync(path.join(PAGES_DIR, saved), r.body);
  }
  pagesResult.push({ locale: p.locale, kind: p.kind, url: p.url, status: r.status, bytes: r.bytes || 0, saved });
  if (r.status === 200 && r.body) {
    for (const link of extractLinks(r.body)) {
      if (!linkSet.has(link)) linkSet.set(link, new Set());
      linkSet.get(link).add(`${p.locale}:${p.kind}`);
    }
    for (const dp of deadPatterns) {
      // 只在 href/src/srcset 属性值里找残留
      const attrVals = [...r.body.matchAll(/(?:href|src|srcset)=["']([^"']+)["']/gi)].map(x => x[1]).join(' ');
      if (attrVals.includes(dp)) {
        deadLeftover.push({ locale: p.locale, kind: p.kind, url: p.url, pattern: dp });
      }
    }
  }
}

// ---------- 2) 去重后逐个请求 ----------
const chains = [];
const results = [];
const urls = [...linkSet.keys()].sort();
for (const u of urls) {
  const r = await resolveUrl(u, chains);
  results.push({ url: u, finalStatus: r.finalStatus, contentType: r.finalContentType, chain: r.chain });
}

const non200 = results.filter(r => r.finalStatus !== 200);
const redirectedOk = results.filter(r => r.finalStatus === 200 && r.chain.length > 1 && [301, 302, 303, 307, 308].includes(r.chain[0].status));
const woff2Hits = results.filter(r => r.url.includes('.woff2'));
const badFont = woff2Hits.filter(r => r.finalStatus !== 200 || !/font\/woff2/i.test(r.contentType));

const summary = {
  base: BASE,
  pagesFetched: PAGE_SET.length,
  pagesNon200: pagesResult.filter(p => p.status !== 200).map(p => ({ locale: p.locale, kind: p.kind, url: p.url, status: p.status })),
  uniqueLinks: urls.length,
  non200Count: non200.length,
  non200: non200.map(r => ({ url: r.url, finalStatus: r.finalStatus, contentType: r.contentType, chain: r.chain.map(c => `${c.status} ${c.url}`) })),
  redirectedOkCount: redirectedOk.length,
  redirectedOk: redirectedOk.map(r => ({ url: r.url, chain: r.chain.map(c => `${c.status} -> ${c.url}`) })),
  woff2Count: woff2Hits.length,
  woff2: woff2Hits.map(r => ({ url: r.url, finalStatus: r.finalStatus, contentType: r.contentType })),
  badFont,
  deadLeftover,
};

fs.writeFileSync(path.join(OUT_DIR, '3c-linkcrawl.json'), JSON.stringify({ summary, pagesResult, results }, null, 2));

let txt = `3c 链接验收（演练环境 ${BASE}，Host: ${HOST_HEADER}）
抓取页数: ${PAGE_SET.length}（12 语种 × 首页//products//services/博客/产品页）
60 页中非 200: ${summary.pagesNon200.length} ${JSON.stringify(summary.pagesNon200)}
页内站内 href/src 去重后链接数: ${urls.length}
最终非 200 数: ${non200.length} ${non200.length ? '=> FAIL' : '=> PASS（判据：=0）'}
301→最终200 数: ${redirectedOk.length}
  ${redirectedOk.map(r => r.url + ' | ' + r.chain.map(c => c.status + ' ' + c.url).join(' -> ')).join('\n  ') || '(无)'}
woff2 链接: ${woff2Hits.length}
  ${woff2Hits.map(r => `${r.url} | ${r.finalStatus} | ${r.contentType}`).join('\n  ') || '(无)'}
非 200 明细:
  ${non200.map(r => `${r.url} | final=${r.finalStatus} | ${r.contentType}`).join('\n  ') || '(无)'}
死链字符串残留（href/src/srcset 属性）: ${deadLeftover.length} ${JSON.stringify(deadLeftover)}
`;
fs.writeFileSync(path.join(OUT_DIR, '3c-linkcrawl-summary.txt'), txt);
console.log(txt);
