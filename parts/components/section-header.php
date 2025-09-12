<?php
/**
 * Reusable Section Header Component
 * 
 * Usage: get_template_part('parts/components/section-header', null, $args);
 * 
 * Expected $args:
 * - 'title' => section title
 * - 'subtitle' => section subtitle (optional)
 * - 'alignment' => 'left', 'center', 'right' (default: 'left')
 * - 'tag' => HTML tag for title (default: 'h2')
 * - 'class' => additional CSS classes
 */

$defaults = array(
	'title' => '',
	'subtitle' => '',
	'alignment' => 'left',
	'tag' => 'h2',
	'class' => ''
);

$header = wp_parse_args($args ?? array(), $defaults);

if (empty($header['title'])) {
	return; // Don't render if no title
}

// Build CSS classes
$classes = array('section-header', 'section-header--' . $header['alignment']);
if (!empty($header['class'])) {
	$classes[] = $header['class'];
}
?>

<div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
	<<?php echo esc_attr($header['tag']); ?> class="section-title">
		<?php echo esc_html($header['title']); ?>
	</<?php echo esc_attr($header['tag']); ?>>
	
	<?php if (!empty($header['subtitle'])) : ?>
		<p class="section-subtitle"><?php echo esc_html($header['subtitle']); ?></p>
	<?php endif; ?>
</div>
