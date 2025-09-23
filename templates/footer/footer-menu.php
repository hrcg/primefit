<?php
/**
 * Footer Menu Component
 * 
 * Usage: get_template_part('templates/footer/footer-menu');
 */
?>

<div class="footer-content">
	<div class="footer-column">
		<h3 class="footer-heading">HELP & SUPPORT</h3>
		<?php
		wp_nav_menu([
			'theme_location' => 'footer-primary',
			'container'      => false,
			'menu_class'     => 'footer-links',
			'fallback_cb'    => false,
		]);
		?>
	</div>
	
	<div class="footer-column">
		<h3 class="footer-heading">SHOP</h3>
		<?php
		wp_nav_menu([
			'theme_location' => 'footer-secondary',
			'container'      => false,
			'menu_class'     => 'footer-links',
			'fallback_cb'    => false,
		]);
		?>
	</div>
	
	<div class="footer-column">
		<div class="connect-header">
			<h3 class="footer-heading">CONNECT</h3>
			<div class="social-icons">
				<a href="#" class="social-icon" aria-label="Instagram">
				<img src="<?php echo get_template_directory_uri(); ?>/assets/images/instagram.svg" alt="Instagram" />
				</a>
				<a href="#" class="social-icon" aria-label="TikTok">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/images/tiktok.svg" alt="TikTok" />
				</a>
			</div>
		</div>
		
		<div class="join-section">
			<a href="#" class="join-link">
				<span class="join-text">JOIN</span><br>
			<span class="join-logo">PRIMEFIT</span>
			</a>
		</div>
	</div>
</div>
