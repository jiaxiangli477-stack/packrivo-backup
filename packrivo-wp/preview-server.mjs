import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';

const ROOT = 'E:/破甲工作区/packrivo-wp/packrivo-home-v4';
const PORT = Number(process.env.PORT || 8090);

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.webp': 'image/webp',
  '.png': 'image/png',
};

createServer(async (req, res) => {
  const url = decodeURIComponent((req.url || '/').split('?')[0]);
  const rel = url === '/' ? '/preview/index.html' : url;
  const file = normalize(join(ROOT, rel));
  if (!file.startsWith(normalize(ROOT))) {
    res.writeHead(403).end('forbidden');
    return;
  }
  try {
    const body = await readFile(file);
    res.writeHead(200, { 'content-type': TYPES[extname(file)] || 'application/octet-stream' });
    res.end(body);
  } catch {
    res.writeHead(404, { 'content-type': 'text/plain; charset=utf-8' }).end('not found: ' + rel);
  }
}).listen(PORT, '127.0.0.1', () => console.log('theme preview on http://127.0.0.1:' + PORT + '/'));
