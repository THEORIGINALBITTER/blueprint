const slug = value => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
const inline = value => value.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\*(.+?)\*/g, '<em>$1</em>');
const memo = document.querySelector('#memo');
const topics = {
  bedarf: './docs/00-bedarf.md',
  model: './docs/01-modell.md',
  skalierung: './docs/02-skalierung.md',
  nische: './docs/03-nische.md',
  demo: './docs/04-demo-szenario.md'
};
const topic = new URLSearchParams(location.search).get('topic');
const selectedCase = new URLSearchParams(location.search).get('case');
const defaultMemoPath = topics[topic] || './docs/memo.md';
let activeMemoFileName = 'memo.md';

async function resolveMemo() {
  if (topic !== 'demo') return { path: defaultMemoPath, cases: [] };
  const response = await fetch('./docs/cases/index.json', { cache: 'no-store' });
  if (!response.ok) return { path: defaultMemoPath, cases: [] };
  const cases = await response.json();
  const active = cases.find(item => item.id === selectedCase) || cases[0];
  const path = active ? `./docs/cases/${active.file}` : defaultMemoPath;
  return { path, cases };
}

resolveMemo().then(({ path: memoPath, cases }) => {
  if (cases.length) {
    const picker = document.createElement('div');
    picker.className = 'case-grid';
    picker.innerHTML = cases.map((item, index) => `<a class="case-card${item.id === selectedCase || (!selectedCase && index === 0) ? ' is-active' : ''}" href="./memo.html?topic=demo&case=${encodeURIComponent(item.id)}"><span>${item.number}</span><strong>${item.title}</strong><small>${item.sector || 'Demo-Case'}</small></a>`).join('');
    memo.before(picker);
  }
  const fileName = memoPath.split('/').pop();
  activeMemoFileName = fileName;
  const inlineMarkdown = window.BLUEPRINT_MEMOS?.[fileName] || window.BLUEPRINT_CASES?.[fileName];
  const freshPath = `${memoPath}${memoPath.includes('?') ? '&' : '?'}v=${Date.now()}`;
  // The production build embeds every memo. Use it first so static hosts never
  // need to expose or interpret Markdown files at runtime.
  if (inlineMarkdown) return inlineMarkdown;
  const apiPath = `/api/memo?file=${encodeURIComponent(fileName)}&ts=${Date.now()}`;
  const phpPath = `./doc.php?file=${encodeURIComponent(fileName)}&ts=${Date.now()}`;
  const paths = [apiPath, freshPath, phpPath];
  return (async () => {
    const failures = [];
    for (const requestPath of paths) {
      try {
        const response = await fetch(requestPath, { cache: 'no-store' });
        if (response.ok) return response.text();
        failures.push(`${requestPath}: HTTP ${response.status}`);
      } catch (error) {
        failures.push(`${requestPath}: ${error.message || 'Netzwerkfehler'}`);
      }
    }
    throw new Error(failures.join(' | '));
  })().catch(error => {
    console.error(`Memo „${fileName}" konnte nicht geladen werden.`, error);
    return inlineMarkdown || Promise.reject(error);
  });
}).then(markdown => {
  memo.innerHTML = markdown.split('\n').map(line => {
    if (line.startsWith('# ')) return `<p class="memo-kicker">Private reading copy</p><h1>${inline(line.slice(2))}</h1>`;
    if (line.startsWith('## ')) { const title = line.slice(3); return `<h2 id="${slug(title)}">${inline(title)}</h2>`; }
    if (line.startsWith('### ')) return `<h3>${inline(line.slice(4))}</h3>`;
    if (line.startsWith('> ')) return `<blockquote>${inline(line.slice(2))}</blockquote>`;
    if (line === '---') return '<hr>';
    if (/^\d+\. /.test(line)) return `<p class="memo-list">${inline(line.replace(/^\d+\. /, ''))}</p>`;
    if (line.startsWith('- ')) return `<p class="memo-list">${inline(line.slice(2))}</p>`;
    return line.trim() ? `<p>${inline(line)}</p>` : '';
  }).join('');
  const edit = document.createElement('a');
  edit.className = 'memo-edit-link';
  edit.href = `./admin/?doc=${encodeURIComponent(activeMemoFileName)}`;
  edit.textContent = 'Dokument bearbeiten ↗';
  memo.append(edit);
  if (location.hash) document.getElementById(location.hash.slice(1))?.scrollIntoView({ block: 'start' });
}).catch(error => {
  console.error('Memo konnte nicht gerendert werden.', error);
  memo.innerHTML = '<p>Das Memo konnte nicht geladen werden. Details stehen in der Browser-Konsole.</p>';
});
