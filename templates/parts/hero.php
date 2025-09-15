<?php
/**
 * Hero Section Template Part
 * 
 * Usage: get_template_part('parts/hero', null, $hero_args);
 * 
 * Expected $args structure:
 * - 'image_desktop' => desktop image URL
 * - 'image_mobile' => mobile image URL
 * - 'heading' => main heading text
 * - 'subheading' => subheading/description text
 * - 'cta_text' => call-to-action button text
 * - 'cta_link' => call-to-action button URL
 * - 'overlay_position' => 'left', 'center', 'right' (default: 'left')
 * - 'text_color' => 'light', 'dark' (default: 'light')
 */

// Set defaults
$defaults = array(
	'image_desktop' => primefit_get_asset_uri(array('/assets/images/DSC03813.webp')),
	'image_mobile' => primefit_get_asset_uri(array('/assets/images/DSC03756.webp')),
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
// Get desktop and mobile image URLs
$hero_image_desktop_url = !empty($hero['image_desktop']) ? $hero['image_desktop'] : primefit_get_asset_uri(array('/assets/images/DSC03813.webp'));
$hero_image_mobile_url = !empty($hero['image_mobile']) ? $hero['image_mobile'] : primefit_get_asset_uri(array('/assets/images/DSC03756.webp'));

// Get video URLs
$hero_video_desktop_url = !empty($hero['video_desktop']) ? $hero['video_desktop'] : '';
$hero_video_mobile_url = !empty($hero['video_mobile']) ? $hero['video_mobile'] : '';

// Video settings
$video_autoplay = !empty($hero['video_autoplay']) ? 'autoplay' : '';
$video_loop = !empty($hero['video_loop']) ? 'loop' : '';
$video_muted = !empty($hero['video_muted']) ? 'muted' : '';

// Fallback to direct theme directory URI if no image found
if (empty($hero_image_desktop_url)) {
	$hero_image_desktop_url = get_template_directory_uri() . '/assets/images/DSC03813.webp';
}
if (empty($hero_image_mobile_url)) {
	$hero_image_mobile_url = get_template_directory_uri() . '/assets/images/DSC03756.webp';
}
?>
<section class="hero" id="<?php echo esc_attr($hero_id); ?>">
	<div class="hero-media">
		<?php if (!empty($hero_video_desktop_url) || !empty($hero_video_mobile_url)) : ?>
			<!-- Video Background with Fallback Image -->
			<div class="hero-video-container">
				<!-- Desktop Video -->
				<?php if (!empty($hero_video_desktop_url)) : ?>
					<video 
						class="hero-video hero-video--desktop" 
						<?php echo $video_autoplay; ?> 
						<?php echo $video_loop; ?> 
						<?php echo $video_muted; ?>
						playsinline
						preload="metadata"
					>
						<source src="<?php echo esc_url($hero_video_desktop_url); ?>" type="video/mp4">
						Your browser does not support the video tag.
					</video>
				<?php endif; ?>
				
				<!-- Mobile Video -->
				<?php if (!empty($hero_video_mobile_url)) : ?>
					<video 
						class="hero-video hero-video--mobile" 
						<?php echo $video_autoplay; ?> 
						<?php echo $video_loop; ?> 
						<?php echo $video_muted; ?>
						playsinline
						preload="metadata"
					>
						<source src="<?php echo esc_url($hero_video_mobile_url); ?>" type="video/mp4">
						Your browser does not support the video tag.
					</video>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<!-- Fallback Image (always present for loading state and fallback) -->
		<picture class="hero-fallback-image">
			<source media="(max-width: 768px)" srcset="<?php echo esc_url($hero_image_mobile_url); ?>">
			<img 
				src="<?php echo esc_url($hero_image_desktop_url); ?>" 
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
