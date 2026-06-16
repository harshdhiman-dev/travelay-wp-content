# 🔧 Fix 500 Error - PaymentIntent Creation

## ❌ Current Error
```
POST https://travelaystagging.com/wp-admin/admin-ajax.php 500 (Internal Server Error)
Payment Error: Failed to initialize payment. Please try again.
```

## 🎯 Root Cause

The 500 error means a **PHP fatal error** is happening on the server. Most likely causes:

1. **Stripe library files not copied yet** (most common)
2. Missing required PHP files in the library
3. PHP syntax error in loaded files

## ✅ Solution Steps

### Step 1: Copy Stripe Library Files (CRITICAL)

**You MUST copy the library files first!**

```cmd
cd "C:\Users\DELL\Local Sites\travelay\app\16 jan amadex plugin working stripe\amadex1 (3) seat working (3)"
xcopy /E /I /Y "stripe-php-master\lib\*" "includes\vendor\stripe\stripe-php\lib\"
xcopy /E /I /Y "stripe-php-master\data\*" "includes\vendor\stripe\stripe-php\data\"
```

**OR use File Explorer:**
1. Open `stripe-php-master/lib/`
2. Select ALL files and folders
3. Copy (Ctrl+C)
4. Go to `includes/vendor/stripe/stripe-php/`
5. Create `lib` folder if needed
6. Paste (Ctrl+V)
7. Repeat for `data/` folder

### Step 2: Verify Files Are Copied

Check if these files exist:
```
includes/vendor/stripe/stripe-php/lib/ApiResource.php
includes/vendor/stripe/stripe-php/lib/PaymentIntent.php
includes/vendor/stripe/stripe-php/lib/Util/Util.php
```

### Step 3: Test Library Installation

Run the diagnostic script:
```
https://your-site.com/wp-content/plugins/amadex/test-stripe-library.php?test=stripe
```

This will tell you exactly what's missing.

### Step 4: Check PHP Error Logs

If files are copied but still getting 500 error, check your PHP error log:

**Common locations:**
- `wp-content/debug.log` (if WP_DEBUG is enabled)
- Server error logs (check your hosting panel)
- `php_error.log` in plugin directory

**Enable WordPress Debug:**
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `wp-content/debug.log` for the exact error.

## 🔍 What I Fixed

1. **Enhanced error handling** in AJAX handler
   - Better error messages
   - Checks if library is loaded before use
   - Catches Stripe-specific exceptions

2. **Fixed PaymentIntent creation**
   - Removed deprecated `confirmation_method` parameter
   - Using `confirm: false` instead
   - Proper idempotency key handling

3. **Added diagnostic script**
   - `test-stripe-library.php` - Run this to check installation

## 📋 Checklist

Before testing again:
- [ ] Copied `stripe-php-master/lib/*` → `includes/vendor/stripe/stripe-php/lib/`
- [ ] Copied `stripe-php-master/data/*` → `includes/vendor/stripe/stripe-php/data/`
- [ ] Verified `ApiResource.php` exists
- [ ] Verified `PaymentIntent.php` exists
- [ ] Verified `Util/Util.php` exists
- [ ] Stripe Secret Key is configured in settings
- [ ] Tested with diagnostic script

## 🧪 Testing

After copying files:

1. **Test library installation:**
   ```
   https://your-site.com/wp-content/plugins/amadex/test-stripe-library.php?test=stripe
   ```
   Should show: ✅ Stripe library is properly installed!

2. **Test booking form:**
   - Fill in card details
   - Click "Confirm & Book"
   - Should create PaymentIntent successfully
   - Should redirect to confirmation page

## 🆘 Still Getting 500 Error?

If you've copied all files and still get 500:

1. **Check PHP error log** - Look for fatal errors
2. **Check file permissions** - PHP needs read access
3. **Check PHP version** - Stripe requires PHP 7.4+
4. **Check memory limit** - May need 128MB+

**Common PHP errors:**
- `Class 'Stripe\ApiResource' not found` → Files not copied
- `Call to undefined method` → Wrong library version
- `Parse error` → Corrupted file during copy

## ✅ Expected Success Flow

1. PaymentMethod created: `pm_1SqEanHCJiMrC3QJgcYUHov3` ✅
2. AJAX call to create PaymentIntent ✅
3. PaymentIntent created: `pi_xxxxx` ✅
4. Payment confirmed on frontend ✅
5. Booking submitted ✅
6. Redirect to confirmation page ✅

---

**The most important step is copying the library files!** Without them, you'll always get a 500 error.
