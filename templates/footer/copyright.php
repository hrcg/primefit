<?php
/**
 * Copyright Component
 * 
 * Usage: get_template_part('parts/footer/copyright', null, $args);
 * 
 * Expected $args:
 * - 'text' => custom copyright text (optional)
 */

$defaults = array(
	'text' => sprintf( '© %s %s', date_i18n( 'Y' ), get_bloginfo( 'name' ) )
);

$copyright = wp_parse_args($args ?? array(), $defaults);

// Allow customization via WordPress Customizer
$copyright_text = get_theme_mod('primefit_copyright_text', $copyright['text']);
?>

<div class="footer-bottom">
	<div class="footer-symbol-container">
		<img src="<?php echo get_template_directory_uri(); ?>/assets/images/symbol.webp" alt="PrimeFit Symbol" class="footer-symbol" loading="lazy" />
		<p>COPYRIGHT © 2025</p>
		<p>PRIMEFIT BETTER THAN YESTERDAY</p>
	</div>
	<div class="site-built-with">
		<a href="https://swissdigital.io" target="_blank" rel="noopener noreferrer" class="site-built-link">
			<span>SITE BUILT BY</span>
			<img src="<?php echo get_template_directory_uri(); ?>/assets/images/swiss-vector.svg" alt="Swiss Vector" class="swiss-vector-icon" loading="lazy" />
		</a>
	</div>
</div>
