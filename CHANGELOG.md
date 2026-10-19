# Changelog

## Publicación open source — 2026-10

El código se publica con licencia MIT en un repositorio nuevo, con un único
commit inicial. Cambios respecto a la última versión en producción (1.7.2):

- La app pasa de `version-es/` a la raíz del repositorio.
- Los datos de la empresa que operaba genpic.com salen del código: nombre,
  CIF, domicilio y emails de los textos legales pasan a ser datos de ejemplo;
  el titular y el email de contacto se configuran con `COMPANY_NAME` y
  `CONTACT_EMAIL`.
- `ADMIN_PATH` pasa a `admin` por defecto; `trustHosts` toma el dominio de
  `APP_URL`.
- `robots.txt` y `sitemap.xml` se generan siempre por ruta desde `APP_URL`.
  Se retiran los `public/robots.txt`, `public/sitemap.xml`, `llms*.txt` e
  `index.md` estáticos, que estaban atados a genpic.com.
- La marca de cortesía, los enlaces de los emails y el pie de página usan el
  dominio de `APP_URL`; el texto de la marca de agua sale de `WATERMARK_TEXT`.
- Las muestras de estilo bajan a 1200 px para que el clone sea ligero. El pack
  original de 2048 px se descarga con `scripts/fetch-samples.sh`.
- Sin ID de Google Analytics en el seeder ni en la página de mantenimiento.

## [1.7.2] — 2026-09-05

### El selector de idioma llevaba a URLs que no existen

Reportado sobre `/fr/cierre`. El `<head>` resolvía los `hreflang` por nombre de
ruta (bien) y el desplegable visible pegaba el prefijo del idioma al path actual
(mal). Con slugs localizados el path del idioma actual no existe en los demás:
`/cierre` en francés es `/fr/fermeture`.

No era solo la página de cierre: **las 10 landings tenían el mismo fallo desde
siempre**, tapado por los redirects 301 de `web.php`. Ahora ambos consumen un
único `$localeUrls`, calculado una vez. No pueden divergir porque son el mismo
array. El regex que quita el prefijo del nombre de ruta sale de
`config('genpic.locales')` en vez de llevar `(en|fr|pt|it)` a mano: añadir un
idioma ya no lo rompe.

### hreflang con parámetros espurios

`Route::view()` registra `view` y `status` como parámetros de la ruta, así que
`route()` los serializaba: los `hreflang` de `/cierre` apuntaban a
`/fr/fermeture?view=closed&status=200` mientras el canonical iba limpio. Estaba
ya en producción. La ruta pasa a closure.

### Sitemap
La clave de caché lleva el estado de cierre, así que activar `CLOSED_MODE` lo
regenera solo en vez de seguir anunciando `/create` (que devuelve 410) durante
los 7 días que dura la caché. Era lo que obligaba al `cache:clear` manual.

5 tests nuevos, uno por cada forma de romperlo. Suite: 178.

## [1.7.1] — 2026-09-05

### La página de cierre daba un plazo de descarga vencido

Detectado en producción a los minutos de desplegar 1.7.0: la página decía «los
archivos se conservan hasta el 4 de septiembre de 2026» — el día anterior. Usaba
`CLOSED_MODE_DATE` para dos cosas distintas, y **cerrar las altas no borra lo que
ya existe**: le estaba diciendo a la gente que sus retratos ya no estaban cuando
sí estaban.

- Nueva `CLOSED_DOWNLOADS_UNTIL`, independiente de la fecha de cierre. Vacía se
  deriva sola: fecha de cierre + `GENERATION_SESSION_TTL_DAYS`.
- Un plazo ya vencido **no se anuncia**: la página pasa al texto sin fecha en vez
  de publicar una fecha en pasado. La página envejece sin mentir.
- Una fecha de cierre por venir se cuenta en futuro («a partir del…») y no en
  pasado («desde el…»). El flag ya corta el funnel; el lede solo dice desde
  cuándo es oficial.

### Otros proyectos en la página de cierre

Once proyectos con una línea cada uno, en los cinco idiomas: mboxviewerpro.com,
iskitch.com, flarepurge.com, killexif.com, files.co, reader.me, password.es,
spyonweb.net, dnsgratis.com, linuxgratis.com y meteo.es. Las descripciones salen
de lo que dice cada web, no de lo que me imagino que hacen.

La lista vive en `config/genpic.php` (`closing.projects`); añadir uno es una línea
de config más su clave `ui.closing_project_*`. Lista de enlaces, no tarjetas:
`design.md` manda Long Document en la familia Contenido.

4 tests nuevos. Suite: 173.

## [1.7.0] — 2026-09-05

### GenPic cierra — y el código se libera

Un año de experimento: la parte técnica funcionó, la comercial no. Muchísimas
generaciones gratuitas y **cero ventas**. Se cierran las altas y las compras, y
el código pasa a ser software libre con licencia MIT.

**Cierre por flag, no por apagón.** `CLOSED_MODE=true` en el `.env` corta el
funnel sin tumbar la web:

- `/create` y `/checkout` devuelven **410 Gone** con la página de cierre. 410 y
  no 404 ni 503: le dice a Google que estas URLs se retiran para siempre.
- Aviso permanente en todas las páginas, en los cinco idiomas, enlazando a la
  página que explica por qué se cierra.
- El CTA del nav pasa de «Crear mi retrato» a «Mis descargas», que es lo único
  que un visitante todavía puede querer.
- **Siguen funcionando**: galería, estilos, las 10 landings, legales,
  `/my-downloads`, `/portrait` y las descargas firmadas. Quien tenga un retrato
  pendiente puede recuperarlo hasta que expire su plazo de conservación.
- Sesiones y pagos ya en vuelo cuando se active el flag aterrizan bien:
  `create.preview`, `create.status`, `checkout.success` y `checkout.cancel`
  quedan deliberadamente fuera del corte.
- Los emails de abandono y de upgrade se silencian: son reclamo comercial y ya
  no hay nada que comprar. El aviso de expiración **sí sigue**, porque es
  informativo — avisa de que los archivos se van a borrar.
- El sitemap sustituye `/create` por la página de cierre, con `hreflang` en los
  cinco idiomas.

**Nueva página `/cierre`** (`/en/closing`, `/fr/fermeture`, `/pt/encerramento`,
`/it/chiusura`): cuenta qué pasó, cómo recuperar un retrato pendiente y que el
código se publica. Familia *Contenido* de `design.md` — Long Document, prosa,
sin tarjetas ni iconos.

### Añadido
- `LICENSE` — MIT, Color Vivo Internet S.L.
- `plans/012-liberacion-open-source.md` — plan de liberación con la auditoría
  del repo: qué puede salir, qué no, y por qué.
- `docs/capturas-web-2026-09/` — capturas del servicio funcionando, tomadas
  antes de cerrarlo.
- 8 tests nuevos: el funnel cerrado devuelve 410, las descargas y las páginas
  públicas siguen abiertas, y sin el flag no cambia nada. Suite: 169.

### Pendiente de decisión
El enlace al repositorio público sale de `OPENSOURCE_REPO_URL`; mientras esté
vacío, la página de cierre dice que se está preparando.

## [1.6.3] — 2026-07-17

### El cuadro de subir foto salía en blanco

Reportado en `/en/create`: el cuadro funcionaba pero no mostraba nada.

**Causa inmediata**: el CSP roto de 1.6.1 (ver 1.6.2). Con Alpine muerto, el contenido del cuadro nunca aparecía porque vivía dentro de un `<template x-if>`, y **un `<template>` es inerte sin JS**. El `<label>` sí es nativo, de ahí que funcionase pero sin decir qué era.

**Causa de fondo, que el hotfix no arreglaba**: el estado por defecto del cuadro no debe depender de JS. Movido a HTML real (`x-show` en vez de `<template x-if>`); ahora el texto se sirve desde el servidor y Alpine solo lo oculta cuando hay preview. El bloque de preview lleva `x-cloak` para no enseñar una imagen rota si Alpine falla.

### Mejorado
- **El cuadro ahora explica para qué sirve**: icono de subida (SVG propio, no emoji), qué elegir, cómo (arrastrar o clic) y qué formatos acepta.
- **Los consejos de foto suben debajo del cuadro.** Vivían *después*, donde ya no ayudan: para entonces el usuario ya había elegido. Ahora orientan la decisión, en lista legible en vez de en una fila que a 375px rompía en cuatro líneas.
- **El aviso de privacidad se lee.** Es lo que más pesa al decidir subir una foto de tu familia, y estaba a `text-xs` centrado con un candado emoji. Ahora va alineado a la izquierda y con interlineado de lectura.

2 tests nuevos, uno verificado contra el `<template>` anterior. Suite: 161.

## [1.6.2] — 2026-07-17

### Hotfix — el CSP de 1.6.1 rompía Alpine

**1.6.1 aplicó `script-src` sin `'unsafe-eval'` y tumbó toda la interactividad del sitio.** Livewire empaqueta Alpine, y Alpine evalúa sus expresiones con el truco de `AsyncFunction` (`Object.getPrototypeOf(async function(){}).constructor`), equivalente a `new Function()`. Sin `unsafe-eval` el navegador lo bloquea y Alpine muere al arrancar — pero para entonces ya ha retirado los `x-cloak`, así que:

- los desplegables (idioma) se quedaban **abiertos permanentemente**,
- el menú móvil no abría,
- el filtro de `/styles` y el lightbox no respondían,
- **la detección facial del uploader no corría**.

Añadido `'unsafe-eval'`. Test de regresión que falla contra el CSP de 1.6.1, más otro que vigila si Alpine deja de necesitar evaluación dinámica (para poder retirarlo).

### Corregido en la documentación
Con `unsafe-inline` **y** `unsafe-eval`, `script-src` **no impide inyectar scripts**: lo único que aporta es restringir desde qué hosts se puede cargar un `<script src>`. Es defensa en profundidad, no una barrera de XSS. 1.6.1 lo vendía como más de lo que es. Para que fuese una barrera real harían falta nonces en cada bloque inline y el build CSP-friendly de Alpine.

## [1.6.1] — 2026-07-17

### Cierre de pendientes

- **El slider de comparación secuestraba el scroll táctil.** El `input[type=range]` cubre toda la imagen (`inset:0; opacity:0`), así que en móvil se tragaba el gesto vertical: el usuario quedaba atrapado sin poder pasar del retrato. `touch-action: pan-y` deja el scroll al navegador y solo el arrastre horizontal llega al slider. Aplicado en los tres (`preview-panel`, `styles/show`, `gallery/show`).
- **El slider era invisible al foco.** Con `opacity:0`, su anillo de foco no se veía: el usuario de teclado operaba a ciegas. El foco se traslada al handle vía `:has()`.
- **43 de 44 vistas declaran ya el sistema.** `design.md` se amplía con las tres familias que faltaban — antes solo declaraba marketing, así que estampar vistas de app y contenido habría sido una etiqueta vacía. **App** (Workbench: el funnel, checkout, descargas) trae reglas propias: los 8 estados obligatorios, todo estado dinámico anunciado, ningún input dependiendo del placeholder, honestidad de estado. **Contenido** (Long Document: legal, errores). La regla de diversificación solo aplica dentro de marketing: en app y contenido la repetición es el objetivo.
- **`script-src` ya se aplica.** Estaba en Report-Only porque el panel permite inyectar scripts hacia cualquier host y una allowlist fija los rompería en silencio. Ahora la allowlist **se deriva de lo que el admin ha configurado** (`SiteSetting::head_scripts` / `body_scripts`, cacheado 1h), incluyendo `src` con protocolo relativo. Si esa lectura falla, no se emite `script-src`: mejor sin directiva que tumbando el sitio. Lleva `unsafe-inline` (el banner de cookies, GA4 y el JSON-LD son inline), así que **no protege contra inyección de scripts inline** — lo que cierra es cargar un `<script src>` desde un host ajeno.
- `font-src 'self'` y `frame-src 'none'` pasan también a aplicados.

3 tests nuevos (`DesignSystemStampTest`) + 2 que verifican que un script del panel **no** se rompe con el CSP. Suite: 157.

### Pendiente
- `welcome.blade.php`: scaffolding de Laravel, no enrutado, cero referencias. Tira de `fonts.bunny.net`. Candidato a borrar — no se toca sin que lo pidas.
- Cifras y testimonios sin fuente (decisión del propietario).

## [1.6.0] — 2026-07-17

### Accesibilidad del funnel

Los ~24 `major` que arrastraba el funnel desde la auditoría de diseño. El funnel es donde entra el dinero y era la parte menos accesible del sitio.

### Corregido
- **11 inputs dependían solo del `placeholder`.** Un placeholder no es una etiqueta: desaparece al escribir y los lectores de pantalla no lo anuncian de forma fiable. Todos los del funnel llevan ahora `<label>` asociado (`sr-only` donde una etiqueta visible cambiaría el diseño) + `autocomplete` donde aplica.
- **Los inputs de email estaban centrados** (`text-center`), lo que hace saltar el cursor al teclear y deja el valor sin ancla de lectura. Es un fallo de uso, no de gusto.
- **Cero `aria-live` en todo el estado dinámico.** Ahora se anuncian: el banner de error (`role="alert"`), la detección facial completa —que es lo que decide si puedes continuar y era muda—, el paso "generando" con `wire:poll`, la transición a "listo" del preview y el error que impide pagar en checkout.
- **`resendPin` no daba ninguna señal**: ni carga, ni `disabled`, ni éxito. El usuario pulsaba y no pasaba nada visible, así que lo pulsaba cinco veces. Nueva propiedad `$pinResent` + estado de carga + `role="status"` al confirmar, y mensaje de error si el envío falla.
- **Cero `:active` en todo el funnel.** En móvil no hay hover: pulsar "Pagar" no devolvía ninguna confirmación táctil. Resuelto en la capa base de `app.css`, global — depender de recordarlo botón a botón es justo cómo se llega a cero.
- **Botones-icono sin nombre accesible** (`✕` de cerrar, quitar estilo del chip): `aria-label` + glifo con `aria-hidden`.
- **Spinners anunciados como contenido**: `aria-hidden` en los 5.
- **Desplegables sin semántica**: `aria-expanded` + `aria-haspopup` en el selector de estilo y el del upsell.
- **Lightbox sin semántica de diálogo**: `role="dialog"`, `aria-modal`, foco al abrir y **devolución del foco al cerrar** — antes el usuario de teclado volvía al principio de la página en vez de a la tarjeta que abrió.
- **Sliders antes/después sin nombre**: `aria-label` en los tres (`preview-panel`, `styles/show`, `gallery/show`).

9 tests nuevos (`FunnelAccessibilityTest`); verificado que 8 de 9 fallan contra el código anterior. Suite: 151.

### Pendiente
- El slider de comparación sigue cubriendo la imagen entera con un `input[type=range]` de `opacity:0`, lo que compite con el scroll táctil en móvil.
- 34 vistas sin stamp de `design.md`.
- `script-src` del CSP en Report-Only (decisión sobre los scripts inyectables del panel).

## [1.5.2] — 2026-07-17

### Bugs pendientes de las auditorías

Cierre de los hallazgos que quedaban abiertos. Ninguno lo detectaba un test antes.

- **`/styles`: el estado vacío no aparecía nunca.** `x-show` consultaba el DOM con `document.querySelectorAll`, que no es una dependencia reactiva de Alpine: se evaluaba una vez al montar, cuando aún había tarjetas. Buscar algo inexistente dejaba la rejilla en blanco, sin mensaje.
- **`/styles`: un apóstrofo rompía el filtro.** Cada tarjeta interpolaba el texto de búsqueda crudo dentro de un literal JS con comillas simples; un estilo llamado "L'Atelier" partía la expresión Alpine de esa tarjeta. Ahora el filtrado vive en datos (`@json`), no en el DOM: un solo cambio arregla los dos.
- **Queries muertas.** `linkedin` y `cv` consultaban `ArtisticStyle::where('style_key', …)` en cada request y sus vistas no usaban el resultado (Conversational FAQ y Narrative Workflow no pintan estilos). Retiradas.
- **`family` cargaba 6 pares y pintaba 3.** La vista (Letter) usa `first()` + `slice(1, 2)`; el controlador pedía 6. Ajustado a 3.
- **`gift`: cada ocasión enlazaba a un estilo aleatorio.** `$styles` viene de `shuffle()`, así que "Navidad" apuntaba a un estilo distinto en cada carga. Una ocasión no es un estilo: ahora enlazan al selector.

Tests de regresión para los dos de `/styles` (fallan contra el código anterior). Suite: 142.

### Pendiente
- ~24 `major` de accesibilidad en el funnel: 9 inputs sin `<label>` asociado, cero `aria-live` en el estado dinámico, ningún `:active`, desplegables sin `aria-expanded`, lightbox sin focus trap.
- 34 vistas sin stamp de `design.md` (home, funnel, galería, checkout, errores, legal).
- Cifras y testimonios sin fuente — decisión del propietario, mantenidos a tamaño de nota.

## [1.5.1] — 2026-07-17

### Seguridad y RGPD — revisión post-despliegue

Revisión de seguridad de lo desplegado en 1.5.0 (sin critical ni high; nada explotable nuevo). Lo que salió, arreglado:

### Corregido
- **Checkout: cambiar de producto reutilizaba la order anterior.** La idempotencia filtraba solo por sesión (pese a que el comentario decía "session+producto") y `orders` no tiene columna de producto. Pedir el pack de 9,99 € tras un intento de 1,00 € te mandaba al checkout de 1,00 €; **en sentido inverso, cobraba 9,99 € a quien pidió 1,00 €**. Ahora filtra por producto base (`is_upsell = false`) e importe — cambiar los estilos extra tampoco reutiliza una order obsoleta. Preexistente, con test de regresión que falla contra el código viejo.
- **Carrera en la creación del checkout.** `stripe_checkout_id` se persiste tras llamar a Stripe y fuera de la transacción, así que la idempotencia (`whereNotNull`) no veía una order recién creada: dos POST concurrentes creaban dos orders + dos Checkout Sessions. `lockForUpdate` no vale (la transacción libera al commit, antes de que exista el id): se usa un lock atómico de cache sobre sesión+producto que abarca la llamada a Stripe.
- El middleware de cabeceras habría pisado el CSP `default-src 'none'` de `SecureImageController`, debilitando las respuestas de imagen. Ahora respeta un CSP ya fijado.

### RGPD
- **Fuentes autoalojadas.** Arreglar el `@import` en 1.5.0 activó una exposición que llevaba dormida: mientras el build lo descartaba, ningún visitante contactaba con Google. Ahora se sirven desde `public/fonts/` y no sale ninguna petición a terceros. Solo subsets latin/latin-ext (36 `@font-face` de Google → 16; 12 ficheros, ~228 KB). Source Serif 4 es variable: un fichero sirve 400/600/700. Licencias OFL 1.1 incluidas, como exige redistribuirlas.
- Retirados los `preconnect` a `fonts.googleapis.com` / `fonts.gstatic.com`, ya innecesarios; añadido `preload` de los dos woff2 del pliegue.

### Añadido
- **CSP en dos capas.** Enforced: `base-uri`, `object-src`, `frame-ancestors`, `form-action` — no dependen de qué scripts se carguen, así que no rompen nada. Report-Only: la política completa con `script-src`, sin aplicar todavía porque el panel permite inyectar scripts arbitrarios (`SiteSetting::head_scripts`) hacia cualquier host y una allowlist los rompería en silencio. Ruta de promoción documentada en `DEPLOY.md § CSP`.
- `DEPLOY.md`: secciones de fuentes autoalojadas, CSP y verificación post-deploy.
- 6 tests nuevos (`CheckoutIdempotencyTest`, `SecurityHeadersTest`). Suite: 140.

### Pendiente
- `script-src` sigue sin aplicarse: depende de decidir qué hacer con los scripts inyectables desde el panel.
- `preconnect` a `js.stripe.com` en el layout, pero **Stripe.js no se carga** (el checkout es un redirect de servidor). Sobra.

## [1.5.0] — 2026-07-17

### Sistema de diseño + rediseño de landings

Auditoría de diseño del frontend. Dos hallazgos dominantes y sus arreglos.

**Las fuentes de marca no cargaban en producción.** El `@import` de Source Serif 4 + Barlow iba detrás de los `@tailwind`, y la spec CSS exige que preceda a cualquier regla: el build lo descartaba en silencio, sin error ni warning. El sitio entero renderizaba en Georgia/system-ui desde el scaffold. Movido a la línea 1 + `preconnect` a fonts.googleapis/gstatic. Verificado en producción: el CSS compilado pasó de 0 a 1 ocurrencias de `googleapis`.

**Las 10 landings eran un solo template.** Compartían la estructura (hero idéntico byte a byte, tira 3-col con emoji, grid de estilos, 3 pasos, CTA) y se diferenciaban por el gradiente inline `A→B→A` del hero — con dos duplicados exactos. Estaba del revés. Nuevo `design.md` en la raíz: fija la marca, libera la estructura.

### Añadido
- `design.md` — sistema de diseño bloqueado (paleta, tipografía, voz del CTA, accesibilidad, macroestructura por landing).
- `<x-before-after>` — díptico antes/después sin slider. Los 36 pares reales de `GalleryExample` se usan por primera vez: antes ninguna landing los mostraba y 5 reciclaban la misma foto de stock.
- 10 macroestructuras distintas: Photographic (painting) · Split Studio (professional) · Conversational FAQ (linkedin) · Narrative Workflow (cv) · Marquee Hero (tinder) · Catalogue (gift) · Bento Grid (friends) · Feature Stack (valentines) · Letter (family) · Manifesto (mothers-day).
- Copy de Letter y Manifesto en es/en/fr/it/pt.

### Accesibilidad
- `pmf-muted` daba **2.80:1** sobre champagne y se usaba para contenido real en ~35 sitios. Token partido: `pmf-muted` #7D626B (5.20:1) para superficie clara, `pmf-muted-dark` #A89098 (5.33:1) para `pmf-dark`. No son intercambiables. `pmf-secondary` #8C6B73 (4.46:1) → #70555C (6.33:1).
- `:focus-visible` no existía en el funnel; `styles/index` y `preview-panel` lo anulaban con `outline-none`. Añadido en capa base, sin animar.
- El input de subida era `class="hidden"` → fuera del orden de tabulación: el paso era **inalcanzable por teclado**. Ahora `sr-only`.
- `prefers-reduced-motion` no existía con 11 animaciones activas.
- `x-cloak` nunca se declaró: el menú móvil y el selector de idioma parpadeaban al cargar.
- `grid-cols-4` sin breakpoint dejaba ~35px por celda a 320px en el selector de orientación.

### Corregido
- **Doble submit en checkout:** `preview-panel` y `checkout` no tenían `wire:loading`/`disabled`. El servidor ya era idempotente (`PaymentController` reutiliza la order pending y su Checkout Session si sigue abierta), así que no había doble cobro real: el guard de cliente cierra la ventana de UX y evita orders pending duplicadas. Queda pendiente el hardening de la carrera concurrente (`stripe_checkout_id` se persiste tras la llamada a Stripe, fuera de la transacción).
- Barras de progreso al **65% fijo** durante esperas de 30-60s → indeterminadas honestas.
- El dropzone no tenía estado de error ni progreso real de subida: el preview local es instantáneo y hacía creer que ya había terminado.
- `<div>` sin cerrar en `valentines` se tragaba 3 emojis como atributos del div.
- Azul de LinkedIn (#0A66C2) y rosa de Tinder (#FD3A73) retirados: rompían el acento único y sugerían una afiliación inexistente.
- Precio y badge de `professional` sacados a i18n (estaban hardcodeados, el badge en castellano para las 5 locales).
- `config/app.php` iba por 1.3.1 con 1.4.1 publicada: el footer llevaba dos releases mintiendo.
- `DEPLOY.md` documentaba `APP_PATH="$RELEASE_PATH/version-es"`, ruta que no existe en la rama `deploy` (subtree aplanado). Script ahora detecta el layout, como ya hacía `setup.sh`. Documentado el flujo de subtree split, que no estaba en ninguna parte.

- N+1: `LandingController::examples()` no hacía eager load de `artisticStyle`; hasta 6 queries extra por landing. Añadido `with('artisticStyle')` (verificado: 6 queries → 2).

### Notas
**RGPD — Google Fonts remoto.** Al arreglar el `@import` se ha activado una exposición que llevaba dormida: mientras el build lo descartaba, ningún visitante contactaba con Google. Ahora cada visita envía su IP a Google Fonts (jurisprudencia LG München, ene-2022). Con público UE y una política de cookies cuidada, lo coherente es autoalojar los `.woff2`: quita la exposición y además elimina una petición render-blocking encadenada. Pendiente.

**Sin CSP** en páginas HTML pese a scripts inline + GTM + Stripe (`SecurityHeaders.php` pone HSTS, X-Frame-Options, Referrer-Policy y Permissions-Policy, pero no CSP). Preexistente, no de esta versión. Pendiente.

Stat-Led y Quote-Led quedan **vetadas** en `design.md`: no hay métricas con fuente ni testimonios reales (los del seeder cambian de nombre según el idioma). Las cifras y testimonios actuales se mantienen por decisión del propietario, a tamaño de nota — nunca de titular.
## [1.4.1] — 2026-06-12

### Auditoría de mejora — seguridad, pagos y baseline de tests

Auditoría completa con vetado manual de hallazgos. 8 planes de implementación ejecutados, cada uno revisado contra sus criterios de cierre antes del merge. Registro completo en `plans/README.md` (incl. 14 hallazgos descartados con justificación).

#### Seguridad
- **fix:** TrustProxies restringido a rangos publicados de Cloudflare (antes `*`) — la IP del cliente ya no es spoofeable vía `X-Forwarded-For`. Cerraba evasión de bans de IP, evasión del rate limit de generaciones gratuitas, suplantación de la allowlist del admin (Filament) e inculpación de IPs ajenas
- **fix:** verificación de PIN con `hash_equals()` (tiempo constante)
- **fix:** rate limit de generaciones unificado — el doble contador (Livewire + middleware con claves distintas) permitía 6 generaciones gratis/IP/24h en vez de 3

#### Bugs críticos
- **fix:** los bans de IP nunca llegaban a Redis — Carbon 3 devuelve `diffInSeconds()` con signo, el TTL salía negativo y `syncToRedis()` lo descartaba; como `CheckIpBan` solo consulta Redis, el sistema de moderación v1.4.0 era inoperante en runtime
- **fix:** fail-open real en `CheckIpBan`/`IpBanService` — `catch (\Throwable)`, antes `\Exception` no capturaba errores de extensión (`Class "Redis" not found` tumbaba el sitio y 41 tests)
- **feat:** `moderation:sync-bans` programado cada hora (cinturón ante restarts de Redis)
- **fix:** 500 en `/checkout/{uuid}` sin `?product=` — TypeError por `null` en `productByKey(string)`; descubierto al restaurar la baseline de tests
- **fix:** `SendCampaignBatchJob::failed()` filtraba por una propiedad inexistente — los lotes fallidos quedaban atascados en `sending` sin reintento
- **fix:** migración que añade `'sending'` al enum `mail_sends.status` — el código escribía un estado que el schema no admitía; la reserva atómica de lotes de campañas fallaba en MariaDB strict mode

#### Tests y CI
- **fix:** suite reparada: 41 tests rotos → **127 en verde**; `TestCase` con `withoutVite()` + `fakeRedis()` — la suite no requiere Redis, MariaDB ni assets compilados
- **test:** +26 tests de caracterización de los servicios del dinero: `CouponService`, `OrderFulfillmentService` (idempotencia con lock, reconciliación de importes Stripe, guard de `max_uses`, dispatch de jobs) y `StripeWebhookController` con firmas HMAC reales (pagos async SEPA, duplicados idempotentes, firma inválida → 400)
- **feat:** CI en GitHub Actions (PHP 8.4 — el lock requiere ≥8.4) en cada push a `main`
- **chore:** `npm audit fix` — 5 de 7 vulnerabilidades resueltas (restantes: dev-only, `shell-quote` vía `concurrently`, exigen major)

#### Migraciones requeridas
```bash
php artisan migrate
```

#### Post-deploy (operador)
- Verificar en logs que las IPs registradas son de visitantes (no de rangos Cloudflare 172.x/104.x/173.x) — validación del cambio de TrustProxies
- Opcional: `php artisan moderation:sync-bans` para sincronizar de inmediato los bans existentes (el cron horario lo hace solo)

## [1.4.0] — 2026-04-06

### Sistema de moderacion de contenido + ban de IP

Doble capa de deteccion de contenido prohibido (NSFW, violencia, armas) con ban automatico de IP.

#### Capa 1 — Deteccion pre-upload (HuggingFace)
- **feat:** `ContentModerationService` con provider pluggable (HuggingFace Inference API, modelo Falconsai/nsfw_image_detection)
- **feat:** analisis de imagen ANTES de enviar a proveedores IA — coste cero de IA si se detecta contenido prohibido
- **feat:** fail-open por diseno — si el servicio de moderacion falla, el upload procede (la Capa 2 es red de seguridad)

#### Capa 2 — Deteccion por rechazo de proveedor IA
- **feat:** `ContentPolicyException` rompe la cadena de proveedores — si fal.ai rechaza por NSFW, NO intenta OpenRouter/Novita/Evolink
- **feat:** deteccion de keywords de content policy en respuestas HTTP 400/403/422/451 de los 4 proveedores IA
- **feat:** `GeneratePortraitJob` captura ContentPolicyException, banea IP y borra imagen original del storage

#### Ban de IP y notificacion admin
- **feat:** `IpBanService` con Redis-first (O(1) sub-ms) + persistencia MariaDB
- **feat:** middleware global `CheckIpBan` — usuario baneado ve 503 generico de mantenimiento, sin pistas del ban ni su duracion
- **feat:** `ContentViolationAdminMail` — email al admin con IP, email, user agent, tipo, score, hash SHA-256
- **feat:** `content_violations` + `ip_bans` — tablas de auditoria con indices optimizados
- **feat:** comando `moderation:sync-bans` para sincronizar bans BD→Redis tras restart

#### Admin (Filament)
- **feat:** `ContentViolationResource` — panel de violaciones (solo lectura, grupo Seguridad)
- **feat:** `IpBanResource` — panel de bans con accion "Desbanear" para admins
- **feat:** test SightEngine/HuggingFace en pagina Diagnostico del Sistema

#### Traducciones
- **feat:** clave `content_not_permitted` en 5 idiomas (es, en, fr, pt, it) — mensaje generico sin mencionar NSFW ni ban

## [1.3.1] — 2026-03-26

### Fixes post-auditoría

- **fix:** GalleryExampleResource — columnas `title.es` y `artisticStyle.label_es` crasheaban por dot notation de Filament, migradas a `getStateUsing()`
- **fix:** pedidos 0€ (regalos/gratis) excluidos de estadísticas admin — nuevo scope `Order::paidReal()`
- **fix:** widget "Últimos pedidos" marca "Gratis" en gris para orders de 0€

## [1.3.0] — 2026-03-26

### Auditoría completa de seguridad y estabilidad

Auditoría de código completa. 28 hallazgos procesados.

#### Seguridad
- **fix:** admin delete ya no borra orders/sessions — anonimiza usuario (regla de negocio)
- **fix:** bounce cleanup anonimiza en vez de borrar datos de negocio
- **fix:** `SecureImageController::original()` ahora requiere PIN verificado
- **fix:** `captureEmail()` no permite reasignar sesión ya reclamada a otro email
- **fix:** PIN flow usa email del servidor, no del estado Livewire del cliente
- **fix:** `/facecounsus` ahora tiene rate limiting (throttle:5,1)
- **fix:** `status()` endpoint ya no expone `watermarked_path` interno
- **fix:** páginas privadas (checkout, downloads, PIN) ahora tienen `noindex, nofollow`
- **fix:** `.gitignore` raíz reforzado para `bootstrap/cache/*.php`

#### Integridad de datos
- **feat:** reconciliación de importes Stripe — persiste `amount_total`, `discount`, `tax`, `promotion_code` reales del checkout
- **fix:** `PaymentController` con idempotencia — reutiliza order pending existente, no crea duplicados
- **fix:** `claimFreeDownload()` envuelto en transacción con re-check para evitar orders duplicadas
- **fix:** `PurgeExpiredFilesJob` usa lock + re-check de orders pagadas antes de borrar (race condition)
- **fix:** `GeneratePortraitJob` y `GenerateUpsellPortraitJob` implementan `ShouldBeUnique` (5min lock)
- **feat:** migración con unique constraint en `email_sequences(sequence_type, email_number)`
- **feat:** índices compuestos en `orders(generation_session_id, status)` y `orders(user_id, status)`

#### Jobs y emails
- **fix:** `GenerateFormatsJob` solo envía `DownloadReadyMail` cuando `allReady` y no enviado antes
- **fix:** `handleUpscale()` logea fallos en `provider_logs` en vez de silenciarlos
- **fix:** `SendCampaignBatchJob` usa estado intermedio `sending` + recovery en `failed()`
- **fix:** `GenerateFormatsJob` con eager loading para evitar N+1 queries

#### Frontend y SEO
- **fix:** URL hardcoded `/create?style=` en Alpine reemplazada por `locale_route()`
- **fix:** sitemap.xml genera URLs y hreflang para los 5 idiomas (ES/EN/FR/PT/IT)
- **fix:** checkout muestra errores de sesión expirada y fallos de Stripe
- **fix:** `ApplyWatermarkJob` centra crop cuadrado en ambos ejes (X e Y)

#### Migraciones requeridas
```bash
php artisan migrate
```

## [1.2.7] — 2026-03-26

### Favicon, EXIF, anti-duplicados, métodos de pago, IndexNow

- Favicon completo: ico multi-resolución, apple-touch-icon, android-chrome, mstile, webmanifest
- Auto-orientación EXIF al subir foto — corrige fotos de móvil giradas 90°
- Anti-duplicados en campañas de email — lock atómico por lote
- Filtro "Desuscritos marketing" + columna Mktg en admin usuarios
- Métodos de pago actualizados: Tarjeta, PayPal, Apple Pay, Google Pay (5 idiomas)
- theme-color #7A2D3B en meta tags
- Fichero verificación IndexNow para indexación rápida

## [1.2.6] — 2026-03-26

### Acumbamail tracking + limpieza bounces

- Header `ACUMBAMAIL-SMTPAPI` en todos los emails para categorización en Acumbamail
- Categorías automáticas: `genpic-pin`, `genpic-session`, `genpic-download`, `genpic-free-download`, `genpic-expiring`, `genpic-admin-alert`, `genpic-transactional`
- Campañas con categoría propia: `genpic-{nombre-campaña}`
- Herramienta de limpieza de bounces en admin (Importar Contactos)
- Pegar emails devueltos → elimina usuarios sin pedidos pagados
- Log visual por email: eliminado / tiene pedidos / no encontrado
- Marca `mail_sends` como bounced automáticamente

## [1.2.5] — 2026-03-26

### Sistema mailing + Web Basic gratis + fixes producción

#### Sistema de Mailing Promocional (Fase 5)
- Migraciones: `mail_campaigns`, `mail_sends`, campos importación en `users`
- Import CSV Mailchimp: auto-detección separador, BOM UTF-8, TemporaryUploadedFile
- Admin Filament: ImportContacts page, MailCampaignResource con envío por lotes
- Job `SendCampaignBatchJob`: envío 100 emails/lote con pausa anti-saturación
- Template email campaña: logos GenPic + face.co, cupón FACECO, unsub link
- 2 plantillas campaña face.co→GenPic en 5 idiomas con oferta 50%
- Página `/facecounsus` para desuscripción pública
- Detección idioma automática por country code
- Admin users: columna Origen, filtros importación, sección detalle

#### Web Basic gratis (promo temporal)
- `FREE_WEB_BASIC_ENABLED=true` en .env para activar/desactivar
- Preview: precio tachado + badge GRATIS + campo email inline
- Genera web_basic con marca "Imagen creada con www.genpic.com" (18px, 90% opacidad)
- Crea Order a 0€ (analytics) con unit_price_cents, total_cents, quantity
- Email con link a página intermedia `/free-download/{uuid}` (signed URL)
- Página descarga con upsell Pack Completo 50% (cupón PROMO50)
- Banner promo en landing principal + todas las landings
- Precios condicionales: ~~1,99€~~ GRATIS en pricing, estilos, emails

#### Fixes producción
- `generation_session_id` en vez de `session_id` en orders
- Fuentes DejaVu en `storage/fonts/` (open_basedir RunCloud)
- Hero slider: CSS opacity crossfade sin stacking
- `trustHosts` con localhost para desarrollo
- Horizon auth bypass en entorno local
- CSV import: manejo TemporaryUploadedFile, validación mime type
- Sitemap.xml con todas las URLs en 5 idiomas

## [1.2.0] — 2026-03-25

### Landing pages + Hero renovado

#### 10 Landing pages específicas (SEO/SEM)
- **Profesional** — `/fotos-profesionales` — comparativa GenPic vs Fotógrafo
- **LinkedIn** — `/foto-linkedin` — stats 21x/36x, color LinkedIn azul
- **CV** — `/foto-para-curriculum` — estándar europeo, fondo blanco
- **San Valentín** — `/san-valentin` — romántico, parejas, perfiles de citas
- **Tinder** — `/fotos-para-tinder` — color Tinder, stats 90%/3x, dating
- **Amigos** — `/fotos-con-amigos` — ocasiones (graduación, viajes, cumples)
- **Familia** — `/fotos-de-familia` — momentos (peques, bodas, abuelos)
- **Regalos** — `/regalo-personalizado-con-foto` — 8 ocasiones, emocional
- **Foto a cuadro** — `/convertir-foto-en-cuadro` — SEO producto core, artísticos
- **Día de la Madre** — `/dia-de-la-madre` — estacional, ideas regalo mamá
- Todas con slugs localizados por idioma (sin caracteres especiales)
- Traducciones completas ES/EN/FR/PT/IT
- Estructura reutilizable para futuras landings

#### Hero de la home renovado
- Carrusel con 3 imágenes comparativas split (familia, profesional, aventura)
- Transición fade suave con Alpine.js x-transition
- Swipe táctil + drag ratón, auto-rotación cada 5s
- Dots de navegación centrados, label sobre imagen
- Nuevo título: "Tus fotos, transformadas en algo extraordinario"
- Subtítulo menciona artístico, profesional, aventura, temático

#### Sección "Para quién" ampliada
- De 4 a 6 usos: añadidos LinkedIn/CV y Tinder/Temáticos
- Sección estilos: 4 aleatorios (shuffle) en vez de 8 fijos

#### Footer reorganizado
- Brand + tagline en fila completa arriba
- 4 columnas: Producto (con FAQ y Descargas) | Inspiración (5 landings) | Más ideas (5 landings) | Legal
- Eliminada sección Soporte y email

## [1.1.0] — 2026-03-24

### 14 estilos nuevos + email opcional + fix zoom IA

#### Nuevas categorías de estilos
- **Profesional** (4 estilos): Foto LinkedIn, Foto Currículum, Retrato Ejecutivo, Foto YouTuber
- **Temático** (3 estilos): San Valentín, Perfil Casual, Perfil Elegante
- **Aventura** (7 estilos): Surfista, Escalador, Safari, Playa Tropical, Skater, Futbolista, Esquiador
- Categorías en BD (`style_category`) — eliminado `$categoryMap` hardcodeado en vista
- `max_faces` por estilo — estilos nuevos solo admiten 1 persona
- Validación client+server: face-api.js bloquea si >max_faces
- Tips de foto por categoría (professional/thematic/adventure) en uploader y página de estilo
- Slider before/after con foto original correcta por categoría (original-woman vs original-family)
- Comando `php artisan styles:generate-previews` para generar thumbs IA automáticamente
- Thumbnails IA reales 300x300 (JPG+WebP) + imágenes completas para slider
- 9 filtros en `/styles` (antes 7): añadidos Profesional, Temático, Aventura
- Traducciones completas en 5 idiomas (ES/EN/FR/PT/IT)

#### Email opcional antes de generar
- `REQUIRE_EMAIL_BEFORE_GENERATION=true` muestra campo email opcional
- Si no da email → se ofrece de nuevo durante la pantalla de polling
- Si da email → confirmación visual, no se vuelve a pedir
- Al terminar generación, si tiene email → envía `SessionCreatedMail` con link al resultado
- `REQUIRE_PIN_VERIFICATION=true` → email obligatorio + verificación PIN antes de generar
- PIN sin email = ignorado (no tiene sentido)
- Nuevos steps en Livewire: `pin_verify` con input PIN, reenvío de código
- Traducciones 5 idiomas para todos los textos nuevos

#### Fix zoom/reframe en generación IA
- **Bug crítico**: `FalAiProvider` solo enviaba `prompt` e `image_urls` a fal.ai
- Ahora envía `negative_prompt`, `edit_strength` e `image_size`
- Sin `edit_strength`, el modelo usaba default (~1.0) con libertad total para reencuadrar
- Sin `image_size`, generaba en resolución/ratio diferentes al original
- Prompts anti-zoom reforzados en `PromptBuilder` (framing instructions al inicio)

## [1.0.0] — 2026-03-24

### Lanzamiento MVP — Multi-idioma 5 lenguas

#### Multi-idioma completo
- 5 idiomas activos: ES, EN, FR, PT, IT
- `locale_route()` helper para URLs localizadas en toda la app
- Selector de idioma con banderas en header
- Rutas con prefijo: `/`, `/en/`, `/fr/`, `/pt/`, `/it/`

#### Modelos JSON translatable
- ArtisticStyle, Product, Format, GalleryExample migrados de columnas `_es`/`_en` a JSON
- Método `t()` unificado (fallback: locale → en → es → primer valor)
- Filament admin con tabs por idioma
- 59 estilos traducidos a 5 idiomas
- 6 productos, 5 formatos, 34 ejemplos de galería traducidos

#### Localización completa
- Todas las rutas `route()` → `locale_route()` en mails, controllers, Livewire
- PIN emails con `__()` en vez de texto hardcodeado ES/EN
- Stripe Checkout locale y country mapping para 5 idiomas
- Metadatos EXIF/IPTC/XMP en el idioma del usuario
- Páginas de error 500 localizadas
- Secuencias email (abandonment + upgrade) traducidas a 5 idiomas con cupón FAMILY

#### Performance y SEO
- WebP con fallback JPG — ahorro ~40% en peso
- Cache 30d para assets estáticos
- GA4 funnel events (select_style, generation_started, view_result, begin_checkout)
- PageSpeed optimizations (fetchpriority, preconnect)

#### Stripe
- Cupones 100% en Stripe Dashboard (eliminada gestión local)
- `allow_promotion_codes` en Checkout
- Tax con Customer object + país por locale
- Stripe SDK v19 compatible

## [0.9.8] — 2026-03-23

### Auditoría de seguridad, integridad y hardening

#### Deploy y seeders
- `setup.sh` interactivo: pide valores, genera `.env`, crea shared, seed, assets — re-ejecutable sin perder datos
- Comando `php artisan deploy:seed` para primer despliegue en producción
- `GalleryExamplesSeeder` con los 36 ejemplos de galería exportados de desarrollo
- `DatabaseSeeder` actualizado: incluye todos los seeders (estilos, productos, testimonios, emails, galería)
- Documentación completa de deploy en `DEPLOY.md` (RunCloud, symlinks, cron, Horizon, checklist)
- `APP_VERSION` configurable en `.env` y `config/app.php`
- Todos los config PHP leen de `.env` — no hay que editar ficheros PHP en producción

#### FAQ actualizado
- "¿Puedo imprimirlo?": cambiado "4000px+" por "imagen de alta calidad en PNG"
- "¿Cuántas personas?": aviso de que con más de 5-6 personas los resultados pueden ser menos profesionales
- "¿Es un buen regalo?": añadido beneficio de fondos de pantalla para móvil y escritorio

#### Stripe cupones (fix)
- Creación de cupones desde admin Filament: eliminado código duplicado con ReflectionMethod
- Nuevo método `CouponService::syncToStripe()` reutiliza lógica existente
- Fix error "Received unknown parameter: coupon" al crear promotion codes

#### Footer y branding
- Copyright actualizado: `© 2026 genpic.com vX.X.X - Color Vivo Internet, SL`
- Nueva línea: "Hecho con ❤️ desde Madrid y Herencia (Ciudad Real) — España"
- Versión de app configurable via `APP_VERSION` en `.env` / `config/app.php`

#### Seguridad (críticos)
- Webhook Stripe con `DB::transaction` + `lockForUpdate` — previene race condition de pago doble
- Cupón `times_used` se incrementa solo tras pago confirmado (webhook/simulado), no al crear order
- `isPaid()` incluye estado `processing` — previene purga de archivos durante generación de formatos
- Lock atómico en dispatch de generación IA — previene jobs duplicados y doble coste

#### Seguridad (importantes)
- Signed URLs (72h) para todas las descargas — middleware `signed` en rutas export/zip
- Verificación `payment_status === 'paid'` en webhook para pagos async (SEPA, Bancontact)
- Rate limit `throttle:3,5` en endpoints de envío de PIN (portrait + my-downloads)
- `trustHosts` configurado — previene host-header poisoning en emails con signed URLs
- Índice único en `stripe_checkout_id` + índices compuestos en `generation_sessions`
- `failed()` handlers en `GeneratePortraitJob` y `GenerateFormatsJob` — marcan status=failed

#### Correcciones de datos
- Cast `decimal:4`/`decimal:2` en `cost_usd` y `style_strength_used` (antes `float`)
- N+1 corregido en `ProcessUpgradeEmailsJob` — eager-load de orders
- `PurgeExpiredFilesJob` usa `chunkById(50)` en vez de `get()` sin límite

#### Banner de Cookies (fixes)
- Fix GA doble: eliminado bloque duplicado en `<head>`, punto único de carga en `ccLoadAnalytics()`
- `body_scripts` ahora se inyecta correctamente (antes se guardaba pero no se renderizaba)
- `head_scripts` inyectados via DOM dinámico (no `document.write`)
- Enlace "Gestionar cookies" en footer — permite cambiar preferencias (cumplimiento RGPD)
- Toggle sincronizado al reabrir banner con estado actual

### Legales completos, Aviso Legal, Banner de Cookies

#### Condiciones Generales de Uso (reescritura completa)
- De 10 secciones básicas a 16 secciones con referencias legales
- Prestador (LSSI-CE art. 10), ámbito mundial con garantías UE (Dir. 2011/83/UE, RGPD)
- Descripción del servicio, requisitos (16 años, RGPD art. 8), uso permitido y prohibido
- Fotos y contenido: propiedad, licencia de procesamiento, licencia personal, eliminación 14d
- Derecho de desistimiento (Dir. 2011/83/UE art. 103.a — contenido digital)
- Propiedad intelectual (LPI, Ley de Marcas), protección de datos (RGPD, LOPDGDD)
- Responsabilidad usuario, limitación responsabilidad (cap 12 meses, excepción UE)
- Resolución disputas (ODR, Juntas Arbitrales), jurisdicción (Reg. UE 1215/2012)

#### Política de Privacidad (mejorada)
- De 8 a 12 secciones con intro de compromiso de transparencia
- Nuevo: datos que NO recogemos (biometría, facial, ubicación, tarjetas, passwords)
- Nuevo: transferencias internacionales (SCCs, TIAs, nota infraestructura EU)
- Nuevo: conservación de datos detallada por tipo (fotos 14d, email hasta solicitud, IP 14d)
- Nuevo: sección de modificaciones con preaviso
- Menores mejorada: refs legales RGPD art. 8 / LOPDGDD art. 7

#### Política de Cookies (mejorada)
- De 4 a 7 secciones con intro LSSI-CE art. 22.2
- Clasificación de cookies (necesarias vs opcionales)
- Tabla con proveedor, cookie cookieConsent añadida
- Nuevo: otras tecnologías (LocalStorage, session storage)
- Nuevo: links directos a guías de gestión Chrome/Firefox/Safari/Edge

#### Aviso Legal (nuevo)
- Nueva página `/legal` con 10 secciones
- Identificación titular (LSSI-CE), objeto del sitio web
- Propiedad intelectual (LPI, Ley de Marcas), exclusión de garantías
- Protección de datos (refs RGPD), enlaces a terceros (LSSI-CE art. 17)
- Accesibilidad (RD 1112/2018), jurisdicción (Reg. UE 1215/2012)
- Link en footer entre Términos y Cookies

#### Banner de Cookies
- Banner fijo inferior (position:fixed inline con !important para compatibilidad)
- 3 botones: Configuración / Rechazar todo / Aceptar todo
- Panel de configuración con toggles: esenciales (siempre activas) + analíticas (opcional)
- Toggle con estilos inline puros (sin dependencia Tailwind)
- Guarda preferencia en localStorage + cookie `cookieConsent`
- Se oculta inmediatamente si ya existe consentimiento previo
- Vanilla JS puro, sin Alpine ni dependencias externas
- Traducciones ES/EN completas

#### Configuración Analytics desde Admin
- Nueva tabla `site_settings` (key/value con grupo y cache 1h)
- Modelo `SiteSetting::get()` / `SiteSetting::set()` con cache automático
- Página Filament `/admin/site-settings`: Google Analytics ID, scripts head/body
- GA4 se inyecta automáticamente solo si el usuario acepta cookies analíticas
- Scripts adicionales (Meta Pixel, Hotjar, etc.) condicionados al consentimiento

#### Otros
- Print premium: label "PNG Alta Calidad" (antes "Ultra HD 4096px")
- Descripciones producto pack_completo actualizadas en seeder y BD
- Galería añadida al menú Producto del footer

## [0.9.7] — 2026-03-22

### Aspect Ratio, PNG Pack, Alertas Admin, UX Upsell

#### Preservación de framing y aspect ratio
- **Causa raíz corregida**: Novita forzaba `size: 2048x2048` cuadrado → `calculateSize()` calcula dimensiones respetando ratio original dentro de los límites de Novita (min 3.6M px, max 16.7M px)
- **OpenRouter pad+crop**: OpenRouter fuerza cuadrado en image-to-image, se soluciona con pre-procesado: padar imagen a cuadrada con fondo desenfocado → API → recortar al ratio original. Resultado: framing preservado sin cortar cabezas
- **PromptBuilder restructurado**: Regla de framing movida a #1 (CRITICAL), se prohíbe explícitamente mover/reposicionar sujetos verticalmente, preservar margen superior exacto
- **Negative prompt enriquecido**: `PromptBuilder::negativePrompt()` añade automáticamente términos anti-crop a todos los estilos

#### Cadena de proveedores reordenada
- **Nuevo orden**: fal_ai → openrouter → novita → evolink (antes: novita primero)
- fal.ai primero porque respeta aspect ratio nativo, OpenRouter como fallback
- **OpenRouter habilitado** en docker-compose.yml y .env
- **OpenRouter image_config 4K**: soporte para generación 4K en text-to-image (4096x2730 en 3:2). En image-to-image genera 2K → upscale automático para pack
- **Aspect ratios OpenRouter**: `closestAspectRatio()` mapea ratio original al más cercano soportado

#### Alertas admin por email cuando proveedor falla
- `ProviderFailedAdminMail` — email a `ADMIN_EMAILS` cuando un proveedor IA falla
- Rate-limited: máx 1 email cada 15 min por proveedor (no saturar bandeja)
- **Alerta CRÍTICA** (siempre envía) cuando TODOS los proveedores fallan
- Template operacional con tabla: proveedor, tipo error, cooldown, timestamp

#### PNG original en Pack Completo
- Nuevo formato `original_png` en config — entrega el PNG crudo de la IA con metadatos IPTC/XMP
- **Garantía 4096px+**: si raw < 4096px, upscale automático con Real-ESRGAN
- `print_premium` optimizado: salta upscale si raw ya es 4096px+

#### UX: selector de estilos extra compacto
- Grid de 50+ thumbnails con scroll reemplazado por **dropdown compacto con búsqueda** (Alpine.js)
- Estilos seleccionados se muestran como **chips** removibles
- Mensaje "máximo alcanzado" cuando llega a 5 extras
- Traducciones ES/EN para nuevas keys

## [0.9.6] — 2026-03-22

### Multi-Proveedor Funcional + Prompt Framing + CDN + Branding

#### Novita provider funcional
- Endpoint `/v3/seedream-4.5`, $0.030/imagen
- Soporte paths locales (base64) + URLs HTTP
- Errores registrados en provider_logs

#### ImageCdnUploader — URLs reales para todos los proveedores
- Upload temporal a fal.ai CDN para proveedores que requieren URL HTTP pública
- Funciona con paths locales y URLs existentes

#### Evolink image-to-image + OpenRouter actualizado
- Evolink funcional con imagen de referencia via URL pública
- OpenRouter modelo actualizado a `bytedance-seed/seedream-4.5`

#### Prompt: no cortar cabezas
- Regla #8 en PromptBuilder: mantener cabezas visibles, centrar sujetos
- STYLE_INSTRUCTION: mantener mismo framing y composición que foto original

#### Branding
- Archivos descargados incluyen `_genpic_com` en el nombre

#### Infraestructura
- Web delivery síncrono (sin cola) para desarrollo
- Link Horizon en admin sidebar

## [0.9.5] — 2026-03-22

### Galería 36 ejemplos + Horizon + Testimonios BD + Cache + Legales + Mejoras UI

#### Laravel Horizon (gestión visual de colas)
- Dashboard en `/horizon` — jobs en tiempo real, métricas, fallos, throughput
- 2 supervisors: `default` (generación, max 5 prod) + `maintenance` (purga, 1 proc)
- Auto-balanceo de workers según carga
- Protegido por `ADMIN_EMAILS` (.env) — mismos admins que Filament
- Docker worker usa `horizon` en vez de `queue:work`
- Producción: 1 entrada Supervisor ejecuta `php artisan horizon` (gestiona todo internamente)
- Timeout default 120s (GeneratePortraitJob), maintenance 300s (PurgeJob)

#### Galería de ejemplos (36 imágenes)
- 36 fotos Unsplash (2 tandas) generadas con estilos aleatorios via `gallery:generate`
- Página `/gallery` con paginación 6/página, filtros por tipo via URL
- Página `/gallery/{id}` con slider before/after (original izq, estilo der)
- Sección featured en landing (6 aleatorios rotan al expirar cache)
- Imágenes estáticas en `public/img/gallery/` (800px JPG optimizadas)
- `GalleryExampleResource` admin Filament (CRUD, reordenar, featured, borrar)
- Paginación simple Anterior/Siguiente traducida ES/EN

#### Testimonios desde BD
- Tabla `testimonials` con campos JSON multi-idioma (quote, author, tag, rating)
- `Testimonial::random(6)` — rotación aleatoria en cada carga de landing
- `TestimonialResource` admin con tabs por idioma, rating, reordenar
- Seeder con 6 testimonios ES/EN

#### Cache páginas públicas
- `CachePublicPages` middleware: cachea HTML completo, TTL configurable via `CACHE_PUBLIC_PAGES_HOURS`
- Aplica a: landing, estilos, galería. Testimonios y galería featured rotan al expirar
- Header `X-Cache: HIT/MISS` para debug

#### Prompt IA mejorado (PromptBuilder)
- Centralizado en `PromptBuilder::build()` — un solo lugar para todos los proveedores
- Prohíbe: cambio etnicidad/piel, marcas registradas, fusión de cuerpos, rearranjar personas
- Regla de composición: mantener posiciones y poses originales

#### Legales actualizados
- Responsable: Color Vivo Internet, SL en privacidad y términos
- Proveedores IA: fal.ai, Novita, Evolink, OpenRouter + Stackscale infra
- Precios: todo EUR con conversión Stripe, referencia Unsplash en términos
- Estilos listas corregidos en las 3 páginas legales

#### Mejoras UI
- Precios enmarcados en contenedor blanco con título integrado y badges confianza
- Precios EN en EUR con nota "converted to your local currency at checkout"
- Selector idioma mantiene la página actual al cambiar ES/EN
- Logo GenPic enlaza a raíz del idioma actual
- Labels slider galería: Original izquierda, estilo derecha
- Link "Galería" en nav header

## [0.9.2] — 2026-03-22

### Multi-Proveedor IA: 4 proveedores SeedReam 4.5

#### 4 proveedores de generación
- **Novita.ai** — $0.030/imagen (más barato) — API OpenAI-compatible
- **Evolink.ai** — $0.031/imagen — API REST async (POST → poll task_id)
- **fal.ai** — $0.040/imagen — API REST con CDN upload
- **OpenRouter** — $0.040/imagen — API OpenAI-compatible

#### Configuración vía .env
- `AI_PROVIDER_CHAIN=novita,evolink,fal_ai,openrouter` — orden de prioridad configurable
- Cada proveedor activable/desactivable individualmente (`NOVITA_ENABLED`, etc.)
- Fallback automático: si uno falla, pasa al siguiente en la cadena
- Routing por presupuesto: si se supera budget diario, prioriza el más barato

#### Upscale con tracking de costes
- Coste de upscale registrado en provider_logs separado ($0.01/imagen)
- Modelo `real-esrgan` identificable en estadísticas
- Config `UPSCALE_PROVIDER` en .env (solo fal.ai por ahora)

#### Costes actualizados
- fal.ai corregido: $0.040 (antes decía $0.014)
- OpenRouter: modelo actualizado a `bytedance-seed/seedream-4.5`
- Admin ProviderPerformanceWidget actualizado con los 4 proveedores

## [0.9.0] — 2026-03-22

### Upsell + Mejoras Admin + Seguridad Admin vía .env

#### Sistema de upsell estilos extra
- Producto `extra_style` (2,99€) — estilo artístico adicional con los 5 formatos del Pack Completo
- Producto `pack_all_styles` (34,99€) — definido pero inactivo, para activar en el futuro
- Máximo 5 estilos extra por order (configurable via `max_quantity`)
- Campos upsell en `products` (is_upsell_addon, upsell_for_product_id, max_quantity)
- Campos upsell en `order_items` (is_upsell, artistic_style_id, generation_status)

#### Backend upsell
- `GenerateUpsellPortraitJob` — genera retratos upsell con Bus::batch() (paralelo si hay workers)
- PaymentController acepta `extra_styles[]`, crea N order_items upsell, Stripe multi-línea
- StripeWebhookController usa `dispatchPostPaymentJobs()` — batch upsell → GenerateFormatsJob
- GenerateFormatsJob solo marca `download_ready` cuando ALL items están listos
- Modelos Order (upsellItems, baseItem, allItemsReady), OrderItem (artisticStyle, needsGeneration), Product (upsellParent, upsellAddons)

#### UI upsell en preview
- Selector visual de estilos extra al elegir Pack Completo
- Grid con thumbnails, checkbox burgundy, máximo 5, resumen de precio en tiempo real
- Skip sin extras disponible ("Continuar sin estilos extra")
- Traducciones ES/EN (8 keys por idioma)

#### Mejoras admin Filament
- Estilos artísticos: prompt + negative_prompt visibles en tabla y edición (solo lectura)
- Sesiones: columnas "Modelo IA" y "Proveedor" con badge de color
- Productos: permitir borrar si no tienen orders, solo desactivar si tienen historial
- Fix ProviderPerformanceWidget: `getTableRecordKey()` para queries con groupBy

#### Admins definidos en .env
- `ADMIN_EMAILS` en .env define quién accede al panel (lista de emails separados por comas)
- `User::canAccessPanel()` comprueba .env en vez de campo `is_admin` en BD
- Más seguro: no se puede manipular desde BD, cambio requiere acceso al servidor
- Se complementa con `ADMIN_ALLOWED_IPS` para doble seguridad

#### Emails editables desde admin con traducciones multi-idioma
- `APP_LOCALES` en .env define idiomas activos (ej: `es,en,fr`)
- Campos JSON translatable: subject, heading, body, cta_text, coupon_text
- Editor HTML rico (RichEditor) para el cuerpo del email — compatible con email HTML
- Tabs dinámicos por idioma en admin (se añade idioma en .env → aparece tab)
- Método `t($field, $locale)` con fallback: locale → en → es → primer valor
- Contenido por defecto seedeado en HTML para todos los emails
- Variables `:days` y `:product` sustituidas automáticamente
- Patrón extensible a estilos y productos cuando se necesiten más idiomas

## [0.8.0] — 2026-03-22

### Emails de Abandono + Upgrade con Descuentos Automáticos

#### Sistema de secuencias de email
- Tabla `email_sequences` — secuencias configurables desde admin
- 4 emails de abandono: 2h (recordatorio), 24h (beneficios), 3d (50% descuento), 7d (última oportunidad)
- 2 emails de upgrade: 48h (beneficios Pack Completo), 5d (50% descuento para clientes)
- `ProcessAbandonmentEmailsJob` — cron cada hora, sesiones sin compra
- `ProcessUpgradeEmailsJob` — cron cada hora, clientes con compra parcial (web/wallpaper)
- Cupones automáticos 50% generados via CouponService, válidos hasta expiración de sesión
- `AbandonmentMail` + `UpgradeMail` — templates HTML con bloque cupón verde
- Traducciones ES/EN completas (30+ keys por idioma)
- `EmailSequenceResource` admin Filament — CRUD: timing, asuntos, descuento, activar/desactivar
- Campos tracking en generation_sessions: abandonment_email_count, upgrade_email_count
- Mínimo 4h entre emails de abandono, 12h entre emails de upgrade

## [0.7.0] — 2026-03-22

### Cupones Stripe + Metadatos EXIF + Face Detection Mejorada + Stripe Sync

#### Sistema de Cupones Stripe
- Tabla `coupons` con soporte porcentaje y cantidad fija
- `CouponService` — crear en Stripe + local, validar, aplicar descuento
- Cupones por producto específico o para todos los productos
- Límites: usos máximos, fecha expiración, uso único por email
- Soporte cupones auto-generados (para futuros emails de abandono)
- `CouponResource` admin Filament — CRUD completo: crear, editar, desactivar, duplicar
- Endpoint `POST /checkout/validate-coupon` — validación AJAX en checkout
- `PaymentController` acepta `coupon_code`, calcula descuento, aplica `promotion_code` en Stripe
- Traducciones ES/EN para todos los mensajes de cupón

#### Metadatos EXIF/IPTC/XMP en todas las imágenes
- `ImageMetadataService` — aplica Comment + IPTC + XMP a cada imagen generada
- Copyright: `© {año} GenPic.com — Color Vivo Internet, SL`
- Campos: autor, descripción, crédito, source (URL de .env), keywords, título, headline
- Términos de uso y link a /terms en XMP
- Idioma dinámico (es/en) según locale de la sesión del usuario
- Se aplica en: watermark, pre-generados web, wallpapers, print premium

#### Face Detection Mejorada
- Detección sobre imagen original (no thumbnail de 160px)
- `inputSize: 512` (antes 320) para mejor detección en grupos
- `scoreThreshold: 0.15` (antes 0.4) — menos estricto, fotos de cuerpo entero

#### Pre-generación Web (compra instantánea)
- `ApplyWatermarkJob` pre-genera web_basic (1024px) y web_hd (2048px) limpios
- `GenerateFormatsJob` usa pre-generados si existen — sin reprocesar
- Comprar Web Basic o Web HD es instantáneo (0ms de procesamiento)

#### Stripe Sync (recuperación de pagos perdidos)
- Comando `stripe:sync-orders` — sincroniza orders pendientes con Stripe API
- Cron cada 5 minutos — recupera webhooks perdidos automáticamente
- Botón "Sincronizar Stripe" en admin Orders (orders pendientes con checkout_id)
- Botón "Reprocesar formatos" en admin (orders pagadas sin descargas)
- Soporta `--dry-run` y `--order=UUID` para debug

## [0.6.0] — 2026-03-21

### Test Suite Completa + Stripe E2E + Métricas Avanzadas Admin

#### Test Suite (90 tests, 170 assertions)
- 9 model factories: User, ArtisticStyle, GenerationSession, Generation, Product, Format, Order, OrderItem, PurgeQueue
- HasFactory trait añadido a los 9 modelos que no lo tenían
- PHPUnit configurado con SQLite in-memory para tests
- **Unit tests (7 archivos):** User, GenerationSession, Order, Product, ArtisticStyle, PurgeQueue, StorageService
- **Feature tests (9 archivos):** Landing, Styles, GenerationFlow, PaymentFlow, StripeWebhook, Download, PurgeExpiredFilesJob, Schedule, LegalPages
- **Stripe E2E tests (18 tests):** Flujo completo sin Stripe, webhook con firma real, idempotencia, firma inválida, checkout_id desconocido, eventos no-checkout, creación order, descarga post-pago, edge cases

#### Stripe E2E Verificado
- Nuevo entorno sandbox Stripe (cuenta 51TDWr7L)
- Comando `php artisan stripe:test-checkout` para testing manual con tarjetas de test
- Flujo completo probado: checkout → webhook firmado → GenerateFormatsJob (75ms) → email descarga → order ready
- Stripe CLI listener configurado y verificado

#### Métricas Avanzadas Admin (5 widgets nuevos)
- **ConversionFunnelWidget** — Funnel sesiones→email→compra con % y sparklines 7 días
- **RevenueChartWidget** — Gráfico línea dual: ingresos € + pedidos/día (30 días)
- **AiCostMarginWidget** — Margen bruto IA, coste medio/generación, presupuesto diario usado con alertas
- **ProviderPerformanceWidget** — Tabla comparativa proveedores: éxito%, velocidad, timeouts, coste
- **HourlyActivityWidget** — Actividad por hora: sesiones vs compras (para routing Fase 3)

#### STATUS.md Actualizado
- Refleja estado real: Fase 1 ~95%, Fase 2 ~35% adelantada
- Detalle completo de lo implementado y pendiente por fase

## [0.5.1] — 2026-03-21

### Auditoría de Código (9 correcciones)

Auditoría de código completa. Todos los hallazgos validados y corregidos.

#### Críticos
- **Email descarga roto** — DownloadReadyMail no incluía `description` ni `expires_at` en los links. La plantilla Blade fallaba silenciosamente y el cliente nunca recibía el email. Corregido: se añaden ambos campos con datos reales del formato y fecha de expiración.
- **Success page con Stripe real** — Stripe redirige con `session_id` (Checkout Session ID), pero la vista buscaba por `orders.uuid`. En producción no mostraba ni order ni descargas. Corregido: PaymentController::success() ahora busca por `stripe_checkout_id` cuando viene de Stripe.
- **Seeder desincronizado** — Formatos (`web` vs `web_basic`/`web_hd`) y productos (3 vs 4, precios antiguos) no coincidían con el config actual. Los exports web se saltaban silenciosamente. Reescrito con 5 formatos, 4 productos, precios actuales y `updateOrInsert`.
- **Webhook no idempotente** — Stripe puede enviar webhooks duplicados. Cada uno redispatchaba GenerateFormatsJob (coste IA, sobreescritura). Corregido: guarda que rechaza si order ya está en `paid`/`processing`/`ready`.

#### Importantes
- **ZIP sin PIN** — `downloadZip()` no verificaba PIN ni referer, bypass de auth cuando PIN activado. Corregido: mismas verificaciones que `export()`.
- **Moneda hardcodeada** — Success page mostraba `€` y formato español para todos los pedidos, incluso USD. Corregido: usa `$order->currency` para símbolo y formato numérico según locale.
- **Crop horizontal descentrado** — ApplyWatermarkJob: crop de horizontal-sobre-vertical usaba offset (0,0), cortando cabezas. Corregido: centra verticalmente `(0, (h-targetH)/2)`.
- **Sin transacción DB** — PaymentController creaba User/Order/Items sin transacción antes de llamar a Stripe. Si Stripe fallaba: 500 + registros huérfanos. Corregido: `DB::transaction()` + `try/catch` con rollback implícito y error amigable.

#### Mantenimiento
- **TTL inconsistente** — Config decía 72h, email decía 72h, UI decía 14 días, nada se aplicaba. Unificado todo a 14 días: config (336h), email template, trust badges ES/EN.

## [0.5.0] — 2026-03-20

### 59 Estilos, Landing Pulida, Checkout Completo

#### 59 Estilos Artísticos (7 categorías)
- Históricos (8): Renacimiento, Siglo de Oro, Victoriano, Colonial, Medieval, Realeza, Egipto, Mitología Griega
- Artísticos (12): Posimpresionismo, Prerrafaelita, Art Nouveau, Pop Art, Cuento de Hadas, Submarino, Aborigen, Acuarela, Pixel Art, Anime/Manga, Cómic Europeo, Art Deco
- Culturales (7): Muralismo, Folk Mexicano, Ukiyo-e, Habana Vintage, Piratas, Lejano Oeste, Safari
- Modernos (11): Deportistas 70s, Espacio, Militar, Ejecutivos, Steampunk, Cyberpunk, Superhéroes, Disco 80s, Rockstar, Magos, Urbano/Reggaetón
- Regionales (17): España, Latinoamérica, Anglosajón, África, Asia, Oceanía
- Eventos (4): Navidad, Halloween, Graduación, Boda
- 59/59 imágenes de muestra generadas con foto de referencia familiar

#### Landing Pulida
- Quitar "gratis/free" de todos los CTAs (vista previa con marca de agua, no gratis)
- IDs de secciones unificados (nav anchor links funcionan: Estilos, Cómo funciona, Precios, FAQ)
- Precios compactos en grid 4 columnas (no cards grandes)
- Landing EN sincronizada con ES: misma estructura, precios USD
- Slider before/after CSS puro en páginas de estilo

#### Checkout Dedicado
- Página /checkout/{session}?product=xxx con resumen de pedido
- Preview mini + producto + formatos incluidos + email + botón "Pagar X€"
- Pack Completo: muestra packs anteriores con precios tachados
- Modo test: pago simulado sin Stripe en entorno local
- Página success con descargas individuales (JPG/PNG) + ZIP para packs
- URLs de descarga seguras (403 en otro navegador)

#### Wallpaper Blur Background
- Cuando orientación no coincide: fondo difuminado + imagen nítida centrada
- Nunca recorta caras — efecto "Instagram"
- $0 coste extra (sin IA adicional)

#### Preservación Facial
- Prompt CRITICAL reforzado para identidad facial
- Eliminación de elementos modernos en estilos históricos
- Negative prompts actualizados

#### Descargas 14 días
- TTL de descarga extendido de 72h a 14 días
- Mensaje actualizado: "se borrarán y no será posible descargarlos"

## [0.4.0] — 2026-03-20

### Compra y Descargas

#### Flujo de Compra Completo
- Modal pide email antes de comprar si no se proporcionó antes
- Modo test: pago simulado sin Stripe en entorno local
- Página de éxito con detalle del pedido (UUID, estado, total)
- Descargas individuales por formato (JPG/PNG)
- Descarga ZIP para packs con múltiples formatos
- URLs seguras: copiar en otro navegador = 403 Forbidden

#### Productos Reestructurados
- Web Básica: 1024px, 1,99€ (nuevo)
- Calidad Web HD: 2048px, 3,99€ (antes 4,99€)
- Pack Fondos: Móvil + Escritorio, 7,99€ (regenera orientación si necesario)
- Pack Completo: Todo + Ultra HD 4096px, 9,99€ (antes 12,99€, incluye upscale)

#### GenerateFormatsJob Inteligente
- Web Basic/HD: resize de imagen existente ($0 extra)
- Wallpapers: regenera con fal.ai si orientación no coincide ($0.014 si necesario)
- Print Premium: upscale 2x con Real-ESRGAN a 4096px (solo Pack Completo, ~$0.01)
- ZIP automático para packs multi-formato

#### Slider Before/After
- Páginas /style/{key}: slider CSS puro + input range para comparar original vs estilo
- Preview del usuario: slider con 75% estilo visible por defecto
- Funciona sin Alpine.js (vanilla JS)

#### Preservación Facial Reforzada
- Prompt CRITICAL para mantener identidad facial exacta
- Eliminación de elementos modernos (zapatillas, relojes) en estilos históricos
- Negative prompts actualizados en 6 estilos históricos

## [0.3.0] — 2026-03-20

### Estilos, Galería y Productos

#### 49 Estilos Artísticos
- 8 Históricos: Renacimiento, Siglo de Oro, Victoriano, Colonial, Medieval, Realeza, Egipto, Mitología Griega
- 7 Artísticos: Posimpresionismo, Prerrafaelita, Art Nouveau, Pop Art, Cuento de Hadas, Mundo Submarino, Arte Aborigen
- 7 Culturales: Muralismo, Folk Mexicano, Ukiyo-e, Habana Vintage, Piratas, Lejano Oeste, Safari
- 10 Modernos: Deportistas 70s, Espacio, Militar, Ejecutivos, Steampunk, Cyberpunk, Superhéroes, Disco 80s, Rockstar, Magos
- 19 Regionales: España (Flamenco, Fallera, Gallego, Vasco), Latinoamérica (Gaucho, Mariachi, Cumbia, Andino, Carnaval), Anglosajón (Escocés, Cowboy, Realeza India), África (Realeza Africana, Egipto), Asia (Hanbok, Dinastía China, Thai), Oceanía (Maorí, Aborigen)

#### Galería Visual Completa
- 49 imágenes de muestra generadas con foto de referencia familiar
- Página /styles con buscador y filtros por categoría (6 categorías)
- Página individual /style/{key} con slider before/after interactivo (CSS puro)
- Thumbnails 300px para cards + originales para detalle

#### Productos y Precios
- Web Básica: 1024px, 1,99€
- Calidad Web HD: 2048px, 3,99€
- Pack Fondos: Móvil (1080×2340) + Escritorio (2560×1440), 7,99€
- Pack Completo: Todo + Ultra HD 4096px, 9,99€
- GenerateFormatsJob inteligente: resize, regeneración de orientación, upscale 2x

#### Modo Test de Pago
- Simulación de compra sin Stripe en entorno local
- Flujo completo testable: generar → preview → comprar → formatos

## [0.2.0] — 2026-03-20

### MVP Funcional

#### Generación IA
- SeedReam v5 Lite Edit (ByteDance) via fal.ai — $0.014/imagen fijo
- Preservación facial reforzada en prompt (CRITICAL face identity)
- Eliminación de elementos modernos (zapatillas, relojes) en estilos históricos
- Orientación configurable: auto, vertical, horizontal, cuadrado (1:1)
- Generación diferida: solo tras verificar email (ahorro de API credits)
- Upload a fal.ai CDN para imágenes locales

#### Sistema de Acceso Seguro
- PIN por email configurable (REQUIRE_PIN_VERIFICATION en .env)
- Email antes de generar configurable (REQUIRE_EMAIL_BEFORE_GENERATION en .env)
- 7 capas de seguridad en imágenes (PIN, referer, rate limit, anti-hotlink, no-cache, logging)
- Storage privado (fuera de public/, serve: false)
- SecureImageController para servir imágenes verificadas

#### Watermark
- Grid diagonal "GenPic.com preview" denso (8px, spacing 38px)
- Preview reducido a 600px (HD original intacta para pago)
- Orientation-aware: crop inteligente sin cortar caras

#### Multi-idioma
- Dominio único genpic.com con prefijo de ruta (/en/)
- Middleware SetLocale, lang files ES/EN
- Landing ES + EN con copy localizado
- Selector de idioma ES/EN en header

#### Livewire Components
- StyleSelector: buscador compacto desplegable
- PhotoUploader: upload + orientación + email opcional + processing
- PreviewPanel: slider before/after + pricing + checkout simulado

#### Docker
- PHP 8.4 + Imagick + Redis + MariaDB 10.11 + Mailpit
- Puerto 8101, Mailpit en 8025
- Queue worker para jobs de generación

## [0.1.0] — 2026-03-19

### Fase 0 — Preparación

#### Documentación
- Arquitectura, convenciones, DB_SCHEMA.md, ARTISTIC_PROMPTS.md
- Documentos de fases, copy y estrategia (no publicados)
- Diseños UI en pencil.dev (landing, wizard, emails, paletas)
- Paleta Burgundy + Champagne

#### Scaffolding
- Laravel 11 en version-es/
- 9 migraciones, 9 modelos, 5 controllers, 4 jobs, 4 Mailables
- Config: image_providers, genpic, filesystems (Wasabi)
- Tailwind CSS con paleta custom, Source Serif 4 + Barlow Semi Condensed
