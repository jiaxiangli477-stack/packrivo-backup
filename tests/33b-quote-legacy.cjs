/* Compare all existing quote-widget DOM states against the 33a parent script. */
const path=require('path'), assert=require('assert'), {execFileSync}=require('child_process');
const {chromium}=require(process.env.PLAYWRIGHT_NODE_PATH || 'E:/破甲工作区/.cache/npm/_npx/9833c18b2d85bc59/node_modules/playwright');
const root=path.resolve(__dirname,'..');
const before=execFileSync('git',['show','503b9c5:packrivo-wp/packrivo-quote/assets/pkq-quote.js'],{cwd:root,encoding:'utf8'});
const after=require('fs').readFileSync(path.join(root,'packrivo-wp/packrivo-quote/assets/pkq-quote.js'),'utf8');
const pricing=require('../packrivo-wp/packrivo-quote/includes/pricing.json');
const cfg={styles:pricing.styles,materials:pricing.materials,prints:pricing.prints,finishes:pricing.finishes,tiers:pricing.tiers,rules:pricing.rules,sizeRef:pricing.sizeRef,tierFloor:pricing.rules.tierFloor,minQty:250,maxQty:250000,setupPrint:220,setupDie:120,rushPercent:22,feePercent:15,minFee:150,endpoint:'http://localhost/unused',nonce:'test',wpNonce:'',inquiryUrl:'/contact/',strings:{},i18n:{title:'Build your quote',sub:'Choose a specification.',submit:'Request this quote',sending:'Sending',done:'Quote requested',error:'Error'}};
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe',args:['--disable-breakpad','--disable-crash-reporter','--disk-cache-dir=E:/破甲工作区/.cache/33b-quote-legacy']});
 async function snapshots(source){
  const p=await browser.newPage(),errors=[];p.on('pageerror',e=>errors.push(e.message));
  await p.setContent('<div class="pkq-root" data-style="rigid" data-qty="250" data-product="product-test" data-length="9.5" data-width="9.5" data-height="4" data-material="rigidboard" data-print="none" data-finish="none"></div>');
  await p.evaluate(c=>{window.PKQ=c},cfg);await p.addScriptTag({content:source});
  const snap=()=>p.locator('.pkq-root').evaluate(e=>e.outerHTML),next=()=>p.locator('.pkq-nav .pkq-btn');
  let out=[await snap()];
  await next().click();out.push(await snap());
  await p.locator('.pkq-dims input').first().fill('10.5');out.push(await snap());
  await next().click();out.push(await snap());
  await p.getByRole('button',{name:'Foil stamp',exact:true}).click();out.push(await snap());
  await next().click();out.push(await snap());
  await p.locator('input[aria-label="Quantity"]').fill('120');out.push(await snap());
  await p.locator('input[aria-label="Quantity"]').fill('250');out.push(await snap());
  await next().click();out.push(await snap());
  if(errors.length)throw Error('Page errors: '+errors.join('; '));
  await p.close();return out;
 }
 const a=await snapshots(before),b=await snapshots(after);await browser.close();
 assert.deepStrictEqual(b,a);console.log('LEGACY_QUOTE_DOM_STATES',a.length,'DIFFERENCES 0');
})().catch(e=>{console.error(e.stack||e);process.exit(1)});
