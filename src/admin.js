const groups={"Identität & Einstieg":['brand','meta','year','heroPrefix','heroEmphasis','heroSuffix','heroSubline','heroSetup','quote'],Fundament:['foundationLead','principle1Title','principle1Text','principle2Title','principle2Text','principle3Title','principle3Text'],Rollen:['strategyName','strategyRole','strategyText','creativeName','creativeRole','creativeText'],Akquise:['acquisitionLead','acquisitionLead1','acquisition1Title','acquisition1Text','acquisition2Title','acquisition2Text'],"Realitätscheck & Abschluss":['realityPrefix','realityEmphasis','risk1Title','risk1Text','risk2Title','risk2Text','risk3Title','risk3Text','closingPrefix','closingEmphasis','closingText','status','footerNote']};
const labels={brand:'Markenname',meta:'Kopfzeile',year:'Jahr',heroPrefix:'Titel, erster Teil',heroEmphasis:'Titel, kursiver Teil',heroSuffix:'Titel, letzter Teil',heroSubline:'Unterzeile',heroSetup:'Setup-Zeile',quote:'Leitsatz',foundationLead:'Einleitung',principle1Title:'Prinzip I — Titel',principle1Text:'Prinzip I — Text',principle2Title:'Prinzip II — Titel',principle2Text:'Prinzip II — Text',principle3Title:'Prinzip III — Titel',principle3Text:'Prinzip III — Text',strategyName:'Strategy — Name',strategyRole:'Strategy — Rolle',strategyText:'Strategy — Beschreibung',creativeName:'Creative — Name',creativeRole:'Creative — Rolle',creativeText:'Creative — Beschreibung',acquisitionLead:'Akquise — Einleitung',acquisitionLead1:'Akquise — Zusatz',acquisition1Title:'Akquise A — Titel',acquisition1Text:'Akquise A — Text',acquisition2Title:'Akquise B — Titel',acquisition2Text:'Akquise B — Text',realityPrefix:'Realität — erster Teil',realityEmphasis:'Realität — kursiger Teil',risk1Title:'Hürde 01 — Titel',risk1Text:'Hürde 01 — Gegenmaßnahme',risk2Title:'Hürde 02 — Titel',risk2Text:'Hürde 02 — Gegenmaßnahme',risk3Title:'Hürde 03 — Titel',risk3Text:'Hürde 03 — Gegenmaßnahme',closingPrefix:'Abschluss — erster Teil',closingEmphasis:'Abschluss — kursiger Teil',closingText:'Abschluss — Text',status:'Status',footerNote:'Footer-Notiz'};
let content={},csrf='';const login=document.querySelector('#login'),editor=document.querySelector('#editor'),toast=document.querySelector('#toast');
const notify=t=>{toast.textContent=t;toast.style.display='block';setTimeout(()=>toast.style.display='none',4000)};
const api=async(url,options={})=>{const r=await fetch(url,options),data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.error||'Anfrage fehlgeschlagen.');return data};
document.querySelector('#login-form').addEventListener('submit',async e=>{e.preventDefault();try{const data=await api('/api/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:document.querySelector('#password').value})});csrf=data.csrf;openEditor()}catch(error){document.querySelector('#login-message').textContent=error.message}});
async function openEditor(){try{content=await api('/api/editor-content');login.hidden=true;editor.hidden=false;const form=document.querySelector('#editor-form');form.innerHTML='';Object.entries(groups).forEach(([name,keys])=>{const section=document.createElement('section');section.innerHTML=`<h2>${name}</h2>`;keys.forEach(key=>{const row=document.createElement('div');row.className='field';const label=document.createElement('label');label.htmlFor=key;label.textContent=labels[key]||key;const input=document.createElement((content[key]||'').length>90?'textarea':'input');input.id=key;input.dataset.key=key;input.value=content[key]||'';row.append(label,input);section.append(row)});form.append(section)});await loadDocuments();await loadGit()}catch(error){notify(error.message)}}
async function loadDocuments() {
  const existing = document.querySelector('#documents');
  if (existing) existing.remove();

  const section = document.createElement('section');
  section.id = 'documents';

  section.innerHTML = `
    <h2>Markdown-Dokumente</h2>

    <div class="field">
      <label for="document-select">Dokument auswählen</label>
      <select id="document-select">
        <option value="">Dokument auswählen …</option>
      </select>
    </div>

    <div class="field">
      <label for="document-content">Inhalt</label>
      <textarea
        id="document-content"
        rows="24"
        spellcheck="false"
        placeholder="Markdown-Inhalt …"
      ></textarea>
    </div>

    <div class="actions">
      <button class="publish" id="save-document" type="button">
        Markdown speichern
      </button>
      <button type="button" id="new-document">
        + Neues Dokument
      </button>
      <button type="button" id="rename-document">
        Umbenennen
      </button>
      <button type="button" id="delete-document">
        Löschen
      </button>
    </div>
  `;

  const form = document.querySelector('#editor-form');
  form.appendChild(section);

  try {
    const documents = await api('/api/docs');

    const select = section.querySelector('#document-select');

    for (const document of documents) {
      const option = document.createElement('option');
      option.value = document.path;
      option.textContent = `${document.title} — ${document.path}`;
      select.appendChild(option);
    }

    const loadDocument = async () => {
      const path = select.value;
      const textarea = section.querySelector('#document-content');

      if (!path) {
        textarea.value = '';
        return;
      }

      try {
        const data = await api(
          `/api/docs/${encodeURIComponent(path)}`
        );

        textarea.value = data.content || '';
      } catch (error) {
        notify(error.message);
      }
    };

    select.addEventListener('change', loadDocument);

    if (documents.length > 0) {
      select.value = documents[0].path;
      await loadDocument();
    }
  } catch (error) {
    notify(`Markdown-Dokumente konnten nicht geladen werden: ${error.message}`);
  }
}
async function refreshDocuments(selected=''){const select=document.querySelector('#document-select');if(!select)return;const list=await api('/api/docs');select.innerHTML='';list.forEach(document=>{const option=document.createElement('option');option.value=document.path;option.textContent=`${document.title} — ${document.path}`;select.append(option)});if(selected)select.value=selected;const load=async()=>{if(!select.value)return;document.querySelector('#document-content').value=(await api(`/api/docs/${encodeURIComponent(select.value)}`)).content};select.onchange=load;if(select.value)await load()}
async function loadGit(){const section=document.createElement('section');section.id='git-editor';section.innerHTML='<h2>Git</h2><p id="git-branch">Prüfe Repository …</p><pre id="git-diff" style="white-space:pre-wrap;overflow:auto;max-height:420px"></pre><div class="field"><label for="git-message">Commit-Nachricht</label><input id="git-message" value="Update Markdown content"></div><div style="display:flex;gap:12px;flex-wrap:wrap"><button type="button" class="publish" id="git-refresh">Status aktualisieren</button><button type="button" class="publish" id="git-commit">Commit</button><button type="button" class="publish" id="git-push">Commit + Push</button></div><p id="git-message-status" class="message"></p>';document.querySelector('#editor-form').after(section);await refreshGit()}
async function refreshGit(){const branch=document.querySelector('#git-branch'),diff=document.querySelector('#git-diff');if(!branch)return;try{const data=await api('/api/git/status');branch.textContent=data.clean?`Branch: ${data.branch} — Arbeitsverzeichnis sauber.`:`Branch: ${data.branch} — Änderungen vorhanden.`;diff.textContent=data.diff||'Keine Änderungen unter docs/.'}catch(error){branch.textContent=error.message}}
async function gitAction(action){const message=document.querySelector('#git-message').value.trim(),status=document.querySelector('#git-message-status');try{const data=await api(`/api/git/${action}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,message})});status.textContent=data.pushed?`Commit ${data.commit} erstellt und gepusht.`:`Commit ${data.commit} erstellt.`;await refreshGit()}catch(error){status.textContent=error.message}}
document.addEventListener('click',async event=>{const id=event.target.id;try{if(id==='save-document'){const select=document.querySelector('#document-select'),textarea=document.querySelector('#document-content');await api(`/api/docs/${encodeURIComponent(select.value)}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,content:textarea.value})});notify(`${select.value} gespeichert.`);await refreshGit()}if(id==='new-document'){const path=prompt('Dateiname, z. B. 05-neu.md:');if(!path)return;const data=await api('/api/docs',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,path,content:'---\ntitle: Neues Dokument\n---\n\n# Neues Dokument\n\n'})});notify('Dokument angelegt.');await refreshDocuments(data.path);await refreshGit()}if(id==='rename-document'){const select=document.querySelector('#document-select');if(!select?.value)return;const path=prompt('Neuer Dateiname:',select.value);if(!path||path===select.value)return;const data=await api(`/api/docs/${encodeURIComponent(select.value)}`,{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,path})});notify('Dokument umbenannt.');await refreshDocuments(data.path);await refreshGit()}if(id==='delete-document'){const select=document.querySelector('#document-select');if(!select?.value||!confirm(`"${select.value}" wirklich löschen?`))return;await api(`/api/docs/${encodeURIComponent(select.value)}`,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf})});notify('Dokument gelöscht.');await refreshDocuments();await refreshGit()}if(id==='git-refresh')await refreshGit();if(id==='git-commit')await gitAction('commit');if(id==='git-push')await gitAction('push');if(id==='publish'){const next={};document.querySelectorAll('[data-key]').forEach(e=>next[e.dataset.key]=e.value);await api('/api/content',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,content:next})});content=next;notify('Veröffentlicht.')}if(id==='logout'){await fetch('/api/logout',{method:'POST'});location.reload()}}catch(error){notify(error.message)}});
