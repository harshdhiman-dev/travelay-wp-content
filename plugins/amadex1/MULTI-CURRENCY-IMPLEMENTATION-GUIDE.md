# Multi-Currency Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Architecture](#architecture)
4. [Installation & Setup](#installation--setup)
5. [Configuration](#configuration)
6. [How It Works](#how-it-works)
7. [Technical Implementation](#technical-implementation)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Overview

The Amadex plugin now supports **multi-currency functionality**, allowing customers to view flight prices in their preferred currency while ensuring all payments are processed in USD (as required by NMI payment gateway).

### Key Requirements
- **NMI Limitation**: NMI only accepts USD payments
- **Customer Experience**: Customers can view and confirm bookings in any supported currency
- **Backend Processing**: Automatic conversion to USD at payment time
- **Transparency**: Exchange rates and USD equivalents are displayed to customers

---

## Features

### ✅ Implemented Features

1. **Currency Selection**
   - Dropdown selector on booking page
   - 15+ supported currencies
   - Real-time price conversion
   - Session-based currency persistence

2. **Automatic Exchange Rates**
   - Fetches rates from exchangerate-api.com (free tier)
   - 24-hour caching for performance
   - Automatic fallback to manual rates

3. **Payment Processing**
   - Automatic USD conversion before NMI submission
   - Conversion details stored in booking records
   - Full audit trail of exchange rates used

4. **Display Consistency**
   - Booking page shows selected currency
   - Confirmation page shows selected currency
   - Email templates show selected currency
   - USD equivalent displayed for transparency

5. **Admin Controls**
   - Default currency setting
   - Manual rate overrides
   - Easy configuration interface

---

## Architecture

### Component Structure

```
amadex1/
├── includes/
│   ├── class-amadex-currency.php          # Core currency service
│   ├── class-amadex-pricing.php           # Price breakdown with currency
│   ├── frontend/
│   │   └── class-amadex-shortcodes.php   # UI & display logic
│   ├── admin/
│   │   └── class-amadex-settings.php     # Admin settings
│   └── amadex-ajax.php                   # AJAX handlers & emails
├── assets/
│   ├── js/
│   │   └── amadex-booking.js             # Frontend currency conversion
│   └── css/
│       └── amadex-booking.css            # Currency selector styles
└── amadex.php                            # Main plugin file
```

### Data Flow

```
1. Customer selects currency
   ↓
2. JavaScript converts prices (real-time)
   ↓
3. Booking submitted with selected_currency
   ↓
4. Backend converts to USD for NMI
   ↓
5. Conversion details stored in flight_data
   ↓
6. Confirmation/Email shows selected currency
```

---

## Installation & Setup

### Prerequisites

- WordPress 5.0+
- PHP 7.4+
- Amadex plugin installed and activated

### Installation Steps

1. **Ensure Currency Class is Loaded**
   - Verify `class-amadex-currency.php` is included in `amadex.php`
   - Check that the file exists in `includes/` directory

2. **Clear Cache**
   - Clear WordPress cache
   - Clear browser cache
   - Clear any CDN cache

3. **Verify Files**
   - All files listed in "Architecture" section should be present
   - Check file permissions (644 for files, 755 for directories)

---

## Configuration

### Admin Settings

Navigate to: **WordPress Admin → Amadex → Settings → Currency Conversion Settings**

#### 1. Default Currency

**Setting**: `Default Currency`

- **Purpose**: Sets the default currency shown to customers
- **Options**: All supported currencies (USD, EUR, GBP, INR, etc.)
- **Default**: USD
- **Location**: Dropdown selector

**How to Configure:**
1. Go to Amadex Settings
2. Find "Currency Conversion Settings" section
3. Select desired default currency
4. Click "Save Changes"

#### 2. Manual Exchange Rate Overrides

**Setting**: `Manual Exchange Rate Overrides`

- **Purpose**: Override automatic API rates with custom rates
- **When to Use**: 
  - API is unavailable
  - You want to use fixed rates
  - You need to apply markup to rates
- **Format**: Rate to convert FROM currency TO USD

**Supported Currency Pairs:**
- EUR → USD
- GBP → USD
- INR → USD
- CAD → USD
- AUD → USD
- JPY → USD
- CNY → USD
- SGD → USD
- AED → USD
- BDT → USD
- PKR → USD
- MXN → USD
- BRL → USD

**How to Configure:**
1. Go to Amadex Settings → Currency Conversion Settings
2. Scroll to "Manual Exchange Rate Overrides" section
3. Enter rate for desired currency pair (e.g., `1.08` for EUR → USD)
4. Leave empty to use automatic API rates
5. Click "Save Changes"

**Example:**
- EUR → USD: `1.08` (means 1 EUR = 1.08 USD)
- GBP → USD: `1.27` (means 1 GBP = 1.27 USD)
- INR → USD: `0.012` (means 1 INR = 0.012 USD)

---

## How It Works

### Customer Journey

#### Step 1: Currency Selection
1. Customer visits booking page
2. Sees currency selector in sidebar (above price summary)
3. Selects preferred currency from dropdown
4. Prices instantly convert to selected currency

#### Step 2: Booking Process
1. Customer reviews prices in selected currency
2. Fills out passenger and payment details
3. Clicks "Confirm & Book"
4. Selected currency is stored in booking data

#### Step 3: Payment Processing
1. System receives booking with selected currency
2. **Backend automatically converts amount to USD**
3. USD amount sent to NMI payment gateway
4. Conversion details stored in booking record

#### Step 4: Confirmation
1. Customer sees confirmation page
2. Prices displayed in selected currency
3. Note shows USD equivalent and exchange rate
4. Email sent with same currency display

### Technical Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (JavaScript)                     │
├─────────────────────────────────────────────────────────────┤
│ 1. Currency selector change event                          │
│ 2. Fetch exchange rate via AJAX                             │
│ 3. Convert all prices: base, taxes, seats, premium          │
│ 4. Update price breakdown display                           │
│ 5. Store selected currency in sessionStorage               │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND (PHP)                            │
├─────────────────────────────────────────────────────────────┤
│ 1. Receive booking_data with selected_currency              │
│ 2. Get exchange rate (API or manual)                        │
│ 3. Convert display_amount to USD:                           │
│    usd_amount = display_amount * exchange_rate              │
│ 4. Store conversion details in flight_data:                │
│    - display_currency                                       │
│    - display_amount                                        │
│    - usd_amount                                            │
│    - exchange_rate                                         │
│ 5. Send USD amount to NMI                                  │
│ 6. Store USD amount in database (for NMI compatibility)    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│              CONFIRMATION PAGE & EMAILS                    │
├─────────────────────────────────────────────────────────────┤
│ 1. Retrieve booking with conversion details                 │
│ 2. Convert USD amounts back to display currency:            │
│    display_amount = usd_amount / exchange_rate              │
│ 3. Display prices in selected currency                      │
│ 4. Show USD equivalent note                                │
└─────────────────────────────────────────────────────────────┘
```

---

## Technical Implementation

### Core Classes

#### 1. `Amadex_Currency` Class

**Location**: `includes/class-amadex-currency.php`

**Purpose**: Central currency conversion service

**Key Methods:**

```php
// Get supported currencies
Amadex_Currency::get_supported_currencies()

// Get currency symbol
Amadex_Currency::get_currency_symbol($currency_code)

// Get exchange rate
Amadex_Currency::get_exchange_rate($from_currency, $to_currency)

// Convert amount
Amadex_Currency::convert($amount, $from_currency, $to_currency)

// Convert to USD (for NMI)
Amadex_Currency::convert_to_usd($amount, $from_currency)

// Format currency
Amadex_Currency::format($amount, $currency_code, $show_symbol = true)

// Get default currency
Amadex_Currency::get_default_currency()

// Validate currency
Amadex_Currency::is_valid_currency($currency_code)
```

**Exchange Rate Priority:**
1. Cached rate (24-hour cache)
2. API fetch (exchangerate-api.com)
3. Manual rates from settings
4. Default rate (1.0 if all fail)

#### 2. `Amadex_Pricing::get_unified_price_breakdown()`

**Location**: `includes/class-amadex-pricing.php`

**Purpose**: Returns price breakdown in selected currency

**Returns:**
```php
array(
    'base_fare' => float,           // In display currency
    'taxes' => float,               // In display currency
    'premium_service' => float,     // In display currency
    'seat_selection' => float,      // In display currency
    'total' => float,               // In display currency
    'currency' => string,           // Display currency code
    'original_currency' => 'USD',   // Always USD (for NMI)
    'exchange_rate' => float        // Rate used for conversion
)
```

**How It Works:**
1. Retrieves stored USD total from booking
2. Checks `flight_data['currency_conversion']` for display currency
3. Converts USD amounts back to display currency
4. Returns all amounts in display currency

### Frontend JavaScript

#### Currency Conversion Handler

**Location**: `assets/js/amadex-booking.js`

**Key Functions:**

```javascript
// Get selected currency
getSelectedCurrency()

// Get exchange rate (with caching)
getExchangeRate(fromCurrency, toCurrency)

// Convert to selected currency
convertToSelectedCurrency(amount, originalCurrency)
```

**Event Handlers:**
- Currency selector change → Updates all prices
- Price breakdown population → Uses selected currency
- Booking submission → Includes selected currency

### Database Storage

#### Booking Record Structure

```php
// Database stores USD amount (for NMI compatibility)
$booking = array(
    'total_amount' => 641.43,  // USD amount
    'currency' => 'USD',        // Always USD
    'flight_data' => array(
        'currency_conversion' => array(
            'display_currency' => 'EUR',
            'display_amount' => 593.45,
            'usd_amount' => 641.43,
            'exchange_rate' => 1.0808,
            'conversion_date' => '2024-01-15 10:30:00'
        )
    )
)
```

---

## API Reference

### AJAX Endpoints

#### 1. Get Exchange Rate

**Endpoint**: `amadex_get_exchange_rate`

**Request:**
```javascript
$.ajax({
    url: AmadexConfig.ajaxUrl,
    type: 'POST',
    data: {
        action: 'amadex_get_exchange_rate',
        nonce: AmadexCurrency.nonce,
        from_currency: 'EUR',
        to_currency: 'USD'
    }
})
```

**Response:**
```json
{
    "success": true,
    "data": {
        "rate": 1.0808,
        "from_currency": "EUR",
        "to_currency": "USD"
    }
}
```

#### 2. Convert Currency

**Endpoint**: `amadex_convert_currency`

**Request:**
```javascript
$.ajax({
    url: AmadexConfig.ajaxUrl,
    type: 'POST',
    data: {
        action: 'amadex_convert_currency',
        nonce: AmadexCurrency.nonce,
        amount: 593.45,
        from_currency: 'EUR',
        to_currency: 'USD'
    }
})
```

**Response:**
```json
{
    "success": true,
    "data": {
        "amount": 641.43,
        "original_amount": 593.45,
        "from_currency": "EUR",
        "to_currency": "USD",
        "exchange_rate": 1.0808,
        "formatted": "$641.43"
    }
}
```

### PHP Functions

#### Convert to USD (for NMI)

```php
$conversion_result = Amadex_Currency::convert_to_usd($amount, $from_currency);

// Returns:
array(
    'amount' => 641.43,              // USD amount
    'original_amount' => 593.45,      // Original amount
    'currency' => 'USD',              // Always USD
    'original_currency' => 'EUR',     // Original currency
    'exchange_rate' => 1.0808         // Rate used
)
```

#### Format Currency

```php
$formatted = Amadex_Currency::format(593.45, 'EUR');
// Returns: "593.45 €"

$formatted = Amadex_Currency::format(641.43, 'USD');
// Returns: "$641.43"
```

---

## Troubleshooting

### Common Issues

#### 1. Currency Not Converting

**Symptoms**: Prices remain in USD when currency is changed

**Solutions**:
- Check browser console for JavaScript errors
- Verify `AmadexCurrency` object is loaded
- Check AJAX endpoint is accessible
- Verify exchange rate API is responding

**Debug Steps**:
```javascript
// In browser console
console.log(window.AmadexCurrency);
console.log(currentSelectedCurrency);
console.log(exchangeRates);
```

#### 2. Exchange Rate Not Fetching

**Symptoms**: Rates show as 1.0 or prices don't convert correctly

**Solutions**:
- Check internet connection (API requires external access)
- Verify exchangerate-api.com is accessible
- Check WordPress `wp_remote_get()` is not blocked
- Use manual rate overrides as fallback

**Debug Steps**:
```php
// Check if API is accessible
$response = wp_remote_get('https://api.exchangerate-api.com/v4/latest/USD');
if (is_wp_error($response)) {
    error_log('Currency API Error: ' . $response->get_error_message());
}
```

#### 3. Payment Amount Mismatch

**Symptoms**: NMI receives wrong amount or payment fails

**Solutions**:
- Verify conversion is happening before NMI call
- Check `convert_to_usd()` is being called
- Ensure USD amount is sent to NMI (not display currency)
- Check conversion logs in error_log

**Debug Steps**:
```php
// Add logging in payment processing
error_log('Currency Conversion:');
error_log('  Display Currency: ' . $display_currency);
error_log('  Display Amount: ' . $display_amount);
error_log('  USD Amount: ' . $usd_amount);
error_log('  Exchange Rate: ' . $exchange_rate);
```

#### 4. Confirmation Page Shows Wrong Currency

**Symptoms**: Confirmation shows USD instead of selected currency

**Solutions**:
- Verify `currency_conversion` exists in `flight_data`
- Check `get_unified_price_breakdown()` is converting correctly
- Ensure exchange rate is stored correctly
- Check database for conversion details

**Debug Steps**:
```php
// Check booking data
$flight_data = json_decode($booking['flight_data'], true);
error_log('Currency Conversion Data: ' . print_r($flight_data['currency_conversion'], true));
```

### Error Logging

Enable WordPress debug logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

### Testing Checklist

- [ ] Currency selector appears on booking page
- [ ] Prices convert when currency is changed
- [ ] Selected currency persists in session
- [ ] Booking submission includes selected currency
- [ ] Payment is processed in USD
- [ ] Conversion details are stored correctly
- [ ] Confirmation page shows selected currency
- [ ] Email shows selected currency
- [ ] USD equivalent note appears
- [ ] Exchange rate is displayed correctly

---

## Best Practices

### 1. Exchange Rate Management

**Recommended:**
- Use automatic API rates for accuracy
- Set manual overrides only when necessary
- Update manual rates regularly (monthly)
- Monitor exchange rate API status

**Avoid:**
- Hardcoding exchange rates in code
- Using outdated manual rates
- Relying solely on manual rates

### 2. Currency Display

**Recommended:**
- Always show currency symbol with amounts
- Display USD equivalent for transparency
- Show exchange rate used
- Use consistent formatting across pages

**Format Examples:**
- USD: `$641.43`
- EUR: `593.45 €`
- GBP: `505.12 £`
- INR: `53,234.56 ₹`

### 3. Error Handling

**Recommended:**
- Always have fallback rates
- Log conversion errors
- Show user-friendly error messages
- Default to USD if conversion fails

**Error Handling Pattern:**
```php
try {
    $conversion = Amadex_Currency::convert_to_usd($amount, $currency);
    $usd_amount = $conversion['amount'];
} catch (Exception $e) {
    error_log('Currency conversion failed: ' . $e->getMessage());
    // Fallback: use amount as-is (assume it's already USD)
    $usd_amount = $amount;
}
```

### 4. Performance

**Optimizations:**
- Exchange rates cached for 24 hours
- Rates fetched only when needed
- JavaScript caches rates in memory
- Minimal API calls

**Cache Strategy:**
- WordPress transients (24 hours)
- JavaScript memory cache (session)
- Manual rates as permanent fallback

### 5. Security

**Considerations:**
- Validate currency codes before conversion
- Sanitize all user inputs
- Verify nonces for AJAX requests
- Log conversion attempts for audit

---

## Supported Currencies

| Code | Currency Name | Symbol |
|------|--------------|--------|
| USD | US Dollar | $ |
| EUR | Euro | € |
| GBP | British Pound | £ |
| INR | Indian Rupee | ₹ |
| CAD | Canadian Dollar | C$ |
| AUD | Australian Dollar | A$ |
| JPY | Japanese Yen | ¥ |
| CNY | Chinese Yuan | ¥ |
| SGD | Singapore Dollar | S$ |
| AED | UAE Dirham | د.إ |
| BDT | Bangladeshi Taka | ৳ |
| PKR | Pakistani Rupee | ₨ |
| MXN | Mexican Peso | $ |
| BRL | Brazilian Real | R$ |

---

## Exchange Rate API

### Provider: exchangerate-api.com

**Free Tier Limits:**
- 1,500 requests/month
- No API key required
- Real-time rates
- Updates daily

**API Endpoint:**
```
https://api.exchangerate-api.com/v4/latest/{BASE_CURRENCY}
```

**Example Response:**
```json
{
    "base": "EUR",
    "date": "2024-01-15",
    "rates": {
        "USD": 1.0808,
        "GBP": 0.8512,
        "INR": 89.65
    }
}
```

**Fallback Strategy:**
1. Try API (exchangerate-api.com)
2. Use cached rate (24 hours)
3. Use manual rate from settings
4. Default to 1.0 (no conversion)

---

## Code Examples

### Example 1: Convert Price in PHP

```php
// Convert EUR 500 to USD
$conversion = Amadex_Currency::convert_to_usd(500, 'EUR');

echo "EUR 500 = USD " . $conversion['amount'];
// Output: EUR 500 = USD 540.40

// Format for display
echo Amadex_Currency::format($conversion['amount'], 'USD');
// Output: $540.40
```

### Example 2: Display Price in Selected Currency

```php
// Get price breakdown
$breakdown = Amadex_Pricing::get_unified_price_breakdown($booking);

// Display
echo Amadex_Currency::format($breakdown['total'], $breakdown['currency']);
// Output: 593.45 € (if EUR selected)
```

### Example 3: JavaScript Currency Conversion

```javascript
// Get exchange rate
const rate = await getExchangeRate('EUR', 'USD');

// Convert amount
const usdAmount = eurAmount * rate;

// Format for display
const formatted = formatCurrencyValue(usdAmount, 'USD');
// Output: $641.43
```

### Example 4: Add Custom Currency

To add a new currency, edit `class-amadex-currency.php`:

```php
private static $supported_currencies = array(
    // ... existing currencies ...
    'NZD' => array('name' => 'New Zealand Dollar', 'symbol' => 'NZ$'),
);
```

Then add manual rate override in admin settings.

---

## Maintenance

### Regular Tasks

1. **Monthly**: Review and update manual exchange rates
2. **Weekly**: Check exchange rate API status
3. **Daily**: Monitor conversion logs for errors
4. **As Needed**: Update currency symbols or names

### Monitoring

**Key Metrics to Track:**
- Exchange rate API success rate
- Conversion accuracy
- Payment processing success rate
- Customer currency preferences

**Log Locations:**
- WordPress: `wp-content/debug.log`
- Server: Check server error logs
- NMI: Check NMI dashboard for payment amounts

---

## Support & Updates

### Getting Help

1. Check this documentation first
2. Review error logs
3. Test with different currencies
4. Verify API connectivity

### Future Enhancements

Potential improvements:
- More currency support
- Custom exchange rate providers
- Currency conversion history
- Multi-currency reporting
- Automatic rate updates via cron

---

## Changelog

### Version 1.0.0 (Initial Release)
- ✅ Multi-currency support
- ✅ Automatic exchange rate fetching
- ✅ Manual rate overrides
- ✅ USD conversion for NMI
- ✅ Confirmation page currency display
- ✅ Email currency display
- ✅ Admin settings interface

---

## License

This implementation is part of the Amadex plugin and follows the same license terms.

---

**Document Version**: 1.0.0  
**Last Updated**: January 2024  
**Author**: Amadex Development Team
