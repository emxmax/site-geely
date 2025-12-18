<?php
if (!defined('ABSPATH')) exit;

$title   = get_field('fg_title') ?: 'ENCUENTRA TU GEELY HOY';
$desc    = get_field('fg_description') ?: '';
$btn_txt = get_field('fg_button_text') ?: '';
$btn_url = get_field('fg_button_url') ?: '';
$new_tab = get_field('fg_button_new_tab');
$image   = get_field('fg_image');

$target = $new_tab ? '_blank' : '_self';
$rel    = $new_tab ? 'noopener noreferrer' : '';
?>

<section class="find-geely">
  <div class="find-geely__inner">

    <div class="find-geely__content">
      <h2 class="find-geely__title"><?php echo esc_html($title); ?></h2>

      <?php if ($desc): ?>
        <p class="find-geely__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>

      <?php if ($btn_txt && $btn_url): ?>
        <a
          href="<?php echo esc_url($btn_url); ?>"
          class="find-geely__btn"
          target="<?php echo esc_attr($target); ?>"
          rel="<?php echo esc_attr($rel); ?>"
        >
          <?php echo esc_html($btn_txt); ?>
        </a>
      <?php endif; ?>
    </div>

    <?php if ($image): ?>
      <div class="find-geely__media">
        <img
          src="<?php echo esc_url($image['url']); ?>"
          alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
        >
      </div>
    <?php endif; ?>

  </div>
</section>
