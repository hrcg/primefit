<?php
/**
 * PrimeFit Theme ACF Field Groups
 *
 * Advanced Custom Fields configuration for product custom fields
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if ACF is active
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

/**
 * Register ACF field groups for WooCommerce products
 */
add_action( 'acf/init', 'primefit_register_acf_product_fields' );
function primefit_register_acf_product_fields() {
	
	/**
	 * Product Features Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_product_features',
		'title' => 'Product Features',
		'fields' => array(
			array(
				'key' => 'field_product_features',
				'label' => 'Product Features',
				'name' => 'product_features',
				'type' => 'repeater',
				'instructions' => 'Add product features with images and descriptions',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_feature_title',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Feature',
				'sub_fields' => array(
					array(
						'key' => 'field_feature_title',
						'label' => 'Feature Title',
						'name' => 'title',
						'type' => 'text',
						'instructions' => 'Enter the feature title (e.g., "FRONT ZIP POCKET")',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '30',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'FRONT ZIP POCKET',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_feature_image',
						'label' => 'Feature Image',
						'name' => 'image',
						'type' => 'image',
						'instructions' => 'Select an image for this feature',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '30',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'id',
						'preview_size' => 'thumbnail',
						'library' => 'all',
						'min_width' => '',
						'min_height' => '',
						'min_size' => '',
						'max_width' => '',
						'max_height' => '',
						'max_size' => '',
						'mime_types' => '',
					),
					array(
						'key' => 'field_feature_description',
						'label' => 'Feature Description',
						'name' => 'description',
						'type' => 'text',
						'instructions' => 'Brief description of the feature',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '40',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'Secure storage for small items',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'product',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Product features with images and descriptions',
	));

	/**
	 * Technical Highlights Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_technical_highlights',
		'title' => 'Technical Highlights',
		'fields' => array(
			array(
				'key' => 'field_technical_highlights',
				'label' => 'Technical Highlights',
				'name' => 'technical_highlights',
				'type' => 'repeater',
				'instructions' => 'Add technical highlights with icons and descriptions',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_highlight_title',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Highlight',
				'sub_fields' => array(
					array(
						'key' => 'field_highlight_title',
						'label' => 'Highlight Title',
						'name' => 'title',
						'type' => 'text',
						'instructions' => 'Enter the highlight title (e.g., "DESIGNED FOR TRAINING")',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '25',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'DESIGNED FOR TRAINING',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_highlight_icon',
						'label' => 'Icon SVG',
						'name' => 'icon',
						'type' => 'textarea',
						'instructions' => 'Paste the SVG icon code',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '25',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '<svg width="24" height="24">...</svg>',
						'maxlength' => '',
						'rows' => 3,
						'new_lines' => '',
					),
					array(
						'key' => 'field_highlight_description',
						'label' => 'Description',
						'name' => 'description',
						'type' => 'textarea',
						'instructions' => 'Description of the technical highlight',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '50',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'Built with the intention of gym training, lifting, HIIT, hybrid training, and cardio.',
						'maxlength' => '',
						'rows' => 3,
						'new_lines' => '',
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'product',
				),
			),
		),
		'menu_order' => 1,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Technical highlights with icons and descriptions',
	));

	/**
	 * Product Information Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_product_information',
		'title' => 'Product Information',
		'fields' => array(
			array(
				'key' => 'field_designed_for',
				'label' => 'Designed For',
				'name' => 'designed_for',
				'type' => 'wysiwyg',
				'instructions' => 'Describe what this product is designed for',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'tabs' => 'all',
				'toolbar' => 'basic',
				'media_upload' => 0,
				'delay' => 1,
			),
			array(
				'key' => 'field_fabric_technology',
				'label' => 'Fabric + Technology',
				'name' => 'fabric_technology',
				'type' => 'wysiwyg',
				'instructions' => 'Describe the fabric and technology used in this product',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'tabs' => 'all',
				'toolbar' => 'basic',
				'media_upload' => 0,
				'delay' => 1,
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'product',
				),
			),
		),
		'menu_order' => 2,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Product information sections',
	));

}

/**
 * Helper function to get ACF field with fallback to legacy meta
 */
function primefit_get_product_field( $field_name, $product_id = null, $legacy_meta_key = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	
	// Try ACF first
	$value = get_field( $field_name, $product_id );
	
	// Fallback to legacy meta if ACF returns empty and legacy key is provided
	if ( empty( $value ) && $legacy_meta_key ) {
		$value = get_post_meta( $product_id, $legacy_meta_key, true );
	}
	
	return $value;
}

/**
 * Helper function to get product features with fallback to legacy JSON
 */
function primefit_get_product_features( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	
	// Try ACF repeater field first
	$features = get_field( 'product_features', $product_id );
	
	if ( empty( $features ) ) {
		// Fallback to legacy JSON format
		$legacy_features = get_post_meta( $product_id, 'primefit_product_features', true );
		if ( ! empty( $legacy_features ) ) {
			$features_data = json_decode( $legacy_features, true );
			if ( is_array( $features_data ) ) {
				return $features_data;
			}
		}
	}
	
	return $features;
}

/**
 * Helper function to get technical highlights with fallback to legacy JSON
 */
function primefit_get_technical_highlights( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	
	// Try ACF repeater field first
	$highlights = get_field( 'technical_highlights', $product_id );
	
	if ( empty( $highlights ) ) {
		// Fallback to legacy JSON format
		$legacy_highlights = get_post_meta( $product_id, 'primefit_technical_highlights', true );
		if ( ! empty( $legacy_highlights ) ) {
			$highlights_data = json_decode( $legacy_highlights, true );
			if ( is_array( $highlights_data ) ) {
				return $highlights_data;
			}
		}
	}
	
	return $highlights;
}

/**
 * Helper function to get product description with fallbacks
 */
function primefit_get_product_description( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	
	// Try legacy meta first
	$description = get_post_meta( $product_id, 'primefit_description', true );
	
	if ( empty( $description ) && function_exists( 'wc_get_product' ) ) {
		// Fallback to WooCommerce product description
		$product_obj = wc_get_product( $product_id );
		if ( $product_obj ) {
			$description = $product_obj->get_description();
		}
	}
	
	return $description;
}
