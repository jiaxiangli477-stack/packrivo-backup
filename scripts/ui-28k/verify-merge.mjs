import fs from 'node:fs';import assert from 'node:assert/strict';import crypto from 'node:crypto';
import {LANGS} from '../product-buyers-27g/merge-i18n.mjs';
const root='E:/破甲工作区/packrivo-backup/',baseline='E:/破甲工作区/.tmp/packrivo-28k/baseline/',out=root+'deploy/verification-28k/';
const read=f=>JSON.parse(fs.readFileSync(f,'utf8').replace(/^\uFEFF/,''));
const input=read(root+'dsh-in/untranslated-27j.json'),approved=read(root+'dsh-out/untranslated-27j.json'),conflicts=read(root+'deploy/i18n-conflicts-28k.json');
assert.equal(crypto.createHash('sha256').update(fs.readFileSync(root+'dsh-out/untranslated-27j.json')).digest('hex'),'edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f');
const dates=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],categories=['Materials & print','Samples & approval','Structures & fit','Supplier briefs'];let preserved=0,cases=0;
for(const lang of LANGS){
 const old=read(baseline+'i18n-merged/'+lang+'-dict.json'),oldDelta=read(baseline+'i18n-delta/'+lang+'-dict.json'),oldJS=read(baseline+'i18n-merged/'+lang+'-js.json');
 const merged=read(root+'deploy/i18n-merged/'+lang+'-dict.json'),delta=read(root+'i18n-delta/'+lang+'-dict.json'),js=read(root+'deploy/i18n-merged/'+lang+'-js.json');
 for(const b of ['title','block','text','attr']){
  for(const [key,val] of Object.entries(old[b]??{})){assert.equal(merged[b][key],val,lang+'/'+b+'/'+key);preserved++;}
  for(const [key,val] of Object.entries(oldDelta[b]??{}))assert.equal(delta[b][key],val,lang+' previous delta');
 }
 for(const [key,val] of Object.entries(oldJS.exact??{}))assert.equal(js.exact[key],val,lang+' existing runtime exact');
 assert.deepEqual(js.patterns.slice(0,oldJS.patterns.length),oldJS.patterns);
 for(const menu of ['Products','Blog','Resources']){
  const open=merged.attr['Open '+menu+' menu'],close=merged.attr['Close '+menu+' menu'];
  const base=open.replace(/^(Open|Close) /,'');
  assert.equal(js.exact['Open '+base],open,lang+' open '+menu);
  assert.equal(js.exact['Close '+base],close,lang+' close '+menu);
 }
 for(const [key,meta] of Object.entries(input)){
  const expected=conflicts[lang].production.find(x=>x.key===key);const actual=merged[meta.kind][key];
  assert.equal(actual,expected?expected.production.find(x=>x.bucket===meta.kind)?.value??expected.production[0].value:approved[key][lang],lang+'/'+key);
 }
 for(const cat of categories)for(let month=0;month<12;month++)for(const min of [null,11]){
  const source=cat+' · '+(min===null?'':min+' min · ')+dates[month]+' 25, 2028';
  const match=js.patterns.find(([pat])=>new RegExp(pat).test(source));assert(match,lang+' missing '+source);
  const value=source.replace(new RegExp(match[0]),match[1]);
  assert(!/\$\d/.test(value)&&value.includes('2028')&&value.includes('25')&&(min===null||value.includes('11')),lang+' malformed '+source+' -> '+value);
  if(lang.startsWith('zh'))assert(value.includes(String(month+1)+' 月'),lang+' bad month '+source+' -> '+value);
  if(lang==='ja')assert(value.includes(String(month+1)+'月'),lang+' bad month '+source+' -> '+value);
  if(lang==='ko')assert(value.includes(String(month+1)+'월'),lang+' bad month '+source+' -> '+value);
  cases++;
 }
}
const result={languages:LANGS.length,approvedEnglishKeys:Object.keys(input).length,approvedSHA256:'edcadc0b117fa3a564c18c25c5b52b96d1917cf72e9777a6f80cd831d2792b3f',existingBucketValuesPreserved:preserved,syntheticDateCases:cases,conflictCount:Object.values(conflicts).reduce((n,c)=>n+c.production.length,0),pass:true};
fs.writeFileSync(out+'merge-verification.json',JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify(result));
console.log('PASS: approved input unchanged; all baseline values and old runtime rules retained; each requested kind resolved; dynamic month/day/year patterns tested');
