// 29z §3b 判据：首页 1440 与 375 宽各截一张图。
// 真实 Chrome + 本地 HTTP 代理，Host 原样带给 8091 的 nginx。
// 用法: node 29z-home-shots.mjs
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';

const OUT = 'E:/破甲工作区/packrivo-backup/deploy/verification-29z/evidence';
const SHOTS = OUT + '/screens';
fs.mkdirSync(SHOTS, { recursive: true });
const SITE = 'http://nginx';
const UP = { host: '127.0.0.1', port: 8091 };
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
const PROXY_PORT = proxy.address().port;

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

const results = [];
for (const [w, h] of [[1440, 900], [375, 812]]) {
  const page = await ctx.newPage();
  await page.setViewportSize({ width: w, height: h });
  const resp = await page.goto(SITE + '/?cb=' + CB, { waitUntil: 'load', timeout: 60000 });
  await page.waitForSelector('img[src*="hero.webp"]', { timeout: 30000 });
  const info = await page.evaluate(() => {
    const hero = document.querySelector('img[src*="hero.webp"]');
    return {
      title: document.title,
      httpStatus: null,
      heroAlt: hero ? hero.getAttribute('alt') : null,
      heroSrc: hero ? hero.currentSrc : null,
      heroNaturalWidth: hero ? hero.naturalWidth : 0,
      heroRenderedWidth: hero ? hero.getBoundingClientRect().width : 0,
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth: window.innerWidth,
    };
  });
  info.httpStatus = resp ? resp.status() : null;
  const shot = path.join(SHOTS, `home-29z-${w}.png`);
  await page.screenshot({ path: shot });
  info.shot = shot;
  info.overflow = info.scrollWidth > info.innerWidth;
  results.push({ viewport: `${w}x${h}`, ...info });
  console.log(JSON.stringify({ viewport: `${w}x${h}`, ...info }));
  await page.close();
}

const outJson = OUT + '/29z-home-shots.json';
fs.writeFileSync(outJson, JSON.stringify(results, null, 1));
console.log('json ->', outJson);
await browser.close();
proxy.close();
