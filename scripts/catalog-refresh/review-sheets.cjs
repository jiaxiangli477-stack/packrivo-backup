/** Render side-by-side review images. Sharp path and manifest supplied by caller. */
const fs = require('fs'); const path = require('path');
const sharp = require(process.argv[2]); const manifest = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
(async () => {
 let written=0, existing=0, failed=0;
 for (const item of manifest) {
  if (fs.existsSync(item.out)) { existing++; continue; }
  try {
   const imgs=await Promise.all(item.paths.map(p=>sharp(p).rotate().resize(600,600,{fit:'contain',background:'#ffffff'}).png().toBuffer()));
   const combined=sharp({create:{width:600*imgs.length,height:600,channels:3,background:'#ffffff'}}).composite(imgs.map((input,i)=>({input,left:600*i,top:0})));
   const temp=item.out+'.tmp'; await combined.png().toFile(temp); fs.renameSync(temp,item.out); written++;
  } catch(e) { console.error('REVIEW_ERROR',item.slug,e.message); failed++; }
 }
 console.log(JSON.stringify({written,existing,failed})); if(failed) process.exitCode=1;
})();
