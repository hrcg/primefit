<?php
/**
 * PrimeFit Theme Setup
 *
 * Theme setup functions, supports, and configurations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	// Site logo support
	add_theme_support( 'custom-logo', [
		'height'      => 80,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => [ 'site-title', 'site-description' ],
	] );
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
		'thumbnail_image_width' => 600,
		'gallery_thumbnail_image_width' => 200,
		'single_image_width' => 1200,
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	
	// Add optimized image sizes for product loops
	add_image_size( 'primefit-product-loop', 400, 500, true ); // 4:5 aspect ratio for product loops
	add_image_size( 'primefit-product-loop-2x', 800, 1000, true ); // Retina version
	add_image_size( 'primefit-product-loop-small', 200, 250, true ); // Mobile/small screens
	
	// Register navigation menus
	register_nav_menus( [
		'primary'        => esc_html__( 'Primary Menu', 'primefit' ),
		'secondary'      => esc_html__( 'Secondary Menu', 'primefit' ),
		'tertiary'       => esc_html__( 'Tertiary Menu', 'primefit' ),
		'footer'         => esc_html__( 'Footer Menu', 'primefit' ),
		'footer-primary' => esc_html__( 'Footer Primary Menu', 'primefit' ),
		'footer-secondary' => esc_html__( 'Footer Secondary Menu', 'primefit' ),
		'footer-tertiary' => esc_html__( 'Footer Tertiary Menu', 'primefit' ),
	] );
	
	// Set content width
	if ( ! isset( $content_width ) ) {
		$content_width = 1200;
	}
}

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
 * Ensure helpful DB indexes exist for WooCommerce meta-heavy queries
 * - Adds composite indexes on postmeta for faster JOINs and lookups
 * - Runs once per environment, guarded by an option flag
 * - Uses dynamic table prefix via $wpdb
 */
add_action( 'admin_init', 'primefit_ensure_postmeta_indexes' );
function primefit_ensure_postmeta_indexes() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$flag = get_option( 'primefit_postmeta_indexes_added', false );
	if ( $flag ) {
		return;
	}
	global $wpdb;
	$table = $wpdb->postmeta;

	// Detect if indexes already exist to avoid errors
	$existing = $wpdb->get_col( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name IN (%s,%s)", 'idx_postmeta_meta_key_post_id', 'idx_postmeta_post_id_meta_key' ) );

	$queries = array();
	if ( ! in_array( 'idx_postmeta_meta_key_post_id', $existing, true ) ) {
		$queries[] = "CREATE INDEX idx_postmeta_meta_key_post_id ON {$table} (meta_key(191), post_id)";
	}
	if ( ! in_array( 'idx_postmeta_post_id_meta_key', $existing, true ) ) {
		$queries[] = "CREATE INDEX idx_postmeta_post_id_meta_key ON {$table} (post_id, meta_key(191))";
	}

	if ( ! empty( $queries ) ) {
		foreach ( $queries as $sql ) {
			$wpdb->query( $sql );
		}
		update_option( 'primefit_postmeta_indexes_added', true, false );
	}
}

/**
 * Enable WebP image upload support
 */
add_filter( 'upload_mimes', 'primefit_add_webp_mime_types' );
function primefit_add_webp_mime_types( $mime_types ) {
	// Add WebP support
	$mime_types['webp'] = 'image/webp';

	return $mime_types;
}

/**
 * Add WebP to allowed file extensions
 */
add_filter( 'wp_check_filetype_and_ext', 'primefit_check_webp_filetype', 10, 4 );
function primefit_check_webp_filetype( $data, $file, $filename, $mimes ) {
	$filetype = wp_check_filetype( $filename, $mimes );
	
	if ( $filetype['ext'] ) {
		return $data;
	}
	
	// Check for WebP
	if ( preg_match( '/\.webp$/i', $filename ) ) {
		$data['ext'] = 'webp';
		$data['type'] = 'image/webp';
	}
	
	
	return $data;
}

/**
 * Enable WebP preview in media library
 */
add_filter( 'wp_generate_attachment_metadata', 'primefit_handle_webp_metadata', 10, 2 );
function primefit_handle_webp_metadata( $metadata, $attachment_id ) {
	$mime_type = get_post_mime_type( $attachment_id );

	// Handle WebP files
	if ( in_array( $mime_type, [ 'image/webp' ], true ) ) {
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

/**
 * Add custom image sizes to admin media library
 */
add_filter( 'image_size_names_choose', 'primefit_custom_image_sizes' );
function primefit_custom_image_sizes( $sizes ) {
	return array_merge( $sizes, array(
		'primefit-product-loop' => __( 'Product Loop (400x500)', 'primefit' ),
		'primefit-product-loop-2x' => __( 'Product Loop Retina (800x1000)', 'primefit' ),
		'primefit-product-loop-small' => __( 'Product Loop Mobile (200x250)', 'primefit' ),
	) );
}

/**
 * Add performance optimization for image loading
 */
add_action( 'wp_head', 'primefit_image_loading_optimizations' );
function primefit_image_loading_optimizations() {
	?>
	<style>
		/* Optimize image loading performance */
		.attachment-woocommerce_thumbnail,
		.product-second-image {
			content-visibility: auto;
			contain-intrinsic-size: 400px 500px;
		}
		
		/* Mobile optimizations */
		@media (max-width: 768px) {
			.attachment-woocommerce_thumbnail,
			.product-second-image {
				contain-intrinsic-size: 200px 250px;
			}
		}
	</style>
	<?php
}

/**
 * Admin notice to regenerate thumbnails after adding new image sizes
 */
add_action( 'admin_notices', 'primefit_thumbnail_regeneration_notice' );
function primefit_thumbnail_regeneration_notice() {
	// Only show on admin pages and if user has permission
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Check if thumbnails have been regenerated
	$regenerated = get_option( 'primefit_thumbnails_regenerated', false );
	
	if ( ! $regenerated ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php _e( 'PrimeFit Theme:', 'primefit' ); ?></strong>
				<?php _e( 'New optimized image sizes have been added for better performance. ', 'primefit' ); ?>
				<a href="<?php echo admin_url( 'admin.php?page=primefit-regenerate-thumbnails' ); ?>" class="button button-primary">
					<?php _e( 'Regenerate Thumbnails', 'primefit' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

/**
 * Add admin menu for thumbnail regeneration
 */
add_action( 'admin_menu', 'primefit_add_thumbnail_regeneration_menu' );
function primefit_add_thumbnail_regeneration_menu() {
	add_management_page(
		__( 'Regenerate Thumbnails', 'primefit' ),
		__( 'Regenerate Thumbnails', 'primefit' ),
		'manage_options',
		'primefit-regenerate-thumbnails',
		'primefit_thumbnail_regeneration_page'
	);
}

/**
 * Thumbnail regeneration admin page
 */
function primefit_thumbnail_regeneration_page() {
	if ( isset( $_POST['regenerate_thumbnails'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'primefit_regenerate_thumbnails' ) ) {
		$processed_count = primefit_regenerate_product_thumbnails();
		update_option( 'primefit_thumbnails_regenerated', true );
		echo '<div class="notice notice-success"><p>' . sprintf( __( 'Successfully processed %d images.', 'primefit' ), $processed_count ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php _e( 'Regenerate Product Thumbnails', 'primefit' ); ?></h1>
		<p><?php _e( 'This will regenerate optimized thumbnails for all product images to improve front page performance.', 'primefit' ); ?></p>
		
		<form method="post">
			<?php wp_nonce_field( 'primefit_regenerate_thumbnails' ); ?>
			<p>
				<input type="submit" name="regenerate_thumbnails" class="button button-primary" value="<?php _e( 'Regenerate Thumbnails', 'primefit' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}
