<?php get_header(); ?>

<?php
// Hero Section - Uses WordPress Customizer settings
$hero_args = primefit_get_hero_config();
primefit_render_hero($hero_args);

// Featured Products Section
$featured_products_args = primefit_get_product_loop_config('sale', array(
	'title' => 'END OF SEASON SALE',
	'limit' => 12,
	'columns' => 4,
	'on_sale' => true,
	'show_view_all' => true,
	'view_all_text' => 'VIEW ALL',
	'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'section_class' => 'featured-products'
));
primefit_render_product_loop($featured_products_args);

// Category Tiles Section
get_template_part('templates/parts/category-tiles');


// Training Division Section
$training_division_args = array(
	'heading' => 'TRAINING DIVISION',
	'subheading' => '[ FALL 2025 COLLECTION ] A PATH WITHOUT OBSTACLES LEADS NOWHERE',
	'cta_primary_text' => 'SHOP NOW',
	'cta_primary_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'cta_secondary_text' => '',
	'cta_secondary_link' => '#',
	'image' => get_template_directory_uri() . '/assets/images/training-dept.jpg'
);
get_template_part('templates/parts/training-division', null, $training_division_args);

// Product Showcase Section
$product_showcase_args = primefit_get_product_loop_config('new', array(
	'title' => 'NEW ARRIVALS',
	'limit' => 8,
	'columns' => 4,
	'show_view_all' => true,
	'view_all_text' => 'SHOP ALL',
	'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'section_class' => 'product-showcase'
));
primefit_render_product_loop($product_showcase_args);

// Training Division Section
$training_division_args = array(
	'heading' => 'Become your best self',
	'subheading' => 'Unlock your potential with purpose-built gear designed for resilience, comfort, and top-tier performance.',
	'cta_primary_text' => 'Arise Now',
	'cta_primary_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'cta_secondary_text' => '',
	'cta_secondary_link' => '#',
	'image' => get_template_directory_uri() . '/assets/images/basketball.webp'
);
get_template_part('templates/parts/training-division', null, $training_division_args);
?>

<?php get_footer(); ?>

