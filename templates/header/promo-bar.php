<?php
/**
 * Promo Bar Template
 * 
 * Displays a promotional banner at the top of the site
 * 
 * Features:
 * - Enable/disable toggle via customizer
 * - Customizable text content
 * - Background and text color selection
 * - Optional clickable link functionality
 * - Responsive design
 */

// Get promo bar configuration from customizer
$promo_config = primefit_get_promo_bar_config();

// Don't display if promo bar is disabled or no promo text is set
if ( ! $promo_config['enabled'] || empty( $promo_config['text'] ) ) {
	return;
}

// Determine if the promo bar should be clickable
$is_clickable = ! empty( $promo_config['link'] );
$container_tag = $is_clickable ? 'a' : 'div';
$container_attrs = $is_clickable ? 'href="' . esc_url( $promo_config['link'] ) . '"' : '';
?>

<<?php echo $container_tag; ?> class="promo-bar" style="background-color: <?php echo esc_attr( $promo_config['bg_color'] ); ?>;" <?php echo $container_attrs; ?>>
	<div class="promo-bar-content">
		<div class="promo-text" style="color: <?php echo esc_attr( $promo_config['text_color'] ); ?>;">
			<?php echo esc_html( $promo_config['text'] ); ?>
		</div>
	</div>
</<?php echo $container_tag; ?>>
