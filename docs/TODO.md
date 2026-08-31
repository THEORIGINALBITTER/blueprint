# TODO — ZenPost Studio Verbindung

## Markdown-Bearbeitung aus Blueprint heraus

- [ ] Orbit-Menü mit ZenPost Studio verbinden.
- [ ] Menüpunkt soll die passende Markdown-Datei in ZenPost Studio öffnen.
- [ ] Betroffene Dateien liegen weiterhin in `blueprint/docs/`:
  - `01-modell.md`
  - `02-skalierung.md`
  - `03-nische.md`
  - `04-demo-szenario.md`
  - `memo.md`
- [ ] Änderungen aus ZenPost Studio zurück nach `blueprint/docs/` speichern.
- [ ] Für die lokale Entwicklung zunächst eine direkte Ordner-/Dateiverbindung prüfen.
- [ ] Danach sichere Variante für veröffentlichte Installationen festlegen (Deep-Link oder lokale API).
- [ ] Authentifizierung und Schreibrechte klären, bevor Dateien überschrieben werden.
- [ ] Nach dem Speichern sicherstellen, dass Blueprint die aktualisierte Markdown-Datei ohne Cache lädt.

**Status:** zurückgestellt — Umsetzung folgt über einen anderen Ansatz.

**Hinweis:** Die Online-Bearbeitung der Markdown-Dateien ist jetzt im Blueprint-Admin-Editor umgesetzt. Die direkte ZenPost-Studio-Verbindung bleibt als separates To-do bestehen.
