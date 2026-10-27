import { readFileSync } from 'node:fs';

const cases = JSON.parse(readFileSync('E:/破甲工作区/packrivo-wp/php-parity.json', 'utf8'));

let worst = 0;
for (const [i, row] of cases.entries()) {
  const payload = {
    ...row.input,
    name: 'Parity Check',
    email: 'parity@example.com',
    company: 'Parity Co',
    country: 'United States',
    notes: 'php vs ts parity',
    source: 'parity',
  };
  const res = await fetch('http://127.0.0.1:3101/api/quotes', {
    method: 'POST',
    headers: { 'content-type': 'application/json', 'x-forwarded-for': `198.51.100.${10 + i}` },
    body: JSON.stringify(payload),
  });
  const js = await res.json();
  const delta = Math.abs(js.unit - row.unit);
  worst = Math.max(worst, delta);
  console.log(
    `${row.input.style.padEnd(8)} qty ${String(row.input.quantity).padStart(6)}  php ${String(row.unit).padStart(6)}  ts ${String(js.unit).padStart(6)}  Δ ${delta.toFixed(4)}`,
  );
}
console.log(`\nmax delta: ${worst.toFixed(4)} → ${worst < 0.005 ? 'PASS' : 'FAIL'}`);
