# Regional Settings Feature Guide

## Overview

The Regional Settings feature provides a Skyscanner-style modal that allows users to select their preferred Language, Country/Region, and Currency. The system automatically detects user location via IP address and applies appropriate defaults.

## Features

✅ **Auto-Detection**: Automatically detects user's country, currency, and language based on IP address  
✅ **Modal Interface**: Beautiful Skyscanner-inspired modal popup  
✅ **Header Button**: Shortcode to display regional settings button in header  
✅ **Smart Defaults**: Country selection auto-updates currency and language suggestions  
✅ **Persistent Settings**: User preferences saved in localStorage  
✅ **Multi-Language Support**: 15+ languages supported  
✅ **Multi-Currency Support**: 14+ currencies supported  
✅ **17+ Countries**: Pre-configured country mappings  

## Installation

The feature is automatically included with the Amadex plugin. No additional installation required.

## Usage

### 1. Display Regional Settings Button

Add the shortcode to your header or any page:

```php
[amadex_regional_settings]
```

**Shortcode Attributes:**

- `style` (optional): Button style - `button` (default) or `text`
- `position` (optional): Button position - `header` (default), `inline`, or `floating`

**Examples:**

```php
<!-- Default header button -->
[amadex_regional_settings]

<!-- Floating button (top-right corner) -->
[amadex_regional_settings position="floating"]

<!-- Inline text link -->
[amadex_regional_settings style="text" position="inline"]
```

### 2. Manual Integration in Header

If you want to add the button manually in your theme's header:

```php
<?php echo do_shortcode('[amadex_regional_settings position="header"]'); ?>
```

Or in your header template file:

```html
<div class="header-regional-settings">
    <?php echo do_shortcode('[amadex_regional_settings]'); ?>
</div>
```

## How It Works

### Auto-Detection Flow

1. **First Visit**: System detects user's IP address
2. **Location Detection**: Uses free IP geolocation API (ipapi.co)
3. **Country Mapping**: Maps country to default currency and language
4. **Settings Applied**: Automatically sets currency, language, and country
5. **Preferences Saved**: Settings saved to localStorage for future visits

### User Selection Flow

1. **User Clicks Button**: Opens regional settings modal
2. **User Selects Options**: Chooses language, country, and currency
3. **Smart Suggestions**: Selecting country auto-suggests currency and language
4. **Save Settings**: User clicks "Save" button
5. **Settings Applied**: Page reloads with new regional settings
6. **Persistent Storage**: Settings saved to localStorage

## Supported Languages

- English (United States) - `en-US`
- English (United Kingdom) - `en-GB`
- English (India) - `en-IN`
- English (Australia) - `en-AU`
- English (Canada) - `en-CA`
- Español (España) - `es-ES`
- Español (México) - `es-MX`
- Français (France) - `fr-FR`
- Deutsch (Deutschland) - `de-DE`
- Italiano (Italia) - `it-IT`
- Português (Brasil) - `pt-BR`
- 日本語 (日本) - `ja-JP`
- 中文 (简体) - `zh-CN`
- العربية (الإمارات) - `ar-AE`
- हिन्दी (भारत) - `hi-IN`

## Supported Countries

- United States (USD, en-US)
- United Kingdom (GBP, en-GB)
- India (INR, en-IN)
- Canada (CAD, en-CA)
- Australia (AUD, en-AU)
- Mexico (MXN, es-MX)
- Brazil (BRL, pt-BR)
- Germany (EUR, de-DE)
- France (EUR, fr-FR)
- Italy (EUR, it-IT)
- Spain (EUR, es-ES)
- Japan (JPY, ja-JP)
- China (CNY, zh-CN)
- United Arab Emirates (AED, en-GB)
- Singapore (SGD, en-GB)
- Bangladesh (BDT, en-GB)
- Pakistan (PKR, en-GB)

## Supported Currencies

- USD - US Dollar ($)
- EUR - Euro (€)
- GBP - British Pound (£)
- INR - Indian Rupee (₹)
- CAD - Canadian Dollar (C$)
- AUD - Australian Dollar (A$)
- JPY - Japanese Yen (¥)
- CNY - Chinese Yuan (¥)
- SGD - Singapore Dollar (S$)
- AED - UAE Dirham (د.إ)
- BDT - Bangladeshi Taka (৳)
- PKR - Pakistani Rupee (₨)
- MXN - Mexican Peso ($)
- BRL - Brazilian Real (R$)

## JavaScript API

Access regional settings programmatically:

```javascript
// Get current settings
const settings = AmadexRegionalSettings.getCurrentSettings();
console.log(settings.language);   // 'en-GB'
console.log(settings.country);    // 'US'
console.log(settings.currency);  // 'USD'

// Open modal programmatically
AmadexRegionalSettings.openModal();

// Close modal programmatically
AmadexRegionalSettings.closeModal();

// Listen for currency changes
$(document).on('amadex:currency-changed', function(event, currency) {
    console.log('Currency changed to:', currency);
    // Update prices, etc.
});
```

## Styling Customization

The modal uses CSS classes that can be customized:

### Main Classes

- `.amadex-regional-settings-btn` - Button styling
- `.amadex-regional-modal` - Modal container
- `.amadex-regional-modal-content` - Modal content box
- `.amadex-regional-field` - Form field container
- `.amadex-regional-select` - Dropdown select styling

### Custom CSS Example

```css
/* Customize modal colors */
.amadex-regional-modal-content {
    background: #f8f9fa;
    border: 2px solid #0E7D3F;
}

/* Customize button */
.amadex-regional-settings-btn {
    background: #0E7D3F;
    color: #ffffff;
}

/* Customize save button */
.amadex-regional-btn-save {
    background: #0E7D3F;
    color: #ffffff;
}
```

## File Structure

```
amadex1/
├── templates/
│   └── regional-settings-modal.php    # Modal HTML template
├── assets/
│   ├── css/
│   │   └── amadex-regional-settings.css  # Modal styles
│   └── js/
│       └── amadex-regional-settings.js    # Modal functionality
├── includes/
│   ├── class-amadex-currency.php         # Currency & location detection
│   ├── amadex-ajax.php                   # AJAX handlers
│   └── frontend/
│       └── class-amadex-shortcodes.php   # Shortcode handler
```

## Technical Details

### IP Detection

- Uses `ipapi.co` free API (1000 requests/day)
- Caches results for 24 hours
- Falls back to default (US/USD) if detection fails

### Storage

- **localStorage**: User preferences persist across sessions
- **sessionStorage**: Current session currency/language
- **WordPress Transients**: IP geolocation cache (24 hours)

### AJAX Endpoints

- `amadex_get_user_location` - Auto-detect user location
- `amadex_get_exchange_rate` - Get currency exchange rates
- `amadex_convert_currency` - Convert currency amounts

## Troubleshooting

### Modal Not Opening

1. Check browser console for JavaScript errors
2. Verify jQuery is loaded
3. Ensure CSS file is enqueued
4. Check for conflicting modal libraries

### Auto-Detection Not Working

1. Check IP geolocation API availability
2. Verify server can make external HTTP requests
3. Check browser console for AJAX errors
4. Fallback to manual selection

### Currency Not Updating

1. Verify currency conversion is enabled
2. Check exchange rate API availability
3. Verify localStorage is not blocked
4. Check browser console for errors

## Browser Compatibility

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- IE11: ⚠️ Limited support (use polyfills)

## Mobile Responsive

The modal is fully responsive and optimized for:
- Desktop (1024px+)
- Tablet (768px - 1023px)
- Mobile (< 768px)

## Best Practices

1. **Place Button in Header**: Most visible location for users
2. **Auto-Detect on First Visit**: Improves user experience
3. **Save User Preferences**: Reduces friction on return visits
4. **Show Currency Symbol**: Makes selection clearer
5. **Mobile-Friendly**: Ensure button is accessible on mobile

## Support

For issues or questions:
1. Check browser console for errors
2. Verify all files are properly enqueued
3. Test IP detection API availability
4. Review WordPress debug log

## Changelog

### Version 1.0.0
- Initial release
- Auto-detection via IP
- Modal interface
- 15+ languages
- 14+ currencies
- 17+ countries
