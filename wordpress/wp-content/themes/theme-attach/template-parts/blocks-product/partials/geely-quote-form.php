<?php
if (!defined('ABSPATH')) exit;

$root_selector = $args['root_selector'] ?? '';
$form_shortcode = $args['form_shortcode'] ?? '';
$include_terms_modal = !empty($args['include_terms_modal']);

if (!$root_selector || !$form_shortcode) return;
?>

<div class="geely-form">
    <?php echo do_shortcode($form_shortcode); ?>
</div>

<script>
    window.__MG_QUOTE_BLOCKS__ = window.__MG_QUOTE_BLOCKS__ || [];
    window.MG_QUOTE_AJAX = window.MG_QUOTE_AJAX || {
        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('mg_quote_ajax')); ?>'
    };
    window.__MG_QUOTE_BLOCKS__.push('<?php echo esc_js($root_selector); ?>');
</script>

<?php if ($include_terms_modal): ?>
    <?php
    // Si ya tienes tu modal como partial, reutilízalo aquí.
    // get_template_part('template-parts/blocks-product/partials/data-policy-modal', null, [...]);
    ?>
<?php endif; ?>