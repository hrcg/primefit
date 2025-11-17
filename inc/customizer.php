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

	// Promo Bar Enable/Disable
	$wp_customize->add_setting( 'primefit_promo_bar_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_promo_bar_enabled', array(
		'label'   => __( 'Enable Promo Bar', 'primefit' ),
		'section' => 'primefit_promo_bar',
		'type'    => 'checkbox',
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

	// Promo Bar Link
	$wp_customize->add_setting( 'primefit_promo_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_promo_link', array(
		'label'   => __( 'Promo Bar Link', 'primefit' ),
		'section' => 'primefit_promo_bar',
		'type'    => 'url',
		'description' => __( 'Optional: Add a link to make the entire promo bar clickable', 'primefit' ),
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
		'default'           => '#000',
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

	// Hero Video Poster (Desktop)
	$wp_customize->add_setting( 'primefit_hero_video_poster_desktop', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_video_poster_desktop', array(
		'label'    => __( 'Video Poster Image (Desktop)', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'image',
		'description' => __( 'Image shown while video is loading on desktop devices', 'primefit' ),
	) ) );

	// Hero Video Poster (Mobile)
	$wp_customize->add_setting( 'primefit_hero_video_poster_mobile', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_hero_video_poster_mobile', array(
		'label'    => __( 'Video Poster Image (Mobile)', 'primefit' ),
		'section'  => 'primefit_hero',
		'mime_type' => 'image',
		'description' => __( 'Image shown while video is loading on mobile devices', 'primefit' ),
	) ) );

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


	// Category Tiles Section Panel
	$wp_customize->add_section( 'primefit_category_tiles', array(
		'title'    => __( 'Category Tiles Section', 'primefit' ),
		'priority' => 37,
	) );

	// Category Tiles Enable/Disable
	$wp_customize->add_setting( 'primefit_category_tiles_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_category_tiles_enabled', array(
		'label'   => __( 'Enable Category Tiles Section', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'checkbox',
	) );

	// Tile 1 Image
	$wp_customize->add_setting( 'primefit_category_tile_1_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_category_tile_1_image', array(
		'label'    => __( 'Tile 1 Image', 'primefit' ),
		'section'  => 'primefit_category_tiles',
		'mime_type' => 'image',
	) ) );

	// Tile 1 Label
	$wp_customize->add_setting( 'primefit_category_tile_1_label', array(
		'default'           => 'RUN',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_1_label', array(
		'label'   => __( 'Tile 1 Label', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 1 Description
	$wp_customize->add_setting( 'primefit_category_tile_1_description', array(
		'default'           => 'Performance gear designed for runners who demand excellence in every stride.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_1_description', array(
		'label'   => __( 'Tile 1 Description', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'textarea',
	) );

	// Tile 1 Button Text
	$wp_customize->add_setting( 'primefit_category_tile_1_button_text', array(
		'default'           => 'Shop Run',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_1_button_text', array(
		'label'   => __( 'Tile 1 Button Text', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 1 Link
	$wp_customize->add_setting( 'primefit_category_tile_1_link', array(
		'default'           => '/designed-for/run',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_category_tile_1_link', array(
		'label'   => __( 'Tile 1 Link', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'url',
	) );

	// Tile 2 Image
	$wp_customize->add_setting( 'primefit_category_tile_2_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_category_tile_2_image', array(
		'label'    => __( 'Tile 2 Image', 'primefit' ),
		'section'  => 'primefit_category_tiles',
		'mime_type' => 'image',
	) ) );

	// Tile 2 Label
	$wp_customize->add_setting( 'primefit_category_tile_2_label', array(
		'default'           => 'TRAIN',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_2_label', array(
		'label'   => __( 'Tile 2 Label', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 2 Description
	$wp_customize->add_setting( 'primefit_category_tile_2_description', array(
		'default'           => 'Training equipment built to push your limits and maximize your potential.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_2_description', array(
		'label'   => __( 'Tile 2 Description', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'textarea',
	) );

	// Tile 2 Button Text
	$wp_customize->add_setting( 'primefit_category_tile_2_button_text', array(
		'default'           => 'Shop Train',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_2_button_text', array(
		'label'   => __( 'Tile 2 Button Text', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 2 Link
	$wp_customize->add_setting( 'primefit_category_tile_2_link', array(
		'default'           => '/designed-for/train',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_category_tile_2_link', array(
		'label'   => __( 'Tile 2 Link', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'url',
	) );

	// Tile 3 Image
	$wp_customize->add_setting( 'primefit_category_tile_3_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_category_tile_3_image', array(
		'label'    => __( 'Tile 3 Image', 'primefit' ),
		'section'  => 'primefit_category_tiles',
		'mime_type' => 'image',
	) ) );

	// Tile 3 Label
	$wp_customize->add_setting( 'primefit_category_tile_3_label', array(
		'default'           => 'REC',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_3_label', array(
		'label'   => __( 'Tile 3 Label', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 3 Description
	$wp_customize->add_setting( 'primefit_category_tile_3_description', array(
		'default'           => 'Technical, versatile gear for everyday use and recreational activities.',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_3_description', array(
		'label'   => __( 'Tile 3 Description', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'textarea',
	) );

	// Tile 3 Button Text
	$wp_customize->add_setting( 'primefit_category_tile_3_button_text', array(
		'default'           => 'Shop Rec',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_category_tile_3_button_text', array(
		'label'   => __( 'Tile 3 Button Text', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'text',
	) );

	// Tile 3 Link
	$wp_customize->add_setting( 'primefit_category_tile_3_link', array(
		'default'           => '/designed-for/rec',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_category_tile_3_link', array(
		'label'   => __( 'Tile 3 Link', 'primefit' ),
		'section' => 'primefit_category_tiles',
		'type'    => 'url',
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

	// Column 5 Heading
	$wp_customize->add_setting( 'primefit_mega_menu_column_5_heading', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_5_heading', array(
		'label'   => __( 'Column 5 Heading', 'primefit' ),
		'section' => 'primefit_mega_menu',
		'type'    => 'text',
		'description' => __( 'Leave empty to hide this column', 'primefit' ),
	) );

	// Column 5 Links
	$wp_customize->add_setting( 'primefit_mega_menu_column_5_links', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mega_menu_column_5_links', array(
		'label'       => __( 'Column 5 Links (comma-separated)', 'primefit' ),
		'section'     => 'primefit_mega_menu',
		'type'        => 'textarea',
		'description' => __( 'Enter links separated by commas', 'primefit' ),
	) );

	// Navigation Badge Section
	$wp_customize->add_section( 'primefit_navigation_badge', array(
		'title'    => __( 'Navigation Badge', 'primefit' ),
		'priority' => 41,
	) );

	// NEW Badge Enable/Disable
	$wp_customize->add_setting( 'primefit_new_badge_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_new_badge_enabled', array(
		'label'   => __( 'Enable NEW Badge', 'primefit' ),
		'section' => 'primefit_navigation_badge',
		'type'    => 'checkbox',
	) );

	// NEW Badge Text
	$wp_customize->add_setting( 'primefit_new_badge_text', array(
		'default'           => 'NEW',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_new_badge_text', array(
		'label'   => __( 'Badge Text', 'primefit' ),
		'section' => 'primefit_navigation_badge',
		'type'    => 'text',
	) );

	// NEW Badge Menu Item
	$wp_customize->add_setting( 'primefit_new_badge_menu_item', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'primefit_new_badge_menu_item', array(
		'label'       => __( 'Menu Item for NEW Badge', 'primefit' ),
		'section'     => 'primefit_navigation_badge',
		'type'        => 'select',
		'choices'     => primefit_get_primary_menu_items_choices(),
		'description' => __( 'Select which menu item should display the NEW badge.', 'primefit' ),
	) );

	// NEW Badge Background Color
	$wp_customize->add_setting( 'primefit_new_badge_bg_color', array(
		'default'           => '#ff3b30',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primefit_new_badge_bg_color', array(
		'label'   => __( 'Badge Background Color', 'primefit' ),
		'section' => 'primefit_navigation_badge',
	) ) );

	// NEW Badge Text Color
	$wp_customize->add_setting( 'primefit_new_badge_text_color', array(
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'primefit_new_badge_text_color', array(
		'label'   => __( 'Badge Text Color', 'primefit' ),
		'section' => 'primefit_navigation_badge',
	) ) );

	// Shop Category Ordering Section Panel
	$wp_customize->add_section( 'primefit_shop_categories', array(
		'title'    => __( 'Shop Categories', 'primefit' ),
		'priority' => 42,
	) );

	// Category Manual Ordering Enable/Disable
	$wp_customize->add_setting( 'primefit_category_manual_ordering', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_category_manual_ordering', array(
		'label'   => __( 'Enable Manual Category Ordering', 'primefit' ),
		'section' => 'primefit_shop_categories',
		'type'    => 'checkbox',
		'description' => __( 'Enable manual ordering of categories in the shop page. When enabled, you can drag and drop categories below to set their display order.', 'primefit' ),
	) );

	// Category Ordering
	$wp_customize->add_setting( 'primefit_category_order', array(
		'default'           => '',
		'sanitize_callback' => 'primefit_sanitize_category_order',
	) );
	$wp_customize->add_control( new PrimeFit_Category_Order_Control( $wp_customize, 'primefit_category_order', array(
		'label'       => __( 'Category Order', 'primefit' ),
		'section'     => 'primefit_shop_categories',
		'description' => __( 'Drag and drop categories to reorder them. Only enabled when "Enable Manual Category Ordering" is checked above.', 'primefit' ),
		'choices'     => primefit_get_product_categories_choices(),
	) ) );

	// Homepage Product Loops Section Panel
	$wp_customize->add_section( 'primefit_homepage_product_loops', array(
		'title'    => __( 'Homepage Product Loops', 'primefit' ),
		'priority' => 43,
	) );

	// Featured Products Section
	$wp_customize->add_setting( 'primefit_featured_products_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_enabled', array(
		'label'   => __( 'Enable Featured Products Section', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'checkbox',
	) );

	// Featured Products Category
	$wp_customize->add_setting( 'primefit_featured_products_category', array(
		'default'           => '',
		'sanitize_callback' => 'primefit_sanitize_select',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_category', array(
		'label'       => __( 'Featured Products Category', 'primefit' ),
		'section'     => 'primefit_homepage_product_loops',
		'type'        => 'select',
		'choices'     => primefit_get_product_categories_choices(),
		'description' => __( 'Leave empty to show all products, or select a specific category to filter by.', 'primefit' ),
	) );

	// Featured Products Title
	$wp_customize->add_setting( 'primefit_featured_products_title', array(
		'default'           => 'END OF SEASON SALE',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_title', array(
		'label'   => __( 'Featured Products Title', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Featured Products Button Text
	$wp_customize->add_setting( 'primefit_featured_products_button_text', array(
		'default'           => 'VIEW ALL',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_button_text', array(
		'label'   => __( 'Featured Products Button Text', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Featured Products Button Link
	$wp_customize->add_setting( 'primefit_featured_products_button_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_button_link', array(
		'label'   => __( 'Featured Products Button Link', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'url',
	) );

	// Featured Products Limit
	$wp_customize->add_setting( 'primefit_featured_products_limit', array(
		'default'           => 12,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_featured_products_limit', array(
		'label'   => __( 'Featured Products Limit', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'number',
		'input_attrs' => array(
			'min' => 1,
			'max' => 50,
		),
		'description' => __( 'Number of products to display (1-50)', 'primefit' ),
	) );

	// Product Showcase Section
	$wp_customize->add_setting( 'primefit_product_showcase_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_enabled', array(
		'label'   => __( 'Enable Product Showcase Section', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'checkbox',
	) );

	// Product Showcase Category
	$wp_customize->add_setting( 'primefit_product_showcase_category', array(
		'default'           => '',
		'sanitize_callback' => 'primefit_sanitize_select',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_category', array(
		'label'       => __( 'Product Showcase Category', 'primefit' ),
		'section'     => 'primefit_homepage_product_loops',
		'type'        => 'select',
		'choices'     => primefit_get_product_categories_choices(),
		'description' => __( 'Leave empty to show all products, or select a specific category to filter by.', 'primefit' ),
	) );

	// Product Showcase Title
	$wp_customize->add_setting( 'primefit_product_showcase_title', array(
		'default'           => 'NEW ARRIVALS',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_title', array(
		'label'   => __( 'Product Showcase Title', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Product Showcase Button Text
	$wp_customize->add_setting( 'primefit_product_showcase_button_text', array(
		'default'           => 'SHOP ALL',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_button_text', array(
		'label'   => __( 'Product Showcase Button Text', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Product Showcase Button Link
	$wp_customize->add_setting( 'primefit_product_showcase_button_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_button_link', array(
		'label'   => __( 'Product Showcase Button Link', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'url',
	) );

	// Product Showcase Limit
	$wp_customize->add_setting( 'primefit_product_showcase_limit', array(
		'default'           => 8,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
		'capability'        => 'edit_theme_options',
		'type'              => 'theme_mod',
	) );
	$wp_customize->add_control( 'primefit_product_showcase_limit', array(
		'label'   => __( 'Product Showcase Limit', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'number',
		'input_attrs' => array(
			'min' => 1,
			'max' => 50,
		),
		'description' => __( 'Number of products to display (1-50)', 'primefit' ),
	) );

	// Third Product Loop Section
	$wp_customize->add_setting( 'primefit_third_product_loop_enabled', array(
		'default'           => false,
		'sanitize_callback' => 'wp_validate_boolean',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_enabled', array(
		'label'   => __( 'Enable Third Product Loop Section', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'checkbox',
	) );

	// Third Product Loop Category
	$wp_customize->add_setting( 'primefit_third_product_loop_category', array(
		'default'           => '',
		'sanitize_callback' => 'primefit_sanitize_select',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_category', array(
		'label'       => __( 'Third Product Loop Category', 'primefit' ),
		'section'     => 'primefit_homepage_product_loops',
		'type'        => 'select',
		'choices'     => primefit_get_product_categories_choices(),
		'description' => __( 'Leave empty to show all products, or select a specific category to filter by.', 'primefit' ),
	) );

	// Third Product Loop Title
	$wp_customize->add_setting( 'primefit_third_product_loop_title', array(
		'default'           => 'FEATURED COLLECTION',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_title', array(
		'label'   => __( 'Third Product Loop Title', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Third Product Loop Button Text
	$wp_customize->add_setting( 'primefit_third_product_loop_button_text', array(
		'default'           => 'EXPLORE MORE',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_button_text', array(
		'label'   => __( 'Third Product Loop Button Text', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'text',
	) );

	// Third Product Loop Button Link
	$wp_customize->add_setting( 'primefit_third_product_loop_button_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_button_link', array(
		'label'   => __( 'Third Product Loop Button Link', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'url',
	) );

	// Third Product Loop Limit
	$wp_customize->add_setting( 'primefit_third_product_loop_limit', array(
		'default'           => 6,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'primefit_third_product_loop_limit', array(
		'label'   => __( 'Third Product Loop Limit', 'primefit' ),
		'section' => 'primefit_homepage_product_loops',
		'type'    => 'number',
		'input_attrs' => array(
			'min' => 1,
			'max' => 50,
		),
		'description' => __( 'Number of products to display (1-50)', 'primefit' ),
	) );

	// Mobile Header Tiles Section Panel
	$wp_customize->add_section( 'primefit_mobile_header_tiles', array(
		'title'    => __( 'Mobile Header Tiles', 'primefit' ),
		'priority' => 44,
	) );

	// Mobile Header Tiles Enable/Disable
	$wp_customize->add_setting( 'primefit_mobile_header_tiles_enabled', array(
		'default'           => true,
		'sanitize_callback' => 'wp_validate_boolean',
	) );
	$wp_customize->add_control( 'primefit_mobile_header_tiles_enabled', array(
		'label'   => __( 'Enable Mobile Header Tiles', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'checkbox',
		'description' => __( 'Display category tiles below the mobile menu', 'primefit' ),
	) );

	// Mobile Tile 1 Image
	$wp_customize->add_setting( 'primefit_mobile_tile_1_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_mobile_tile_1_image', array(
		'label'    => __( 'Tile 1 Image', 'primefit' ),
		'section'  => 'primefit_mobile_header_tiles',
		'mime_type' => 'image',
	) ) );

	// Mobile Tile 1 Description
	$wp_customize->add_setting( 'primefit_mobile_tile_1_description', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_1_description', array(
		'label'   => __( 'Tile 1 Description', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'textarea',
	) );

	// Mobile Tile 1 Button Text
	$wp_customize->add_setting( 'primefit_mobile_tile_1_button_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_1_button_text', array(
		'label'   => __( 'Tile 1 Button Text', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'text',
	) );

	// Mobile Tile 1 Link
	$wp_customize->add_setting( 'primefit_mobile_tile_1_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_1_link', array(
		'label'   => __( 'Tile 1 Link', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'url',
	) );

	// Mobile Tile 2 Image
	$wp_customize->add_setting( 'primefit_mobile_tile_2_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_mobile_tile_2_image', array(
		'label'    => __( 'Tile 2 Image', 'primefit' ),
		'section'  => 'primefit_mobile_header_tiles',
		'mime_type' => 'image',
	) ) );

	// Mobile Tile 2 Description
	$wp_customize->add_setting( 'primefit_mobile_tile_2_description', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_2_description', array(
		'label'   => __( 'Tile 2 Description', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'textarea',
	) );

	// Mobile Tile 2 Button Text
	$wp_customize->add_setting( 'primefit_mobile_tile_2_button_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_2_button_text', array(
		'label'   => __( 'Tile 2 Button Text', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'text',
	) );

	// Mobile Tile 2 Link
	$wp_customize->add_setting( 'primefit_mobile_tile_2_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_2_link', array(
		'label'   => __( 'Tile 2 Link', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'url',
	) );

	// Mobile Tile 3 Image
	$wp_customize->add_setting( 'primefit_mobile_tile_3_image', array(
		'default'           => '',
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'primefit_mobile_tile_3_image', array(
		'label'    => __( 'Tile 3 Image', 'primefit' ),
		'section'  => 'primefit_mobile_header_tiles',
		'mime_type' => 'image',
	) ) );

	// Mobile Tile 3 Description
	$wp_customize->add_setting( 'primefit_mobile_tile_3_description', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_3_description', array(
		'label'   => __( 'Tile 3 Description', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'textarea',
	) );

	// Mobile Tile 3 Button Text
	$wp_customize->add_setting( 'primefit_mobile_tile_3_button_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_3_button_text', array(
		'label'   => __( 'Tile 3 Button Text', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'text',
	) );

	// Mobile Tile 3 Link
	$wp_customize->add_setting( 'primefit_mobile_tile_3_link', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( 'primefit_mobile_tile_3_link', array(
		'label'   => __( 'Tile 3 Link', 'primefit' ),
		'section' => 'primefit_mobile_header_tiles',
		'type'    => 'url',
	) );
}

/**
 * Helper function to get promo bar configuration from customizer
 */
function primefit_get_promo_bar_config() {
	return array(
		'enabled' => get_theme_mod( 'primefit_promo_bar_enabled', true ),
		'text' => get_theme_mod( 'primefit_promo_text', 'END OF SEASON SALE — UP TO 60% OFF — LIMITED TIME ONLY' ),
		'link' => get_theme_mod( 'primefit_promo_link', '' ),
		'bg_color' => get_theme_mod( 'primefit_promo_bg_color', '#ff3b30' ),
		'text_color' => get_theme_mod( 'primefit_promo_text_color', '#000' ),
	);
}

/**
 * Helper function to get hero configuration from customizer
 */
function primefit_get_hero_config() {
	// Use caching for better performance
	$cache_key = 'primefit_hero_config';
	$cached_config = get_transient( $cache_key );

	if ( $cached_config !== false ) {
		return $cached_config;
	}

	// Get desktop and mobile image IDs
	$hero_image_desktop_id = get_theme_mod( 'primefit_hero_image_desktop' );
	$hero_image_mobile_id = get_theme_mod( 'primefit_hero_image_mobile' );

	// Get image URLs - use full resolution for hero images
	$hero_image_desktop_url = $hero_image_desktop_id ? wp_get_attachment_image_url( $hero_image_desktop_id, 'full' ) : '';
	$hero_image_mobile_url = $hero_image_mobile_id ? wp_get_attachment_image_url( $hero_image_mobile_id, 'full' ) : '';

	// Get video IDs and URLs
	$hero_video_desktop_id = get_theme_mod( 'primefit_hero_video_desktop' );
	$hero_video_mobile_id = get_theme_mod( 'primefit_hero_video_mobile' );

	$hero_video_desktop_url = $hero_video_desktop_id ? wp_get_attachment_url( $hero_video_desktop_id ) : '';
	$hero_video_mobile_url = $hero_video_mobile_id ? wp_get_attachment_url( $hero_video_mobile_id ) : '';

	// Get video poster IDs and URLs
	$hero_video_poster_desktop_id = get_theme_mod( 'primefit_hero_video_poster_desktop' );
	$hero_video_poster_mobile_id = get_theme_mod( 'primefit_hero_video_poster_mobile' );

	$hero_video_poster_desktop_url = $hero_video_poster_desktop_id ? wp_get_attachment_image_url( $hero_video_poster_desktop_id, 'full' ) : '';
	$hero_video_poster_mobile_url = $hero_video_poster_mobile_id ? wp_get_attachment_image_url( $hero_video_poster_mobile_id, 'full' ) : '';

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

	$config = array(
		'image_desktop' => $hero_image_desktop_url,
		'image_mobile' => $hero_image_mobile_url,
		'video_desktop' => $hero_video_desktop_url,
		'video_mobile' => $hero_video_mobile_url,
		'video_poster_desktop' => $hero_video_poster_desktop_url,
		'video_poster_mobile' => $hero_video_poster_mobile_url,
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

	// Cache for 1 hour (3600 seconds)
	set_transient( $cache_key, $config, 3600 );

	return $config;
}

/**
 * Helper function to get training division configuration from customizer
 */
function primefit_get_training_division_config() {
	// Get background image ID and URL - use full resolution
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
	$training_image_url = $training_image_id ? wp_get_attachment_image_url( $training_image_id, 'large' ) : '';
	
	// Fallback to default image if no custom image is set
	if ( empty( $training_image_url ) ) {
		$training_image_url = get_template_directory_uri() . '/assets/images/basketball.webp';
	}

	// Get CTA links with fallbacks
	$cta_primary_link = get_theme_mod( 'primefit_training_division_2_cta_primary_link' );
	if ( empty( $cta_primary_link ) && function_exists( 'wc_get_page_permalink' ) ) {
		$cta_primary_link = wc_get_page_permalink( 'shop' );
	}

	return array(
		'image' => $training_image_url,
		'heading' => get_theme_mod( 'primefit_training_division_2_heading', 'Become your best self' ),
		'subheading' => get_theme_mod( 'primefit_training_division_2_subheading', 'Unlock your potential with purpose-built gear designed for resilience, comfort, and top-tier performance.' ),
		'cta_primary_text' => get_theme_mod( 'primefit_training_division_2_cta_primary_text', 'Arise Now' ),
		'cta_primary_link' => $cta_primary_link,
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
		'column_5_heading' => get_theme_mod( 'primefit_mega_menu_column_5_heading', '' ),
		'column_5_links' => get_theme_mod( 'primefit_mega_menu_column_5_links', '' ),
	);
}

/**
 * Helper function to get category tiles configuration from customizer
 */
function primefit_get_category_tiles_config() {
	// Use caching for better performance
	$cache_key = 'primefit_category_tiles_config';
	$cached_config = get_transient( $cache_key );

	if ( $cached_config !== false ) {
		return $cached_config;
	}

	// Get image IDs and URLs for each tile
	$tile_1_image_id = get_theme_mod( 'primefit_category_tile_1_image' );
	$tile_2_image_id = get_theme_mod( 'primefit_category_tile_2_image' );
	$tile_3_image_id = get_theme_mod( 'primefit_category_tile_3_image' );

	$tile_1_image_url = $tile_1_image_id ? wp_get_attachment_image_url( $tile_1_image_id, 'large' ) : '';
	$tile_2_image_url = $tile_2_image_id ? wp_get_attachment_image_url( $tile_2_image_id, 'large' ) : '';
	$tile_3_image_url = $tile_3_image_id ? wp_get_attachment_image_url( $tile_3_image_id, 'large' ) : '';

	// Fallback to default images if no custom images are set
	$default_images = array(
		'/assets/images/run.webp',
		'/assets/images/train.webp',
		'/assets/images/rec.webp'
	);

	if ( empty( $tile_1_image_url ) ) {
		$tile_1_image_url = primefit_get_asset_uri( array( '/assets/images/run.webp', '/assets/images/run.jpg' ) );
	}
	if ( empty( $tile_2_image_url ) ) {
		$tile_2_image_url = primefit_get_asset_uri( array( '/assets/images/train.webp', '/assets/images/train.jpg' ) );
	}
	if ( empty( $tile_3_image_url ) ) {
		$tile_3_image_url = primefit_get_asset_uri( array( '/assets/images/rec.webp', '/assets/images/rec.jpg' ) );
	}

	$config = array(
		'enabled' => get_theme_mod( 'primefit_category_tiles_enabled', true ),
		'tiles' => array(
			array(
				'image' => $tile_1_image_url,
				'alt' => get_theme_mod( 'primefit_category_tile_1_label', 'RUN' ),
				'label' => get_theme_mod( 'primefit_category_tile_1_label', 'RUN' ),
				'url' => get_theme_mod( 'primefit_category_tile_1_link', '/designed-for/run' ),
				'description' => get_theme_mod( 'primefit_category_tile_1_description', 'Performance gear designed for runners who demand excellence in every stride.' ),
				'button_text' => get_theme_mod( 'primefit_category_tile_1_button_text', 'Shop Run' )
			),
			array(
				'image' => $tile_2_image_url,
				'alt' => get_theme_mod( 'primefit_category_tile_2_label', 'TRAIN' ),
				'label' => get_theme_mod( 'primefit_category_tile_2_label', 'TRAIN' ),
				'url' => get_theme_mod( 'primefit_category_tile_2_link', '/designed-for/train' ),
				'description' => get_theme_mod( 'primefit_category_tile_2_description', 'Training equipment built to push your limits and maximize your potential.' ),
				'button_text' => get_theme_mod( 'primefit_category_tile_2_button_text', 'Shop Train' )
			),
			array(
				'image' => $tile_3_image_url,
				'alt' => get_theme_mod( 'primefit_category_tile_3_label', 'REC' ),
				'label' => get_theme_mod( 'primefit_category_tile_3_label', 'REC' ),
				'url' => get_theme_mod( 'primefit_category_tile_3_link', '/designed-for/rec' ),
				'description' => get_theme_mod( 'primefit_category_tile_3_description', 'Technical, versatile gear for everyday use and recreational activities.' ),
				'button_text' => get_theme_mod( 'primefit_category_tile_3_button_text', 'Shop Rec' )
			)
		)
	);

	// Cache for 1 hour (3600 seconds)
	set_transient( $cache_key, $config, 3600 );

	return $config;
}

/**
 * Helper function to get navigation badge configuration from customizer
 */
function primefit_get_navigation_badge_config() {
	return array(
		'enabled' => get_theme_mod( 'primefit_new_badge_enabled', true ),
		'text' => get_theme_mod( 'primefit_new_badge_text', 'NEW' ),
		'menu_item_id' => get_theme_mod( 'primefit_new_badge_menu_item', '' ),
		'bg_color' => get_theme_mod( 'primefit_new_badge_bg_color', '#ff3b30' ),
		'text_color' => get_theme_mod( 'primefit_new_badge_text_color', '#ffffff' ),
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

/**
 * Helper function to get product categories for customizer dropdown
 */
function primefit_get_product_categories_choices() {
	$choices = array(
		'' => __( 'All Products', 'primefit' ),
	);

	// Check if WooCommerce is active
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $choices;
	}

	$categories = get_terms( array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );

	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		foreach ( $categories as $category ) {
			$choices[ $category->term_id ] = $category->name;
		}
	}

	return $choices;
}

/**
 * Sanitize category order input
 */
function primefit_sanitize_category_order( $input ) {
	if ( ! is_string( $input ) ) {
		return '';
	}

	// Remove any non-numeric characters except commas and dashes
	$sanitized = preg_replace( '/[^0-9,-]/', '', $input );

	return $sanitized;
}

/**
 * Get custom category order from theme options
 */
function primefit_get_category_order() {
	$manual_ordering_enabled = get_theme_mod( 'primefit_category_manual_ordering', false );
	$category_order = get_theme_mod( 'primefit_category_order', '' );

	if ( ! $manual_ordering_enabled || empty( $category_order ) ) {
		return array();
	}

	// Convert string like "1,3,5,2,4" to array
	$order_array = array_map( 'intval', explode( ',', $category_order ) );

	// Filter out any invalid category IDs
	$valid_categories = array();
	foreach ( $order_array as $category_id ) {
		if ( term_exists( $category_id, 'product_cat' ) ) {
			$valid_categories[] = $category_id;
		}
	}

	return $valid_categories;
}

/**
 * Custom sortable category order control
 */
if ( class_exists( 'WP_Customize_Control' ) ) {
	class PrimeFit_Category_Order_Control extends WP_Customize_Control {
		public $type = 'category_order';

	public function render_content() {
		$manual_ordering = get_theme_mod( 'primefit_category_manual_ordering', false );
		$current_order = get_theme_mod( 'primefit_category_order', '' );

		if ( ! $manual_ordering ) {
			echo '<p>' . esc_html__( 'Enable Manual Category Ordering above to use this control.', 'primefit' ) . '</p>';
			return;
		}

		$categories = primefit_get_product_categories_choices();
		unset( $categories[''] ); // Remove "All Products" option

		// Get current order array
		$current_order_array = ! empty( $current_order ) ? explode( ',', $current_order ) : array();

		?>
		<label>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo $this->description; ?></span>
			<?php endif; ?>
		</label>

		<div class="category-order-control">
			<ul class="category-order-list sortable-list">
				<?php if ( ! empty( $current_order_array ) ) : ?>
					<?php foreach ( $current_order_array as $category_id ) : ?>
						<?php
						$category = get_term( $category_id, 'product_cat' );
						if ( $category && ! is_wp_error( $category ) ) :
						?>
							<li class="category-order-item" data-category-id="<?php echo esc_attr( $category_id ); ?>">
								<span class="category-name"><?php echo esc_html( $category->name ); ?></span>
								<button type="button" class="remove-category" title="<?php esc_attr_e( 'Remove from order', 'primefit' ); ?>">×</button>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php
				// Show remaining categories that aren't in the current order
				foreach ( $categories as $category_id => $category_name ) :
					if ( ! in_array( $category_id, $current_order_array ) ) :
					?>
					<li class="category-order-item available-category" data-category-id="<?php echo esc_attr( $category_id ); ?>">
						<span class="category-name"><?php echo esc_html( $category_name ); ?></span>
						<button type="button" class="add-category" title="<?php esc_attr_e( 'Add to order', 'primefit' ); ?>">+</button>
					</li>
				<?php endif; endforeach; ?>
			</ul>

			<input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr( $current_order ); ?>" />
			<p class="description">
				<?php esc_html_e( 'Drag items to reorder. Use the + and × buttons to add/remove categories from your custom order.', 'primefit' ); ?>
			</p>
		</div>

		<script>
		(function($) {
			'use strict';

			$(document).ready(function() {
				// Initialize sortable functionality for this control
				var $control = $('.category-order-control').last();
				var $list = $control.find('.category-order-list');
				var $hiddenInput = $control.find('input[type="hidden"]');

				// Make the list sortable if jQuery UI is available
				if (typeof $.fn.sortable !== 'undefined') {
					$list.sortable({
						placeholder: 'category-order-item sortable-placeholder',
						axis: 'y',
						handle: '.category-name',
						update: function(event, ui) {
							updateCategoryOrder();
						}
					});
				}

				// Handle add/remove buttons
				$control.on('click', '.add-category', function(e) {
					e.preventDefault();
					var $item = $(this).closest('.category-order-item');
					$item.removeClass('available-category');
					$(this).removeClass('add-category').addClass('remove-category');
					$(this).attr('title', '<?php esc_attr_e( 'Remove from order', 'primefit' ); ?>');
					$(this).html('×');
					updateCategoryOrder();
				});

				$control.on('click', '.remove-category', function(e) {
					e.preventDefault();
					var $item = $(this).closest('.category-order-item');
					$item.addClass('available-category');
					$(this).removeClass('remove-category').addClass('add-category');
					$(this).attr('title', '<?php esc_attr_e( 'Add to order', 'primefit' ); ?>');
					$(this).html('+');
					updateCategoryOrder();
				});

				function updateCategoryOrder() {
					var order = [];

					// Get all ordered items (not available-category)
					$control.find('.category-order-item:not(.available-category)').each(function() {
						var categoryId = $(this).data('category-id');
						if (categoryId) {
							order.push(categoryId);
						}
					});

					// Update the hidden input
					$hiddenInput.val(order.join(',')).trigger('change');
				}
			});
		})(jQuery);
		</script>

		<style>
		.category-order-control {
			margin-top: 10px;
		}
		.category-order-list {
			list-style: none;
			margin: 0;
			padding: 0;
			border: 1px solid #ddd;
			border-radius: 4px;
			background: #fff;
			min-height: 200px;
			max-height: 400px;
			overflow-y: auto;
		}
		.category-order-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 8px 12px;
			border-bottom: 1px solid #eee;
			background: #f9f9f9;
			cursor: move;
			transition: background-color 0.2s;
		}
		.category-order-item:last-child {
			border-bottom: none;
		}
		.category-order-item:hover {
			background: #f0f0f0;
		}
		.category-order-item.available-category {
			background: #fff;
			opacity: 0.7;
		}
		.category-order-item.available-category:hover {
			background: #f8f8f8;
			opacity: 1;
		}
		.category-name {
			flex-grow: 1;
			font-size: 13px;
			color: #333;
		}
		.category-order-item button {
			background: none;
			border: none;
			color: #666;
			cursor: pointer;
			font-size: 16px;
			padding: 2px 6px;
			border-radius: 3px;
			transition: all 0.2s;
		}
		.category-order-item button:hover {
			background: #e0e0e0;
			color: #333;
		}
		.category-order-item .add-category {
			color: #28a745;
		}
		.category-order-item .remove-category {
			color: #dc3545;
		}
		.category-order-item.sortable-placeholder {
			background: #fff3cd;
			border: 2px dashed #ffc107;
			visibility: visible !important;
		}
		.category-order-control .description {
			margin-top: 8px;
			font-style: italic;
			color: #666;
			font-size: 12px;
		}
		.category-order-list .ui-sortable-helper {
			background: #007cba;
			color: white;
			border: none;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		}
		.category-order-list .ui-sortable-helper .category-name {
			color: white;
		}
		.category-order-list .ui-sortable-helper button {
			color: white;
		}
		.category-order-list .ui-sortable-helper button:hover {
			background: rgba(255, 255, 255, 0.2);
		}
		</style>
		<?php
	}
	}
}

/**
 * Helper function to get featured products configuration from customizer
 */
function primefit_get_featured_products_config() {
	// Get category slug from ID
	$category_id = get_theme_mod( 'primefit_featured_products_category', '' );
	$category_slug = '';

	if ( ! empty( $category_id ) ) {
		$category = get_term( $category_id, 'product_cat' );
		$category_slug = $category && ! is_wp_error( $category ) ? $category->slug : '';
	}

	// Get button link - don't apply fallback here, let the render function handle it
	$button_link = get_theme_mod( 'primefit_featured_products_button_link', '' );

	return array(
		'enabled' => get_theme_mod( 'primefit_featured_products_enabled', true ),
		'category' => $category_slug,
		'title' => get_theme_mod( 'primefit_featured_products_title', 'END OF SEASON SALE' ),
		'button_text' => get_theme_mod( 'primefit_featured_products_button_text', 'VIEW ALL' ),
		'button_link' => $button_link,
		'limit' => get_theme_mod( 'primefit_featured_products_limit', 12 ),
		'columns' => 4,
		'on_sale' => false, // Changed from true - let category filter work properly
		'show_view_all' => true,
		'section_class' => 'featured-products'
	);
}

/**
 * Helper function to get product showcase configuration from customizer
 */
function primefit_get_product_showcase_config() {
	// Get category slug from ID
	$category_id = get_theme_mod( 'primefit_product_showcase_category', '' );
	$category_slug = '';

	if ( ! empty( $category_id ) ) {
		$category = get_term( $category_id, 'product_cat' );
		$category_slug = $category && ! is_wp_error( $category ) ? $category->slug : '';
	}

	// Get button link - don't apply fallback here, let the render function handle it
	$button_link = get_theme_mod( 'primefit_product_showcase_button_link', '' );

	return array(
		'enabled' => get_theme_mod( 'primefit_product_showcase_enabled', true ),
		'category' => $category_slug,
		'title' => get_theme_mod( 'primefit_product_showcase_title', 'NEW ARRIVALS' ),
		'button_text' => get_theme_mod( 'primefit_product_showcase_button_text', 'SHOP ALL' ),
		'button_link' => $button_link,
		'limit' => get_theme_mod( 'primefit_product_showcase_limit', 8 ),
		'columns' => 4,
		'show_view_all' => true,
		'section_class' => 'product-showcase'
	);
}

/**
 * Helper function to get third product loop configuration from customizer
 */
function primefit_get_third_product_loop_config() {
	// Get category slug from ID
	$category_id = get_theme_mod( 'primefit_third_product_loop_category', '' );
	$category_slug = '';

	if ( ! empty( $category_id ) ) {
		$category = get_term( $category_id, 'product_cat' );
		$category_slug = $category && ! is_wp_error( $category ) ? $category->slug : '';
	}

	// Get button link - don't apply fallback here, let the render function handle it
	$button_link = get_theme_mod( 'primefit_third_product_loop_button_link', '' );

	return array(
		'enabled' => get_theme_mod( 'primefit_third_product_loop_enabled', false ),
		'category' => $category_slug,
		'title' => get_theme_mod( 'primefit_third_product_loop_title', 'FEATURED COLLECTION' ),
		'button_text' => get_theme_mod( 'primefit_third_product_loop_button_text', 'EXPLORE MORE' ),
		'button_link' => $button_link,
		'limit' => get_theme_mod( 'primefit_third_product_loop_limit', 6 ),
		'columns' => 3, // Different layout for variety
		'show_view_all' => true,
		'section_class' => 'third-product-loop'
	);
}

/**
 * Helper function to get mobile header tiles configuration from customizer
 */
function primefit_get_mobile_header_tiles_config() {
	// Use caching for better performance
	$cache_key = 'primefit_mobile_header_tiles_config';
	$cached_config = get_transient( $cache_key );

	if ( $cached_config !== false ) {
		return $cached_config;
	}

	// Get image IDs and URLs for each tile
	$tile_1_image_id = get_theme_mod( 'primefit_mobile_tile_1_image' );
	$tile_2_image_id = get_theme_mod( 'primefit_mobile_tile_2_image' );
	$tile_3_image_id = get_theme_mod( 'primefit_mobile_tile_3_image' );

	$tile_1_image_url = $tile_1_image_id ? wp_get_attachment_image_url( $tile_1_image_id, 'full' ) : '';
	$tile_2_image_url = $tile_2_image_id ? wp_get_attachment_image_url( $tile_2_image_id, 'full' ) : '';
	$tile_3_image_url = $tile_3_image_id ? wp_get_attachment_image_url( $tile_3_image_id, 'full' ) : '';

	$tiles = array();

	// Tile 1
	if ( ! empty( $tile_1_image_url ) ) {
		$tiles[] = array(
			'image' => $tile_1_image_url,
			'alt' => get_theme_mod( 'primefit_mobile_tile_1_description', '' ),
			'description' => get_theme_mod( 'primefit_mobile_tile_1_description', '' ),
			'button_text' => get_theme_mod( 'primefit_mobile_tile_1_button_text', '' ),
			'url' => get_theme_mod( 'primefit_mobile_tile_1_link', '' ),
		);
	}

	// Tile 2
	if ( ! empty( $tile_2_image_url ) ) {
		$tiles[] = array(
			'image' => $tile_2_image_url,
			'alt' => get_theme_mod( 'primefit_mobile_tile_2_description', '' ),
			'description' => get_theme_mod( 'primefit_mobile_tile_2_description', '' ),
			'button_text' => get_theme_mod( 'primefit_mobile_tile_2_button_text', '' ),
			'url' => get_theme_mod( 'primefit_mobile_tile_2_link', '' ),
		);
	}

	// Tile 3
	if ( ! empty( $tile_3_image_url ) ) {
		$tiles[] = array(
			'image' => $tile_3_image_url,
			'alt' => get_theme_mod( 'primefit_mobile_tile_3_description', '' ),
			'description' => get_theme_mod( 'primefit_mobile_tile_3_description', '' ),
			'button_text' => get_theme_mod( 'primefit_mobile_tile_3_button_text', '' ),
			'url' => get_theme_mod( 'primefit_mobile_tile_3_link', '' ),
		);
	}

	$config = array(
		'enabled' => get_theme_mod( 'primefit_mobile_header_tiles_enabled', true ),
		'tiles' => $tiles,
	);

	// Cache for 1 hour (3600 seconds)
	set_transient( $cache_key, $config, 3600 );

	return $config;
}

/**
 * Sanitize select/dropdown values
 */
function primefit_sanitize_select( $input, $setting ) {
	// If empty string, return it
	if ( $input === '' || $input === '0' ) {
		return $input;
	}
	
	// Otherwise sanitize as integer
	return absint( $input );
}

/**
 * Clear homepage caches when customizer settings are saved
 */
add_action( 'customize_save_after', 'primefit_clear_customizer_caches' );
function primefit_clear_customizer_caches( $wp_customize ) {
	// Clear hero config cache
	delete_transient( 'primefit_hero_config' );
	
	// Clear category tiles cache
	delete_transient( 'primefit_category_tiles_config' );
	
	// Clear mobile header tiles cache
	delete_transient( 'primefit_mobile_header_tiles_config' );
	
	// Clear any product loop caches (they use dynamic cache keys)
	// We'll use a wildcard delete approach
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_primefit_product_loop_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_primefit_product_loop_%'" );
}
