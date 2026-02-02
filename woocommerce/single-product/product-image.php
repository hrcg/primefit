<?php
/**
 * Single Product Image
 *
 * @package PrimeFit
 * @since 1.0.0
 * @version 9.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product ) {
	return;
}

$product_id = $product->get_id();

// Get the current selected color from POST data or default
$selected_color = '';
if ( isset( $_POST['attribute_pa_color'] ) ) {
	$selected_color = sanitize_text_field( $_POST['attribute_pa_color'] ?? '' );
} elseif ( isset( $_GET['color'] ) ) {
	$selected_color = sanitize_text_field( $_GET['color'] ?? '' );
}

// Try to get cached gallery data first
$cached_gallery = primefit_get_cached_product_data( $product_id . '_gallery' );
if ( false !== $cached_gallery ) {
	// Validate and set defaults for all expected keys
	$attachment_ids = isset( $cached_gallery['attachment_ids'] ) && is_array( $cached_gallery['attachment_ids'] ) ? $cached_gallery['attachment_ids'] : array();
	$variation_galleries = isset( $cached_gallery['variation_galleries'] ) && is_array( $cached_gallery['variation_galleries'] ) ? $cached_gallery['variation_galleries'] : array();
	$image_urls = isset( $cached_gallery['image_urls'] ) && is_array( $cached_gallery['image_urls'] ) ? $cached_gallery['image_urls'] : array();
	$thumb_urls = isset( $cached_gallery['thumb_urls'] ) && is_array( $cached_gallery['thumb_urls'] ) ? $cached_gallery['thumb_urls'] : array();
	
	$video = isset( $cached_gallery['video'] ) && is_array( $cached_gallery['video'] ) ? $cached_gallery['video'] : array(
		'id'        => 0,
		'url'       => '',
		'thumb_id'  => 0,
		'thumb_url' => '',
	);
	
	$video_position = isset( $cached_gallery['video_position'] ) ? (int) $cached_gallery['video_position'] : 1;
	if ( $video_position < 1 ) {
		$video_position = 1;
	}
} else {
	// Cache miss - get data and cache it
	// Always start with the default product gallery
	$attachment_ids = $product->get_gallery_image_ids();
	$main_image_id = $product->get_image_id();

	// Add main image to the beginning of gallery
	if ( $main_image_id ) {
		array_unshift( $attachment_ids, $main_image_id );
	}

	// Remove duplicates
	$attachment_ids = array_unique( $attachment_ids );

	// Optional: product gallery video (attachment)
	$video_id = defined( 'PRIMEFIT_PRODUCT_VIDEO_META_KEY' ) ? (int) get_post_meta( $product_id, PRIMEFIT_PRODUCT_VIDEO_META_KEY, true ) : 0;
	$video_thumb_id = defined( 'PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY' ) ? (int) get_post_meta( $product_id, PRIMEFIT_PRODUCT_VIDEO_THUMB_META_KEY, true ) : 0;
	$video_position = defined( 'PRIMEFIT_PRODUCT_VIDEO_POSITION_META_KEY' ) ? (int) get_post_meta( $product_id, PRIMEFIT_PRODUCT_VIDEO_POSITION_META_KEY, true ) : 1;
	if ( $video_position < 1 ) {
		$video_position = 1;
	}
	$video    = array(
		'id'        => $video_id,
		'url'       => '',
		'thumb_id'  => $video_thumb_id,
		'thumb_url' => '',
	);
	if ( $video_id > 0 ) {
		$mime = get_post_mime_type( $video_id );
		if ( $mime && strpos( $mime, 'video/' ) === 0 ) {
			$video_url = wp_get_attachment_url( $video_id );
			if ( $video_url ) {
				$video['url'] = $video_url;
				$video_thumb  = $video_thumb_id ? wp_get_attachment_image_url( $video_thumb_id, 'thumbnail' ) : '';
				if ( ! $video_thumb ) {
					$video_thumb = wp_get_attachment_image_url( $video_id, 'thumbnail' );
				}
				if ( ! $video_thumb && ! empty( $main_image_id ) ) {
					$video_thumb = wp_get_attachment_image_url( $main_image_id, 'thumbnail' );
				}
				$video['thumb_url'] = $video_thumb ? $video_thumb : '';
			}
		}
	}

	// Allow products with video-only (no images) or images-only (no video)
	if ( empty( $attachment_ids ) && empty( $video['url'] ) ) {
		return;
	}

	// Get variation galleries from ACF field
	$variation_galleries = primefit_get_variation_gallery_data( $product_id );

	// Generate URLs for all images with caching
	$image_urls = array();
	$thumb_urls = array();
	foreach ( $attachment_ids as $attachment_id ) {
		if ( $attachment_id ) {
			// Try to get cached URLs first
			$large_url = primefit_get_cached_attachment_image_url( $attachment_id, 'large' );
			if ( false === $large_url ) {
				$large_url = wp_get_attachment_image_url( $attachment_id, 'large' );
				primefit_cache_attachment_image_url( $attachment_id, 'large', $large_url );
			}
			$image_urls[ $attachment_id ] = $large_url;
			
			$thumb_url = primefit_get_cached_attachment_image_url( $attachment_id, 'thumbnail' );
			if ( false === $thumb_url ) {
				$thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
				primefit_cache_attachment_image_url( $attachment_id, 'thumbnail', $thumb_url );
			}
			$thumb_urls[ $attachment_id ] = $thumb_url;
		}
	}

	// Cache the gallery data
	$gallery_data = compact( 'attachment_ids', 'variation_galleries', 'image_urls', 'thumb_urls', 'video', 'video_position' );
	primefit_cache_product_data( $product_id . '_gallery', $gallery_data );
}

// Generate URLs for variation gallery images with caching
foreach ( $variation_galleries as $color => $gallery_data ) {
	if ( isset( $gallery_data['images'] ) && is_array( $gallery_data['images'] ) ) {
		foreach ( $gallery_data['images'] as $attachment_id ) {
			if ( $attachment_id && ! isset( $image_urls[ $attachment_id ] ) ) {
				$large_url = primefit_get_cached_attachment_image_url( $attachment_id, 'large' );
				if ( false === $large_url ) {
					$large_url = wp_get_attachment_image_url( $attachment_id, 'large' );
					primefit_cache_attachment_image_url( $attachment_id, 'large', $large_url );
				}
				$image_urls[ $attachment_id ] = $large_url;
				
				$thumb_url = primefit_get_cached_attachment_image_url( $attachment_id, 'thumbnail' );
				if ( false === $thumb_url ) {
					$thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
					primefit_cache_attachment_image_url( $attachment_id, 'thumbnail', $thumb_url );
				}
				$thumb_urls[ $attachment_id ] = $thumb_url;
			}
		}
	}
}

$gallery_data = array(
	'default' => $attachment_ids,
	'variations' => $variation_galleries,
	'current_color' => '', // Always empty on initial load to ensure default gallery is used
	'product_id' => $product->get_id(),
	'image_urls' => $image_urls,
	'thumb_urls' => isset( $thumb_urls ) && is_array( $thumb_urls ) ? $thumb_urls : array(),
	'video'      => isset( $video ) && is_array( $video ) ? $video : array( 'id' => 0, 'url' => '', 'thumb_id' => 0, 'thumb_url' => '' ),
	'video_position' => isset( $video_position ) ? max( 1, (int) $video_position ) : 1,
);

$gallery_item_count = count( $attachment_ids ) + ( ! empty( $gallery_data['video']['url'] ) ? 1 : 0 );
$has_video          = ! empty( $gallery_data['video']['url'] );
$has_images         = ! empty( $attachment_ids );

// Build ordered media list (images + video inserted at selected position).
$media_items = array();
foreach ( $attachment_ids as $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id > 0 ) {
		$media_items[] = array( 'type' => 'image', 'id' => $attachment_id );
	}
}
if ( $has_video ) {
	$insert_at = max( 0, min( count( $media_items ), (int) $gallery_data['video_position'] - 1 ) );
	array_splice( $media_items, $insert_at, 0, array( array( 'type' => 'video', 'id' => (int) $gallery_data['video']['id'] ) ) );
}

?>

<div class="product-gallery-container">
	<!-- Main Image Display -->
	<div class="product-main-image">
		<div class="main-image-wrapper">
			<?php
			$first_item_type = ! empty( $media_items ) ? $media_items[0]['type'] : ( $has_images ? 'image' : 'video' );
			$main_attachment_id = ( 'image' === $first_item_type && $has_images ) ? $attachment_ids[0] : 0;

			// Try to get cached image URL first
			$main_image_url = '';
			if ( $main_attachment_id ) {
				$main_image_url = primefit_get_cached_attachment_image_url( $main_attachment_id, 'large' );
				if ( false === $main_image_url ) {
					$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'large' );
					primefit_cache_attachment_image_url( $main_attachment_id, 'large', $main_image_url );
				}
			}

			// Try to get cached alt text first
			$main_image_alt = '';
			if ( $main_attachment_id ) {
				$main_image_alt = primefit_get_cached_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt' );
				if ( false === $main_image_alt ) {
					$main_image_alt = get_post_meta( $main_attachment_id, '_wp_attachment_image_alt', true );
					primefit_cache_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt', $main_image_alt );
				}
			}
			?>
			<?php
			// Use original media without WebP conversion
			if ( $main_attachment_id ) {
				$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'large' );
			}
			?>
			<?php if ( 'image' === $first_item_type && $has_images && $main_image_url ) : ?>
				<img src="<?php echo esc_url( $main_image_url ); ?>" 
					 alt="<?php echo esc_attr( $main_image_alt ); ?>" 
					 class="main-product-image" 
					 loading="eager" 
					 fetchpriority="high" 
					 decoding="async" 
					 width="800" 
					 height="800" />
			<?php elseif ( 'video' === $first_item_type && $has_video ) : ?>
				<video class="main-product-video" autoplay muted loop playsinline preload="auto" disablepictureinpicture>
					<source src="<?php echo esc_url( $gallery_data['video']['url'] ); ?>" />
				</video>
			<?php endif; ?>
		</div>

		<!-- Image Navigation Dots -->
		<?php if ( $gallery_item_count > 1 ) : ?>


			<!-- Navigation Arrows -->
			<button class="image-nav image-nav-prev" aria-label="<?php esc_attr_e( 'Previous media', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<button class="image-nav image-nav-next" aria-label="<?php esc_attr_e( 'Next media', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		<?php endif; ?>
	</div>

	<!-- Thumbnail Gallery -->
	<?php if ( $gallery_item_count > 1 ) : ?>
		<div class="product-thumbnails">
			<?php foreach ( $media_items as $index => $item ) : ?>
				<?php if ( 'image' === $item['type'] ) : ?>
					<?php
					$attachment_id = (int) $item['id'];
					// Try to get cached alt text first
					$thumbnail_alt = primefit_get_cached_attachment_meta( $attachment_id, '_wp_attachment_image_alt' );
					if ( false === $thumbnail_alt ) {
						$thumbnail_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
						primefit_cache_attachment_meta( $attachment_id, '_wp_attachment_image_alt', $thumbnail_alt );
					}
					$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
					?>
					<button
						class="thumbnail-item <?php echo 0 === $index ? 'active' : ''; ?>"
						data-media-index="<?php echo esc_attr( $index ); ?>"
						aria-label="<?php printf( esc_attr__( 'View item %d', 'primefit' ), $index + 1 ); ?>"
					>
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" 
							 alt="<?php echo esc_attr( $thumbnail_alt ); ?>" 
							 class="thumbnail-image" 
							 loading="lazy" 
							 decoding="async" 
							 width="150" 
							 height="150" />
					</button>
				<?php elseif ( 'video' === $item['type'] ) : ?>
					<?php
					$video_thumb = $gallery_data['video']['thumb_url'];
					if ( ! $video_thumb && $has_images ) {
						$video_thumb = wp_get_attachment_image_url( $attachment_ids[0], 'thumbnail' );
					}
					?>
					<button
						class="thumbnail-item thumbnail-item--video <?php echo 0 === $index ? 'active' : ''; ?>"
						data-media-index="<?php echo esc_attr( $index ); ?>"
						aria-label="<?php echo esc_attr__( 'View video', 'primefit' ); ?>"
					>
						<img
							src="<?php echo esc_url( $video_thumb ); ?>"
							alt="<?php echo esc_attr__( 'Product video', 'primefit' ); ?>"
							class="thumbnail-image"
							loading="lazy"
							decoding="async"
							width="150"
							height="150"
						/>
					</button>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const galleryContainer = document.querySelector('.product-gallery-container');
	if (!galleryContainer) return;

	const mainImageWrapper = galleryContainer.querySelector('.main-image-wrapper');
	const prevBtn = galleryContainer.querySelector('.image-nav-prev');
	const nextBtn = galleryContainer.querySelector('.image-nav-next');

	// Gallery data from PHP
	const galleryData = <?php echo json_encode( $gallery_data ); ?>;
	

	// Build media list: images + (optional) video as last item.
	let currentColor = '';
	let currentMedia = [];
	let currentIndex = 0;

	function numberIsInteger(n) {
		return typeof n === 'number' && isFinite(n) && Math.floor(n) === n;
	}

	function hasVideo() {
		return !!(galleryData.video && galleryData.video.url);
	}

	function buildMediaFromImageIds(imageIds) {
		const media = [];
		if (Array.isArray(imageIds)) {
			imageIds.forEach((id) => {
				const imageId = Number(id);
				if (!imageId || imageId === 0) return;
				const url = (galleryData.image_urls && galleryData.image_urls[imageId]) ? galleryData.image_urls[imageId] : '';
				if (!url) return;
				const thumb = (galleryData.thumb_urls && galleryData.thumb_urls[imageId]) ? galleryData.thumb_urls[imageId] : url;
				media.push({ type: 'image', id: imageId, url, thumb });
			});
		}

		if (hasVideo()) {
			const videoItem = {
				type: 'video',
				id: galleryData.video.id || 0,
				url: galleryData.video.url,
				thumb: galleryData.video.thumb_url || (media[0] ? media[0].thumb : '')
			};

			const rawPos = Number(galleryData.video_position || 1);
			const pos = (isFinite(rawPos) && rawPos >= 1) ? Math.floor(rawPos) : 1; // 1-based
			const insertAt = Math.max(0, Math.min(media.length, pos - 1)); // 0..len
			media.splice(insertAt, 0, videoItem);
		}

		return media;
	}

	function isValidIndex(i) {
		if (!numberIsInteger(i = Number(i))) return false;
		if (i < 0 || i >= currentMedia.length) return false;
		const item = currentMedia[i];
		if (!item) return false;
		if (item.type === 'image') return !!item.url;
		if (item.type === 'video') return !!item.url;
		return false;
	}

	function getNextValidIndex(start) {
		if (!currentMedia || !currentMedia.length) return 0;
		let i = start;
		for (let c = 0; c < currentMedia.length; c++) {
			i = (i + 1) % currentMedia.length;
			if (isValidIndex(i)) return i;
		}
		return start;
	}

	function getPrevValidIndex(start) {
		if (!currentMedia || !currentMedia.length) return 0;
		let i = start;
		for (let c = 0; c < currentMedia.length; c++) {
			i = (i - 1 + currentMedia.length) % currentMedia.length;
			if (isValidIndex(i)) return i;
		}
		return start;
	}

	function getFirstValidIndex() {
		if (!currentMedia || !currentMedia.length) return 0;
		for (let i = 0; i < currentMedia.length; i++) {
			if (isValidIndex(i)) return i;
		}
		return 0;
	}

	function updateActiveThumbnails(index) {
		const thumbs = galleryContainer.querySelectorAll('.thumbnail-item');
		thumbs.forEach((thumb) => {
			const i = parseInt(thumb.dataset.mediaIndex, 10);
			thumb.classList.toggle('active', i === index);
		});
	}

	function renderMainMedia(index) {
		if (!isValidIndex(index)) {
			const candidate = getNextValidIndex(Math.max(0, Number(index)) - 1);
			if (isValidIndex(candidate)) index = candidate;
			else {
				const firstValid = getFirstValidIndex();
				if (!isValidIndex(firstValid)) return;
				index = firstValid;
			}
		}

		const item = currentMedia[index];
		if (!item) return;

		// Remove existing media element.
		const existing = mainImageWrapper.querySelector('.main-product-image, .main-product-video');
		if (existing) existing.remove();

		if (item.type === 'image') {
			const img = document.createElement('img');
			img.src = item.url;
			img.alt = `Product image ${index + 1}`;
			img.className = 'main-product-image';
			img.loading = 'eager';
			img.decoding = 'async';
			img.setAttribute('fetchpriority', 'high');
			img.width = 800;
			img.height = 800;
			img.dataset.mediaIndex = String(index);
			mainImageWrapper.appendChild(img);
		} else if (item.type === 'video') {
			const video = document.createElement('video');
			video.className = 'main-product-video';
			video.autoplay = true;
			video.muted = true;
			video.loop = true;
			video.preload = 'auto';
			video.playsInline = true;
			video.dataset.mediaIndex = String(index);
			// Disable controls to prevent layout shifts
			video.controls = false;
			video.disablePictureInPicture = true;
			const source = document.createElement('source');
			source.src = item.url;
			video.appendChild(source);
			mainImageWrapper.appendChild(video);

			// Ensure autoplay triggers even when inserted dynamically.
			try { video.play().catch(() => {}); } catch (e) {}
		}

		currentIndex = index;
		updateActiveThumbnails(index);
	}

	// Switch to a different color gallery
	function switchColorGallery(color) {
		// Normalize color for consistent matching
		const normalizedColor = color ? color.toLowerCase().trim() : '';

		if (!normalizedColor || !galleryData.variations || !galleryData.variations[normalizedColor]) {
			// Switch back to default gallery
			currentMedia = buildMediaFromImageIds(galleryData.default);
			currentColor = '';
		} else {
			// Switch to specific color gallery
			currentMedia = buildMediaFromImageIds(galleryData.variations[normalizedColor].images);
			currentColor = color; // Keep original color for reference
		}

		// Validate that we have media to display
		if (!currentMedia || currentMedia.length === 0) {
			currentMedia = buildMediaFromImageIds(galleryData.default);
			currentColor = '';
		}

		// Try to align to the variation's primary image (if provided)
		currentIndex = 0;
		if (currentColor && galleryData.variations && galleryData.variations[normalizedColor]) {
			const colorOption = document.querySelector(`[data-color="${color}"]`);
			const variationImage = colorOption ? colorOption.getAttribute('data-variation-image') : '';
			if (variationImage) {
				const mediaIndex = currentMedia.findIndex((it) => it && it.type === 'image' && it.url === variationImage);
				if (mediaIndex !== -1) currentIndex = mediaIndex;
			}
		}

		// Update thumbnail gallery first
		updateThumbnailGallery();

		// Then render main media
		renderMainMedia(currentIndex);
	}

	// Update thumbnail gallery to show current media (images + video)
	function updateThumbnailGallery() {
		// Ensure thumbnail container exists
		let thumbnailContainer = galleryContainer.querySelector('.product-thumbnails');
		if (!thumbnailContainer) {
			thumbnailContainer = document.createElement('div');
			thumbnailContainer.className = 'product-thumbnails';
			galleryContainer.appendChild(thumbnailContainer);
		}

		// Clear existing thumbnails
		thumbnailContainer.innerHTML = '';

		// Validate currentMedia before processing
		if (!currentMedia || currentMedia.length === 0) {
			return;
		}

		// Add new thumbnails
		currentMedia.forEach((item, index) => {
			if (!item || !item.url) return;

			const isVideo = item.type === 'video';
			const thumbnailUrl = item.thumb || item.url;
			const label = isVideo ? 'View video' : `View image ${index + 1}`;
			const alt = isVideo ? 'Product video' : `Product image ${index + 1}`;
			const extraClass = isVideo ? ' thumbnail-item--video' : '';

			const thumbnailHtml = `
				<button class="thumbnail-item${extraClass} ${index === currentIndex ? 'active' : ''}"
						data-media-index="${index}"
						aria-label="${label}">
					<img src="${thumbnailUrl}"
						 alt="${alt}"
						 class="thumbnail-image"
						 loading="lazy"
						 decoding="async"
						 width="150"
						 height="150" />
				</button>
			`;

			thumbnailContainer.insertAdjacentHTML('beforeend', thumbnailHtml);
		});
	}

	// Event handlers for gallery navigation
	function bindGalleryEvents() {
		// Thumbnail click handlers (will be rebound after gallery updates)
		galleryContainer.removeEventListener('click', handleThumbnailClick);
		galleryContainer.addEventListener('click', handleThumbnailClick);


		// Navigation handlers
		if (prevBtn) {
			prevBtn.addEventListener('click', () => {
				const newIndex = getPrevValidIndex(currentIndex);
				renderMainMedia(newIndex);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', () => {
				const newIndex = getNextValidIndex(currentIndex);
				renderMainMedia(newIndex);
			});
		}

		// Swipe functionality is handled by ProductGallery class in single-product.js
	}


	// Handle thumbnail clicks
	function handleThumbnailClick(e) {
		if (e.target.closest('.thumbnail-item')) {
			e.preventDefault();
			const index = parseInt(e.target.closest('.thumbnail-item').dataset.mediaIndex);
			if (!isNaN(index)) {
				renderMainMedia(index);
			}
		}
	}


	// Listen for color changes from the product variations
	function bindColorChangeEvents() {
		// Color option clicks
		document.removeEventListener('click', handleColorOptionClick);
		document.addEventListener('click', handleColorOptionClick);

		// WooCommerce variation changes
		document.removeEventListener('woocommerce_variation_select_change', handleVariationChange);
		document.addEventListener('woocommerce_variation_select_change', handleVariationChange);
	}

	// Handle color option clicks
	function handleColorOptionClick(e) {
		if (e.target.closest('.color-option')) {
			const color = e.target.closest('.color-option').dataset.color;
			switchColorGallery(color);
		}
	}

	// Handle WooCommerce variation changes
	function handleVariationChange(e) {
		// Get selected color from form inputs
		const colorInputs = [
			'input[name="attribute_pa_color"]',
			'select[name="attribute_pa_color"]',
			'input[name="attribute_color"]',
			'select[name="attribute_color"]'
		];

		let selectedColor = '';
		for (const selector of colorInputs) {
			const element = document.querySelector(selector);
			if (element && element.value) {
				selectedColor = element.value;
				break;
			}
		}

		if (selectedColor && selectedColor !== currentColor) {
			switchColorGallery(selectedColor);
		}
	}

	// Initialize everything
	// Always start with default gallery media
	currentMedia = buildMediaFromImageIds(galleryData.default);
	currentIndex = 0;
	currentColor = '';
	updateThumbnailGallery();
	renderMainMedia(0);
	bindGalleryEvents();
	bindColorChangeEvents();

	// Lightweight, self-contained swipe support
	(function initSwipe() {
		let startX = 0;
		let startY = 0;
		let startTime = 0;

		function onTouchStart(e) {
			if (!e.touches || !e.touches.length) return;
			startX = e.touches[0].clientX;
			startY = e.touches[0].clientY;
			startTime = Date.now();
		}

		function onTouchEnd(e) {
			if (!e.changedTouches || !e.changedTouches.length) return;
			const endX = e.changedTouches[0].clientX;
			const endY = e.changedTouches[0].clientY;
			const deltaX = endX - startX;
			const deltaY = endY - startY;
			const elapsed = Date.now() - startTime;

			const minDistance = 30; // px
			const maxTime = 600; // ms

			if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minDistance && elapsed < maxTime) {
				if (!currentMedia || currentMedia.length <= 1) return;
				if (deltaX < 0) {
					const newIndex = getNextValidIndex(currentIndex);
					renderMainMedia(newIndex);
				} else if (deltaX > 0) {
					const newIndex = getPrevValidIndex(currentIndex);
					renderMainMedia(newIndex);
				}
			}
		}

		// Bind swipe handlers only once to avoid duplicate handling (skip/jump)
		const swipeTarget = mainImageWrapper;
		if (swipeTarget) {
			swipeTarget.addEventListener('touchstart', onTouchStart, { passive: true });
			swipeTarget.addEventListener('touchend', onTouchEnd, { passive: true });
		}
	})();

	// Keyboard navigation
	document.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft') {
			const newIndex = getPrevValidIndex(currentIndex);
			renderMainMedia(newIndex);
		} else if (e.key === 'ArrowRight') {
			const newIndex = getNextValidIndex(currentIndex);
			renderMainMedia(newIndex);
		}
	});

	// Listen for color selection events from the ProductVariations class
	document.addEventListener('colorSelected', function(e) {
		switchColorGallery(e.detail.color);
	});

	// Expose the switchColorGallery function globally for external use
	window.switchProductGallery = switchColorGallery;

	// Mobile Lightbox Implementation
	// Note: Images only; video is handled inline.
	function initMobileLightbox() {
		// Only initialize on mobile devices
		if (window.innerWidth > 768) return;

		const lightbox = createLightboxElement();
		let currentLightboxIndex = 0;

		// Open lightbox when tapping main image (images only)
		mainImageWrapper.addEventListener('click', function(e) {
			if (window.innerWidth > 768) return;
			const item = currentMedia[currentIndex];
			if (!item || item.type !== 'image') return;
			if (e.target && e.target.closest && e.target.closest('video')) return;
			e.preventDefault();
			e.stopPropagation();
			openLightbox(currentIndex);
		});

		function createLightboxElement() {
			const lightbox = document.createElement('div');
			lightbox.className = 'mobile-lightbox';
			lightbox.innerHTML = `
				<div class="lightbox-overlay"></div>
				<div class="lightbox-content">
					<button class="lightbox-close" aria-label="Close lightbox">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
					<div class="lightbox-controls">
						<button class="lightbox-zoom-in" aria-label="Zoom in">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
						<button class="lightbox-zoom-out" aria-label="Zoom out">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>
					<div class="lightbox-image-container">
						<img class="lightbox-image" src="" alt="" />
					</div>
					<div class="lightbox-counter">
						<span class="lightbox-current">1</span> / <span class="lightbox-total">1</span>
					</div>
				</div>
			`;
			document.body.appendChild(lightbox);
			return lightbox;
		}

		function openLightbox(index) {
			const item = currentMedia[index];
			if (!item || item.type !== 'image') return;
			const imageUrl = item.url;
			const imageAlt = `Product image ${index + 1}`;

			if (!imageUrl) return;

			currentLightboxIndex = index;
			const lightboxImage = lightbox.querySelector('.lightbox-image');
			const lightboxCurrent = lightbox.querySelector('.lightbox-current');
			const lightboxTotal = lightbox.querySelector('.lightbox-total');

			// Reset zoom state when opening new image
			resetZoom();

			// Set image and counter
			lightboxImage.src = imageUrl;
			lightboxImage.alt = imageAlt;
			const imageIndices = currentMedia.map((it, idx) => (it && it.type === 'image' ? idx : -1)).filter((idx) => idx !== -1);
			const imagePosition = imageIndices.indexOf(index);
			lightboxCurrent.textContent = imagePosition === -1 ? '1' : String(imagePosition + 1);
			lightboxTotal.textContent = String(imageIndices.length || 1);

			// Show lightbox
			lightbox.classList.add('active');
			document.body.style.overflow = 'hidden';
			document.body.style.position = 'fixed';
			document.body.style.width = '100%';

			// Focus management
			lightbox.querySelector('.lightbox-close').focus();
		}

		function closeLightbox() {
			lightbox.classList.remove('active');
			document.body.style.overflow = '';
			document.body.style.position = '';
			document.body.style.width = '';
		}

		function navigateLightbox(direction) {
			const imageIndices = currentMedia.map((it, idx) => (it && it.type === 'image' ? idx : -1)).filter((idx) => idx !== -1);
			if (!imageIndices.length) return;
			const pos = imageIndices.indexOf(currentLightboxIndex);
			const nextPos = direction === 'next'
				? (pos < imageIndices.length - 1 ? pos + 1 : 0)
				: (pos > 0 ? pos - 1 : imageIndices.length - 1);
			const newIndex = imageIndices[nextPos];
			openLightbox(newIndex);
		}

		// Event listeners
		lightbox.addEventListener('click', function(e) {
			if (e.target.classList.contains('lightbox-overlay') || e.target.closest('.lightbox-close')) {
				closeLightbox();
			}
		});

		// Keyboard navigation
		document.addEventListener('keydown', function(e) {
			if (!lightbox.classList.contains('active')) return;

			switch(e.key) {
				case 'Escape':
					closeLightbox();
					break;
				case 'ArrowLeft':
					navigateLightbox('prev');
					break;
				case 'ArrowRight':
					navigateLightbox('next');
					break;
			}
		});

		// Touch navigation and zoom for lightbox
		let lightboxStartX = 0;
		let lightboxStartY = 0;
		let isZoomed = false;
		let currentScale = 1;
		let currentTranslateX = 0;
		let currentTranslateY = 0;

		const lightboxImage = lightbox.querySelector('.lightbox-image');
		const lightboxImageContainer = lightbox.querySelector('.lightbox-image-container');
		const zoomInBtn = lightbox.querySelector('.lightbox-zoom-in');
		const zoomOutBtn = lightbox.querySelector('.lightbox-zoom-out');

		// Zoom button functionality
		zoomInBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			zoomIn();
		});

		zoomOutBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			zoomOut();
		});

		function zoomIn() {
			if (currentScale < 3) {
				isZoomed = true;
				currentScale = Math.min(currentScale + 0.5, 3);
				lightboxImage.style.transform = `scale(${currentScale})`;
				lightboxImage.style.transition = 'transform 0.3s ease';
				lightboxImageContainer.style.overflow = 'auto';
				updateZoomButtons();
			}
		}

		function zoomOut() {
			if (currentScale > 1) {
				currentScale = Math.max(currentScale - 0.5, 1);
				lightboxImage.style.transform = `scale(${currentScale})`;
				lightboxImage.style.transition = 'transform 0.3s ease';
				
				if (currentScale === 1) {
					isZoomed = false;
					currentTranslateX = 0;
					currentTranslateY = 0;
					lightboxImage.style.transform = 'scale(1) translate(0, 0)';
					lightboxImageContainer.style.overflow = 'hidden';
				}
				updateZoomButtons();
			}
		}

		function resetZoom() {
			isZoomed = false;
			currentScale = 1;
			currentTranslateX = 0;
			currentTranslateY = 0;
			lightboxImage.style.transform = 'scale(1) translate(0, 0)';
			lightboxImage.style.transition = 'transform 0.3s ease';
			lightboxImageContainer.style.overflow = 'hidden';
			updateZoomButtons();
		}

		function updateZoomButtons() {
			zoomInBtn.style.opacity = currentScale >= 3 ? '0.5' : '1';
			zoomOutBtn.style.opacity = currentScale <= 1 ? '0.5' : '1';
		}

		// Pan functionality when zoomed
		let isPanning = false;
		let startPanX = 0;
		let startPanY = 0;

		lightboxImage.addEventListener('touchstart', function(e) {
			if (isZoomed && e.touches.length === 1) {
				isPanning = true;
				startPanX = e.touches[0].clientX - currentTranslateX;
				startPanY = e.touches[0].clientY - currentTranslateY;
				lightboxImage.style.transition = 'none';
			}
		}, { passive: true });

		lightboxImage.addEventListener('touchmove', function(e) {
			if (isZoomed && isPanning && e.touches.length === 1) {
				e.preventDefault();
				currentTranslateX = e.touches[0].clientX - startPanX;
				currentTranslateY = e.touches[0].clientY - startPanY;
				lightboxImage.style.transform = `scale(${currentScale}) translate(${currentTranslateX}px, ${currentTranslateY}px)`;
			}
		}, { passive: false });

		lightboxImage.addEventListener('touchend', function(e) {
			if (isPanning) {
				isPanning = false;
				lightboxImage.style.transition = 'transform 0.1s ease';
			}
		}, { passive: true });

		// Swipe navigation (only when not zoomed and not on controls)
		lightbox.addEventListener('touchstart', function(e) {
			// Only track swipe if not zoomed and not touching controls
			if (!isZoomed && !e.target.closest('.lightbox-controls') && !e.target.closest('.lightbox-close')) {
				lightboxStartX = e.touches[0].clientX;
				lightboxStartY = e.touches[0].clientY;
			}
		}, { passive: true });

		lightbox.addEventListener('touchend', function(e) {
			// Only handle swipe if not zoomed and not touching controls
			if (!isZoomed && !e.target.closest('.lightbox-controls') && !e.target.closest('.lightbox-close') && e.changedTouches && e.changedTouches.length) {
				const endX = e.changedTouches[0].clientX;
				const endY = e.changedTouches[0].clientY;
				const deltaX = endX - lightboxStartX;
				const deltaY = endY - lightboxStartY;

				// Only handle horizontal swipes with sufficient distance
				if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 80) {
					e.preventDefault();
					if (deltaX > 0) {
						navigateLightbox('prev');
					} else {
						navigateLightbox('next');
					}
				}
			}
		}, { passive: false });

		// Handle window resize to disable lightbox on desktop
		window.addEventListener('resize', function() {
			if (window.innerWidth > 768 && lightbox.classList.contains('active')) {
				closeLightbox();
			}
		});

		// Update lightbox when gallery changes (color switching)
		const originalSwitchColorGallery = switchColorGallery;
		window.switchColorGallery = function(color) {
			originalSwitchColorGallery(color);
			// Close lightbox if open when gallery changes
			if (lightbox.classList.contains('active')) {
				closeLightbox();
			}
		};
	}

	// Initialize mobile lightbox
	initMobileLightbox();
});
</script>
