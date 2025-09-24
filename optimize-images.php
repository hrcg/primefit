<?php
/**
 * PrimeFit Image Optimization Script
 * 
 * Run this script to optimize all existing images with WebP format
 * Usage: php optimize-images.php
 */

// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../../wp-load.php' );

// Check if we're running from command line or admin
if ( ! defined( 'WP_CLI' ) && ! is_admin() && ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied. This script can only be run by administrators.' );
}

echo "PrimeFit Image Optimization Script\n";
echo "==================================\n\n";

// Check format support
$webp_supported = function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' );

echo "Format Support:\n";
echo "- WebP: " . ( $webp_supported ? "✓ Supported" : "✗ Not supported" ) . "\n\n";

if ( ! $webp_supported ) {
	echo "Error: WebP format is not supported on this server.\n";
	echo "Please contact your hosting provider to enable GD library with WebP support.\n";
	exit( 1 );
}

// Get all images
$args = [
	'post_type' => 'attachment',
	'post_mime_type' => 'image',
	'posts_per_page' => -1,
	'post_status' => 'inherit'
];

$attachments = get_posts( $args );
$total_images = count( $attachments );
$processed_count = 0;
$webp_count = 0;
$errors = [];

echo "Found {$total_images} images to process.\n\n";

foreach ( $attachments as $attachment ) {
	$attachment_id = $attachment->ID;
	$file_path = get_attached_file( $attachment_id );
	
	if ( ! $file_path || ! file_exists( $file_path ) ) {
		$errors[] = "File not found for attachment ID {$attachment_id}";
		continue;
	}
	
	echo "Processing: " . basename( $file_path ) . " (ID: {$attachment_id})... ";
	
	$attachment_processed = 0;
	
	// Generate WebP version if supported
	if ( $webp_supported ) {
		$webp_result = primefit_generate_webp_image( $attachment_id, 'full' );
		if ( $webp_result ) {
			$webp_count++;
			$attachment_processed++;
		}
		
		// Generate WebP for all registered sizes
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( $metadata && isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				primefit_generate_webp_image( $attachment_id, $size_name );
			}
		}
	}
	
	
	if ( $attachment_processed > 0 ) {
		echo "✓ ({$attachment_processed} formats)\n";
		$processed_count++;
	} else {
		echo "✗ (no formats generated)\n";
	}
	
	// Update progress every 10 images
	if ( $processed_count % 10 === 0 ) {
		echo "Progress: {$processed_count}/{$total_images} images processed\n";
	}
}

echo "\n";
echo "Optimization Complete!\n";
echo "======================\n";
echo "Total images processed: {$processed_count}/{$total_images}\n";
echo "WebP versions generated: {$webp_count}\n";

if ( ! empty( $errors ) ) {
	echo "\nErrors encountered:\n";
	foreach ( $errors as $error ) {
		echo "- {$error}\n";
	}
}

// Update the optimization count
update_option( 'primefit_images_optimized', $processed_count );

echo "\nImage optimization completed successfully!\n";
echo "Your images are now optimized for better PageSpeed Insights scores.\n";
