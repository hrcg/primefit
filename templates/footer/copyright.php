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
	<p>COPYRIGHT © 2025 - PRIMEFIT ATHLETICS</p>
</div>
