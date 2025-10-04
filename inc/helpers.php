<?php
/**
 * PrimeFit Theme Helper Functions
 *
 * Utility and helper functions for theme functionality
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get first existing asset URI from a list of candidates relative to theme dir
 *
 * @param array $candidates Array of relative paths to check
 * @return string Asset URI or empty string if none found
 */
function primefit_get_asset_uri( array $candidates ) {
	foreach ( $candidates as $relative ) {
		$path = get_theme_file_path( $relative );
		if ( file_exists( $path ) ) {
			return get_theme_file_uri( $relative );
		}
	}
	return '';
}

/**
 * Get file modification time for cache busting
 *
 * @param string $file_path Relative path to file from theme directory
 * @return string|int File modification time or theme version as fallback
 */
function primefit_get_file_version( $file_path ) {
	$full_path = get_theme_file_path( $file_path );
	
	if ( file_exists( $full_path ) ) {
		return filemtime( $full_path );
	}
	
	// Fallback to theme version if file doesn't exist
	return PRIMEFIT_VERSION;
}

/**
 * Get optimized image URL with modern format support
 *
 * @param string $image_url Original image URL
 * @param string $format Desired format (webp, avif)
 * @return string Optimized image URL or original if not available
 */
function primefit_get_optimized_image_url( $image_url, $format = 'webp' ) {
	if ( empty( $image_url ) ) {
		return $image_url;
	}

	// Check if the image is already in the desired format
	$current_format = strtolower( pathinfo( $image_url, PATHINFO_EXTENSION ) );
	if ( $current_format === $format ) {
		return $image_url;
	}

	// Generate optimized URL
	$optimized_url = str_replace( ['.jpg', '.jpeg', '.png'], '.' . $format, $image_url );

	// Check if optimized version exists (for local images)
	if ( strpos( $optimized_url, home_url() ) === 0 ) {
		$local_path = str_replace( home_url(), ABSPATH, $optimized_url );
		if ( file_exists( $local_path ) ) {
			return $optimized_url;
		}
	}

	// For theme assets, check multiple fallback formats
	if ( strpos( $image_url, get_template_directory_uri() ) === 0 ) {
		// Try multiple fallback formats
		$fallback_formats = ['webp', 'jpg', 'jpeg', 'png'];

		foreach ( $fallback_formats as $fallback_format ) {
			if ( $fallback_format === $format ) {
				continue; // Skip the requested format as we've already tried it
			}

			$fallback_url = str_replace( ['.jpg', '.jpeg', '.png', '.webp'], '.' . $fallback_format, $image_url );

			if ( strpos( $fallback_url, get_template_directory_uri() ) === 0 ) {
				$local_path = str_replace( get_template_directory_uri(), get_template_directory(), $fallback_url );

				if ( file_exists( $local_path ) ) {
					return $fallback_url;
				}
			}
		}
	}

	return $image_url; // Return original if no optimized version found
}

/**
 * Get high-quality optimized image URL for hero images
 *
 * @param string $image_url Original image URL
 * @param string $format Desired format (webp, avif)
 * @return string High-quality optimized image URL or original if not available
 */
function primefit_get_hero_optimized_image_url( $image_url, $format = 'webp' ) {
	if ( empty( $image_url ) ) {
		return $image_url;
	}

	// Check if the image is already in the desired format
	$current_format = strtolower( pathinfo( $image_url, PATHINFO_EXTENSION ) );
	if ( $current_format === $format ) {
		return $image_url;
	}

	// Generate optimized URL with high quality
	$optimized_url = str_replace( ['.jpg', '.jpeg', '.png'], '.' . $format, $image_url );

	// Check if optimized version exists (for local images)
	if ( strpos( $optimized_url, home_url() ) === 0 ) {
		$local_path = str_replace( home_url(), ABSPATH, $optimized_url );
		if ( file_exists( $local_path ) ) {
			return $optimized_url;
		}
	}

	// For theme assets, check multiple fallback formats
	if ( strpos( $image_url, get_template_directory_uri() ) === 0 ) {
		// Try multiple fallback formats
		$fallback_formats = ['webp', 'jpg', 'jpeg', 'png'];

		foreach ( $fallback_formats as $fallback_format ) {
			if ( $fallback_format === $format ) {
				continue; // Skip the requested format as we've already tried it
			}

			$fallback_url = str_replace( ['.jpg', '.jpeg', '.png', '.webp'], '.' . $fallback_format, $image_url );

			if ( strpos( $fallback_url, get_template_directory_uri() ) === 0 ) {
				$local_path = str_replace( get_template_directory_uri(), get_template_directory(), $fallback_url );

				if ( file_exists( $local_path ) ) {
					return $fallback_url;
				}
			}
		}
	}

	return $image_url; // Return original if no optimized version found
}

/**
 * Get responsive image URL for specific dimensions
 *
 * @param string $image_url Original image URL
 * @param int $width Target width
 * @return string Responsive image URL or original if not available
 */
function primefit_get_responsive_image_url( $image_url, $width ) {
	if ( empty( $image_url ) ) {
		return $image_url;
	}

	// For theme assets, try to find responsive versions
	if ( strpos( $image_url, get_template_directory_uri() ) === 0 ) {
		$path_info = pathinfo( $image_url );
		$dirname = $path_info['dirname'];
		$filename = $path_info['filename'];
		$extension = $path_info['extension'];

		// Try common responsive naming patterns
		$responsive_patterns = [
			"{$dirname}/{$filename}-{$width}w.{$extension}",
			"{$dirname}/{$filename}@{$width}w.{$extension}",
			"{$dirname}/{$width}/{$filename}.{$extension}",
		];

		foreach ( $responsive_patterns as $pattern ) {
			$local_path = str_replace( get_template_directory_uri(), get_template_directory(), $pattern );
			if ( file_exists( $local_path ) ) {
				return $pattern;
			}
		}
	}

	// For uploaded images, try WordPress responsive image sizes
	if ( strpos( $image_url, home_url() ) === 0 ) {
		// Try to get attachment ID and generate responsive URL
		$attachment_id = primefit_get_attachment_id_from_url( $image_url );
		if ( $attachment_id ) {
			$responsive_url = wp_get_attachment_image_url( $attachment_id, array( $width, $width ) );
			if ( $responsive_url ) {
				return $responsive_url;
			}
		}
	}

	return $image_url; // Return original if no responsive version found
}

/**
 * Get attachment ID from image URL
 *
 * @param string $image_url Image URL
 * @return int|null Attachment ID or null if not found
 */
function primefit_get_attachment_id_from_url( $image_url ) {
	global $wpdb;

	if ( empty( $image_url ) ) {
		return null;
	}

	// Remove query parameters
	$url = strtok( $image_url, '?' );

	// Get attachment ID from URL
	$attachment_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE guid = %s OR post_name = %s",
			$url,
			basename( $url )
		)
	);

	return $attachment_id ? (int) $attachment_id : null;
}

/**
 * Generate responsive image sources for modern formats (helper function)
 *
 * @param string $image_url Original image URL
 * @param array $sizes Array of sizes (e.g., ['400w', '800w'])
 * @return array Array of responsive sources
 */
function primefit_generate_responsive_sources_helper( $image_url, $sizes = [] ) {
	if ( empty( $image_url ) ) {
		return [];
	}

	$sources = [];
	$formats = ['webp'];

	foreach ( $formats as $format ) {
		$optimized_url = primefit_get_optimized_image_url( $image_url, $format );
		if ( $optimized_url !== $image_url ) {
			$srcset_parts = [];
			foreach ( $sizes as $size ) {
				$srcset_parts[] = $optimized_url . ' ' . $size;
			}
			$sources[$format] = implode( ', ', $srcset_parts );
		}
	}

	return $sources;
}

/**
 * Get best available image URI with format fallback
 *
 * @param array $urls Array of image URLs with fallback formats
 * @return string Best available image URL
 */
function primefit_get_best_image_uri( $urls = [] ) {
	if ( empty( $urls ) || ! is_array( $urls ) ) {
		return '';
	}

	foreach ( $urls as $url ) {
		if ( empty( $url ) ) {
			continue;
		}

		// If it's a relative path, convert to full URL
		if ( strpos( $url, 'http' ) !== 0 ) {
			$url = get_template_directory_uri() . $url;
		}

		// Check if file exists
		if ( strpos( $url, get_template_directory_uri() ) === 0 ) {
			$local_path = str_replace( get_template_directory_uri(), get_template_directory(), $url );

			if ( file_exists( $local_path ) ) {
				return $url;
			}
		} else {
			// For external URLs, just return the first one
			return $url;
		}
	}

	// If no files exist, return the first URL anyway as fallback
	return $urls[0];
}

/**
 * Get hero image for WooCommerce category
 *
 * @param object $category WooCommerce category object
 * @param string $size Image size (default: 'large')
 * @return string Hero image URL
 * @since 1.0.0
 */
function primefit_get_category_hero_image( $category, $size = 'large' ) {
	$category_image_url = '';
	
	// Get category image from WooCommerce
	$category_image_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
	
	if ( $category_image_id ) {
		$category_image_url = wp_get_attachment_image_url( $category_image_id, $size );
	}
	
	// Fallback to default hero image if no category image
	if ( empty( $category_image_url ) ) {
		$category_image_url = primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png'));
	}
	
	return $category_image_url;
}

/**
 * Regenerate optimized thumbnails for product images
 * This should be run once after adding new image sizes
 */
function primefit_regenerate_product_thumbnails() {
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	
	// Get all product images
	$args = array(
		'post_type' => 'product',
		'posts_per_page' => -1,
		'post_status' => 'publish'
	);
	
	$products = get_posts( $args );
	$processed_images = array();
	
	foreach ( $products as $product ) {
		$product_obj = wc_get_product( $product->ID );
		if ( ! $product_obj ) continue;
		
		// Get main image
		$main_image_id = $product_obj->get_image_id();
		if ( $main_image_id && ! in_array( $main_image_id, $processed_images ) ) {
			$processed_images[] = $main_image_id;
			wp_generate_attachment_metadata( $main_image_id, get_attached_file( $main_image_id ) );
		}
		
		// Get gallery images
		$gallery_ids = $product_obj->get_gallery_image_ids();
		foreach ( $gallery_ids as $gallery_id ) {
			if ( ! in_array( $gallery_id, $processed_images ) ) {
				$processed_images[] = $gallery_id;
				wp_generate_attachment_metadata( $gallery_id, get_attached_file( $gallery_id ) );
			}
		}
	}
	
	return count( $processed_images );
}

/**
 * Get optimized product image URL with fallback
 */
function primefit_get_optimized_product_image_url( $image_id, $size = 'primefit-product-loop' ) {
	if ( ! $image_id ) {
		return '';
	}
	
	// Try to get the optimized size first
	$image_url = wp_get_attachment_image_url( $image_id, $size );
	
	// Fallback to medium if optimized size doesn't exist
	if ( ! $image_url ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'medium' );
	}
	
	// Final fallback to full size
	if ( ! $image_url ) {
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );
	}
	
	return $image_url;
}

/**
 * Get shop hero configuration
 *
 * @since 1.0.0
 * @return array Hero configuration for shop pages
 */
function primefit_get_shop_hero_config() {
	$hero_args = array();
	
	if ( is_shop() ) {
		$hero_args = array(
			'image_desktop' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png')),
			'image_mobile' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png')),
			'heading' => 'SHOP ALL',
			'subheading' => 'DISCOVER OUR COMPLETE COLLECTION OF PREMIUM FITNESS APPAREL',
			'cta_text' => '',
			'cta_link' => '',
			'overlay_position' => 'center',
			'text_color' => 'light'
		);
	} elseif ( is_product_category() ) {
		$category = get_queried_object();
		
		// Get category hero image (with automatic fallback)
		$hero_image = primefit_get_category_hero_image( $category );
		
		// Generate subheading with fallback
		$subheading = '';
		if ( !empty( $category->description ) ) {
			$subheading = wp_strip_all_tags( $category->description );
			// Limit description length for hero display
			if ( strlen( $subheading ) > 80 ) {
				$subheading = wp_trim_words( $subheading, 12, '...' );
			}
			$subheading = strtoupper( $subheading );
		} else {
			$subheading = 'EXPLORE OUR ' . strtoupper( html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ) ) . ' COLLECTION';
		}
		
		$hero_args = array(
			'image_desktop' => $hero_image,
			'image_mobile' => $hero_image,
			'heading' => strtoupper( html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ) ),
			'subheading' => $subheading,
			'cta_text' => '',
			'cta_link' => '',
			'overlay_position' => 'center',
			'text_color' => 'light'
		);
		
		// Allow customization via filter
		$hero_args = apply_filters( 'primefit_category_hero_args', $hero_args, $category );
	} elseif ( is_product_tag() ) {
		$tag = get_queried_object();
		$hero_args = array(
			'image_desktop' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png')),
			'image_mobile' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png')),
			'heading' => strtoupper( $tag->name ),
			'subheading' => 'PRODUCTS TAGGED: ' . strtoupper( $tag->name ),
			'cta_text' => '',
			'cta_link' => '',
			'overlay_position' => 'center',
			'text_color' => 'light'
		);
	}
	
	return $hero_args;
}



/**
 * Render size selection overlay for variable products in product loops
 *
 * @param WC_Product $product Variable product object
 */
function primefit_render_size_selection_overlay( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}
	
	// Get available variations
	$available_variations = $product->get_available_variations();
	
	if ( empty( $available_variations ) ) {
		return;
	}
	
	// Get size attribute (common attribute names for size)
	$size_attribute = '';
	$size_options = array();
	
	// Look for size-related attributes
	$attributes = $product->get_variation_attributes();
	
	// First, look for attributes containing 'size' - your products have 'pa_size'
	foreach ( $attributes as $attribute_name => $options ) {
		$clean_name = strtolower( str_replace( 'pa_', '', $attribute_name ) );
		if ( in_array( $clean_name, array( 'size', 'sizes', 'clothing-size' ) ) || strpos( $clean_name, 'size' ) !== false ) {
			$size_attribute = $attribute_name;
			$size_options = $options;
			break;
		}
	}
	
	// If no size attribute found, look for common clothing size patterns
	if ( empty( $size_attribute ) ) {
		foreach ( $attributes as $attribute_name => $options ) {
			// Check if options contain typical clothing sizes
			$typical_sizes = array( 'xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'small', 'medium', 'large', 'extra-small', 'extra-large' );
			$option_values = array_map( 'strtolower', $options );
			
			if ( array_intersect( $typical_sizes, $option_values ) ) {
				$size_attribute = $attribute_name;
				$size_options = $options;
				break;
			}
		}
	}
	
	if ( empty( $size_options ) ) {
		return;
	}
	
	// Build variation data for JavaScript with stock information
	$variation_data = array();
	$all_variations_data = array(); // Store all variations for dynamic filtering
	
	foreach ( $available_variations as $variation ) {
		$variation_obj = wc_get_product( $variation['variation_id'] );
		if ( ! $variation_obj ) {
			continue;
		}
		
		// Try different ways to get the size value
		$size_value = '';
		$color_value = '';
		
		// Method 1: Direct attribute access
		if ( isset( $variation['attributes'][ $size_attribute ] ) ) {
			$size_value = $variation['attributes'][ $size_attribute ];
		}
		
		// Method 2: Try with attribute_ prefix
		$attr_key = 'attribute_' . $size_attribute;
		if ( empty( $size_value ) && isset( $variation['attributes'][ $attr_key ] ) ) {
			$size_value = $variation['attributes'][ $attr_key ];
		}
		
		// Method 3: Get from variation object
		if ( empty( $size_value ) ) {
			$size_value = $variation_obj->get_attribute( $size_attribute );
		}
		
		// Get color value for filtering
		foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
			if ( stripos( $attr_name, 'color' ) !== false ) {
				$color_value = $attr_value;
				break;
			}
		}
		
		if ( ! empty( $size_value ) ) {
			$is_in_stock = $variation_obj->is_in_stock();
			$stock_quantity = $variation_obj->get_stock_quantity();
			$max_purchase_quantity = $variation_obj->get_max_purchase_quantity();
			
			$variation_info = array(
				'variation_id' => $variation['variation_id'],
				'is_in_stock' => $is_in_stock,
				'stock_quantity' => $stock_quantity,
				'max_purchase_quantity' => $max_purchase_quantity,
				'color' => $color_value,
				'size' => $size_value,
				'url' => $product->get_permalink() . '?' . http_build_query( array(
					'attribute_' . str_replace( 'pa_', '', $size_attribute ) => $size_value
				) )
			);
			
			// Store all variations for dynamic filtering
			$all_variations_data[ $variation['variation_id'] ] = $variation_info;
			
			// Only add to variation_data if in stock (for backward compatibility)
			if ( $is_in_stock ) {
				$variation_data[ $size_value ] = $variation_info;
			}
		}
	}
	
	if ( empty( $all_variations_data ) ) {
		return;
	}
	
	?>
	<div class="product-size-options<?php echo isset( $_GET['debug_sizes'] ) ? ' debug-visible' : ''; ?>" 
		 data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
		 data-variations="<?php echo esc_attr( wp_json_encode( $all_variations_data ) ); ?>">
		<div class="size-options">
			<?php 
			// Get default color for initial size button setup
			$default_color = '';
			$default_attributes = $product->get_default_attributes();
			foreach ( $default_attributes as $attr_name => $attr_value ) {
				if ( stripos( $attr_name, 'color' ) !== false ) {
					$default_color = $attr_value;
					break;
				}
			}
			
			// If no default color, use the first available color
			if ( empty( $default_color ) ) {
				foreach ( $all_variations_data as $variation_info ) {
					if ( ! empty( $variation_info['color'] ) ) {
						$default_color = $variation_info['color'];
						break;
					}
				}
			}
			
			foreach ( $size_options as $size_value ) : ?>
				<?php 
				// Find variation with this size and default color
				$size_variation = null;
				foreach ( $all_variations_data as $variation_info ) {
					if ( $variation_info['size'] === $size_value && $variation_info['color'] === $default_color ) {
						$size_variation = $variation_info;
						break;
					}
				}
				
				// If no variation found for default color, find any variation with this size
				if ( ! $size_variation ) {
					foreach ( $all_variations_data as $variation_info ) {
						if ( $variation_info['size'] === $size_value ) {
							$size_variation = $variation_info;
							break;
						}
					}
				}
				
				// If still no variation found, create a placeholder
				if ( ! $size_variation ) {
					$size_variation = array(
						'variation_id' => 0,
						'is_in_stock' => false,
						'stock_quantity' => 0,
						'max_purchase_quantity' => 0,
						'color' => $default_color,
						'size' => $size_value,
						'url' => ''
					);
				}
				?>
				<button class="size-option <?php echo ! $size_variation['is_in_stock'] ? 'out-of-stock' : ''; ?>" 
				   data-size="<?php echo esc_attr( $size_value ); ?>"
				   data-variation-id="<?php echo esc_attr( $size_variation['variation_id'] ); ?>"
				   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
				   data-is-in-stock="<?php echo $size_variation['is_in_stock'] ? 'true' : 'false'; ?>"
				   data-stock-quantity="<?php echo esc_attr( $size_variation['stock_quantity'] ); ?>"
				   data-max-purchase-quantity="<?php echo esc_attr( $size_variation['max_purchase_quantity'] ); ?>"
				   data-color="<?php echo esc_attr( $size_variation['color'] ); ?>"
				   <?php echo ! $size_variation['is_in_stock'] ? 'disabled' : ''; ?>>
					<?php echo esc_html( strtoupper( $size_value ) ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Debug function to test size overlay rendering
 * Add ?debug_sizes=1 to any page URL to see debug info
 */
add_action( 'wp_footer', 'primefit_debug_size_overlay' );
function primefit_debug_size_overlay() {
	if ( isset( $_GET['debug_sizes'] ) && $_GET['debug_sizes'] == '1' ) {
		// SECURITY: Only allow admin users to see debug info
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div style="position: fixed; bottom: 0; left: 0; background: black; color: white; padding: 10px; z-index: 9999; max-width: 400px; font-size: 12px; max-height: 300px; overflow-y: auto;">
			<strong>Size Overlay Debug:</strong><br>

			<?php
			global $woocommerce_loop;
			if ( isset( $woocommerce_loop['is_shortcode'] ) && $woocommerce_loop['is_shortcode'] ) {
				echo '✓ WooCommerce shortcode detected<br>';
			}

			if ( function_exists( 'wc_get_products' ) ) {
				$variable_products = wc_get_products( array(
					'type' => 'variable',
					'limit' => 5,
					'status' => 'publish'
				) );

				echo 'Variable products found: ' . count( $variable_products ) . '<br>';

				foreach ( $variable_products as $product ) {
					echo '<hr style="margin: 5px 0;">';
					echo '<strong>Product:</strong> ' . esc_html( $product->get_name() ) . '<br>';
					$attributes = $product->get_variation_attributes();
					echo '<strong>Attributes:</strong> ' . esc_html( implode( ', ', array_keys( $attributes ) ) ) . '<br>';

					foreach ( $attributes as $attr_name => $options ) {
						echo '<strong>' . esc_html( $attr_name ) . ':</strong> ' . esc_html( implode( ', ', $options ) ) . '<br>';
					}

					$variations = $product->get_available_variations();
					echo '<strong>Variations:</strong> ' . count( $variations ) . '<br>';
				}
			}
			?>
		</div>
		<?php
	}
}

/**
 * Get WooCommerce product categories for shop page
 *
 * @param array $args Arguments for get_terms query
 * @return array Array of category data for display
 * @since 1.0.0
 */
function primefit_get_shop_categories( $args = array() ) {
	// Default arguments
	$defaults = array(
		'taxonomy' => 'product_cat',
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'hide_empty' => true,
		'parent' => 0, // Only top-level categories by default
		'number' => 12, // Limit number of categories
		'include_subcategories' => false // New parameter
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	// If including subcategories, remove parent restriction
	if ( $args['include_subcategories'] ) {
		unset( $args['parent'] );
	}
	
	// Get categories
	$categories = get_terms( $args );
	
	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return array();
	}
	
	$category_data = array();
	
	foreach ( $categories as $category ) {
		// Get category image
		$image_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
		$image_url = '';
		
		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'large' );
		}
		
		// Fallback to default category images if no custom image
		if ( empty( $image_url ) ) {
			$image_url = primefit_get_default_category_image( $category );
		}
		
		// Get product count
		$product_count = $category->count;
		
		// Build category description
		$description = ! empty( $category->description ) 
			? wp_trim_words( wp_strip_all_tags( $category->description ), 12, '...' )
			: sprintf( __( 'Explore our %s collection with %d products', 'primefit' ), 
					  strtolower( html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ) ), 
					  $product_count );
		
		// Check if this is a subcategory
		$is_subcategory = $category->parent > 0;
		$parent_category = null;
		
		if ( $is_subcategory ) {
			$parent_category = get_term( $category->parent, 'product_cat' );
		}
		
		$category_data[] = array(
			'id' => $category->term_id,
			'name' => html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ),
			'slug' => $category->slug,
			'description' => $description,
			'image' => $image_url,
			'url' => get_term_link( $category ),
			'count' => $product_count,
			'button_text' => sprintf( __( 'Shop %s', 'primefit' ), esc_html( html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ) ) ),
			'is_subcategory' => $is_subcategory,
			'parent_name' => $parent_category ? esc_html( html_entity_decode( $parent_category->name, ENT_QUOTES, 'UTF-8' ) ) : null,
			'parent_slug' => $parent_category ? $parent_category->slug : null
		);
	}
	
	return $category_data;
}

/**
 * Get default category image based on category name/slug
 *
 * @param object $category WooCommerce category object
 * @return string Default image URL
 * @since 1.0.0
 */
function primefit_get_default_category_image( $category ) {
	$default_images = array(
		'run' => primefit_get_asset_uri(array('/assets/images/run.webp', '/assets/images/run.jpg', '/assets/images/run.jpeg', '/assets/images/run.png')),
		'train' => primefit_get_asset_uri(array('/assets/images/train.webp', '/assets/images/train.jpg', '/assets/images/train.jpeg', '/assets/images/train.png')),
		'rec' => primefit_get_asset_uri(array('/assets/images/rec.webp', '/assets/images/rec.jpg', '/assets/images/rec.jpeg', '/assets/images/rec.png'))
	);
	
	$category_slug = strtolower( $category->slug );
	
	// Check if we have a specific image for this category
	foreach ( $default_images as $slug_pattern => $image_path ) {
		if ( strpos( $category_slug, $slug_pattern ) !== false ) {
			$image_url = primefit_get_asset_uri( array( $image_path ) );
			if ( ! empty( $image_url ) ) {
				return $image_url;
			}
		}
	}
	
	// Fallback to hero image
	return primefit_get_asset_uri( array( '/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png' ) );
}

/**
 * Render shop categories grid
 *
 * @param array $args Configuration arguments
 * @return void
 * @since 1.0.0
 */
function primefit_render_shop_categories( $args = array() ) {
	$defaults = array(
		'title' => __( 'Shop by Category', 'primefit' ),
		'columns' => 4,
		'limit' => 12,
		'show_count' => true,
		'hide_empty' => true,
		'parent' => 0,
		'section_class' => 'shop-categories',
		'include_subcategories' => false
	);
	
	$section = wp_parse_args( $args, $defaults );
	
	// Get categories
	$category_args = array(
		'number' => $section['limit'],
		'hide_empty' => $section['hide_empty'],
		'parent' => $section['parent'],
		'include_subcategories' => $section['include_subcategories']
	);
	
	$categories = primefit_get_shop_categories( $category_args );
	
	if ( empty( $categories ) ) {
		return;
	}
	
	// Build CSS classes
	$section_classes = array(
		$section['section_class'],
		'category-grid',
		'category-grid--' . $section['columns'] . '-columns'
	);
	
	$section_classes = implode( ' ', array_filter( $section_classes ) );
	?>
	<section class="<?php echo esc_attr( $section_classes ); ?>">
		<div class="container">
			<?php if ( ! empty( $section['title'] ) ) : ?>
			<?php endif; ?>
			
			<div class="category-grid-content">
				<?php foreach ( $categories as $category ) : ?>
					<div class="category-tile<?php echo $category['is_subcategory'] ? ' category-tile--subcategory' : ''; ?>" 
						 data-category-name="<?php echo esc_attr( strtoupper( html_entity_decode( $category['name'], ENT_QUOTES, 'UTF-8' ) ) ); ?>"
						 data-parent-category="<?php echo esc_attr( $category['parent_slug'] ); ?>">
						<div class="category-tile-inner">
							<div class="category-tile-image">
								<img src="<?php echo esc_url( $category['image'] ); ?>" 
								     alt="<?php echo esc_attr( html_entity_decode( $category['name'], ENT_QUOTES, 'UTF-8' ) ); ?>" 
								     loading="lazy" />
							</div>
							
							<div class="category-tile-overlay">
								<div class="category-tile-content">
									<h3 class="category-tile-title">
										<a href="<?php echo esc_url( $category['url'] ); ?>">
											<?php echo esc_html( html_entity_decode( $category['name'], ENT_QUOTES, 'UTF-8' ) ); ?>
										</a>
									</h3>
									
									<div class="category-tile-actions">
										<a href="<?php echo esc_url( $category['url'] ); ?>" class="category-tile-button">
											VIEW PRODUCTS
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render hero section with improved abstraction
 *
 * @param array $args Hero configuration arguments
 * @return void
 */
function primefit_render_hero( $args = array() ) {
	// Set defaults
	$defaults = array(
		'image_desktop' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
		'image_mobile' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
		'heading' => 'END OF SEASON SALE',
		'subheading' => 'UP TO 60% OFF. LIMITED TIME ONLY. WHILE SUPPLIES LAST.',
		'cta_text' => 'SHOP NOW',
		'cta_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
		'overlay_position' => 'left',
		'text_color' => 'light',
		'height' => 'auto', // 'auto', 'small', 'medium', 'large', 'full'
		'parallax' => false,
		'overlay_opacity' => 0.4
	);

	$hero = wp_parse_args( $args, $defaults );
	
	// Generate unique ID for this hero instance
	$hero_id = 'hero-' . uniqid();
	
	// Get hero image URLs
	$hero_image_desktop_url = !empty($hero['image_desktop']) ? $hero['image_desktop'] : primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg'));
	$hero_image_mobile_url = !empty($hero['image_mobile']) ? $hero['image_mobile'] : primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg'));
	
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
	
	// Fallback to direct theme directory URI if no image found
	if ( empty( $hero_image_desktop_url ) ) {
		$hero_image_desktop_url = primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png'));
	}
	if ( empty( $hero_image_mobile_url ) ) {
		$hero_image_mobile_url = primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg', '/assets/images/hero-image.jpeg', '/assets/images/hero-image.png'));
	}
	
	// Build CSS classes
	$hero_classes = array(
		'hero',
		'hero--' . $hero['overlay_position'],
		'hero--' . $hero['text_color'],
		'hero--' . $hero['height']
	);
	
	if ( $hero['parallax'] ) {
		$hero_classes[] = 'hero--parallax';
	}
	
	$hero_classes = implode( ' ', $hero_classes );
	?>
	<section class="<?php echo esc_attr( $hero_classes ); ?>" id="<?php echo esc_attr( $hero_id ); ?>">
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
			
		<!-- High Quality Fallback Image (always present for loading state and fallback) -->
		<picture class="hero-fallback-image">
			<?php
			// Get WebP versions for better compression while maintaining quality
			$desktop_webp = primefit_get_hero_optimized_image_url($hero_image_desktop_url, 'webp');
			$mobile_webp = primefit_get_hero_optimized_image_url($hero_image_mobile_url, 'webp');

			// Add WebP sources if available (but keep full resolution)
			if ($desktop_webp !== $hero_image_desktop_url) {
				echo '<source media="(min-width: 769px)" type="image/webp" srcset="' . esc_url($desktop_webp) . '">';
			}

			if ($mobile_webp !== $hero_image_mobile_url) {
				echo '<source media="(max-width: 768px)" type="image/webp" srcset="' . esc_url($mobile_webp) . '">';
			}
			?>
			<source media="(max-width: 768px)" srcset="<?php echo esc_url( $hero_image_mobile_url ); ?>">
			<img
				src="<?php echo esc_url( $hero_image_desktop_url ); ?>"
				alt="<?php echo esc_attr( $hero['heading'] ); ?>"
				loading="eager"
				fetchpriority="high"
				decoding="async"
				class="hero-image"
				width="1920"
				height="1080"
			/>
		</picture>
			<div class="hero-overlay" style="opacity: <?php echo esc_attr( $hero['overlay_opacity'] ); ?>;"></div>
		</div>
		
		<div class="hero-content">
			<div class="container">
				<div class="hero-text hero-text--<?php echo esc_attr($hero['overlay_position']); ?> hero-text--<?php echo esc_attr($hero['text_color']); ?>">
					<?php if ( ! empty( $hero['heading'] ) ) : ?>
						<h1 class="hero-heading"><?php echo esc_html( $hero['heading'] ); ?></h1>
					<?php endif; ?>
					
					<?php if ( ! empty( $hero['subheading'] ) ) : ?>
						<p class="hero-subheading"><?php echo esc_html( $hero['subheading'] ); ?></p>
					<?php endif; ?>
					
					<?php if ( ! empty( $hero['cta_text'] ) && ! empty( $hero['cta_link'] ) ) : ?>
						<div class="hero-cta">
							<a href="<?php echo esc_url( $hero['cta_link'] ); ?>" class="training-division-button button button--primary">
								<?php echo esc_html( $hero['cta_text'] ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Get hero configuration for different page types
 *
 * @param string $page_type Type of page ('home', 'shop', 'category', 'tag')
 * @param array $custom_args Custom arguments to override defaults
 * @return array Hero configuration
 */
function primefit_get_hero_config_for_page( $page_type = 'home', $custom_args = array() ) {
	$configs = array(
		'home' => primefit_get_hero_config(),
		'shop' => primefit_get_shop_hero_config(),
		'category' => primefit_get_shop_hero_config(),
		'tag' => primefit_get_shop_hero_config(),
	);
	
	$config = isset( $configs[ $page_type ] ) ? $configs[ $page_type ] : $configs['home'];
	
	// Merge with custom arguments
	return wp_parse_args( $custom_args, $config );
}

/**
 * Render product loop section with improved abstraction
 *
 * @param array $args Product loop configuration arguments
 * @return void
 */
function primefit_render_product_loop( $args = array() ) {
	// Set defaults
	$defaults = array(
		'title' => 'PRODUCTS',
		'limit' => 8,
		'columns' => 4,
		'orderby' => 'date',
		'order' => 'DESC',
		'visibility' => 'visible',
		'category' => '',
		'tag' => '',
		'featured' => false,
		'on_sale' => false,
		'best_selling' => false,
		'show_view_all' => true,
		'view_all_text' => 'VIEW ALL',
		'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
		'section_class' => 'product-section',
		'header_alignment' => 'center',
		'layout' => 'grid', // 'grid', 'carousel', 'list'
		'show_pagination' => false,
		'products_per_page' => 12
	);

	$section = wp_parse_args( $args, $defaults );

	// Generate cache key based on all relevant parameters
	$cache_key = primefit_get_product_loop_cache_key( $section );

	// Try to get cached content first
	$cached_content = get_transient( $cache_key );

	if ( $cached_content !== false ) {
		echo $cached_content;
		return;
	}

	// Build WooCommerce shortcode attributes
	$shortcode_atts = array(
		'limit' => absint( $section['limit'] ),
		'columns' => absint( $section['columns'] ),
		'orderby' => sanitize_text_field( $section['orderby'] ),
		'order' => sanitize_text_field( $section['order'] ),
		'visibility' => sanitize_text_field( $section['visibility'] )
	);

	// Add conditional attributes
	if ( ! empty( $section['category'] ) ) {
		$shortcode_atts['category'] = sanitize_text_field( $section['category'] );
	}

	if ( ! empty( $section['tag'] ) ) {
		$shortcode_atts['tag'] = sanitize_text_field( $section['tag'] );
	}

	if ( $section['featured'] ) {
		$shortcode_atts['featured'] = 'true';
	}

	if ( $section['on_sale'] ) {
		$shortcode_atts['on_sale'] = 'true';
	}

	if ( $section['best_selling'] ) {
		$shortcode_atts['best_selling'] = 'true';
	}

	// Build shortcode string
	$shortcode_string = '[products';
	foreach ( $shortcode_atts as $key => $value ) {
		$shortcode_string .= ' ' . $key . '="' . esc_attr( $value ) . '"';
	}
	$shortcode_string .= ']';

	// Build CSS classes
	$section_classes = array(
		$section['section_class'],
		'product-loop',
		'product-loop--' . $section['layout'],
		'product-loop--' . $section['columns'] . '-columns'
	);

	$section_classes = implode( ' ', array_filter( $section_classes ) );

	ob_start();
	?>
	<section class="<?php echo esc_attr( $section_classes ); ?>">
		<div class="container">
			<?php if ( ! empty( $section['title'] ) ) : ?>
				<?php
				get_template_part( 'templates/parts/section-header', null, array(
					'title' => $section['title'],
					'alignment' => $section['header_alignment']
				) );
				?>
			<?php endif; ?>

			<div class="product-loop-content">
				<?php echo do_shortcode( $shortcode_string ); ?>
			</div>

			<?php if ( $section['show_view_all'] && ! empty( $section['view_all_text'] ) ) : ?>
				<div class="product-loop-actions">
					<a href="<?php echo esc_url( $section['view_all_link'] ); ?>" class="button button--outline">
						<?php echo esc_html( $section['view_all_text'] ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( $section['show_pagination'] ) : ?>
				<div class="product-loop-pagination">
					<?php
					// This would need custom pagination logic for WooCommerce products
					// For now, we'll use the default WooCommerce pagination
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	$content = ob_get_clean();

	// Cache the content for 15 minutes (900 seconds)
	set_transient( $cache_key, $content, 900 );

	echo $content;
}

/**
 * Generate cache key for product loop based on parameters
 *
 * @param array $section Product loop section parameters
 * @return string Cache key
 */
function primefit_get_product_loop_cache_key( $section ) {
	$key_parts = array(
		'primefit_product_loop',
		'limit_' . $section['limit'],
		'columns_' . $section['columns'],
		'orderby_' . $section['orderby'],
		'order_' . $section['order'],
		'visibility_' . $section['visibility']
	);

	// Add conditional parameters only if they exist
	if ( ! empty( $section['category'] ) ) {
		$key_parts[] = 'category_' . sanitize_title( $section['category'] );
	}

	if ( ! empty( $section['tag'] ) ) {
		$key_parts[] = 'tag_' . sanitize_title( $section['tag'] );
	}

	if ( $section['featured'] ) {
		$key_parts[] = 'featured';
	}

	if ( $section['on_sale'] ) {
		$key_parts[] = 'on_sale';
	}

	if ( $section['best_selling'] ) {
		$key_parts[] = 'best_selling';
	}

	// Add current language for multilingual support
	if ( function_exists( 'wpml_get_current_language' ) ) {
		$key_parts[] = 'lang_' . wpml_get_current_language();
	}

	return implode( '_', $key_parts );
}

/**
 * Clear all product loop caches
 *
 * This function should be called whenever products are updated, added, or removed
 */
function primefit_clear_product_loop_caches() {
	global $wpdb;

	// Delete all transients that start with our product loop cache prefix
	$cache_prefix = 'primefit_product_loop_';
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . $cache_prefix ) . '%',
			$wpdb->esc_like( '_transient_timeout_' . $cache_prefix ) . '%'
		)
	);
}

/**
 * Clear category tiles configuration cache
 */
function primefit_clear_category_tiles_cache() {
	delete_transient( 'primefit_category_tiles_config' );
}

/**
 * Clear hero configuration cache
 */
function primefit_clear_hero_config_cache() {
	delete_transient( 'primefit_hero_config' );
}

/**
 * Clear all caches when products are modified
 */
add_action( 'save_post_product', 'primefit_invalidate_caches_on_product_change', 10, 3 );
add_action( 'delete_post', 'primefit_invalidate_caches_on_product_deletion' );
add_action( 'woocommerce_product_set_stock', 'primefit_invalidate_caches_on_stock_change' );
add_action( 'woocommerce_update_product', 'primefit_invalidate_caches_on_product_update' );

function primefit_invalidate_caches_on_product_change( $post_id, $post, $update ) {
	// Only clear cache if this is a product post
	if ( $post->post_type !== 'product' ) {
		return;
	}

	// Clear all product loop caches
	primefit_clear_product_loop_caches();
}

function primefit_invalidate_caches_on_product_deletion( $post_id ) {
	$post = get_post( $post_id );

	// Only clear cache if this is a product post
	if ( $post && $post->post_type === 'product' ) {
		primefit_clear_product_loop_caches();
	}
}

function primefit_invalidate_caches_on_stock_change( $product ) {
	primefit_clear_product_loop_caches();
}

function primefit_invalidate_caches_on_product_update( $product_id ) {
	primefit_clear_product_loop_caches();
}

/**
 * Clear caches when theme customizer settings are updated
 */
add_action( 'customize_save_after', 'primefit_invalidate_caches_on_customizer_save' );
function primefit_invalidate_caches_on_customizer_save() {
	// Clear category tiles cache when customizer is saved
	primefit_clear_category_tiles_cache();

	// Clear hero config cache
	primefit_clear_hero_config_cache();

	// Clear all product loop caches in case settings affect product display
	primefit_clear_product_loop_caches();
}

/**
 * Clear caches when theme is switched
 */
add_action( 'switch_theme', 'primefit_invalidate_all_caches_on_theme_switch' );
function primefit_invalidate_all_caches_on_theme_switch() {
	primefit_clear_product_loop_caches();
	primefit_clear_category_tiles_cache();
	primefit_clear_hero_config_cache();
}

/**
 * Get product loop configuration for different contexts
 *
 * @param string $context Context ('featured', 'new', 'sale', 'category', 'custom')
 * @param array $custom_args Custom arguments to override defaults
 * @return array Product loop configuration
 */
function primefit_get_product_loop_config( $context = 'featured', $custom_args = array() ) {
	$configs = array(
		'featured' => array(
			'title' => 'FEATURED PRODUCTS',
			'limit' => 8,
			'columns' => 4,
			'featured' => true,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		),
		'new' => array(
			'title' => 'NEW ARRIVALS',
			'limit' => 8,
			'columns' => 4,
			'orderby' => 'date',
			'order' => 'DESC'
		),
		'sale' => array(
			'title' => 'ON SALE',
			'limit' => 8,
			'columns' => 4,
			'on_sale' => true,
			'orderby' => 'date',
			'order' => 'DESC'
		),
		'best_selling' => array(
			'title' => 'BEST SELLERS',
			'limit' => 8,
			'columns' => 4,
			'best_selling' => true,
			'orderby' => 'popularity',
			'order' => 'DESC'
		),
		'category' => array(
			'title' => 'CATEGORY PRODUCTS',
			'limit' => 12,
			'columns' => 4,
			'orderby' => 'date',
			'order' => 'DESC'
		),
		'custom' => array(
			'title' => 'PRODUCTS',
			'limit' => 8,
			'columns' => 4,
			'orderby' => 'date',
			'order' => 'DESC'
		)
	);
	
	$config = isset( $configs[ $context ] ) ? $configs[ $context ] : $configs['custom'];
	
	// Merge with custom arguments
	return wp_parse_args( $custom_args, $config );
}

/**
 * Register shortcodes for easy use of abstracted functions
 */
add_action( 'init', 'primefit_register_shortcodes' );
function primefit_register_shortcodes() {
	// Hero section shortcode
	add_shortcode( 'primefit_hero', 'primefit_hero_shortcode' );
	
	// Product loop shortcode
	add_shortcode( 'primefit_products', 'primefit_products_shortcode' );
	
	// Shop categories shortcode
	add_shortcode( 'primefit_shop_categories', 'primefit_shop_categories_shortcode' );
}

/**
 * Hero section shortcode
 * Usage: [primefit_hero heading="My Title" subheading="My subtitle" cta_text="Shop Now" cta_link="/shop"]
 */
function primefit_hero_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'heading' => 'HERO TITLE',
		'subheading' => 'Hero subtitle text',
		'cta_text' => 'SHOP NOW',
		'cta_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
		'overlay_position' => 'center',
		'text_color' => 'light',
		'height' => 'medium',
		'image' => ''
	), $atts );
	
	// Convert image string to array if provided
	if ( ! empty( $atts['image'] ) ) {
		$atts['image'] = array( $atts['image'] );
	}
	
	ob_start();
	primefit_render_hero( $atts );
	return ob_get_clean();
}

/**
 * Product loop shortcode
 * Usage: [primefit_products title="Featured Products" limit="8" columns="4" featured="true"]
 */
function primefit_products_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'title' => 'PRODUCTS',
		'limit' => '8',
		'columns' => '4',
		'orderby' => 'date',
		'order' => 'DESC',
		'category' => '',
		'tag' => '',
		'featured' => 'false',
		'on_sale' => 'false',
		'best_selling' => 'false',
		'show_view_all' => 'true',
		'view_all_text' => 'VIEW ALL',
		'view_all_link' => function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '#',
		'section_class' => 'product-section'
	), $atts );
	
	// Convert string booleans to actual booleans
	$atts['featured'] = $atts['featured'] === 'true';
	$atts['on_sale'] = $atts['on_sale'] === 'true';
	$atts['best_selling'] = $atts['best_selling'] === 'true';
	$atts['show_view_all'] = $atts['show_view_all'] === 'true';
	
	ob_start();
	primefit_render_product_loop( $atts );
	return ob_get_clean();
}

/**
 * Shop categories shortcode
 * Usage: [primefit_shop_categories title="Shop by Category" columns="4" limit="12"]
 */
function primefit_shop_categories_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'title' => 'Shop by Category',
		'columns' => '4',
		'limit' => '12',
		'show_count' => 'true',
		'hide_empty' => 'true',
		'parent' => '0',
		'section_class' => 'shop-categories'
	), $atts );
	
	// Convert string values to appropriate types
	$atts['columns'] = absint( $atts['columns'] );
	$atts['limit'] = absint( $atts['limit'] );
	$atts['parent'] = absint( $atts['parent'] );
	$atts['show_count'] = $atts['show_count'] === 'true';
	$atts['hide_empty'] = $atts['hide_empty'] === 'true';
	
	ob_start();
	primefit_render_shop_categories( $atts );
	return ob_get_clean();
}

/**
 * Custom quantity input with plus/minus buttons
 * 
 * @param array $args Arguments for the quantity input
 * @param WC_Product|null $product Product object
 * @param bool $echo Whether to echo or return the output
 * @return string|void
 */
function primefit_quantity_input( $args = array(), $product = null, $echo = true ) {
	if ( is_null( $product ) ) {
		$product = $GLOBALS['product'];
	}

	$defaults = array(
		'input_id'     => uniqid( 'quantity_' ),
		'input_name'   => 'quantity',
		'input_value'  => '1',
		'classes'      => apply_filters( 'woocommerce_quantity_input_classes', array( 'input-text', 'qty', 'text' ), $product ),
		'max_value'    => apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
		'min_value'    => apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
		'step'         => apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
		'pattern'      => apply_filters( 'woocommerce_quantity_input_pattern', has_filter( 'woocommerce_stock_amount', 'intval' ) ? '[0-9]*' : '' ),
		'inputmode'    => apply_filters( 'woocommerce_quantity_input_inputmode', has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'numeric' : '' ),
		'product_name' => $product ? $product->get_title() : '',
		'placeholder'  => apply_filters( 'woocommerce_quantity_input_placeholder', '', $product ),
	);

	$args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );

	// Apply sanity to min/max args - min cannot be lower than 0.
	$args['min_value'] = max( $args['min_value'], 0 );
	$args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : '';

	// Max cannot be lower than min if defined.
	if ( '' !== $args['max_value'] && $args['max_value'] < $args['min_value'] ) {
		$args['max_value'] = $args['min_value'];
	}

	$classes = array_map( 'sanitize_html_class', $args['classes'] );

	ob_start();
	?>
	<div class="quantity">
		<button type="button" class="minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'primefit' ); ?>"></button>
		<input
			type="number"
			id="<?php echo esc_attr( $args['input_id'] ); ?>"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			name="<?php echo esc_attr( $args['input_name'] ); ?>"
			value="<?php echo esc_attr( $args['input_value'] ); ?>"
			aria-label="<?php esc_attr_e( 'Product quantity', 'primefit' ); ?>"
			size="4"
			min="<?php echo esc_attr( $args['min_value'] ); ?>"
			max="<?php echo esc_attr( 0 < $args['max_value'] ? $args['max_value'] : '' ); ?>"
			<?php if ( ! empty( $args['step'] ) ) : ?>
				step="<?php echo esc_attr( $args['step'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $args['placeholder'] ) ) : ?>
				placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $args['inputmode'] ) ) : ?>
				inputmode="<?php echo esc_attr( $args['inputmode'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $args['pattern'] ) ) : ?>
				pattern="<?php echo esc_attr( $args['pattern'] ); ?>"
			<?php endif; ?>
		/>
		<button type="button" class="plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'primefit' ); ?>"></button>
	</div>
	<?php

	$output = ob_get_clean();

	if ( $echo ) {
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		return $output;
	}
}

/**
 * Render color swatches for product loop
 *
 * @param WC_Product $product The product object
 * @return void
 */
function primefit_render_product_loop_color_swatches( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}
	
	// Get product attributes
	$attributes = $product->get_attributes();
	$color_attribute = null;
	
	// Find color attribute
	foreach ( $attributes as $attribute ) {
		if ( $attribute->is_taxonomy() ) {
			$attribute_name = wc_attribute_label( $attribute->get_name() );
			if ( stripos( $attribute_name, 'color' ) !== false ) {
				$color_attribute = $attribute;
				break;
			}
		}
	}
	
	if ( ! $color_attribute ) {
		return;
	}
	
	// Get variations and color options
	$variations = $product->get_available_variations();
	$color_options = array();
	$variation_images = array();
	
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
			// Store variation image for this color
			if ( ! empty( $variation['image']['src'] ) ) {
				$variation_images[ $color_value ] = $variation['image']['src'];
			} elseif ( ! empty( $variation['image_id'] ) ) {
				$variation_images[ $color_value ] = wp_get_attachment_image_url( $variation['image_id'], 'large' );
			}
		}
	}
	
	if ( empty( $color_options ) ) {
		return;
	}
	
	// Get default color
	$default_attributes = $product->get_default_attributes();
	$default_color = '';
	foreach ( $default_attributes as $attr_name => $attr_value ) {
		if ( stripos( $attr_name, 'color' ) !== false ) {
			$default_color = $attr_value;
			break;
		}
	}
	
	echo '<div class="product-loop-color-swatches">';
	
	foreach ( $color_options as $index => $color_option ) :
		$color_name = wc_attribute_label( $color_option );
		$color_slug = sanitize_title( $color_option );
		$is_default_color = ( $color_option === $default_color ) || ( $index === 0 && empty( $default_color ) );
		$variation_image = isset( $variation_images[ $color_option ] ) ? $variation_images[ $color_option ] : '';
		
		// If no variation image, use the main product image
		if ( empty( $variation_image ) ) {
			$main_image_id = $product->get_image_id();
			if ( $main_image_id ) {
				$variation_image = wp_get_attachment_image_url( $main_image_id, 'large' );
			} else {
				$variation_image = '';
			}
		}

		// Add cache busting parameter to prevent image caching issues
		if ( ! empty( $variation_image ) ) {
			$variation_image = add_query_arg( 'v', '1.0', $variation_image );
		}

		// Debug: Log variation image data
		if ( WP_DEBUG && $variation_image ) {
		}
		
		?>
		<button 
			class="color-swatch <?php echo $is_default_color ? 'active default-color' : ''; ?>"
			data-color="<?php echo esc_attr( $color_option ); ?>"
			data-variation-image="<?php echo esc_attr( $variation_image ); ?>"
			data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
			aria-label="<?php printf( esc_attr__( 'Select color %s', 'primefit' ), $color_name ); ?>"
			title="<?php echo esc_attr( $color_name ); ?>"
		>
			<span class="color-swatch-inner color-<?php echo esc_attr( $color_slug ); ?>"></span>
		</button>
		<?php
	endforeach;
	
	echo '</div>';
}

/**
 * Get cached categories with transient caching for expensive operations
 * 
 * @param array $args Arguments for get_terms()
 * @return array|WP_Error Array of term objects or WP_Error
 */
function primefit_get_cached_categories( $args = [] ) {
	// Create cache key based on arguments
	$cache_key = 'primefit_categories_' . md5( serialize( $args ) );
	$cached = get_transient( $cache_key );
	
	if ( false === $cached ) {
		$cached = get_terms( $args );
		// Cache for 48 hours for better performance (increased from 24 hours)
		set_transient( $cache_key, $cached, 48 * HOUR_IN_SECONDS );
	}
	
	return $cached;
}

/**
 * Get cached products with transient caching for expensive operations
 * 
 * @param array $args Arguments for get_posts()
 * @return array Array of post objects
 */
function primefit_get_cached_products( $args = [] ) {
	// Create cache key based on arguments
	$cache_key = 'primefit_products_' . md5( serialize( $args ) );
	$cached = get_transient( $cache_key );
	
	if ( false === $cached ) {
		$cached = get_posts( $args );
		// Cache for 24 hours for better performance (increased from 12 hours)
		set_transient( $cache_key, $cached, 24 * HOUR_IN_SECONDS );
	}
	
	return $cached;
}

/**
 * Clear cached data when products or categories are updated
 */
add_action( 'save_post', 'primefit_clear_product_cache' );
add_action( 'delete_post', 'primefit_clear_product_cache' );
add_action( 'created_term', 'primefit_clear_category_cache' );
add_action( 'edited_term', 'primefit_clear_category_cache' );
add_action( 'delete_term', 'primefit_clear_category_cache' );

function primefit_clear_product_cache( $post_id ) {
	if ( get_post_type( $post_id ) === 'product' ) {
		// Clear all product-related transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_products_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_products_%'" );
		
		// Clear recommended products cache
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_recommended_products_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_recommended_products_%'" );
		
		// Update cache timestamp for better cache invalidation
		update_option( 'primefit_last_cache_update', time() );
	}
}

function primefit_clear_category_cache( $term_id ) {
	$term = get_term( $term_id );
	if ( $term && in_array( $term->taxonomy, [ 'product_cat', 'product_tag' ] ) ) {
		// Clear all category-related transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_categories_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_categories_%'" );
		
		// Update cache timestamp for better cache invalidation
		update_option( 'primefit_last_cache_update', time() );
	}
}

/**
 * Cache WooCommerce product variations data
 *
 * @param int $product_id Product ID
 * @param array $variations Variations data
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_product_variations( $product_id, $variations, $expiration = 3600 ) {
	$cache_key = "product_variations_{$product_id}";
	wp_cache_set( $cache_key, $variations, 'primefit_variations', $expiration );
}

/**
 * Get cached WooCommerce product variations data
 *
 * @param int $product_id Product ID
 * @return array|false Cached variations data or false if not cached
 */
function primefit_get_cached_product_variations( $product_id ) {
	$cache_key = "product_variations_{$product_id}";
	return wp_cache_get( $cache_key, 'primefit_variations' );
}

/**
 * Get cached product data to reduce database queries
 *
 * @param int $product_id Product ID
 * @return array|false Cached product data or false if not cached
 */
function primefit_get_cached_product_data( $product_id ) {
	$cache_key = "product_data_{$product_id}";
	return wp_cache_get( $cache_key, 'primefit_product_data' );
}

/**
 * Cache comprehensive product data
 *
 * @param int $product_id Product ID
 * @param array $data Product data array
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_product_data( $product_id, $data, $expiration = 3600 ) {
	$cache_key = "product_data_{$product_id}";
	wp_cache_set( $cache_key, $data, 'primefit_product_data', $expiration );
}

/**
 * Get cached product meta data
 *
 * @param int $product_id Product ID
 * @param string $meta_key Meta key
 * @return mixed|false Cached meta value or false if not cached
 */
function primefit_get_cached_product_meta( $product_id, $meta_key ) {
	$cache_key = "product_meta_{$product_id}_{$meta_key}";
	return wp_cache_get( $cache_key, 'primefit_product_meta' );
}

/**
 * Cache product meta data
 *
 * @param int $product_id Product ID
 * @param string $meta_key Meta key
 * @param mixed $value Meta value
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_product_meta( $product_id, $meta_key, $value, $expiration = 3600 ) {
	$cache_key = "product_meta_{$product_id}_{$meta_key}";
	wp_cache_set( $cache_key, $value, 'primefit_product_meta', $expiration );
}

/**
 * Get cached attachment meta data
 *
 * @param int $attachment_id Attachment ID
 * @param string $meta_key Meta key
 * @return mixed|false Cached meta value or false if not cached
 */
function primefit_get_cached_attachment_meta( $attachment_id, $meta_key ) {
	$cache_key = "attachment_meta_{$attachment_id}_{$meta_key}";
	return wp_cache_get( $cache_key, 'primefit_attachment_meta' );
}

/**
 * Cache attachment meta data
 *
 * @param int $attachment_id Attachment ID
 * @param string $meta_key Meta key
 * @param mixed $value Meta value
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_attachment_meta( $attachment_id, $meta_key, $value, $expiration = 3600 ) {
	$cache_key = "attachment_meta_{$attachment_id}_{$meta_key}";
	wp_cache_set( $cache_key, $value, 'primefit_attachment_meta', $expiration );
}

/**
 * Get cached attachment image URL
 *
 * @param int $attachment_id Attachment ID
 * @param string $size Image size
 * @return string|false Cached image URL or false if not cached
 */
function primefit_get_cached_attachment_image_url( $attachment_id, $size = 'large' ) {
	$cache_key = "attachment_url_{$attachment_id}_{$size}";
	return wp_cache_get( $cache_key, 'primefit_attachment_urls' );
}

/**
 * Cache attachment image URL
 *
 * @param int $attachment_id Attachment ID
 * @param string $size Image size
 * @param string $url Image URL
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_attachment_image_url( $attachment_id, $size, $url, $expiration = 3600 ) {
	$cache_key = "attachment_url_{$attachment_id}_{$size}";
	wp_cache_set( $cache_key, $url, 'primefit_attachment_urls', $expiration );
}

/**
 * Get comprehensive product data in a single optimized query
 * This reduces multiple database calls to a single query
 *
 * @param int $product_id Product ID
 * @return array Comprehensive product data
 */
function primefit_get_optimized_product_data( $product_id ) {
	global $wpdb;

	// Try cache first
	$cache_key = "product_comprehensive_{$product_id}";
	$cached_data = wp_cache_get( $cache_key, 'primefit_comprehensive' );
	if ( false !== $cached_data ) {
		return $cached_data;
	}

	// Single optimized query to get all product data
	$query = $wpdb->prepare("
		SELECT
			p.ID,
			p.post_title,
			p.post_content,
			p.post_excerpt,
			pm1.meta_value as sku,
			pm2.meta_value as price,
			pm3.meta_value as sale_price,
			pm4.meta_value as regular_price,
			pm5.meta_value as stock_status,
			pm6.meta_value as stock_quantity,
			pm7.meta_value as manage_stock,
			pm8.meta_value as product_type,
			pm9.meta_value as attributes,
			pm10.meta_value as default_attributes,
			pm11.meta_value as gallery_images,
			pm12.meta_value as main_image_id
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_sku')
		LEFT JOIN {$wpdb->postmeta} pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_price')
		LEFT JOIN {$wpdb->postmeta} pm3 ON (p.ID = pm3.post_id AND pm3.meta_key = '_sale_price')
		LEFT JOIN {$wpdb->postmeta} pm4 ON (p.ID = pm4.post_id AND pm4.meta_key = '_regular_price')
		LEFT JOIN {$wpdb->postmeta} pm5 ON (p.ID = pm5.post_id AND pm5.meta_key = '_stock_status')
		LEFT JOIN {$wpdb->postmeta} pm6 ON (p.ID = pm6.post_id AND pm6.meta_key = '_stock')
		LEFT JOIN {$wpdb->postmeta} pm7 ON (p.ID = pm7.post_id AND pm7.meta_key = '_manage_stock')
		LEFT JOIN {$wpdb->postmeta} pm8 ON (p.ID = pm8.post_id AND pm8.meta_key = '_product_attributes')
		LEFT JOIN {$wpdb->postmeta} pm9 ON (p.ID = pm9.post_id AND pm9.meta_key = '_default_attributes')
		LEFT JOIN {$wpdb->postmeta} pm10 ON (p.ID = pm10.post_id AND pm10.meta_key = '_product_image_gallery')
		LEFT JOIN {$wpdb->postmeta} pm11 ON (p.ID = pm11.post_id AND pm11.meta_key = '_thumbnail_id')
		WHERE p.ID = %d AND p.post_type = 'product'
	", $product_id);

	$results = $wpdb->get_row( $query );

	if ( ! $results ) {
		return array();
	}

	// Process and structure the data
	$product_data = array(
		'id' => $results->ID,
		'title' => $results->post_title,
		'content' => $results->post_content,
		'excerpt' => $results->post_excerpt,
		'sku' => $results->sku ?: '',
		'price' => $results->price ?: '',
		'sale_price' => $results->sale_price ?: '',
		'regular_price' => $results->regular_price ?: '',
		'stock_status' => $results->stock_status ?: 'instock',
		'stock_quantity' => $results->stock_quantity ?: '',
		'manage_stock' => $results->manage_stock ?: 'no',
		'product_type' => $results->product_type ?: 'simple',
		'is_variable' => $results->product_type === 'variable',
		'is_in_stock' => $results->stock_status === 'instock',
		'attributes' => $results->attributes ? maybe_unserialize( $results->attributes ) : array(),
		'default_attributes' => $results->default_attributes ? maybe_unserialize( $results->default_attributes ) : array(),
		'gallery_images' => $results->gallery_images ? array_filter( explode( ',', $results->gallery_images ) ) : array(),
		'main_image_id' => $results->main_image_id ?: 0,
		'price_html' => '', // Will be calculated separately if needed
	);

	// Cache the data
	wp_cache_set( $cache_key, $product_data, 'primefit_comprehensive', 3600 );

	return $product_data;
}

/**
 * Get optimized product variations data
 * Combines variations, attributes, and meta data in optimized queries
 *
 * @param int $product_id Product ID
 * @return array|false Variations data or false if not found
 */
function primefit_get_optimized_product_variations( $product_id ) {
	$cache_key = "product_variations_optimized_{$product_id}";
	$cached = wp_cache_get( $cache_key, 'primefit_variations' );

	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	// Get variations with all necessary data in one query
	$query = $wpdb->prepare("
		SELECT
			v.ID as variation_id,
			v.post_title as variation_title,
			vm1.meta_value as attributes,
			vm2.meta_value as price,
			vm3.meta_value as regular_price,
			vm4.meta_value as sale_price,
			vm5.meta_value as stock_status,
			vm6.meta_value as stock_quantity,
			vm7.meta_value as manage_stock,
			vm8.meta_value as image_id,
			vm9.meta_value as description,
			vm10.meta_value as sku
		FROM {$wpdb->posts} v
		LEFT JOIN {$wpdb->postmeta} vm1 ON (v.ID = vm1.post_id AND vm1.meta_key = 'attribute_pa_color')
		LEFT JOIN {$wpdb->postmeta} vm2 ON (v.ID = vm2.post_id AND vm2.meta_key = '_price')
		LEFT JOIN {$wpdb->postmeta} vm3 ON (v.ID = vm3.post_id AND vm3.meta_key = '_regular_price')
		LEFT JOIN {$wpdb->postmeta} vm4 ON (v.ID = vm4.post_id AND vm4.meta_key = '_sale_price')
		LEFT JOIN {$wpdb->postmeta} vm5 ON (v.ID = vm5.post_id AND vm5.meta_key = '_stock_status')
		LEFT JOIN {$wpdb->postmeta} vm6 ON (v.ID = vm6.post_id AND vm6.meta_key = '_stock')
		LEFT JOIN {$wpdb->postmeta} vm7 ON (v.ID = vm7.post_id AND vm7.meta_key = '_manage_stock')
		LEFT JOIN {$wpdb->postmeta} vm8 ON (v.ID = vm8.post_id AND vm8.meta_key = '_thumbnail_id')
		LEFT JOIN {$wpdb->postmeta} vm9 ON (v.ID = vm9.post_id AND vm9.meta_key = '_variation_description')
		LEFT JOIN {$wpdb->postmeta} vm10 ON (v.ID = vm10.post_id AND vm10.meta_key = '_sku')
		WHERE v.post_parent = %d AND v.post_type = 'product_variation' AND v.post_status = 'publish'
		ORDER BY v.menu_order ASC
	", $product_id);

	$variations = $wpdb->get_results( $query );

	if ( empty( $variations ) ) {
		wp_cache_set( $cache_key, array(), 'primefit_variations', 3600 );
		return array();
	}

	// Process variations data
	$processed_variations = array();
	foreach ( $variations as $variation ) {
		$attributes = array();
		if ( $variation->attributes ) {
			$attributes = array( 'pa_color' => $variation->attributes );
		}

		$processed_variations[] = array(
			'variation_id' => $variation->variation_id,
			'variation_title' => $variation->variation_title,
			'attributes' => $attributes,
			'display_price' => $variation->price,
			'display_regular_price' => $variation->regular_price,
			'display_sale_price' => $variation->sale_price,
			'is_in_stock' => $variation->stock_status === 'instock',
			'stock_quantity' => intval( $variation->stock_quantity ),
			'manage_stock' => $variation->manage_stock === 'yes',
			'image_id' => $variation->image_id,
			'variation_description' => $variation->description,
			'sku' => $variation->sku,
		);
	}

	// Cache the processed variations
	wp_cache_set( $cache_key, $processed_variations, 'primefit_variations', 3600 );

	return $processed_variations;
}

/**
 * Cache ACF field data
 *
 * @param string $field_name ACF field name
 * @param int $post_id Post ID
 * @param mixed $data Field data
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function primefit_cache_acf_field( $field_name, $post_id, $data, $expiration = 3600 ) {
	$cache_key = "acf_{$field_name}_{$post_id}";
	wp_cache_set( $cache_key, $data, 'primefit_acf', $expiration );
}

/**
 * Get cached ACF field data
 *
 * @param string $field_name ACF field name
 * @param int $post_id Post ID
 * @return mixed|false Cached field data or false if not cached
 */
function primefit_get_cached_acf_field( $field_name, $post_id ) {
	$cache_key = "acf_{$field_name}_{$post_id}";
	return wp_cache_get( $cache_key, 'primefit_acf' );
}

/**
 * Get variation galleries from WooCommerce variations
 *
 * @param int $product_id Product ID
 * @return array Variation galleries organized by color
 */
function primefit_get_woo_variation_galleries( $product_id ) {
	$cache_key = "woo_variation_galleries_{$product_id}";
	$cached = wp_cache_get( $cache_key, 'primefit_variations' );
	
	if ( false !== $cached ) {
		return $cached;
	}
	
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return array();
	}
	
	$variations = $product->get_available_variations();
	$variation_galleries = array();
	
	// Debug: Log variations data
	error_log( 'PrimeFit Debug: Found ' . count( $variations ) . ' variations for product ' . $product_id );
	
	foreach ( $variations as $variation ) {
		// Get color attribute
		$color = '';
		foreach ( $variation['attributes'] as $attr_name => $attr_value ) {
			if ( stripos( $attr_name, 'color' ) !== false && ! empty( $attr_value ) ) {
				$color = strtolower( trim( $attr_value ) );
				break;
			}
		}
		
		// Debug: Log variation data
		error_log( 'PrimeFit Debug: Variation ' . $variation['variation_id'] . ' - Color: ' . $color . ' - Has gallery_images: ' . ( ! empty( $variation['gallery_images'] ) ? 'Yes' : 'No' ) );
		
		if ( ! empty( $color ) && ! empty( $variation['gallery_images'] ) ) {
			$gallery_images = array();
			foreach ( $variation['gallery_images'] as $image_data ) {
				$gallery_images[] = $image_data['id'];
			}
			
			if ( ! empty( $gallery_images ) ) {
				$variation_galleries[ $color ] = array(
					'images' => $gallery_images,
					'count' => count( $gallery_images )
				);
				error_log( 'PrimeFit Debug: Added gallery for color ' . $color . ' with ' . count( $gallery_images ) . ' images' );
			}
		}
	}
	
	// Debug: Log final result
	error_log( 'PrimeFit Debug: Final variation galleries: ' . print_r( $variation_galleries, true ) );
	
	// Cache the result
	wp_cache_set( $cache_key, $variation_galleries, 'primefit_variations', 3600 );
	
	return $variation_galleries;
}

/**
 * Clear product-related caches when product is updated
 *
 * @param int $product_id Product ID
 */
function primefit_clear_product_performance_cache( $product_id ) {
	// Clear variations cache
	$variations_cache_key = "product_variations_{$product_id}";
	wp_cache_delete( $variations_cache_key, 'primefit_variations' );

	// Clear optimized variations cache
	$optimized_variations_key = "product_variations_optimized_{$product_id}";
	wp_cache_delete( $optimized_variations_key, 'primefit_variations' );

	// Clear comprehensive product data cache
	$comprehensive_key = "product_comprehensive_{$product_id}";
	wp_cache_delete( $comprehensive_key, 'primefit_comprehensive' );

	// Clear basic product data cache
	$product_data_key = "product_data_{$product_id}";
	wp_cache_delete( $product_data_key, 'primefit_product_data' );

	// Clear gallery data cache
	$gallery_key = "{$product_id}_gallery";
	wp_cache_delete( $gallery_key, 'primefit_product_data' );

	// Clear attachment caches for product images
	global $wpdb;
	$attachments = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'attachment'",
		$product_id
	) );

	foreach ( $attachments as $attachment_id ) {
		// Clear attachment URL caches
		$url_cache_keys = array(
			"attachment_url_{$attachment_id}_full",
			"attachment_url_{$attachment_id}_thumbnail"
		);
		foreach ( $url_cache_keys as $key ) {
			wp_cache_delete( $key, 'primefit_attachment_urls' );
		}

		// Clear attachment meta caches
		$meta_cache_key = "attachment_meta_{$attachment_id}_wp_attachment_image_alt";
		wp_cache_delete( $meta_cache_key, 'primefit_attachment_meta' );
	}

	// Clear ACF field caches
	$acf_fields = array( 'variation_gallery', 'size_guide_image', 'primefit_description' );
	foreach ( $acf_fields as $field_name ) {
		$acf_cache_key = "acf_{$field_name}_{$product_id}";
		wp_cache_delete( $acf_cache_key, 'primefit_acf' );
	}

	// Clear WooCommerce product cache
	wp_cache_delete( $product_id, 'posts' );
	wp_cache_delete( $product_id, 'post_meta' );
}

/**
 * Service Worker Cache Management
 * Mobile-specific caching utilities
 */

/**
 * Get cache version for service worker
 */
function primefit_get_cache_version() {
	return 'primefit-mobile-v2.0';
}

/**
 * Get critical resources for service worker caching
 */
function primefit_get_critical_resources() {
	$critical_resources = [
		'/',
		PRIMEFIT_THEME_URI . '/assets/css/app.css',
		PRIMEFIT_THEME_URI . '/assets/css/header.css',
		PRIMEFIT_THEME_URI . '/assets/js/core.js',
		PRIMEFIT_THEME_URI . '/assets/js/app.js'
	];
	
	// Add page-specific critical resources
	if ( is_front_page() ) {
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/css/hero.css';
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/js/hero-video.js';
	}
	
	if ( is_product() ) {
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/css/single-product.css';
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/js/single-product.js';
	}
	
	if ( is_shop() || is_product_category() || is_product_tag() ) {
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/css/woocommerce.css';
		$critical_resources[] = PRIMEFIT_THEME_URI . '/assets/js/shop.js';
	}
	
	return apply_filters( 'primefit_critical_resources', $critical_resources );
}

/**
 * Check if device is mobile for service worker registration
 */
function primefit_is_mobile_device() {
	return wp_is_mobile() || 
		   ( isset( $_SERVER['HTTP_USER_AGENT'] ) && 
			 preg_match( '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ) );
}

/**
 * Get service worker configuration
 */
function primefit_get_sw_config() {
	return [
		'version' => primefit_get_cache_version(),
		'critical_resources' => primefit_get_critical_resources(),
		'cache_strategies' => [
			'static' => [
				'maxAge' => 7 * 24 * 60 * 60 * 1000, // 7 days
				'maxEntries' => 50
			],
			'dynamic' => [
				'maxAge' => 24 * 60 * 60 * 1000, // 1 day
				'maxEntries' => 30
			],
			'images' => [
				'maxAge' => 3 * 24 * 60 * 60 * 1000, // 3 days
				'maxEntries' => 100
			]
		],
		'mobile_only' => true,
		'scope' => '/'
	];
}

/**
 * Add service worker meta tags for mobile
 */
add_action( 'wp_head', 'primefit_add_sw_meta_tags', 1 );
function primefit_add_sw_meta_tags() {
	if ( ! primefit_is_mobile_device() ) {
		return;
	}
	
	echo '<meta name="mobile-web-app-capable" content="yes">';
	echo '<meta name="apple-mobile-web-app-capable" content="yes">';
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
	echo '<meta name="theme-color" content="#0d0d0d">';
}

/**
 * Convert category names to category links for mega menu
 *
 * @param string $category_names Comma-separated category names
 * @return array Array of category links with name and url
 */
function primefit_get_category_links( $category_names ) {
	$links = array();

	if ( empty( $category_names ) ) {
		return $links;
	}

	$category_names_array = array_map( 'trim', explode( ',', $category_names ) );

	foreach ( $category_names_array as $category_name ) {
		if ( empty( $category_name ) ) {
			continue;
		}

		// Try to find category by name first
		$category = get_term_by( 'name', $category_name, 'product_cat' );

		// If not found by name, try by slug
		if ( ! $category ) {
			$category_slug = sanitize_title( $category_name );
			$category = get_term_by( 'slug', $category_slug, 'product_cat' );
		}

		if ( $category && ! is_wp_error( $category ) ) {
			$links[] = array(
				'name' => $category_name,
				'url'  => get_term_link( $category )
			);
		} else {
			// If category doesn't exist, create a fallback URL
			$links[] = array(
				'name' => $category_name,
				'url'  => home_url( '/product-category/' . sanitize_title( $category_name ) . '/' )
			);
		}
	}

	return $links;
}