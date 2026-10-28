const fs=require('fs');
const path=require('path');
const {chromium}=require('E:/破甲工作区/packrivo-v2/node_modules/playwright-core');
const sharp=require('E:/破甲工作区/packrivo-v2/node_modules/sharp');
const root=path.resolve(__dirname,'../..');
const out=path.join(root,'deploy','verification-27g');
const origin='http://localhost:8081';
const slugs=['product-pillow-jewelry-box','product-double-wall-tray-lid','product-hinged-nonmagnetic-rigid-box','molded-pulp-insert','product-hinged-thermoformed-clamshell'];
const langs=['de','ja','ar'];
const input=[1,2,3,4,5].flatMap(i=>JSON.parse(fs.readFileSync(path.join(root,'dsh-out',`batch-0${i}.json`),'utf8')));
const by=Object.fromEntries(input.map(x=>[x.slug,x]));
(async()=>{
  fs.mkdirSync(out,{recursive:true});
  const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--disable-background-networking']});
  const results=[];
  try {
    for(const slug of slugs)for(const lang of langs){
      const page=await browser.newPage({viewport:{width:lang==='ar'?375:1280,height:900}});
      await page.route('**/*',route=>{
        const req=route.request(),u=new URL(req.url());
        const local=u.origin===origin;
        const publicImage=u.hostname==='www.packrivo.com'&&req.resourceType()==='image'&&(u.pathname.startsWith('/wp-content/')||u.pathname.startsWith('/assets/'));
        return local||publicImage?route.continue():route.abort();
      });
      const response=await page.goto(`${origin}/${lang}/${slug}/`,{waitUntil:'domcontentloaded',timeout:45000});
      const actual=await page.evaluate(()=>({title:document.title,titleTags:[...document.querySelectorAll('head>title')].map(e=>e.textContent),h1:[...document.querySelectorAll('h1')].map(e=>e.textContent.trim()),description:[...document.querySelectorAll('meta[name="description"]')].map(e=>e.content),direction:getComputedStyle(document.documentElement).direction,htmlDir:document.documentElement.dir,overflow:document.documentElement.scrollWidth>innerWidth}));
      const expected=by[slug].i18n[lang];
      const match=response.status()===200&&actual.title===expected.title&&actual.h1.length===1&&actual.h1[0]===expected.h1&&actual.description.length===1&&actual.description[0]===expected.description;
      const result={slug,lang,status:response.status(),...actual,match};results.push(result);console.log(JSON.stringify(result));
      if(lang==='ar'&&['product-pillow-jewelry-box','product-double-wall-tray-lid'].includes(slug)){
        // Chromium captures the wrong blank region when the inherited negative
        // skip-link expands an RTL canvas. Preserve the page as-is and extract
        // the rightmost, visible 375px viewport from its full capture.
        const raw=await page.screenshot({fullPage:true});
        const dimensions=await sharp(raw).metadata();
        const file=`${slug}-ar-375.png`;
        await sharp(raw).extract({left:dimensions.width-375,top:0,width:375,height:900}).png().toFile(path.join(out,file));
        result.screenshot={file,width:375,height:900,sourceCanvasWidth:dimensions.width,method:'rightmost viewport from unmodified RTL page capture'};
      }
      await page.close();
    }
  } finally { await browser.close(); }
  fs.writeFileSync(path.join(out,'http-results.json'),JSON.stringify(results,null,2)+'\n','utf8');
  const fail=results.filter(r=>!r.match);
  const rtlFail=results.filter(r=>r.lang==='ar'&&(r.direction!=='rtl'||r.htmlDir!=='rtl'));
  const overflow=results.filter(r=>r.lang==='ar'&&r.overflow);
  console.log(`HTTP checks=${results.length} field_mismatches=${fail.length} RTL_direction_failures=${rtlFail.length} horizontal_overflow_pages=${overflow.length}`);
  if(fail.length||rtlFail.length||overflow.length)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
