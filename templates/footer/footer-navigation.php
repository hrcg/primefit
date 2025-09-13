<?php
/**
 * ASRV Footer Navigation Template Part
 *
 * Displays the footer navigation menu
 *
 * @package ASRV_Theme
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if footer menu is assigned
if ( has_nav_menu( 'footer' ) ) : ?>
	<nav class="asrv-footer-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Footer Navigation', ASRV_THEME_TEXTDOMAIN ); ?>">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'footer',
			'menu_class'     => 'asrv-footer-menu',
			'container'      => false,
			'depth'          => 1,
			'fallback_cb'    => false,
		) );
		?>
	</nav>
<?php endif;