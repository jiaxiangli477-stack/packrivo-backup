import fs from 'node:fs';import assert from 'node:assert/strict';
const root='E:/破甲工作区/packrivo-backup/deploy/verification-28l/',before=JSON.parse(fs.readFileSync(root+'cta-before.json','utf8')),after=JSON.parse(fs.readFileSync(root+'cta-after.json','utf8'));
assert.equal(after.length,9);let links=0,labels=0,productCards=0;
for(const a of after){const prior=before.find(x=>x.lang===a.lang&&x.route===a.route);assert(prior);assert.equal(a.pageStatus,200);assert.equal(prior.links.length,a.links.length,a.lang+a.route);
 for(let i=0;i<a.links.length;i++){const x=a.links[i],old=prior.links[i];assert.equal(x.text,old.text,a.lang+' text '+a.route);labels++;
  const u=new URL(x.href),expected=a.lang==='en'?'/get-a-quote/':'/'+a.lang+'/inquiry/';assert.equal(u.hostname,'localhost',x.href);assert.equal(u.pathname,expected,x.href);assert.equal(x.http,200,x.href+' '+JSON.stringify(x.chain));
  if(x.location==='product-card'){assert(u.searchParams.has('product'),x.href);productCards++;if(a.lang==='en'){assert.equal(u.searchParams.get('qty'),'500',x.href);assert(u.searchParams.has('style'),x.href);}}
  if(a.route==='/product-pillow-jewelry-box/'&&x.location!=='product-card'){assert.equal(u.searchParams.get('product'),a.lang==='en'?'product-pillow-jewelry-box':'pillow-jewelry-box',x.href);if(a.lang==='en'){assert.equal(u.searchParams.get('style'),'carton');assert.equal(u.searchParams.get('qty'),'500');}}
  links++;
 }
}
console.log(JSON.stringify({pages:after.length,links,labelsUnchanged:labels,productCardLinks:productCards,non200:0}));console.log('PASS: en quote path; ar/de localized inquiry; product context, style, quantity and existing button text checked');
