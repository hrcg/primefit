<?php
/**
 * Product Technical Highlights Template
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

// Get technical highlights from custom fields
$highlights = get_post_meta( $product->get_id(), 'primefit_technical_highlights', true );

if ( empty( $highlights ) ) {
	return;
}

// Parse highlights (expecting JSON format)
$highlights_data = json_decode( $highlights, true );

if ( ! is_array( $highlights_data ) || empty( $highlights_data ) ) {
	return;
}
?>

<div class="product-technical-highlights-container">
	<h2 class="technical-highlights-title"><?php esc_html_e( 'TECHNICAL HIGHLIGHTS', 'primefit' ); ?></h2>
	
	<div class="technical-highlights-grid">
		<?php foreach ( $highlights_data as $highlight ) : ?>
			<?php
			$highlight_title = isset( $highlight['title'] ) ? $highlight['title'] : '';
			$highlight_icon = isset( $highlight['icon'] ) ? $highlight['icon'] : '';
			$highlight_description = isset( $highlight['description'] ) ? $highlight['description'] : '';
			
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
