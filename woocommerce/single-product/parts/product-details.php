<?php
/**
 * Product Details Template
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

// Get WooCommerce default attributes
$default_attributes = $product->get_default_attributes();
$default_color = '';
$default_size = '';

// Extract default color and size from WooCommerce defaults
foreach ( $default_attributes as $attr_name => $attr_value ) {
	$clean_name = strtolower( str_replace( 'pa_', '', $attr_name ) );
	if ( stripos( $clean_name, 'color' ) !== false || 
		 stripos( $attr_name, 'color' ) !== false ||
		 stripos( $attr_name, 'pa_color' ) !== false ) {
		$default_color = $attr_value;
	}
	if ( stripos( $clean_name, 'size' ) !== false || 
		 stripos( $attr_name, 'size' ) !== false ||
		 stripos( $attr_name, 'pa_size' ) !== false ) {
		$default_size = $attr_value;
	}
}

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
				
				// Determine which color should be active (default or first)
				$default_color_index = 0;
				if ( $default_color && in_array( $default_color, $color_options ) ) {
					$default_color_index = array_search( $default_color, $color_options );
				}
				
				foreach ( $color_options as $index => $color_option ) :
					$color_name = wc_attribute_label( $color_option );
					$color_slug = sanitize_title( $color_option );
					$is_default_color = ( $index === $default_color_index );
				?>
					<button 
						class="color-option <?php echo $is_default_color ? 'active' : ''; ?>"
						data-color="<?php echo esc_attr( $color_option ); ?>"
						aria-label="<?php printf( esc_attr__( 'Select color %s', 'primefit' ), $color_name ); ?>"
					>
						<span class="color-swatch color-<?php echo esc_attr( $color_slug ); ?>"></span>
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
				
				// Determine which size should be selected (default or first)
				$default_size_index = -1;
				if ( $default_size && in_array( $default_size, $size_options ) ) {
					$default_size_index = array_search( $default_size, $size_options );
				}
				
				foreach ( $size_options as $index => $size_option ) :
					$size_name = wc_attribute_label( $size_option );
					$is_selected_size = false;
					
					// Check if this size should be selected
					if ( $default_size_index >= 0 ) {
						// Use WooCommerce default size if available
						$is_selected_size = ( $index === $default_size_index );
					} else {
						// Fallback to first size if no default set
						$is_selected_size = ( $index === 0 );
					}
				?>
					<button 
						class="size-option <?php echo $is_selected_size ? 'selected' : ''; ?>"
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
</div>
