// 29x 浏览器判定（真实 Chrome，走本地 HTTP 代理把 Host 原样带给 8091 的 nginx）：
//   A. 博客列表 /blog/ 375/768/1024/1440：无横向溢出、12 张卡片、每张都有封面图
//   B. 20 个原生页面 × 4 宽度：无横向溢出；面包屑与 H1 距左边缘 >= 16px；1440 下内容左边缘与 logo 左边缘误差 <= 4px
//   C. 首页 / 产品页 / 报价页不受影响
//   每个原生页面另存一张 1440 截图
// 用法: node 29x-browser.mjs <outDir>
import http from 'node:http';
import fs from 'node:fs';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = process.argv[2] || 'E:/破甲工作区/packrivo-backup/deploy/verification-29x/evidence';
const SHOTS = OUT + '/screens';
fs.mkdirSync(SHOTS, { recursive: true });
const SITE = 'http://nginx';
const UP = { host: '127.0.0.1', port: 8091 };
let PROXY_PORT = 0; // 由系统分配，避免上一轮残留进程占端口
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
await new Promise((r) => proxy.listen(0, '127.0.0.1', r));
PROXY_PORT = proxy.address().port;

const browser = await chromium.launch({
  executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  headless: true,
  args: [`--proxy-server=http://127.0.0.1:${PROXY_PORT}`, '--proxy-bypass-list=<-loopback>'],
  env: { ...process.env, TEMP: 'E:/破甲工作区/.tmp', TMP: 'E:/破甲工作区/.tmp' },
});
const ctx = await browser.newContext();
for (const host of ['https://www.packrivo.com/**', 'https://packrivo.com/**']) {
  await ctx.route(host, (r) => {
    const u = new URL(r.request().url());
    return r.fulfill({ status: 301, headers: { location: SITE + u.pathname + u.search } });
  });
}

const PAGES = [
  ['/services/', 'services'],
  ['/about/', 'about'],
  ['/customization/', 'customization'],
  ['/samples/', 'samples'],
  ['/jewelry-boxes/', 'jewelry-boxes'],
  ['/products/', 'products'],
  ['/inserts/', 'inserts'],
  ['/inquiry/', 'inquiry'],
  ['/packaging-samples/', 'packaging-samples'],
  ['/guide-jewelry-insert-fit/', 'guide-jewelry-insert-fit'],
  ['/product-magnetic-jewelry-box/', 'product-magnetic-jewelry-box'],
  ['/product-drawer-jewelry-box/', 'product-drawer-jewelry-box'],
  ['/product-lid-and-base-jewelry-box/', 'product-lid-and-base-jewelry-box'],
  ['/product-paperboard-insert/', 'product-paperboard-insert'],
  ['/product-die-cut-insert/', 'product-die-cut-insert'],
  ['/thermoformed-insert-tray/', 'thermoformed-insert-tray'],
  ['/molded-pulp-insert/', 'molded-pulp-insert'],
  ['/product-mailer-with-insert/', 'product-mailer-with-insert'],
  ['/de/services/', 'de-services'],
  ['/ja/inserts/', 'ja-inserts'],
];
const WIDTHS = [[375, 812], [768, 1024], [1024, 768], [1440, 900]];

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
    return { tag: e.tagName.toLowerCase(), cls: (e.className || '').toString().slice(0, 50), right: Math.round(r.right), w: Math.round(r.width) };
  });

  const h1 = document.querySelector('h1');
  const crumb = document.querySelector('.breadcrumb, [class*="breadcrumb"], nav[aria-label*="readcrumb"], #breadcrumbs');
  const wrap = document.querySelector('.pk-page-body');
  const logo = document.querySelector('.pk-brand-mark') || document.querySelector('.pk-brand');
  const rect = (el) => (el ? el.getBoundingClientRect() : null);

  const cards = [...document.querySelectorAll('.pk-jcard')];
  // 演练站的 uploads 只带了 27 个文件，精选图原图不在库里（会 404）；这里判卡片是否真的输出封面 <img src>，
  // 图片能否解码单独记在 cardsDecoded 里备查。
  const cardsWithImg = cards.filter((c) => {
    const img = c.querySelector('.pk-jcard-media img');
    return !!(img && (img.getAttribute('src') || '').trim());
  });
  const cardsDecoded = cards.filter((c) => { const img = c.querySelector('.pk-jcard-media img'); return !!(img && img.complete && img.naturalWidth > 0); });

  const r1 = rect(h1), rc = rect(crumb), rw = rect(wrap), rl = rect(logo);
  return {
    url: location.href,
    bodyClass: document.body.className,
    h1: h1 ? h1.textContent.trim().slice(0, 70) : '',
    h1Left: r1 ? Math.round(r1.left) : null,
    crumbLeft: rc ? Math.round(rc.left) : null,
    wrapLeft: rw ? Math.round(rw.left) : null,
    logoLeft: rl ? Math.round(rl.left) : null,
    wrapWidth: rw ? Math.round(rw.width) : null,
    cards: cards.length,
    cardsWithImg: cardsWithImg.length,
    cardsDecoded: cardsDecoded.length,
    cardImgSample: cards.length ? (cards[0].querySelector('.pk-jcard-media img') || {}).currentSrc || '' : '',
    scrollWidth: de.scrollWidth,
    innerWidth: window.innerWidth,
    badCount: bad.length,
    bad: bad.slice(0, 5),
  };
};

const rows = [];
async function probe(path, key, width, height, shot) {
  const page = await ctx.newPage();
  await page.setViewportSize({ width, height });
  let status = 0, m = null, err = '';
  try {
    const resp = await page.goto(`${SITE}${path}?cb=${CB}-${width}`, { waitUntil: 'load', timeout: 60000 });
    status = resp ? resp.status() : 0;
    // 卡片封面是 loading=lazy：先滚到底、再全改 eager、等 decode，量到的才算数
    await page.evaluate(async () => {
      const step = window.innerHeight;
      for (let y = 0; y < document.body.scrollHeight; y += step) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 150)); }
      window.scrollTo(0, 0);
      const imgs = [...document.images];
      imgs.forEach((i) => { i.loading = 'eager'; });
      await Promise.all(imgs.map((i) => (i.complete ? Promise.resolve() : new Promise((r) => { i.onload = r; i.onerror = r; setTimeout(r, 4000); }))));
    }).catch(() => {});
    for (let i = 0; i < 5 && !m; i++) {
      try {
        await page.waitForTimeout(700);
        m = await page.evaluate(measure);
      } catch (e) {
        err = String(e).slice(0, 90);
        await page.waitForLoadState('load', { timeout: 10000 }).catch(() => {});
      }
    }
    if (shot && m) await page.screenshot({ path: `${SHOTS}/${key}-1440.png`, fullPage: true }).catch(() => {});
  } catch (e) {
    status = -1; err = String(e).slice(0, 120);
  }
  if (!m) m = { url: '', bodyClass: '(measure-failed ' + err + ')', h1: '', h1Left: null, crumbLeft: null, wrapLeft: null, logoLeft: null, wrapWidth: null, cards: 0, cardsWithImg: 0, cardsDecoded: 0, cardImgSample: '', scrollWidth: -1, innerWidth: width, badCount: -1, bad: [] };
  await page.close();
  rows.push({ path, key, width, status, ...m });
}

for (const [path, key] of PAGES) {
  for (const [w, h] of WIDTHS) await probe(path, key, w, h, w === 1440);
}
// A. 博客列表
for (const [w, h] of WIDTHS) await probe('/blog/', 'blog', w, h, false);
// C. 不受影响的三页
for (const [path, key] of [['/', 'home'], ['/get-a-quote/', 'quote']]) await probe(path, key, 1440, 900, false);

await browser.close();
proxy.close();

const noOverflow = (r) => r.scrollWidth === r.innerWidth && r.badCount === 0;
const judgePages = rows.filter((r) => PAGES.some(([p]) => p === r.path)).map((r) => ({
  key: r.key, width: r.width, status: r.status,
  overflow: noOverflow(r), sw: r.scrollWidth, iw: r.innerWidth, bad: r.badCount,
  h1Left: r.h1Left, crumbLeft: r.crumbLeft, wrapLeft: r.wrapLeft, logoLeft: r.logoLeft,
  leftOk: r.h1Left !== null && r.h1Left >= 16 && (r.crumbLeft === null || r.crumbLeft >= 16),
  contentLeft: [r.h1Left, r.crumbLeft].filter((v) => v !== null).length ? Math.min(...[r.h1Left, r.crumbLeft].filter((v) => v !== null)) : null,
  align1440: r.width === 1440 ? ([r.h1Left, r.crumbLeft].filter((v) => v !== null).length && r.logoLeft !== null ? Math.abs(Math.min(...[r.h1Left, r.crumbLeft].filter((v) => v !== null)) - r.logoLeft) : null) : null,
}));
const judgeBlog = rows.filter((r) => r.path === '/blog/').map((r) => ({
  width: r.width, status: r.status, overflow: noOverflow(r), sw: r.scrollWidth, iw: r.innerWidth, bad: r.badCount,
  cards: r.cards, cardsWithImg: r.cardsWithImg, cardsDecoded: r.cardsDecoded,
  cardsOk: r.cards === 12 && r.cardsWithImg === 12,
}));
const judgeOthers = rows.filter((r) => ['/', '/products/', '/get-a-quote/'].includes(r.path)).map((r) => ({
  path: r.path, status: r.status, overflow: noOverflow(r), sw: r.scrollWidth, iw: r.innerWidth, bad: r.badCount,
  template: r.path === '/products/' ? r.bodyClass.includes('page-template-template-products-v4') : null,
  h1: r.h1,
}));

const fails = {
  pageOverflow: judgePages.filter((r) => !r.overflow).length,
  pageLeft: judgePages.filter((r) => !r.leftOk).length,
  pageAlign1440: judgePages.filter((r) => r.width === 1440 && (r.align1440 === null || r.align1440 > 4)).length,
  blog: judgeBlog.filter((r) => !r.overflow || !r.cardsOk).length,
  others: judgeOthers.filter((r) => r.status !== 200 || !r.overflow).length,
};
fs.writeFileSync(`${OUT}/rehearsal-29x-browser.json`, JSON.stringify({ cb: CB, time: new Date().toISOString(), site: SITE, proxyPort: PROXY_PORT, upstream: UP, pages: PAGES, widths: WIDTHS, rows, judgePages, judgeBlog, judgeOthers, fails }, null, 1));

console.log('原生页面 20×4 期望 80 条，实测', judgePages.length);
console.log('溢出 FAIL =', fails.pageOverflow, ' 左边距 FAIL =', fails.pageLeft, ' 1440 对齐 FAIL =', fails.pageAlign1440);
console.log('博客 4 档：', JSON.stringify(judgeBlog));
console.log('其它三页：', JSON.stringify(judgeOthers));
console.log('FAIL 合计 =', fails.pageOverflow + fails.pageLeft + fails.pageAlign1440 + fails.blog + fails.others);
