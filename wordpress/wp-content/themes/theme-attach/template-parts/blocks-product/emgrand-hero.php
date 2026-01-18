<?php
/**
 * Bloque: Emgrand Hero con carrusel + navegación + STICKY NAV
 */

// Campos generales del bloque
$title      = get_field('hero_title');
$subtitle   = get_field('hero_subtitle');

$cta_quote_text = get_field('hero_cta_quote_text');
$cta_quote_url  = get_field('hero_cta_quote_url');

$nav_items = get_field('hero_nav_items');

$cta_datasheet_text = get_field('hero_cta_datasheet_text');
$cta_datasheet_url  = get_field('hero_cta_datasheet_url');

if (is_singular('producto')) {
  $product_title         = get_the_title(get_the_ID());
  $product_datasheet_url = get_field('product_datasheet_url', get_the_ID());

  if ($product_datasheet_url) $cta_datasheet_url = $product_datasheet_url;
  if ($product_title) $title = $product_title;
}

// Repeater de slides
$slides = get_field('hero_slides');

// Fallback: si no hay slides usamos hero_car_image
if (!$slides) {
  $fallback_img = get_field('hero_car_image');
  if ($fallback_img) {
    $slides = [
      [
        'slide_type'  => 'image',
        'slide_image' => $fallback_img,
        'slide_alt'   => '',
      ],
    ];
  }
}

$has_nav = !empty($nav_items) || (!empty($cta_datasheet_text) && !empty($cta_datasheet_url));
?>

<?php if ($slides) : ?>
  <!-- HERO (solo carrusel + overlay superior) -->
  <section class="emg-hero">
    <!-- SOLO FONDO / SLIDES -->
    <div class="swiper emg-hero__swiper">
      <div class="swiper-wrapper">
        <?php foreach ($slides as $slide) : ?>
          <?php
          $type = $slide['slide_type'] ?? 'image';

          $media     = $slide['slide_image'] ?? null;
          $media_url = '';
          $alt_text  = '';

          if (is_array($media)) {
            $media_url = $media['url'] ?? '';
            $alt_text  = $slide['slide_alt'] ?? ($media['alt'] ?? '');
          } else {
            $media_url = $media ?: '';
            $alt_text  = $slide['slide_alt'] ?? '';
          }

          $bg_style  = '';
          $video_url = '';

          if ($type === 'video') {
            $video_url = $media_url;
          } else {
            if ($media_url) $bg_style = "background-image:url('" . esc_url($media_url) . "');";
          }

          $slide_class = $type === 'video'
            ? 'emg-hero__slide emg-hero__slide--video'
            : 'emg-hero__slide emg-hero__slide--image';
          ?>
          <div class="swiper-slide <?php echo esc_attr($slide_class); ?>" style="<?php echo esc_attr($bg_style); ?>">
            <?php if ($type === 'video' && $video_url) : ?>
              <video
                class="emg-hero__bg-video"
                src="<?php echo esc_url($video_url); ?>"
                autoplay
                muted
                loop
                playsinline
              ></video>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div><!-- /.emg-hero__swiper -->

    <!-- OVERLAY ÚNICO -->
    <div class="emg-hero__overlay">
      <!-- ZONA SUPERIOR -->
      <div class="emg-hero__top">
        <div class="emg-hero__top-inner">
          <?php if ($title) : ?>
            <h1 class="emg-hero__title"><?php echo esc_html($title); ?></h1>
          <?php endif; ?>

          <?php if ($subtitle) : ?>
            <p class="emg-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
          <?php endif; ?>
        </div>

        <?php $product_id = get_the_ID(); ?>
        <a
          href="<?php echo esc_url(home_url('/cotiza/?product_id=' . (int)$product_id)); ?>"
          class="emg-hero__btn emg-hero__btn--light"
        >
          Cotizar
        </a>
      </div><!-- /.emg-hero__top -->
    </div><!-- /.emg-hero__overlay -->
  </section>

  <?php if ($has_nav) : ?>
    <!-- STICKY NAV (FUERA DEL HERO, para que pegue en toda la página) -->
    <div class="emg-sticky-nav" data-emg-sticky-nav>
      <div class="emg-sticky-nav__inner">
        <div class="emg-hero__bottom" data-emg-bottom>
          <!-- Marca + flecha (toggle mobile) -->
          <button class="emg-hero__nav-toggle" type="button" aria-expanded="false" data-emg-nav-toggle>
            <span class="emg-hero__brand"><?php echo esc_html($title); ?></span>
            <span class="emg-hero__nav-icon" aria-hidden="true"></span>
          </button>

          <?php if (!empty($nav_items)) : ?>
            <nav class="emg-hero__nav" data-emg-nav>
              <?php foreach ($nav_items as $item) :
                $label = $item['item_title'] ?? '';
                $url   = $item['item_url'] ?? '#';
                if (!$label) continue;
              ?>
                <a href="<?php echo esc_url($url); ?>" class="emg-hero__nav-link">
                  <?php echo esc_html($label); ?>
                </a>
              <?php endforeach; ?>
            </nav>
          <?php endif; ?>

          <?php if (!empty($cta_datasheet_text) && !empty($cta_datasheet_url)) : ?>
            <a
              target="_blank"
              href="<?php echo esc_url($cta_datasheet_url); ?>"
              class="emg-hero__btn emg-hero__btn--header emg-hero__btn--outline"
            >
              <?php echo esc_html($cta_datasheet_text); ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>
