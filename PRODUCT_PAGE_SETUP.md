# Product Page Setup Guide

## Overview

This guide explains how to set up the new product page layout with all its features and functionality.

## Admin Fields Setup

### 1. Product Features

In the product edit page, use the "Product Features" field with this JSON format:

```json
[
  {
    "title": "FRONT ZIP POCKET",
    "image": 123,
    "description": "Secure storage for small items"
  },
  {
    "title": "LASER-PUNCHED LETTERING",
    "image": 124,
    "description": "Brand logo with mesh texture"
  },
  {
    "title": "ADJUSTABLE ANKLE SNAPS",
    "image": 125,
    "description": "Customizable fit at the ankle"
  }
]
```

**Note:** Replace `123`, `124`, `125` with actual WordPress media attachment IDs.

### 2. Technical Highlights

Use the "Technical Highlights" field with this JSON format:

```json
[
  {
    "title": "DESIGNED FOR TRAINING",
    "icon": "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M12 2L2 7L12 12L22 7L12 2Z' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M2 17L12 22L22 17' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/><path d='M2 12L12 17L22 12' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>",
    "description": "Built with the intention of gym training, lifting, HIIT, hybrid training, and cardio."
  },
  {
    "title": "RELAXED FIT",
    "icon": "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><rect x='3' y='3' width='18' height='18' rx='2' ry='2' stroke='currentColor' stroke-width='2'/><path d='M9 9H15' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M9 15H15' stroke='currentColor' stroke-width='2' stroke-linecap='round'/></svg>",
    "description": "This product provides a slightly looser, comfortable fit for everyday wear."
  },
  {
    "title": "WAFFLE KNIT FABRICATION",
    "icon": "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2'/><circle cx='12' cy='12' r='6' stroke='currentColor' stroke-width='2'/><circle cx='12' cy='12' r='2' stroke='currentColor' stroke-width='2'/></svg>",
    "description": "Technical waffle knit material with four-way stretch and a super soft handfeel."
  }
]
```

### 3. Product Information Fields

#### Custom Description

Override the default product description with custom content. Supports HTML.

#### Designed For

Describe what the product is designed for. Example:

```
Built for athletes who demand performance and comfort during intense training sessions.
```

#### Fabric + Technology

Describe the fabric and technology used. Example:

```
Made from premium technical waffle knit fabric with four-way stretch technology for maximum mobility and comfort.
```

## Color Swatches Setup

To enable color swatches, you need to add CSS for each color. Add this to your theme's CSS:

```css
.color-swatch.color-black {
  background-color: #000000;
}

.color-swatch.color-white {
  background-color: #ffffff;
}

.color-swatch.color-grey {
  background-color: #808080;
}

.color-swatch.color-navy {
  background-color: #000080;
}

/* Add more colors as needed */
```

## Features

### ✅ Implemented Features

1. **Product Image Gallery**

   - Thumbnail navigation
   - Main image display
   - Touch/swipe support for mobile
   - Keyboard navigation
   - Image dots indicator

2. **Product Details Section**

   - SKU display
   - Product title
   - Color information
   - Price with sale pricing
   - Color selection (for variable products)
   - Size selection (for variable products)
   - Stock status notices
   - Add to cart / Notify when available

3. **Product Features Section**

   - Image-based feature showcase
   - Customizable feature descriptions
   - Responsive grid layout

4. **Technical Highlights Section**

   - Icon-based highlights
   - Custom descriptions
   - Responsive layout

5. **Product Information Sections**

   - Collapsible sections
   - Description, Designed For, Fabric + Technology
   - Smooth animations
   - Keyboard accessibility

6. **Dark/Light Mode Compatibility**

   - CSS custom properties
   - Automatic theme detection
   - Manual theme switching support

7. **Mobile Responsiveness**

   - Responsive grid layouts
   - Touch-friendly interactions
   - Optimized for all screen sizes
   - Mobile-first approach

8. **Accessibility Features**
   - Keyboard navigation
   - ARIA labels
   - Focus indicators
   - Screen reader support
   - Reduced motion support

## File Structure

```
single-product.php                           # Main product page template
woocommerce/single-product/parts/
├── product-gallery.php                      # Image gallery component
├── product-details.php                      # Product details component
├── product-features.php                     # Features section
├── product-technical-highlights.php         # Technical highlights
└── product-information.php                  # Collapsible info sections

assets/css/single-product.css                # Product page styles
assets/js/single-product.js                 # Product page JavaScript
inc/woocommerce.php                          # Updated with new admin fields
```

## Usage Instructions

1. **Create a new product** in WooCommerce
2. **Upload product images** to the gallery
3. **Set up product attributes** for color and size variations
4. **Fill in the custom fields** using the JSON formats above
5. **Configure color swatches** in CSS
6. **Test the product page** on different devices

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Notes

- Images are optimized with proper aspect ratios
- CSS uses efficient selectors and minimal repaints
- JavaScript is modular and only loads on product pages
- Touch events are optimized for mobile performance

## Troubleshooting

### Images not displaying

- Check that attachment IDs in JSON are correct
- Verify images are uploaded to WordPress media library

### Color swatches not showing

- Add CSS for each color variant
- Ensure color names match the attribute values

### JavaScript not working

- Check browser console for errors
- Verify jQuery is loaded
- Ensure WooCommerce is active

### Mobile layout issues

- Test on actual devices, not just browser dev tools
- Check touch event handling
- Verify viewport meta tag is present
