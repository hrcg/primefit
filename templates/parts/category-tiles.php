<?php
/**
 * Category Tiles Section
 * 
 * Usage: get_template_part('templates/parts/category-tiles');
 * 
 * Gets configuration from WordPress Customizer
 */

// Get category tiles configuration from customizer
$category_tiles_config = primefit_get_category_tiles_config();

// Don't render if disabled
if ( ! $category_tiles_config['enabled'] ) {
	return;
}

$section = $category_tiles_config;
?>

<section class="container tiles-3">
	<?php foreach ($section['tiles'] as $tile) : ?>
		<div class="tile">
			<img 
				src="<?php echo esc_url( $tile['image'] ); ?>" 
				alt="<?php echo esc_attr( $tile['alt'] ); ?>" 
			/>
			<a href="<?php echo esc_url( $tile['url'] ); ?>" class="tile-label">
				<?php echo esc_html( $tile['label'] ); ?>
			</a>
			<div class="tile-overlay">
				<div class="tile-content">
					<p class="tile-description"><?php echo esc_html( $tile['description'] ); ?></p>
					<a href="<?php echo esc_url( $tile['url'] ); ?>" class="tile-button button button--primary">
						<?php echo esc_html( $tile['button_text'] ); ?>
					</a>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</section>
