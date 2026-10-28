import fs from 'node:fs';import {execFileSync} from 'node:child_process';import crypto from 'node:crypto';import {priceQuote} from 'file:///E:/破甲工作区/packrivo-v2/lib/pricing.ts';
const dir='E:/破甲工作区/packrivo-backup/deploy/verification-28l/',tag=process.argv[2]||'unknown';
const cases=[
 {style:'rigid',material:'rigidboard',print:'full-color',finish:'matte',quantity:500,length:9.5,width:9.5,height:4,rush:false},
 {style:'mailer',material:'kraft',print:'full-color',finish:'matte',quantity:500,length:30,width:22,height:12,rush:false},
 {style:'sticker',material:'kraft',print:'full-color',finish:'matte',quantity:500,length:15,width:10,height:2,rush:false},
 {style:'shipping',material:'kraft',print:'full-color',finish:'matte',quantity:500,length:30,width:25,height:20,rush:false}
];
const p=JSON.parse(execFileSync('D:/Codex/packrivo-review-2026-10-18/full-remediation/runtime/native/php-8.3.31/php.exe',[dir+'quote-price.php'],{input:JSON.stringify(cases),encoding:'utf8'}));const rows=[];
for(const [i,c] of cases.entries()){const ts=priceQuote(c),php=p[i];if(Math.abs(ts.unit-php.unit)>.005||Math.abs(ts.total-php.total)>.005)throw Error('priceQuote parity failed '+c.style+' '+JSON.stringify({ts,php}));rows.push({input:c,typescript:{unit:ts.unit,total:ts.total},php:{unit:php.unit,total:php.total}});console.log(c.style,'qty='+c.quantity,'TS='+ts.unit+'/'+ts.total,'PHP='+php.unit+'/'+php.total,'PASS');}
const files=['E:/破甲工作区/packrivo-v2/lib/pricing.ts','E:/破甲工作区/packrivo-wp/packrivo-quote/includes/class-pkq-pricing.php','E:/破甲工作区/packrivo-wp/packrivo-quote/includes/pricing.json','E:/破甲工作区/packrivo-wp/packrivo-quote/assets/pkq-quote.js'];const hashes=Object.fromEntries(files.map(f=>[f,crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex')]));fs.writeFileSync(dir+'quote-prices-'+tag+'.json',JSON.stringify({rows,hashes},null,2)+'\n');console.log('PASS 4 cases: TS priceQuote equals PHP pricing engine');
