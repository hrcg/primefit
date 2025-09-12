<?php
/**
 * Reusable Image Component with Fallbacks
 * 
 * Usage: get_template_part('parts/components/image', null, $args);
 * 
 * Expected $args:
 * - 'src' => image source (can be array for fallbacks)
 * - 'alt' => alt text
 * - 'class' => CSS classes
 * - 'attributes' => additional HTML attributes (array)
 * - 'lazy' => enable lazy loading (default: true)
 * - 'sizes' => responsive sizes attribute
 */

$defaults = array(
	'src' => '',
	'alt' => '',
	'class' => '',
	'attributes' => array(),
	'lazy' => true,
	'sizes' => ''
);

$image = wp_parse_args($args ?? array(), $defaults);

if (empty($image['src'])) {
	return; // Don't render if no source
}

// Handle fallback images
$image_src = is_array($image['src']) ? primefit_get_asset_uri($image['src']) : $image['src'];

// Build CSS classes
$classes = array();
if (!empty($image['class'])) {
	$classes[] = $image['class'];
}

// Build attributes
$attributes = array();
if ($image['lazy']) {
	$attributes['loading'] = 'lazy';
}
if (!empty($image['sizes'])) {
	$attributes['sizes'] = $image['sizes'];
}

// Add custom attributes
if (!empty($image['attributes']) && is_array($image['attributes'])) {
	$attributes = array_merge($attributes, $image['attributes']);
}

// Build attributes string
$attributes_string = '';
foreach ($attributes as $attr => $value) {
	$attributes_string .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
}
?>

<img 
	src="<?php echo esc_url($image_src); ?>" 
	alt="<?php echo esc_attr($image['alt']); ?>"
	<?php if (!empty($classes)) : ?>class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php endif; ?>
	<?php echo $attributes_string; ?>
/>
