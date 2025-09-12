<?php
/**
 * Featured Products Section
 * 
 * Usage: get_template_part('parts/sections/featured-products', null, $args);
 * 
 * Expected $args:
 * - 'title' => section title
 * - 'limit' => number of products to show
 * - 'columns' => number of columns
 * - 'orderby' => order products by
 * - 'order' => order direction
 * - 'visibility' => product visibility
 * - 'category' => product category slug (optional)
 */

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
	'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#'
);

$section = wp_parse_args($args ?? array(), $defaults);

// Build shortcode attributes
$shortcode_atts = '[products';
$shortcode_atts .= ' limit="' . absint($section['limit']) . '"';
$shortcode_atts .= ' columns="' . absint($section['columns']) . '"';
$shortcode_atts .= ' orderby="' . esc_attr($section['orderby']) . '"';
$shortcode_atts .= ' order="' . esc_attr($section['order']) . '"';
$shortcode_atts .= ' visibility="' . esc_attr($section['visibility']) . '"';
if (!empty($section['category'])) {
	$shortcode_atts .= ' category="' . esc_attr($section['category']) . '"';
}
$shortcode_atts .= ']';
?>

<section class="featured-products container">
	<?php if ( !empty($section['title']) ) : ?>
		<?php 
		get_template_part('parts/components/section-header', null, array(
			'title' => $section['title'],
			'alignment' => 'center'
		)); 
		?>
	<?php endif; ?>
	
	<?php echo do_shortcode($shortcode_atts); ?>
	
	<?php if ( !empty($section['show_view_all']) && $section['show_view_all'] ) : ?>
		<div class="featured-products-actions">
			<a href="<?php echo esc_url($section['view_all_link']); ?>" class="featured-products-view-all button button--outline">
				<?php echo esc_html($section['view_all_text']); ?>
			</a>
		</div>
	<?php endif; ?>
</section>
