<?php
/**
 * Header template
 * 
 * Displays the site header with responsive navigation and mobile-optimized scroll behavior
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate header classes based on current context
 *
 * @return string Space-separated CSS classes
 */
function primefit_get_header_classes() {
	$classes = [ 'site-header' ];
	
	// Add context-specific classes
	if ( is_front_page() ) {
		$classes[] = 'site-header--home';
	}
	
	if ( is_shop() || is_product_category() || is_product_tag() ) {
		$classes[] = 'site-header--shop';
	}
	
	if ( is_single() && 'product' === get_post_type() ) {
		$classes[] = 'site-header--product';
	}
	
	/**
	 * Filter header CSS classes
	 *
	 * @param array $classes Array of CSS classes
	 */
	$classes = apply_filters( 'primefit_header_classes', $classes );
	
	return implode( ' ', $classes );
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="dark light">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php 
/**
 * Hook: primefit_before_header
 *
 * @since 1.0.0
 */
do_action( 'primefit_before_header' );

// Display promo bar
get_template_part( 'parts/header/promo-bar' );
?>

<header class="<?php echo esc_attr( primefit_get_header_classes() ); ?>" role="banner">
	<div class="container">
		<div class="header-bar">
			<button 
				class="menu-toggle" 
				aria-controls="primary-menu" 
				aria-expanded="false" 
				aria-label="<?php esc_attr_e( 'Open navigation menu', 'primefit' ); ?>"
				data-mobile-menu-toggle
			>
				<span class="hamburger" aria-hidden="true"></span>
			</button>
			
			<?php get_template_part( 'parts/header/primary-navigation' ); ?>
			<?php get_template_part( 'parts/header/brand-logo' ); ?>
			
			<div class="header-icons">
				<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
					<?php get_template_part( 'parts/header/mini-cart' ); ?>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="mobile-nav-overlay" hidden aria-hidden="true"></div>
	</div>
</header>

<?php
/**
 * Hook: primefit_after_header
 *
 * @since 1.0.0
 */
do_action( 'primefit_after_header' );
?>

<main id="content" class="site-content" role="main">

