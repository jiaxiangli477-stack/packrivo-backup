import fs from 'node:fs';import {chromium} from 'file:///E:/破甲工作区/packrivo-v2/node_modules/playwright-core/index.mjs';
const base='http://localhost:8081',phase=process.argv[2]||'before',out='E:/破甲工作区/packrivo-backup/deploy/verification-28l/';
const b=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,env:{...process.env,TEMP:'E:/破甲工作区/.tmp',TMP:'E:/破甲工作区/.tmp'}}),c=await b.newContext({viewport:{width:375,height:900}});
await c.route('**/*',r=>{const u=new URL(r.request().url());return ['GET','HEAD'].includes(r.request().method())&&['localhost','127.0.0.1'].includes(u.hostname)?r.continue():r.abort()});const p=await c.newPage(),rows=[];
for(const lang of ['en','ar','de'])for(const route of ['/','/product-pillow-jewelry-box/','/products/']){
 const url=base+(lang==='en'?'':'/'+lang)+route;const resp=await p.goto(url,{waitUntil:'domcontentloaded',timeout:45000});await p.waitForTimeout(1000);
 const links=await p.locator('a[href]').evaluateAll(es=>es.map(e=>({text:e.textContent.replace(/\s+/g,' ').trim(),href:e.href,selector:e.className,location:e.closest('header')?'header':e.closest('.pk-product-card')?'product-card':e.closest('.pr-sticky')?'sticky':e.closest('footer')?'footer':'page'})).filter(x=>/\/(?:get-a-quote|inquiry)\/?(?:\?|$)/.test(x.href)));
 for(const link of links){const u=new URL(link.href);if(u.hostname!=='localhost'){link.http='external';continue}try{let cur=link.href,chain=[];for(let n=0;n<6;n++){let r=await fetch(cur,{redirect:'manual',signal:AbortSignal.timeout(8000)});chain.push({status:r.status,url:cur});if(r.status>=300&&r.status<400&&r.headers.get('location')){const next=new URL(r.headers.get('location'),cur);if(next.hostname!=='localhost')break;cur=next.href;}else break;}link.http=chain.at(-1).status;link.chain=chain;}catch(e){link.http=String(e)}}
 rows.push({lang,route,pageStatus:resp.status(),links});console.log(phase,lang,route,'page',resp.status(),'links',links.length,'non200',links.filter(x=>x.http!==200).length);
}
fs.writeFileSync(out+'cta-'+phase+'.json',JSON.stringify(rows,null,2)+'\n');await b.close();
