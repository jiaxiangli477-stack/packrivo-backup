import fs from 'node:fs';import assert from 'node:assert/strict';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
const tag=process.argv[2]||'unknown',dir='E:/破甲工作区/packrivo-backup/deploy/verification-28l/';
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'}});const ctx=await browser.newContext({viewport:{width:375,height:900}});
await ctx.route('**/*',r=>{const u=new URL(r.request().url());return ['GET','HEAD'].includes(r.request().method())&&['localhost','127.0.0.1'].includes(u.hostname)?r.continue():r.abort()});const page=await ctx.newPage();
let res=await page.goto('http://localhost:8081/get-a-quote/?style=rigid&qty=500',{waitUntil:'domcontentloaded'});assert.equal(res.status(),200);await page.waitForSelector('.pkq-shell');await page.waitForTimeout(700);
const results=[];for(let step=0;step<5;step++){
 await page.locator('.pkq-step').nth(step).click();await page.waitForTimeout(100);
 const v=await page.evaluate(()=>{
  const card=document.querySelector('.pkq-card'),main=document.querySelector('.pkq-main'),aside=document.querySelector('.pkq-aside'),nav=document.querySelector('.pkq-nav');
  const parse=s=>{const m=s.match(/[\d.]+/g).map(Number);return [m[0],m[1],m[2],m.length>3?m[3]:1]};
  const bg=parse(getComputedStyle(card).backgroundColor),lum=rgb=>rgb.slice(0,3).map(x=>x/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);
  let rows=[];for(const e of card.querySelectorAll('p,dt,dd')){const r=e.getBoundingClientRect();if(!r.width||!r.height)continue;const style=getComputedStyle(e),fg=parse(style.color),alpha=fg[3],blend=fg.slice(0,3).map((x,i)=>x*alpha+bg[i]*(1-alpha)),a=lum(blend),b=lum(bg),ratio=(Math.max(a,b)+.05)/(Math.min(a,b)+.05),size=parseFloat(style.fontSize),bold=parseInt(style.fontWeight)>=700;rows.push({tag:e.tagName,class:e.className,text:e.innerText.trim(),color:style.color,background:getComputedStyle(card).backgroundColor,fontSize:size,bold,ratio:+ratio.toFixed(3),threshold:size>=24||(bold&&size>=18.6667)?3:4.5});}
  return {mainTop:Math.round(main.getBoundingClientRect().top),navBottom:Math.round(nav.getBoundingClientRect().bottom),asideTop:Math.round(aside.getBoundingClientRect().top),asideText:aside.innerText,rows};
 });results.push({step:step+1,...v});
}
await page.locator('.pkq-step').first().click();await page.screenshot({path:dir+'quote-'+tag+'-375.png',fullPage:true});await browser.close();
fs.writeFileSync(dir+'quote-ui-'+tag+'.json',JSON.stringify(results,null,2)+'\n');
const failures=[];for(const v of results){if(v.navBottom>v.asideTop)failures.push(`step${v.step}: choices/continue bottom=${v.navBottom} exceeds summary top=${v.asideTop}`);for(const x of v.rows)if(x.ratio+1e-6<x.threshold)failures.push(`step${v.step}: ${x.tag}.${x.class} ${x.ratio}:1 < ${x.threshold}:1 text=${x.text}`);}
for(const v of results)console.log(`step=${v.step} main_top=${v.mainTop} continue_bottom=${v.navBottom} summary_top=${v.asideTop} min_contrast=${Math.min(...v.rows.map(x=>x.ratio)).toFixed(3)}`);
console.log('step1 card measurements:',JSON.stringify(results[0].rows));console.log('failures='+failures.length);for(const f of failures)console.log('FAIL '+f);if(failures.length)process.exitCode=1;else console.log('PASS mobile order and contrast, five steps');
