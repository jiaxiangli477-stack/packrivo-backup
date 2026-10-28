import fs from 'node:fs';import path from 'node:path';import assert from 'node:assert/strict';import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..'),read=f=>JSON.parse(fs.readFileSync(path.join(root,f),'utf8'));
const before=read('deploy/verification-27i/curl-before.json'),after=read('deploy/verification-27i/curl-after.json'),hubs=read('dsh-out/hubs-27i.json').filter(x=>x.path),backup=read('deploy/rollback-27i.json');
assert.equal(before.length,32);assert.equal(after.length,32);
const exceptions=[],failures=[];let strict=0;
for(const actual of after){
 const source=hubs.find(h=>h.path===actual.path),enDesc=actual.path==='/inquiry/'?backup.rows[actual.path].meta._packrivo_description[0]:source.description;
 const merged=actual.lang==='en'?null:read('deploy/i18n-merged/'+actual.lang+'-dict.json');
 const expectedTitle=actual.lang==='en'?source.title:merged.title[source.title];
 const expectedDescription=actual.lang==='en'?enDesc:merged.attr[enDesc];
 const desiredTitle=actual.lang==='en'?source.title:source.i18n[actual.lang].title,desiredDesc=actual.lang==='en'?source.description:source.i18n[actual.lang].description;
 if(actual.status!==200||actual.titles.length!==1||actual.descriptions.length!==1||actual.titles[0]!==expectedTitle||actual.descriptions[0]!==expectedDescription)failures.push(actual.url);
 if(actual.titles[0]===desiredTitle&&actual.descriptions[0]===desiredDesc)strict++;else exceptions.push({path:actual.path,language:actual.lang,titleDifferent:actual.titles[0]!==desiredTitle,descriptionDifferent:actual.descriptions[0]!==desiredDesc,reason:actual.path==='/inquiry/'?'existing inquiry English/production translation preserved':'production-existing title translation preserved'});
 if(/sourcing|توريد|Beschaffung|代采|采购|採購/i.test(actual.titles.join(' ')))failures.push(actual.url+': banned title');
}
let h1Unchanged=0;for(const r of after.filter(x=>x.lang==='en')){assert.deepEqual(r.h1,before.find(b=>b.path===r.path&&b.lang==='en').h1);h1Unchanged++;}
const bannedTitleHits=after.filter(r=>/sourcing|توريد|Beschaffung|代采|采购|採購/i.test(r.titles.join(' '))).length;
const result={responses:after.length,singleTitle:after.filter(r=>r.titles.length===1).length,singleDescription:after.filter(r=>r.descriptions.length===1).length,policyMismatches:failures.length,dshExactRows:strict,preservedExceptionRows:exceptions.length,englishH1Unchanged:h1Unchanged,bannedTitleHits,exceptions,failures};
fs.writeFileSync(path.join(root,'deploy/verification-27i/comparison.json'),JSON.stringify(result,null,2)+'\n','utf8');
console.log(`curl responses=${after.length} single_title=${result.singleTitle} single_description=${result.singleDescription} preservation_policy_mismatches=${failures.length}`);
console.log(`dsh_exact_rows=${strict} preserved_exception_rows=${exceptions.length} English_H1_unchanged=${h1Unchanged}/8`);
console.log('banned_title_hits='+bannedTitleHits);
for(const r of exceptions)console.log(JSON.stringify(r));if(failures.length)process.exitCode=1;
