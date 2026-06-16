# Booking Flow Test Plan - Addons Fix Verification
## Level 5 Comprehensive Testing

**Date:** Test execution after addons fix implementation  
**Status:** Ready for execution

---

## Test Objectives

Verify that the addons fix correctly:
1. ✅ Calculates base/taxes from P_charge only (not including addons)
2. ✅ Displays addons separately on confirmation page
3. ✅ Displays addons separately in email
4. ✅ Ensures total = base + taxes + addons (matches NMI)
5. ✅ Prevents addons from being incorrectly assigned to seats/premium

---

## Test Scenarios

### Test Case 1: Booking with Addons Only (No Seats, No Premium)

**Setup:**
- Flight: Any flight with pricing rules enabled
- Addons: TravelayGent ($25) + TravelaySurance ($30) = $55 total
- Seats: None selected
- Premium Service: Not added

**Expected Results:**

**Booking Page:**
- Base Fare: X (from P_display)
- Taxes: Y (from P_display)
- TravelayGent: $25.00
- TravelaySurance: $30.00
- Total: P_display + $55.00

**Backend Processing:**
- P_charge: Calculated from pricing rules
- Addons total: $55.00
- Total amount: P_charge + $55.00
- Sent to NMI: P_charge + $55.00 ✅

**Confirmation Page:**
- Base Fare: A (from P_charge only, NOT including addons)
- Taxes: B (from P_charge only, NOT including addons)
- TravelayGent: $25.00 (displayed separately)
- TravelaySurance: $30.00 (displayed separately)
- Total: A + B + $55.00 = P_charge + $55.00 ✅
- Difference calculation: Should be $0.00 (no seats/premium) ✅

**Email:**
- Same as confirmation page ✅

**Verification Formula:**
```
Base + Taxes = P_charge (NOT including addons)
Addons = $55.00 (separate)
Total = P_charge + $55.00 ✅
```

---

### Test Case 2: Booking with Addons + Seats (No Premium)

**Setup:**
- Flight: Any flight with pricing rules enabled
- Addons: TravelayGent ($25) + TravelaySurance ($30) = $55 total
- Seats: Selected with charges (e.g., $100)
- Premium Service: Not added

**Expected Results:**

**Booking Page:**
- Base Fare: X (from P_display)
- Taxes: Y (from P_display)
- Seat Selection: $100.00
- TravelayGent: $25.00
- TravelaySurance: $30.00
- Total: P_display + $100 + $55.00

**Backend Processing:**
- P_charge: Calculated from pricing rules
- Seat charges: $100.00
- Addons total: $55.00
- Total amount: P_charge + $100 + $55.00
- Sent to NMI: P_charge + $100 + $55.00 ✅

**Confirmation Page:**
- Base Fare: A (from P_charge only)
- Taxes: B (from P_charge only)
- Seat Selection: $100.00
- TravelayGent: $25.00
- TravelaySurance: $30.00
- Total: A + B + $100 + $55.00 = P_charge + $100 + $55.00 ✅
- Difference calculation: Should account for seats only, not addons ✅

**Email:**
- Same as confirmation page ✅

**Verification Formula:**
```
Base + Taxes = P_charge (NOT including addons/seats)
Seats = $100.00 (separate)
Addons = $55.00 (separate)
Total = P_charge + $100 + $55.00 ✅
```

---

### Test Case 3: Booking with Addons + Premium (No Seats)

**Setup:**
- Flight: Any flight with pricing rules enabled
- Addons: TravelayGent ($25) + TravelaySurance ($30) = $55 total
- Seats: None selected
- Premium Service: Added (legacy or new system)

**Expected Results:**

**Backend Processing:**
- P_charge: Calculated from pricing rules
- Premium service: $25.00 (if legacy) or included in addons
- Addons total: $55.00 (or $80 if premium included)
- Total amount: P_charge + premium + addons
- Sent to NMI: Correct total ✅

**Confirmation Page:**
- Base Fare: A (from P_charge only)
- Taxes: B (from P_charge only)
- Premium Service: $25.00 (if legacy) OR TravelayGent: $25.00 (if new system)
- TravelaySurance: $30.00
- Total: A + B + premium + addons ✅
- Difference calculation: Should account for premium only, not addons ✅

**Email:**
- Same as confirmation page ✅

---

### Test Case 4: Booking with Addons + Seats + Premium

**Setup:**
- Flight: Any flight with pricing rules enabled
- Addons: TravelayGent ($25) + TravelaySurance ($30) = $55 total
- Seats: Selected with charges (e.g., $100)
- Premium Service: Added (if legacy system)

**Expected Results:**

**Backend Processing:**
- P_charge: Calculated from pricing rules
- Seat charges: $100.00
- Premium service: $25.00 (if legacy)
- Addons total: $55.00
- Total amount: P_charge + $100 + $25 + $55.00
- Sent to NMI: Correct total ✅

**Confirmation Page:**
- Base Fare: A (from P_charge only)
- Taxes: B (from P_charge only)
- Seat Selection: $100.00
- Premium Service: $25.00 (if legacy) OR TravelayGent: $25.00 (if new)
- TravelaySurance: $30.00
- Total: A + B + $100 + $25 + $55.00 ✅
- Difference calculation: Should account for seats + premium, not addons ✅

**Email:**
- Same as confirmation page ✅

---

### Test Case 5: Booking WITHOUT Addons (Baseline Test)

**Setup:**
- Flight: Any flight with pricing rules enabled
- Addons: None selected
- Seats: Optional (with or without)
- Premium Service: Optional (with or without)

**Expected Results:**

**Confirmation Page:**
- Base Fare: A (from P_charge only)
- Taxes: B (from P_charge only)
- No addons displayed
- Total: A + B + seats + premium ✅
- Difference calculation: Should work as before (no addons) ✅

**Email:**
- Same as confirmation page ✅

**Verification:**
- Should work exactly as before the fix
- No regression in existing functionality ✅

---

## Verification Checklist

### Confirmation Page Verification

- [ ] Base Fare + Taxes = P_charge only (NOT including addons)
- [ ] Addons displayed separately with correct amounts
- [ ] Total = Base + Taxes + Seats + Premium + Addons
- [ ] Total matches NMI payment amount
- [ ] Difference calculation doesn't assign addons to seats/premium
- [ ] Currency conversion works correctly (if applicable)

### Email Verification

- [ ] Same as confirmation page
- [ ] Base Fare + Taxes = P_charge only
- [ ] Addons displayed separately
- [ ] Total matches confirmation page
- [ ] Total matches NMI payment amount

### Backend Processing Verification

- [ ] `process_booking()` correctly calculates P_charge + addons + seats
- [ ] `$total_amount_usd` includes addons
- [ ] NMI receives correct total (P_charge + addons + seats)
- [ ] Database stores correct total

### Edge Cases

- [ ] Booking with only one addon
- [ ] Booking with multiple addons
- [ ] Booking with free seats (charges = $0)
- [ ] Booking with legacy premium_service
- [ ] Booking with new addons system
- [ ] Booking with currency conversion
- [ ] Booking without pricing rules (fallback)

---

## Test Execution Steps

1. **Prepare Test Environment**
   - Ensure pricing rules are enabled
   - Ensure addons are configured (TravelayGent, TravelaySurance)
   - Clear any cached data

2. **Execute Test Case 1**
   - Create booking with addons only
   - Verify booking page
   - Complete booking
   - Verify confirmation page
   - Check email
   - Verify NMI amount

3. **Execute Test Case 2**
   - Create booking with addons + seats
   - Verify all stages

4. **Execute Test Case 3**
   - Create booking with addons + premium
   - Verify all stages

5. **Execute Test Case 4**
   - Create booking with all options
   - Verify all stages

6. **Execute Test Case 5**
   - Create booking without addons
   - Verify no regression

7. **Verify Edge Cases**
   - Test various combinations
   - Test currency conversion
   - Test legacy vs new system

---

## Expected Test Results Summary

| Test Case | Base+Taxes | Addons | Seats | Premium | Total | Status |
|-----------|------------|--------|-------|---------|-------|--------|
| **1. Addons Only** | P_charge | $55 | $0 | $0 | P_charge + $55 | ✅ |
| **2. Addons + Seats** | P_charge | $55 | $100 | $0 | P_charge + $155 | ✅ |
| **3. Addons + Premium** | P_charge | $55 | $0 | $25 | P_charge + $80 | ✅ |
| **4. All Options** | P_charge | $55 | $100 | $25 | P_charge + $180 | ✅ |
| **5. No Addons** | P_charge | $0 | $0 | $0 | P_charge | ✅ |

---

## Critical Verification Points

### ✅ Must Pass

1. **Base + Taxes = P_charge only** (NOT including addons)
2. **Addons displayed separately** (not absorbed into base/taxes)
3. **Total = Base + Taxes + Addons + Seats + Premium**
4. **Total matches NMI payment**
5. **No regression** in bookings without addons

### ⚠️ Should Pass

1. Difference calculation correctly accounts for addons
2. Currency conversion works with addons
3. Legacy premium_service compatibility
4. Edge cases handled correctly

---

## Test Execution Log

**Date:** [To be filled during execution]  
**Tester:** [To be filled]  
**Environment:** [To be filled]

### Test Results

| Test Case | Status | Notes |
|-----------|--------|-------|
| Test Case 1 | ⏳ Pending | |
| Test Case 2 | ⏳ Pending | |
| Test Case 3 | ⏳ Pending | |
| Test Case 4 | ⏳ Pending | |
| Test Case 5 | ⏳ Pending | |

---

## Post-Test Actions

1. Review all test results
2. Document any issues found
3. Fix any critical issues
4. Re-test if fixes applied
5. Update documentation if needed

---

**Status:** Ready for execution  
**Next Step:** Execute tests and document results
