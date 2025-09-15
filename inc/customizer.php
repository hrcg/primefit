<?php
/**
 * PrimeFit Theme Customizer
 *
 * Theme customizer settings and controls
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme Customizer Settings
 */
add_action( 'customize_register', 'primefit_customize_register' );
function primefit_customize_register( $wp_customize ) {
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
	$wp_customize->add_section( 'primefit_hero', array(
		'title'    => __( 'Hero Section', 'primefit' ),
		'priority' => 30,
	) );

	// Hero Background Image (Desktop)
	$wp_customize->add_setting( 'primefit_hero_image_desktop', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_image_desktop', array(
		'label'    => __( 'Hero Background Image (Desktop)', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'image',
	) ) );

	// Hero Background Image (Mobile)
	$wp_customize->add_setting( 'primefit_hero_image_mobile', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_image_mobile', array(
		'label'    => __( 'Hero Background Image (Mobile)', 'primefit' ),
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

	// Hero Background Video (Desktop)
	$wp_customize->add_setting( 'primefit_hero_video_desktop', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_video_desktop', array(
		'label'    => __( 'Hero Background Video (Desktop)', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'video',
	) ) );

	// Hero Background Video (Mobile)
	$wp_customize->add_setting( 'primefit_hero_video_mobile', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_video_mobile', array(
		'label'    => __( 'Hero Background Video (Mobile)', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'video',
	) ) );

	// Hero Video Autoplay
	$wp_customize->add_setting( 'primefit_hero_video_autoplay', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_hero_video_autoplay', array(
		'label'   => __( 'Autoplay Video', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'checkbox',
	) );

	// Hero Video Loop
	$wp_customize->add_setting( 'primefit_hero_video_loop', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_hero_video_loop', array(
		'label'   => __( 'Loop Video', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'checkbox',
	) );

	// Hero Video Muted
	$wp_customize->add_setting( 'primefit_hero_video_muted', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_hero_video_muted', array(
		'label'   => __( 'Mute Video', 'primefit' ),
		'section' => 'primefit_hero',
		'type'    => 'checkbox',
	) );

	// Training Division Section Panel
	$wp_customize->add_section( 'primefit_training_division', array(
		'title'    => __( 'Training Division Section', 'primefit' ),
		'priority' => 35,
	) );

	// Training Division Background Image
	$wp_customize->add_setting( 'primefit_training_division_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_training_division_image', array(
		'label'    => __( 'Background Image', 'primefit' ),
		'section'  => 'primefit_training_division',
		'mime_type' => 'image',
	) ) );

	// Training Division Heading
	$wp_customize->add_setting( 'primefit_training_division_heading', array(
		'default'           => 'TRAINING DIVISION',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_heading', array(
		'label'   => __( 'Heading', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'text',
	) );

	// Training Division Subheading
	$wp_customize->add_setting( 'primefit_training_division_subheading', array(
		'default'           => '[ FALL 2025 COLLECTION ] A PATH WITHOUT OBSTACLES LEADS NOWHERE',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_subheading', array(
		'label'   => __( 'Subheading', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'textarea',
	) );

	// Training Division Primary CTA Text
	$wp_customize->add_setting( 'primefit_training_division_cta_primary_text', array(
		'default'           => 'SHOP NOW',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_cta_primary_text', array(
		'label'   => __( 'Primary Button Text', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'text',
	) );

	// Training Division Primary CTA Link
	$wp_customize->add_setting( 'primefit_training_division_cta_primary_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_training_division_cta_primary_link', array(
		'label'   => __( 'Primary Button Link', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'url',
	) );

	// Training Division Secondary CTA Text
	$wp_customize->add_setting( 'primefit_training_division_cta_secondary_text', array(
		'default'           => 'VIEW LOOKBOOK',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_cta_secondary_text', array(
		'label'   => __( 'Secondary Button Text', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'text',
	) );

	// Training Division Secondary CTA Link
	$wp_customize->add_setting( 'primefit_training_division_cta_secondary_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_training_division_cta_secondary_link', array(
		'label'   => __( 'Secondary Button Link', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'url',
	) );

	// Training Division Show Secondary Button
	$wp_customize->add_setting( 'primefit_training_division_show_secondary_button', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_training_division_show_secondary_button', array(
		'label'   => __( 'Show Secondary Button', 'primefit' ),
		'section' => 'primefit_training_division',
		'type'    => 'checkbox',
	) );

	// Second Training Division Section Panel
	$wp_customize->add_section( 'primefit_training_division_2', array(
		'title'    => __( 'Second Training Division Section', 'primefit' ),
		'priority' => 36,
	) );

	// Second Training Division Background Image
	$wp_customize->add_setting( 'primefit_training_division_2_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_training_division_2_image', array(
		'label'    => __( 'Background Image', 'primefit' ),
		'section'  => 'primefit_training_division_2',
		'mime_type' => 'image',
	) ) );

	// Second Training Division Heading
	$wp_customize->add_setting( 'primefit_training_division_2_heading', array(
		'default'           => 'Become your best self',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_heading', array(
		'label'   => __( 'Heading', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'text',
	) );

	// Second Training Division Subheading
	$wp_customize->add_setting( 'primefit_training_division_2_subheading', array(
		'default'           => 'Unlock your potential with purpose-built gear designed for resilience, comfort, and top-tier performance.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_subheading', array(
		'label'   => __( 'Subheading', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'textarea',
	) );

	// Second Training Division Primary CTA Text
	$wp_customize->add_setting( 'primefit_training_division_2_cta_primary_text', array(
		'default'           => 'Arise Now',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_cta_primary_text', array(
		'label'   => __( 'Primary Button Text', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'text',
	) );

	// Second Training Division Primary CTA Link
	$wp_customize->add_setting( 'primefit_training_division_2_cta_primary_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_cta_primary_link', array(
		'label'   => __( 'Primary Button Link', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'url',
	) );

	// Second Training Division Secondary CTA Text
	$wp_customize->add_setting( 'primefit_training_division_2_cta_secondary_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_cta_secondary_text', array(
		'label'   => __( 'Secondary Button Text', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'text',
	) );

	// Second Training Division Secondary CTA Link
	$wp_customize->add_setting( 'primefit_training_division_2_cta_secondary_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_cta_secondary_link', array(
		'label'   => __( 'Secondary Button Link', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'url',
	) );

	// Second Training Division Show Secondary Button
	$wp_customize->add_setting( 'primefit_training_division_2_show_secondary_button', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_training_division_2_show_secondary_button', array(
		'label'   => __( 'Show Secondary Button', 'primefit' ),
		'section' => 'primefit_training_division_2',
		'type'    => 'checkbox',
	) );

	// Mega Menu Section Panel
	$wp_customize->add_section( 'primefit_mega_menu', array(
		'title'    => __( 'Mega Menu', 'primefit' ),
		'priority' => 40,
	) );

	// Mega Menu Enable/Disable
	$wp_customize->add_setting( 'primefit_mega_menu_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_enabled', array(
		'label'   => __( 'Enable Mega Menu', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'checkbox',
	) );

	// Mega Menu Trigger Menu Item
	$wp_customize->add_setting( 'primefit_mega_menu_trigger_item', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_trigger_item', array(
		'label'       => __( 'Menu Item That Triggers Mega Menu', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'select',
		'choices'     => primefit_get_primary_menu_items_choices(),
		'description' => __( 'Select which menu item should trigger the mega menu when hovered.', 'primefit' ),
	) );

	// Column 1 Heading
	$wp_customize->add_setting( 'primefit_mega_menu_column_1_heading', array(
		'default'           => 'TOPS',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_1_heading', array(
		'label'   => __( 'Column 1 Heading', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'text',
	) );

	// Column 1 Links
	$wp_customize->add_setting( 'primefit_mega_menu_column_1_links', array(
		'default'           => 'Sports Bras,Tanks & Short Sleeves,Hoodies & Sweatshirts',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_1_links', array(
		'label'       => __( 'Column 1 Links (comma-separated)', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'textarea',
		'description' => __( 'Enter links separated by commas. Example: Sports Bras,Tanks & Short Sleeves,Hoodies & Sweatshirts', 'primefit' ),
	) );

	// Column 2 Heading
	$wp_customize->add_setting( 'primefit_mega_menu_column_2_heading', array(
		'default'           => 'BOTTOMS',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_2_heading', array(
		'label'   => __( 'Column 2 Heading', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'text',
	) );

	// Column 2 Links
	$wp_customize->add_setting( 'primefit_mega_menu_column_2_links', array(
		'default'           => 'Shorts,Leggings & Joggers,Pants',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_2_links', array(
		'label'       => __( 'Column 2 Links (comma-separated)', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'textarea',
		'description' => __( 'Enter links separated by commas. Example: Shorts,Leggings & Joggers,Pants', 'primefit' ),
	) );

	// Column 3 Heading
	$wp_customize->add_setting( 'primefit_mega_menu_column_3_heading', array(
		'default'           => 'ACCESSORIES',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_3_heading', array(
		'label'   => __( 'Column 3 Heading', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'text',
	) );

	// Column 3 Links
	$wp_customize->add_setting( 'primefit_mega_menu_column_3_links', array(
		'default'           => 'Hats & Headwear,Bags,Socks,Jewelry & Hardware',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_3_links', array(
		'label'       => __( 'Column 3 Links (comma-separated)', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'textarea',
		'description' => __( 'Enter links separated by commas. Example: Hats & Headwear,Bags,Socks,Jewelry & Hardware', 'primefit' ),
	) );

	// Column 4 Heading
	$wp_customize->add_setting( 'primefit_mega_menu_column_4_heading', array(
		'default'           => 'DESIGNED FOR',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_4_heading', array(
		'label'   => __( 'Column 4 Heading', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'text',
	) );

	// Column 4 Links
	$wp_customize->add_setting( 'primefit_mega_menu_column_4_links', array(
		'default'           => 'Run,Train,Rec',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_4_links', array(
		'label'       => __( 'Column 4 Links (comma-separated)', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'textarea',
		'description' => __( 'Enter links separated by commas. Example: Run,Train,Rec', 'primefit' ),
	) );
}

/**
 * Helper function to get hero configuration from customizer
 */
function primefit_get_hero_config() {
	// Get desktop and mobile image IDs
	$hero_image_desktop_id = get_theme_mod( 'primefit_hero_image_desktop' );
	$hero_image_mobile_id = get_theme_mod( 'primefit_hero_image_mobile' );
	
	// Get image URLs
	$hero_image_desktop_url = $hero_image_desktop_id ? wp_get_attachment_image_url( $hero_image_desktop_id, 'full' ) : '';
	$hero_image_mobile_url = $hero_image_mobile_id ? wp_get_attachment_image_url( $hero_image_mobile_id, 'full' ) : '';
	
	// Get video IDs and URLs
	$hero_video_desktop_id = get_theme_mod( 'primefit_hero_video_desktop' );
	$hero_video_mobile_id = get_theme_mod( 'primefit_hero_video_mobile' );
	
	$hero_video_desktop_url = $hero_video_desktop_id ? wp_get_attachment_url( $hero_video_desktop_id ) : '';
	$hero_video_mobile_url = $hero_video_mobile_id ? wp_get_attachment_url( $hero_video_mobile_id ) : '';
	
	// Fallback to default image if no custom images are set
	$default_image_url = primefit_get_asset_uri( array( '/assets/images/DSC03756.webp', '/assets/images/hero-image.jpg' ) );
	
	if ( empty( $hero_image_desktop_url ) ) {
		$hero_image_desktop_url = $default_image_url;
	}
	if ( empty( $hero_image_mobile_url ) ) {
		$hero_image_mobile_url = $default_image_url;
	}

	$cta_link = get_theme_mod( 'primefit_hero_cta_link' );
	if ( empty( $cta_link ) && function_exists( 'wc_get_page_permalink' ) ) {
		$cta_link = wc_get_page_permalink( 'shop' );
	}

	return array(
		'image_desktop' => $hero_image_desktop_url,
		'image_mobile' => $hero_image_mobile_url,
		'video_desktop' => $hero_video_desktop_url,
		'video_mobile' => $hero_video_mobile_url,
		'heading' => get_theme_mod( 'primefit_hero_heading', 'END OF SEASON SALE' ),
		'subheading' => get_theme_mod( 'primefit_hero_subheading', 'UP TO 60% OFF. LIMITED TIME ONLY. WHILE SUPPLIES LAST.' ),
		'cta_text' => get_theme_mod( 'primefit_hero_cta_text', 'SHOP NOW' ),
		'cta_link' => $cta_link,
		'overlay_position' => get_theme_mod( 'primefit_hero_text_position', 'left' ),
		'text_color' => get_theme_mod( 'primefit_hero_text_color', 'light' ),
		'video_autoplay' => get_theme_mod( 'primefit_hero_video_autoplay', true ),
		'video_loop' => get_theme_mod( 'primefit_hero_video_loop', true ),
		'video_muted' => get_theme_mod( 'primefit_hero_video_muted', true ),
	);
}

/**
 * Helper function to get training division configuration from customizer
 */
function primefit_get_training_division_config() {
	// Get background image ID and URL
	$training_image_id = get_theme_mod( 'primefit_training_division_image' );
	$training_image_url = $training_image_id ? wp_get_attachment_image_url( $training_image_id, 'full' ) : '';
	
	// Fallback to default image if no custom image is set
	if ( empty( $training_image_url ) ) {
		$training_image_url = get_template_directory_uri() . '/assets/images/training-dept.jpg';
	}

	// Get CTA links with fallbacks
	$cta_primary_link = get_theme_mod( 'primefit_training_division_cta_primary_link' );
	if ( empty( $cta_primary_link ) && function_exists( 'wc_get_page_permalink' ) ) {
		$cta_primary_link = wc_get_page_permalink( 'shop' );
	}

	$cta_secondary_link = get_theme_mod( 'primefit_training_division_cta_secondary_link' );
	if ( empty( $cta_secondary_link ) ) {
		$cta_secondary_link = '#';
	}

	return array(
		'image' => $training_image_url,
		'heading' => get_theme_mod( 'primefit_training_division_heading', 'TRAINING DIVISION' ),
		'subheading' => get_theme_mod( 'primefit_training_division_subheading', '[ FALL 2025 COLLECTION ] A PATH WITHOUT OBSTACLES LEADS NOWHERE' ),
		'cta_primary_text' => get_theme_mod( 'primefit_training_division_cta_primary_text', 'SHOP NOW' ),
		'cta_primary_link' => $cta_primary_link,
		'cta_secondary_text' => get_theme_mod( 'primefit_training_division_cta_secondary_text', 'VIEW LOOKBOOK' ),
		'cta_secondary_link' => $cta_secondary_link,
		'show_secondary_button' => get_theme_mod( 'primefit_training_division_show_secondary_button', true ),
	);
}

/**
 * Helper function to get second training division configuration from customizer
 */
function primefit_get_training_division_2_config() {
	// Get background image ID and URL
	$training_image_id = get_theme_mod( 'primefit_training_division_2_image' );
	$training_image_url = $training_image_id ? wp_get_attachment_image_url( $training_image_id, 'full' ) : '';
	
	// Fallback to default image if no custom image is set
	if ( empty( $training_image_url ) ) {
		$training_image_url = get_template_directory_uri() . '/assets/images/basketball.webp';
	}

	// Get CTA links with fallbacks
	$cta_primary_link = get_theme_mod( 'primefit_training_division_2_cta_primary_link' );
	if ( empty( $cta_primary_link ) && function_exists( 'wc_get_page_permalink' ) ) {
		$cta_primary_link = wc_get_page_permalink( 'shop' );
	}

	$cta_secondary_link = get_theme_mod( 'primefit_training_division_2_cta_secondary_link' );
	if ( empty( $cta_secondary_link ) ) {
		$cta_secondary_link = '#';
	}

	return array(
		'image' => $training_image_url,
		'heading' => get_theme_mod( 'primefit_training_division_2_heading', 'Become your best self' ),
		'subheading' => get_theme_mod( 'primefit_training_division_2_subheading', 'Unlock your potential with purpose-built gear designed for resilience, comfort, and top-tier performance.' ),
		'cta_primary_text' => get_theme_mod( 'primefit_training_division_2_cta_primary_text', 'Arise Now' ),
		'cta_primary_link' => $cta_primary_link,
		'cta_secondary_text' => get_theme_mod( 'primefit_training_division_2_cta_secondary_text', '' ),
		'cta_secondary_link' => $cta_secondary_link,
		'show_secondary_button' => get_theme_mod( 'primefit_training_division_2_show_secondary_button', false ),
	);
}

/**
 * Helper function to get mega menu configuration from customizer
 */
function primefit_get_mega_menu_config() {
	return array(
		'enabled' => get_theme_mod( 'primefit_mega_menu_enabled', true ),
		'trigger_item' => get_theme_mod( 'primefit_mega_menu_trigger_item', '' ),
		'column_1_heading' => get_theme_mod( 'primefit_mega_menu_column_1_heading', 'TOPS' ),
		'column_1_links' => get_theme_mod( 'primefit_mega_menu_column_1_links', 'Sports Bras,Tanks & Short Sleeves,Hoodies & Sweatshirts' ),
		'column_2_heading' => get_theme_mod( 'primefit_mega_menu_column_2_heading', 'BOTTOMS' ),
		'column_2_links' => get_theme_mod( 'primefit_mega_menu_column_2_links', 'Shorts,Leggings & Joggers,Pants' ),
		'column_3_heading' => get_theme_mod( 'primefit_mega_menu_column_3_heading', 'ACCESSORIES' ),
		'column_3_links' => get_theme_mod( 'primefit_mega_menu_column_3_links', 'Hats & Headwear,Bags,Socks,Jewelry & Hardware' ),
		'column_4_heading' => get_theme_mod( 'primefit_mega_menu_column_4_heading', 'DESIGNED FOR' ),
		'column_4_links' => get_theme_mod( 'primefit_mega_menu_column_4_links', 'Run,Train,Rec' ),
	);
}

/**
 * Helper function to get primary menu items for customizer dropdown
 */
function primefit_get_primary_menu_items_choices() {
	$choices = array(
		'' => __( 'Select a menu item...', 'primefit' ),
	);
	
	// Get the primary menu
	$menu_items = wp_get_nav_menu_items( get_nav_menu_locations()['primary'] ?? 0 );
	
	if ( $menu_items ) {
		foreach ( $menu_items as $item ) {
			// Only include top-level menu items (no parent)
			if ( $item->menu_item_parent == 0 ) {
				$choices[ $item->ID ] = $item->title;
			}
		}
	}
	
	return $choices;
}
