const fs = require('fs');
const vm = require('vm');
const path = require('path');
const cp = require('child_process');
const root = path.resolve(__dirname, '..');
const file = path.join(root, 'packrivo-wp/packrivo-quote/assets/pkq-quote.js');
let js = fs.readFileSync(file, 'utf8');
js = js.replace('  function boot() {', '  globalThis.priceUnderTest = price;\n  function boot() {');
const context = { document: {readyState:'loading', addEventListener() {}}, console };
vm.createContext(context);vm.runInContext(js,context);
const php = cp.execFileSync('docker', ['run','--rm','-v',`${root}:/work:ro`,'php:8.2-cli','php','/work/packrivo-wp/packrivo-quote/demo/parity.php'], {encoding:'utf8'});
const rows = JSON.parse(php);
const cfg = {styles:{rigid:{base:3.4}},minQty:250,maxQty:250000,setupPrint:220,setupDie:120,rushPercent:22,feePercent:15,minFee:150};
let failures=0;
for (const row of rows) {
  const input = {...row.input, qty:row.input.quantity};
  const jsRow=context.priceUnderTest(input,cfg);
  const ok = row.unit===jsRow.unit && row.fee===jsRow.fee;
  console.log(`qty=${input.qty} PHP unit=${row.unit.toFixed(2)} fee=${row.fee.toFixed(2)} JS unit=${jsRow.unit.toFixed(2)} fee=${jsRow.fee.toFixed(2)} ${ok?'PASS':'FAIL'}`);
  if(!ok) failures++;
}
if(failures) process.exit(1);
