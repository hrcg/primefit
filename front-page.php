<?php get_header(); ?>

<?php
// Hero Section - Uses WordPress Customizer settings
$hero_args = primefit_get_hero_config();
primefit_render_hero($hero_args);

// Featured Products Section
$featured_products_config = primefit_get_featured_products_config();
if ( $featured_products_config['enabled'] ) {
	$featured_products_args = primefit_get_product_loop_config('sale', $featured_products_config);
	primefit_render_product_loop($featured_products_args);
}

// Category Tiles Section
get_template_part('templates/parts/category-tiles');

// Product Showcase Section
$product_showcase_config = primefit_get_product_showcase_config();
if ( $product_showcase_config['enabled'] ) {
	$product_showcase_args = primefit_get_product_loop_config('new', $product_showcase_config);
	primefit_render_product_loop($product_showcase_args);
}

// Training Division Section (uses customizer settings)
get_template_part('templates/parts/training-division');


// Third Product Loop Section
$third_product_loop_config = primefit_get_third_product_loop_config();
if ( $third_product_loop_config['enabled'] ) {
	$third_product_loop_args = primefit_get_product_loop_config('custom', $third_product_loop_config);
	primefit_render_product_loop($third_product_loop_args);
}

// Second Training Division Section (uses customizer settings)
$training_division_2_config = primefit_get_training_division_2_config();
get_template_part('templates/parts/training-division', null, $training_division_2_config);
?>

<?php get_footer(); ?>

