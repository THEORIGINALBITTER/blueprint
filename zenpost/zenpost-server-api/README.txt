ZenPost Server API Setup
=========================

1) Alle Dateien aus diesem ZIP in /api/zenpost/ auf deinem Server hochladen.
2) setup.php einmalig im Browser aufrufen:
   https://deine-domain.de/api/zenpost/setup.php
3) DB-Daten eintragen und speichern.
4) Danach setup.php sofort sperren, entfernen oder nur einmalig nutzbar machen.
5) In ZenPost API Tab eintragen:
   URL: https://denisbitter.de/stage01/api
   Upsert: /save_articles.php
   Upload: /upload_images.php
   Ping: /ping.php
6) API testen + Test-Insert senden.
