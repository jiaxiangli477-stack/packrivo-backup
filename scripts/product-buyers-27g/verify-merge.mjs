import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {fileURLToPath} from 'node:url';
import {LANGS} from './merge-i18n.mjs';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const base=process.argv[2];if(!base)throw new Error('Pass the read-only baseline directory.');
const read=f=>JSON.parse(fs.readFileSync(f,'utf8').replace(/^\uFEFF/,''));
const rollback=read(path.join(root,'deploy/rollback-27g.json'));
for(const [file,hash] of Object.entries(rollback.input_files))assert.equal(crypto.createHash('sha256').update(fs.readFileSync(path.join(root,'dsh-out',file))).digest('hex'),hash,'Approved input changed: '+file);
const entries=[1,2,3,4,5].flatMap(i=>read(path.join(root,'dsh-out',`batch-0${i}.json`)));
const prior=read(path.join(root,'scripts/product-buyers-27g/prior-i18n-27e.json'));
const ui=read(path.join(root,'dsh-out/ui-strings.json'));
const partitions=read(path.join(root,'scripts/product-buyers-27g/ui-partitions.json'));
const baselineManifest=read(path.join(root,'deploy/i18n-baseline-27g.json'));
const conflicts=read(path.join(root,'deploy/i18n-conflicts-27g.json'));
let sourceChecks=0,productionChecks=0,uiChecks=0;
const placeholders=s=>(s.match(/\{[^}]+\}/g)??[]).sort();
for(const lang of LANGS){
  const delta=read(path.join(root,'i18n-delta',`${lang}-dict.json`));
  const merged=read(path.join(root,'deploy/i18n-merged',`${lang}-dict.json`));
  const baseFile=path.join(base,`${lang}-dict.json`),production=read(baseFile);
  assert.equal(crypto.createHash('sha256').update(fs.readFileSync(baseFile)).digest('hex'),baselineManifest[lang].sha256);
  for(const bucket of ['title','block','text','attr']){
    for(const [key,value] of Object.entries(production[bucket])){assert.equal(merged[bucket][key],value,`${lang}/${bucket}: production overwritten`);productionChecks++;}
    for(const [key,value] of Object.entries(prior[lang][bucket])){assert.equal(delta[bucket][key],value,`${lang}: prior proposal lost`);sourceChecks++;}
  }
  for(const row of entries){const tr=row.i18n[lang];for(const [bucket,key,value] of [['title',row.new_title,tr.title],['block',row.new_h1,tr.h1],['text',row.new_h1,tr.h1],['block',row.new_description,tr.description],['attr',row.new_description,tr.description]]){assert.equal(delta[bucket][key],value,`${lang}/${row.slug}/${bucket}: reviewed source changed`);assert.equal(typeof merged[bucket][key],'string');sourceChecks++;}}
  for(const [key,translations] of Object.entries(ui))for(const bucket of partitions[key]){assert.equal(delta[bucket][key],translations[lang],`${lang}: UI changed`);assert.deepEqual(placeholders(delta[bucket][key]),placeholders(key));assert.equal(typeof merged[bucket][key],'string');uiChecks++;}
}
const result={approvedBatchFilesUnchanged:5,productDeltaSourceChecks:sourceChecks,uiPartitionChecks:uiChecks,productionBucketValuesPreserved:productionChecks,conflictingEnglishKeys:Object.values(conflicts).reduce((n,a)=>n+a.length,0),languages:LANGS.length};
fs.writeFileSync(path.join(root,'deploy/verification-27g/merge-audit.json'),JSON.stringify(result,null,2)+'\n','utf8');
console.log(JSON.stringify(result));
console.log('PASS: reviewed proposals complete; production values retained; placeholders unchanged');
