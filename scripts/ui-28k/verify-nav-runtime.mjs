import fs from 'node:fs';import assert from 'node:assert/strict';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
import {LANGS} from '../product-buyers-27g/merge-i18n.mjs';
const root='E:/破甲工作区/packrivo-backup/',out=root+'deploy/verification-28k/';
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'},args:['--disable-background-networking']});
const ctx=await browser.newContext({viewport:{width:1280,height:900}});await ctx.route('**/*',r=>{const u=new URL(r.request().url());return ['localhost','127.0.0.1'].includes(u.hostname)||['data:','blob:'].includes(u.protocol)?r.continue():r.abort();});const p=await ctx.newPage();const result=[];
for(const lang of LANGS){
 const response=await p.goto('http://localhost:8081/'+lang+'/',{waitUntil:'domcontentloaded'});assert.equal(response.status(),200);
 const d=JSON.parse(fs.readFileSync(root+'deploy/i18n-merged/'+lang+'-dict.json','utf8'));
 for(const [i,name] of ['Products','Blog','Resources'].entries()){
  const button=p.locator('[data-pk-toggle]').nth(i),open=d.attr['Open '+name+' menu'],close=d.attr['Close '+name+' menu'];
  assert.equal(await button.getAttribute('aria-label'),open,lang+' initial '+name);
  await button.click();await p.waitForFunction(([index,v])=>document.querySelectorAll('[data-pk-toggle]')[index].getAttribute('aria-label')===v,[i,close],{timeout:3000});
  await button.click();await p.waitForFunction(([index,v])=>document.querySelectorAll('[data-pk-toggle]')[index].getAttribute('aria-label')===v,[i,open],{timeout:3000});
  result.push({lang,menu:name,initial:open,opened:close,reclosed:open});
 }
 console.log(lang,'3 menus open/close PASS');
}
fs.writeFileSync(out+'nav-runtime.json',JSON.stringify(result,null,2)+'\n');console.log('PASS 33 menus / 66 actual click transitions');await browser.close();
