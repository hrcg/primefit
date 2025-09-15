<?php
/**
 * Hero Section Template Part
 * 
 * Usage: get_template_part('parts/hero', null, $hero_args);
 * 
 * Expected $args structure:
 * - 'image' => array of image paths (webp, jpg fallbacks)
 * - 'heading' => main heading text
 * - 'subheading' => subheading/description text
 * - 'cta_text' => call-to-action button text
 * - 'cta_link' => call-to-action button URL
 * - 'overlay_position' => 'left', 'center', 'right' (default: 'left')
 * - 'text_color' => 'light', 'dark' (default: 'light')
 */

// Set defaults
$defaults = array(
	'image' => array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg'),
	'heading' => 'END OF SEASON SALE',
	'subheading' => 'UP TO 60% OFF. LIMITED TIME ONLY. WHILE SUPPLIES LAST.',
	'cta_text' => 'SHOP NOW',
	'cta_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
	'overlay_position' => 'left',
	'text_color' => 'light'
);

$hero = wp_parse_args($args ?? array(), $defaults);

// Generate unique ID for this hero instance
$hero_id = 'hero-' . uniqid();
?>

<?php 
$hero_image_url = primefit_get_asset_uri($hero['image']);
// Fallback to direct theme directory URI if no image found
if (empty($hero_image_url)) {
	$hero_image_url = get_template_directory_uri() . '/assets/images/hero-image.webp';
}
?>
<section class="hero" id="<?php echo esc_attr($hero_id); ?>" style="background-image: url('<?php echo esc_url($hero_image_url); ?>');">
	<div class="hero-media" style="display: none;">
		<picture>
			<img 
				src="<?php echo esc_url($hero_image_url); ?>" 
				alt="<?php echo esc_attr($hero['heading']); ?>" 
				loading="eager"
				class="hero-image"
			/>
		</picture>
		<div class="hero-overlay"></div>
	</div>
	
	<div class="hero-content">
		<div class="container">
			<div class="hero-text hero-text--<?php echo esc_attr($hero['overlay_position']); ?> hero-text--<?php echo esc_attr($hero['text_color']); ?>">
				<?php if (!empty($hero['heading'])) : ?>
					<h1 class="hero-heading"><?php echo esc_html($hero['heading']); ?></h1>
				<?php endif; ?>
				
				<?php if (!empty($hero['subheading'])) : ?>
					<p class="hero-subheading"><?php echo esc_html($hero['subheading']); ?></p>
				<?php endif; ?>
				
				<?php if (!empty($hero['cta_text']) && !empty($hero['cta_link'])) : ?>
					<div class="hero-cta">
						<a href="<?php echo esc_url($hero['cta_link']); ?>" class="training-division-button button button--primary">
							<?php echo esc_html($hero['cta_text']); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
