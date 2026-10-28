import fs from 'node:fs';
import assert from 'node:assert/strict';
const root='E:/破甲工作区/packrivo-backup/';
const dir=root+'deploy/verification-27j/';
const original=JSON.parse(fs.readFileSync(dir+'scan-occurrences.json','utf8'));
const stats=JSON.parse(fs.readFileSync(dir+'scan-statistics.json','utf8'));
const titles=new Set(JSON.parse(fs.readFileSync(dir+'excluded-article-titles.json','utf8')));
// The task excludes numeric prices, unit values and article content. Do not
// export fixed-price/size variants as translation keys or change their data.
function excluded(n){
 if(titles.has(n.text))return 'article title';
 if(/^(?:from\s+)?[$€£]\s*[\d,.]+\s*\/\s*unit$/i.test(n.text))return 'price and unit';
 if(/^at\s+\d[\d,]*\s+pcs\s*·\s*[\d.,×x\s]+\s*cm$/i.test(n.text))return 'quantity and dimensions';
 if(/^\d[\d,]*\s*\/\s*design$/i.test(n.text))return 'quantity unit';
 if(/^\d+(?:[–-]\d+)?\s+days$/i.test(n.text))return 'duration unit';
 if(/^per unit at [\d,]+ pieces$/i.test(n.text))return 'price quantity unit';
 if(/^WhatsApp:\s*[+\d\s-]+$/.test(n.text))return 'contact brand and phone number';
 if(n.lang==='fr'&&n.text==='Total')return 'native French homograph';
 return null;
}
const removed=original.filter(n=>excluded(n)).map(n=>({...n,reason:excluded(n)}));
const final=original.filter(n=>!excluded(n));
const inventory={};
for(const n of final){const v=inventory[n.text]??={where:[],kind:n.kind,missing:[]};if(!v.where.includes(n.path))v.where.push(n.path);if(!v.missing.includes(n.lang))v.missing.push(n.lang);if(n.kind==='attr')v.kind='attr';}
const langs=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'];
for(const s of stats){assert(s.status===200||s.fallback?.status===200);s.count=new Set(final.filter(n=>n.path===s.path&&n.lang===s.lang).map(n=>n.text)).size;}
for(const [key,v] of Object.entries(inventory)){assert(!titles.has(key));assert(['block','text','attr'].includes(v.kind));assert(v.where.length&&v.missing.length);assert(v.missing.every(l=>langs.includes(l)));for(const old of ['Kraft E-flute','White E-flute','Greyboard 2 mm','dielines included'])assert(!key.includes(old));}
assert.equal(stats.length,77);assert.equal(stats.filter(s=>s.status===200).length,55);assert.equal(stats.filter(s=>s.fallback?.status===200).length,22);
fs.writeFileSync(root+'dsh-in/untranslated-27j.json',JSON.stringify(Object.fromEntries(Object.entries(inventory).sort(([a],[b])=>a.localeCompare(b))),null,2)+'\n');
fs.writeFileSync(dir+'scan-statistics.json',JSON.stringify(stats,null,2)+'\n');
fs.writeFileSync(dir+'route-unavailable.json',JSON.stringify(stats.filter(s=>s.status!==200),null,2)+'\n');
fs.writeFileSync(dir+'scan-occurrences.json',JSON.stringify(final,null,2)+'\n');
fs.writeFileSync(dir+'inventory-exclusions.json',JSON.stringify(removed,null,2)+'\n');
const paths=[...new Set(stats.map(s=>s.path))];
const csv='page,'+langs.join(',')+'\n'+paths.map(p=>JSON.stringify(p)+','+langs.map(l=>stats.find(s=>s.path===p&&s.lang===l).count).join(',')).join('\n')+'\n';
fs.writeFileSync(dir+'scan-statistics.csv',csv);
const summary={uniqueEnglishStrings:Object.keys(inventory).length,pages:7,languages:11,canonical200:55,canonical404:22,verifiedNativeQuery200:22,byKind:Object.fromEntries(['block','text','attr'].map(k=>[k,Object.values(inventory).filter(v=>v.kind===k).length])),excludedOccurrences:removed.length};
fs.writeFileSync(dir+'inventory-audit.json',JSON.stringify(summary,null,2)+'\n');
console.log(JSON.stringify(summary));console.log(csv.trim());console.log('PASS: schema, 77 observed page/language surfaces, exact source inventory, no removed phrases or article titles');
