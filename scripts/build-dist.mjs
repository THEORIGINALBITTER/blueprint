import { cp, mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

const root = new URL('..', import.meta.url).pathname;
const dist = join(root, 'dist');
await rm(dist, { recursive: true, force: true });
await mkdir(dist, { recursive: true });

const files = ['index.html', 'memo.html', 'admin.html', 'public.css', 'style.css', 'content.php', 'doc.php', 'config.php', '.htaccess'];
const directories = ['assets', 'docs', 'private', 'src', 'admin', 'orbit-dist'];
for (const file of files) await cp(join(root, file), join(dist, file));
for (const directory of directories) await cp(join(root, directory), join(dist, directory), { recursive: true });

// Embed case Markdown as a deployment-safe fallback. This keeps the memo usable
// on hosts that do not serve .md files while still treating docs/cases as source.
const caseIndex = JSON.parse(await readFile(join(root, 'docs', 'cases', 'index.json'), 'utf8'));
const caseEntries = await Promise.all(caseIndex.map(async item => [item.file, await readFile(join(root, 'docs', 'cases', item.file), 'utf8')]));
const memoEntries = await Promise.all((await readdir(join(root, 'docs')))
  .filter(file => file.endsWith('.md'))
  .map(async file => [file, await readFile(join(root, 'docs', file), 'utf8')]));
const memoScript = `window.BLUEPRINT_MEMOS = ${JSON.stringify(Object.fromEntries([...memoEntries, ...caseEntries]))};\n`;
await writeFile(join(dist, 'src', 'cases-data.js'), memoScript);

// Bake the current content into the static HTML as a reliable offline/deployment fallback.
const content = JSON.parse(await readFile(join(root, 'private', 'content.json'), 'utf8'));
const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[char]);
let indexHtml = await readFile(join(dist, 'index.html'), 'utf8');
for (const [key, value] of Object.entries(content)) {
  const pattern = new RegExp(`(<[^>]*data-field=["']${key}["'][^>]*>)[\\s\\S]*?(</[^>]+>)`);
  indexHtml = indexHtml.replace(pattern, `$1${escapeHtml(value)}$2`);
}
await writeFile(join(dist, 'index.html'), indexHtml);

console.log(`Build erstellt: ${dist}`);
