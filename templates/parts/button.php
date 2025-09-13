<?php
/**
 * Reusable Button Component
 * 
 * Usage: get_template_part('parts/components/button', null, $args);
 * 
 * Expected $args:
 * - 'text' => button text
 * - 'url' => button URL
 * - 'style' => 'primary', 'secondary', 'outline' (default: 'primary')
 * - 'size' => 'small', 'medium', 'large' (default: 'medium')
 * - 'class' => additional CSS classes
 * - 'attributes' => additional HTML attributes (array)
 */

$defaults = array(
	'text' => 'Button',
	'url' => '#',
	'style' => 'primary',
	'size' => 'medium',
	'class' => '',
	'attributes' => array()
);

$button = wp_parse_args($args ?? array(), $defaults);

// Build CSS classes
$classes = array('button', 'button--' . $button['style'], 'button--' . $button['size']);
if (!empty($button['class'])) {
	$classes[] = $button['class'];
}

// Build attributes
$attributes = '';
if (!empty($button['attributes']) && is_array($button['attributes'])) {
	foreach ($button['attributes'] as $attr => $value) {
		$attributes .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
	}
}
?>

<a 
	href="<?php echo esc_url($button['url']); ?>" 
	class="<?php echo esc_attr(implode(' ', $classes)); ?>"
	<?php echo $attributes; ?>
>
	<?php echo esc_html($button['text']); ?>
</a>
