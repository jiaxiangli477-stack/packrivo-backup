/**
 * 演练：用页面下发的 PKQ 配置 + pkq-quote.js 里的同一套公式，算「前端」价格。
 * 用法：node pkq-front-price.js <pkq.json> <pkq-quote.js> <state.json>
 * 输出一行 JSON：{unit,total,setup,qty,steps:{...}}
 */
const fs = require('fs');

const [cfgPath, jsPath, statePath] = process.argv.slice(2);
if (!cfgPath || !jsPath || !statePath) {
  console.error('用法：node pkq-front-price.js <pkq.json> <pkq-quote.js> <state.json>');
  process.exit(1);
}
const cfg = JSON.parse(fs.readFileSync(cfgPath, 'utf8'));
const state = JSON.parse(fs.readFileSync(statePath, 'utf8'));
const src = fs.readFileSync(jsPath, 'utf8');

const start = src.indexOf('var MATERIALS');
const end = src.indexOf('function money(');
if (start < 0 || end <= start) {
  console.error('FAIL 无法从 pkq-quote.js 切出计算段');
  process.exit(2);
}
const calc = src.slice(start, end);

const body = calc + `
function mergeGroups(cfg) {
  var groups = { materials: MATERIALS, prints: PRINTS, finishes: FINISHES };
  Object.keys(groups).forEach(function (group) {
    var incoming = cfg[group];
    if (!incoming) return;
    Object.keys(incoming).forEach(function (key) {
      var local = groups[group][key];
      if (!local) return;
      if (typeof incoming[key].factor === 'number') local.factor = incoming[key].factor;
      if (typeof incoming[key].add === 'number') local.add = incoming[key].add;
    });
  });
}
// 与页面里的 normaliseConfig 一致：wp_localize 把数字都发成字符串，必须先转回 number，
// 否则 price() 里的 setup += '220' 会变成字符串拼接（这是本次演练第一版抓到的 bug）。
['minQty', 'maxQty', 'setupPrint', 'setupDie', 'rushPercent'].forEach(function (k) {
  cfg[k] = Number(cfg[k]) || 0;
});
if (cfg.tierFloor) cfg.tierFloor = Number(cfg.tierFloor);
mergeGroups(cfg);
var p = price(state, cfg);
var dl = dim(state.length), dw = dim(state.width), dh = dim(state.height);
var area = 2 * (dl * dw + dl * dh + dw * dh);
var d = cfg.sizeRef || [30, 20, 10];
var refA = 2 * (d[0] * d[1] + d[0] * d[2] + d[1] * d[2]);
return JSON.stringify({
  unit: p.unit, total: p.total, setup: p.setup, qty: p.qty,
  steps: {
    style: state.style, styleBase: cfg.styles[state.style].base,
    material: state.material, materialFactor: MATERIALS[state.material].factor,
    print: state.print, printAdd: PRINTS[state.print].add,
    finish: state.finish, finishAdd: FINISHES[state.finish].add,
    dims: [dl, dw, dh], area: area, refArea: refA,
    sizeFactor: sizeFactor(dl, dw, dh, cfg),
    tier: quantityFactor(p.qty, cfg),
    rush: state.rush ? 1 + cfg.rushPercent / 100 : 1
  }
});
`;

try {
  const fn = new Function('cfg', 'state', body);
  console.log(fn(cfg, state));
} catch (e) {
  console.error('FAIL 执行失败: ' + e.message);
  process.exit(3);
}
