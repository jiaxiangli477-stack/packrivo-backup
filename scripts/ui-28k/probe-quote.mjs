import fs from 'node:fs';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
const out='E:/破甲工作区/packrivo-backup/deploy/verification-28k/';
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'},args:['--disable-background-networking']});
const context=await browser.newContext({viewport:{width:1280,height:900}});await context.route('**/*',r=>{const u=new URL(r.request().url());return ['localhost','127.0.0.1'].includes(u.hostname)||['data:','blob:'].includes(u.protocol)?r.continue():r.abort();});
const page=await context.newPage(),response=await page.goto('http://localhost:8081/get-a-quote/',{waitUntil:'domcontentloaded'});if(response.status()!==200)throw Error('Quote page unavailable');await page.waitForSelector('.pkq-shell');
const collected=new Set();
async function read(){for(const t of await page.evaluate(()=>{let items=[];let w=document.createTreeWalker(document.querySelector('.pkq-shell'),NodeFilter.SHOW_TEXT),n;while(n=w.nextNode()){let e=n.parentElement;const s=getComputedStyle(e);if(!e.getClientRects().length||s.display==='none'||s.visibility==='hidden'||e.closest('[aria-hidden=true]'))continue;let v=n.textContent.replace(/\s+/g,' ').trim();if(v&&/[A-Za-z]{2}/.test(v)&&!/^[$\d.,\s×+%/-]+$/.test(v))items.push(v);}for(const e of document.querySelectorAll('.pkq-shell [placeholder],.pkq-shell [aria-label],.pkq-shell [alt]')){if(e.getClientRects().length)for(const a of ['placeholder','aria-label','alt']){const v=e.getAttribute(a);if(v&&/[A-Za-z]{2}/.test(v))items.push(v)}}return items;}))collected.add(t);}
for(let step=0;step<5;step++){
 await page.locator('.pkq-step').nth(step).click();await page.waitForTimeout(100);await read();
 if(step===2){const count=await page.locator('.pkq-main .pkq-options').first().locator('.pkq-chip').count();for(let i=0;i<count;i++){await page.locator('.pkq-main .pkq-options').first().locator('.pkq-chip').nth(i).click();await page.waitForTimeout(60);await read();}}
}
const sorted=[...collected].sort(),result={url:page.url(),status:response.status(),steps:5,materialChoicesExplored:true,uniqueVisibleEnglishStrings:sorted.length,strings:sorted,limitations:'Visible five-step UI and material notes only; submission success/error and unseen server responses are not executed or counted. Dynamic numbers create variants.'};
fs.writeFileSync(out+'quote-visible-strings.json',JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify({status:result.status,steps:result.steps,uniqueVisibleEnglishStrings:sorted.length,submission:false}));
await browser.close();
