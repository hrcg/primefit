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

$product_id = $product->get_id();

// Try to get cached product data first
$cached_data = primefit_get_cached_product_data( $product_id );
if ( false !== $cached_data ) {
	extract( $cached_data );
} else {
	// Cache miss - get data and cache it
	$sku = $product->get_sku();
	$product_name = $product->get_name();
	$price_html = $product->get_price_html();
	$is_variable = $product->is_type( 'variable' );
	$is_in_stock = $product->is_in_stock();
	$stock_status = $product->get_stock_status();

	// Cache the data
	$product_data = compact( 'sku', 'product_name', 'price_html', 'is_variable', 'is_in_stock', 'stock_status' );
	primefit_cache_product_data( $product_id, $product_data );
}

// Cache variations data to avoid duplicate expensive calls
$variations = null;
$variations_json = null;
$variations_attr = null;
if ( $is_variable ) {
	$product_id = $product->get_id();
	
	// Try to get cached variations first
	$variations = primefit_get_cached_product_variations( $product_id );
	
	// If not cached, get from WooCommerce and cache it
	if ( false === $variations ) {
		$variations = $product->get_available_variations();
		primefit_cache_product_variations( $product_id, $variations );
	}
	
	$variations_json = wp_json_encode( $variations );
	$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
}

// Get product attributes for color/size selection with caching
$attributes = $product->get_attributes();
$color_attribute = null;
$size_attribute = null;

// Get variation attributes for better detection with caching (only for variable products)
$variation_attributes = $is_variable && method_exists( $product, 'get_variation_attributes' ) 
	? $product->get_variation_attributes() 
	: array();

// Get WooCommerce default attributes with caching
$default_attributes = $product->get_default_attributes();
$default_color = '';
$default_size = '';

// Extract default color and size from WooCommerce defaults
foreach ( $default_attributes as $attr_name => $attr_value ) {
	$clean_name = strtolower( str_replace( 'pa_', '', $attr_name ) );
	if ( ( $clean_name && stripos( $clean_name, 'color' ) !== false ) || 
		 ( $attr_name && stripos( $attr_name, 'color' ) !== false ) ||
		 ( $attr_name && stripos( $attr_name, 'pa_color' ) !== false ) ) {
		$default_color = $attr_value;
	}
	if ( ( $clean_name && stripos( $clean_name, 'size' ) !== false ) || 
		 ( $attr_name && stripos( $attr_name, 'size' ) !== false ) ||
		 ( $attr_name && stripos( $attr_name, 'pa_size' ) !== false ) ) {
		$default_size = $attr_value;
	}
}

foreach ( $attributes as $attribute ) {
	if ( $attribute->is_taxonomy() ) {
		$attribute_name = $attribute->get_name();
		$attribute_label = wc_attribute_label( $attribute_name );
		
		// Check for color attribute
		if ( stripos( $attribute_label, 'color' ) !== false || 
			 stripos( $attribute_name, 'color' ) !== false ||
			 stripos( $attribute_name, 'pa_color' ) !== false ) {
			$color_attribute = $attribute;
		}
		
		// Check for size attribute
		if ( stripos( $attribute_label, 'size' ) !== false || 
			 stripos( $attribute_name, 'size' ) !== false ||
			 stripos( $attribute_name, 'pa_size' ) !== false ) {
			$size_attribute = $attribute;
		}
	}
}

// If no size attribute found in taxonomy attributes, check variation attributes
if ( ! $size_attribute && ! empty( $variation_attributes ) ) {
	foreach ( $variation_attributes as $attr_name => $options ) {
		$clean_name = strtolower( str_replace( 'pa_', '', $attr_name ) );
		if ( in_array( $clean_name, array( 'size', 'sizes', 'clothing-size' ) ) || 
			 ( $clean_name && strpos( $clean_name, 'size' ) !== false ) ) {
			// Create a mock attribute object for consistency
			$size_attribute = (object) array(
				'get_name' => function() use ($attr_name) { return $attr_name; },
				'is_taxonomy' => function() { return true; }
			);
			break;
		}
	}
}
?>

<div class="product-details-container">
	<?php if ( $sku ) : ?>
		<div class="product-sku">
			<span class="sku-label"><?php echo esc_html( $sku ); ?></span>
		</div>
	<?php endif; ?>
	
	<h1 class="product-title"><?php echo esc_html( $product_name ); ?></h1>
	
	<?php if ( $color_attribute ) : ?>
		<div class="product-color">
			<span class="color-label"><?php echo esc_html( wc_attribute_label( $color_attribute->get_name() ) ); ?>:</span>
			<span class="color-value"><?php echo esc_html( $product->get_attribute( $color_attribute->get_name() ) ); ?></span>
		</div>
	<?php endif; ?>
	
	<div class="product-price">
		<?php echo $price_html; ?>
	</div>
	
	<?php if ( $is_variable && $variations ) : ?>
		<?php if ( $color_attribute ) : ?>
		<div class="product-color-selection">
			<div class="color-options">
				<?php
				$color_options = array();
				
				foreach ( $variations as $variation ) {
					$color_value = '';
					foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
						if ( stripos( $attr_name, 'color' ) !== false ) {
							$color_value = strtolower( trim( $attr_value ) );
							break;
						}
					}
					if ( $color_value && ! in_array( $color_value, $color_options ) ) {
						$color_options[] = $color_value;
					}
				}
				
				// Determine which color should be active (default or first)
				$default_color_index = 0;
				$normalized_default_color = $default_color ? strtolower( trim( $default_color ) ) : '';
				if ( $normalized_default_color && in_array( $normalized_default_color, $color_options ) ) {
					$default_color_index = array_search( $normalized_default_color, $color_options );
				}
				
				foreach ( $color_options as $index => $color_option ) :
					$color_name = wc_attribute_label( $color_option );
					$color_slug = sanitize_title( $color_option );
					$is_default_color = ( $index === $default_color_index );

					
					// Try to get variation image for this color
					$variation_image = '';
					$variation_id = '';
					$available_sizes = array();
					
					foreach ( $variations as $variation ) {
						$has_color_match = false;
						$current_size = '';
						
						// Check if this variation matches the current color
						foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
							if ( stripos( $attr_name, 'color' ) !== false && $attr_value === $color_option ) {
								$has_color_match = true;
								
								// Get variation image (use first one found)
								if ( ! empty( $variation['image']['src'] ) && empty( $variation_image ) ) {
									$variation_image = $variation['image']['src'];
								}
								
								// Get variation ID (use first one found)
								if ( empty( $variation_id ) ) {
									$variation_id = $variation['variation_id'];
								}
								
								break;
							}
						}
						
						// If this variation matches the color, get its size
						if ( $has_color_match ) {
							foreach ( $variation['attributes'] as $size_attr_name => $size_attr_value ) {
								if ( stripos( $size_attr_name, 'size' ) !== false ) {
									$current_size = $size_attr_value;
									break;
								}
							}
							
							// Add this size to available sizes if not already added
							if ( $current_size && ! in_array( $current_size, $available_sizes ) ) {
								$available_sizes[] = $current_size;
							}
						}
					}
					

				?>
					<button
						class="color-option <?php echo $is_default_color ? 'active' : ''; ?>"
						data-color="<?php echo esc_attr( $color_option ); ?>"
						data-variation-image="<?php echo esc_attr( $variation_image ); ?>"
						data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
						data-available-sizes="<?php echo esc_attr( json_encode( $available_sizes ) ); ?>"
						data-color-display="<?php echo esc_attr( $color_name ); ?>"
						aria-label="<?php printf( esc_attr__( 'Select color %s', 'primefit' ), $color_name ); ?>"
					>
						<?php if ( $variation_image ) : ?>
							<img src="<?php echo esc_url( $variation_image ); ?>" alt="<?php echo esc_attr( $color_name ); ?>" class="color-swatch">
						<?php else : ?>
							<span class="color-swatch color-<?php echo esc_attr( $color_slug ); ?>"></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	
	<?php if ( $is_variable && $size_attribute ) : ?>
		<div class="product-size-selection">
			<div class="size-options">
				<?php
				$size_options = array();
				$size_attribute_name = $size_attribute->get_name();
				
				// Get all unique size options from variations
				foreach ( $variations as $variation ) {
					$size_value = '';
					
					// Try different ways to get the size value
					if ( isset( $variation['attributes'][ $size_attribute_name ] ) ) {
						$size_value = $variation['attributes'][ $size_attribute_name ];
					} elseif ( isset( $variation['attributes']['attribute_' . $size_attribute_name] ) ) {
						$size_value = $variation['attributes']['attribute_' . $size_attribute_name];
					} else {
						// Fallback: look for any attribute containing 'size'
						foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
							if ( stripos( $attr_name, 'size' ) !== false ) {
								$size_value = $attr_value;
								break;
							}
						}
					}
					
					if ( $size_value && ! in_array( $size_value, $size_options ) ) {
						$size_options[] = $size_value;
					}
				}
				
				// Sort size options in a logical order
				$size_order = array( 'xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'small', 'medium', 'large', 'extra-small', 'extra-large' );
				usort( $size_options, function( $a, $b ) use ( $size_order ) {
					$a_index = array_search( strtolower( $a ), $size_order );
					$b_index = array_search( strtolower( $b ), $size_order );
					
					if ( $a_index === false && $b_index === false ) {
						return strcmp( $a, $b );
					} elseif ( $a_index === false ) {
						return 1;
					} elseif ( $b_index === false ) {
						return -1;
					} else {
						return $a_index - $b_index;
					}
				});
				
				// Determine which size should be selected (default or first available)
				$default_size_index = -1;
				if ( $default_size && in_array( $default_size, $size_options ) ) {
					$default_size_index = array_search( $default_size, $size_options );
				}
				
				foreach ( $size_options as $index => $size_option ) :
					$size_name = wc_attribute_label( $size_option );
					$size_slug = sanitize_title( $size_option );
					$is_selected_size = false;
					
					// Check if this size should be selected
					if ( $default_size_index >= 0 ) {
						// Use WooCommerce default size if available
						$is_selected_size = ( $index === $default_size_index );
					} else {
						// Fallback to first size if no default set
						$is_selected_size = ( $index === 0 );
					}
					
					// Check if this size is available for the default color
					$is_available_for_default_color = false;
					if ( ! empty( $color_options ) ) {
						$selected_color = $default_color ? $default_color : $color_options[0];
						
						// Get sizes available for the selected color
						foreach ( $variations as $variation ) {
							$has_color_match = false;
							$current_size = '';
							
							foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
								if ( stripos( $attr_name, 'color' ) !== false && $attr_value === $selected_color ) {
									$has_color_match = true;
								}
								if ( stripos( $attr_name, 'size' ) !== false ) {
									$current_size = $attr_value;
								}
							}
							
							if ( $has_color_match && $current_size === $size_option ) {
								$is_available_for_default_color = true;
								break;
							}
						}
					}
				?>
					<button 
						class="size-option <?php echo ( $is_selected_size && $is_available_for_default_color ) ? 'selected' : ''; ?>"
						data-size="<?php echo esc_attr( $size_option ); ?>"
						data-size-slug="<?php echo esc_attr( $size_slug ); ?>"
						aria-label="<?php printf( esc_attr__( 'Select size %s', 'primefit' ), $size_name ); ?>"
					>
						<?php echo esc_html( strtoupper( $size_name ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php 
			$size_guide_image = primefit_get_size_guide_image( $product->get_id() );
			if ( $size_guide_image ) : ?>
				<a href="#" class="size-guide-link" data-size-guide-image="<?php echo esc_url( $size_guide_image ); ?>"><?php esc_html_e( 'SIZE GUIDE', 'primefit' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if ( ! $is_in_stock ) : ?>
		<div class="stock-notice">
			<span class="stock-text"><?php esc_html_e( 'FINAL SALE // NO RETURNS OR EXCHANGES', 'primefit' ); ?></span>
		</div>
	<?php endif; ?>
	
	<div class="product-actions">
		<?php if ( $is_in_stock ) : ?>
			<?php if ( $is_variable && $variations ) : ?>
				<form class="primefit-variations-form variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; ?>">
					<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
					<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
					<input type="hidden" name="variation_id" class="variation_id" value="0" />
					
					<?php if ( $color_attribute ) : ?>
						<input type="hidden" name="attribute_<?php echo esc_attr( $color_attribute->get_name() ); ?>" class="attribute_color_input" value="" />
					<?php endif; ?>
					<?php if ( $size_attribute ) : ?>
						<input type="hidden" name="attribute_<?php echo esc_attr( $size_attribute->get_name() ); ?>" class="attribute_size_input" value="" />
					<?php endif; ?>
					
					<div class="quantity-input-wrapper">
						<?php primefit_quantity_input(
							array(
								'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
								'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
								'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
							),
							$product
						); ?>
					</div>
					
					<button type="submit" class="single_add_to_cart_button button alt ajax_add_to_cart" data-product_id="<?php echo absint( $product->get_id() ); ?>" disabled>
						<?php esc_html_e( 'SELECT OPTIONS', 'primefit' ); ?>
					</button>
				</form>
			<?php else : ?>
				<?php woocommerce_template_single_add_to_cart(); ?>
			<?php endif; ?>
		<?php else : ?>
			<button class="notify-button" type="button">
				<?php esc_html_e( 'NOTIFY WHEN AVAILABLE', 'primefit' ); ?>
			</button>
		<?php endif; ?>
	</div>
	
	<div class="product-collapsible-sections">
		<?php
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
		
	</div>
</div>

<script type="text/javascript">
window.primefitProductData = {
	variations: <?php echo json_encode( $variations ?? array() ); ?>,
	productId: <?php echo absint( $product->get_id() ); ?>,
	defaultColor: <?php echo json_encode( $default_color ); ?>,
	defaultSize: <?php echo json_encode( $default_size ); ?>,
	variationGalleries: <?php echo json_encode( primefit_get_variation_gallery_data( $product->get_id() ) ); ?>
};
</script>
