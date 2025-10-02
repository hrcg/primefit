<?php
/**
 * Single Product Tabs
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.8.0
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

// Only show tabs if there's content
if ( empty( $description ) ) {
	return;
}
?>


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
