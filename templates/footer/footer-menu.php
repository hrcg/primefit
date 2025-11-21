<?php
/**
 * Footer Menu Component
 * 
 * Usage: get_template_part('templates/footer/footer-menu');
 */
?>

<div class="footer-content">
	<div class="footer-column">
		<h3 class="footer-heading">
			<span class="footer-heading-text">HELP & SUPPORT</span>
			<button class="footer-toggle" aria-label="Toggle Help & Support menu" aria-expanded="false">
				<span class="toggle-icon">+</span>
			</button>
		</h3>
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
		<h3 class="footer-heading">
			<span class="footer-heading-text">SHOP</span>
			<button class="footer-toggle" aria-label="Toggle Shop menu" aria-expanded="false">
				<span class="toggle-icon">+</span>
			</button>
		</h3>
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
		<h3 class="footer-heading">
			<span class="footer-heading-text">Collections</span>
			<button class="footer-toggle" aria-label="Toggle Collections menu" aria-expanded="false">
				<span class="toggle-icon">+</span>
			</button>
		</h3>
		<?php
		wp_nav_menu([
			'theme_location' => 'footer-tertiary',
			'container'      => false,
			'menu_class'     => 'footer-links',
			'fallback_cb'    => false,
		]);
		?>
	</div>
		
	<div class="footer-column">
		<h3 class="footer-heading">
			<span class="footer-heading-text">Location</span>
			<button class="footer-toggle" aria-label="Toggle Location menu" aria-expanded="false">
				<span class="toggle-icon">+</span>
			</button>
		</h3>
		<?php
		wp_nav_menu([
			'theme_location' => 'footer-fourth',
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
				<a href="https://www.instagram.com/primefit.eu/" class="social-icon" aria-label="Instagram">
				<img src="<?php echo get_template_directory_uri(); ?>/assets/images/instagram.svg" alt="Instagram" loading="lazy" />
				</a>
				<a href="https://www.tiktok.com/@primefit.eu" class="social-icon" aria-label="TikTok">
					<img src="<?php echo get_template_directory_uri(); ?>/assets/images/tiktok.svg" alt="TikTok" loading="lazy" />
				</a>
			</div>
		</div>
	</div>
</div>
