<?php get_header(); ?>

<div class="container">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				
				<div class="entry-meta">
					<span class="posted-on">
						<?php echo get_the_date(); ?>
					</span>
					<span class="byline">
						<?php _e( 'by', 'primefit' ); ?> 
						<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
							<?php the_author(); ?>
						</a>
					</span>
					<?php if ( has_category() ) : ?>
						<span class="cat-links">
							<?php _e( 'in', 'primefit' ); ?> <?php the_category( ', ' ); ?>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="entry-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages( array(
					'before' => '<div class="page-links">',
					'after'  => '</div>',
				) );
				?>
			</div>

			<footer class="entry-footer">
				<?php if ( has_tag() ) : ?>
					<div class="tag-links">
						<?php the_tags( __( 'Tags: ', 'primefit' ), ', ', '' ); ?>
					</div>
				<?php endif; ?>
			</footer>
		</article>

		<?php
		// If comments are open or we have at least one comment, load up the comment template.
		if ( comments_open() || get_comments_number() ) :
			comments_template();
		endif;
		?>

		<?php
		// Post navigation
		the_post_navigation( array(
			'prev_text' => '<span class="nav-subtitle">' . __( 'Previous:', 'primefit' ) . '</span> <span class="nav-title">%title</span>',
			'next_text' => '<span class="nav-subtitle">' . __( 'Next:', 'primefit' ) . '</span> <span class="nav-title">%title</span>',
		) );
		?>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
