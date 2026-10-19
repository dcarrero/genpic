# Design — GenPic

Sistema de diseño bloqueado para las landings promocionales. Toda página que se
rediseñe lee este fichero antes de emitir código. No se regenera por página: se
extiende o se enmienda cuando el sistema necesita crecer.

> **Nota sobre el formato.** La plantilla de partida proponía tokens OKLCH
> (`--color-paper`, `--color-ink`…). Este proyecto es Tailwind 3 con hex y clases
> nombradas, consumidas por 59 ficheros Blade. Una capa OKLCH paralela sería un
> sistema que nadie usa. Este documento describe el sistema **en el vocabulario
> del proyecto**: la fuente de verdad de los valores es `tailwind.config.js`.

## El problema que este sistema resuelve

Las 10 landings compartían la **estructura** (hero idéntico byte a byte, tira de
3 columnas con emoji, grid de estilos, 3 pasos, CTA burgundy) y se diferenciaban
por el **gradiente del hero** (`A→B→A` inline, con dos duplicados exactos). Está
del revés: la estructura debe variar, la marca debe ser constante.

Este sistema fija la marca y libera la estructura.

## Género

**editorial.** Source Serif 4 como display, burgundy sobre champagne, retratos
pintados. Pese al tema familiar, no es *playful*: la paleta y la tipografía ya
comprometen un registro cálido-editorial. No introducir el vocabulario redondeado
(Plus Jakarta, radios grandes, ilustración blanda) del género playful.

## Familias de macroestructuras

El proyecto tiene tres familias. La regla de diversificación **solo aplica dentro
de marketing**: ahí cada página tiene forma propia. En app y contenido pasa lo
contrario — la repetición es el objetivo, porque la coherencia es lo que hace que
un flujo se aprenda una vez.

| Familia | Qué incluye | Forma | Enriquecimiento |
|---|---|---|---|
| **Marketing** | las 10 landings, home, `/styles`, `/gallery` | una macroestructura distinta por página (tabla de abajo) | permitido |
| **App** | funnel (`livewire/*`), `checkout`, `generator`, `downloads`, `free-download`, `portrait`, `unsubscribe` | **Workbench** — la función lleva la página | **prohibido** |
| **Contenido** | `legal/*`, `errors/*` | **Long Document** — prosa, medida estrecha | solo tipografía |

### App — reglas propias

Estas páginas no compiten por atención: la sostienen. Nada de heroes, nada de
fotografía decorativa, nada de enriquecimiento. Lo que sí es obligatorio:

- **Los 8 estados** en todo elemento interactivo: default · hover ·
  `:focus-visible` · `:active` · disabled · loading · error · success.
  El funnel llegó a producción con cuatro de los ocho a cero.
- **Todo estado dinámico se anuncia.** Si Livewire o Alpine lo cambian sin
  recargar, lleva `aria-live` o `role="alert"`. Un `wire:poll` mudo deja al
  usuario de lector de pantalla esperando indefinidamente.
- **Ningún input depende del `placeholder`**: `<label>` asociado siempre,
  `sr-only` si una etiqueta visible estorba.
- **Honestidad de estado**: nada de barras de progreso con porcentaje inventado.
  Si no hay progreso real, indeterminado.

### Contenido — reglas propias

Medida ~36rem, sin tarjetas, sin iconos. El texto es el diseño.

### Marketing — una forma por página

Cada una tiene una forma propia; esa es la variedad. Ninguna se repite.

| Landing | Macroestructura | Por qué |
|---|---|---|
| `painting` | Photographic | La transformación *es* el argumento |
| `professional` | Split Studio | Díptico casual \| profesional |
| `linkedin` | Conversational FAQ | Producto que se enfrenta a escepticismo |
| `cv` | Narrative Workflow | Proceso genuinamente ordinal |
| `tinder` | Marquee Hero | Declaración, energía de app |
| `gift` | Catalogue | Índice de 8 ocasiones |
| `friends` | Bento Grid | Múltiples entradas de peso desigual |
| `family` | Letter | Íntimo, primera persona |
| `mothers-day` | Manifesto | Declaración emocional |
| `valentines` | Feature Stack | Panel fijo + pares de pareja en scroll |

### Macroestructuras vetadas y por qué

- **Stat-Led** — requiere una métrica defendible. Las cifras disponibles
  (`21×`, `36×`, `90%`, `3×`, `4.9/5`, `+2.000 retratos`) no tienen fuente.
  Una cifra inventada a 8-12rem es peor que ninguna cifra.
- **Quote-Led** — requiere testimonios reales. Los de `TestimonialsSeeder` están
  inventados y cambian de nombre según el idioma (`Laura G. · Madrid` /
  `Sarah T. · London`). Un testimonio falso como titular destruye la confianza
  en cuanto alguien lo mira dos veces.

Si algún día hay cifras auditadas o testimonios reales con permiso, ambas se
desbloquean. Hasta entonces, no.

## Paleta

Fuente de verdad: `tailwind.config.js`. Ninguna landing declara color
inline.

| Token | Valor | Uso |
|---|---|---|
| `champagne` | `#FDF8F4` | Papel por defecto |
| `champagne-dark` | `#F0E8E0` | Banda alterna |
| `burgundy` | `#7A2D3B` | Acento único, ≤5% del viewport |
| `burgundy-dark` | `#5E2230` | Hover del acento |
| `burgundy-soft` | `#F0D8D0` | Fondo suave del acento |
| `copper` | `#D4956A` | Acento secundario, muy escaso |
| `pmf-text` | `#2D1F24` | Tinta |
| `pmf-secondary` | `#70555C` | Párrafo · 6.33:1 sobre champagne |
| `pmf-muted` | `#7D626B` | Apoyo · 5.20:1 sobre champagne |
| `pmf-muted-dark` | `#A89098` | Apoyo **solo sobre `pmf-dark`** · 5.33:1 |
| `pmf-dark` | `#2D1F24` | Superficie oscura |
| `pmf-border` | `#E5D8D0` | Filete |

**Contraste.** `pmf-muted` y `pmf-muted-dark` no son intercambiables: cada uno
falla AA en el fondo del otro. Sobre superficie clara, `pmf-muted`. Sobre
`pmf-dark`, `pmf-muted-dark`. Sin excepciones.

**Prohibido: el gradiente de hero.** `style="background: linear-gradient(135deg,
#A, #B, #A)"` está vetado en todas las landings. Era el único diferenciador entre
ellas y era ruido. Las superficies son planas: `champagne`, `champagne-dark`,
`burgundy` o `pmf-dark`.

## Tipografía

- **Display** · `font-serif` → Source Serif 4, 600/700, **siempre roman**
- **Cuerpo** · `font-sans` → Barlow Semi Condensed, 400/500
- Cursiva: solo énfasis dentro de párrafo. **Nunca en un heading.**
- El `@import` de fuentes vive en `resources/css/app.css` **línea 1** — si se
  mueve detrás de los `@tailwind`, el build lo descarta y el sitio cae a Georgia.

### Escala de display por longitud de titular

| Caracteres | Clase tope |
|---|---|
| ≤ 50 | `text-4xl sm:text-5xl` |
| 51–90 | `text-3xl sm:text-4xl` |
| > 90 | reescribir más corto |

**Excepción: Marquee Hero** (`tinder`). En esa forma el display *es* el pliegue,
y el tope sube a `text-4xl sm:text-6xl lg:text-7xl`. El escalón móvil no se
toca: a 320px un titular de 41 caracteres a `text-6xl` rompe en pedazos. Toda
display grande lleva `[overflow-wrap:anywhere]` para que un nombre largo no
desborde. Esta excepción es solo para Marquee: no se extiende a las otras nueve.

## Espaciado

Escala de Tailwind (4pt). **Prohibido `py-16 px-6` en toda sección**: ese ritmo
plano era parte del tell. Cada landing varía su densidad vertical según su forma
— una Letter respira distinto que un Bento.

## Movimiento

- Sin librería. Alpine para estado, CSS para transición.
- Transición: propiedad **nombrada**, nunca `transition` pelada (anima 10
  propiedades) ni `transition-all`.
- `prefers-reduced-motion` está resuelto globalmente en `app.css`. No se re-declara.
- **Un gesto por elemento.** `hover:scale-105` universal está vetado.
- Sin reveal on scroll. Las landings son cortas; el contenido está ahí.

## Iconos

**Sin emoji como icono.** Eran el sistema de iconos de facto y rompen la voz de
trazo, dependen del SO y los lectores de pantalla los verbalizan. Las landings no
llevan iconos: llevan tipografía y fotografía real. Si algún día hace falta un
set, se elige uno y se documenta aquí.

## Imagen — el activo real

- `GalleryExample` tiene **36 pares antes/después** taxonomizados por `photo_type`
  (`individual` 17 · `couple` 9 · `family` 7 · `group` 3) y un `scopeOfType()`.
  **Esto es lo que debe llevar las landings.**
- `img/hero/` solo tiene 3 fotos. `hero_family.jpg` se repetía en 5 landings.
  **Vetado reutilizar un mismo hero en más de una landing.**
- ~74 estilos con imagen en `img/styles/`.
- Toda imagen sobre el pliegue: `fetchpriority="high"`, nunca `loading="lazy"`.
- Toda imagen bajo el pliegue: `loading="lazy"`.

## Voz del CTA

- **Primario** · `bg-burgundy text-white font-bold px-8 py-4 rounded-lg`
  · `hover:bg-burgundy-dark` · **siempre `whitespace-nowrap`**
- **Sobre superficie oscura** · se invierte: `bg-white text-burgundy`
- **Secundario** · enlace tipográfico con `underline-offset`, sin caja
- Etiqueta: verbo + objeto, ≤ 4 palabras. Los CTA de 27+ caracteres rompían a dos
  líneas a 360px.
- Un CTA final por página. Uno, no dos.

## Lo que TODAS deben compartir

- El banner de promoción (`landings._free-promo-banner`)
- El nav y el footer del layout
- La paleta y las dos fuentes
- La voz del CTA (forma, radio, ritmo de padding)
- Superficies planas — cero gradientes
- Sin emoji, sin `text-center` por defecto

## Lo que cada una PUEDE variar

- La macroestructura (es la variedad)
- La densidad vertical
- El tratamiento de imagen dentro de lo que su forma permite
- El orden y el peso de sus secciones

## Accesibilidad — suelo, no aspiración

- `:focus-visible` está en la capa base de `app.css`. No se anula con
  `outline-none` en ninguna parte.
- Contraste AA en todo texto. Los tokens ya lo garantizan si se usan bien.
- Sin `grid-cols-N` fijo sin breakpoint.
- Ningún CTA ni enlace de nav rompe a dos líneas.

## Deuda conocida

- **Cifras sin fuente** en `linkedin`, `tinder`, `professional`
  (`lang/*/ui.php`). Decisión del propietario: mantener por ahora. Riesgo bajo
  Ley de Competencia Desleal si alguien las reclama.
- **Testimonios inventados** en `TestimonialsSeeder`, con nombre distinto por
  idioma. Se muestran en la home. Mismo estado: mantener por ahora.
- Ambas bloquean Stat-Led y Quote-Led respectivamente.
