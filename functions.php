<?php
/**
 * PrimeFit Theme Functions
 *
 * Core theme functionality, hooks, and WordPress customizations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme constants
 */
define( 'PRIMEFIT_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'PRIMEFIT_THEME_DIR', get_template_directory() );
define( 'PRIMEFIT_THEME_URI', get_template_directory_uri() );

/**
 * Theme setup and support
 *
 * @since 1.0.0
 */
add_action( 'after_setup_theme', 'primefit_setup_theme' );
function primefit_setup_theme() {
	// Theme supports
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'menus' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script'
	] );
	
	// WooCommerce support
	add_theme_support( 'woocommerce', [
		'thumbnail_image_width' => 300,
		'gallery_thumbnail_image_width' => 100,
		'single_image_width' => 600,
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	
	// Register navigation menus
	register_nav_menus( [
		'primary' => esc_html__( 'Primary Menu', 'primefit' ),
		'footer' => esc_html__( 'Footer Menu', 'primefit' ),
	] );
	
	// Set content width
	if ( ! isset( $content_width ) ) {
		$content_width = 1200;
	}
}

/**
 * Enqueue theme assets
 *
 * @since 1.0.0
 */
add_action( 'wp_enqueue_scripts', 'primefit_enqueue_assets' );
function primefit_enqueue_assets() {
	// Theme styles
	wp_enqueue_style( 
		'primefit-style', 
		get_stylesheet_uri(), 
		[], 
		PRIMEFIT_VERSION 
	);
	
	wp_enqueue_style( 
		'primefit-app', 
		PRIMEFIT_THEME_URI . '/assets/app.css', 
		[], 
		PRIMEFIT_VERSION 
	);
	
	// WooCommerce styles
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 
			'primefit-woocommerce', 
			PRIMEFIT_THEME_URI . '/assets/woocommerce.css', 
			[ 'primefit-app' ], 
			PRIMEFIT_VERSION 
		);
	}
	
	// Theme scripts
	wp_enqueue_script( 
		'primefit-app', 
		PRIMEFIT_THEME_URI . '/assets/app.js', 
		[ 'jquery' ], 
		PRIMEFIT_VERSION, 
		true 
	);
	
	// Pass data to JavaScript
	wp_localize_script( 'primefit-app', 'primefitData', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'primefit_nonce' ),
		'isMobile' => wp_is_mobile(),
		'breakpoints' => [
			'mobile' => 768,
			'tablet' => 1024,
			'desktop' => 1200
		]
	] );
	
	// Dashicons for admin functionality
	wp_enqueue_style( 'dashicons' );
}

/**
 * Performance optimizations
 *
 * @since 1.0.0
 */
add_action( 'init', 'primefit_performance_optimizations' );
function primefit_performance_optimizations() {
	// Disable emojis for performance
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
}

/**
 * Header functionality and hooks
 *
 * @since 1.0.0
 */

/**
 * Add mobile header body class
 *
 * @param array $classes Existing body classes
 * @return array Modified body classes
 */
add_filter( 'body_class', 'primefit_add_mobile_header_class' );
function primefit_add_mobile_header_class( $classes ) {
	if ( wp_is_mobile() ) {
		$classes[] = 'mobile-device';
	}
	
	if ( is_front_page() ) {
		$classes[] = 'has-hero-header';
	}
	
	return $classes;
}

/**
 * Add mobile-specific viewport meta tag enhancements
 *
 * @since 1.0.0
 */
add_action( 'wp_head', 'primefit_mobile_viewport_enhancements' );
function primefit_mobile_viewport_enhancements() {
	echo '<meta name="mobile-web-app-capable" content="yes">';
	echo '<meta name="apple-mobile-web-app-capable" content="yes">';
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
}

// Product scripts: enqueue on product pages and pages with product loops
add_action( 'wp_enqueue_scripts', function() {
	// Load on single product pages, shop pages, and pages with WooCommerce content
	if ( is_product() || is_shop() || is_product_category() || is_product_tag() || is_front_page() || (function_exists('wc_get_page_id') && (is_page(wc_get_page_id('shop')) || is_page(wc_get_page_id('cart')) || is_page(wc_get_page_id('checkout')))) ) {
		wp_enqueue_script( 'primefit-product', get_template_directory_uri() . '/assets/product.js', [ 'jquery' ], wp_get_theme()->get( 'Version' ), true );
	}
} );

// WooCommerce: wrap product thumbnail and add cart count fragment.
add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {
	ob_start();
	?>
	<span class="count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
	<?php
	$fragments['span.count'] = ob_get_clean();
	return $fragments;
} );

// WooCommerce: Custom product thumbnail with hover effect for product loops
add_action( 'init', function() {
	// Remove default hooks
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	
	// Keep default product link wrapper but modify it
	add_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	
	// Add our custom hooks
	add_action( 'woocommerce_before_shop_loop_item_title', 'primefit_loop_product_thumbnail', 10 );
	add_action( 'woocommerce_after_shop_loop_item_title', 'primefit_loop_product_price', 10 );
} );

function primefit_loop_product_thumbnail() {
	global $product;
	
	if ( ! $product ) {
		return;
	}
	
	// Get product images
	$attachment_ids = $product->get_gallery_image_ids();
	$main_image_id = $product->get_image_id();
	$second_image_id = !empty($attachment_ids) ? $attachment_ids[0] : null;
	
	echo '<div class="product-image-container">';
	
	if ( $main_image_id ) {
		echo wp_get_attachment_image( $main_image_id, 'large', false, [
			'class' => 'attachment-woocommerce_thumbnail',
			'alt' => esc_attr( $product->get_name() )
		] );
	}
	
	if ( $second_image_id ) {
		echo wp_get_attachment_image( $second_image_id, 'large', false, [
			'class' => 'product-second-image',
			'alt' => esc_attr( $product->get_name() )
		] );
	}
	
	// Add status badge
	get_template_part( 'parts/woocommerce/product-status-badge' );
	
	// Add size selection overlay for variable products
	if ( $product->is_type( 'variable' ) ) {
		primefit_render_size_selection_overlay( $product );
	}
	
	echo '</div>';
}

function primefit_loop_product_price() {
	get_template_part( 'parts/woocommerce/product-price' );
}


/**
 * Render size selection overlay for variable products in product loops
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
	
	// Build variation data for JavaScript
	$variation_data = array();
	foreach ( $available_variations as $variation ) {
		$variation_obj = wc_get_product( $variation['variation_id'] );
		if ( ! $variation_obj || ! $variation_obj->is_in_stock() ) {
			continue;
		}
		
		// Try different ways to get the size value
		$size_value = '';
		
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
		
		if ( ! empty( $size_value ) ) {
			$variation_data[ $size_value ] = array(
				'variation_id' => $variation['variation_id'],
				'url' => $product->get_permalink() . '?' . http_build_query( array(
					'attribute_' . str_replace( 'pa_', '', $size_attribute ) => $size_value
				) )
			);
		}
	}
	
	if ( empty( $variation_data ) ) {
		return;
	}
	
	?>
	<div class="product-size-overlay<?php echo isset( $_GET['debug_sizes'] ) ? ' debug-visible' : ''; ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
		<div class="size-options">
			<?php foreach ( $size_options as $size_value ) : ?>
				<?php if ( isset( $variation_data[ $size_value ] ) ) : ?>
					<a href="<?php echo esc_url( $variation_data[ $size_value ]['url'] ); ?>" 
					   class="size-option" 
					   data-size="<?php echo esc_attr( $size_value ); ?>"
					   data-variation-id="<?php echo esc_attr( $variation_data[ $size_value ]['variation_id'] ); ?>">
						<?php echo esc_html( strtoupper( $size_value ) ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Debug function to test size overlay rendering
 * Add ?debug_sizes=1 to any page URL to see debug info
 */
add_action( 'wp_footer', function() {
	if ( isset( $_GET['debug_sizes'] ) && $_GET['debug_sizes'] == '1' ) {
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
				echo '<strong>Product:</strong> ' . $product->get_name() . '<br>';
				$attributes = $product->get_variation_attributes();
				echo '<strong>Attributes:</strong> ' . implode( ', ', array_keys( $attributes ) ) . '<br>';
				
				foreach ( $attributes as $attr_name => $options ) {
					echo '<strong>' . $attr_name . ':</strong> ' . implode( ', ', $options ) . '<br>';
				}
				
				$variations = $product->get_available_variations();
				echo '<strong>Variations:</strong> ' . count( $variations ) . '<br>';
			}
			
			// Test if overlay is being called
			echo '<hr style="margin: 5px 0;">';
			echo '<strong>Testing overlay function:</strong><br>';
			if ( ! empty( $variable_products ) ) {
				$test_product = $variable_products[0];
				
				// Debug the overlay generation step by step
				echo '<strong>Product:</strong> ' . $test_product->get_name() . '<br>';
				echo '<strong>Product Type:</strong> ' . $test_product->get_type() . '<br>';
				
				$attributes = $test_product->get_variation_attributes();
				echo '<strong>Attributes found:</strong> ' . count( $attributes ) . '<br>';
				
				// Test size attribute detection
				$size_attribute = '';
				$size_options = array();
				
				foreach ( $attributes as $attribute_name => $options ) {
					$clean_name = strtolower( str_replace( 'pa_', '', $attribute_name ) );
					echo '<strong>Testing attribute:</strong> ' . $attribute_name . ' (clean: ' . $clean_name . ')<br>';
					if ( in_array( $clean_name, array( 'size', 'sizes', 'clothing-size' ) ) || strpos( $clean_name, 'size' ) !== false ) {
						$size_attribute = $attribute_name;
						$size_options = $options;
						echo '✓ Size attribute found: ' . $size_attribute . '<br>';
						break;
					}
				}
				
				if ( empty( $size_attribute ) ) {
					echo '✗ No size attribute detected<br>';
				} else {
					echo '<strong>Size options:</strong> ' . implode( ', ', $size_options ) . '<br>';
					
					// Test variations
					$available_variations = $test_product->get_available_variations();
					echo '<strong>Available variations:</strong> ' . count( $available_variations ) . '<br>';
					
					$variation_data = array();
					foreach ( $available_variations as $i => $variation ) {
						if ( $i < 2 ) { // Only test first 2 for debug
							echo '<strong>Variation ' . ($i+1) . ':</strong><br>';
							echo '- ID: ' . $variation['variation_id'] . '<br>';
							echo '- Attributes: ' . print_r( $variation['attributes'], true ) . '<br>';
							
							$variation_obj = wc_get_product( $variation['variation_id'] );
							echo '- In stock: ' . ( $variation_obj && $variation_obj->is_in_stock() ? 'Yes' : 'No' ) . '<br>';
						}
					}
				}
				
				ob_start();
				primefit_render_size_selection_overlay( $test_product );
				$overlay_output = ob_get_clean();
				echo '<strong>Overlay HTML length:</strong> ' . strlen( $overlay_output ) . '<br>';
				if ( strlen( $overlay_output ) > 0 ) {
					echo '✓ Overlay HTML generated<br>';
					echo '<textarea style="width: 100%; height: 60px; font-size: 10px;">' . htmlspecialchars( $overlay_output ) . '</textarea>';
				} else {
					echo '✗ No overlay HTML generated<br>';
				}
			}
		}
		
		echo '</div>';
	}
} );

// Utility: get first existing asset URI from a list of candidates relative to theme dir.
function primefit_get_asset_uri( array $candidates ) {
	foreach ( $candidates as $relative ) {
		$path = get_theme_file_path( $relative );
		if ( file_exists( $path ) ) {
			return get_theme_file_uri( $relative );
		}
	}
	return '';
}

// Admin: Product custom fields (Highlights, Details) in WooCommerce product data -> General.
add_action( 'woocommerce_product_options_general_product_data', function() {
	echo '<div class="options_group">';
	woocommerce_wp_textarea_input( [
		'id' => 'primefit_highlights',
		'label' => __( 'Highlights', 'primefit' ),
		'placeholder' => __( "One per line", 'primefit' ),
		'description' => __( 'Key highlights. Use one per line.', 'primefit' ),
		'rows' => 5,
	] );
	woocommerce_wp_textarea_input( [
		'id' => 'primefit_details',
		'label' => __( 'Details', 'primefit' ),
		'description' => __( 'Details content. Supports basic HTML.', 'primefit' ),
		'rows' => 6,
	] );
	echo '</div>';
} );

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
	$map = [ 'primefit_highlights', 'primefit_details' ];
	foreach ( $map as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
} );

// Add tabs for Highlights and Details on product page.
add_filter( 'woocommerce_product_tabs', function( $tabs ) {
	$highlights = get_post_meta( get_the_ID(), 'primefit_highlights', true );
	$details    = get_post_meta( get_the_ID(), 'primefit_details', true );
	if ( ! empty( $highlights ) ) {
		$tabs['primefit_highlights'] = [
			'title'    => __( 'Highlights', 'primefit' ),
			'priority' => 15,
			'callback' => function() use ( $highlights ) {
				$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $highlights ) ) );
				echo '<ul class="pf-highlights">';
				foreach ( $lines as $line ) {
					echo '<li>' . wp_kses_post( $line ) . '</li>';
				}
				echo '</ul>';
			},
		];
	}
	if ( ! empty( $details ) ) {
		$tabs['additional_information']['callback'] = function() use ( $details ) {
			echo wp_kses_post( wpautop( $details ) );
		};
	}
	return $tabs;
} );

// Meta box for Additional Sections (rich content with images) displayed below tabs.
add_action( 'add_meta_boxes', function() {
	add_meta_box( 'primefit_product_sections', __( 'PrimeFit Additional Sections', 'primefit' ), function( $post ) {
		$val = get_post_meta( $post->ID, 'primefit_additional_html', true );
		wp_editor( $val, 'primefit_additional_html', [ 'textarea_rows' => 8 ] );
	}, 'product', 'normal', 'default' );
} );

add_action( 'save_post_product', function( $post_id ) {
	if ( isset( $_POST['primefit_additional_html'] ) ) {
		update_post_meta( $post_id, 'primefit_additional_html', wp_kses_post( wp_unslash( $_POST['primefit_additional_html'] ) ) );
	}
} );

add_action( 'woocommerce_after_single_product_summary', function() {
	$additional = get_post_meta( get_the_ID(), 'primefit_additional_html', true );
	if ( ! empty( $additional ) ) {
		echo '<section class="pf-additional container">' . wp_kses_post( $additional ) . '</section>';
	}
}, 12 );

/**
 * Theme Customizer Settings
 */
add_action( 'customize_register', function( $wp_customize ) {
	// Promo Bar Section
	$wp_customize->add_section( 'primefit_promo_bar', array(
		'title'    => __( 'Promo Bar', 'primefit' ),
		'priority' => 25,
	) );

	// Promo Bar Text
	$wp_customize->add_setting( 'primefit_promo_text', array(
		'default'           => 'END OF SEASON SALE — UP TO 60% OFF — LIMITED TIME ONLY',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_promo_text', array(
		'label'   => __( 'Promo Text', 'primefit' ),
		'section' => 'primefit_promo_bar',
		'type'    => 'text',
	) );

	// Promo Bar Background Color
	$wp_customize->add_setting( 'primefit_promo_bg_color', array(
		'default'           => '#ff3b30',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primefit_promo_bg_color', array(
		'label'   => __( 'Background Color', 'primefit' ),
		'section' => 'primefit_promo_bar',
	) ) );

	// Promo Bar Text Color
	$wp_customize->add_setting( 'primefit_promo_text_color', array(
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primefit_promo_text_color', array(
		'label'   => __( 'Text Color', 'primefit' ),
		'section' => 'primefit_promo_bar',
	) ) );

	// Footer Section
	$wp_customize->add_section( 'primefit_footer', array(
		'title'    => __( 'Footer', 'primefit' ),
		'priority' => 35,
	) );

	// Copyright Text
	$wp_customize->add_setting( 'primefit_copyright_text', array(
		'default'           => sprintf( '© %s %s', date_i18n( 'Y' ), get_bloginfo( 'name' ) ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_copyright_text', array(
		'label'   => __( 'Copyright Text', 'primefit' ),
		'section' => 'primefit_footer',
		'type'    => 'text',
	) );

	// Hero Section Panel
	// Hero Section Panel
	$wp_customize->add_section( 'primefit_hero', array(
		'title'    => __( 'Hero Section', 'primefit' ),
		'priority' => 30,
	) );

	// Hero Background Image
	$wp_customize->add_setting( 'primefit_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_image', array(
		'label'    => __( 'Hero Background Image', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'image',
	) ) );

	// Hero Heading
	$wp_customize->add_setting( 'primefit_hero_heading', array(
		'default'           => 'END OF SEASON SALE',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_hero_heading', array(
		'label'   => __( 'Hero Heading', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'text',
	) );

	// Hero Subheading
	$wp_customize->add_setting( 'primefit_hero_subheading', array(
		'default'           => 'UP TO 60% OFF. LIMITED TIME ONLY. WHILE SUPPLIES LAST.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_hero_subheading', array(
		'label'   => __( 'Hero Subheading', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'textarea',
	) );

	// Hero CTA Text
	$wp_customize->add_setting( 'primefit_hero_cta_text', array(
		'default'           => 'SHOP NOW',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_hero_cta_text', array(
		'label'   => __( 'Call-to-Action Text', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'text',
	) );

	// Hero CTA Link
	$wp_customize->add_setting( 'primefit_hero_cta_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_hero_cta_link', array(
		'label'   => __( 'Call-to-Action Link', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'url',
	) );

	// Hero Text Position
	$wp_customize->add_setting( 'primefit_hero_text_position', array(
		'default'           => 'left',
		'sanitize_callback' => 'sanitize_key',
	) );
	$wp_customize->add_control( 'primefit_hero_text_position', array(
		'label'   => __( 'Text Position', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'select',
		'choices' => array(
			'left'   => __( 'Left', 'primefit' ),
			'center' => __( 'Center', 'primefit' ),
			'right'  => __( 'Right', 'primefit' ),
		),
	) );

	// Hero Text Color
	$wp_customize->add_setting( 'primefit_hero_text_color', array(
		'default'           => 'light',
		'sanitize_callback' => 'sanitize_key',
	) );
	$wp_customize->add_control( 'primefit_hero_text_color', array(
		'label'   => __( 'Text Color Theme', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'select',
		'choices' => array(
			'light' => __( 'Light (for dark backgrounds)', 'primefit' ),
			'dark'  => __( 'Dark (for light backgrounds)', 'primefit' ),
		),
	) );
} );

/**
 * Helper function to get hero configuration from customizer
 */
function primefit_get_hero_config() {
	$hero_image_id = get_theme_mod( 'primefit_hero_image' );
	$hero_image_url = $hero_image_id ? wp_get_attachment_image_url( $hero_image_id, 'full' ) : '';
	
	// Fallback to default image if no custom image is set
	if ( empty( $hero_image_url ) ) {
		$hero_image_url = primefit_get_asset_uri( array( '/assets/media/hero-image.webp', '/assets/media/hero-image.jpg' ) );
	}

	$cta_link = get_theme_mod( 'primefit_hero_cta_link' );
	if ( empty( $cta_link ) && function_exists( 'wc_get_page_permalink' ) ) {
		$cta_link = wc_get_page_permalink( 'shop' );
	}

	return array(
		'image' => array( $hero_image_url ),
		'heading' => get_theme_mod( 'primefit_hero_heading', 'END OF SEASON SALE' ),
		'subheading' => get_theme_mod( 'primefit_hero_subheading', 'UP TO 60% OFF. LIMITED TIME ONLY. WHILE SUPPLIES LAST.' ),
		'cta_text' => get_theme_mod( 'primefit_hero_cta_text', 'SHOP NOW' ),
		'cta_link' => $cta_link,
		'overlay_position' => get_theme_mod( 'primefit_hero_text_position', 'left' ),
		'text_color' => get_theme_mod( 'primefit_hero_text_color', 'light' ),
	);
}

/**
 * Add product status tags to WooCommerce products
 */
add_action( 'woocommerce_before_shop_loop_item_title', 'primefit_add_product_status_tag', 5 );
function primefit_add_product_status_tag() {
	global $product;
	
	// Check if product is on sale
	if ( $product->is_on_sale() ) {
		$sale_percentage = 'SALE';
		$tag_class = 'sale';
		$percentage = 0;
		
		if ( $product->get_regular_price() && $product->get_sale_price() ) {
			$percentage = round( ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100 );
			if ( $percentage >= 50 ) {
				$sale_percentage = 'FLASH SALE';
				$tag_class = 'flash-sale';
			}
		}
		
		echo '<span class="product-status-tag ' . esc_attr( $tag_class ) . '">' . esc_html( $sale_percentage ) . '</span>';
	}
	
	// Check if product is out of stock
	if ( ! $product->is_in_stock() ) {
		echo '<span class="product-status-tag sold-out">SOLD OUT</span>';
	}
}

/**
 * Add sold out text for out of stock products
 */
add_action( 'woocommerce_after_shop_loop_item_title', 'primefit_add_sold_out_text', 15 );
function primefit_add_sold_out_text() {
	global $product;
	
	if ( ! $product->is_in_stock() ) {
		echo '<p class="sold-out-text">SOLD OUT</p>';
	}
}

/**
 * Enable WebP and AVIF image upload support
 */
add_filter( 'upload_mimes', 'primefit_add_webp_avif_mime_types' );
function primefit_add_webp_avif_mime_types( $mime_types ) {
	// Add WebP support
	$mime_types['webp'] = 'image/webp';
	
	// Add AVIF support
	$mime_types['avif'] = 'image/avif';
	
	return $mime_types;
}

/**
 * Add WebP and AVIF to allowed file extensions
 */
add_filter( 'wp_check_filetype_and_ext', 'primefit_check_webp_avif_filetype', 10, 4 );
function primefit_check_webp_avif_filetype( $data, $file, $filename, $mimes ) {
	$filetype = wp_check_filetype( $filename, $mimes );
	
	if ( $filetype['ext'] ) {
		return $data;
	}
	
	// Check for WebP
	if ( preg_match( '/\.webp$/i', $filename ) ) {
		$data['ext'] = 'webp';
		$data['type'] = 'image/webp';
	}
	
	// Check for AVIF
	if ( preg_match( '/\.avif$/i', $filename ) ) {
		$data['ext'] = 'avif';
		$data['type'] = 'image/avif';
	}
	
	return $data;
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
		$category_image_url = get_template_directory_uri() . '/assets/media/hero-image.webp';
	}
	
	return $category_image_url;
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
			'image' => array('/assets/media/hero-image.webp', '/assets/media/hero-image.jpg'),
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
			$subheading = 'EXPLORE OUR ' . strtoupper( $category->name ) . ' COLLECTION';
		}
		
		$hero_args = array(
			'image' => array( $hero_image ),
			'heading' => strtoupper( $category->name ),
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
			'image' => array('/assets/media/hero-image.webp', '/assets/media/hero-image.jpg'),
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
 * Remove WooCommerce shop sidebar and filters
 *
 * @since 1.0.0
 */
function primefit_remove_shop_sidebar() {
	// Remove sidebar
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	
	// Remove layered nav widgets
	remove_action( 'woocommerce_sidebar', 'woocommerce_output_content_wrapper_end', 20 );
}

/**
 * Disable WooCommerce shop filters and sorting on archive pages
 *
 * @since 1.0.0
 */
add_action( 'init', 'primefit_disable_shop_filters' );
function primefit_disable_shop_filters() {
	if ( is_admin() ) {
		return;
	}
	
	// Remove default ordering dropdown (we have our own in the filter bar)
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	
	// Remove default result count (we have our own in the filter bar)
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	
	// Remove breadcrumbs on shop page
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
}

/**
 * Ensure WooCommerce sorting still works with our custom filter bar
 *
 * @since 1.0.0
 */
add_action( 'pre_get_posts', 'primefit_handle_custom_sorting' );
function primefit_handle_custom_sorting( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	
	// Only apply to WooCommerce shop/category/tag pages
	if ( ! ( $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) ) ) {
		return;
	}
	
	// Handle sorting
	if ( isset( $_GET['orderby'] ) ) {
		$orderby = wc_clean( $_GET['orderby'] );
		
		switch ( $orderby ) {
			case 'menu_order':
				$query->set( 'orderby', 'menu_order title' );
				$query->set( 'order', 'ASC' );
				break;
			case 'date':
				$query->set( 'orderby', 'date ID' );
				$query->set( 'order', 'DESC' );
				break;
			case 'price':
				$query->set( 'meta_key', '_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'ASC' );
				break;
			case 'price-desc':
				$query->set( 'meta_key', '_price' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
			case 'popularity':
				$query->set( 'meta_key', 'total_sales' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
			case 'rating':
				$query->set( 'meta_key', '_wc_average_rating' );
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				break;
		}
	}
}

/**
 * Enable WebP and AVIF preview in media library
 */
add_filter( 'wp_generate_attachment_metadata', 'primefit_handle_webp_avif_metadata', 10, 2 );
function primefit_handle_webp_avif_metadata( $metadata, $attachment_id ) {
	$mime_type = get_post_mime_type( $attachment_id );
	
	// Handle WebP and AVIF files
	if ( in_array( $mime_type, [ 'image/webp', 'image/avif' ], true ) ) {
		$file = get_attached_file( $attachment_id );
		
		if ( $file && file_exists( $file ) ) {
			// Get basic image info
			$image_size = getimagesize( $file );
			if ( $image_size ) {
				$metadata['width'] = $image_size[0];
				$metadata['height'] = $image_size[1];
			}
		}
	}
	
	return $metadata;
}


