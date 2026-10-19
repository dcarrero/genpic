# Estilos artísticos y prompts

> Documento de diseño previo a la implementación (el proyecto se llamó RetratosIA
> al principio). La fuente de verdad son `database/migrations/` y
> `database/seeders/`; donde no coincidan, manda el código.

## Criterios de Selección de Estilos

**Seguridad legal:** Solo estilos cuyas referencias sean:
- Artistas fallecidos hace +70 años (dominio público en la UE: muerte + 70 años)
- Movimientos artísticos genéricos sin artista vivo asociado
- Estilos "inspirados en" una época/corriente, no en un artista específico vivo
- Estéticas culturales o históricas de uso general

**Regla de oro:** Los *estilos* artísticos no tienen copyright. Una "pintura al óleo estilo renacentista" es libre. "Al estilo de [artista vivo]" es problemático. Usar siempre descriptores de época/técnica/escuela.

---

## FASE 1 — 4 Estilos de Lanzamiento

---

### 1. Renacimiento Clásico
**style_key:** `renacimiento`  
**Artistas de referencia (todos dominio público):** Leonardo da Vinci (†1519), Rafael (†1520), Tiziano (†1576), Ghirlandaio (†1494)

```
POSITIVE PROMPT:
Renaissance oil painting portrait, 16th century Italian master style, 
dramatic chiaroscuro lighting from the left, rich jewel-tone colors (deep crimson, 
gold, ultramarine), detailed realistic brushwork, noble aristocratic attire with 
ornate embroidery and lace collar, dark verdaccio background, museum quality fine art, 
Uffizi Gallery aesthetic, sfumato technique, detailed hands, 
high resolution masterpiece

NEGATIVE PROMPT:
modern clothing, contemporary, photograph, digital art, cartoon, anime, manga, 
3d render, blurry, low quality, ugly, text, watermark, signature, frame, 
deformed hands, extra fingers, bad anatomy, oversaturated, neon colors

style_strength: 0.75
aspect_ratio: 3:4 (portrait vertical)
```

---

### 2. Siglo de Oro Español — Estilo Barroco Ibérico
**style_key:** `siglo_oro`  
**Referencia histórica:** Escuela española de pintura s.XVII. Velázquez (†1660), Zurbarán (†1664), Murillo (†1682), Ribera (†1652) — todos dominio público.

```
POSITIVE PROMPT:
Spanish Baroque oil painting portrait, 17th century Golden Age style,
dramatic Tenebrism lighting, deep shadow and bright highlight contrast,
dark neutral background (near black), noble Spanish courtly attire, 
subtle earth tones (ochre, sienna, burnt umber, ivory), realistic fabric texture 
(black velvet, white ruff collar), psychological depth in the expression,
austere dignity, painterly loose brushwork in background tight in face,
Prado Museum quality masterpiece

NEGATIVE PROMPT:
bright colors, cheerful, modern clothing, photograph, digital, cartoon, 
watermark, text, signature, deformed, extra limbs, blurry, low quality,
Italian Renaissance style, French style

style_strength: 0.80
aspect_ratio: 3:4
```

---

### 3. Retrato Victoriano — Época Dorada Británica
**style_key:** `victorian`  
**Referencia histórica:** Movimiento académico victoriano s.XIX. John Singer Sargent (†1925), Frederic Leighton (†1896), Lawrence Alma-Tadema (†1912) — dominio público.

```
POSITIVE PROMPT:
Victorian era formal oil painting portrait, 1860-1890 British academic style,
warm amber and sepia tones, dignified formal pose, elaborate period dress
(for women: silk gown with bustle, lace, pearls / for men: frock coat, cravat),
lush interior background (library or garden), painterly realistic technique,
soft warm lighting from window, rich fabric details, aristocratic poise,
National Portrait Gallery London quality, high detail masterpiece

NEGATIVE PROMPT:
modern clothing, casual attire, photograph, digital art, cartoon, fantasy,
dark background, Spanish style, dramatic shadows, medieval, ancient, 
watermark, blurry, deformed, text

style_strength: 0.73
aspect_ratio: 3:4
```

---

### 4. Muralismo Mexicano — Estilo Épico del Pueblo
**style_key:** `muralismo`  
**Referencia histórica:** Movimiento muralista mexicano 1920-1960. Diego Rivera (†1957), José Clemente Orozco (†1949), David Alfaro Siqueiros (†1974) — **ATENCIÓN:** Siqueiros solo murió en 1974. En UE caduca 2044. Usar solo descriptores del movimiento, nunca nombrar a Siqueiros.

```
POSITIVE PROMPT:
Mexican muralism style portrait, 1930s revolutionary social realism,
bold flat forms with strong outlines, earthy vibrant pigments (terracotta, 
ochre yellow, cobalt blue, forest green, warm red), heroic dignified expression,
architectural fresco aesthetic, monumental composition, traditional Mexican 
cultural elements in background (woven textiles, architectural arches, 
tropical plants), dramatic upward perspective, noble pueblo aesthetic,
social realism school, high contrast colors

NEGATIVE PROMPT:
photograph, dark tones, European style, cartoon, anime, digital art,
passive expression, pale colors, Victorian, baroque, modern contemporary,
watermark, text, blurry, low quality

style_strength: 0.78
aspect_ratio: 3:4 o 1:1
```

---

## FASE 2 — 6 Estilos Adicionales

---

### 5. Romanticismo Oscuro — Goya tardío
**style_key:** `goya_tardio`  
**Referencia:** Francisco de Goya (†1828) — dominio público. Pinturas negras, retratos tardíos.

```
POSITIVE PROMPT:
Late Romanticism dark oil painting portrait, early 19th century Spanish style,
dramatic expressionistic brushwork, somber palette (blacks, grays, dark umber, 
flashes of warm ochre), psychological intensity in the face, loose gestural 
background, introspective haunting expression, Romantic era clothing,
emotional raw energy in the paint application, museum masterpiece quality

NEGATIVE PROMPT:
bright colors, cheerful expression, detailed background, photograph, digital,
cartoon, clean lines, Renaissance technique, Victorian style, watermark, text

style_strength: 0.82
```

---

### 6. Retrato Posimpresionista — Color y Emoción
**style_key:** `posimpresionismo`  
**Referencia:** Paul Cézanne (†1906), Paul Gauguin (†1903), Vincent van Gogh (†1890) — todos dominio público.

```
POSITIVE PROMPT:
Post-Impressionist oil painting portrait, 1885-1905 style,
expressive visible brushstrokes with thick impasto texture, non-naturalistic 
bold colors (vivid oranges, electric blues, acid yellows against complementary shadows),
simplified geometric forms in background, emotional color symbolism,
flattened perspective with decorative elements, raw emotional energy,
Orsay Museum quality, painted with passion and expressiveness

NEGATIVE PROMPT:
photorealistic, smooth blending, dark Renaissance style, photograph,
digital art, cartoon, academic realism, Victorian formality,
muted colors, watermark, text

style_strength: 0.78
```

---

### 7. Retrato Colonial Latinoamericano
**style_key:** `colonial_lat`  
**Referencia histórica:** Pintura virreinal s.XVII-XVIII, escuelas de Cuzco, Quito, México. Autores anónimos o fallecidos, dominio público absoluto.

```
POSITIVE PROMPT:
Latin American Colonial viceregal portrait painting, 17th-18th century style,
criolla aristocratic formal attire, elaborate gold and silver embroidery,
flat frontal composition with hierarchical stiffness, rich brocade fabrics,
devotional objects or heraldic elements in background, warm ochre and gold tones,
Cuzco school or Mexican viceregal aesthetic, religious iconographic influence,
museum quality historical portrait, estofado gold technique details

NEGATIVE PROMPT:
European Renaissance, dark Spanish style, casual, modern, photograph,
digital, cartoon, soft lighting, impressionist, dynamic pose,
watermark, text, blurry

style_strength: 0.77
```

---

### 8. Gótico Americano — Realismo Rural
**style_key:** `american_gothic`  
**Referencia:** Grant Wood (†1942, dominio público en UE desde 2012), Regionalist movement 1930s.

```
POSITIVE PROMPT:
American Regionalist painting portrait, 1930s Grant Wood school style,
simplified geometric forms, flat precise draftsmanship, stoic dignified expression,
hard-edged realistic technique, subtle earth and green tones (sage, barn red, 
cream, sky blue), rural American setting suggestion in background,
Gothic architectural details, honest straightforward gaze,
formal rigid posture, Social Realism American school quality

NEGATIVE PROMPT:
European style, baroque drama, impressionist looseness, photograph,
digital art, cartoon, anime, dark colors, romantic, watermark, text

style_strength: 0.72
```

---

### 9. Ukiyo-e — Grabado Japonés
**style_key:** `ukiyo_e`  
**Referencia:** Katsushika Hokusai (†1849), Kitagawa Utamaro (†1806), Hiroshige (†1858) — dominio público total.

```
POSITIVE PROMPT:
Japanese Ukiyo-e woodblock print style portrait, Edo period (1600-1868),
bold flat black outlines with minimal shading, clean color fills (pearl white,
vermillion red, indigo blue, spring green, warm gold), elegant kimono or 
period attire with intricate pattern details, graceful stylized features,
decorative floral or geometric background pattern, calligraphic linear quality,
Ukiyo-e master print aesthetic, Japonisme style

NEGATIVE PROMPT:
photorealistic, Western oil painting, shadows, chiaroscuro, 3d render,
photograph, dark tones, European style, cartoon, anime (modern anime),
watermark, text, blurry

style_strength: 0.80
aspect_ratio: 2:3
```

---

### 10. Prerrafaelita — Belleza Medieval
**style_key:** `prerafaelita`  
**Referencia:** Dante Gabriel Rossetti (†1882), John Everett Millais (†1896), Edward Burne-Jones (†1898) — dominio público.

```
POSITIVE PROMPT:
Pre-Raphaelite Brotherhood painting portrait, 1860s Victorian romantic style,
jewel-bright saturated colors (ruby red, sapphire blue, emerald green, gold),
exquisite botanical detail in background (flowers, foliage, flowing water),
flowing natural hair with decorative elements, medieval or Arthurian costume,
luminous skin with rosy cheeks, dreamy romantic expression, 
meticulous detail in every element, Tate Britain museum quality,
ornate decorative border, flattened medieval perspective

NEGATIVE PROMPT:
dark tones, Spanish baroque, impressionist looseness, modern clothing,
photograph, digital art, cartoon, abstract, minimalist, casual,
watermark, text, blurry

style_strength: 0.76
```

---

## Estilos Adicionales para Fase 3+ (pipeline)

| Style Key | Nombre | Referencia | Status Legal |
|---|---|---|---|
| `art_nouveau` | Art Nouveau / Modernismo | Alphonse Mucha †1939 | ✅ Dominio público EU |
| `expresionismo` | Expresionismo Alemán | Ernst Ludwig Kirchner †1938 | ✅ Dominio público EU |
| `retrato_flandes` | Flandes Dorado | Jan van Eyck †1441, Rubens †1640 | ✅ Dominio público |
| `pompier` | Academicismo Pompier | William-Adolphe Bouguereau †1905 | ✅ Dominio público |
| `futurismo` | Futurismo Italiano | Umberto Boccioni †1916 | ✅ Dominio público EU |
| `constructivismo` | Constructivismo Ruso | El Lissitzky †1941 | ✅ Dominio público EU |
| `simbolismo` | Simbolismo | Gustave Moreau †1898 | ✅ Dominio público |
| `miniatura_persa` | Miniatura Persa | Estilo histórico s.XIII-XVII | ✅ Sin copyright |
| `retrato_ming` | Retratos Dinastía Ming | Estilo histórico s.XIV-XVII | ✅ Sin copyright |
| `icono_bizantino` | Icono Bizantino | Estilo histórico s.VI-XV | ✅ Sin copyright |

---

## Estilos a EVITAR (riesgo legal)

| Estilo | Problema |
|---|---|
| "estilo Frida Kahlo" | Frida Kahlo †1954 — dominio público en EU desde 2024, pero su imagen y marca están activamente protegidas por la Fundación Kahlo. Usar descriptores genéricos en su lugar. |
| "estilo Banksy" | Artista vivo, obra con copyright activo. |
| "estilo Picasso" | †1973. Dominio público en EU desde 2043. Sus herederos son muy agresivos legalmente. |
| "estilo Salvador Dalí" | †1989. Dominio público EU desde 2059. Fundación Dalí muy activa. |
| "estilo Andy Warhol" | †1987. Dominio público EU desde 2057. |
| "estilo Klimt" | †1918. Técnicamente dominio público (>70 años). Pero sus obras específicas pueden tener reclamaciones de herederos. Usar descriptores genéricos "Art Nouveau vienés". |

**Alternativa segura para todos estos:** Usar descriptores del movimiento/época/técnica sin nombrar al artista. En lugar de "estilo Frida Kahlo" → usar el prompt de `art_nouveau` mexicano con elementos folk mexicanos.

---

## Prompt para Estilo "Frida Kahlo" sin nombrarla

```
POSITIVE PROMPT (Mexican Folk Surrealism):
Mexican folk art portrait painting, 1940s Naive Surrealism style,
lush tropical botanical background (hibiscus, cactus, birds of paradise),
traditional Tehuana costume with embroidered blouse and huipil,
decorative folk art elements (ex-votos, retablo aesthetic),
introspective intense direct gaze, symbolic animals (hummingbird, 
deer, monkey), vivid saturated colors (fuchsia, cobalt, gold, forest green),
naïve realism with surrealist symbolic elements, Mexican identity pride

style_strength: 0.79
```

---

## Notas Técnicas para Integración con fal.ai / InstantID

```python
# InstantID preserva el parecido facial mientras aplica el estilo
# Parámetros clave en fal.ai:

{
  "image_url": "{signed_url_foto_original}",
  "prompt": "{base_prompt}",
  "negative_prompt": "{negative_prompt}",
  "ip_adapter_scale": 0.8,        # Control de identidad facial (0.6-0.9)
  "controlnet_conditioning_scale": 0.8,
  "num_inference_steps": 30,       # 20 (rápido) a 50 (calidad)
  "guidance_scale": 5.0,
  "seed": -1,                      # -1 = aleatorio (guardar seed en DB para reproducir)
  "width": 768,
  "height": 1024,                  # Ratio 3:4 para retratos
}

# Post-proceso siempre:
# 1. Upscale x2 con Real-ESRGAN (disponible en fal.ai o Replicate)
# 2. Aplicar watermark
# 3. Generar formatos según compra
```

---

## Ejemplos de Copy para Nombres de Estilos en UI

```
renacimiento  → "Renacimiento Clásico" / "Como en los grandes museos"
siglo_oro     → "Siglo de Oro Español" / "El estilo del Prado"
victorian     → "Retrato Victoriano" / "Elegancia del s.XIX"
muralismo     → "Muralismo Mexicano" / "Arte del Pueblo"
goya_tardio   → "Romanticismo Oscuro" / "Misterio y profundidad"
posimpresionismo → "Colores Vivos" / "Pincelada expresiva"
colonial_lat  → "Retrato Colonial" / "La nobleza del Nuevo Mundo"
american_gothic → "Realismo Americano" / "Clásico de los años 30"
ukiyo_e       → "Grabado Japonés" / "Arte Ukiyo-e"
prerafaelita  → "Prerrafaelita" / "Romanticismo Medieval"
```
