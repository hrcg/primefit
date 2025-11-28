<?php
/**
 * My Account Gift Cards Template
 *
 * Displays the customer's PW Gift Cards balance inside a dedicated
 * account tab, consistent with other PrimeFit account templates.
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="account-container">
	<div class="account-layout">
		<div class="account-content-section">
			<div class="account-content">
				<div class="dashboard-header">
					<div class="dashboard-navigation">
						<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="back-to-dashboard">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M19 12H5"></path>
								<polyline points="12,19 5,12 12,5"></polyline>
							</svg>
							<?php esc_html_e( 'Back to Orders', 'primefit' ); ?>
						</a>
					</div>
				</div>

				<div class="dashboard-cards">
					<div class="dashboard-card">
						<div class="card-icon">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
								<path d="M3 10h18"></path>
								<path d="M8 15h.01"></path>
								<path d="M12 15h4"></path>
							</svg>
						</div>
						<h3 class="card-title"><?php esc_html_e( 'Gift Card Balance', 'primefit' ); ?></h3>
						<p class="card-description">
							<?php esc_html_e( 'View your available balance and manage your PW Gift Cards.', 'primefit' ); ?>
						</p>

						<div class="gift-card-balance">
							<?php
							// Render PW Gift Cards balance shortcode.
							echo do_shortcode( '[pw_gift_cards_balance]' );
							?>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>




