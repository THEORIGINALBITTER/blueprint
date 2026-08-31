import { createServer } from 'node:http';
import { readFile, readdir, writeFile } from 'node:fs/promises';
import { randomBytes, timingSafeEqual } from 'node:crypto';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';
import { adminPassword } from './config.mjs';

const root = fileURLToPath(new URL('.', import.meta.url));
const contentPath = join(root, 'private', 'content.json');
const docsPath = join(root, 'docs');
const sessions = new Map();
const port = Number(process.env.PORT || 8787);
const mime = { '.html':'text/html; charset=utf-8', '.js':'text/javascript; charset=utf-8', '.css':'text/css; charset=utf-8', '.json':'application/json; charset=utf-8', '.md':'text/markdown; charset=utf-8', '.svg':'image/svg+xml', '.ico':'image/x-icon' };

const json = (res, code, value) => { res.writeHead(code, { 'Content-Type':'application/json; charset=utf-8', 'Cache-Control':'no-store' }); res.end(JSON.stringify(value)); };
const body = (req) => new Promise((resolve, reject) => { let data=''; req.on('data', c => { data += c; if (data.length > 250000) req.destroy(); }); req.on('end', () => { try { resolve(JSON.parse(data || '{}')); } catch { reject(new Error('Ungültige Anfrage.')); } }); });
const sessionFor = (req) => { const id = /(?:^|; )blueprint_session=([^;]+)/.exec(req.headers.cookie || '')?.[1]; return id && sessions.get(id); };
const same = (a, b) => { const x=Buffer.from(a), y=Buffer.from(b); return x.length === y.length && timingSafeEqual(x, y); };
const requireAuth = (req, res) => { const session = sessionFor(req); if (!session) { json(res, 401, { error:'Nicht angemeldet.' }); return null; } return session; };

createServer(async (req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
  try {
    if (req.method === 'POST' && url.pathname === '/api/login') {
      const { password } = await body(req);
      if (adminPassword === 'CHANGE-THIS-TO-A-LONG-UNIQUE-PASSWORD' || typeof password !== 'string' || !same(adminPassword, password)) return json(res, 401, { error:'Passwort stimmt nicht oder wurde noch nicht konfiguriert.' });
      const id = randomBytes(24).toString('hex'); const csrf = randomBytes(24).toString('hex'); sessions.set(id, { csrf });
      res.writeHead(200, { 'Content-Type':'application/json; charset=utf-8', 'Set-Cookie':`blueprint_session=${id}; HttpOnly; SameSite=Strict; Path=/` }); return res.end(JSON.stringify({ csrf }));
    }
    if (req.method === 'POST' && url.pathname === '/api/logout') { const id=/(?:^|; )blueprint_session=([^;]+)/.exec(req.headers.cookie || '')?.[1]; if(id) sessions.delete(id); res.writeHead(204, {'Set-Cookie':'blueprint_session=; Max-Age=0; Path=/'}); return res.end(); }
    if (req.method === 'GET' && url.pathname === '/api/content') return json(res, 200, JSON.parse(await readFile(contentPath, 'utf8')));
    if (req.method === 'GET' && url.pathname === '/api/editor-content') { if (!requireAuth(req,res)) return; return json(res, 200, JSON.parse(await readFile(contentPath, 'utf8'))); }
    if (req.method === 'GET' && url.pathname === '/api/docs') {
      if (!requireAuth(req,res)) return;
      const files = (await readdir(docsPath, { withFileTypes: true }))
        .filter(entry => entry.isFile() && /^\d{2}-[a-z0-9-]+\.md$|^memo\.md$/i.test(entry.name))
        .map(entry => entry.name).sort();
      return json(res, 200, files);
    }
    if (req.method === 'GET' && url.pathname === '/api/memo') {
      const fileName = String(url.searchParams.get('file') || '');
      if (!/^(?:\d{2}-[a-z0-9-]+|memo)\.md$/i.test(fileName)) return json(res, 400, { error:'Ungültige Memo-Datei.' });
      const candidates = [join(docsPath, fileName), join(docsPath, 'cases', fileName)];
      for (const filePath of candidates) {
        try { const content = await readFile(filePath, 'utf8'); res.writeHead(200, { 'Content-Type':'text/markdown; charset=utf-8', 'Cache-Control':'no-store' }); return res.end(content); } catch {}
      }
      res.writeHead(404); return res.end('Not found');
    }
    const docMatch = url.pathname.match(/^\/api\/docs\/([^/]+)$/);
    if (docMatch && (req.method === 'GET' || req.method === 'POST')) {
      if (!requireAuth(req,res)) return;
      const fileName = decodeURIComponent(docMatch[1]);
      if (!/^(?:\d{2}-[a-z0-9-]+|memo)\.md$/i.test(fileName)) return json(res, 400, { error:'Ungültige Dokumentdatei.' });
      const filePath = join(docsPath, fileName);
      if (req.method === 'GET') return json(res, 200, { fileName, content: await readFile(filePath, 'utf8') });
      const { csrf, content } = await body(req);
      const session = sessionFor(req);
      if (!session || !same(session.csrf, String(csrf || ''))) return json(res, 403, { error:'Sitzung abgelaufen. Bitte neu anmelden.' });
      if (typeof content !== 'string' || content.length > 100000) return json(res, 422, { error:'Ungültiger Dokumentinhalt.' });
      await writeFile(filePath, content.endsWith('\n') ? content : `${content}\n`);
      return json(res, 200, { ok:true });
    }
    if (req.method === 'POST' && url.pathname === '/api/content') {
      const session = requireAuth(req,res); if (!session) return;
      const { csrf, content } = await body(req); if (!same(session.csrf, String(csrf || ''))) return json(res, 403, { error:'Sitzung abgelaufen. Bitte neu anmelden.' });
      const existing = JSON.parse(await readFile(contentPath, 'utf8'));
      if (!content || typeof content !== 'object' || Object.keys(content).length !== Object.keys(existing).length || Object.keys(content).some(key => !(key in existing) || typeof content[key] !== 'string' || content[key].length > 3000)) return json(res, 422, { error:'Ungültiger Inhalt.' });
      await writeFile(contentPath, JSON.stringify(Object.fromEntries(Object.keys(existing).map(key => [key, content[key].trim()])), null, 2) + '\n');
      return json(res, 200, { ok:true });
    }
    let requested = url.pathname === '/' ? 'index.html' : url.pathname === '/admin/' ? 'admin.html' : url.pathname.slice(1);
    requested = normalize(requested).replace(/^([/\\])+/, ''); const file = join(root, requested);
    if (!file.startsWith(root) || requested.includes('private') || requested.startsWith('config') || requested.endsWith('.php')) { res.writeHead(404); return res.end(); }
    const fileData = await readFile(file); res.writeHead(200, { 'Content-Type': mime[extname(file)] || 'application/octet-stream' }); res.end(fileData);
  } catch (error) { if (error.code === 'ENOENT') { res.writeHead(404); res.end('Not found'); } else { console.error(error); json(res, 500, { error:'Serverfehler.' }); } }
}).listen(port, '127.0.0.1', () => console.log(`Blueprint läuft auf http://127.0.0.1:${port}\nEditor: http://127.0.0.1:${port}/admin/`));
