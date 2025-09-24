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
 * Get hero image for WooCommerce category
 *
 * @param object $category WooCommerce category object
 * @param string $size Image size (default: 'full')
 * @return string Hero image URL
 * @since 1.0.0
 */
function primefit_get_category_hero_image( $category, $size = 'full' ) {
	$category_image_url = '';
	
	// Get category image from WooCommerce
	$category_image_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
	
	if ( $category_image_id ) {
		$category_image_url = wp_get_attachment_image_url( $category_image_id, $size );
	}
	
	// Fallback to default hero image if no category image
	if ( empty( $category_image_url ) ) {
		$category_image_url = get_template_directory_uri() . '/assets/images/hero-image.webp';
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
			'image_desktop' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
			'image_mobile' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
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
			'image_desktop' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
			'image_mobile' => primefit_get_asset_uri(array('/assets/images/hero-image.webp', '/assets/images/hero-image.jpg')),
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

		echo '<div style="position: fixed; bottom: 0; left: 0; background: black; color: white; padding: 10px; z-index: 9999; max-width: 400px; font-size: 12px; max-height: 300px; overflow-y: auto;">';
		echo '<strong>Size Overlay Debug:</strong><br>';

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

		echo '</div>';
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
		'run' => '/assets/images/run.webp',
		'train' => '/assets/images/train.webp',
		'rec' => '/assets/images/rec.webp'
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
	return primefit_get_asset_uri( array( '/assets/images/hero-image.webp', '/assets/images/hero-image.jpg' ) );
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
		$hero_image_desktop_url = get_template_directory_uri() . '/assets/images/hero-image.webp';
	}
	if ( empty( $hero_image_mobile_url ) ) {
		$hero_image_mobile_url = get_template_directory_uri() . '/assets/images/hero-image.webp';
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
							preload="metadata"
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
							preload="metadata"
						>
							<source src="<?php echo esc_url($hero_video_mobile_url); ?>" type="video/mp4">
							Your browser does not support the video tag.
						</video>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<!-- Fallback Image (always present for loading state and fallback) -->
			<picture class="hero-fallback-image">
				<source media="(max-width: 768px)" srcset="<?php echo esc_url( $hero_image_mobile_url ); ?>">
				<img 
					src="<?php echo esc_url( $hero_image_desktop_url ); ?>" 
					alt="<?php echo esc_attr( $hero['heading'] ); ?>" 
					loading="eager"
					class="hero-image"
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
				$variation_images[ $color_value ] = wp_get_attachment_image_url( $variation['image_id'], 'full' );
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
				$variation_image = wp_get_attachment_image_url( $main_image_id, 'full' );
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