<?php get_header(); ?>

<?php
// Hero section for shop page
if ( is_shop() || is_product_category() || is_product_tag() ) {
	$hero_args = primefit_get_shop_hero_config();
	if ( !empty($hero_args) ) {
		get_template_part('parts/hero', null, $hero_args);
	}
}
?>

<div class="woocommerce-page">
	<div class="container woocommerce-container">
		<?php 
		// Remove WooCommerce sidebar for shop pages
		if ( is_shop() || is_product_category() || is_product_tag() ) {
			add_action( 'woocommerce_before_main_content', 'primefit_remove_shop_sidebar' );
		}
		
		woocommerce_content(); 
		?>
	</div>
</div>

<?php get_footer(); ?>

