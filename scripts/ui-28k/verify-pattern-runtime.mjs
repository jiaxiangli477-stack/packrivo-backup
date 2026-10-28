import fs from 'node:fs';import assert from 'node:assert/strict';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
import {LANGS} from '../product-buyers-27g/merge-i18n.mjs';
const root='E:/破甲工作区/packrivo-backup/',out=root+'deploy/verification-28k/';
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'},args:['--disable-background-networking']});
const ctx=await browser.newContext();await ctx.route('**/*',r=>{const u=new URL(r.request().url());return ['localhost','127.0.0.1'].includes(u.hostname)||['data:','blob:'].includes(u.protocol)?r.continue():r.abort();});const p=await ctx.newPage();
const cats=['Materials & print','Samples & approval','Structures & fit','Supplier briefs'],months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];const output=[];
for(const lang of LANGS){
 const response=await p.goto('http://localhost:8081/'+lang+'/',{waitUntil:'domcontentloaded'});assert.equal(response.status(),200);assert.equal(await p.locator('#packrivo-i18n-runtime').count(),1);
 const js=JSON.parse(fs.readFileSync(root+'deploy/i18n-merged/'+lang+'-js.json','utf8'));const inputs=[];
 for(const cat of cats)for(const mon of months)for(const min of [null,11])inputs.push(cat+' · '+(min===null?'':min+' min · ')+mon+' 25, 2028');
 const expected=inputs.map(s=>{const row=js.patterns.find(([re])=>new RegExp(re).test(s));assert(row,lang+'/'+s);return s.replace(new RegExp(row[0]),row[1]);});
 await p.evaluate(values=>{const wrap=document.createElement('div');wrap.id='pk28k-runtime-test';for(const value of values){const n=document.createElement('p');n.textContent=value;wrap.appendChild(n);}document.body.appendChild(wrap);},inputs);
 await p.waitForFunction(([expected])=>{const a=[...document.querySelectorAll('#pk28k-runtime-test p')].map(n=>n.textContent);return a.length===expected.length&&a.every((v,i)=>v===expected[i]);},[expected],{timeout:7000});
 output.push({lang,cases:inputs.length,example:await p.locator('#pk28k-runtime-test p').first().textContent()});console.log(lang,inputs.length,'PASS');
}
fs.writeFileSync(out+'runtime-pattern-browser.json',JSON.stringify(output,null,2)+'\n');console.log('PASS runtime_injected_patterns=1056');await browser.close();
