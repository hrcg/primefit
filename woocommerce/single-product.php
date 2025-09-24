<?php
/**
 * The template for displaying single product pages
 *
 * @package PrimeFit
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure we have a proper WC_Product instance
if ( ! ( $product instanceof WC_Product ) ) {
	$product = wc_get_product( get_the_ID() );
}

get_header( 'shop' );
?>

<div class="single-product-page">
	<div class="product-container">
		
		<?php
		/**
		 * Hook: woocommerce_before_single_product.
		 *
		 * @hooked woocommerce_output_all_notices - 10
		 */
		do_action( 'woocommerce_before_single_product' );

		if ( post_password_required() ) {
			echo get_the_password_form(); // WPCS: XSS ok.
			return;
		}
		?>

		<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>
			<div class="product-layout">
				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary.
				 *
				 * @hooked woocommerce_show_product_images - 20
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>

				<div class="summary entry-summary">
					<?php
					/**
					 * Hook: woocommerce_single_product_summary.
					 *
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 * @hooked WC_Structured_Data::generate_product_data() - 60
					 */
					do_action( 'woocommerce_single_product_summary' );
					?>
				</div>
			</div>

			<?php
			/**
			 * Hook: woocommerce_after_single_product_summary.
			 *
			 * @hooked woocommerce_output_product_data_tabs - 10
			 * @hooked woocommerce_upsell_display - 15
			 * @hooked woocommerce_output_related_products - 20
			 */
			do_action( 'woocommerce_after_single_product_summary' );
			?>
		</div>

		<?php do_action( 'woocommerce_after_single_product' ); ?>
	</div>
</div>

<!-- Size Guide Modal -->
<div id="size-guide-modal" class="size-guide-modal" style="display: none;">
	<div class="size-guide-modal-overlay"></div>
	<div class="size-guide-modal-content">
		<div class="size-guide-modal-header">
			<h3 class="size-guide-modal-title"><?php esc_html_e( 'Size Guide', 'primefit' ); ?></h3>
			<button class="size-guide-modal-close" type="button" aria-label="<?php esc_attr_e( 'Close size guide', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
		<div class="size-guide-modal-body">
			<img id="size-guide-modal-image" src="" alt="<?php esc_attr_e( 'Size Guide', 'primefit' ); ?>" class="size-guide-image">
		</div>
	</div>
</div>

<?php get_footer( 'shop' ); ?>
