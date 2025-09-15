<?php
/**
 * Training Division Section
 * 
 * Usage: get_template_part('parts/sections/training-division', null, $args);
 * 
 * Expected $args:
 * - 'heading' => main heading text
 * - 'subheading' => subheading/description text
 * - 'cta_primary_text' => primary call-to-action button text
 * - 'cta_primary_link' => primary call-to-action button URL
 * - 'cta_secondary_text' => secondary call-to-action button text
 * - 'cta_secondary_link' => secondary call-to-action button URL
 * - 'image' => background image path
 */

// Get customizer configuration
$customizer_config = primefit_get_training_division_config();

// Set defaults (merge customizer config with passed args)
$defaults = array(
	'heading' => $customizer_config['heading'],
	'subheading' => $customizer_config['subheading'],
	'cta_primary_text' => $customizer_config['cta_primary_text'],
	'cta_primary_link' => $customizer_config['cta_primary_link'],
	'cta_secondary_text' => $customizer_config['cta_secondary_text'],
	'cta_secondary_link' => $customizer_config['cta_secondary_link'],
	'image' => $customizer_config['image'],
	'show_secondary_button' => $customizer_config['show_secondary_button']
);

$section = wp_parse_args($args ?? array(), $defaults);

// Generate unique ID for this section instance
$section_id = 'training-division-' . uniqid();
?>

<section class="training-division" id="<?php echo esc_attr($section_id); ?>">
	<div class="training-division-media">
		<picture>
			<img 
				src="<?php echo esc_url($section['image']); ?>" 
				alt="<?php echo esc_attr($section['heading']); ?>" 
				loading="lazy"
				class="training-division-image"
			/>
		</picture>
		<div class="training-division-overlay"></div>
	</div>
	
	<div class="training-division-content">
		<div class="container">
			<div class="training-division-text">
				<?php if (!empty($section['heading'])) : ?>
					<h2 class="training-division-heading"><?php echo esc_html($section['heading']); ?></h2>
				<?php endif; ?>
				
				<?php if (!empty($section['subheading'])) : ?>
					<p class="training-division-subheading"><?php echo esc_html($section['subheading']); ?></p>
				<?php endif; ?>
				
				<div class="training-division-actions">
					<?php if (!empty($section['cta_primary_text']) && !empty($section['cta_primary_link'])) : ?>
						<a href="<?php echo esc_url($section['cta_primary_link']); ?>" class="training-division-button button button--primary">
							<?php echo esc_html($section['cta_primary_text']); ?>
						</a>
					<?php endif; ?>
					
					<?php if ($section['show_secondary_button'] && !empty($section['cta_secondary_text']) && !empty($section['cta_secondary_link'])) : ?>
						<a href="<?php echo esc_url($section['cta_secondary_link']); ?>" class="training-division-button button button--outline">
							<?php echo esc_html($section['cta_secondary_text']); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
