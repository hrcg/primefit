<?php get_header(); ?>

<div class="container" style="padding: 0; margin-top: 60px;">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>


			<div class="page-content">
				<?php
				the_content();

				wp_link_pages( array(
					'before' => '<div class="page-links">',
					'after'  => '</div>',
				) );
				?>
			</div>

			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>
		</article>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
