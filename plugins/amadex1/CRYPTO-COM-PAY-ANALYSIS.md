# Crypto.com Pay – Flow, Fixes & WooCommerce Plugin Analysis

## 1. Why the "Confirm & Book" button was missing on the compact price bar

When the user selected the **Crypto.com** payment tab, the payment-tab switch logic did **not** show the step-next or confirm-book buttons. For **PayPal** it explicitly hides them (and shows the PayPal button instead). For **credit_card** it shows `#amadex-step-next` in the `else` branch. For **crypto_com** it only showed the Crypto.com form and set the payment method; it never called `.show()` on `#amadex-step-next` or `#amadex-confirm-book`.

So:
- If the user had previously selected PayPal, those buttons stayed hidden when switching to Crypto.com.
- On mobile, the compact price bar uses the same `#amadex-step-next` (moved into `#amadex-price-bar-step-next-wrap`), so hiding it removed the only visible “Confirm” action.

**Fix applied:** In the payment-tab handler, when `method === 'crypto_com'` we now:
- `$('#amadex-step-next').show().text('Confirm & Book')`
- `$('#amadex-confirm-book').show()`
- `$('#amadex-payment-submit').hide()`

So when Crypto.com is selected, the compact price bar (and main area) show **Confirm & Book** as intended.

---

## 2. How Crypto.com Pay flow actually works (official docs)

From [Crypto.com Pay for Business](https://pay-docs.crypto.com/):

- **Button SDK:** “Once the button is clicked, there will be a **popup** for customers to complete payment.”
- **Crypto.com App:** “A user can simply use the Crypto.com App to **scan the QR code**, select their preferred payment cryptocurrency and complete the transaction.”

So the intended flow is:

1. **On your site:** User selects Crypto.com and clicks **Confirm & Book**.
2. **Your backend** creates a booking and a Crypto.com payment; frontend receives a `payment_id`.
3. **Your frontend** shows the **Crypto.com Pay Button** (from their SDK) in a container (e.g. `#cryptocom-pay-button-mount`).
4. User clicks that **Crypto.com Pay** button.
5. **SDK opens a popup** (modal). That popup contains the **QR code** and payment details.
6. User either:
   - **Scans the QR code** with the Crypto.com App and completes payment in the app, or
   - Uses the popup (e.g. “Open in app” / in-test “click QR to simulate”).
7. When payment is captured, the popup closes and your `onApprove` runs (e.g. redirect to confirmation).

So:
- The **QR code is not rendered directly on your page**. It appears **inside the popup** that the SDK opens when the user clicks the Crypto.com Pay button.
- Your integration correctly: (1) creates the payment server-side, (2) shows the SDK button, (3) opens the popup (with QR) on button click. No change needed to that flow; the missing piece was only the visibility of **Confirm & Book** when Crypto.com is selected.

---

## 3. WooCommerce plugin: “Crypto.com Pay Checkout for WooCommerce”

**Plugin path:**  
`wp-content/plugins/crypto-com-pay-checkout-for-woocommerce/`

**What it does:**
- Registers a **WooCommerce payment gateway** (WC checkout only).
- Uses the same API base: `https://pay.crypto.com/api/payments/`.
- Uses **Bearer** token in `Authorization` header (e.g. `Authorization: Bearer <secret_key>`).  
  Official Pay docs use **Basic** auth (`secret_key:`). So the plugin may be using an older or alternate auth; if something breaks in WooCommerce with that plugin, that’s a separate issue.
- Hooks: WooCommerce only (`WC_Payment_Gateway`, checkout, blocks, REST webhook at `/wp-json/crypto-pay/v1/webhook`).
- Does **not** load on Amadex booking pages; Amadex does not use WooCommerce checkout.

**Conclusion – interference / coexistence:**
- **No conflict on Amadex:** The WooCommerce plugin only runs on WooCommerce checkout. Amadex flight booking uses its own pages and its own Crypto.com Pay integration (Amadex settings: Crypto.com Pay keys, create payment, Button SDK).
- **Same site, both can be used:**  
  - **WooCommerce:** Use “Crypto.com Pay Checkout for WooCommerce” (and its keys in that plugin’s settings) for product checkout.  
  - **Amadex:** Use the built-in Crypto.com Pay integration (Amadex Payment Settings → Crypto.com Pay keys) for flight booking.
- **Shared global:** Both can load the same SDK script (`https://js.crypto.com/sdk?publishable-key=...`). They would share the global `cryptopay` if both were on one page. Amadex only loads the SDK on the booking page after creating a payment; WooCommerce loads on its checkout. So on normal flows they don’t run on the same page; no practical conflict.
- **Recommendation:** Keep both if you need Crypto.com on WooCommerce and on Amadex. Use **one set of keys per context** (WooCommerce plugin settings vs Amadex Payment Settings). If you only need Crypto.com on flight booking, you can disable the WooCommerce plugin to avoid any future confusion.

---

## 4. Summary of changes made

- **amadex-booking.js (payment tab handler):**  
  When the user selects the **Crypto.com** tab, we now:
  - Show `#amadex-step-next` and set its text to **Confirm & Book**.
  - Show `#amadex-confirm-book`, hide `#amadex-payment-submit`.

Result:
- The **Confirm & Book** button appears on the compact price bar (and in the main area) when Crypto.com is selected.
- Flow stays correct: Confirm & Book → create booking + payment → show Crypto.com Pay button → user clicks it → **popup with QR** opens → user scans with app or completes in popup → redirect to confirmation.
