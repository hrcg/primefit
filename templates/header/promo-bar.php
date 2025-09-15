<?php
/**
 * Promo Bar Template
 * 
 * Displays a promotional banner at the top of the site
 */

// Get promo bar settings from customizer
$promo_text = get_theme_mod( 'primefit_promo_text', 'END OF SEASON SALE — UP TO 60% OFF — LIMITED TIME ONLY' );
$promo_bg_color = get_theme_mod( 'primefit_promo_bg_color', '#ff3b30' );
$promo_text_color = get_theme_mod( 'primefit_promo_text_color', '#ffffff' );

// Don't display if no promo text is set
if ( empty( $promo_text ) ) {
	return;
}
?>

<div class="promo-bar" style="background-color: <?php echo esc_attr( $promo_bg_color ); ?>;">
	<div class="promo-bar-content">
		<div class="promo-text" style="color: <?php echo esc_attr( $promo_text_color ); ?>;">
			<?php echo esc_html( $promo_text ); ?>
		</div>
	</div>
</div>
