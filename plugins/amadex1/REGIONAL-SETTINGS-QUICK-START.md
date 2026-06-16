# Regional Settings - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Add Button to Header

Add this shortcode to your theme's header.php file (usually in the header section):

```php
<?php echo do_shortcode('[amadex_regional_settings]'); ?>
```

**OR** use the WordPress shortcode in any page/post:

```
[amadex_regional_settings]
```

### Step 2: That's It!

The feature will automatically:
- ✅ Detect user's location via IP
- ✅ Set default currency, language, and country
- ✅ Show modal when button is clicked
- ✅ Save user preferences

## 📍 Button Placement Options

### Option 1: Header (Recommended)
```php
<!-- In header.php, typically in the navigation area -->
<div class="header-right">
    <?php echo do_shortcode('[amadex_regional_settings position="header"]'); ?>
</div>
```

### Option 2: Floating Button
```php
<!-- Anywhere in your theme -->
<?php echo do_shortcode('[amadex_regional_settings position="floating"]'); ?>
```

### Option 3: Inline
```php
<!-- In content area -->
<?php echo do_shortcode('[amadex_regional_settings position="inline"]'); ?>
```

## 🎨 Customization

### Change Button Text/Colors

Add to your theme's `style.css`:

```css
/* Custom button style */
.amadex-regional-settings-btn {
    background: #0E7D3F;
    color: #ffffff;
    border-radius: 12px;
    padding: 10px 16px;
}

/* Custom modal colors */
.amadex-regional-modal-content {
    border: 2px solid #0E7D3F;
}
```

## 🔧 How It Works

1. **First Visit**: System auto-detects location from IP
2. **User Clicks Button**: Modal opens with current settings
3. **User Changes Settings**: Selects language, country, currency
4. **User Saves**: Settings saved and page reloads
5. **Future Visits**: Uses saved preferences

## 📱 Mobile Support

Fully responsive! Works on:
- Desktop ✅
- Tablet ✅
- Mobile ✅

## ❓ Troubleshooting

**Button not showing?**
- Check if shortcode is properly added
- Verify CSS file is loading (check browser console)

**Auto-detection not working?**
- Check server can make external HTTP requests
- Verify IP geolocation API is accessible
- Falls back to US/USD if detection fails

**Modal not opening?**
- Check browser console for JavaScript errors
- Verify jQuery is loaded
- Check for conflicting modal libraries

## 📚 Full Documentation

See `REGIONAL-SETTINGS-GUIDE.md` for complete documentation.

## 🎯 Example Usage

```php
<!-- In WordPress header template -->
<header>
    <div class="site-header">
        <div class="logo">Your Logo</div>
        <nav>Navigation Menu</nav>
        <div class="header-actions">
            <?php echo do_shortcode('[amadex_regional_settings]'); ?>
        </div>
    </div>
</header>
```

## ✅ Features Checklist

- [x] Auto-detect user location
- [x] Modal interface
- [x] Language selection
- [x] Country selection
- [x] Currency selection
- [x] Persistent storage
- [x] Mobile responsive
- [x] Skyscanner-style design

## 🆘 Need Help?

1. Check browser console for errors
2. Verify all plugin files are present
3. Test IP detection API
4. Review WordPress debug log

---

**That's it!** Your regional settings feature is now ready to use! 🎉
