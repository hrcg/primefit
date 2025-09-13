<?php get_header(); ?>

<div class="container">
	<section class="error-404 not-found">
		<header class="page-header">
			<h1 class="page-title"><?php _e( 'Oops! That page can&rsquo;t be found.', 'primefit' ); ?></h1>
		</header>

		<div class="page-content">
			<p><?php _e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'primefit' ); ?></p>

			<?php get_search_form(); ?>

			<div class="error-404-links">
				<h3><?php _e( 'Popular Pages', 'primefit' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Home', 'primefit' ); ?></a></li>
					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
						<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php _e( 'Shop', 'primefit' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( home_url( '/blog' ) ); ?>"><?php _e( 'Blog', 'primefit' ); ?></a></li>
				</ul>
			</div>

			<?php if ( function_exists( 'wc_get_products' ) ) : ?>
				<div class="error-404-products">
					<h3><?php _e( 'Featured Products', 'primefit' ); ?></h3>
					<?php
					$products = wc_get_products( array(
						'limit' => 4,
						'status' => 'publish',
						'featured' => true,
					) );

					if ( $products ) :
						echo '<div class="products-grid">';
						foreach ( $products as $product ) :
							?>
							<div class="product-item">
								<a href="<?php echo esc_url( $product->get_permalink() ); ?>">
									<?php echo $product->get_image( 'thumbnail' ); ?>
									<h4><?php echo $product->get_name(); ?></h4>
									<span class="price"><?php echo $product->get_price_html(); ?></span>
								</a>
							</div>
							<?php
						endforeach;
						echo '</div>';
					endif;
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</div>

<?php get_footer(); ?>
