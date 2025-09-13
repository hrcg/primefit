<?php get_header(); ?>

<?php
// Check if this is a WooCommerce page
if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
	// Get shop hero configuration
	$hero_args = primefit_get_shop_hero_config();
	
	if ( ! empty( $hero_args ) ) {
		primefit_render_hero( $hero_args );
	}
	
	// Add shop filter bar
	get_template_part( 'templates/parts/woocommerce/shop-filter-bar' );
	
	// WooCommerce will handle the product loop
	?>
	<div class="container">
		<?php woocommerce_content(); ?>
	</div>
	<?php
} else {
	// Regular archive page
	?>
	<div class="container">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				if ( is_category() ) {
					single_cat_title();
				} elseif ( is_tag() ) {
					single_tag_title();
				} elseif ( is_author() ) {
					printf( __( 'Author: %s', 'primefit' ), get_the_author() );
				} elseif ( is_date() ) {
					if ( is_year() ) {
						printf( __( 'Year: %s', 'primefit' ), get_the_date( 'Y' ) );
					} elseif ( is_month() ) {
						printf( __( 'Month: %s', 'primefit' ), get_the_date( 'F Y' ) );
					} elseif ( is_day() ) {
						printf( __( 'Day: %s', 'primefit' ), get_the_date() );
					}
				} else {
					_e( 'Archives', 'primefit' );
				}
				?>
			</h1>
		</header>

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
