<?php
/**
 * PrimeFit Product Video (WooCommerce)
 *
 * Adds a product-level video attachment field and exposes it for frontend gallery usage.
 *
 * @package PrimeFit
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key for the product video attachment ID.
 */
const PRIMEFIT_PRODUCT_VIDEO_META_KEY = '_primefit_product_video_id';

/**
 * Meta key for the product video position in gallery (1-based index).
 * Example: 1 = first item, 2 = second item, etc.
 */
const PRIMEFIT_PRODUCT_VIDEO_POSITION_META_KEY = '_primefit_product_video_position';

/**
 * Meta key for the product video thumbnail attachment ID (image).
 */
const PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY = '_primefit_product_video_thumb_id';

/**
 * Default maximum position for video in gallery (when product has no images yet).
 */
const PRIMEFIT_PRODUCT_VIDEO_DEFAULT_MAX_POSITION = 12;

/**
 * Register product video metabox.
 */
add_action( 'add_meta_boxes', 'primefit_register_product_video_metabox' );
function primefit_register_product_video_metabox() {
	add_meta_box(
		'primefit_product_video',
		__( 'Product Video', 'primefit' ),
		'primefit_render_product_video_metabox',
		'product',
		'side',
		'default'
	);
}

/**
 * Render the product video metabox.
 *
 * @param WP_Post $post Current post object.
 */
function primefit_render_product_video_metabox( $post ) {
	$video_id  = (int) get_post_meta( $post->ID, PRIMEFIT_PRODUCT_VIDEO_META_KEY, true );
	$video_url = $video_id ? wp_get_attachment_url( $video_id ) : '';
	$thumb_id  = (int) get_post_meta( $post->ID, PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY, true );
	$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
	$position  = (int) get_post_meta( $post->ID, PRIMEFIT_PRODUCT_VIDEO_POSITION_META_KEY, true );
	if ( $position < 1 ) {
		$position = 1;
	}

	// Estimate max positions based on current product images (main + gallery).
	$max_position = PRIMEFIT_PRODUCT_VIDEO_DEFAULT_MAX_POSITION;
	if ( class_exists( 'WooCommerce' ) ) {
		$product = wc_get_product( $post->ID );
		if ( $product ) {
			$ids = $product->get_gallery_image_ids();
			$main_id = $product->get_image_id();
			if ( $main_id ) {
				array_unshift( $ids, $main_id );
			}
			$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
			$max_position = max( 1, count( $ids ) + 1 ); // +1 allows placing after the last image
		}
	}

	if ( $position > $max_position ) {
		$position = $max_position;
	}

	wp_nonce_field( 'primefit_save_product_video', 'primefit_product_video_nonce' );
	?>
	<div class="primefit-product-video-field">
		<input type="hidden" name="primefit_product_video_id" value="<?php echo esc_attr( $video_id ); ?>" data-primefit-product-video-id />
		<input type="hidden" name="primefit_product_video_thumb_id" value="<?php echo esc_attr( $thumb_id ); ?>" data-primefit-product-video-thumb-id />

		<div class="primefit-product-video-preview" data-primefit-product-video-preview>
			<?php if ( $video_url ) : ?>
				<video controls preload="metadata" style="max-width:100%; height:auto;">
					<source src="<?php echo esc_url( $video_url ); ?>" />
				</video>
				<p style="margin: 8px 0 0;">
					<a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( 'Open video', 'primefit' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p style="margin: 0;">
					<?php echo esc_html__( 'No video selected.', 'primefit' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<p style="margin-top: 10px;">
			<button type="button" class="button button-secondary" data-primefit-product-video-select>
				<?php echo esc_html__( 'Select/Upload Video', 'primefit' ); ?>
			</button>
			<button type="button" class="button button-link-delete" data-primefit-product-video-remove <?php echo $video_id ? '' : 'style="display:none;"'; ?>>
				<?php echo esc_html__( 'Remove', 'primefit' ); ?>
			</button>
		</p>

		<p style="margin-top: 10px;">
			<strong style="display:block; margin-bottom: 6px;">
				<?php echo esc_html__( 'Video thumbnail', 'primefit' ); ?>
			</strong>
			<div data-primefit-product-video-thumb-preview>
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="max-width:100%; height:auto; display:block;" />
				<?php else : ?>
					<p style="margin: 0;"><?php echo esc_html__( 'No thumbnail selected.', 'primefit' ); ?></p>
				<?php endif; ?>
			</div>
			<p style="margin-top: 8px;">
				<button type="button" class="button button-secondary" data-primefit-product-video-thumb-select>
					<?php echo esc_html__( 'Select/Upload Thumbnail', 'primefit' ); ?>
				</button>
				<button type="button" class="button button-link-delete" data-primefit-product-video-thumb-remove <?php echo $thumb_id ? '' : 'style="display:none;"'; ?>>
					<?php echo esc_html__( 'Remove thumbnail', 'primefit' ); ?>
				</button>
			</p>
		</p>

		<p style="margin-top: 10px;">
			<label for="primefit_product_video_position" style="display:block; font-weight:600; margin-bottom: 4px;">
				<?php echo esc_html__( 'Video position in gallery', 'primefit' ); ?>
			</label>
			<select name="primefit_product_video_position" id="primefit_product_video_position" class="widefat">
				<?php for ( $i = 1; $i <= $max_position; $i++ ) : ?>
					<?php
					$label = sprintf( '%d', $i );
					if ( 1 === $i ) {
						$label .= ' — ' . esc_html__( 'First', 'primefit' );
					} elseif ( 2 === $i ) {
						$label .= ' — ' . esc_html__( 'Second', 'primefit' );
					} elseif ( 3 === $i ) {
						$label .= ' — ' . esc_html__( 'Third', 'primefit' );
					} elseif ( $i === $max_position ) {
						$label .= ' — ' . esc_html__( 'Last', 'primefit' );
					}
					?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $position, $i ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</p>

		<p class="description" style="margin-top: 6px;">
			<?php echo esc_html__( 'This video will appear in the product gallery on the product page.', 'primefit' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Enqueue admin assets (media uploader) for product edit screens.
 */
add_action( 'admin_enqueue_scripts', 'primefit_enqueue_product_video_admin_assets' );
function primefit_enqueue_product_video_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || empty( $screen->post_type ) || 'product' !== $screen->post_type ) {
		return;
	}

	// Media uploader framework.
	wp_enqueue_media();

	$script_rel = '/assets/js/admin-product-video.js';
	wp_enqueue_script(
		'primefit-admin-product-video',
		PRIMEFIT_THEME_URI . $script_rel,
		array( 'jquery' ),
		function_exists( 'primefit_get_file_version' ) ? primefit_get_file_version( $script_rel ) : PRIMEFIT_VERSION,
		true
	);
}

/**
 * Save product video meta.
 */
add_action( 'save_post_product', 'primefit_save_product_video_meta' );
function primefit_save_product_video_meta( $post_id ) {
	// Autosave / revisions
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Capability check
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Nonce check
	$nonce = $_POST['primefit_product_video_nonce'] ?? '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'primefit_save_product_video' ) ) {
		return;
	}

	$video_id = isset( $_POST['primefit_product_video_id'] ) ? (int) $_POST['primefit_product_video_id'] : 0;
	if ( $video_id > 0 ) {
		update_post_meta( $post_id, PRIMEFIT_PRODUCT_VIDEO_META_KEY, $video_id );
	} else {
		delete_post_meta( $post_id, PRIMEFIT_PRODUCT_VIDEO_META_KEY );
	}

	$position = isset( $_POST['primefit_product_video_position'] ) ? (int) $_POST['primefit_product_video_position'] : 1;
	if ( $position < 1 ) {
		$position = 1;
	}
	update_post_meta( $post_id, PRIMEFIT_PRODUCT_VIDEO_POSITION_META_KEY, $position );

	$thumb_id = isset( $_POST['primefit_product_video_thumb_id'] ) ? (int) $_POST['primefit_product_video_thumb_id'] : 0;
	if ( $thumb_id > 0 ) {
		update_post_meta( $post_id, PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY, $thumb_id );
	} else {
		delete_post_meta( $post_id, PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY );
	}

	// Invalidate all related caches
	// 1. Gallery data cache
	wp_cache_delete( "product_data_{$post_id}_gallery", 'primefit_product_data' );
	
	// 2. Clear cached attachment URLs for video and thumbnail (if they changed)
	if ( $video_id > 0 ) {
		wp_cache_delete( "{$video_id}_large", 'primefit_attachment_urls' );
		wp_cache_delete( "{$video_id}_thumbnail", 'primefit_attachment_urls' );
		wp_cache_delete( "{$video_id}__wp_attachment_image_alt", 'primefit_attachment_meta' );
	}
	if ( $thumb_id > 0 ) {
		wp_cache_delete( "{$thumb_id}_large", 'primefit_attachment_urls' );
		wp_cache_delete( "{$thumb_id}_thumbnail", 'primefit_attachment_urls' );
		wp_cache_delete( "{$thumb_id}__wp_attachment_image_alt", 'primefit_attachment_meta' );
	}
}

