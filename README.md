# The 0-to-1 Blueprint

Private Blueprint-Seite mit einem passwortgeschützten Browser-Editor.

## Lokal starten

```bash
cd blueprint
npm run dev
```

Danach: `http://127.0.0.1:8787` und für den Editor `http://127.0.0.1:8787/admin/`.

Vor dem Start in `config.mjs` ein langes eigenes Passwort setzen.

`npm run dev` baut zuerst das eingebettete ZenOrbit-Menü, startet danach den Node-Server und Tailwinds Watch-Modus zusammen. Für einzelne Builds stehen `npm run orbit:build` und `npm run css:build` bereit.

## Gemeinsames Layout

`src/site-layout.js` ist die Layout-Master-Datei. Sie steuert Header und Footer auf Blueprint, Memo und im angemeldeten Editor zentral. Die übrigen Frontend-Controller liegen ebenfalls in `src/`; redaktionelle Markdown-Dokumente werden gesammelt in `docs/` gepflegt.

## Veröffentlichung

Das Projekt benötigt für den Editor einen Node-kompatiblen Hoster, zum Beispiel Railway, Render oder einen eigenen Server. Die Subdomain kann anschließend auf diesen Hoster zeigen. Für ein klassisches PHP-Webhosting bleibt alternativ die bereits enthaltene PHP-Variante verfügbar.

Nach dem Deployment ist der Editor unter `https://strategy.deinedomain.com/admin/` erreichbar. Änderungen dort mit **„Änderungen veröffentlichen“** speichern; sie sind sofort auf der öffentlichen Seite sichtbar.

`private/` und `config.php` sind durch die mitgelieferten `.htaccess`-Regeln geschützt. Beim verwendeten Hosting müssen Apache-`.htaccess`-Dateien erlaubt und PHP-Schreibrechte für den Ordner `private/` vorhanden sein.

Die öffentliche Seite sowie der Editor sind mit `noindex, nofollow` von der Suchmaschinen-Indexierung ausgenommen.
# blueprint
