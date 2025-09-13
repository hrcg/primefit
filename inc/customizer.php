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
}

/**
 * Helper function to get hero configuration from customizer
 */
function primefit_get_hero_config() {
	$hero_image_id = get_theme_mod( 'primefit_hero_image' );
	$hero_image_url = $hero_image_id ? wp_get_attachment_image_url( $hero_image_id, 'full' ) : '';
	
	// Fallback to default image if no custom image is set
	if ( empty( $hero_image_url ) ) {
		$hero_image_url = primefit_get_asset_uri( array( '/assets/images/hero-image.webp', '/assets/images/hero-image.jpg' ) );
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
