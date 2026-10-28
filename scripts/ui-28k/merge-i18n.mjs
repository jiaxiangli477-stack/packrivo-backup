import fs from 'node:fs';import path from 'node:path';import crypto from 'node:crypto';
import {LANGS,mergeKeepingProduction} from '../product-buyers-27g/merge-i18n.mjs';
const args=Object.fromEntries(process.argv.slice(2).reduce((a,v,i,all)=>{if(v.startsWith('--'))a.push([v.slice(2),all[i+1]]);return a;},[]));
const root=path.resolve(args.root??'E:/破甲工作区/packrivo-backup'),baseline='E:/破甲工作区/.tmp/packrivo-28k/baseline';
const baseDir=args.base??path.join(baseline,'i18n-merged'),deltaDir=args['delta-base']??path.join(baseline,'i18n-delta'),jsDir=args['js-base']??baseDir;
const read=f=>JSON.parse(fs.readFileSync(f,'utf8').replace(/^\uFEFF/,''));
const write=(f,v)=>fs.writeFileSync(f,JSON.stringify(v,null,2)+'\n','utf8');
const sha=f=>crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const approvedFile=path.join(root,'dsh-out/untranslated-27j.json');
const input=read(path.join(root,'dsh-in/untranslated-27j.json')),approved=read(approvedFile);
const occurrences=read(path.join(root,'deploy/verification-27j/scan-occurrences.json'));
if(Object.keys(input).length!==207||Object.keys(approved).length!==207||Object.keys(input).some(k=>!Object.hasOwn(approved,k)))throw Error('Approved input mismatch');
const categories=['Materials & print','Samples & approval','Structures & fit','Supplier briefs'];
const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const esc=s=>s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
const specs={},stats=[],conflicts={},allDeltas={},allMerged={},allRuntime={};
const buckets=['title','block','text','attr'];
for(const lang of LANGS){
 const baseFile=path.join(baseDir,lang+'-dict.json'),deltaFile=path.join(deltaDir,lang+'-dict.json'),jsFile=path.join(jsDir,lang+'-js.json');
 const base=read(baseFile),delta=read(deltaFile),js=read(jsFile),proposal={title:{},block:{},text:{},attr:{}},sourceConflicts=[];
 for(const [key,meta] of Object.entries(input)){
  const t=approved[key]?.[lang];if(typeof t!=='string'||!t.trim()||!['block','text','attr'].includes(meta.kind))throw Error('Invalid input '+lang+' '+key);
  const kinds=new Set(meta.kind==='attr'?['attr']:['block','text']);
  // 27j records attr as the priority kind if the same English is also visible
  // as text; restore that secondary occurrence using its recorded evidence.
  if(meta.kind==='attr'&&occurrences.some(x=>x.text===key&&x.kind!=='attr')){kinds.add('block');kinds.add('text');}
  for(const b of kinds){proposal[b][key]=t;delta[b]??={};if(Object.hasOwn(delta[b],key)&&delta[b][key]!==t)sourceConflicts.push({key,bucket:b,existing:delta[b][key],approved:t,resolution:'existing delta retained'});else delta[b][key]=t;}
 }
 const merged=mergeKeepingProduction(base,proposal),runtime=structuredClone(js),patterns=[];runtime.patterns??=[];runtime.exact??={};let exactAdded=0;
 // nav.js saves the already translated open label in data-label, then prepends
 // English Open/Close on subsequent clicks. Map that generated text to the
 // existing effective translation; never overwrite a pre-existing runtime key.
 for(const menu of ['Products','Blog','Resources']){
  const open='Open '+menu+' menu',close='Close '+menu+' menu';
  const openLabel=merged.merged.attr[open],closeLabel=merged.merged.attr[close];
  if(!openLabel||!closeLabel)throw Error('Missing reviewed navigation label '+lang+'/'+menu);
  const baseLabel=openLabel.replace(/^(Open|Close) /,'');
  for(const [key,value] of [['Open '+baseLabel,openLabel],['Close '+baseLabel,closeLabel]]){
   if(Object.hasOwn(runtime.exact,key)){if(runtime.exact[key]!==value)sourceConflicts.push({key,existing:runtime.exact[key],approved:value,resolution:'existing runtime exact retained'});}
   else{runtime.exact[key]=value;exactAdded++;}
  }
 }
 const locale=(lang==='zh-CN'?'zh-CN':lang==='zh-TW'?'zh-TW':lang);
 for(const cat of categories){
  for(const withMin of [true,false]){
   const matchKey=Object.keys(input).find(k=>withMin?new RegExp('^'+esc(cat)+' · (\\d+) min · Oct (\\d+), 2026$').test(k):k===cat+' · Oct 20, 2026'||k===cat+' · Oct 15, 2026'||k===cat+' · Oct 26, 2026');
   if(!matchKey)throw Error('Missing approved date template '+lang+' '+cat+' '+withMin);
   const match=matchKey.match(withMin?/ · (\d+) min · Oct (\d+), 2026$/:/ · Oct (\d+), 2026$/);
   const min=withMin?match[1]:null,day=withMin?match[2]:match[1];let template=approved[matchKey][lang];
   const localOct=new Intl.DateTimeFormat(locale,{month:['ar','it'].includes(lang)?'long':'short',timeZone:'UTC'}).format(new Date(Date.UTC(2026,9,1)));
   if(lang.startsWith('zh')){if(!/(?<!\d)10(?!\d)/.test(template))throw Error('Cannot locate reviewed month '+lang);}
   else if(!template.includes(localOct))throw Error('Cannot locate approved month '+lang+' '+localOct+' '+template);
   for(let m=0;m<12;m++){
    let replacement=template;
    const localMonth=new Intl.DateTimeFormat(locale,{month:['ar','it'].includes(lang)?'long':'short',timeZone:'UTC'}).format(new Date(Date.UTC(2026,m,1)));
    replacement=replacement.replace(/(?<!\d)2026(?!\d)/g,withMin?'$3':'$2');
    replacement=replacement.replace(new RegExp('(?<!\\d)'+day+'(?!\\d)','g'),withMin?'$2':'$1');
    if(withMin)replacement=replacement.replace(new RegExp('(?<!\\d)'+min+'(?!\\d)','g'),'$1');
    if(lang.startsWith('zh'))replacement=replacement.replace(/(?<!\d)10(?!\d)/,String(m+1));
    else replacement=replacement.replace(localOct,localMonth);
    const regex='^'+esc(cat)+' · '+(withMin?'(\\d+) min · ':'')+months[m]+' (\\d{1,2}), (\\d{4})$';
    const existing=runtime.patterns.find(p=>p[0]===regex);
    if(existing&&existing[1]!==replacement)sourceConflicts.push({pattern:regex,existing:existing[1],approved:replacement,resolution:'existing runtime pattern retained'});
    if(!existing){runtime.patterns.push([regex,replacement]);patterns.push([regex,replacement]);}
   }
  }
 }
 const reviewSample='Materials & print · 6 min · Oct 20, 2026';
 const selected=runtime.patterns.find(([re])=>new RegExp(re).test(reviewSample));
 if(!selected||reviewSample.replace(new RegExp(selected[0]),selected[1])!==approved[reviewSample][lang])throw Error('Date pattern does not reproduce reviewed example '+lang);
 allDeltas[lang]=delta;allMerged[lang]=merged.merged;allRuntime[lang]=runtime;
 conflicts[lang]={production:merged.conflicts,priorDeltaAndPatterns:sourceConflicts};
 stats.push({language:lang,addedEnglishKeys:merged.addedEnglishKeys,addedBucketKeys:merged.addedBucketKeys,productionConflicts:merged.conflicts.length,priorDeltaConflicts:sourceConflicts.length,newRuntimeExact:exactAdded,newRuntimePatterns:patterns.length});
 specs[lang]={dictionaryBaselineSHA256:sha(baseFile),deltaBaselineSHA256:sha(deltaFile),runtimeBaselineSHA256:sha(jsFile)};
}
for(const lang of LANGS){write(path.join(root,'i18n-delta',lang+'-dict.json'),allDeltas[lang]);write(path.join(root,'deploy/i18n-merged',lang+'-dict.json'),allMerged[lang]);write(path.join(root,'deploy/i18n-merged',lang+'-js.json'),allRuntime[lang]);}
write(path.join(root,'deploy/i18n-conflicts-28k.json'),conflicts);write(path.join(root,'deploy/i18n-statistics-28k.json'),stats);write(path.join(root,'deploy/i18n-baseline-28k.json'),{approvedSHA256:sha(approvedFile),languages:specs});
console.log('language added_english added_bucket production_conflicts prior_delta_or_runtime_conflicts runtime_exact runtime_patterns');
for(const r of stats)console.log([r.language,r.addedEnglishKeys,r.addedBucketKeys,r.productionConflicts,r.priorDeltaConflicts,r.newRuntimeExact,r.newRuntimePatterns].join(' '));
console.log('PASS: reviewed source unchanged; existing production and prior delta values retained; October approved sample matched by runtime date pattern');
