<?php
/**
 * After Single Product Summary
 *
 * @package PrimeFit
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

// Get product features using ACF with fallback to legacy meta
$features_data = primefit_get_product_features( $product->get_id() );
$highlights_data = primefit_get_technical_highlights( $product->get_id() );
?>

<?php if ( ! empty( $features_data ) && is_array( $features_data ) ) : ?>
	<!-- Product Features Section -->
	<div class="product-features">
		<div class="product-features-container">
			<h2 class="features-title"><?php esc_html_e( 'FEATURES', 'primefit' ); ?></h2>
			
			<div class="features-grid">
				<?php foreach ( $features_data as $feature ) : ?>
					<?php
					$feature_title = isset( $feature['title'] ) ? $feature['title'] : '';
					// Handle both ACF format (direct ID) and legacy format (nested array)
					$feature_image_id = 0;
					if ( isset( $feature['image'] ) ) {
						if ( is_array( $feature['image'] ) ) {
							// Legacy format - image is an array with ID
							$feature_image_id = intval( $feature['image'] );
						} else {
							// ACF format - image is direct ID
							$feature_image_id = intval( $feature['image'] );
						}
					}
					$feature_description = isset( $feature['description'] ) ? $feature['description'] : '';
					
					if ( empty( $feature_title ) ) {
						continue;
					}
					?>
					<div class="feature-item">
						<?php if ( $feature_image_id ) : ?>
							<div class="feature-image">
								<?php echo wp_get_attachment_image( $feature_image_id, 'full', false, array( 'class' => 'feature-img' ) ); ?>
							</div>
						<?php endif; ?>
						
						<div class="feature-content">
							<h3 class="feature-title"><?php echo esc_html( $feature_title ); ?></h3>
							<?php if ( $feature_description ) : ?>
								<p class="feature-description"><?php echo esc_html( $feature_description ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( ! empty( $highlights_data ) && is_array( $highlights_data ) ) : ?>
	<!-- Technical Highlights Section -->
	<div class="product-technical-highlights">
		<div class="product-technical-highlights-container">
			<h2 class="technical-highlights-title"><?php esc_html_e( 'TECHNICAL HIGHLIGHTS', 'primefit' ); ?></h2>
			
			<div class="technical-highlights-grid">
				<?php foreach ( $highlights_data as $highlight ) : ?>
					<?php
					$highlight_title = isset( $highlight['title'] ) ? $highlight['title'] : '';
					$highlight_description = isset( $highlight['description'] ) ? $highlight['description'] : '';
					
					// Handle image upload (new ACF format) and legacy SVG code format
					$highlight_icon = '';
					
					if ( isset( $highlight['icon_image'] ) ) {
						// New ACF format - uploaded image
						$image_id = intval( $highlight['icon_image'] );
						if ( $image_id ) {
							$highlight_icon = wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'highlight-icon-img' ) );
						}
					} elseif ( isset( $highlight['icon'] ) ) {
						// Legacy format - direct SVG code (for backward compatibility)
						$highlight_icon = $highlight['icon'];
					}
					
					if ( empty( $highlight_title ) ) {
						continue;
					}
					?>
					<div class="technical-highlight-item">
						<?php if ( $highlight_icon ) : ?>
							<div class="highlight-icon">
								<?php echo wp_kses_post( $highlight_icon ); ?>
							</div>
						<?php endif; ?>
						
						<div class="highlight-content">
							<h3 class="highlight-title"><?php echo esc_html( $highlight_title ); ?></h3>
							<?php if ( $highlight_description ) : ?>
								<p class="highlight-description"><?php echo esc_html( $highlight_description ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
<?php endif; ?>
