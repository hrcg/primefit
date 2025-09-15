<?php
/**
 * Single Product Summary
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

$sku = $product->get_sku();
$product_name = $product->get_name();
$price_html = $product->get_price_html();
$is_variable = $product->is_type( 'variable' );
$is_in_stock = $product->is_in_stock();
$stock_status = $product->get_stock_status();

// Get product attributes for color/size selection
$attributes = $product->get_attributes();
$color_attribute = null;
$size_attribute = null;

foreach ( $attributes as $attribute ) {
	if ( $attribute->is_taxonomy() ) {
		$attribute_name = wc_attribute_label( $attribute->get_name() );
		if ( stripos( $attribute_name, 'color' ) !== false ) {
			$color_attribute = $attribute;
		} elseif ( stripos( $attribute_name, 'size' ) !== false ) {
			$size_attribute = $attribute;
		}
	}
}
?>

<div class="product-details-container">
	<!-- Product SKU -->
	<?php if ( $sku ) : ?>
		<div class="product-sku">
			<span class="sku-label"><?php echo esc_html( $sku ); ?></span>
		</div>
	<?php endif; ?>
	
	<!-- Product Title -->
	<h1 class="product-title"><?php echo esc_html( $product_name ); ?></h1>
	
	<!-- Product Color (if available) -->
	<?php if ( $color_attribute ) : ?>
		<div class="product-color">
			<span class="color-label"><?php echo esc_html( wc_attribute_label( $color_attribute->get_name() ) ); ?>:</span>
			<span class="color-value"><?php echo esc_html( $product->get_attribute( $color_attribute->get_name() ) ); ?></span>
		</div>
	<?php endif; ?>
	
	<!-- Product Price -->
	<div class="product-price">
		<?php echo $price_html; ?>
	</div>
	
	<!-- Color Selection (for variable products) -->
	<?php if ( $is_variable && $color_attribute ) : ?>
		<div class="product-color-selection">
			<span class="selection-label"><?php echo esc_html( wc_attribute_label( $color_attribute->get_name() ) ); ?>:</span>
			<div class="color-options">
				<?php
				$variations = $product->get_available_variations();
				$color_options = array();
				
				foreach ( $variations as $variation ) {
					$color_value = '';
					foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
						if ( stripos( $attr_name, 'color' ) !== false ) {
							$color_value = $attr_value;
							break;
						}
					}
					if ( $color_value && ! in_array( $color_value, $color_options ) ) {
						$color_options[] = $color_value;
					}
				}
				
				foreach ( $color_options as $index => $color_option ) :
					$color_name = wc_attribute_label( $color_option );
					$color_slug = sanitize_title( $color_option );
					
					// Try to get variation image for this color
					$variation_image = '';
					foreach ( $variations as $variation ) {
						foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
							if ( stripos( $attr_name, 'color' ) !== false && $attr_value === $color_option ) {
								if ( ! empty( $variation['image']['src'] ) ) {
									$variation_image = $variation['image']['src'];
									break 2;
								}
							}
						}
					}
				?>
					<button 
						class="color-option <?php echo $index === 0 ? 'active' : ''; ?>"
						data-color="<?php echo esc_attr( $color_option ); ?>"
						aria-label="<?php printf( esc_attr__( 'Select color %s', 'primefit' ), $color_name ); ?>"
					>
						<?php if ( $variation_image ) : ?>
							<img src="<?php echo esc_url( $variation_image ); ?>" alt="<?php echo esc_attr( $color_name ); ?>" class="color-swatch">
						<?php else : ?>
							<span class="color-swatch color-<?php echo esc_attr( $color_slug ); ?>"></span>
						<?php endif; ?>
						<span class="color-name"><?php echo esc_html( $color_name ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	
	<!-- Size Selection (for variable products) -->
	<?php if ( $is_variable && $size_attribute ) : ?>
		<div class="product-size-selection">
			<span class="selection-label"><?php echo esc_html( wc_attribute_label( $size_attribute->get_name() ) ); ?>:</span>
			<div class="size-options">
				<?php
				$size_options = array();
				foreach ( $variations as $variation ) {
					$size_value = '';
					foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
						if ( stripos( $attr_name, 'size' ) !== false ) {
							$size_value = $attr_value;
							break;
						}
					}
					if ( $size_value && ! in_array( $size_value, $size_options ) ) {
						$size_options[] = $size_value;
					}
				}
				
				foreach ( $size_options as $size_option ) :
					$size_name = wc_attribute_label( $size_option );
				?>
					<button 
						class="size-option"
						data-size="<?php echo esc_attr( $size_option ); ?>"
						aria-label="<?php printf( esc_attr__( 'Select size %s', 'primefit' ), $size_name ); ?>"
					>
						<?php echo esc_html( $size_name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<a href="#" class="size-guide-link"><?php esc_html_e( 'SIZE GUIDE', 'primefit' ); ?></a>
		</div>
	<?php endif; ?>
	
	<!-- Stock Status Notice -->
	<?php if ( ! $is_in_stock ) : ?>
		<div class="stock-notice">
			<span class="stock-text"><?php esc_html_e( 'FINAL SALE // NO RETURNS OR EXCHANGES', 'primefit' ); ?></span>
		</div>
	<?php endif; ?>
	
	<!-- Add to Cart / Notify Button -->
	<div class="product-actions">
		<?php if ( $is_in_stock ) : ?>
			<?php woocommerce_template_single_add_to_cart(); ?>
		<?php else : ?>
			<button class="notify-button" type="button">
				<?php esc_html_e( 'NOTIFY WHEN AVAILABLE', 'primefit' ); ?>
			</button>
		<?php endif; ?>
	</div>
	
	<!-- Collapsible Sections -->
	<div class="product-collapsible-sections">
		<?php
		// Description Section
		$description = $product->get_description();
		if ( $description ) :
		?>
			<div class="collapsible-section">
				<button class="collapsible-toggle" type="button">
					<span class="collapsible-title"><?php esc_html_e( 'DESCRIPTION', 'primefit' ); ?></span>
					<span class="collapsible-icon">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</button>
				<div class="collapsible-content">
					<div class="collapsible-inner">
						<?php echo wp_kses_post( wpautop( $description ) ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		
		<?php
		// Additional Information Section
		$additional_info = $product->get_short_description();
		if ( $additional_info ) :
		?>
			<div class="collapsible-section">
				<button class="collapsible-toggle" type="button">
					<span class="collapsible-title"><?php esc_html_e( 'DESIGNED FOR', 'primefit' ); ?></span>
					<span class="collapsible-icon">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</button>
				<div class="collapsible-content">
					<div class="collapsible-inner">
						<?php echo wp_kses_post( wpautop( $additional_info ) ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		
		<?php
		// Fabric + Technology Section (from ACF or custom fields)
		$fabric_tech = get_field( 'fabric_technology', get_the_ID() );
		if ( $fabric_tech ) :
		?>
			<div class="collapsible-section">
				<button class="collapsible-toggle" type="button">
					<span class="collapsible-title"><?php esc_html_e( 'FABRIC + TECHNOLOGY', 'primefit' ); ?></span>
					<span class="collapsible-icon">
						<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</button>
				<div class="collapsible-content">
					<div class="collapsible-inner">
						<?php echo wp_kses_post( wpautop( $fabric_tech ) ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	// Collapsible sections functionality
	const collapsibleToggles = document.querySelectorAll('.collapsible-toggle');
	
	collapsibleToggles.forEach(toggle => {
		toggle.addEventListener('click', function() {
			const content = this.nextElementSibling;
			const icon = this.querySelector('.collapsible-icon');
			
			if (content.classList.contains('open')) {
				content.classList.remove('open');
				icon.style.transform = 'rotate(0deg)';
			} else {
				content.classList.add('open');
				icon.style.transform = 'rotate(180deg)';
			}
		});
	});
	
	// Color selection functionality
	const colorOptions = document.querySelectorAll('.color-option');
	colorOptions.forEach(option => {
		option.addEventListener('click', function() {
			// Remove active class from all options
			colorOptions.forEach(opt => opt.classList.remove('active'));
			// Add active class to clicked option
			this.classList.add('active');
		});
	});
	
	// Size selection functionality
	const sizeOptions = document.querySelectorAll('.size-option');
	sizeOptions.forEach(option => {
		option.addEventListener('click', function() {
			// Remove selected class from all options
			sizeOptions.forEach(opt => opt.classList.remove('selected'));
			// Add selected class to clicked option
			this.classList.add('selected');
		});
	});
});
</script>
