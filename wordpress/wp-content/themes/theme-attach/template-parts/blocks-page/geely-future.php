<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('get_field')) return;

$block_id = !empty($block['anchor']) ? $block['anchor'] : ('future-hero-' . ($block['id'] ?? uniqid()));
$custom_id = get_field('future_id');
if (!empty($custom_id)) $block_id = sanitize_title($custom_id);

$title = get_field('future_title') ?: 'LISTO PARA EL FUTURO';
$desc  = get_field('future_desc') ?: '';

$bg_desktop = get_field('future_bg_desktop');
$bg_mobile  = get_field('future_bg_mobile');

$bg_desktop_url = is_array($bg_desktop) ? ($bg_desktop['url'] ?? '') : (is_string($bg_desktop) ? $bg_desktop : '');
$bg_mobile_url  = is_array($bg_mobile)  ? ($bg_mobile['url'] ?? '')  : (is_string($bg_mobile) ? $bg_mobile : '');

if (!$bg_mobile_url) $bg_mobile_url = $bg_desktop_url;
?>

<section
  id="<?php echo esc_attr($block_id); ?>"
  class="gf-future-hero"
  style="
    --gf-future-bg-desktop: url('<?php echo esc_url($bg_desktop_url); ?>');
    --gf-future-bg-mobile: url('<?php echo esc_url($bg_mobile_url); ?>');
  "
>
  <div class="gf-future-hero__bg" aria-hidden="true"></div>

  <div class="gf-future-hero__inner">
    <div class="gf-future-hero__content">
      <h2 class="gf-future-hero__title"><?php echo esc_html($title); ?></h2>

      <?php if (!empty($desc)) : ?>
        <p class="gf-future-hero__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>