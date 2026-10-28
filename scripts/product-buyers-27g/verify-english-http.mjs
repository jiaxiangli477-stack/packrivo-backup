import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const read=f=>JSON.parse(fs.readFileSync(f,'utf8'));
const entries=[1,2,3,4,5].flatMap(i=>read(path.join(root,'dsh-out',`batch-0${i}.json`)));
for(const f of ['products.json','third.json','fourth.json'])for(const r of read(path.join(root,'scripts/product-buyers-27e',f)))entries.push({slug:r.slug,new_title:r.title+' | Packrivo',new_h1:r.title,new_description:r.description});
const decode=s=>s.replace(/&#(x[0-9a-f]+|\d+);/gi,(_,n)=>String.fromCodePoint(n[0].toLowerCase()==='x'?parseInt(n.slice(1),16):parseInt(n,10))).replace(/&(amp|lt|gt|quot|apos|nbsp);/g,(_,n)=>({amp:'&',lt:'<',gt:'>',quot:'"',apos:"'",nbsp:' '})[n]);
const results=new Array(entries.length);let next=0;
await Promise.all(Array.from({length:4},async()=>{
  while(next<entries.length){const i=next++,entry=entries[i];
    try{
      const r=await fetch('http://localhost:8081/'+entry.slug+'/',{redirect:'manual',signal:AbortSignal.timeout(45000)});
      const html=await r.text(),head=(html.match(/<head\b[^>]*>([\s\S]*?)<\/head>/i)??[])[1]??'';
      const titles=[...head.matchAll(/<title\b[^>]*>([\s\S]*?)<\/title>/gi)].map(m=>decode(m[1]));
      const h1=[...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map(m=>decode(m[1].replace(/<[^>]*>/g,'')).trim());
      const description=[...head.matchAll(/<meta\b[^>]*\bname=["']description["'][^>]*>/gi)].map(m=>decode((m[0].match(/\bcontent=(["'])([\s\S]*?)\1/i)??[])[2]??''));
      results[i]={slug:entry.slug,status:r.status,title:titles[0]??'',titleTags:titles,h1,description,match:r.status===200&&titles.length>=1&&titles.every(t=>t===entry.new_title)&&h1.length===1&&h1[0]===entry.new_h1&&description.length===1&&description[0]===entry.new_description};
    }catch(error){results[i]={slug:entry.slug,match:false,error:error.message};}
  }
}));
const titles=results.map(r=>r.title??''),bad=results.filter(r=>!r.match),sourcing=titles.filter(t=>/sourcing/i.test(t)).length,duplicates=titles.length-new Set(titles.map(t=>t.toLowerCase())).size;
fs.mkdirSync(path.join(root,'deploy/verification-27g'),{recursive:true});
fs.writeFileSync(path.join(root,'deploy/verification-27g/english-134.json'),JSON.stringify(results,null,2)+'\n','utf8');
console.log(`English HTTP pages=${results.length} matches=${results.length-bad.length} mismatches=${bad.length} sourcing_titles=${sourcing} duplicate_titles=${duplicates}`);
console.log(`Existing markup finding: pages_with_multiple_head_title_tags=${results.filter(r=>(r.titleTags?.length??0)>1).length}`);
for(const r of bad)console.log(JSON.stringify(r));
if(bad.length||sourcing||duplicates)process.exitCode=1;
