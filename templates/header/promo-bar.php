<?php
/**
 * Promo Bar Component
 * 
 * Usage: get_template_part('parts/header/promo-bar', null, $args);
 * 
 * Expected $args:
 * - 'text' => promo text to display
 * - 'background_color' => background color (optional)
 * - 'text_color' => text color (optional)
 */

$defaults = array(
	'text' => 'END OF SEASON SALE — UP TO 60% OFF — LIMITED TIME ONLY',
	'background_color' => '#ff3b30',
	'text_color' => '#fff'
);

$promo = wp_parse_args($args ?? array(), $defaults);

// Allow customization via WordPress Customizer
$promo_text = get_theme_mod('primefit_promo_text', $promo['text']);
$promo_bg = get_theme_mod('primefit_promo_bg_color', $promo['background_color']);
$promo_color = get_theme_mod('primefit_promo_text_color', $promo['text_color']);

if (empty($promo_text)) {
	return; // Don't show promo bar if no text
}
?>

<div class="promo-bar" style="background-color: <?php echo esc_attr($promo_bg); ?>; color: <?php echo esc_attr($promo_color); ?>;">
	<div class="container"><?php echo esc_html($promo_text); ?></div>
</div>
