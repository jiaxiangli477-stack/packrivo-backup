import { chromium } from 'playwright-core';

const browser = await chromium.launch({ channel: 'msedge', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('pageerror', (e) => errors.push(String(e).slice(0, 160)));

await page.goto('http://127.0.0.1:8899/', { waitUntil: 'networkidle' });
await page.waitForTimeout(500);
await page.screenshot({ path: 'shots/wp-widget-1.png', fullPage: false });

// step 1 → pick rigid
await page.getByRole('button', { name: /Rigid box/ }).first().click();
await page.getByRole('button', { name: 'Continue' }).click();
await page.waitForTimeout(200);

// step 2 → preset
await page.getByRole('button', { name: /24×18×7/ }).click();
await page.getByRole('button', { name: 'Continue' }).click();
await page.waitForTimeout(200);

// step 3 → soft touch
await page.getByRole('button', { name: 'Soft-touch lamination' }).click();
await page.getByRole('button', { name: 'Continue' }).click();
await page.waitForTimeout(200);

// step 4 → 2500
await page.getByRole('button', { name: '2,500' }).first().click();
await page.getByRole('button', { name: 'Continue' }).click();
await page.waitForTimeout(300);

const priceText = await page.locator('.pkq-price').first().innerText();
console.log('widget unit price:', priceText);

await page.fill('input[name="name"]', 'Widget Test');
await page.fill('input[name="email"]', 'widget@example.com');
await page.fill('input[name="company"]', 'Widget Co');
await page.click('.pkq-submit');
await page.waitForSelector('.pkq-h3:has-text("Quote requested")', { timeout: 15000 });
const ref = await page.locator('.pkq-big').first().innerText();
console.log('order ref:', ref);
await page.screenshot({ path: 'shots/wp-widget-done.png', fullPage: false });

const orders = await page.evaluate(() => fetch('/orders.jsonl').then((r) => r.text()));
console.log('orders.jsonl lines:', orders.trim().split('\n').length);
console.log('last order:', orders.trim().split('\n').slice(-1)[0]);
console.log('page errors:', errors.length ? errors : 'none');

await browser.close();
