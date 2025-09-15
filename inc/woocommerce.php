<?php
/**
 * PrimeFit Theme WooCommerce Integration
 *
 * WooCommerce-specific functionality and customizations
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Admin: Product custom fields (Highlights, Details) in WooCommerce product data -> General
 */
add_action( 'woocommerce_product_options_general_product_data', 'primefit_add_product_custom_fields' );
function primefit_add_product_custom_fields() {
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
}

/**
 * Save product custom fields
 */
add_action( 'woocommerce_process_product_meta', 'primefit_save_product_custom_fields' );
function primefit_save_product_custom_fields( $post_id ) {
	$map = [ 'primefit_highlights', 'primefit_details' ];
	foreach ( $map as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}

/**
 * Add tabs for Highlights and Details on product page
 */
add_filter( 'woocommerce_product_tabs', 'primefit_add_product_tabs' );
function primefit_add_product_tabs( $tabs ) {
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
}

/**
 * Meta box for Additional Sections (rich content with images) displayed below tabs
 */
add_action( 'add_meta_boxes', 'primefit_add_product_meta_box' );
function primefit_add_product_meta_box() {
	add_meta_box( 
		'primefit_product_sections', 
		__( 'PrimeFit Additional Sections', 'primefit' ), 
		'primefit_product_meta_box_callback', 
		'product', 
		'normal', 
		'default' 
	);
}

/**
 * Meta box callback function
 */
function primefit_product_meta_box_callback( $post ) {
	$val = get_post_meta( $post->ID, 'primefit_additional_html', true );
	wp_editor( $val, 'primefit_additional_html', [ 'textarea_rows' => 8 ] );
}

/**
 * Save additional sections meta box
 */
add_action( 'save_post_product', 'primefit_save_product_additional_html' );
function primefit_save_product_additional_html( $post_id ) {
	if ( isset( $_POST['primefit_additional_html'] ) ) {
		update_post_meta( $post_id, 'primefit_additional_html', wp_kses_post( wp_unslash( $_POST['primefit_additional_html'] ) ) );
	}
}

/**
 * Display additional sections on product page
 */
add_action( 'woocommerce_after_single_product_summary', 'primefit_display_additional_sections', 12 );
function primefit_display_additional_sections() {
	$additional = get_post_meta( get_the_ID(), 'primefit_additional_html', true );
	if ( ! empty( $additional ) ) {
		echo '<section class="pf-additional container">' . wp_kses_post( $additional ) . '</section>';
	}
}

/**
 * Header cart fragments (update cart count asynchronously)
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'primefit_header_cart_fragment' );
function primefit_header_cart_fragment( $fragments ) {
	ob_start();
	?>
	<span class="cart-count" data-cart-count>
		<?php echo WC()->cart ? intval( WC()->cart->get_cart_contents_count() ) : 0; ?>
	</span>
	<?php
	$fragments['span[data-cart-count]'] = ob_get_clean();
	return $fragments;
}