<?php get_header(); ?>

<div class="container">
	<header class="page-header">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: search query. */
				esc_html__( 'Search Results for: %s', 'primefit' ),
				'<span>' . get_search_query() . '</span>'
			);
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="search-results">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result-item' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="search-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'thumbnail' ); ?>
							</a>
						</div>
					<?php endif; ?>
					
					<div class="search-content">
						<h2 class="search-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						
						<div class="search-meta">
							<span class="search-type"><?php echo get_post_type(); ?></span>
							<span class="search-date"><?php echo get_the_date(); ?></span>
						</div>
						
						<div class="search-excerpt">
							<?php the_excerpt(); ?>
						</div>
						
						<a href="<?php the_permalink(); ?>" class="search-link">
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
		<div class="no-results">
			<h2><?php _e( 'Nothing Found', 'primefit' ); ?></h2>
			<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'primefit' ); ?></p>
			
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
