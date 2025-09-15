<?php
/**
 * Product Information Template
 *
 * @package PrimeFit
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

// Get custom product information using ACF with fallback to legacy meta
$description = primefit_get_product_description( $product->get_id() );
$designed_for = primefit_get_product_field( 'designed_for', $product->get_id(), 'primefit_designed_for' );
$fabric_technology = primefit_get_product_field( 'fabric_technology', $product->get_id(), 'primefit_fabric_technology' );
?>

<div class="product-information-container">
	<!-- Description Section -->
	<?php if ( ! empty( $description ) ) : ?>
		<div class="information-section">
			<button class="information-toggle" data-target="description">
				<span class="section-title"><?php esc_html_e( 'DESCRIPTION', 'primefit' ); ?></span>
				<span class="toggle-icon">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
			</button>
			<div class="information-content" id="description">
				<div class="content-inner">
					<?php echo wp_kses_post( wpautop( $description ) ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
	
	<!-- Designed For Section -->
	<?php if ( ! empty( $designed_for ) ) : ?>
		<div class="information-section">
			<button class="information-toggle" data-target="designed-for">
				<span class="section-title"><?php esc_html_e( 'DESIGNED FOR', 'primefit' ); ?></span>
				<span class="toggle-icon">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
			</button>
			<div class="information-content" id="designed-for">
				<div class="content-inner">
					<?php echo wp_kses_post( wpautop( $designed_for ) ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
	
	<!-- Fabric + Technology Section -->
	<?php if ( ! empty( $fabric_technology ) ) : ?>
		<div class="information-section">
			<button class="information-toggle" data-target="fabric-technology">
				<span class="section-title"><?php esc_html_e( 'FABRIC + TECHNOLOGY', 'primefit' ); ?></span>
				<span class="toggle-icon">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
			</button>
			<div class="information-content" id="fabric-technology">
				<div class="content-inner">
					<?php echo wp_kses_post( wpautop( $fabric_technology ) ); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const toggles = document.querySelectorAll('.information-toggle');
	
	toggles.forEach(toggle => {
		toggle.addEventListener('click', function() {
			const targetId = this.dataset.target;
			const content = document.getElementById(targetId);
			const icon = this.querySelector('.toggle-icon svg');
			
			if (!content) return;
			
			const isOpen = content.classList.contains('open');
			
			if (isOpen) {
				// Close
				content.classList.remove('open');
				icon.style.transform = 'rotate(0deg)';
				this.setAttribute('aria-expanded', 'false');
			} else {
				// Open
				content.classList.add('open');
				icon.style.transform = 'rotate(180deg)';
				this.setAttribute('aria-expanded', 'true');
			}
		});
		
		// Set initial ARIA attributes
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('role', 'button');
		toggle.setAttribute('tabindex', '0');
		
		// Keyboard support
		toggle.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				this.click();
			}
		});
	});
});
</script>
