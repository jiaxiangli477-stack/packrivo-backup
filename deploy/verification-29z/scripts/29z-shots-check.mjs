// 29z 判据 4f（本机判定版）：/products/ 的改前 + 改后 x 1440/375 四张截图。
//
// 用法：node 29z-shots-check.mjs <shots-dir>
//
// 判据（四条同时成立才算 PASS 4f）：
//   1) before-1440 / before-375 / after-1440 / after-375 四张 png 都在且非空；
//   2) 四个视口 status 都是 200；
//   3) 卡片数与改前一致（after 的两档都等于 before-1440 的值，且该值 > 0）；
//   4) 四张都 overflow=false，改后两档渲染里 ¥ 计数 0。
//
// 退出码：0 = PASS，1 = FAIL（逐条打印原因），2 = 用法错误。
import fs from 'node:fs';
import path from 'node:path';

const DIR = process.argv[2];
if (!DIR) { console.error('用法: node 29z-shots-check.mjs <shots-dir>'); process.exit(2); }

const WANT = ['before-1440', 'before-375', 'after-1440', 'after-375'];
const errs = [];
const loaded = {};

for (const name of WANT) {
  const png = path.join(DIR, `${name}.png`);
  if (!fs.existsSync(png) || fs.statSync(png).size === 0) {
    errs.push(`${name}.png 缺失或为空（${png}）`);
    continue;
  }
  const w = name.endsWith('1440') ? 1440 : 375;
  const meta = path.join(DIR, `shots-${name.startsWith('before') ? 'before' : 'after'}.json`);
  if (!fs.existsSync(meta)) { errs.push(`${name}: 缺 ${path.basename(meta)}，不知道卡片数/溢出/¥ 计数`); continue; }
  const j = JSON.parse(fs.readFileSync(meta, 'utf8'));
  const s = (j.shot || []).find((x) => x.width === w);
  if (!s) { errs.push(`${name}: ${path.basename(meta)} 里没有 ${w} 宽的记录`); continue; }
  loaded[name] = s;
}

for (const [name, s] of Object.entries(loaded)) {
  if (s.status !== 200) errs.push(`${name}: status=${s.status}（期望 200）`);
  if (s.overflow) errs.push(`${name}: 横向溢出（scrollWidth > clientWidth）`);
}
const beforeCards = loaded['before-1440'] ? loaded['before-1440'].cards : null;
if (beforeCards === null || beforeCards <= 0) {
  if (beforeCards !== null) errs.push(`before-1440: 卡片数 ${beforeCards}（期望 > 0）`);
} else {
  for (const name of ['before-375', 'after-1440', 'after-375']) {
    if (loaded[name] && loaded[name].cards !== beforeCards) {
      errs.push(`${name}: 卡片数 ${loaded[name].cards} ≠ 改前 ${beforeCards}`);
    }
  }
}
for (const name of ['after-1440', 'after-375']) {
  if (loaded[name] && loaded[name].yen_in_dom !== 0) errs.push(`${name}: 渲染里 ¥ 有 ${loaded[name].yen_in_dom} 处（期望 0）`);
}

const lines = [];
for (const name of WANT) {
  const s = loaded[name];
  if (!s) { lines.push(`    ${name}: 缺`); continue; }
  lines.push(`    ${name}: status=${s.status} cards=${s.cards} overflow=${s.overflow} ¥=${s.yen_in_dom} ${s.bytes} B`);
}

if (errs.length === 0) {
  console.log('== 判据 4f（本机）==');
  lines.forEach((l) => console.log(l));
  console.log(`  PASS  4f 改前改后 x 1440/375 四张截图（4/4 齐，卡片 ${beforeCards} = ${beforeCards}，无溢出，改后 ¥ 0）`);
  process.exit(0);
}

console.log('== 判据 4f（本机）==');
lines.forEach((l) => console.log(l));
for (const e of errs) console.log(`  REASON  ${e}`);
console.log(`  FAIL  4f 改前改后 x 1440/375 四张截图（${errs.length} 条问题）`);
process.exit(1);
