# Fuentes autoalojadas

Se sirven desde aquí, no desde Google Fonts. Motivo: **RGPD** — con `@import`
remoto cada visitante enviaba su IP a Google (LG München, ene-2022). Autoalojadas
no sale ninguna petición a terceros, y además se ahorra una petición
render-blocking encadenada.

## Qué hay

| Familia | Uso | Ficheros |
|---|---|---|
| Source Serif 4 | display (`font-serif`) | `source-serif-4-var-*` (variable: sirve 400/600/700) + `source-serif-4-400-italic-*` |
| Barlow Semi Condensed | cuerpo (`font-sans`) | `barlow-semi-condensed-{400,500,600,700}-*` |

Solo subsets **latin** y **latin-ext**: el sitio va en es/en/fr/it/pt, así que
cyrillic/greek/vietnamese sobran. Google servía 36 `@font-face`; aquí son 16.
Total: 12 ficheros, ~228 KB.

Los `@font-face` viven en `resources/css/app.css` y los pesos deben coincidir con
`tailwind.config.js` → `theme.extend.fontFamily`.

## Regenerar

Si cambian los pesos, pedir a Google el CSS con un User-Agent moderno (para que
devuelva woff2), quedarse con los bloques `latin` y `latin-ext`, descargar cada
URL única y reescribir los `src` a `/fonts/`. Ojo: Source Serif 4 es **variable**,
una misma URL sirve varios pesos — no asumir un fichero por peso.

## Licencia

Ambas son SIL Open Font License 1.1. Autoalojarlas es redistribuirlas, y la OFL
exige acompañarlas de su texto:

- Source Serif 4 — Copyright 2014 The Source Serif 4 Project Authors. Ver `OFL-SourceSerif4.txt`.
- Barlow Semi Condensed — Copyright 2017 The Barlow Project Authors. Ver `OFL-BarlowSemiCondensed.txt`.
