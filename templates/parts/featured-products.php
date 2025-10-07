<?php
/**
 * Featured Products Section
 * 
 * Usage: get_template_part('templates/parts/featured-products', null, $args);
 * 
 * This template now uses the abstracted primefit_render_product_loop() function
 * for better reusability and consistency.
 */

// Set defaults for featured products
$defaults = array(
	'title' => 'END OF SEASON SALE',
	'limit' => 12,
	'columns' => 4,
	'orderby' => 'date',
	'order' => 'DESC',
	'visibility' => 'visible',
	'category' => '',
	'show_view_all' => true,
	'view_all_text' => 'VIEW ALL',
	'view_all_link' => '', // Will be handled by wp_parse_args with custom args
	'section_class' => 'featured-products'
);

$section_args = wp_parse_args($args ?? array(), $defaults);

// Use the abstracted product loop function
primefit_render_product_loop($section_args);
?>
