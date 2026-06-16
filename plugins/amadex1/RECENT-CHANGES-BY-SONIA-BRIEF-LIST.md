# Recent Changes — Brief List
## Website modifications (Developer: Sonia)

**Compiled:** Current  
**Source:** File modification dates + documentation  
**Note:** No git history available; changes inferred from file dates and docs.

---

## 1. **STRIPE PAYMENT FIXES** (Jan 28–30)

### Stripe 500 Error / Diagnostics
- **Stripe diagnostic tool** (`diagnose-stripe-error.php`) — diagnoses 500 errors in Stripe payments
- **Test scripts** — `test-stripe-ajax.php`, `test-stripe-endpoint.php`, `test-stripe-library.php` for debugging
- **Stripe double-load prevention** — `class-amadex-payment-stripe.php` updated to avoid loading Stripe twice and triggering fatal “class already declared” errors
- **Safe output buffer** — `amadex-ajax.php` adds `amadex_safe_ob_clean()` to avoid JSON/500 issues when output buffers are active

### Payment Page
- **`amadex-payment-page.js`** — updated payment flow, duplicate-booking prevention
- **`amadex-payment-page.css`** — layout and styling updates for payment page

---

## 2. **REGIONAL SETTINGS FIXES** (Jan 31)

- **Default region** — Default changed from en-GB to **USA / USD / en-US**
- **Toggle switch** — Admin option to enable/disable regional settings
- **Persistence** — Currency and regional settings kept across search → results → booking
- **Files:** `class-amadex-currency.php`, `amadex-regional-settings.js`, `regional-settings-modal.php`, `class-amadex-shortcodes.php`, `class-amadex-settings.php`

---

## 3. **PER-PERSON PRICING DISCOUNT FIX** (Jan 31)

- **Issue** — Discounts from Pricing Rules Engine did not show when `travelerPricings` existed
- **Fix** — When Pricing Rules Engine is on, use discounted `price.total` (P_display) instead of original `travelerPricings`
- **File:** `assets/js/amadex.js` — `createFlightElement()` logic updated

---

## 4. **ADMIN SETTINGS** (Jan 29)

- **`class-amadex-settings.php`** — updates to settings tabs and payment options
- **`amadex.php`** — main plugin file changes

---

## 5. **OTHER RECENT FEATURES** (Jan 26–27)

*(May be prior work or overlapping with Sonia’s work.)*

### Addons Fix
- Addons separated from base fare on confirmation page and email
- **Files:** `class-amadex-pricing.php`, `class-amadex-shortcodes.php`, `amadex-ajax.php`

### Duplicate Booking Prevention
- SessionStorage cleared after successful booking
- Redirect when user presses back after confirmation
- **Files:** `amadex-confirmation.js`, `amadex-booking.js`, `amadex-payment-page.js`

### Creative Experience
- Micro-interactions, ripple effects, confetti on confirmation
- “Adventure Awaits” badge and animated total on confirmation
- **Files:** `amadex-creative-experience.css`, `amadex-creative-experience.js`

### Step Elements
- Hero lines, badges, teasers, strips for each booking step
- Popular route chips, “We found your flights” message, secure payment bar
- **Files:** `amadex-step-elements.css`, `amadex-step-elements.js`

---

## Summary Table

| Feature                     | Files Touched      | Status  |
|----------------------------|--------------------|---------|
| Stripe diagnostics & fix   | 5+ files           | Done    |
| Regional settings defaults | 5 files            | Done    |
| Per-person discount fix    | amadex.js          | Done    |
| Admin settings             | 2 files            | Done    |
| Addons fix                 | 3 PHP files        | Done    |
| Duplicate booking fix      | 3 JS files         | Done    |
| Creative experience        | 2 new + shortcodes | Done    |
| Step elements              | 2 new + booking.js | Done    |

---

**Total:** 20+ files modified or added in recent work.
