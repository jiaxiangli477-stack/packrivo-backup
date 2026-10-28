import fs from 'node:fs';
import { chromium } from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
const out='E:/破甲工作区/packrivo-backup/deploy/verification-28k/';
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
const previous='E:/破甲工作区/packrivo-backup/deploy/verification-27j/';
const refs=JSON.parse(fs.readFileSync(previous+'english-reference.json','utf8'));
const before=JSON.parse(fs.readFileSync(previous+'scan-occurrences.json','utf8'));
const keptPaths=paths.filter(p=>p!=='/get-a-quote/');
const titles=new Set(JSON.parse(fs.readFileSync(previous+'excluded-article-titles.json','utf8')));
function eligible(n,lang){const s=n.text;if(n.kind==='attr'||!relevant(s)||titles.has(s)||native[lang].includes(s))return false;
 if(/^(?:from\s+)?[$€£]\s*[\d,.]+\s*\/\s*unit$/i.test(s)||/^at\s+\d[\d,]*\s+pcs\s*·\s*[\d.,×x\s]+\s*cm$/i.test(s)||/^\d[\d,]*\s*\/\s*design$/i.test(s)||/^\d+(?:[–-]\d+)?\s+days$/i.test(s)||/^per unit at [\d,]+ pieces$/i.test(s)||/^WhatsApp:\s*[+\d\s-]+$/.test(s))return false;
 if(lang==='fr'&&s==='Total')return false;return true;}
const results=[],residual=[];
for(const lang of langs)for(const p of keptPaths){
 await page.setViewportSize({width:1280,height:900});const direct='http://localhost:8081/'+lang+(p==='/'?'/':p);
 let scan=await surface(direct),fallback=null;
 if(scan.status!==200&&p.includes('/2026/10/26/')){fallback=await surface('http://localhost:8081/'+lang+'/?p=2937');if(fallback.status!==200||!(await page.locator('body').getAttribute('class')).split(/\s+/).includes('postid-2937'))throw Error('Article query did not return expected post');}
 const observed=fallback??scan;
 if(observed.status!==200){results.push({lang,path:p,before:null,after:null,status:scan.status});console.log(lang,p,'HTTP',scan.status);continue;}
 const refSet=new Set(refs[p].nodes.filter(n=>eligible(n,lang)).map(n=>n.text));
 const found=observed.nodes.filter(n=>eligible(n,lang)&&refSet.has(n.text));
 const unique=[...new Set(found.map(n=>n.text))];
 residual.push(...unique.map(text=>({lang,path:p,text})));
 const original=new Set(before.filter(n=>n.lang===lang&&n.path===p&&eligible(n,lang)).map(n=>n.text));
 results.push({lang,path:p,before:original.size,after:unique.length,status:scan.status,fallback:fallback?{status:fallback.status,url:fallback.url}:null});
 console.log(lang,p,original.size,'->',unique.length,'canonical',scan.status,fallback?'query200':'');
 fs.writeFileSync(out+'progress.json',JSON.stringify(results,null,2)+'\n');
}
const csv='page,'+langs.join(',')+'\n'+keptPaths.map(p=>JSON.stringify(p)+','+langs.map(l=>{const r=results.find(x=>x.path===p&&x.lang===l);return r.before+'→'+(r.after??'HTTP '+r.status)}).join(',')).join('\n')+'\n';
fs.writeFileSync(out+'browser-results.json',JSON.stringify(results,null,2)+'\n');fs.writeFileSync(out+'remaining-text.json',JSON.stringify(residual,null,2)+'\n');fs.writeFileSync(out+'before-after.csv',csv);
console.log(csv.trim());console.log('PAGE_LANGUAGE_PAIRS',results.length,'CANONICAL_404_FALLBACK',results.filter(x=>x.fallback).length,'RESIDUAL_OCCURRENCES',residual.length);
for(const lang of ['ar','zh-CN']){await page.setViewportSize({width:375,height:900});await page.goto('http://localhost:8081/'+lang+'/',{waitUntil:'domcontentloaded'});await sleep(1000);await page.screenshot({path:out+'home-'+lang+'-375.png',fullPage:true});console.log('SCREENSHOT home-'+lang+'-375.png');}
await browser.close();
