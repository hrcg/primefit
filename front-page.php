<?php get_header(); ?>

<?php
// Hero Section - Uses WordPress Customizer settings
$hero_args = primefit_get_hero_config();
get_template_part('parts/hero', null, $hero_args);

// Featured Products Section
$featured_products_args = array(
	'title' => 'END OF SEASON SALE',
	'limit' => 12,
	'columns' => 4,
	'orderby' => 'date',
	'order' => 'DESC',
	'visibility' => 'visible',
	'show_view_all' => true,
	'view_all_text' => 'VIEW ALL',
	'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#'
);
get_template_part('parts/sections/featured-products', null, $featured_products_args);

// Training Division Section
$training_division_args = array(
	'heading' => 'TRAINING DIVISION',
	'subheading' => '[ FALL 2025 COLLECTION ] A PATH WITHOUT OBSTACLES LEADS NOWHERE',
	'cta_primary_text' => 'SHOP NOW',
	'cta_primary_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'cta_secondary_text' => '',
	'cta_secondary_link' => '#',
	'image' => get_template_directory_uri() . '/assets/media/training-dept.jpg'
);
get_template_part('parts/sections/training-division', null, $training_division_args);

// Product Showcase Section
$product_showcase_args = array(
	'title' => 'NEW ARRIVALS',
	'limit' => 8,
	'columns' => 4,
	'orderby' => 'date',
	'order' => 'DESC',
	'visibility' => 'visible',
	'category' => '', // Add your desired category slug here
	'show_view_all' => true,
	'view_all_text' => 'SHOP ALL',
	'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#'
);
get_template_part('parts/sections/product-showcase', null, $product_showcase_args);

// Category Tiles Section
get_template_part('parts/sections/category-tiles');
?>

<?php get_footer(); ?>

