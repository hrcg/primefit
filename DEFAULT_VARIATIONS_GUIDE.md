# Default Variations Support

This document explains how the PrimeFit theme now supports WooCommerce's default variation selection for size and color attributes.

## Overview

The theme now respects WooCommerce's "Default Form Values" settings for variable products, automatically selecting the configured default color and size when customers visit a product page.

## How It Works

### 1. WooCommerce Configuration

In the WordPress admin, when editing a variable product:

1. Go to **Products** → Select your variable product
2. Click on the **Variations** tab
3. Set the **Default Form Values** for Size and Color
4. Click **Update** to save

### 2. Theme Implementation

The theme automatically:

- **Detects default attributes** using `$product->get_default_attributes()`
- **Extracts color and size defaults** from WooCommerce settings
- **Pre-selects the default options** in the frontend interface
- **Updates available sizes** based on the default color selection
- **Enables the Add to Cart button** if both default color and size are selected

### 3. Frontend Behavior

When a customer visits a product page:

1. **Default color** is automatically selected and highlighted
2. **Default size** is automatically selected (if available for the default color)
3. **Product image** updates to show the variation image for the default color
4. **Add to Cart button** is enabled if both defaults are selected
5. **Size options** are filtered to show only sizes available for the default color

## Technical Implementation

### PHP Changes

- **`woocommerce/single-product/product-summary.php`**: Main implementation
- **`woocommerce/single-product/parts/product-details.php`**: Consistent behavior across templates

Key functions:

- `$product->get_default_attributes()` - Gets WooCommerce default attributes
- Attribute detection for color and size variations
- Default selection logic for both color and size options

### JavaScript Changes

- **`assets/js/single-product.js`**: Enhanced variation handling
- **Inline JavaScript**: Initialization with default values

Key features:

- Default color and size initialization
- Automatic size filtering based on selected color
- Add to cart button state management

## Debugging

The implementation includes debug information accessible via browser console:

```javascript
console.log(
  "PrimeFit Default Variations:",
  window.primefitProductData.debugInfo
);
```

This shows:

- Default attributes from WooCommerce
- Available color and size options
- Current selection state

## Fallback Behavior

If no defaults are set in WooCommerce:

1. **Color**: First available color is selected
2. **Size**: First available size is selected
3. **Functionality**: Remains fully functional

## Browser Compatibility

- Modern browsers with ES6+ support
- Graceful degradation for older browsers
- Mobile-responsive design maintained

## Testing

To test the implementation:

1. Set default variations in WooCommerce admin
2. Visit the product page
3. Verify default color and size are pre-selected
4. Check that Add to Cart button is enabled
5. Test color switching updates available sizes
6. Verify size selection works correctly

## Notes

- Default variations only work for products with both color and size attributes
- The implementation respects WooCommerce's stock management
- Out-of-stock variations are automatically excluded from selection
- The feature integrates seamlessly with existing theme functionality
