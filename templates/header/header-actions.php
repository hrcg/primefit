<?php
/**
 * Header Actions Component (Cart, Account, Search, etc.)
 * 
 * Usage: get_template_part('templates/header/header-actions');
 */
?>

<div class="header-actions header-actions--mobile">
	<a class="locations-link" href="/locations">LOCATIONS</a>
	<a class="account-link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">ACCOUNT</a>
	<a class="search-link" href="<?php echo esc_url( home_url( '/' ) ); ?>?s=">SEARCH</a>
	
	<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
		<?php get_template_part( 'templates/header/mini-cart' ); ?>
	<?php endif; ?>
</div>
