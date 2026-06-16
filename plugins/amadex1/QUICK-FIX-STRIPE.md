# ⚡ Quick Fix: Stripe Library Installation

## ✅ What You Have
- `Stripe.php` file ✓
- Plugin code ready ✓

## ❌ What's Missing
- Complete Stripe PHP library (needs PaymentIntent, ApiResource, etc.)

## 🚀 FASTEST SOLUTION (Choose One)

### Option A: Download Complete Library (5 minutes)

1. **Download the full Stripe PHP library:**
   - Direct link: https://github.com/stripe/stripe-php/archive/refs/heads/master.zip
   - Or visit: https://github.com/stripe/stripe-php

2. **Extract the ZIP file** (you'll get `stripe-php-master` folder)

3. **Copy entire folder to:**
   ```
   amadex1 (3) seat working (3)/
   └── includes/
       └── vendor/
           └── stripe/
               └── stripe-php/
   ```

4. **Rename `stripe-php-master` to `stripe-php`**

5. **Verify this file exists:**
   ```
   includes/vendor/stripe/stripe-php/init.php
   ```

### Option B: Use Composer (2 minutes)

```bash
cd "C:\Users\DELL\Local Sites\travelay\app\16 jan amadex plugin working stripe\amadex1 (3) seat working (3)"
composer require stripe/stripe-php
```

## ✅ Verification

After installation, test your booking form. The error "Stripe PHP library is not installed" should disappear.

## 📁 Required File Structure

Your plugin should have:
```
amadex1 (3) seat working (3)/
└── includes/
    └── vendor/
        └── stripe/
            └── stripe-php/
                ├── init.php          ← Must exist
                ├── Stripe.php        ← You have this
                ├── PaymentIntent.php ← Required
                ├── ApiResource.php   ← Required
                └── (many other files)
```

## 🔍 Quick Check

Open in browser console (F12) and test booking:
- If error gone → ✅ Success!
- If still error → Check file path and permissions

## ⚠️ Important

**Just having `Stripe.php` is not enough!** You need the complete library with:
- PaymentIntent.php
- ApiResource.php  
- All other Stripe classes

That's why you need to download the complete library from GitHub.
