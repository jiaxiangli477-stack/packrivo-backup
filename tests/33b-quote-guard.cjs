/* Browser-level tests of 33b's opt-in quote guards; all requests are mocked. */
const path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_NODE_PATH || 'E:/破甲工作区/.cache/npm/_npx/9833c18b2d85bc59/node_modules/playwright');
const pricing = require('../packrivo-wp/packrivo-quote/includes/pricing.json');
const script = path.resolve(__dirname, '../packrivo-wp/packrivo-quote/assets/pkq-quote.js');
const cfg = {
  styles:pricing.styles, materials:pricing.materials, prints:pricing.prints, finishes:pricing.finishes,
  tiers:pricing.tiers, rules:pricing.rules, sizeRef:pricing.sizeRef, tierFloor:pricing.rules.tierFloor,
  minQty:250, maxQty:250000, setupPrint:220, setupDie:120, rushPercent:22, feePercent:15, minFee:150,
  endpoint:'http://localhost/never-sent', nonce:'test', wpNonce:'', inquiryUrl:'/contact/', strings:{},
  i18n:{title:'Build your quote',sub:'Choose a specification.',submit:'Request this quote',sending:'Sending',done:'Quote requested',error:'Error'}
};
function check(ok, what) { if (!ok) throw Error('FAIL ' + what); console.log('PASS ' + what); }
(async()=>{
  const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe',args:['--disable-breakpad','--disable-crash-reporter','--disk-cache-dir=E:/破甲工作区/.cache/33b-quote-test']});
  async function setup(attrs) {
    const page=await browser.newPage({viewport:{width:1000,height:900}}), errors=[];
    page.on('pageerror', e=>errors.push(e.message));
    await page.setContent('<!doctype html><html><body><div class="pkq-root" '+attrs+'></div></body></html>');
    await page.evaluate(config=>{window.PKQ=config;window.__request=null;window.fetch=(url,opts)=>{window.__request=JSON.parse(opts.body);return Promise.resolve({ok:true,json:()=>Promise.resolve({ref:'TEST-LOCAL'})});};},cfg);
    await page.addScriptTag({path:script});
    return {page,errors};
  }
  const next=p=>p.locator('.pkq-nav .pkq-btn');
  const fields=p=>p.locator('.pkq-dims input');
  const noPrice=async p=>(await p.locator('.pkq-price, .pkq-tile-price, .pkq-table').count())===0;
  let t=await setup('data-style="rigid" data-qty="250" data-product="product-test" data-length="9.5" data-width="9.5" data-height="4" data-material="rigidboard" data-print="none" data-finish="none"');
  check(await t.page.locator('.pkq-price').count()===1 && await t.page.locator('.pkq-tile-price').count()>0,'legacy calculator immediately prices known input');
  check((await t.page.locator('.pkq-aside').innerText()).includes('250 units'),'legacy quantity unchanged');
  check(t.errors.length===0,'legacy no JavaScript errors'); await t.page.close();

  t=await setup('data-style="rigid" data-qty="250" data-product="product-test" data-require-size="1" data-require-material="1"');
  check(await noPrice(t.page),'missing dimensions and material show no estimated price');
  check(await t.page.locator('.pkq-step').nth(2).isDisabled(),'material step blocked before dimensions');
  await next(t.page).click();
  check((await fields(t.page).count())===3,'dimension fields present');
  check((await fields(t.page).nth(0).inputValue())==='' && (await fields(t.page).nth(1).inputValue())==='' && (await fields(t.page).nth(2).inputValue())==='','unknown dimensions start empty');
  check((await fields(t.page).nth(0).getAttribute('min'))==='2' && (await fields(t.page).nth(0).getAttribute('step'))==='0.1','guarded dimension controls describe valid limits');
  check(await next(t.page).isDisabled(),'cannot continue with missing dimensions');
  await fields(t.page).nth(0).fill('1.9'); await fields(t.page).nth(1).fill('9.5'); await fields(t.page).nth(2).fill('4');
  check(await next(t.page).isDisabled() && await noPrice(t.page),'out-of-range dimensions blocked without a quote');
  await fields(t.page).nth(0).fill('9.55');
  check(await next(t.page).isDisabled(),'nonrepresentable sub-millimetre dimension blocked');
  await fields(t.page).nth(0).fill('9.5');
  check(!(await next(t.page).isDisabled()),'valid explicit dimensions enable next step');
  await next(t.page).click();
  check(await noPrice(t.page) && await next(t.page).isDisabled(),'material is unselected and no estimate is shown');
  await t.page.getByRole('button',{name:'Rigid greyboard',exact:true}).click();
  check(await t.page.locator('.pkq-price').count()===1 && !(await next(t.page).isDisabled()),'choosing material enables estimate and progression');
  await next(t.page).click(); await next(t.page).click();
  await t.page.locator('input[name="name"]').fill('Local Test');
  await t.page.locator('input[name="email"]').fill('local@example.test');
  await t.page.locator('input[name="company"]').fill('Test Company');
  await t.page.locator('.pkq-submit').click();
  const sent=await t.page.evaluate(()=>window.__request);
  check(sent && sent.style==='rigid' && sent.material==='rigidboard' && sent.print==='none' && sent.finish==='none' && sent.quantity===250 && sent.length===9.5 && sent.width===9.5 && sent.height===4,'mocked submission carries explicit inputs');
  check(t.errors.length===0,'guarded flow no JavaScript errors'); await t.page.close();

  t=await setup('data-style="rigid" data-qty="250" data-product="product-test" data-length="5" data-width="5" data-height="3.5" data-require-material="1"');
  check(await noPrice(t.page),'known dimensions with unknown material show no estimate');
  await t.page.getByRole('button',{name:'Rigid box',exact:true}).click();
  check(await noPrice(t.page),'clicking the preselected style does not guess a material');
  await next(t.page).click();
  check((await fields(t.page).nth(0).inputValue())==='5' && (await fields(t.page).nth(2).inputValue())==='3.5','confirmed dimensions are preserved');
  check(!(await next(t.page).isDisabled()),'known dimensions permit material selection'); await t.page.close();

  t=await setup('data-style="rigid" data-qty="250" data-product="product-test" data-length="9.5" data-width="9.5" data-height="4" data-material="constructor" data-require-material="1"');
  check(await noPrice(t.page),'prototype property is not accepted as a material');
  await t.page.close();

  t=await setup('data-style="rigid" data-qty="250" data-product="product-test" data-length="9.5" data-width="9.5" data-height="4" data-material="rigidboard" data-require-size="1" data-require-material="1"');
  check(await t.page.locator('.pkq-price').count()===1,'both known inputs remain immediately quotable');
  await next(t.page).click();
  await fields(t.page).nth(0).fill('');
  check(await noPrice(t.page) && await next(t.page).isDisabled() && await t.page.getByRole('status').isVisible(),'clearing a known dimension hides price and explains the block');
  await fields(t.page).nth(0).fill('9.5');
  await t.page.getByRole('button',{name:/01 Style/}).click();
  await t.page.getByRole('button',{name:/Mailer box/}).click();
  check(await noPrice(t.page),'changing style invalidates previous specific inputs');
  await next(t.page).click();
  check((await fields(t.page).nth(0).inputValue())==='','changed style does not silently supply dimensions');
  await t.page.close(); await browser.close();
})().catch(error=>{console.error(error.stack||error);process.exit(1)});
