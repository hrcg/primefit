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
	'image_desktop' => primefit_get_asset_uri(array('/assets/images/DSC03813.webp', '/assets/images/DSC03813.jpg', '/assets/images/DSC03813.jpeg', '/assets/images/DSC03813.png')),
	'image_mobile' => primefit_get_asset_uri(array('/assets/images/DSC03756.webp', '/assets/images/DSC03756.jpg', '/assets/images/DSC03756.jpeg', '/assets/images/DSC03756.png')),
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
$hero_image_desktop_url = !empty($hero['image_desktop']) ? $hero['image_desktop'] : primefit_get_asset_uri(array('/assets/images/DSC03813.webp', '/assets/images/DSC03813.jpg', '/assets/images/DSC03813.jpeg', '/assets/images/DSC03813.png'));
$hero_image_mobile_url = !empty($hero['image_mobile']) ? $hero['image_mobile'] : primefit_get_asset_uri(array('/assets/images/DSC03756.webp', '/assets/images/DSC03756.jpg', '/assets/images/DSC03756.jpeg', '/assets/images/DSC03756.png'));

// Get video URLs
$hero_video_desktop_url = !empty($hero['video_desktop']) ? $hero['video_desktop'] : '';
$hero_video_mobile_url = !empty($hero['video_mobile']) ? $hero['video_mobile'] : '';

// Get video poster URLs
$hero_video_poster_desktop_url = !empty($hero['video_poster_desktop']) ? $hero['video_poster_desktop'] : '';
$hero_video_poster_mobile_url = !empty($hero['video_poster_mobile']) ? $hero['video_poster_mobile'] : '';

// Video settings
$video_autoplay = !empty($hero['video_autoplay']) ? 'autoplay' : '';
$video_loop = !empty($hero['video_loop']) ? 'loop' : '';
$video_muted = !empty($hero['video_muted']) ? 'muted' : '';

// Fallback to direct theme directory URI if no image found (with format fallbacks)
if (empty($hero_image_desktop_url)) {
	$hero_image_desktop_url = primefit_get_asset_uri(array('/assets/images/DSC03813.webp', '/assets/images/DSC03813.jpg', '/assets/images/DSC03813.jpeg', '/assets/images/DSC03813.png'));
}
if (empty($hero_image_mobile_url)) {
	$hero_image_mobile_url = primefit_get_asset_uri(array('/assets/images/DSC03756.webp', '/assets/images/DSC03756.jpg', '/assets/images/DSC03756.jpeg', '/assets/images/DSC03756.png'));
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
						<?php if (!empty($hero_video_poster_desktop_url)) : ?>poster="<?php echo esc_url($hero_video_poster_desktop_url); ?>"<?php endif; ?>
						playsinline
						preload="none"
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
						<?php if (!empty($hero_video_poster_mobile_url)) : ?>poster="<?php echo esc_url($hero_video_poster_mobile_url); ?>"<?php endif; ?>
						playsinline
						preload="none"
					>
						<source src="<?php echo esc_url($hero_video_mobile_url); ?>" type="video/mp4">
						Your browser does not support the video tag.
					</video>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		
		<!-- Optimized Hero Image with Modern Formats and Responsive Srcset -->
		<picture class="hero-fallback-image">
			<?php
			// Generate responsive srcset for desktop image
			$desktop_srcset = '';
			$desktop_sizes = array(480, 768, 1024, 1280, 1920);
			foreach ($desktop_sizes as $size) {
				$desktop_responsive_url = primefit_get_responsive_image_url($hero_image_desktop_url, $size);
				if ($desktop_responsive_url && $desktop_responsive_url !== $hero_image_desktop_url) {
					$desktop_srcset .= esc_url($desktop_responsive_url) . ' ' . $size . 'w, ';
				}
			}

			// Generate responsive srcset for mobile image
			$mobile_srcset = '';
			$mobile_sizes = array(320, 480, 768);
			foreach ($mobile_sizes as $size) {
				$mobile_responsive_url = primefit_get_responsive_image_url($hero_image_mobile_url, $size);
				if ($mobile_responsive_url && $mobile_responsive_url !== $hero_image_mobile_url) {
					$mobile_srcset .= esc_url($mobile_responsive_url) . ' ' . $size . 'w, ';
				}
			}

			// Get optimized WebP versions with fallback to original format
			$desktop_webp = primefit_get_optimized_image_url($hero_image_desktop_url, 'webp');
			$mobile_webp = primefit_get_optimized_image_url($hero_image_mobile_url, 'webp');

			// Add WebP sources if available
			if ($desktop_webp !== $hero_image_desktop_url) {
				$desktop_webp_srcset = '';
				foreach ($desktop_sizes as $size) {
					$desktop_webp_responsive = primefit_get_responsive_image_url($desktop_webp, $size);
					if ($desktop_webp_responsive && $desktop_webp_responsive !== $desktop_webp) {
						$desktop_webp_srcset .= esc_url($desktop_webp_responsive) . ' ' . $size . 'w, ';
					}
				}
				if ($desktop_webp_srcset) {
					echo '<source media="(min-width: 769px)" type="image/webp" srcset="' . rtrim($desktop_webp_srcset, ', ') . '">';
				}
			}

			if ($mobile_webp !== $hero_image_mobile_url) {
				$mobile_webp_srcset = '';
				foreach ($mobile_sizes as $size) {
					$mobile_webp_responsive = primefit_get_responsive_image_url($mobile_webp, $size);
					if ($mobile_webp_responsive && $mobile_webp_responsive !== $mobile_webp) {
						$mobile_webp_srcset .= esc_url($mobile_webp_responsive) . ' ' . $size . 'w, ';
					}
				}
				if ($mobile_webp_srcset) {
					echo '<source media="(max-width: 768px)" type="image/webp" srcset="' . rtrim($mobile_webp_srcset, ', ') . '">';
				}
			}
			?>

			<!-- Responsive img with srcset -->
			<img
				src="<?php echo esc_url($hero_image_desktop_url); ?>"
				srcset="<?php echo !empty($desktop_srcset) ? rtrim($desktop_srcset, ', ') : ''; ?>"
				sizes="(max-width: 768px) 100vw, (min-width: 769px) 100vw"
				alt="<?php echo esc_attr($hero['heading']); ?>"
				loading="eager"
				fetchpriority="high"
				decoding="async"
				class="hero-image"
				width="1920"
				height="1080"
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
