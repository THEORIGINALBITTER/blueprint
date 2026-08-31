# Demo-Cases

Die Demo-Szenarien werden als eigenständige Markdown-Dateien gepflegt. Jede Datei beschreibt genau einen Kundenfall und bleibt unabhängig von den strategischen Grundlagen (`01`–`03`).

## Namenskonvention

`NN-kurzer-case-slug.md`

Beispiele:

- `01-architectural-interiors.md`
- `02-industrial-transformation.md`

## Technische Zuordnung

Die Oberfläche kann Cases über eine stabile ID laden:

```text
memo.html?topic=demo&case=industrial-transformation
```

Die Case-Auswahl sollte später aus einer kleinen Registry kommen. Dadurch bleiben Menü, Case-Übersicht und Editor unabhängig voneinander und neue Szenarien benötigen nur eine neue Markdown-Datei plus Registry-Eintrag.
