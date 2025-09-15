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
	
	// Register navigation menus
	register_nav_menus( [
		'primary'   => esc_html__( 'Primary Menu', 'primefit' ),
		'secondary' => esc_html__( 'Secondary Menu', 'primefit' ),
		'tertiary'  => esc_html__( 'Tertiary Menu', 'primefit' ),
		'footer'    => esc_html__( 'Footer Menu', 'primefit' ),
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
