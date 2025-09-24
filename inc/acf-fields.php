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
 * Get color options from WooCommerce product variations
 * Optimized to avoid N+1 queries by bulk loading variation objects
 */
function primefit_get_product_color_choices( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return array();
	}

	$colors = array();
	$variations = $product->get_available_variations();
	$variation_ids = wp_list_pluck( $variations, 'variation_id' );

	// Bulk load all variation objects to avoid N+1 queries
	$variation_objects = array();
	if ( ! empty( $variation_ids ) ) {
		$variation_objects = array_filter( array_map( 'wc_get_product', $variation_ids ) );
	}

	foreach ( $variations as $variation ) {
		$variation_id = $variation['variation_id'];
		$variation_obj = isset( $variation_objects[ $variation_id ] ) ? $variation_objects[ $variation_id ] : null;

		if ( ! $variation_obj || ! $variation_obj->is_in_stock() ) {
			continue;
		}

		$attributes = $variation['attributes'];

		// Look for color attributes
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( stripos( $attribute_name, 'color' ) !== false && ! empty( $attribute_value ) ) {
				// Clean up the color value for display
				$color_name = ucwords( str_replace( array( 'attribute_', 'pa_' ), '', $attribute_value ) );
				// Use normalized color value as key for consistent matching
				$normalized_color = strtolower( trim( $attribute_value ) );
				$colors[ $normalized_color ] = $color_name;
			}
		}
	}

	return $colors;
}

/**
 * Filter ACF field choices to populate color options from variations
 */
add_filter( 'acf/load_field/key=field_variation_color', 'primefit_populate_color_choices' );
function primefit_populate_color_choices( $field ) {
	// Get the current product ID from the post context
	$product_id = get_the_ID();

	if ( $product_id ) {
		$color_choices = primefit_get_product_color_choices( $product_id );
		$field['choices'] = $color_choices;
	}

	return $field;
}

/**
 * Register ACF field groups for WooCommerce products
 */
add_action( 'acf/init', 'primefit_register_acf_product_fields' );

/**
 * Register ACF field groups for WooCommerce coupons
 */
add_action( 'acf/init', 'primefit_register_acf_coupon_fields' );
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
						'key' => 'field_highlight_icon_image',
						'label' => 'Icon Image',
						'name' => 'icon_image',
						'type' => 'image',
						'instructions' => 'Upload an icon image (SVG, PNG, JPG)',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '25',
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
						'mime_types' => 'svg,png,jpg,jpeg',
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
	 * Size Guide Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_size_guide',
		'title' => 'Size Guide',
		'fields' => array(
			array(
				'key' => 'field_size_guide_image',
				'label' => 'Size Guide Image',
				'name' => 'size_guide_image',
				'type' => 'image',
				'instructions' => 'Upload a size guide image that will be displayed in a popup modal when customers click the "SIZE GUIDE" button',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'return_format' => 'id',
				'preview_size' => 'medium',
				'library' => 'all',
				'min_width' => '',
				'min_height' => '',
				'min_size' => '',
				'max_width' => '',
				'max_height' => '',
				'max_size' => '',
				'mime_types' => 'jpg,jpeg,png,webp',
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
		'description' => 'Size guide image for product sizing information',
	));

	/**
	 * Variation Gallery Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_variation_gallery',
		'title' => 'Variation Gallery Images',
		'fields' => array(
			array(
				'key' => 'field_variation_gallery',
				'label' => 'Variation Gallery Images',
				'name' => 'variation_gallery',
				'type' => 'repeater',
				'instructions' => 'Add gallery images for different product variations (color combinations)',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_variation_color',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Variation Gallery',
				'sub_fields' => array(
					array(
						'key' => 'field_variation_color',
						'label' => 'Color',
						'name' => 'color',
						'type' => 'select',
						'instructions' => 'Select a color from your product variations',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '30',
							'class' => '',
							'id' => '',
						),
						'choices' => array(),
						'default_value' => '',
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'return_format' => 'value',
						'ajax' => 0,
						'placeholder' => 'Select Color',
					),
					array(
						'key' => 'field_variation_images',
						'label' => 'Gallery Images',
						'name' => 'images',
						'type' => 'gallery',
						'instructions' => 'Select images for this color variation gallery',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '70',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'id',
						'preview_size' => 'thumbnail',
						'insert' => 'append',
						'library' => 'all',
						'min' => 1,
						'max' => 10,
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
		'menu_order' => 4,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => 'Gallery images for different color variations',
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
 * Helper function to get variation gallery images
 */
function primefit_get_variation_gallery( $product_id = null, $color = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}

	// Try ACF variation gallery field first
	$variation_galleries = get_field( 'variation_gallery', $product_id );

	if ( empty( $variation_galleries ) ) {
		return array();
	}

	// If no specific color requested, return empty array to use default gallery
	if ( empty( $color ) ) {
		return array();
	}

	// Find gallery for specific color
	$normalized_color = strtolower( trim( $color ) );
	foreach ( $variation_galleries as $gallery ) {
		if ( ! empty( $gallery['color'] ) && strtolower( trim( $gallery['color'] ) ) === $normalized_color ) {
			return ! empty( $gallery['images'] ) ? $gallery['images'] : array();
		}
	}

	// If specific color not found, return first gallery as fallback
	return ! empty( $variation_galleries[0]['images'] ) ? $variation_galleries[0]['images'] : array();
}

/**
 * Helper function to get all variation galleries
 */
function primefit_get_all_variation_galleries( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}

	$variation_galleries = get_field( 'variation_gallery', $product_id );

	if ( empty( $variation_galleries ) ) {
		return array();
	}

	$galleries = array();
	foreach ( $variation_galleries as $gallery ) {
		if ( ! empty( $gallery['color'] ) && ! empty( $gallery['images'] ) ) {
			$galleries[ $gallery['color'] ] = $gallery['images'];
		}
	}

	return $galleries;
}

/**
 * Helper function to get variation gallery data with color matching
 */
function primefit_get_variation_gallery_data( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}

	$variation_galleries = get_field( 'variation_gallery', $product_id );

	if ( empty( $variation_galleries ) ) {
		return array();
	}

	$gallery_data = array();
	foreach ( $variation_galleries as $gallery ) {
		if ( ! empty( $gallery['color'] ) && ! empty( $gallery['images'] ) ) {
			// Use normalized color value as key for consistent matching
			$color_key = strtolower( trim( $gallery['color'] ) );
			$gallery_data[ $color_key ] = array(
				'images' => $gallery['images'],
				'count' => count( $gallery['images'] )
			);
		}
	}

	return $gallery_data;
}

/**
 * Helper function to get available colors from product variations
 */
function primefit_get_product_colors( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}

	$color_choices = primefit_get_product_color_choices( $product_id );
	return array_keys( $color_choices );
}

/**
 * Helper function to check if a product has variation galleries configured
 */
function primefit_has_variation_galleries( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}

	$variation_galleries = get_field( 'variation_gallery', $product_id );
	return ! empty( $variation_galleries );
}

/**
 * Helper function to get size guide image
 */
function primefit_get_size_guide_image( $product_id = null ) {
	if ( ! $product_id ) {
		global $product;
		$product_id = $product ? $product->get_id() : get_the_ID();
	}
	
	$size_guide_image_id = get_field( 'size_guide_image', $product_id );
	
	if ( $size_guide_image_id ) {
		return wp_get_attachment_image_url( $size_guide_image_id, 'full' );
	}
	
	return false;
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

/**
 * Register ACF field groups for WooCommerce coupons
 */
function primefit_register_acf_coupon_fields() {

	/**
	 * Coupon Email Association Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_coupon_emails',
		'title' => 'Coupon Email Management',
		'fields' => array(
			array(
				'key' => 'field_associated_emails',
				'label' => 'Associated Email Addresses',
				'name' => 'associated_emails',
				'type' => 'repeater',
				'instructions' => 'Add email addresses that can use this coupon code. Leave empty to allow any email.',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_email_address',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Email',
				'sub_fields' => array(
					array(
						'key' => 'field_email_address',
						'label' => 'Email Address',
						'name' => 'email',
						'type' => 'email',
						'instructions' => 'Enter the email address allowed to use this coupon',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '60',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'customer@example.com',
					),
					array(
						'key' => 'field_email_notes',
						'label' => 'Notes',
						'name' => 'notes',
						'type' => 'text',
						'instructions' => 'Optional notes about this email association',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '40',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => 'VIP customer, newsletter subscriber, etc.',
						'maxlength' => 100,
					),
				),
			),
			array(
				'key' => 'field_email_restrictions',
				'label' => 'Email Usage Restrictions',
				'name' => 'email_restrictions',
				'type' => 'select',
				'instructions' => 'Choose how to handle emails not in the associated list',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'choices' => array(
					'allow_all' => 'Allow any email address',
					'restrict_list' => 'Only allow emails in the associated list',
					'require_verification' => 'Require email verification for new addresses',
				),
				'default_value' => 'allow_all',
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'ajax' => 0,
				'placeholder' => '',
			),
			array(
				'key' => 'field_usage_notifications',
				'label' => 'Usage Notifications',
				'name' => 'usage_notifications',
				'type' => 'true_false',
				'instructions' => 'Send email notifications when this coupon is used',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'Enable usage notifications',
				'default_value' => 0,
				'ui' => 1,
				'ui_on_text' => 'Enabled',
				'ui_off_text' => 'Disabled',
			),
			array(
				'key' => 'field_notification_emails',
				'label' => 'Notification Recipients',
				'name' => 'notification_emails',
				'type' => 'textarea',
				'instructions' => 'Email addresses to receive usage notifications (one per line)',
				'required' => 0,
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_usage_notifications',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => get_option( 'admin_email' ),
				'placeholder' => "admin@yourstore.com\nmanager@yourstore.com",
				'maxlength' => '',
				'rows' => 3,
				'new_lines' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'shop_coupon',
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
		'description' => 'Email association and notification settings for coupons',
	));

	/**
	 * Coupon Analytics Field Group
	 */
	acf_add_local_field_group( array(
		'key' => 'group_coupon_analytics',
		'title' => 'Coupon Analytics',
		'fields' => array(
			array(
				'key' => 'field_enable_analytics',
				'label' => 'Enable Advanced Analytics',
				'name' => 'enable_analytics',
				'type' => 'true_false',
				'instructions' => 'Track detailed usage analytics for this coupon',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'message' => 'Enable detailed analytics tracking',
				'default_value' => 1,
				'ui' => 1,
				'ui_on_text' => 'Enabled',
				'ui_off_text' => 'Disabled',
			),
			array(
				'key' => 'field_analytics_goal',
				'label' => 'Usage Goal',
				'name' => 'analytics_goal',
				'type' => 'number',
				'instructions' => 'Target number of uses for this coupon',
				'required' => 0,
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_enable_analytics',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '100',
				'min' => 1,
				'max' => '',
				'step' => 1,
			),
			array(
				'key' => 'field_target_audience',
				'label' => 'Target Audience',
				'name' => 'target_audience',
				'type' => 'textarea',
				'instructions' => 'Describe the target audience for this coupon campaign',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => 'Newsletter subscribers, first-time customers, VIP members, etc.',
				'maxlength' => '',
				'rows' => 3,
				'new_lines' => '',
			),
			array(
				'key' => 'field_campaign_notes',
				'label' => 'Campaign Notes',
				'name' => 'campaign_notes',
				'type' => 'wysiwyg',
				'instructions' => 'Additional notes about this coupon campaign',
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
					'value' => 'shop_coupon',
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
		'description' => 'Advanced analytics and campaign tracking for coupons',
	));

}
