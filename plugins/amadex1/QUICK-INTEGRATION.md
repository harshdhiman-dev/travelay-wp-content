# ⚡ QUICK STRIPE INTEGRATION - 2 MINUTES

## 🎯 What You Need to Do

You have `stripe-php-master/` folder with all files. Just copy them!

### Step 1: Copy Library Files

**Open Command Prompt and run:**

```cmd
cd "C:\Users\DELL\Local Sites\travelay\app\16 jan amadex plugin working stripe\amadex1 (3) seat working (3)"
xcopy /E /I /Y "stripe-php-master\lib\*" "includes\vendor\stripe\stripe-php\lib\"
xcopy /E /I /Y "stripe-php-master\data\*" "includes\vendor\stripe\stripe-php\data\"
```

**OR use File Explorer:**
1. Go to `stripe-php-master/lib/`
2. Copy ALL files and folders
3. Paste into `includes/vendor/stripe/stripe-php/lib/`
4. Go to `stripe-php-master/data/`
5. Copy ALL files
6. Paste into `includes/vendor/stripe/stripe-php/data/`

### Step 2: Verify

Check if this file exists:
```
includes/vendor/stripe/stripe-php/lib/ApiResource.php
```

If YES → ✅ Done! Test your booking form.
If NO → Files not copied correctly, try again.

### Step 3: Test

1. Go to booking form
2. Fill card details
3. Click "Confirm & Book"
4. Should redirect to confirmation page ✅

---

## 🗑️ Clean Up (After Testing Works)

You can delete:
- `stripe-php-master/` folder (no longer needed)
- `install-stripe.php`
- `copy-stripe-library.bat` and `.ps1`
- `VERIFY-STRIPE-FILES.php`

**Keep:**
- `init.php` (already correct)
- `lib/` directory (all Stripe files)
- `data/` directory (CA certificates)

---

## ✅ That's It!

Once files are copied, everything will work automatically!
