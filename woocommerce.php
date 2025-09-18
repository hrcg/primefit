<?php get_header(); ?>

<?php
// Check if this is a WooCommerce page
if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() || is_product() || is_checkout() || is_cart() || is_account_page() ) ) {
	// Only show hero on category/tag pages, not on main shop page
	if ( is_product_category() || is_product_tag() ) {
		$hero_args = primefit_get_shop_hero_config();
		
		if ( ! empty( $hero_args ) ) {
			primefit_render_hero( $hero_args );
		}
	}
	
	// Show categories grid on main shop page only (not on category or tag pages)
	if ( is_shop() ) {
		
		// Render shop categories section with subcategories included
		primefit_render_shop_categories( array(
			'title' => __( 'Shop by Category', 'primefit' ),
			'columns' => 4,
			'limit' => 20, // Increased to accommodate subcategories
			'show_count' => true,
			'hide_empty' => true,
			'parent' => null, // Changed from 0 to null to include all categories
			'include_subcategories' => true // New parameter
		) );
		
		// Don't show products on main shop page - only show categories
		// Continue to footer instead of returning early
	}
	
	// Add shop filter bar for product category/tag pages
	if ( is_product_category() || is_product_tag() ) {
		get_template_part( 'woocommerce/shop/filter-bar' );
	}
	
	// Handle checkout and cart pages - let WooCommerce handle template selection
	if ( is_checkout() || is_cart() || is_account_page() ) {
		// For checkout, let the custom template handle the layout
		if ( is_checkout() ) {
			woocommerce_content();
		} else {
			// For cart and account pages, use container
			?>
			<div class="container">
				<?php woocommerce_content(); ?>
			</div>
			<?php
		}
		return; // Exit early to prevent further processing
	}
	
	// Handle single product pages
	if ( is_product() ) {
		// Use our custom single product template
		get_template_part( 'woocommerce/single-product' );
		return; // Exit early to prevent further processing
	}
	// WooCommerce will handle the product loop for category/tag pages only
	// Only show products on category/tag pages, not on main shop page
	elseif ( is_product_category() || is_product_tag() ) {
		?>
		<div class="container">
			<div class="products-grid-wrapper">
				<?php woocommerce_content(); ?>
			</div>
		</div>
		<?php
	}
} else {
	// Regular archive page
	?>
	<div class="container">


		<?php if ( have_posts() ) : ?>
			<div class="posts-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-item' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="post-thumbnail">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'medium' ); ?>
								</a>
							</div>
						<?php endif; ?>
						
						<div class="post-content">
							<h2 class="post-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							
							<div class="post-meta">
								<span class="post-date"><?php echo get_the_date(); ?></span>
								<span class="post-author"><?php _e( 'by', 'primefit' ); ?> <?php the_author(); ?></span>
							</div>
							
							<div class="post-excerpt">
								<?php the_excerpt(); ?>
							</div>
							
							<a href="<?php the_permalink(); ?>" class="read-more">
								<?php _e( 'Read More', 'primefit' ); ?>
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			// Pagination
			the_posts_pagination( array(
				'prev_text' => __( 'Previous', 'primefit' ),
				'next_text' => __( 'Next', 'primefit' ),
			) );
			?>
		<?php else : ?>
			<p><?php _e( 'No posts found.', 'primefit' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
?>

<?php get_footer(); ?>
