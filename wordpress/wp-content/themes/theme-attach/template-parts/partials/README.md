# Partials - Componentes y Secciones Reutilizables

## 📁 Estructura de Carpetas

```
template-parts/partials/
├── sections/           # Secciones completas reutilizables
│   ├── hero.php
│   ├── faq-accordion.php
│   ├── cta-banner.php
│   ├── image-text.php
│   └── grid-features.php
├── components/         # Componentes atómicos
│   ├── breadcrumb.php
│   ├── card.php
│   ├── modal.php
│   ├── button.php
│   ├── badge.php
│   └── pagination.php
└── forms/             # Wrappers de formularios
    └── cf7-wrapper.php
```

---

## 🎯 Diferencia entre `sections/` y `components/`

### `sections/` - Secciones Completas

**Características**:
- ✅ Bloques completos de UI (Hero, FAQ, CTA Banner, Image+Text)
- ✅ Pueden contener múltiples componentes dentro
- ✅ Reciben argumentos complejos (arrays de objetos)
- ✅ Tienen estructura semántica completa (`<section>`)
- ✅ Son independientes y autosuficientes

**Cuándo usar**:
- Necesitas una sección hero que se repite en varios bloques ACF
- Quieres un FAQ accordion que cambia solo las preguntas
- Tienes un banner de CTA que se usa en múltiples páginas

### `components/` - Componentes Atómicos

**Características**:
- ✅ Elementos UI pequeños y reutilizables (Card, Button, Badge, Breadcrumb)
- ✅ Reciben argumentos simples (strings, números, arrays planos)
- ✅ NO tienen estructura semántica completa (sin `<section>`)
- ✅ Pueden ser usados dentro de secciones o bloques

**Cuándo usar**:
- Necesitas una card que se use para productos, promociones y blog
- Quieres botones con variantes consistentes (primary, secondary, outline)
- Tienes badges que se repiten en varios contextos

---

## 📝 Reglas de Creación

### ✅ Criterios para crear un Partial

**SÍ crear un partial cuando**:
1. El mismo HTML se repite en 3+ bloques diferentes
2. Solo cambian los textos/imágenes, la estructura HTML es idéntica
3. Necesitas variantes visuales (color, tamaño) sin duplicar HTML
4. El componente NO depende de ACF (recibe argumentos)

**NO crear un partial cuando**:
1. El HTML solo se usa en UN bloque específico
2. La lógica está fuertemente acoplada a un dominio (producto, promoción)
3. Requiere lógica de negocio compleja específica de un CPT
4. Es mejor como bloque ACF independiente

### 📐 Estructura de un Partial

Todos los partials deben seguir este patrón:

```php
<?php
/**
 * Partial: [Nombre del Componente]
 * 
 * [Descripción breve]
 * 
 * @param string $arg1       Descripción del argumento 1
 * @param array  $arg2       Descripción del argumento 2
 * @param string $variant    Variante: 'opcion1'|'opcion2'|'default'
 * @param string $class      Clases CSS adicionales
 */

if (!defined('ABSPATH')) exit;

// Valores por defecto
$arg1 = $args['arg1'] ?? '';
$arg2 = $args['arg2'] ?? [];
$variant = $args['variant'] ?? 'default';
$class = $args['class'] ?? '';

// Validar contenido mínimo
if (empty($arg1)) {
    return; // No renderizar si faltan datos esenciales
}

// Construir clases CSS
$component_classes = [
    'component-name',
    "component-name--{$variant}",
    $class
];

$component_classes = implode(' ', array_filter($component_classes));
?>

<!-- HTML del componente -->
<div class="<?php echo esc_attr($component_classes); ?>">
    <!-- Contenido -->
</div>
```

---

## 🚀 Cómo Usar los Partials

### Método 1: Usando `get_template_part()`

```php
<?php
// En un bloque ACF (template-parts/blocks-product/emgrand-hero.php)

if (!defined('ABSPATH')) exit;

// Obtener datos de ACF
$hero_title = get_field('block_hero_title');
$hero_background = get_field('block_hero_background');

// Renderizar partial de hero
get_template_part('template-parts/partials/sections/hero', null, [
    'title' => $hero_title,
    'subtitle' => 'Conoce el nuevo Emgrand',
    'background' => $hero_background['url'] ?? '',
    'variant' => 'product',
    'class' => 'emgrand-hero',
    'align' => 'left',
    'cta' => [
        'text' => 'Cotizar ahora',
        'url' => get_field('product_quote_url') ?: '#'
    ]
]);
?>
```

### Método 2: Usando Helpers (Recomendado)

```php
<?php
// En un bloque ACF

if (!defined('ABSPATH')) exit;

// Renderizar sección hero
theme_attach_render_section('hero', [
    'title' => get_field('block_hero_title'),
    'subtitle' => 'Conoce el nuevo Emgrand',
    'background' => get_field('block_hero_background')['url'] ?? '',
    'variant' => 'product',
    'class' => 'emgrand-hero'
]);

// Renderizar componente card
theme_attach_render_component('card', [
    'title' => get_the_title(),
    'image' => theme_attach_get_post_image_url(),
    'excerpt' => theme_attach_truncate_words(get_the_excerpt(), 15),
    'url' => get_permalink(),
    'variant' => 'product',
    'class' => 'my-custom-class'
]);
?>
```

### Método 3: Renderizar Grid de Cards

```php
<?php
// En un bloque ACF

$query = new WP_Query([
    'post_type' => 'producto',
    'posts_per_page' => 6
]);

// Renderizar grid completo de cards
theme_attach_render_cards_grid($query, 'product', 'custom-grid-class');
?>
```

---

## 🎨 Sistema de Variantes

Los partials usan **variantes** para cambiar su apariencia sin duplicar HTML:

```php
// Hero con variante 'product'
theme_attach_render_section('hero', [
    'title' => 'Emgrand',
    'variant' => 'product'  // Aplica .hero-section--product en CSS
]);

// Card con variante 'promotion'
theme_attach_render_component('card', [
    'title' => 'Promoción especial',
    'variant' => 'promotion'  // Aplica .card--promotion en CSS
]);
```

### Variantes Comunes

#### Hero Section
- `default` - Hero básico
- `product` - Hero de productos (altura mayor, overlay gradient)
- `page` - Hero de páginas estáticas
- `promotion` - Hero de promociones

#### Card
- `default` - Card básica
- `product` - Card de producto (muestra precio)
- `promotion` - Card de promoción (imagen más pequeña)
- `blog` - Card de blog (muestra fecha y categoría)

---

## 🛠️ Helpers Disponibles

### Helpers de Renderizado

```php
// Renderizar sección
theme_attach_render_section('hero', $args);

// Renderizar componente
theme_attach_render_component('card', $args);

// Renderizar grid de cards
theme_attach_render_cards_grid($query, 'product', 'grid-class');
```

### Helpers de Configuración

```php
// Obtener configuración de card según CPT
$card_config = theme_attach_get_card_config('producto', $post_id);

// Obtener items de breadcrumb automático
$breadcrumb_items = theme_attach_get_breadcrumb_items();

// O con items personalizados
$breadcrumb_items = theme_attach_get_breadcrumb_items([
    ['url' => '/modelos/', 'label' => 'Modelos'],
    ['label' => 'Emgrand']
]);
```

### Helpers Globales (disponibles en todo el tema)

```php
// Formatear precio
theme_attach_format_price(15000); // S/ 15,000.00

// Truncar texto
theme_attach_truncate_words($text, 20);

// Obtener imagen del post
theme_attach_get_post_image_url($post_id, 'large');

// Decodificar HTML entities
theme_attach_safe_html_decode($text);
```

---

## 📋 Ejemplos Prácticos

### Ejemplo 1: Hero Section en Bloque de Producto

```php
<?php
// template-parts/blocks-product/emgrand-hero.php

if (!defined('ABSPATH')) exit;

$hero_title = get_field('block_hero_title');
$hero_subtitle = get_field('block_hero_subtitle');
$hero_background = get_field('block_hero_background');

// Fallback a datos del CPT
if (is_singular('producto') && empty($hero_title)) {
    $hero_title = get_the_title();
}

theme_attach_render_section('hero', [
    'title' => $hero_title,
    'subtitle' => $hero_subtitle,
    'description' => get_field('product_short_description'),
    'background' => $hero_background['url'] ?? '',
    'variant' => 'product',
    'class' => 'emgrand-hero',
    'align' => 'left',
    'cta' => [
        'text' => __('Cotizar ahora', 'theme-attach'),
        'url' => get_field('product_quote_url') ?: '#'
    ]
]);
?>
```

### Ejemplo 2: Grid de Productos con Cards Reutilizables

```php
<?php
// template-parts/blocks-page/models-showcase.php

if (!defined('ABSPATH')) exit;

$products_ids = get_field('block_products_showcase');

if (!empty($products_ids)) {
    $query = new WP_Query([
        'post_type' => 'producto',
        'post__in' => $products_ids,
        'orderby' => 'post__in',
        'posts_per_page' => -1
    ]);
    
    if ($query->have_posts()): ?>
        <section class="models-showcase">
            <div class="models-showcase__container">
                <h2 class="models-showcase__title">
                    <?php echo esc_html(get_field('block_showcase_title')); ?>
                </h2>
                
                <?php
                // Renderizar grid de cards de productos
                theme_attach_render_cards_grid(
                    $query,
                    'product',
                    'models-showcase__grid'
                );
                ?>
            </div>
        </section>
    <?php endif;
}
?>
```

### Ejemplo 3: FAQ Accordion Reutilizable

```php
<?php
// Uso en template-parts/blocks-product/product-faq.php

if (!defined('ABSPATH')) exit;

$preguntas = get_field('preguntas_frecuentes');

if (!empty($preguntas)) {
    theme_attach_render_section('faq-accordion', [
        'title' => get_field('block_faq_title') ?: 'Preguntas Frecuentes',
        'questions' => $preguntas,
        'variant' => 'product',
        'class' => 'product-faq'
    ]);
}
?>
```

### Ejemplo 4: Breadcrumb en Single CPT

```php
<?php
// En templates/single-producto.html o como partial

theme_attach_render_component('breadcrumb', [
    'items' => theme_attach_get_breadcrumb_items([
        ['url' => '/modelos/', 'label' => 'Modelos'],
        ['label' => get_the_title()]
    ]),
    'show_home' => true,
    'class' => 'product-breadcrumb'
]);
?>
```

---

## 🎨 Estilos de Partials

Los estilos base de todos los partials están en **`assets/css/partials.css`**.

### Convenciones CSS

1. **Clase base**: `.component-name`
2. **Variantes**: `.component-name--variant`
3. **Elementos**: `.component-name__element`
4. **Modificadores**: `.component-name__element--modifier`

### Ejemplo de CSS

```css
/* Clase base */
.hero-section {
    position: relative;
    min-height: 500px;
}

/* Variante producto */
.hero-section--product {
    min-height: 600px;
}

.hero-section--product .hero-section__overlay {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.3) 100%);
}

/* Elemento */
.hero-section__title {
    font-size: 3rem;
}

/* Modificador */
.hero-section__title--large {
    font-size: 4rem;
}
```

### Personalización con Clases Adicionales

```php
// Renderizar hero con clase personalizada
theme_attach_render_section('hero', [
    'title' => 'Mi Hero',
    'variant' => 'product',
    'class' => 'my-custom-hero'  // Clase adicional
]);
```

```css
/* Personalización específica del bloque */
.my-custom-hero.hero-section {
    background-color: #000;
}

.my-custom-hero .hero-section__title {
    color: #FFD700;
}
```

---

## ⚠️ Qué NO hacer

### ❌ NO crear partials para lógica de dominio

```php
// ❌ INCORRECTO - Esto debería estar en inc/product/helpers.php
// partials/sections/product-specifications.php

function get_product_specs($product_id) {
    // Lógica específica de producto
}
```

### ❌ NO depender de ACF en partials

```php
// ❌ INCORRECTO - Los partials NO deben usar get_field()
<?php
$title = get_field('block_hero_title'); // NO!
?>

// ✅ CORRECTO - Recibir como argumento
<?php
$title = $args['title'] ?? '';
?>
```

### ❌ NO duplicar HTML en partials

```php
// ❌ INCORRECTO - Si necesitas esto, usa variantes
// partials/components/card-product.php
// partials/components/card-promotion.php
// partials/components/card-blog.php (3 archivos casi idénticos)

// ✅ CORRECTO - Un solo archivo con variantes
// partials/components/card.php (con variantes 'product', 'promotion', 'blog')
```

---

## ✅ Checklist para Crear un Partial

Antes de crear un nuevo partial, verifica:

- [ ] ¿Se repite el mismo HTML en 3+ lugares?
- [ ] ¿Solo cambian los textos/imágenes, no la estructura?
- [ ] ¿NO depende de lógica específica de un dominio?
- [ ] ¿Puede recibir argumentos en lugar de usar ACF directamente?
- [ ] ¿Tiene sentido que otros bloques lo reutilicen?
- [ ] ¿Las variantes CSS son suficientes para personalizarlo?

Si respondiste **SÍ** a todas, adelante y crea el partial.

---

## 📚 Referencias

- **Helpers de partials**: `inc/partials/helpers.php`
- **Helpers globales**: `inc/helpers.php`
- **Estilos de partials**: `assets/css/partials.css`
- **Instrucciones del proyecto**: `.github/copilot-instructions.md`

---

**Versión**: 1.0.0  
**Última actualización**: Enero 2026  
**Mantenedor**: Theme Attach - Geely
