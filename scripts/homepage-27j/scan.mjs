import fs from 'node:fs';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
const out='E:/破甲工作区/packrivo-backup/deploy/verification-27j/';
const langs=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'];
const paths=['/','/products/','/jewelry-boxes/','/blog/','/2026/10/26/jewelry-pouch-box-customer-handoff/','/product-pillow-jewelry-box/','/get-a-quote/'];
const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'},args:['--disable-background-networking']});
const context=await browser.newContext({viewport:{width:1280,height:900}});
// Do not let this read-only local browser reach production or other hosts.
await context.route('**/*',r=>{const u=new URL(r.request().url());return ['localhost','127.0.0.1'].includes(u.hostname)||['data:','blob:'].includes(u.protocol)?r.continue():r.abort();});
const page=await context.newPage();
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
async function collect(){return page.evaluate(()=>{
 const excluded='script,style,noscript,template,.pk-post-title,.pk-journal-excerpt,.pk-featured h2,.pk-featured img,.pk-post-thumb,.pk-post-cover,article.article,.pk-guide li,.pk-featured > div:first-child > p:not(.pk-eyebrow),.pk-skip';
 const norm=s=>s.replace(/\s+/g,' ').trim();
 function visible(el){if(!el||el.closest(excluded))return false;let e=el;while(e){const s=getComputedStyle(e);if(s.display==='none'||s.visibility==='hidden'||s.opacity==='0'||e.hidden||e.getAttribute('aria-hidden')==='true')return false;e=e.parentElement;}return !!el.getClientRects().length;}
 const result=[];const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let n;
 while(n=walker.nextNode()) {let el=n.parentElement;const option=el?.closest('option');if(option)el=option.closest('select');if(!visible(el))continue;const text=norm(n.textContent);if(!/[A-Za-z]/.test(text))continue;const block=['P','DIV','H1','H2','H3','H4','LI','TD','TH','CAPTION','SUMMARY','LABEL','OPTION'].includes(n.parentElement.tagName)&&n.parentElement.children.length===0;result.push({text,kind:block?'block':'text',tag:n.parentElement.tagName,cls:n.parentElement.className||''});}
 for(const el of document.querySelectorAll('[alt],[placeholder],[aria-label]')) {if(!visible(el))continue;for(const attr of ['alt','placeholder','aria-label']){const text=norm(el.getAttribute(attr)||'');if(/[A-Za-z]/.test(text))result.push({text,kind:'attr',attr,tag:el.tagName,cls:el.className||''});}}
 return result;
});}
async function surface(url){
 const response=await page.goto(url,{waitUntil:'domcontentloaded',timeout:45000});
 await page.waitForLoadState('networkidle',{timeout:2000}).catch(()=>{});await sleep(800);
 const status=response.status();if(status!==200)return {status,url:page.url(),nodes:[],title:await page.title()};
 const nodes=[];
 for(const width of [1280,375]){
  await page.setViewportSize({width,height:900});
  // Capture normal and expanded FAQ/navigation states without leaving the page.
  nodes.push(...await collect());
  await page.locator('details').evaluateAll(es=>es.forEach(e=>e.open=true));
  const burger=page.locator('[data-pk-burger]');if(await burger.isVisible().catch(()=>false))await burger.click();
  await sleep(350);nodes.push(...await collect());
  const toggles=page.locator('[data-pk-toggle]');
  for(let i=0;i<await toggles.count();i++){const t=toggles.nth(i);if(await t.isVisible().catch(()=>false)){await t.click();await sleep(250);nodes.push(...await collect());if(await t.getAttribute('aria-expanded')==='true')await t.click();}}
 }
 return {status,url:page.url(),nodes:[...new Map(nodes.map(n=>[n.kind+'|'+n.text,n])).values()]};
}
function relevant(s){
 if(!/[A-Za-z]{2}/.test(s))return false;
 if(/^(?:Packrivo|EN|FAQ|FAQs|USD|MOQ|DDP|FOB|EVA|PE|PU|CMYK|RGB|FSC|PET|B2B|PDF|WA|UV|mm|cm|gsm|pcs|units?|pieces?|kg|g|×|\d[\d\s.,%$€£/–—+x×-]*)$/i.test(s))return false;
 if(/^[\d\s.,%$€£/–—+x×-]+(?:mm|cm|gsm|pcs|kg|g|units?|pieces?)?(?:\s*[x×]\s*\d+\s*(?:mm|cm))?$/i.test(s))return false;
 if(/^(?:[\w.+-]+@[\w.-]+\.[a-z]+|https?:\/\/\S+|www\.\S+)$/i.test(s))return false;
 if(/^(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{4}$/.test(s))return false;
 return true;
}
const native={de:['Blog','Material','Name','Details','Text','Filter','Format','Logo'],es:['Blog','Material','Total','Color','Logo'],fr:['Blog','Contact','Menu','Logo','Structure','Structures','Format','Options'],it:['Blog','Menu','Logo','Materiale'],nl:['Blog','Contact','Menu','Logo','Filter','Material','Details','Format'],pt:['Blog','Material','Total','Logo'],ar:['Blog','Logo'],ja:['Blog','Logo'],ko:['Blog','Logo'],'zh-CN':['Blog','Logo'],'zh-TW':['Blog','Logo']};
const refs={};
for(const p of paths){await page.setViewportSize({width:1280,height:900});refs[p]=await surface('http://localhost:8081'+p);console.log('reference',p,refs[p].status,refs[p].nodes.length);}
fs.writeFileSync(out+'english-reference.json',JSON.stringify(refs,null,2)+'\n');
const inventory={},stats=[],occurrences=[];
for(const lang of langs)for(const p of paths){
 await page.setViewportSize({width:1280,height:900});
 const scan=await surface('http://localhost:8081/'+lang+(p==='/'?'/':p));
 const reference=new Set(refs[p].nodes.filter(n=>relevant(n.text)).map(n=>n.text));
 const found=scan.nodes.filter(n=>reference.has(n.text)&&!native[lang].includes(n.text));
 const unique=[...new Set(found.map(n=>n.text))];
 for(const n of found){const v=inventory[n.text]??={where:[],kind:n.kind,missing:[]};if(!v.where.includes(p))v.where.push(p);if(!v.missing.includes(lang))v.missing.push(lang);if(n.kind==='attr')v.kind='attr';occurrences.push({lang,path:p,...n});}
 stats.push({path:p,lang,status:scan.status,url:scan.url,count:scan.status===200?unique.length:null,title:scan.title});
 console.log(lang,p,scan.status,scan.status===200?unique.length:'UNAVAILABLE');
}
fs.writeFileSync('E:/破甲工作区/packrivo-backup/dsh-in/untranslated-27j.json',JSON.stringify(Object.fromEntries(Object.entries(inventory).sort(([a],[b])=>a.localeCompare(b))),null,2)+'\n');
fs.writeFileSync(out+'scan-statistics.json',JSON.stringify(stats,null,2)+'\n');
fs.writeFileSync(out+'scan-occurrences.json',JSON.stringify(occurrences,null,2)+'\n');
fs.writeFileSync(out+'route-unavailable.json',JSON.stringify(stats.filter(s=>s.status!==200),null,2)+'\n');
fs.writeFileSync(out+'scan-statistics.csv','page,'+langs.join(',')+'\n'+paths.map(p=>JSON.stringify(p)+','+langs.map(l=>{const s=stats.find(s=>s.path===p&&s.lang===l);return s.count??('HTTP '+s.status);}).join(',')).join('\n')+'\n');
console.log('INVENTORY',Object.keys(inventory).length,'VALID',stats.filter(s=>s.status===200).length,'UNAVAILABLE',stats.filter(s=>s.status!==200).length);
await page.goto('http://localhost:8081/',{waitUntil:'networkidle'});
for(const width of [375,768,1280]){await page.setViewportSize({width,height:900});await sleep(400);await page.screenshot({path:out+'home-en-'+width+'.png',fullPage:true});console.log('SCREENSHOT home-en-'+width+'.png');}
await browser.close();
