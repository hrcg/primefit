<?php
/**
 * Product Features Template
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

// Get product features from custom fields
$features = get_post_meta( $product->get_id(), 'primefit_product_features', true );

if ( empty( $features ) ) {
	return;
}

// Parse features (expecting JSON format)
$features_data = json_decode( $features, true );

if ( ! is_array( $features_data ) || empty( $features_data ) ) {
	return;
}
?>

<div class="product-features-container">
	<h2 class="features-title"><?php esc_html_e( 'FEATURES', 'primefit' ); ?></h2>
	
	<div class="features-grid">
		<?php foreach ( $features_data as $feature ) : ?>
			<?php
			$feature_title = isset( $feature['title'] ) ? $feature['title'] : '';
			$feature_image_id = isset( $feature['image'] ) ? intval( $feature['image'] ) : 0;
			$feature_description = isset( $feature['description'] ) ? $feature['description'] : '';
			
			if ( empty( $feature_title ) ) {
				continue;
			}
			?>
			<div class="feature-item">
				<?php if ( $feature_image_id ) : ?>
					<div class="feature-image">
						<?php echo wp_get_attachment_image( $feature_image_id, 'medium', false, array( 'class' => 'feature-img' ) ); ?>
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
