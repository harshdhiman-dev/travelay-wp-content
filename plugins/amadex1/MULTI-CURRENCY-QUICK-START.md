# Multi-Currency Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Access Settings
1. Go to **WordPress Admin → Amadex → Settings**
2. Click on **"Currency Conversion Settings"** tab

### Step 2: Set Default Currency
1. Find **"Default Currency"** dropdown
2. Select your preferred default currency (e.g., USD, EUR, GBP)
3. Click **"Save Changes"**

### Step 3: (Optional) Set Manual Rates
1. Scroll to **"Manual Exchange Rate Overrides"** section
2. Enter rates for currencies you want to override
3. Leave empty to use automatic API rates
4. Click **"Save Changes"**

### Step 4: Test
1. Visit your booking page
2. Check currency selector appears in sidebar
3. Change currency and verify prices convert
4. Complete a test booking

---

## ✅ Verification Checklist

- [ ] Currency selector visible on booking page
- [ ] Prices convert when currency changes
- [ ] Default currency matches your selection
- [ ] Confirmation page shows selected currency
- [ ] Email shows selected currency
- [ ] Payment processes successfully

---

## 🔧 Common Settings

### Default Currency Options
- **USD** - US Dollar (Recommended for US-based businesses)
- **EUR** - Euro (Recommended for European businesses)
- **GBP** - British Pound (Recommended for UK businesses)
- **INR** - Indian Rupee (Recommended for Indian businesses)

### Manual Rate Examples

**EUR to USD:**
- Rate: `1.08` (means 1 EUR = 1.08 USD)

**GBP to USD:**
- Rate: `1.27` (means 1 GBP = 1.27 USD)

**INR to USD:**
- Rate: `0.012` (means 1 INR = 0.012 USD)

---

## 📋 How It Works (Simple Explanation)

1. **Customer selects currency** → Prices convert instantly
2. **Customer books** → Selected currency is saved
3. **Payment time** → System converts to USD automatically
4. **NMI receives** → USD amount only (as required)
5. **Confirmation** → Shows customer's selected currency

**Key Point**: Customer sees their currency, but payment is always in USD behind the scenes.

---

## 🆘 Quick Troubleshooting

### Prices Not Converting?
- Clear browser cache
- Check JavaScript console for errors
- Verify exchange rate API is accessible

### Wrong Currency on Confirmation?
- Check booking was saved with selected currency
- Verify `currency_conversion` exists in booking data
- Check exchange rate was stored correctly

### Payment Failing?
- Verify amount sent to NMI is in USD
- Check conversion logs in error_log
- Ensure exchange rate is valid (> 0)

---

## 📞 Need More Help?

See the full documentation: `MULTI-CURRENCY-IMPLEMENTATION-GUIDE.md`

---

**Quick Start Version**: 1.0.0
