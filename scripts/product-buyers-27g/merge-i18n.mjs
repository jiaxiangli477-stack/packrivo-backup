import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath, pathToFileURL } from 'node:url';

export const LANGS=['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'];
const BUCKETS=['title','block','text','attr'];
const read=file=>JSON.parse(fs.readFileSync(file,'utf8').replace(/^\uFEFF/,''));
const write=(file,data)=>fs.writeFileSync(file,JSON.stringify(data,null,2)+'\n','utf8');
const empty=()=>Object.fromEntries(BUCKETS.map(k=>[k,{}]));

export function mergeKeepingProduction(base, delta) {
  const merged=structuredClone(base), conflicts=[];
  for(const bucket of BUCKETS) merged[bucket]??={};
  const keys=new Set(BUCKETS.flatMap(b=>Object.keys(delta[b]??{})));
  let addedBucketKeys=0,addedEnglishKeys=0;
  for(const key of keys){
    const existing=BUCKETS.filter(b=>Object.hasOwn(base[b]??{},key)).map(bucket=>({bucket,value:base[bucket][key]}));
    const requested=BUCKETS.filter(b=>Object.hasOwn(delta[b]??{},key)).map(bucket=>({bucket,value:delta[bucket][key]}));
    const conflict=existing.length>0 && requested.some(r=>existing.some(e=>e.value!==r.value));
    if(conflict) conflicts.push({key,production:existing,requested,resolution:'production retained'});
    if(!existing.length) addedEnglishKeys++;
    for(const request of requested){
      if(Object.hasOwn(base[request.bucket]??{},key)) continue;
      merged[request.bucket][key]=conflict ? existing[0].value : request.value;
      addedBucketKeys++;
    }
  }
  return {merged,conflicts,addedBucketKeys,addedEnglishKeys};
}

function main(){
  const options=Object.fromEntries(process.argv.slice(2).reduce((a,v,i,all)=>{if(v.startsWith('--')) a.push([v.slice(2),all[i+1]]);return a;},[]));
  const root=path.resolve(options.root??path.join(path.dirname(fileURLToPath(import.meta.url)),'../..'));
  if(!options.base) throw new Error('Pass --base with the read-only production dictionary directory.');
  const baseline=path.resolve(options.base), here=path.dirname(fileURLToPath(import.meta.url));
  const priorFile=path.join(here,'prior-i18n-27e.json');
  // Freeze the four already-reviewed pages once, before replacing combined deltas.
  if(!fs.existsSync(priorFile)){
    const prior=Object.fromEntries(LANGS.map(lang=>[lang,read(path.join(root,'i18n-delta',`${lang}-dict.json`))]));
    fs.writeFileSync(priorFile,JSON.stringify(prior,null,2)+'\n',{encoding:'utf8',flag:'wx'});
  }
  const prior=read(priorFile);
  const items=[1,2,3,4,5].flatMap(i=>read(path.join(root,'dsh-out',`batch-0${i}.json`)));
  if(items.length!==130 || new Set(items.map(x=>x.slug)).size!==130) throw new Error('Expected 130 approved unique pages.');
  const ui=read(path.join(root,'dsh-out','ui-strings.json'));
  if(Object.keys(ui).length!==60) throw new Error('Expected 60 approved UI strings.');
  const attrOnly=new Set(['Red drawer jewelry box with ribbon pull and insert','Black and white lid-and-base jewelry boxes with inserts']);
  const uiBuckets=Object.fromEntries(Object.keys(ui).map(key=>[key,attrOnly.has(key)?['attr']:key==='Search'||key.startsWith('Custom ')?['text','block','attr']:['text','block']]));
  const deltas={}, results=[], conflicts={}, manifests={}, sourceConflicts=[];
  for(const lang of LANGS){
    const delta=structuredClone(prior[lang]??empty());
    const add=(bucket,key,value,source)=>{
      if(typeof value!=='string'||!value) throw new Error(`${lang}: missing ${source}/${key}`);
      if(Object.hasOwn(delta[bucket],key)&&delta[bucket][key]!==value){sourceConflicts.push({lang,bucket,key,retained:delta[bucket][key],requested:value,source});return;}
      delta[bucket][key]=value;
    };
    for(const item of items){
      const tr=item.i18n[lang]; if(!tr)throw new Error(`${lang}: missing ${item.slug}`);
      add('title',item.new_title,tr.title,item.slug);
      for(const bucket of ['block','text'])add(bucket,item.new_h1,tr.h1,item.slug);
      for(const bucket of ['block','attr'])add(bucket,item.new_description,tr.description,item.slug);
    }
    for(const [key,translations] of Object.entries(ui))for(const bucket of uiBuckets[key])add(bucket,key,translations[lang],'ui');
    const baseFile=path.join(baseline,`${lang}-dict.json`),base=read(baseFile);
    if(!BUCKETS.every(b=>base[b]&&typeof base[b]==='object'))throw new Error(`Invalid baseline ${lang}`);
    const result=mergeKeepingProduction(base,delta);
    deltas[lang]=delta;results.push({language:lang,deltaBucketKeys:BUCKETS.reduce((n,b)=>n+Object.keys(delta[b]).length,0),addedBucketKeys:result.addedBucketKeys,addedEnglishKeys:result.addedEnglishKeys,conflictingEnglishKeys:result.conflicts.length});
    conflicts[lang]=result.conflicts;
    manifests[lang]={file:`${lang}-dict.json`,sha256:crypto.createHash('sha256').update(fs.readFileSync(baseFile)).digest('hex')};
    result.lang=lang; results[results.length-1].merged=result.merged;
  }
  if(sourceConflicts.length)throw new Error('Reviewed source translations conflict: '+JSON.stringify(sourceConflicts));
  const out=path.join(root,'deploy','i18n-merged');fs.mkdirSync(out,{recursive:true});
  for(const result of results){write(path.join(root,'i18n-delta',`${result.language}-dict.json`),deltas[result.language]);write(path.join(out,`${result.language}-dict.json`),result.merged);delete result.merged;}
  write(path.join(root,'deploy','i18n-conflicts-27g.json'),conflicts);
  write(path.join(root,'deploy','i18n-baseline-27g.json'),manifests);
  write(path.join(root,'deploy','i18n-statistics-27g.json'),results);
  write(path.join(here,'ui-partitions.json'),uiBuckets);
  console.log('language delta_bucket_keys added_bucket_keys added_english_keys conflicts');
  for(const row of results)console.log([row.language,row.deltaBucketKeys,row.addedBucketKeys,row.addedEnglishKeys,row.conflictingEnglishKeys].join(' '));
  console.log('production existing bucket values preserved; reviewed source conflicts=0');
}
if(process.argv[1]&&import.meta.url===pathToFileURL(path.resolve(process.argv[1])).href) main();
