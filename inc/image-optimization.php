<?php
/**
 * PrimeFit Theme Image Optimization
 *
 * Advanced image loading, lazy loading, and WebP support
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate responsive image markup with lazy loading and modern formats
 *
 * @param int $attachment_id Image attachment ID
 * @param string $size Image size
 * @param array $args Additional arguments
 * @return string HTML markup
 */
function primefit_get_responsive_image( $attachment_id, $size = 'full', $args = [] ) {
	$defaults = [
		'class' => '',
		'alt' => '',
		'loading' => 'lazy',
		'fetchpriority' => 'auto',
		'sizes' => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
		'webp' => true,
		'quality' => 85, // Higher quality for better compression
		'width' => '',
		'height' => '',
		'decoding' => 'async',
	];
	
	$args = wp_parse_args( $args, $defaults );
	
	if ( ! $attachment_id ) {
		return '';
	}
	
	// Get image metadata
	$image_meta = wp_get_attachment_metadata( $attachment_id );
	if ( ! $image_meta ) {
		return '';
	}
	
	// Get alt text
	$alt_text = $args['alt'] ?: get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	
	// Generate responsive sources with optimized quality
	$sources = primefit_generate_responsive_sources( $attachment_id, $size, $args );
	
	// Build picture element
	$picture_html = '<picture>';
	
	
	// Add WebP sources if supported (good compression)
	if ( $args['webp'] && primefit_webp_supported() ) {
		foreach ( $sources['webp'] as $source ) {
			$picture_html .= sprintf(
				'<source type="image/webp" srcset="%s" sizes="%s">',
				esc_attr( $source['srcset'] ),
				esc_attr( $args['sizes'] )
			);
		}
	}
	
	// Add fallback img element with optimized attributes
	$img_attributes = [
		'src' => wp_get_attachment_image_url( $attachment_id, $size ),
		'alt' => $alt_text,
		'class' => $args['class'],
		'loading' => $args['loading'],
		'decoding' => $args['decoding'],
		'sizes' => $args['sizes'],
	];
	
	// Add width and height for CLS prevention
	if ( $args['width'] ) {
		$img_attributes['width'] = $args['width'];
	}
	if ( $args['height'] ) {
		$img_attributes['height'] = $args['height'];
	}
	
	// Add fetchpriority for above-the-fold images
	if ( $args['fetchpriority'] !== 'auto' ) {
		$img_attributes['fetchpriority'] = $args['fetchpriority'];
	}
	
	$img_html = '<img';
	foreach ( $img_attributes as $attr => $value ) {
		if ( $value ) {
			$img_html .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
		}
	}
	$img_html .= '>';
	
	$picture_html .= $img_html . '</picture>';
	
	return $picture_html;
}

/**
 * Generate responsive image sources for different formats
 *
 * @param int $attachment_id Image attachment ID
 * @param string $size Base image size
 * @param array $args Arguments
 * @return array Sources array
 */
function primefit_generate_responsive_sources( $attachment_id, $size, $args ) {
	$sources = [
		'webp' => [],
		'fallback' => []
	];
	
	// Define responsive breakpoints
	$breakpoints = [
		'primefit-product-loop-small' => '200w',
		'primefit-product-loop' => '400w',
		'primefit-product-loop-2x' => '800w',
		'large' => '1200w',
		'full' => '1920w'
	];
	
	foreach ( $breakpoints as $breakpoint_size => $width ) {
		
		// Generate WebP source
		if ( $args['webp'] ) {
			$webp_url = primefit_get_image_url_with_format( $attachment_id, $breakpoint_size, 'webp' );
			if ( $webp_url ) {
				$sources['webp'][] = [
					'srcset' => $webp_url . ' ' . $width,
					'media' => ''
				];
			}
		}
	}
	
	return $sources;
}

/**
 * Generate WebP version of an image with optimized quality
 *
 * @param int $attachment_id Image attachment ID
 * @param string $size Image size
 * @param int $quality Quality setting (1-100)
 * @return string|false WebP file path or false
 */
function primefit_generate_webp_image( $attachment_id, $size = 'full', $quality = 85 ) {
	// Get the original image file
	$original_file = get_attached_file( $attachment_id );
	if ( ! $original_file || ! file_exists( $original_file ) ) {
		return false;
	}
	
	// Get image size info
	$image_sizes = wp_get_attachment_metadata( $attachment_id );
	if ( ! $image_sizes ) {
		return false;
	}
	
	// Determine the source file for the requested size
	$source_file = $original_file;
	if ( $size !== 'full' && isset( $image_sizes['sizes'][ $size ] ) ) {
		$upload_dir = wp_upload_dir();
		$source_file = $upload_dir['basedir'] . '/' . dirname( $image_sizes['file'] ) . '/' . $image_sizes['sizes'][ $size ]['file'];
	}
	
	if ( ! file_exists( $source_file ) ) {
		return false;
	}
	
	// Create WebP filename
	$file_info = pathinfo( $source_file );
	$webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
	
	// Check if WebP already exists
	if ( file_exists( $webp_file ) ) {
		return $webp_file;
	}
	
	// Generate WebP using WordPress image editor (better quality)
	$editor = wp_get_image_editor( $source_file );
	if ( ! is_wp_error( $editor ) ) {
		$result = $editor->save( $webp_file, 'image/webp' );
		if ( ! is_wp_error( $result ) ) {
			return $webp_file;
		}
	}
	
	// Fallback to GD library
	if ( function_exists( 'imagewebp' ) ) {
		$image = primefit_load_image_for_webp( $source_file );
		if ( $image ) {
			$success = imagewebp( $image, $webp_file, $quality );
			imagedestroy( $image );
			
			if ( $success ) {
				return $webp_file;
			}
		}
	}
	
	return false;
}


/**
 * Load image resource for WebP conversion
 *
 * @param string $file_path Path to image file
 * @return resource|false Image resource or false
 */
function primefit_load_image_for_webp( $file_path ) {
	$image_info = getimagesize( $file_path );
	if ( ! $image_info ) {
		return false;
	}
	
	$mime_type = $image_info['mime'];
	
	switch ( $mime_type ) {
		case 'image/jpeg':
			return imagecreatefromjpeg( $file_path );
		case 'image/png':
			return imagecreatefrompng( $file_path );
		case 'image/gif':
			return imagecreatefromgif( $file_path );
		case 'image/webp':
			return imagecreatefromwebp( $file_path );
		default:
			return false;
	}
}

/**
 * Get image URL with specific format (WebP)
 *
 * @param int $attachment_id Image attachment ID
 * @param string $size Image size
 * @param string $format Image format (webp)
 * @return string|false Image URL or false
 */
function primefit_get_image_url_with_format( $attachment_id, $size, $format ) {
	$original_url = wp_get_attachment_image_url( $attachment_id, $size );
	if ( ! $original_url ) {
		return false;
	}
	
	// For WebP, try to generate if it doesn't exist
	if ( $format === 'webp' ) {
		$webp_file = primefit_generate_webp_image( $attachment_id, $size );
		if ( $webp_file ) {
			$upload_dir = wp_upload_dir();
			$webp_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $webp_file );
			return $webp_url;
		}
	}
	
	
	// Check if format-specific version exists
	$upload_dir = wp_upload_dir();
	$file_path = get_attached_file( $attachment_id );
	
	if ( ! $file_path ) {
		return false;
	}
	
	$file_info = pathinfo( $file_path );
	$format_file = $file_info['dirname'] . '/' . $file_info['filename'] . '-' . $size . '.' . $format;
	
	// Check if format file exists
	if ( file_exists( $format_file ) ) {
		$format_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $format_file );
		return $format_url;
	}
	
	// Fallback to original URL
	return $original_url;
}

/**
 * Automatically generate WebP versions when images are uploaded
 */
add_action( 'wp_generate_attachment_metadata', 'primefit_auto_generate_modern_formats', 10, 2 );
function primefit_auto_generate_modern_formats( $metadata, $attachment_id ) {
	// Only process images
	$mime_type = get_post_mime_type( $attachment_id );
	if ( ! in_array( $mime_type, [ 'image/jpeg', 'image/png', 'image/gif' ] ) ) {
		return $metadata;
	}
	
	// Generate WebP for original size
	if ( primefit_webp_supported() ) {
		primefit_generate_webp_image( $attachment_id, 'full' );
	}
	
	// Generate WebP for all registered sizes
	if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			if ( primefit_webp_supported() ) {
				primefit_generate_webp_image( $attachment_id, $size_name );
			}
		}
	}
	
	return $metadata;
}

/**
 * Clean up WebP files when images are deleted
 */
add_action( 'delete_attachment', 'primefit_cleanup_modern_format_files' );
function primefit_cleanup_modern_format_files( $attachment_id ) {
	$file_path = get_attached_file( $attachment_id );
	if ( ! $file_path ) {
		return;
	}
	
	$file_info = pathinfo( $file_path );
	$upload_dir = wp_upload_dir();
	
	// Remove WebP version of original file
	$webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

	if ( file_exists( $webp_file ) ) {
		unlink( $webp_file );
	}
	
	// Remove WebP versions of all sizes
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( $metadata && isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			$size_webp_file = $file_info['dirname'] . '/' . $file_info['filename'] . '-' . $size_name . '.webp';

			if ( file_exists( $size_webp_file ) ) {
				unlink( $size_webp_file );
			}
		}
	}
}

/**
 * Add lazy loading attributes to images
 */
add_filter( 'wp_get_attachment_image_attributes', 'primefit_add_lazy_loading_attributes', 10, 3 );
function primefit_add_lazy_loading_attributes( $attr, $attachment, $size ) {
	// Don't add lazy loading to above-the-fold images
	$above_fold_images = [
		'hero-image',
		'logo',
		'header-image'
	];
	
	$is_above_fold = false;
	foreach ( $above_fold_images as $above_fold ) {
		if ( strpos( $attr['src'], $above_fold ) !== false ) {
			$is_above_fold = true;
			break;
		}
	}
	
	if ( ! $is_above_fold ) {
		$attr['loading'] = 'lazy';
		$attr['decoding'] = 'async';
	}
	
	return $attr;
}

/**
 * Optimize product images for better performance
 */
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'primefit_optimize_product_images', 10, 2 );
function primefit_optimize_product_images( $html, $attachment_id ) {
	// Replace with responsive image
	$responsive_html = primefit_get_responsive_image( $attachment_id, 'woocommerce_single', [
		'class' => 'wp-post-image',
		'loading' => 'lazy',
		'webp' => true
	] );
	
	return $responsive_html ?: $html;
}

/**
 * Optimize product loop images
 */
add_filter( 'woocommerce_product_get_image', 'primefit_optimize_product_loop_images', 10, 2 );
function primefit_optimize_product_loop_images( $image, $product ) {
	$image_id = $product->get_image_id();

	if ( $image_id ) {
	$responsive_html = primefit_get_responsive_image( $image_id, 'primefit-product-loop', [
		'class' => 'attachment-woocommerce_thumbnail',
		'loading' => 'lazy',
		'webp' => true
	] );

		return $responsive_html ?: $image;
	}

	return $image;
}

/**
 * Optimize mini cart images
 */
add_filter( 'woocommerce_cart_item_thumbnail', 'primefit_optimize_mini_cart_images', 10, 3 );
function primefit_optimize_mini_cart_images( $image, $cart_item, $cart_item_key ) {
	$product = $cart_item['data'];
	$image_id = $product->get_image_id();

	if ( $image_id ) {
		$responsive_html = primefit_get_responsive_image( $image_id, 'thumbnail', [
			'class' => 'attachment-woocommerce_thumbnail',
			'loading' => 'lazy',
			'webp' => true
		] );

		return $responsive_html ?: $image;
	}

	return $image;
}

/**
 * Optimize cart widget images
 */
add_filter( 'woocommerce_widget_cart_item_thumbnail', 'primefit_optimize_widget_cart_images', 10, 3 );
function primefit_optimize_widget_cart_images( $image, $cart_item, $cart_item_key ) {
	$product = $cart_item['data'];
	$image_id = $product->get_image_id();

	if ( $image_id ) {
		$responsive_html = primefit_get_responsive_image( $image_id, 'thumbnail', [
			'class' => 'attachment-woocommerce_thumbnail',
			'loading' => 'lazy',
			'webp' => true
		] );

		return $responsive_html ?: $image;
	}

	return $image;
}

/**
 * Check if WebP generation is supported
 */
function primefit_webp_supported() {
	return function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' );
}


/**
 * Add image optimization admin notice
 */
add_action( 'admin_notices', 'primefit_image_optimization_notice' );
function primefit_image_optimization_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Check WebP support
	if ( ! primefit_webp_supported() ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php _e( 'PrimeFit Theme:', 'primefit' ); ?></strong>
				<?php _e( 'WebP generation is not supported on this server. Please contact your hosting provider to enable GD library with WebP support.', 'primefit' ); ?>
			</p>
		</div>
		<?php
		return;
	}
	
	$optimized_count = get_option( 'primefit_images_optimized', 0 );
	$total_images = wp_count_posts( 'attachment' )->inherit;
	
	if ( $optimized_count < $total_images ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php _e( 'PrimeFit Theme:', 'primefit' ); ?></strong>
				<?php printf( 
					__( 'Generate WebP versions for %d images to improve performance. ', 'primefit' ), 
					$total_images - $optimized_count 
				); ?>
				<a href="<?php echo admin_url( 'admin.php?page=primefit-optimize-images' ); ?>" class="button button-primary">
					<?php _e( 'Generate WebP Images', 'primefit' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

/**
 * Add image optimization admin page
 */
add_action( 'admin_menu', 'primefit_add_image_optimization_menu' );
function primefit_add_image_optimization_menu() {
	add_management_page(
		__( 'Optimize Images', 'primefit' ),
		__( 'Optimize Images', 'primefit' ),
		'manage_options',
		'primefit-optimize-images',
		'primefit_image_optimization_page'
	);
}

/**
 * Image optimization admin page
 */
function primefit_image_optimization_page() {
	if ( isset( $_POST['optimize_images'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'primefit_optimize_images' ) ) {
		$processed_count = primefit_optimize_all_images();
		update_option( 'primefit_images_optimized', $processed_count );
		echo '<div class="notice notice-success"><p>' . sprintf( __( 'Successfully generated WebP versions for %d images.', 'primefit' ), $processed_count ) . '</p></div>';
	}
	
	$webp_supported = primefit_webp_supported();
	$total_images = wp_count_posts( 'attachment' )->inherit;
	$optimized_count = get_option( 'primefit_images_optimized', 0 );
	?>
	<div class="wrap">
		<h1><?php _e( 'WebP Image Generation', 'primefit' ); ?></h1>
		
		<?php if ( ! $webp_supported ) : ?>
			<div class="notice notice-error">
				<p><strong><?php _e( 'WebP Support Not Available', 'primefit' ); ?></strong></p>
				<p><?php _e( 'Your server does not support WebP generation. Please contact your hosting provider to enable GD library with WebP support.', 'primefit' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-info">
				<p><strong><?php _e( 'WebP Support Available', 'primefit' ); ?></strong></p>
				<p><?php _e( 'Your server supports WebP generation. This will create smaller, faster-loading images.', 'primefit' ); ?></p>
			</div>
			
			<div class="card">
				<h2><?php _e( 'Image Statistics', 'primefit' ); ?></h2>
				<p><strong><?php _e( 'Total Images:', 'primefit' ); ?></strong> <?php echo $total_images; ?></p>
				<p><strong><?php _e( 'WebP Versions Generated:', 'primefit' ); ?></strong> <?php echo $optimized_count; ?></p>
				<p><strong><?php _e( 'Remaining:', 'primefit' ); ?></strong> <?php echo max( 0, $total_images - $optimized_count ); ?></p>
			</div>
			
			<form method="post">
				<?php wp_nonce_field( 'primefit_optimize_images' ); ?>
				<p>
					<input type="submit" name="optimize_images" class="button button-primary" value="<?php _e( 'Generate WebP Versions for All Images', 'primefit' ); ?>" />
				</p>
			</form>
			
			<div class="card">
				<h3><?php _e( 'Benefits of WebP', 'primefit' ); ?></h3>
				<ul>
					<li><?php _e( '25-35% smaller file sizes compared to JPEG', 'primefit' ); ?></li>
					<li><?php _e( 'Faster page load times', 'primefit' ); ?></li>
					<li><?php _e( 'Better user experience', 'primefit' ); ?></li>
					<li><?php _e( 'Automatic generation for new uploads', 'primefit' ); ?></li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Optimize all images by generating WebP versions
 */
function primefit_optimize_all_images() {
	$args = [
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
		'post_status' => 'inherit'
	];
	
	$attachments = get_posts( $args );
	$processed_count = 0;
	
	foreach ( $attachments as $attachment ) {
		$file_path = get_attached_file( $attachment->ID );
		
		if ( $file_path && file_exists( $file_path ) ) {
			// Generate WebP version
			primefit_generate_webp_version( $file_path );

			$processed_count++;
		}
	}
	
	return $processed_count;
}

/**
 * Optimize a single image by generating WebP versions
 */
function primefit_optimize_single_image( $attachment_id ) {
	$processed_count = 0;
	
	// Generate WebP for original size
	if ( primefit_generate_webp_image( $attachment_id, 'full' ) ) {
		$processed_count++;
	}
	
	// Generate WebP for all registered sizes
	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( $metadata && isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
		foreach ( $metadata['sizes'] as $size_name => $size_data ) {
			if ( primefit_generate_webp_image( $attachment_id, $size_name ) ) {
				$processed_count++;
			}
		}
	}
	
	return $processed_count;
}

/**
 * Generate WebP version of image
 */
function primefit_generate_webp_version( $file_path ) {
	$file_info = pathinfo( $file_path );
	$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
	
	// Skip if WebP version already exists
	if ( file_exists( $webp_path ) ) {
		return true;
	}
	
	// Use WordPress image editor
	$editor = wp_get_image_editor( $file_path );
	if ( is_wp_error( $editor ) ) {
		return false;
	}
	
	// Save as WebP
	$result = $editor->save( $webp_path, 'image/webp' );
	return ! is_wp_error( $result );
}


/**
 * Add image optimization to media library
 */
add_filter( 'attachment_fields_to_edit', 'primefit_add_image_optimization_fields', 10, 2 );
function primefit_add_image_optimization_fields( $fields, $post ) {
	if ( strpos( $post->post_mime_type, 'image/' ) === 0 ) {
		$fields['primefit_optimize'] = [
			'label' => __( 'Optimize Image', 'primefit' ),
			'input' => 'html',
			'html' => '<button type="button" class="button primefit-optimize-single" data-id="' . $post->ID . '">' . __( 'Generate WebP', 'primefit' ) . '</button>'
		];
	}
	
	return $fields;
}

/**
 * AJAX handler for single image optimization
 */
add_action( 'wp_ajax_primefit_optimize_single_image', 'primefit_ajax_optimize_single_image' );
function primefit_ajax_optimize_single_image() {
	check_ajax_referer( 'primefit_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized' );
	}
	
	$attachment_id = intval( $_POST['attachment_id'] );
	$file_path = get_attached_file( $attachment_id );
	
	if ( $file_path && file_exists( $file_path ) ) {
		$webp_result = primefit_generate_webp_version( $file_path );

		wp_send_json_success( [
			'webp' => $webp_result
		] );
	}
	
	wp_send_json_error( 'File not found' );
}
