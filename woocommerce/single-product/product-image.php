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
	extract( $cached_gallery );
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

	if ( empty( $attachment_ids ) ) {
		return;
	}

	// Get variation galleries from ACF field
	$variation_galleries = primefit_get_variation_gallery_data( $product_id );

	// Generate URLs for all images with caching
	$image_urls = array();
	foreach ($attachment_ids as $attachment_id) {
		if ($attachment_id) {
			$image_urls[$attachment_id] = wp_get_attachment_image_url($attachment_id, 'large');
		}
	}

	// Cache the gallery data
	$gallery_data = compact( 'attachment_ids', 'variation_galleries', 'image_urls' );
	primefit_cache_product_data( $product_id . '_gallery', $gallery_data );
}

// Generate URLs for variation gallery images
foreach ($variation_galleries as $color => $gallery_data) {
	if (isset($gallery_data['images']) && is_array($gallery_data['images'])) {
		foreach ($gallery_data['images'] as $attachment_id) {
			if ($attachment_id && !isset($image_urls[$attachment_id])) {
				$image_urls[$attachment_id] = wp_get_attachment_image_url($attachment_id, 'large');
			}
		}
	}
}

$gallery_data = array(
	'default' => $attachment_ids,
	'variations' => $variation_galleries,
	'current_color' => '', // Always empty on initial load to ensure default gallery is used
	'product_id' => $product->get_id(),
	'image_urls' => $image_urls
);


?>

<div class="product-gallery-container">
	<!-- Main Image Display -->
	<div class="product-main-image">
		<div class="main-image-wrapper">
			<?php
			$main_attachment_id = $attachment_ids[0];

			// Try to get cached image URL first
			$main_image_url = primefit_get_cached_attachment_image_url( $main_attachment_id, 'large' );
			if ( false === $main_image_url ) {
				$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'large' );
				primefit_cache_attachment_image_url( $main_attachment_id, 'large', $main_image_url );
			}

			// Try to get cached alt text first
			$main_image_alt = primefit_get_cached_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt' );
			if ( false === $main_image_alt ) {
				$main_image_alt = get_post_meta( $main_attachment_id, '_wp_attachment_image_alt', true );
				primefit_cache_attachment_meta( $main_attachment_id, '_wp_attachment_image_alt', $main_image_alt );
			}
			?>
			<?php
			// Use original image without WebP conversion
			$main_image_url = wp_get_attachment_image_url( $main_attachment_id, 'large' );
			?>
			<img src="<?php echo esc_url( $main_image_url ); ?>" 
				 alt="<?php echo esc_attr( $main_image_alt ); ?>" 
				 class="main-product-image" 
				 loading="eager" 
				 fetchpriority="high" 
				 decoding="async" 
				 width="800" 
				 height="800" />
		</div>

		<!-- Image Navigation Dots -->
		<?php if ( count( $attachment_ids ) > 1 ) : ?>


			<!-- Navigation Arrows -->
			<button class="image-nav image-nav-prev" aria-label="<?php esc_attr_e( 'Previous image', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<button class="image-nav image-nav-next" aria-label="<?php esc_attr_e( 'Next image', 'primefit' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		<?php endif; ?>
	</div>

	<!-- Thumbnail Gallery -->
	<?php if ( count( $attachment_ids ) > 1 ) : ?>
		<div class="product-thumbnails">
			<?php foreach ( $attachment_ids as $index => $attachment_id ) : ?>
				<?php
				// Try to get cached image URL first
				$thumbnail_url = primefit_get_cached_attachment_image_url( $attachment_id, 'full' );
				if ( false === $thumbnail_url ) {
					$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'full' );
					primefit_cache_attachment_image_url( $attachment_id, 'full', $thumbnail_url );
				}

				// Try to get cached alt text first
				$thumbnail_alt = primefit_get_cached_attachment_meta( $attachment_id, '_wp_attachment_image_alt' );
				if ( false === $thumbnail_alt ) {
					$thumbnail_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
					primefit_cache_attachment_meta( $attachment_id, '_wp_attachment_image_alt', $thumbnail_alt );
				}
				?>
				<button
					class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>"
					data-image-index="<?php echo esc_attr( $index ); ?>"
					aria-label="<?php printf( esc_attr__( 'View image %d', 'primefit' ), $index + 1 ); ?>"
				>
					<?php
					// Use original image without WebP conversion
					$thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
					?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" 
						 alt="<?php echo esc_attr( $thumbnail_alt ); ?>" 
						 class="thumbnail-image" 
						 loading="lazy" 
						 decoding="async" 
						 width="150" 
						 height="150" />
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const galleryContainer = document.querySelector('.product-gallery-container');
	if (!galleryContainer) return;

	const mainImage = galleryContainer.querySelector('.main-product-image');
	const mainImageWrapper = galleryContainer.querySelector('.main-image-wrapper');
	const thumbnails = galleryContainer.querySelectorAll('.thumbnail-item');
	const prevBtn = galleryContainer.querySelector('.image-nav-prev');
	const nextBtn = galleryContainer.querySelector('.image-nav-next');

	// Gallery data from PHP
	const galleryData = <?php echo json_encode( $gallery_data ); ?>;
	


	let currentImages = galleryData.default;
	let currentIndex = 0;
	let currentColor = galleryData.current_color;

	// Helpers to handle invalid image IDs in currentImages (zeros/null)
	function isValidIndex(i) {
		return (
			numberIsInteger(i = Number(i)) &&
			i >= 0 &&
			i < currentImages.length &&
			currentImages[i] &&
			currentImages[i] !== 0 &&
			!!galleryData.image_urls[currentImages[i]]
		);
	}

	function numberIsInteger(n) {
		return typeof n === 'number' && isFinite(n) && Math.floor(n) === n;
	}

	function getNextValidIndex(start) {
		if (!currentImages || !currentImages.length) return 0;
		let i = start;
		for (let c = 0; c < currentImages.length; c++) {
			i = (i + 1) % currentImages.length;
			if (isValidIndex(i)) return i;
		}
		return start;
	}

	function getPrevValidIndex(start) {
		if (!currentImages || !currentImages.length) return 0;
		let i = start;
		for (let c = 0; c < currentImages.length; c++) {
			i = (i - 1 + currentImages.length) % currentImages.length;
			if (isValidIndex(i)) return i;
		}
		return start;
	}

	function getFirstValidIndex() {
		if (!currentImages || !currentImages.length) return 0;
		for (let i = 0; i < currentImages.length; i++) {
			if (isValidIndex(i)) return i;
		}
		return 0;
	}

	// Initialize gallery with current images
	function initializeGallery() {

		// Always start with default gallery
		currentImages = galleryData.default;
		currentIndex = getFirstValidIndex();
		currentColor = '';

		// Only switch to variation gallery if we have a selected color AND variation galleries exist
		if (currentColor) {
			const normalizedColor = currentColor.toLowerCase().trim();
			if (galleryData.variations && galleryData.variations[normalizedColor] && galleryData.variations[normalizedColor].images.length > 0) {
				currentImages = galleryData.variations[normalizedColor].images;
			}
		}

		// Final safety check
		if (!currentImages || currentImages.length === 0) {
			currentImages = galleryData.default;
			currentColor = '';
		}


		// Create thumbnails first
		updateThumbnailGallery();

		// Then update main image
		updateMainImage(0);
	}

	function updateMainImage(index) {
		// Normalize target index to nearest valid image
		if (!isValidIndex(index)) {
			// Try forward to find the next valid image
			const candidate = getNextValidIndex(Math.max(0, Number(index)) - 1);
			if (isValidIndex(candidate)) {
				index = candidate;
			} else {
				const firstValid = getFirstValidIndex();
				if (!isValidIndex(firstValid)) return; // no valid images
				index = firstValid;
			}
		}

		const imageId = currentImages[index];
		const imageUrl = galleryData.image_urls[imageId] || '';

		if (!imageUrl) {
			return;
		}

		const imageAlt = `Product image ${index + 1}`;

		// Update image source and attributes (no WebP conversion)
		mainImage.src = imageUrl;
		mainImage.alt = imageAlt;
		mainImage.dataset.imageIndex = index;

		// Update active states for thumbnails (after ensuring they exist)
		const thumbnails = galleryContainer.querySelectorAll('.thumbnail-item');
		thumbnails.forEach((thumb, i) => {
			thumb.classList.toggle('active', i === index);
		});

		currentIndex = index;
	}

	// Switch to a different color gallery
	function switchColorGallery(color) {
		// Normalize color for consistent matching
		const normalizedColor = color ? color.toLowerCase().trim() : '';

		if (!normalizedColor || !galleryData.variations || !galleryData.variations[normalizedColor]) {
			// Switch back to default gallery
			currentImages = galleryData.default;
			currentColor = '';
		} else {
			// Switch to specific color gallery
			currentImages = galleryData.variations[normalizedColor].images;
			currentColor = color; // Keep original color for reference
		}

		// Validate that we have images to display
		if (!currentImages || currentImages.length === 0) {
			currentImages = galleryData.default;
			currentColor = '';
		}

		// Find the main variation image for this color and set it as the primary image
		if (currentColor && galleryData.variations && galleryData.variations[normalizedColor]) {
			// Get the main variation image from the color option data
			const colorOption = document.querySelector(`[data-color="${color}"]`);
			if (colorOption) {
				const variationImage = colorOption.getAttribute('data-variation-image');
				if (variationImage) {
					// Find this image in the current gallery array
					const imageIndex = currentImages.findIndex(imageId => {
						if (!imageId || imageId === 0) return false;
						const imageUrl = galleryData.image_urls[imageId];
						return imageUrl === variationImage;
					});

					if (imageIndex !== -1) {
						currentIndex = imageIndex;
					} else {
						currentIndex = 0;
					}
				} else {
					currentIndex = 0;
				}
			} else {
				currentIndex = 0;
			}
		} else {
			// For default gallery or fallback cases, use index 0
			currentIndex = 0;
		}

		// Update thumbnail gallery first
		updateThumbnailGallery();

		// Then update main image - ensure we have a valid image at currentIndex
		if (currentImages && currentImages.length > 0 && currentImages[currentIndex]) {
			updateMainImage(currentIndex);
		} else {
			// Find the first valid image in the gallery
			for (let i = 0; i < currentImages.length; i++) {
				if (currentImages[i] && currentImages[i] !== 0) {
					currentIndex = i;
					updateMainImage(i);
					break;
				}
			}
		}
	}

	// Update thumbnail gallery to show current images
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

		// Validate currentImages before processing
		if (!currentImages || currentImages.length === 0) {
			return;
		}

		// Add new thumbnails
		currentImages.forEach((imageId, index) => {
			if (!imageId || imageId === 0) return; // Skip empty or invalid image IDs

			const thumbnailUrl = galleryData.image_urls[imageId] || '';
			const thumbnailAlt = `Product image ${index + 1}`;

			if (!thumbnailUrl) {
				return;
			}

			const thumbnailHtml = `
				<button class="thumbnail-item ${index === currentIndex ? 'active' : ''}"
						data-image-index="${index}"
						aria-label="View image ${index + 1}">
					<img src="${thumbnailUrl}"
						 alt="${thumbnailAlt}"
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
				updateMainImage(newIndex);
			});
		}

		if (nextBtn) {
			nextBtn.addEventListener('click', () => {
				const newIndex = getNextValidIndex(currentIndex);
				updateMainImage(newIndex);
			});
		}

		// Swipe functionality is handled by ProductGallery class in single-product.js
	}


	// Handle thumbnail clicks
	function handleThumbnailClick(e) {
		if (e.target.closest('.thumbnail-item')) {
			e.preventDefault();
			const index = parseInt(e.target.closest('.thumbnail-item').dataset.imageIndex);
			if (!isNaN(index)) {
				updateMainImage(index);
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
	initializeGallery();
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
				if (!currentImages || currentImages.length <= 1) return;
				if (deltaX < 0) {
					const newIndex = getNextValidIndex(currentIndex);
					updateMainImage(newIndex);
				} else if (deltaX > 0) {
					const newIndex = getPrevValidIndex(currentIndex);
					updateMainImage(newIndex);
				}
			}
		}

		// Bind swipe handlers only once to avoid duplicate handling (skip/jump)
		const swipeTarget = mainImageWrapper || mainImage;
		if (swipeTarget) {
			swipeTarget.addEventListener('touchstart', onTouchStart, { passive: true });
			swipeTarget.addEventListener('touchend', onTouchEnd, { passive: true });
		}
	})();

	// Keyboard navigation
	document.addEventListener('keydown', (e) => {
		if (e.key === 'ArrowLeft') {
			const newIndex = getPrevValidIndex(currentIndex);
			updateMainImage(newIndex);
		} else if (e.key === 'ArrowRight') {
			const newIndex = getNextValidIndex(currentIndex);
			updateMainImage(newIndex);
		}
	});

	// Listen for color selection events from the ProductVariations class
	document.addEventListener('colorSelected', function(e) {
		switchColorGallery(e.detail.color);
	});

	// Expose the switchColorGallery function globally for external use
	window.switchProductGallery = switchColorGallery;

	// Mobile Lightbox Implementation
	function initMobileLightbox() {
		// Only initialize on mobile devices
		if (window.innerWidth > 768) return;

		const lightbox = createLightboxElement();
		let currentLightboxIndex = 0;

		// Add click handler to main image for mobile lightbox
		mainImage.addEventListener('click', function(e) {
			if (window.innerWidth <= 768) {
				e.preventDefault();
				e.stopPropagation();
				openLightbox(currentIndex);
			}
		});

		// Override existing thumbnail click handler for mobile lightbox
		const originalHandleThumbnailClick = handleThumbnailClick;
		handleThumbnailClick = function(e) {
			if (window.innerWidth <= 768 && e.target.closest('.thumbnail-item')) {
				e.preventDefault();
				e.stopPropagation();
				const index = parseInt(e.target.closest('.thumbnail-item').dataset.imageIndex);
				if (!isNaN(index)) {
					openLightbox(index);
				}
			} else {
				// Call original handler for desktop
				originalHandleThumbnailClick.call(this, e);
			}
		};

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
			if (!currentImages || !currentImages[index]) return;

			const imageId = currentImages[index];
			const imageUrl = galleryData.image_urls[imageId];
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
			lightboxCurrent.textContent = index + 1;
			lightboxTotal.textContent = currentImages.length;

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
			let newIndex;
			if (direction === 'next') {
				newIndex = currentLightboxIndex < currentImages.length - 1 ? currentLightboxIndex + 1 : 0;
			} else {
				newIndex = currentLightboxIndex > 0 ? currentLightboxIndex - 1 : currentImages.length - 1;
			}
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
