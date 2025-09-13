<?php
/**
 * Brand Logo Component
 * 
 * Usage: get_template_part('parts/header/brand-logo');
 */

$logo_white = primefit_get_asset_uri( [ '/assets/images/logo-white.webp', '/assets/images/logo-white.png' ] );
$logo_black = primefit_get_asset_uri( [ '/assets/images/logo-black.webp', '/assets/images/logo-black.png' ] );
?>

<div class="brand">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo" aria-label="<?php bloginfo( 'name' ); ?>">
		<?php if ( $logo_white ) : ?>
			<img class="logo-img logo-white" src="<?php echo esc_url( $logo_white ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="150" height="auto" />
		<?php endif; ?>
		<?php if ( $logo_black ) : ?>
			<img class="logo-img logo-black" src="<?php echo esc_url( $logo_black ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="150" height="auto" />
		<?php endif; ?>
	</a>
</div>
