import fs from 'node:fs';
const p = 'E:/破甲工作区/.tmp/packrivo-28m/diag-home-cards.json';
const d = JSON.parse(fs.readFileSync(p, 'utf8').replace(/^\uFEFF/, ''));
console.log('== home_specs 改前 7 项 ==');
for (const s of d.specs) {
  console.log(`${s.slug}  status=${s.status || 'MISSING'}  cover=${s.cover ? 'Y' : 'N'}  style=${s.style || '-'}  price=${s.price === null ? 'NULL' : s.price}  missing=${s.missing.join(',') || 'none'}`);
}
console.log(`\n== 封面+价格都齐（可直接上首页）: ${d.card_ready.length} ==`);
for (const x of d.card_ready) console.log(`  ${x.slug} | ${x.style} | $${x.price} | ${x.title}`);
console.log(`\n== 有封面缺价格: ${d.cover_only.length} ==`);
for (const x of d.cover_only) console.log(`  ${x.slug} | style=${x.style || '-'}`);
console.log(`\n== 有价格缺封面: ${d.price_only.length} ==`);
for (const x of d.price_only) console.log(`  ${x.slug} | ${x.style} | $${x.price}`);
console.log(`\n== 产品页总数: ${d.all_products.length} ==`);
