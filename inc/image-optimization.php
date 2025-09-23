<?php
/**
 * PrimeFit Theme Image Optimization
 *
 * Advanced image loading, lazy loading, and WebP/AVIF support
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate responsive image markup with lazy loading
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
		'avif' => true,
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
	
	// Generate responsive sources
	$sources = primefit_generate_responsive_sources( $attachment_id, $size, $args );
	
	// Build picture element
	$picture_html = '<picture>';
	
	// Add AVIF sources if supported
	if ( $args['avif'] ) {
		foreach ( $sources['avif'] as $source ) {
			$picture_html .= sprintf(
				'<source type="image/avif" srcset="%s" sizes="%s">',
				esc_attr( $source['srcset'] ),
				esc_attr( $args['sizes'] )
			);
		}
	}
	
	// Add WebP sources if supported
	if ( $args['webp'] ) {
		foreach ( $sources['webp'] as $source ) {
			$picture_html .= sprintf(
				'<source type="image/webp" srcset="%s" sizes="%s">',
				esc_attr( $source['srcset'] ),
				esc_attr( $args['sizes'] )
			);
		}
	}
	
	// Add fallback img element
	$img_attributes = [
		'src' => wp_get_attachment_image_url( $attachment_id, $size ),
		'alt' => $alt_text,
		'class' => $args['class'],
		'loading' => $args['loading'],
		'sizes' => $args['sizes'],
	];
	
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
		'avif' => [],
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
		// Generate AVIF source
		if ( $args['avif'] ) {
			$avif_url = primefit_get_image_url_with_format( $attachment_id, $breakpoint_size, 'avif' );
			if ( $avif_url ) {
				$sources['avif'][] = [
					'srcset' => $avif_url . ' ' . $width,
					'media' => ''
				];
			}
		}
		
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
 * Get image URL with specific format (WebP/AVIF)
 *
 * @param int $attachment_id Image attachment ID
 * @param string $size Image size
 * @param string $format Image format (webp, avif)
 * @return string|false Image URL or false
 */
function primefit_get_image_url_with_format( $attachment_id, $size, $format ) {
	$original_url = wp_get_attachment_image_url( $attachment_id, $size );
	if ( ! $original_url ) {
		return false;
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
		'webp' => true,
		'avif' => true
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
			'webp' => true,
			'avif' => true
		] );
		
		return $responsive_html ?: $image;
	}
	
	return $image;
}

/**
 * Add image optimization admin notice
 */
add_action( 'admin_notices', 'primefit_image_optimization_notice' );
function primefit_image_optimization_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
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
					__( 'Optimize %d images for better performance. ', 'primefit' ), 
					$total_images - $optimized_count 
				); ?>
				<a href="<?php echo admin_url( 'admin.php?page=primefit-optimize-images' ); ?>" class="button button-primary">
					<?php _e( 'Optimize Images', 'primefit' ); ?>
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
		echo '<div class="notice notice-success"><p>' . sprintf( __( 'Successfully optimized %d images.', 'primefit' ), $processed_count ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php _e( 'Optimize Images', 'primefit' ); ?></h1>
		<p><?php _e( 'This will generate WebP and AVIF versions of all images for better performance.', 'primefit' ); ?></p>
		
		<form method="post">
			<?php wp_nonce_field( 'primefit_optimize_images' ); ?>
			<p>
				<input type="submit" name="optimize_images" class="button button-primary" value="<?php _e( 'Optimize All Images', 'primefit' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}

/**
 * Optimize all images by generating WebP and AVIF versions
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
			
			// Generate AVIF version
			primefit_generate_avif_version( $file_path );
			
			$processed_count++;
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
 * Generate AVIF version of image
 */
function primefit_generate_avif_version( $file_path ) {
	$file_info = pathinfo( $file_path );
	$avif_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.avif';
	
	// Skip if AVIF version already exists
	if ( file_exists( $avif_path ) ) {
		return true;
	}
	
	// Use WordPress image editor
	$editor = wp_get_image_editor( $file_path );
	if ( is_wp_error( $editor ) ) {
		return false;
	}
	
	// Save as AVIF
	$result = $editor->save( $avif_path, 'image/avif' );
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
			'html' => '<button type="button" class="button primefit-optimize-single" data-id="' . $post->ID . '">' . __( 'Generate WebP/AVIF', 'primefit' ) . '</button>'
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
		$avif_result = primefit_generate_avif_version( $file_path );
		
		wp_send_json_success( [
			'webp' => $webp_result,
			'avif' => $avif_result
		] );
	}
	
	wp_send_json_error( 'File not found' );
}
