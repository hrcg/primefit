<?php
/**
 * Product Showcase Section
 * 
 * Usage: get_template_part('templates/parts/product-showcase', null, $args);
 * 
 * This template now uses the abstracted primefit_render_product_loop() function
 * for better reusability and consistency.
 */

// Set defaults for product showcase
$defaults = array(
	'title' => 'NEW ARRIVALS',
	'limit' => 8,
	'columns' => 4,
	'orderby' => 'date',
	'order' => 'DESC',
	'visibility' => 'visible',
	'category' => '',
	'show_view_all' => true,
	'view_all_text' => 'SHOP ALL',
	'view_all_link' => '', // Will be handled by wp_parse_args with custom args
	'section_class' => 'product-showcase'
);

$section_args = wp_parse_args($args ?? array(), $defaults);

// Use the abstracted product loop function
primefit_render_product_loop($section_args);
?>
