<?php
/**
 * ASRV Footer Information Template Part
 *
 * Displays additional footer information like social links, newsletter signup
 *
 * @package ASRV_Theme
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="asrv-footer-info">
	
	<!-- Social Media Links -->
	<div class="asrv-social-links">
		<h3 class="asrv-footer-heading"><?php esc_html_e( 'Follow Us', ASRV_THEME_TEXTDOMAIN ); ?></h3>
		<div class="asrv-social-icons">
			<a href="#" class="asrv-social-link" aria-label="<?php esc_attr_e( 'Follow us on Instagram', ASRV_THEME_TEXTDOMAIN ); ?>">
				<svg class="asrv-icon asrv-icon--instagram" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M12 2.163C15.204 2.163 15.584 2.175 16.85 2.233C20.102 2.381 21.621 3.924 21.769 7.152C21.827 8.417 21.838 8.797 21.838 12.001C21.838 15.206 21.826 15.585 21.769 16.85C21.62 20.075 20.105 21.621 16.85 21.769C15.584 21.827 15.206 21.839 12 21.839C8.796 21.839 8.416 21.827 7.151 21.769C3.891 21.62 2.38 20.07 2.232 16.849C2.174 15.584 2.162 15.205 2.162 12C2.162 8.796 2.175 8.417 2.232 7.151C2.381 3.924 3.896 2.38 7.151 2.232C8.417 2.175 8.796 2.163 12 2.163ZM12 0C8.741 0 8.333 0.014 7.053 0.072C2.695 0.272 0.273 2.69 0.073 7.052C0.014 8.333 0 8.741 0 12C0 15.259 0.014 15.668 0.072 16.948C0.272 21.306 2.69 23.728 7.052 23.928C8.333 23.986 8.741 24 12 24C15.259 24 15.668 23.986 16.948 23.928C21.302 23.728 23.73 21.31 23.927 16.948C23.986 15.668 24 15.259 24 12C24 8.741 23.986 8.333 23.928 7.053C23.732 2.699 21.311 0.273 16.949 0.073C15.668 0.014 15.259 0 12 0ZM12 5.838C8.597 5.838 5.838 8.597 5.838 12C5.838 15.403 8.597 18.162 12 18.162C15.403 18.162 18.162 15.403 18.162 12C18.162 8.597 15.403 5.838 12 5.838ZM12 16C9.791 16 8 14.209 8 12C8 9.791 9.791 8 12 8C14.209 8 16 9.791 16 12C16 14.209 14.209 16 12 16ZM18.406 4.155C18.406 4.955 17.761 5.6 16.961 5.6C16.161 5.6 15.516 4.955 15.516 4.155C15.516 3.355 16.161 2.71 16.961 2.71C17.761 2.71 18.406 3.355 18.406 4.155Z" fill="currentColor"/>
				</svg>
			</a>
			<a href="#" class="asrv-social-link" aria-label="<?php esc_attr_e( 'Follow us on Twitter', ASRV_THEME_TEXTDOMAIN ); ?>">
				<svg class="asrv-icon asrv-icon--twitter" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M23 3C22.0424 3.67548 20.9821 4.19211 19.86 4.53C19.2577 3.83751 18.4573 3.34669 17.567 3.12393C16.6767 2.90116 15.7395 2.95718 14.8821 3.28445C14.0247 3.61173 13.2884 4.19445 12.773 4.95371C12.2575 5.71297 11.9877 6.61234 12 7.53V8.53C10.2426 8.57557 8.50127 8.18581 6.93101 7.39624C5.36074 6.60667 4.01032 5.43666 3 4C3 4 -1 13 8 17C5.94053 18.398 3.48716 19.099 1 19C10 24 21 19 21 7.5C20.9991 7.22145 20.9723 6.94359 20.92 6.67C21.9406 5.66349 22.6608 4.39271 23 3V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
			<a href="#" class="asrv-social-link" aria-label="<?php esc_attr_e( 'Follow us on Facebook', ASRV_THEME_TEXTDOMAIN ); ?>">
				<svg class="asrv-icon asrv-icon--facebook" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M18 2H15C13.6739 2 12.4021 2.52678 11.4645 3.46447C10.5268 4.40215 10 5.67392 10 7V10H7V14H10V22H14V14H17L18 10H14V7C14 6.73478 14.1054 6.48043 14.2929 6.29289C14.4804 6.10536 14.7348 6 15 6H18V2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
		</div>
	</div>
	
	<!-- Newsletter Signup -->
	<div class="asrv-newsletter">
		<h3 class="asrv-footer-heading"><?php esc_html_e( 'Stay Updated', ASRV_THEME_TEXTDOMAIN ); ?></h3>
		<p class="asrv-newsletter-description"><?php esc_html_e( 'Subscribe to get updates on new products and exclusive offers.', ASRV_THEME_TEXTDOMAIN ); ?></p>
		<form class="asrv-newsletter-form" action="#" method="post">
			<div class="asrv-form-group">
				<input type="email" 
					   class="asrv-form-input" 
					   placeholder="<?php esc_attr_e( 'Enter your email', ASRV_THEME_TEXTDOMAIN ); ?>" 
					   required>
				<button type="submit" class="asrv-btn asrv-btn--secondary">
					<?php esc_html_e( 'Subscribe', ASRV_THEME_TEXTDOMAIN ); ?>
				</button>
			</div>
		</form>
	</div>
	
</div>