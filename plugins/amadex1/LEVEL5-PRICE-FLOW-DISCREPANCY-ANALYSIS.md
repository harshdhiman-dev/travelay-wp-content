# Level 5 Deep Analysis: Price Flow Discrepancies
## Based on Actual Fresh Booking Data

**Date:** Deep analysis with actual booking data  
**Scope:** Price discrepancies between flight results, booking page, and confirmation page  
**Status:** ✅ **ROOT CAUSE IDENTIFIED** - No code changes, analysis only

---

## Executive Summary

**Actual Booking Data:**
- **Flight Results Page:** $1,120.62 per person ✅ (Correct)
- **Booking Page Total:** $2,296.23 ✅ (Correct: P_display + Addons)
- **Confirmation Page Total:** $3,045.26 ❌ (Wrong: Missing addons in sum)

**Critical Finding:**
- Confirmation page shows addons ($55) in the list but **NOT included in the total**
- Base + Taxes = $3,045.26 (this is P_charge, which should be $2,990.26)
- The $55 addons are **absorbed into base/taxes** instead of being shown separately
- **Expected Total:** $3,045.26 + $55.00 = **$3,100.26** ✅

**Root Cause:**
- `get_unified_price_breakdown()` function doesn't subtract addons from `$base_total`
- Addons are stored in `flight_data['addons']` but function doesn't read them
- Function calculates base/taxes from inflated `$base_total` (which includes addons)
- Verification formula doesn't include addons

---

## Part 1: Actual Price Flow Analysis

### 1.1 Flight Results Page

**Price Shown:** $1,120.62 per person

**Calculation:**
- For 2 adults: $1,120.62 × 2 = $2,241.24
- This is **P_display** (price after discount, before flat fee)

**Status:** ✅ **CORRECT** - Matches pricing rules engine logic

### 1.2 Booking Page Price Summary

**Breakdown:**
```
Base Fare (2 Adults):     $736.20
Taxes & Fees:             $1,505.03
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total Amount:             $2,296.23
```

**Verification:**
- Base + Taxes = $736.20 + $1,505.03 = **$2,241.23** ✅ (This is P_display)
- Addons = $25.00 + $30.00 = **$55.00** ✅
- Total = $2,241.23 + $55.00 = **$2,296.23** ✅

**Status:** ✅ **CORRECT** - Booking page correctly shows P_display + Addons

### 1.3 Confirmation Page Price Summary

**Breakdown:**
```
Payment Method:           CREDIT CARD
Base Fare (2 Adults):     $1,000.31
Taxes & Fees:             $2,044.95
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total Amount:             $3,045.26
```

**Verification:**
- Base + Taxes = $1,000.31 + $2,044.95 = **$3,045.26** ❌
- Addons listed: $25.00 + $30.00 = **$55.00** ✅
- **But Total = $3,045.26 (does NOT include addons!)** ❌

**Expected Total:**
- Base + Taxes (P_charge) = $2,990.26 (should be, but showing $3,045.26)
- Addons = $55.00
- **Expected Total = $2,990.26 + $55.00 = $3,045.26** ✅
- **OR if addons absorbed: $3,045.26 + $55.00 = $3,100.26** ✅

**Status:** ❌ **WRONG** - Addons are displayed but NOT included in total sum

---

## Part 2: The Math Behind the Discrepancy

### 2.1 Pricing Rules Engine Calculation

**From Pricing Rules:**
- B_markup = Original price after markup
- P_display = B_markup × (1 - discount%) = $2,241.23
- P_charge = B_markup + flat_fee = $2,990.26

**Working Backwards:**
- P_display = $2,241.23
- Discount = 10%
- B_markup = $2,241.23 ÷ 0.90 = **$2,490.26**
- P_charge = $2,490.26 + flat_fee
- If P_charge = $2,990.26, then flat_fee = **$500.00** ✅

### 2.2 What Should Be on Confirmation Page

**Expected Breakdown:**
```
Base Fare:                $X (from P_charge split)
Taxes:                    $Y (from P_charge split)
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total:                    $2,990.26 + $55.00 = $3,045.26
```

**OR (if addons absorbed into base/taxes):**
```
Base Fare:                $X (includes addons)
Taxes:                    $Y (includes addons)
TravelayGent™:            $25.00 (listed but in base/taxes)
TravelaySurance™:         $30.00 (listed but in base/taxes)
─────────────────────────────────
Total:                    $3,045.26 (but should show $3,100.26)
```

### 2.3 What Actually Shows on Confirmation Page

**Actual Breakdown:**
```
Base Fare:                $1,000.31
Taxes:                    $2,044.95
TravelayGent™:            $25.00 (listed but NOT in sum)
TravelaySurance™:         $30.00 (listed but NOT in sum)
─────────────────────────────────
Total:                    $3,045.26 (Base + Taxes only)
```

**The Problem:**
- Base + Taxes = $3,045.26 (this is P_charge + addons absorbed)
- Addons are listed but NOT added to total
- **Total should be: $3,045.26 + $55.00 = $3,100.26** ✅

**The Math:**
- If Base + Taxes = $3,045.26 and this includes addons:
  - P_charge = $3,045.26 - $55.00 = **$2,990.26** ✅
  - But addons are shown separately, so total should be $3,045.26 + $55.00 = **$3,100.26**

---

## Part 3: Code Flow Analysis

### 3.1 Backend Processing (process_booking)

**File:** `includes/amadex-ajax.php`

**Step 1: Get P_charge (Line 1220-1226)**
```php
$pricing_charge_total = floatval($flight_data['price']['pricing_charge_total'] ?? 0);
// $pricing_charge_total = 2990.26 ✅

if ($pricing_charge_total > 0) {
    $total_amount = $pricing_charge_total;
    // $total_amount = 2990.26 ✅
}
```

**Step 2: Process Addons (Line 1308-1328)**
```php
$addons_total = 0;
foreach ($booking_data['addons'] as $addon) {
    $addons_total += $addon_price;
    // TravelaySurance: $30.00
    // TravelayGent: $25.00
    // $addons_total = 55.00 ✅
}
```

**Step 3: Add Addons to Total (Line 1364)**
```php
if ($addons_total > 0) {
    $total_amount = $total_amount + $addons_total;
    // $total_amount = 2990.26 + 55.00 = 3045.26 ✅
}
```

**Step 4: Store in Database (Line 1600)**
```php
$booking_result = $database->create_booking(array(
    'total_amount' => $total_amount_usd, // = 3045.26 ✅
    'flight_data' => $flight_data, // Contains addons array ✅
));
```

**Status:** ✅ **CORRECT** - Backend correctly calculates and stores $3,045.26

### 3.2 Confirmation Page Calculation (get_unified_price_breakdown)

**File:** `includes/class-amadex-pricing.php`

**Step 1: Get Stored Total (Line 321)**
```php
$stored_total = floatval($booking['total_amount'] ?? 0);
// $stored_total = 3045.26 ✅ (includes addons)
```

**Step 2: Get Flight Data (Line 334)**
```php
$flight_data = $booking['flight_data'];
// Contains: flight_data['addons'] = [TravelaySurance $30, TravelayGent $25] ✅
```

**Step 3: Calculate Base Total (Line 468)**
```php
$base_total = $stored_total; // = 3045.26
if ($premium_service_added) {
    $base_total = $base_total - $premium_service_amount;
}
if ($seat_charges > 0) {
    $base_total = $base_total - $seat_charges;
}
// ❌ MISSING: Does NOT subtract addons_total!
// So $base_total = 3045.26 (should be 2990.26 after subtracting 55 addons)
```

**Problem:** Function doesn't check for `flight_data['addons']` array!

**Step 4: Split Base Total into Base/Taxes (Line 547-548)**
```php
$final_base = round($base_total * $base_ratio, 2);
// $final_base = 3045.26 × base_ratio = 1000.31 ✅
$final_taxes = round($base_total - $final_base, 2);
// $final_taxes = 3045.26 - 1000.31 = 2044.95 ✅
```

**Problem:** Uses inflated `$base_total` (includes addons), so base/taxes are inflated!

**Step 5: Verification Formula (Line 569)**
```php
$calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges;
// = 1000.31 + 2044.95 + 0 + 0 = 3045.26 ✅
// ❌ MISSING: Does NOT include addons_total in formula!
```

**Problem:** Verification formula doesn't include addons, so it matches `$stored_total` by accident (because addons are absorbed into base/taxes).

**Step 6: Return Breakdown (Line 591-602)**
```php
return array(
    'base_fare' => $convert_to_display($final_base), // = 1000.31 ✅
    'taxes' => $convert_to_display($final_taxes), // = 2044.95 ✅
    'premium_service' => ..., // = 0 (not used)
    'seat_selection' => ..., // = 0 (no seats)
    'total' => $convert_to_display($stored_total), // = 3045.26 ✅
    // ❌ MISSING: No 'addons' field in return array!
);
```

**Problem:** Function doesn't return addons in breakdown array, so frontend can't display them correctly!

---

## Part 4: The Exact Discrepancy Points

### 4.1 Discrepancy #1: Addons Not Subtracted from base_total

**Location:** `includes/class-amadex-pricing.php`, line 468

**Current Code:**
```php
$base_total = $stored_total; // = 3045.26
if ($premium_service_added) {
    $base_total = $base_total - $premium_service_amount;
}
if ($seat_charges > 0) {
    $base_total = $base_total - $seat_charges;
}
// ❌ MISSING: Does NOT subtract addons!
```

**What Should Happen:**
```php
$base_total = $stored_total; // = 3045.26

// ✅ ADD THIS:
$addons_total = 0;
if (isset($flight_data['addons']) && is_array($flight_data['addons'])) {
    foreach ($flight_data['addons'] as $addon) {
        $addons_total += floatval($addon['price'] ?? 0);
    }
}
// $addons_total = 55.00 ✅

if ($addons_total > 0) {
    $base_total = $base_total - $addons_total;
    // $base_total = 3045.26 - 55.00 = 2990.26 ✅
}
```

**Impact:** ❌ **CRITICAL** - Base/taxes are inflated by $55.00

### 4.2 Discrepancy #2: Verification Formula Doesn't Include Addons

**Location:** `includes/class-amadex-pricing.php`, line 569

**Current Code:**
```php
$calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges;
// = 1000.31 + 2044.95 + 0 + 0 = 3045.26
// ❌ MISSING: Does NOT include addons_total!
```

**What Should Happen:**
```php
$calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges + $addons_total;
// = 1000.31 + 2044.95 + 0 + 0 + 55.00 = 3100.26 ✅
```

**Impact:** ❌ **HIGH** - Verification doesn't catch the missing addons

### 4.3 Discrepancy #3: Return Array Doesn't Include Addons

**Location:** `includes/class-amadex-pricing.php`, line 591-602

**Current Code:**
```php
return array(
    'base_fare' => $convert_to_display($final_base),
    'taxes' => $convert_to_display($final_taxes),
    'premium_service' => ...,
    'seat_selection' => ...,
    'total' => $convert_to_display($stored_total),
    // ❌ MISSING: No 'addons' field!
);
```

**What Should Happen:**
```php
return array(
    'base_fare' => $convert_to_display($final_base),
    'taxes' => $convert_to_display($final_taxes),
    'premium_service' => ...,
    'seat_selection' => ...,
    'addons' => $convert_to_display($addons_total), // ✅ ADD THIS
    'addons_list' => $flight_data['addons'] ?? array(), // ✅ ADD THIS
    'total' => $convert_to_display($stored_total + $addons_total), // ✅ FIX THIS
);
```

**Impact:** ❌ **HIGH** - Frontend can't display addons correctly in total

---

## Part 5: Why Total Shows $3,045.26 Instead of $3,100.26

### 5.1 The Calculation Flow

**Step 1: Backend Stores Total**
- `total_amount = 3045.26` ✅ (P_charge + addons)

**Step 2: Confirmation Page Reads Total**
- `$stored_total = 3045.26` ✅

**Step 3: Calculate Base Total**
- `$base_total = 3045.26` ❌ (should be 2990.26 after subtracting addons)

**Step 4: Split into Base/Taxes**
- `$final_base = 1000.31` (from inflated $base_total)
- `$final_taxes = 2044.95` (from inflated $base_total)
- Base + Taxes = **$3,045.26** ❌ (includes addons)

**Step 5: Display Total**
- Total = `$stored_total = 3045.26` ✅
- But Base + Taxes already = $3,045.26, so addons are absorbed!

### 5.2 Why It Should Be $3,100.26

**If addons are shown separately, they should be added to total:**

**Expected Calculation:**
```
Base Fare:                $X (from P_charge = $2,990.26 split)
Taxes:                    $Y (from P_charge = $2,990.26 split)
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total:                    $2,990.26 + $55.00 = $3,045.26
```

**BUT if addons are absorbed into base/taxes:**
```
Base Fare:                $1,000.31 (includes $55 addons)
Taxes:                    $2,044.95 (includes $55 addons)
TravelayGent™:            $25.00 (listed separately)
TravelaySurance™:         $30.00 (listed separately)
─────────────────────────────────
Total:                    $3,045.26 + $55.00 = $3,100.26 ✅
```

**The Issue:**
- Addons are listed separately, so they should be added to total
- But total = $3,045.26 (doesn't include the listed addons)
- **Expected: $3,045.26 + $55.00 = $3,100.26** ✅

---

## Part 6: Root Causes Summary

### 6.1 Primary Root Cause

**Issue:** `get_unified_price_breakdown()` doesn't handle `flight_data['addons']` array

**Location:** `includes/class-amadex-pricing.php`, lines 468-602

**Problems:**
1. Doesn't read `flight_data['addons']` array
2. Doesn't calculate `$addons_total` from the array
3. Doesn't subtract `$addons_total` from `$base_total`
4. Doesn't include `$addons_total` in verification formula
5. Doesn't return `addons` field in breakdown array

**Impact:** ❌ **CRITICAL** - Addons are absorbed into base/taxes instead of shown separately

### 6.2 Secondary Root Cause

**Issue:** Verification formula matches by accident

**Location:** `includes/class-amadex-pricing.php`, line 569

**Problem:**
- Formula: `$final_base + $final_taxes = $stored_total`
- This matches because addons are absorbed into base/taxes
- But it should be: `$final_base + $final_taxes + $addons_total = $stored_total + $addons_total`

**Impact:** ❌ **MEDIUM** - Verification doesn't catch the issue

### 6.3 Tertiary Root Cause

**Issue:** Return array doesn't include addons

**Location:** `includes/class-amadex-pricing.php`, line 591-602

**Problem:**
- Function doesn't return `addons` or `addons_list` in breakdown
- Frontend can't properly display addons in total

**Impact:** ❌ **HIGH** - Frontend displays addons but can't include them in sum

---

## Part 7: Expected vs Actual Breakdown

### 7.1 Expected Breakdown (Correct)

```
Base Fare:                $X (from P_charge = $2,990.26)
Taxes:                    $Y (from P_charge = $2,990.26)
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total:                    $2,990.26 + $55.00 = $3,045.26
```

**OR (if addons shown separately but total includes them):**
```
Base Fare:                $X (from P_charge = $2,990.26)
Taxes:                    $Y (from P_charge = $2,990.26)
TravelayGent™:            $25.00
TravelaySurance™:         $30.00
─────────────────────────────────
Total:                    $3,045.26 + $55.00 = $3,100.26 ✅
```

### 7.2 Actual Breakdown (Wrong)

```
Base Fare:                $1,000.31 (inflated, includes addons)
Taxes:                    $2,044.95 (inflated, includes addons)
TravelayGent™:            $25.00 (listed but NOT in sum)
TravelaySurance™:         $30.00 (listed but NOT in sum)
─────────────────────────────────
Total:                    $3,045.26 (Base + Taxes only, missing addons)
```

**The Fix:**
- Subtract addons from `$base_total` before splitting
- Base + Taxes = $2,990.26 (P_charge)
- Add addons = $55.00
- **Total = $2,990.26 + $55.00 = $3,045.26** ✅

**OR (if addons should be added to displayed total):**
- Base + Taxes = $3,045.26 (current, includes addons)
- Add listed addons = $55.00
- **Total = $3,045.26 + $55.00 = $3,100.26** ✅

---

## Part 8: Summary

### 8.1 What's Working

| Component | Status | Details |
|-----------|--------|---------|
| **Flight Results Page** | ✅ Correct | Shows $1,120.62 per person (P_display) |
| **Booking Page** | ✅ Correct | Shows P_display + Addons = $2,296.23 |
| **Backend Processing** | ✅ Correct | Calculates P_charge + Addons = $3,045.26 |
| **Database Storage** | ✅ Correct | Stores `total_amount = 3045.26` |

### 8.2 What's Broken

| Component | Status | Details |
|-----------|--------|---------|
| **get_unified_price_breakdown()** | ❌ Broken | Doesn't handle `flight_data['addons']` array |
| **Base Total Calculation** | ❌ Wrong | Doesn't subtract addons from `$base_total` |
| **Verification Formula** | ❌ Wrong | Doesn't include addons in formula |
| **Return Array** | ❌ Missing | Doesn't return addons field |
| **Confirmation Total** | ❌ Wrong | Shows $3,045.26 (should be $3,100.26 if addons listed) |

### 8.3 The Fix Needed

**File:** `includes/class-amadex-pricing.php`, `get_unified_price_breakdown()` function

**Changes:**
1. Read `flight_data['addons']` array (after line 458)
2. Calculate `$addons_total` from the array
3. Subtract `$addons_total` from `$base_total` (line 468)
4. Include `$addons_total` in verification formula (line 569)
5. Return `addons` field in breakdown array (line 591)
6. Update total calculation to include addons if they're shown separately

---

**End of Level 5 Analysis.**  
**Conclusion:** The root cause is that `get_unified_price_breakdown()` function doesn't handle the `flight_data['addons']` array. It calculates base/taxes from an inflated `$base_total` that includes addons, then displays addons separately but doesn't include them in the total sum. The total should be $3,100.26 ($3,045.26 + $55.00) if addons are shown separately, OR the base/taxes should be reduced by $55.00 to show P_charge correctly, then add addons to get $3,045.26.
