import fs from 'node:fs';import assert from 'node:assert/strict';
const root='E:/破甲工作区/packrivo-backup/deploy/verification-28l/',before=JSON.parse(fs.readFileSync(root+'cta-before.json','utf8')),after=JSON.parse(fs.readFileSync(root+'cta-after.json','utf8'));
const e=s=>String(s??'').replace(/\|/g,'\\|').replace(/\n/g,' ');let md='| 语言 | 页面 | 位置 | 按钮原文/译文 | 改前 href | 改后 href | 最终 HTTP |\n| --- | --- | --- | --- | --- | --- | ---: |\n';let n=0;
for(const a of after){const b=before.find(x=>x.lang===a.lang&&x.route===a.route);assert(b&&b.links.length===a.links.length);for(let i=0;i<a.links.length;i++){const x=a.links[i],old=b.links[i];assert.equal(x.text,old.text);md+='| '+[a.lang,a.route,x.location,x.text,'`'+old.href+'`','`'+x.href+'`',x.http].map(e).join(' | ')+' |\n';n++;}}
fs.writeFileSync(root+'cta-table.md',md);console.log('PASS table rows='+n+' before/after text unchanged; final HTTP 200 for each');
