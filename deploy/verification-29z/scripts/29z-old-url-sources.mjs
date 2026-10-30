// 定位生产上仍引用 5 个旧 URL 的位置（页面 + 上下文 120 字符）
import fs from 'node:fs';
const BASE = 'https://www.packrivo.com';
const LANGS = ['', 'de', 'fr', 'es', 'it', 'nl', 'pt', 'ar', 'ja', 'ko', 'zh-CN', 'zh-TW'];
const BLOG = '2026/09/21/jewelry-box-insert-fit-measurements/';
const PRODUCT = 'product-magnetic-jewelry-box/';
const KINDS = ['home', 'products', 'services', 'blog', 'product'];
const OLD = ['/rigid-gift-boxes/', '/resources/', '/packaging-examples/', '/pricing/', '/sustainability/'];

function pageUrl(lang, kind) {
  const p = lang ? `${lang}/` : '';
  if (kind === 'home') return `/${p}`;
  if (kind === 'products') return `/${p}products/`;
  if (kind === 'services') return `/${p}services/`;
  if (kind === 'blog') return `/${p}${BLOG}`;
  return `/${p}${PRODUCT}`;
}

const hits = {};
for (const lang of LANGS) {
  for (const kind of KINDS) {
    const url = pageUrl(lang, kind);
    let body = '';
    try { const r = await fetch(BASE + url, { headers: { 'user-agent': 'packrivo-verify/29z' } }); if (r.status === 200) body = await r.text(); } catch { continue; }
    for (const o of OLD) {
      let i = body.indexOf(`"${o}`);
      if (i < 0) i = body.indexOf(`'${o}`);
      if (i < 0) i = body.indexOf(o);
      if (i >= 0) {
        hits[o] = hits[o] || [];
        hits[o].push({ page: url || '/', ctx: body.slice(Math.max(0, i - 90), i + 60).replace(/\s+/g, ' ') });
      }
    }
  }
}
const out = {};
for (const [k, v] of Object.entries(hits)) out[k] = { pages: v.length, first: v[0] };
fs.writeFileSync('E:/破甲工作区/packrivo-backup/deploy/verification-29z/evidence/old-url-sources-prod.json', JSON.stringify({ summary: Object.fromEntries(Object.entries(hits).map(([k, v]) => [k, v.length])), detail: out }, null, 2));
console.log(JSON.stringify(out, null, 2));
