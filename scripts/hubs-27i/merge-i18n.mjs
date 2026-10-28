import fs from 'node:fs';import path from 'node:path';import crypto from 'node:crypto';import {fileURLToPath} from 'node:url';
import {LANGS,mergeKeepingProduction} from '../product-buyers-27g/merge-i18n.mjs';
const args=Object.fromEntries(process.argv.slice(2).reduce((a,v,i,all)=>{if(v.startsWith('--'))a.push([v.slice(2),all[i+1]]);return a;},[]));
const root=path.resolve(args.root??path.join(path.dirname(fileURLToPath(import.meta.url)),'../..'));
for(const key of ['base','js-base','paths-base'])if(!args[key])throw new Error('Missing --'+key);
const read=f=>JSON.parse(fs.readFileSync(f,'utf8').replace(/^\uFEFF/,''));const write=(f,d)=>fs.writeFileSync(f,JSON.stringify(d,null,2)+'\n','utf8');const sha=f=>crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const here=path.dirname(fileURLToPath(import.meta.url)),priorFile=path.join(here,'prior-i18n-27g.json');
if(!fs.existsSync(priorFile)){const d=Object.fromEntries(LANGS.map(l=>[l,read(path.join(root,'i18n-delta',l+'-dict.json'))]));fs.writeFileSync(priorFile,JSON.stringify(d,null,2)+'\n',{encoding:'utf8',flag:'wx'});}
const prior=read(priorFile),input=read(path.join(root,'dsh-out/hubs-27i.json')),hubs=input.filter(r=>r.path),ui=input.find(r=>r.ui);
if(hubs.length!==8||ui?.ui!=='Get a quote · MOQ 500')throw new Error('Unexpected approved input.');
const backup=read(args['metadata-backup']??path.join(root,'deploy/rollback-27i.json'));
const actualInquiry=backup.rows['/inquiry/'].meta._packrivo_description[0];
const stats=[],conflicts={},manifest={input_sha256:sha(path.join(root,'dsh-out/hubs-27i.json')),dictionaries:{},runtime:{},paths:{sha256:sha(args['paths-base'])}};
const out=path.join(root,'deploy/i18n-merged');fs.mkdirSync(out,{recursive:true});
for(const lang of LANGS){
 const proposal=structuredClone(prior[lang]);
 const add=(bucket,key,value)=>{if(typeof value!=='string'||!value)throw new Error('Missing approved translation.');if(Object.hasOwn(proposal[bucket],key)&&proposal[bucket][key]!==value)throw new Error('Reviewed-source conflict: '+lang+'/'+key);proposal[bucket][key]=value;};
 for(const hub of hubs){add('title',hub.title,hub.i18n[lang].title);for(const bucket of ['block','attr'])add(bucket,hub.description,hub.i18n[lang].description);}
 for(const bucket of ['block','text'])add(bucket,ui.ui,ui.i18n[lang]);
 const baseFile=path.join(args.base,lang+'-dict.json'),base=read(baseFile),result=mergeKeepingProduction(base,proposal);
 if(typeof result.merged.attr[actualInquiry]!=='string')throw new Error('No existing full inquiry translation: '+lang);
 const jsFile=path.join(args['js-base'],lang+'-js.json'),js=read(jsFile),runtime=structuredClone(js);runtime.exact??={};let runtimeConflict=null;
 if(Object.hasOwn(js.exact??{},ui.ui)&&js.exact[ui.ui]!==ui.i18n[lang])runtimeConflict={key:ui.ui,production:js.exact[ui.ui],requested:ui.i18n[lang],resolution:'production retained'};
 else runtime.exact[ui.ui]=ui.i18n[lang];
 write(path.join(root,'i18n-delta',lang+'-dict.json'),proposal);write(path.join(out,lang+'-dict.json'),result.merged);write(path.join(out,lang+'-js.json'),runtime);
 manifest.dictionaries[lang]={sha256:sha(baseFile)};manifest.runtime[lang]={sha256:sha(jsFile)};
 conflicts[lang]={server:result.conflicts,runtime:runtimeConflict?[runtimeConflict]:[]};
 stats.push({language:lang,addedBucketKeys:result.addedBucketKeys,addedEnglishKeys:result.addedEnglishKeys,serverConflictKeys:result.conflicts.length,runtimeAddedKeys:Object.hasOwn(js.exact??{},ui.ui)?0:1,runtimeConflicts:runtimeConflict?1:0});
}
const paths=read(args['paths-base']);if(!Array.isArray(paths))throw new Error('Invalid paths baseline.');const addedPaths=hubs.map(r=>r.path).filter(p=>!paths.includes(p));write(path.join(out,'paths.json'),[...paths,...addedPaths]);manifest.paths.added=addedPaths;
write(path.join(root,'deploy/i18n-statistics-27i.json'),stats);write(path.join(root,'deploy/i18n-conflicts-27i.json'),conflicts);write(path.join(root,'deploy/i18n-baseline-27i.json'),manifest);
write(path.join(root,'deploy/verification-27i/inquiry-description-exception.json'),{path:'/inquiry/',resolution:'preserve existing English and its existing production translations',approvedShortDescription:hubs.find(r=>r.path==='/inquiry/').description,preservedExistingDescription:actualInquiry});
console.log('language added_bucket_keys added_english_keys server_conflicts runtime_added runtime_conflicts');for(const r of stats)console.log([r.language,r.addedBucketKeys,r.addedEnglishKeys,r.serverConflictKeys,r.runtimeAddedKeys,r.runtimeConflicts].join(' '));console.log('translation paths added: '+JSON.stringify(addedPaths));console.log('existing inquiry English and complete production translations retained');
