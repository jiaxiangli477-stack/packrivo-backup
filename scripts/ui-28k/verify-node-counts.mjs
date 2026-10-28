import fs from 'node:fs';import path from 'node:path';import crypto from 'node:crypto';import assert from 'node:assert/strict';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
import {LANGS} from '../product-buyers-27g/merge-i18n.mjs';
const root='E:/破甲工作区/packrivo-backup/',tmp='E:/破甲工作区/.tmp/packrivo-28k/',dest=root+'deploy/verification-28k/',merged=root+'deploy/i18n-merged/';
const base=tmp+'baseline/i18n-merged/',post=tmp+'post-merge/';fs.mkdirSync(post,{recursive:true});
const files=LANGS.flatMap(l=>[l+'-dict.json',l+'-js.json']);for(const f of files)fs.copyFileSync(merged+f,post+f);
const hash=f=>crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');const saved=Object.fromEntries(files.map(f=>[f,hash(post+f)]));
const refs=JSON.parse(fs.readFileSync(root+'deploy/verification-27j/english-reference.json','utf8'));
const approved=JSON.parse(fs.readFileSync(root+'dsh-out/untranslated-27j.json','utf8'));
const titles=new Set(JSON.parse(fs.readFileSync(root+'deploy/verification-27j/excluded-article-titles.json','utf8')));
const paths=['/','/products/','/jewelry-boxes/','/blog/','/2026/10/26/jewelry-pouch-box-customer-handoff/','/product-pillow-jewelry-box/'];
const native={de:['Blog','Material','Name','Details','Text','Filter','Format','Logo'],es:['Blog','Material','Total','Color','Logo'],fr:['Blog','Contact','Menu','Logo','Structure','Structures','Format','Options'],it:['Blog','Menu','Logo','Materiale'],nl:['Blog','Contact','Menu','Logo','Filter','Material','Details','Format'],pt:['Blog','Material','Total','Logo'],ar:['Blog','Logo'],ja:['Blog','Logo'],ko:['Blog','Logo'],'zh-CN':['Blog','Logo'],'zh-TW':['Blog','Logo']};
function eligible(s,lang){if(!/[A-Za-z]{2}/.test(s)||titles.has(s)||native[lang].includes(s)||approved[s]?.[lang]===s)return false;
 if(/^(?:Packrivo|EN|FAQ|FAQs|USD|MOQ|DDP|FOB|EVA|PE|PU|CMYK|RGB|FSC|PET|B2B|PDF|WA|UV|mm|cm|gsm|pcs|units?|pieces?|kg|g|×|\d[\d\s.,%$€£/–—+x×-]*)$/i.test(s))return false;
 if(/^(?:from\s+)?[$€£]\s*[\d,.]+\s*\/\s*unit$/i.test(s)||/^at\s+\d[\d,]*\s+pcs\s*·\s*[\d.,×x\s]+\s*cm$/i.test(s)||/^\d[\d,]*\s*\/\s*design$/i.test(s)||/^\d+(?:[–-]\d+)?\s+days$/i.test(s)||/^per unit at [\d,]+ pieces$/i.test(s)||/^WhatsApp:\s*[+\d\s-]+$/.test(s))return false;
 if(/^(?:[\w.+-]+@[\w.-]+\.[a-z]+|https?:\/\/\S+|www\.\S+)$/i.test(s))return false;
 if(/^(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},?\s+\d{4}$/.test(s))return false;
 return !(lang==='fr'&&s==='Total');}
let browser,ctx;
async function run(label){
 const jobs=LANGS.flatMap(lang=>paths.map(p=>({lang,p})));const rows=[];let cursor=0;
 async function worker(){const page=await ctx.newPage();try{while(cursor<jobs.length){const {lang,p}=jobs[cursor++];let response=await page.goto('http://localhost:8081/'+lang+(p==='/'?'/':p),{waitUntil:'domcontentloaded',timeout:45000});let status=response.status(),fallback=null;
  if(status!==200&&p.includes('/2026/10/26/')){response=await page.goto('http://localhost:8081/'+lang+'/?p=2937',{waitUntil:'domcontentloaded'});fallback={status:response.status(),url:page.url()};assert.equal(fallback.status,200);assert((await page.locator('body').getAttribute('class')).split(/\s+/).includes('postid-2937'));}
  assert.equal(response.status(),200,lang+' '+p);await page.waitForTimeout(700);
  const nodes=await page.evaluate(()=>{const exclude='script,style,noscript,template,.pk-post-title,.pk-journal-excerpt,.pk-featured h2,.pk-featured img,.pk-post-thumb,.pk-post-cover,article.article,.pk-guide li,.pk-featured > div:first-child > p:not(.pk-eyebrow),.pk-skip';let w=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT),n,out=[];while(n=w.nextNode()){const el=n.parentElement;if(!el||el.closest(exclude))continue;let x=el,ok=true;while(x){const s=getComputedStyle(x);if(s.display==='none'||s.visibility==='hidden'||s.opacity==='0'||x.hidden||x.getAttribute('aria-hidden')==='true'){ok=false;break;}x=x.parentElement;}if(!ok||!el.getClientRects().length)continue;const text=n.textContent.replace(/\s+/g,' ').trim();if(text)out.push({text,element:el.tagName,classes:typeof el.className==='string'?el.className:''});}return out;});
  const reference=new Set(refs[p].nodes.filter(n=>n.kind!=='attr').map(n=>n.text));const match=nodes.filter(n=>reference.has(n.text)&&eligible(n.text,lang));rows.push({lang,path:p,status,fallback,count:match.length,nodes:match});console.log(label,lang,p,match.length,'canonical',status,fallback?'query200':'');}}
 finally{await page.close();}}
 await Promise.all(Array.from({length:4},()=>worker()));return rows;
}
let before,after;
try{
 browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'},args:['--disable-background-networking']});ctx=await browser.newContext({viewport:{width:1280,height:900}});
 await ctx.route('**/*',r=>{const u=new URL(r.request().url());return ['localhost','127.0.0.1'].includes(u.hostname)||['data:','blob:'].includes(u.protocol)?r.continue():r.abort();});
 for(const f of files)fs.copyFileSync(base+f,merged+f);
 before=await run('before');
 for(const f of files)fs.copyFileSync(post+f,merged+f);
 after=await run('after');
}finally{for(const f of files)fs.copyFileSync(post+f,merged+f);if(browser)await browser.close();}
for(const f of files)assert.equal(hash(merged+f),saved[f],f+' restoration');
const rows=paths.flatMap(p=>LANGS.map(lang=>{const b=before.find(r=>r.path===p&&r.lang===lang),a=after.find(r=>r.path===p&&r.lang===lang);return {path:p,lang,before:b.count,after:a.count,status:a.status,fallback:a.fallback,remaining:a.nodes};}));
const csv='page,'+LANGS.join(',')+'\n'+paths.map(p=>JSON.stringify(p)+','+LANGS.map(lang=>{const x=rows.find(r=>r.path===p&&r.lang===lang);return x.before+'→'+x.after;}).join(',')).join('\n')+'\n';
fs.writeFileSync(dest+'node-counts.json',JSON.stringify(rows,null,2)+'\n');fs.writeFileSync(dest+'node-counts.csv',csv);console.log(csv.trim());console.log('PASS: 66 page/language node counts before and after; 22 mounted files restored and SHA-256 verified');
