// 28m 段1：首页卡片实测——标题、From 价格、href、hero 最低价
const url = process.argv[2] || 'http://localhost:8081/';
const res = await fetch(url, { redirect: 'follow' });
const html = await res.text();
const strip = (s) => s.replace(/<[^>]+>/g, '').replace(/&#0?39;/g, "'").replace(/&amp;/g, '&').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();

const cards = [...html.matchAll(/<article class="pk-product-card">([\s\S]*?)<\/article>/g)].map((m) => {
  const b = m[1];
  const title = strip((b.match(/<span class="pk-card-title">([\s\S]*?)<\/span>/) || [, ''])[1]);
  const group = strip((b.match(/<span class="pk-product-card-group">([\s\S]*?)<\/span>/) || [, ''])[1]);
  const price = strip((b.match(/<strong>([\s\S]*?)<\/strong>/) || [, ''])[1]);
  const href = (b.match(/<a href="([^"]+)"[^>]*>Configure|<a href="([^"]+)"[^>]*>Get a price/) || null);
  const allHrefs = [...b.matchAll(/href="([^"]+)"/g)].map((x) => x[1]);
  const cta = allHrefs[allHrefs.length - 1] || '';
  const img = (b.match(/<img src="([^"]+)"/) || [, ''])[1];
  return { title, group, price, cta: cta.replace(/&amp;/g, '&'), img };
});

const heroFrom = strip((html.match(/<dt>From[^<]*<\/dt><dd>([\s\S]*?)<\/dd>/) || [, ''])[1]);
const h1 = strip((html.match(/<h1[^>]*>([\s\S]*?)<\/h1>/) || [, ''])[1]);

console.log(JSON.stringify({ url, status: res.status, h1, heroFrom, cardCount: cards.length, cards }, null, 2));
