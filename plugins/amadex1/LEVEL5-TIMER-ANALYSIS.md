# Level 5 Core Analysis: Flight Booking Page Timer System

**Date:** Analysis performed  
**Scope:** Complete frontend and backend analysis of the 20-minute countdown timer on the flight booking page  
**No code changes** — analysis only.

---

## Executive Summary

The timer is a **frontend-only** countdown system that:
- Runs for **20 minutes** (1200 seconds) from when the booking page loads
- **Resets** on navigation away (beforeunload clears state)
- **Pauses/resumes** on tab switching (visibility change preserves state)
- **Refreshes flight price** when expired (via `amadex_recalculate_price` API)
- **No backend validation** — timer is purely client-side for UX urgency

---

## Part 1: Frontend Timer Implementation

### 1.1 Core Files

| File | Purpose | Key Functions |
|------|---------|---------------|
| `assets/js/amadex-booking.js` | Timer logic, state management, price refresh | `startBookingTimer()`, `refreshFlightPriceOnExpiry()`, `restartTimerAfterRefresh()`, `showFiveMinuteWarningModal()` |
| `assets/css/amadex-booking.css` | Timer visual styling | `.amadex-timer-container`, `.amadex-timer-card`, `.timer-expired`, `.amadex-timer-warning-modal` |
| `includes/frontend/class-amadex-shortcodes.php` | Timer HTML markup | Timer badge HTML (lines 2143-2156) |

### 1.2 Timer Initialization Flow

```
Page Load (initBookingPage)
    ↓
Check flight ID from sessionStorage
    ├─ New flight → Clear timer state → Start fresh (20:00)
    └─ Same flight → Clear timer state → Start fresh (20:00)
    ↓
startBookingTimer() called (line 1079)
    ↓
Check for paused timer (tab switch)
    ├─ If paused < 3 seconds ago → Resume from saved state
    └─ Otherwise → Start fresh (20:00)
    ↓
setInterval(updateTimer, 1000) → Updates every second
```

**Key Points:**
- Timer **always starts fresh** (20:00) when booking page loads
- Timer state is **cleared on navigation away** (beforeunload event)
- Timer **pauses/resumes** only for tab switching (visibility change)

### 1.3 Timer State Management (sessionStorage)

| Key | Purpose | When Set | When Cleared |
|-----|---------|----------|--------------|
| `amadex_booking_timer_start` | Timestamp when timer started | `startBookingTimer()` (line 4246) | Navigation away, new flight, timer restart |
| `amadex_booking_timer_remaining` | Remaining seconds | Every second in `updateTimer()` (line 4331) | Navigation away, new flight, timer restart |
| `amadex_booking_timer_paused_at` | Timestamp when page hidden | `visibilitychange` event (line 674) | Navigation away, resume, timer restart |
| `amadex_last_booking_flight_id` | Last flight ID for comparison | Flight detection (line 768) | New flight selected |

**State Lifecycle:**
1. **Navigation away:** All timer keys cleared (beforeunload, line 660-662)
2. **Tab switch (hidden):** `timer_remaining` and `paused_at` saved (line 672-674)
3. **Tab switch (visible):** Timer resumes if pause was < 3 seconds ago (line 4187-4198)
4. **New flight:** All timer keys cleared, new flight ID stored (line 764-768)
5. **Same flight:** Timer keys cleared, fresh timer starts (line 778-791)

### 1.4 Timer Countdown Logic

**Function:** `startBookingTimer()` (line 4142)

**Duration:** Always **20 minutes** (1200 seconds) — hardcoded

**Update Interval:** Every **1 second** via `setInterval(updateTimer, 1000)`

**Display Format:** `MM:SS` (e.g., "20:00", "05:00", "00:00")

**Key Logic:**
```javascript
function updateTimer() {
    if (timeRemaining <= 0) {
        // Timer expired → Refresh price
        refreshFlightPriceOnExpiry();
        return;
    }
    
    // Show 5-minute warning modal (only once)
    if (timeRemaining <= 300 && timeRemaining > 295 && !window.amadexWarningModalShown) {
        showFiveMinuteWarningModal();
        window.amadexWarningModalShown = true;
    }
    
    // Update display
    timerDisplay.text(formattedTime);
    timeRemaining--;
}
```

### 1.5 Timer Expiration Behavior

**When timer reaches 00:00:**

1. **Display changes:**
   - Timer shows `--:--` (line 4285)
   - Subtitle: "Refreshing price..." (line 4278)
   - Adds `timer-expired` class (line 4270)

2. **Price refresh triggered:**
   - Calls `refreshFlightPriceOnExpiry()` (line 4291)
   - Gets flight/search data from sessionStorage (lines 4420-4440)
   - Syncs passenger counts from DOM (line 4455)
   - Calls `updatePriceWithAmadeusAPI(flight, searchData, 'timer_refresh', rawOffer)` (line 4472)

3. **Timer restart:**
   - **Only if price refresh succeeds** (line 2719-2720)
   - Calls `restartTimerAfterRefresh()` (line 4481)
   - Resets to 20:00, clears expired styling

4. **If price refresh fails:**
   - Timer **does NOT restart** (line 2616, 2745, 2785)
   - Shows error in timer subtitle
   - User can manually retry

### 1.6 Five-Minute Warning Modal

**Trigger:** When timer reaches **5:00** (300 seconds) or less (line 4297)

**Function:** `showFiveMinuteWarningModal()` (line 4355)

**Behavior:**
- Shows modal: "Your search is expiring soon... In [time] we'll need to recheck flight availability."
- Modal timer updates in real-time (line 4310-4313)
- User can close modal → Triggers price refresh immediately (line 4395, 4407)
- Modal auto-closes when timer hits 00:00 (line 4316-4325)

**One-time display:** `window.amadexWarningModalShown` flag prevents duplicate modals

### 1.7 Timer Reset Scenarios

| Scenario | Behavior | Code Location |
|----------|----------|---------------|
| **New flight selected** | Clear all timer state, start fresh 20:00 | Line 764-768 |
| **Navigation away** | Clear all timer state (beforeunload) | Line 660-662 |
| **Page refresh** | Clear timer state, start fresh 20:00 | Line 778-791 |
| **Tab switch (hidden)** | Save remaining time, pause timestamp | Line 672-674 |
| **Tab switch (visible)** | Resume if pause < 3 seconds ago | Line 4187-4198 |
| **Price refresh success** | Restart timer to 20:00 | Line 4481-4522 |
| **Price refresh failure** | Timer stays expired, no restart | Line 2616, 2745, 2785 |

---

## Part 2: Backend Timer Integration

### 2.1 Backend Price Refresh API

**Endpoint:** `amadex_recalculate_price` (AJAX action)

**Handler:** `recalculate_price()` in `amadex-ajax.php` (line 5330)

**Timer-Specific Flow:**

1. **Frontend sends:** `raw_offer` JSON when `passengerType === 'timer_refresh'` (line 2579-2582)
2. **Backend receives:** `$_POST['raw_offer']` (line 5350)
3. **Backend logic:**
   - **PRIORITY 1:** If `raw_offer` provided AND passenger counts match → Use direct Price API (line 5361-5451)
   - **PRIORITY 2:** If passenger counts changed OR direct pricing fails → Re-search flights (line 5462-5501)
4. **Response:** New price data (base, taxes, total, currency, travelerPricings)

**Key Backend Code:**
```php
// Check if raw_offer is provided (for timer refresh - direct pricing)
$raw_offer_json = isset($_POST['raw_offer']) ? stripslashes($_POST['raw_offer']) : '';

if (!empty($raw_offer_json)) {
    $raw_offer = json_decode($raw_offer_json, true);
    
    // Check if passenger counts match
    if ($passenger_counts_match) {
        // Use direct Price API (fast)
        $pricing_result = $api->price_flight_offer_with_seats($raw_offer, array());
    } else {
        // Passenger counts changed - use re-search method
        // Fall through to PRIORITY 2
    }
}
```

### 2.2 Backend Timer Validation

**Finding:** **NO backend timer validation exists**

- Backend does **NOT** check if 20 minutes have elapsed
- Backend does **NOT** reject bookings if timer expired
- Backend does **NOT** store timer start/end times
- Timer is **purely frontend UX** — no backend enforcement

**Implication:** Timer expiration only triggers price refresh; it does **NOT** block booking submission.

### 2.3 Price Refresh Success/Failure Handling

**Success (line 2716-2720):**
```javascript
if (window.amadexTimerRefreshInProgress) {
    window.amadexTimerRefreshInProgress = false;
    console.log('Price refresh completed successfully - resetting timer to 20:00');
    restartTimerAfterRefresh();
}
```

**Failure (line 2743-2753, 2773-2786):**
```javascript
if (window.amadexTimerRefreshInProgress) {
    window.amadexTimerRefreshInProgress = false;
    timerSubtitle.text('Price refresh failed. Please try again.');
    // Timer will NOT auto-restart
}
```

**Timeout (line 2605-2617):**
```javascript
if (window.amadexTimerRefreshInProgress) {
    timerSubtitle.text('Price refresh timed out. Please try again.');
    window.amadexTimerRefreshInProgress = false;
    // Timer will NOT auto-restart
}
```

---

## Part 3: Timer Integration with Booking Flow

### 3.1 Timer and Booking Submission

**Key Finding:** Timer expiration does **NOT** block booking submission.

- User can click "Confirm & Book" even if timer shows 00:00
- Backend does **NOT** validate timer expiration
- Booking proceeds normally regardless of timer state

**Timer's Role:** 
- **UX urgency** — encourages faster booking
- **Price refresh** — updates prices when expired
- **NOT a booking blocker** — does not prevent submission

### 3.2 Timer and Price Updates

**Flow:**
```
Timer expires (00:00)
    ↓
refreshFlightPriceOnExpiry()
    ↓
updatePriceWithAmadeusAPI(flight, searchData, 'timer_refresh', rawOffer)
    ↓
AJAX: amadex_recalculate_price (with raw_offer)
    ↓
Backend: Direct Price API or Re-search
    ↓
Response: New price data
    ↓
Frontend: Update price breakdown, flight.price
    ↓
If success → restartTimerAfterRefresh() → Timer resets to 20:00
If failure → Timer stays expired, shows error
```

### 3.3 Timer and Flight Selection

**New Flight Detection (line 758-792):**
- Compares `currentFlightId` vs `amadex_last_booking_flight_id`
- If different → **New flight** → Clear timer, start fresh
- If same → **Same flight** → Clear timer, start fresh (timer resets on navigation)

**Key Point:** Timer **always starts fresh** when booking page loads, regardless of flight ID.

---

## Part 4: Timer Files and Dependencies

### 4.1 Files Containing Timer Code

| File | Lines | Purpose |
|------|-------|---------|
| `assets/js/amadex-booking.js` | 4139-4553 | Core timer functions (`startBookingTimer`, `refreshFlightPriceOnExpiry`, `restartTimerAfterRefresh`, `showFiveMinuteWarningModal`, `updateTimer`) |
| `assets/js/amadex-booking.js` | 650-713 | Timer state management (beforeunload, visibilitychange) |
| `assets/js/amadex-booking.js` | 756-792 | Flight detection and timer reset logic |
| `assets/js/amadex-booking.js` | 2523-2803 | `updatePriceWithAmadeusAPI` (price refresh with timer_refresh support) |
| `assets/css/amadex-booking.css` | 2905-3100 | Timer container, card, display, expired state styles |
| `assets/css/amadex-booking.css` | 2568-2645 | Timer warning modal styles |
| `includes/frontend/class-amadex-shortcodes.php` | 2143-2156 | Timer HTML markup (badge structure) |
| `includes/amadex-ajax.php` | 5330-5612 | `recalculate_price()` handler (supports `raw_offer` for timer refresh) |

### 4.2 Dependencies

**Timer depends on:**
- jQuery (`$` selector, DOM manipulation)
- `sessionStorage` (state persistence)
- `setInterval` / `clearInterval` (countdown)
- `updatePriceWithAmadeusAPI()` (price refresh on expiry)
- `syncCurrentPassengerCountsToSearchData()` (passenger count sync)

**Timer does NOT depend on:**
- Backend timer validation
- Database timer storage
- Server-side session management
- Any Flight Leads Management features

---

## Part 5: Timer Behavior Matrix

### 5.1 User Actions and Timer Response

| User Action | Timer Behavior |
|-------------|----------------|
| **Page load (new flight)** | Start fresh 20:00 |
| **Page load (same flight)** | Start fresh 20:00 (timer resets on navigation) |
| **Navigate away** | Clear all timer state |
| **Tab switch (hidden)** | Pause, save remaining time |
| **Tab switch (visible)** | Resume if pause < 3 seconds ago |
| **Timer reaches 5:00** | Show warning modal (once) |
| **Timer reaches 00:00** | Refresh price, show "Refreshing..." |
| **Price refresh succeeds** | Restart timer to 20:00 |
| **Price refresh fails** | Timer stays expired, no restart |
| **Click "Confirm & Book"** | Booking proceeds (timer state irrelevant) |

### 5.2 Timer State Transitions

```
[INITIAL]
    ↓ (page load)
[RUNNING: 20:00]
    ↓ (countdown)
[RUNNING: 15:00]
    ↓ (countdown)
[RUNNING: 5:00] → [WARNING MODAL SHOWN]
    ↓ (countdown)
[RUNNING: 1:00]
    ↓ (countdown)
[EXPIRED: 00:00] → [PRICE REFRESH TRIGGERED]
    ├─ Success → [RESTART: 20:00]
    └─ Failure → [EXPIRED: Error message, no restart]
```

---

## Part 6: Critical Findings

### 6.1 Timer is Frontend-Only

- **No backend validation** — timer expiration does not block booking
- **No server-side enforcement** — timer is UX-only
- **No database storage** — timer state is sessionStorage only

### 6.2 Timer Always Resets on Page Load

- **New flight:** Timer starts fresh (20:00)
- **Same flight:** Timer starts fresh (20:00) — **does NOT continue from previous session**
- **Navigation away:** Timer state cleared, fresh start on return

**Rationale:** "Timer resets on navigation" — industry best practice to prevent stale pricing.

### 6.3 Timer Pauses Only for Tab Switching

- **Tab switch (hidden):** Timer pauses, state saved
- **Tab switch (visible):** Timer resumes if pause < 3 seconds ago
- **Navigation away:** Timer state cleared (beforeunload)

**Logic:** Distinguishes between "tab switch" (preserve) vs "navigation" (reset).

### 6.4 Price Refresh on Expiry

- **Automatic:** Timer expiration triggers price refresh
- **No page reload:** Price updates via AJAX
- **Timer restart:** Only if price refresh succeeds
- **Manual retry:** If refresh fails, user can retry

### 6.5 Five-Minute Warning

- **One-time modal:** Shows at 5:00 or less (range check 300-295 seconds)
- **User can close:** Closing modal triggers immediate price refresh
- **Auto-closes:** When timer hits 00:00

---

## Part 7: Timer Files Summary

### 7.1 Files Modified for Timer

| File | Status | Purpose |
|------|--------|---------|
| `assets/js/amadex-booking.js` | **Modified** | Timer logic, state management, price refresh integration |
| `assets/css/amadex-booking.css` | **Modified** | Timer styling, expired state, warning modal styles |
| `includes/frontend/class-amadex-shortcodes.php` | **Modified** | Timer HTML markup in booking page template |
| `includes/amadex-ajax.php` | **Modified** | `recalculate_price()` supports `raw_offer` for timer refresh |

### 7.2 Timer-Specific Functions

| Function | Location | Purpose |
|----------|----------|---------|
| `startBookingTimer()` | `amadex-booking.js:4142` | Initialize/start timer countdown |
| `updateTimer()` | `amadex-booking.js:4263` | Update display every second |
| `refreshFlightPriceOnExpiry()` | `amadex-booking.js:4416` | Trigger price refresh when timer expires |
| `restartTimerAfterRefresh()` | `amadex-booking.js:4481` | Restart timer after successful price refresh |
| `showFiveMinuteWarningModal()` | `amadex-booking.js:4355` | Show warning modal at 5 minutes |
| `clearBookingTimerSession()` | `amadex-booking.js:4123` | Clear all timer state |
| `restartTimer()` | `amadex-booking.js:4527` | Manual timer restart |
| `recalculate_price()` | `amadex-ajax.php:5330` | Backend price refresh handler (supports timer refresh) |

---

## Part 8: Timer Integration Points

### 8.1 Timer and Price Refresh API

**Frontend → Backend:**
- Action: `amadex_recalculate_price`
- Special flag: `passengerType === 'timer_refresh'`
- Special data: `raw_offer` JSON (for direct pricing)
- Backend priority: Direct Price API if passenger counts match, else re-search

### 8.2 Timer and Flight Data

**Dependencies:**
- `sessionStorage.getItem('amadex_booking_flight')` — flight data for price refresh
- `sessionStorage.getItem('amadex_search_data')` — search params for price refresh
- `flight.rawOffer` — required for direct pricing API call

**If missing:** Timer shows error "Unable to refresh price. Please try again."

### 8.3 Timer and Passenger Counts

**Sync before refresh:**
- `syncCurrentPassengerCountsToSearchData()` called before price refresh (line 4455)
- Ensures DOM passenger counts are synced to search data
- Critical for accurate price recalculation

---

## Part 9: Timer Edge Cases and Behaviors

### 9.1 Multiple Timer Instances

**Protection:** `window.amadexBookingTimerInterval` stores interval ID
- If timer already running, clears old interval before starting new (line 4168-4171)
- Prevents duplicate timers

### 9.2 Timer and Page Refresh

**Behavior:** Timer state cleared on `beforeunload` (line 660-662)
- Page refresh → Timer starts fresh (20:00)
- **Does NOT** continue from previous session

### 9.3 Timer and Browser Back/Forward

**Behavior:** Navigation triggers `beforeunload` → Timer state cleared
- Back button → Timer starts fresh (20:00)
- Forward button → Timer starts fresh (20:00)

### 9.4 Timer and Mobile App Switching

**Behavior:** `visibilitychange` event fires
- App switch (hidden) → Timer pauses, state saved
- App switch back (visible) → Timer resumes if pause < 3 seconds ago
- If pause > 3 seconds → Timer starts fresh (likely navigation, not tab switch)

### 9.5 Timer Expiration During Booking

**Behavior:** Timer expiration does **NOT** interrupt booking
- User can submit booking even if timer shows 00:00
- Price refresh runs in background
- Booking proceeds normally

---

## Part 10: Timer CSS Classes and Styling

### 10.1 Timer CSS Classes

| Class | Purpose | Applied When |
|-------|---------|--------------|
| `.amadex-timer-container` | Timer wrapper container | Always (HTML structure) |
| `.amadex-timer-card` | Timer card/badge | Always (HTML structure) |
| `#amadex-booking-timer-badge` | Timer badge ID | Always (HTML structure) |
| `.timer-expired` | Expired state styling | When timer reaches 00:00 (line 4270) |
| `.timer-display` | Timer display span | Always (HTML structure) |
| `.amadex-timer-subtitle` | Subtitle text | Always (HTML structure) |
| `.amadex-timer-warning-overlay` | Warning modal overlay | When 5-minute warning shown |
| `.amadex-timer-warning-modal` | Warning modal container | When 5-minute warning shown |

### 10.2 Timer Visual States

| State | Display | Subtitle | Styling |
|-------|---------|----------|---------|
| **Running** | `MM:SS` (e.g., "20:00") | "This price will expire after 20 minutes." | Normal green color |
| **Expired** | `--:--` | "Refreshing price..." | Green, opacity 0.7, `timer-expired` class |
| **Refresh Error** | `--:--` | "Price refresh failed. Please try again." | Red color, bold |
| **Refresh Success** | `20:00` (restarted) | "This price will expire after 20 minutes." | Normal green color |

---

## Part 11: Timer Backend Analysis

### 11.1 Backend Timer Validation

**Finding:** **NONE**

- No backend function checks timer expiration
- No backend validation of `amadex_booking_timer_start` or `amadex_booking_timer_remaining`
- No database table stores timer data
- No server-side session tracks timer state

**Conclusion:** Timer is **100% frontend UX** — backend has no knowledge of timer state.

### 11.2 Backend Price Refresh (Timer-Triggered)

**Endpoint:** `amadex_recalculate_price`

**Timer-Specific Handling:**
- Receives `raw_offer` JSON when `passengerType === 'timer_refresh'` (line 5350)
- Uses direct Price API if passenger counts match (line 5393-5396)
- Falls back to re-search if counts changed or direct pricing fails (line 5462-5501)

**Response Format:**
```json
{
    "success": true,
    "data": {
        "price": {
            "base": 126.42,
            "taxes": 32.13,
            "total": 158.55,
            "grandTotal": 158.55,
            "currency": "USD",
            "travelerPricings": [...]
        }
    }
}
```

### 11.3 Backend Booking Validation (Timer-Related)

**Finding:** **NONE**

- `process_booking()` does **NOT** check timer expiration
- `process_booking()` does **NOT** validate timer state
- Booking submission proceeds regardless of timer state

**Conclusion:** Timer expiration does **NOT** block booking — it only triggers price refresh.

---

## Part 12: Timer Integration Summary

### 12.1 Timer and Our 7 Features

| Feature | Timer Interaction |
|---------|-------------------|
| **Duplicate booking prevention** | None — timer does not affect locks/request hash |
| **Request hash generation** | None — timer does not affect request hash |
| **JS syntax fixes** | None — timer functions are separate |
| **Confirm & Book / NMI flow** | None — timer does not block booking submission |
| **Booking locks lifecycle** | None — timer does not affect locks table |
| **DUPLICATE_REQUEST** | None — timer does not affect duplicate detection |
| **AJAX error handling** | **Yes** — timer refresh uses same error handling flow |

**Conclusion:** Timer is **independent** from our 7 features. Only shares AJAX error handling.

### 12.2 Timer and Flight Leads Management

| Feature | Timer Interaction |
|---------|-------------------|
| **Fraud detection** | None — timer does not affect fraud collection |
| **Assignment engine** | None — timer does not affect lead assignment |
| **PDF/Export** | None — timer does not affect exports |
| **Analytics** | None — timer does not affect analytics |

**Conclusion:** Timer is **completely independent** from Flight Leads Management.

---

## Part 13: Timer Files (Complete List)

### 13.1 Files Containing Timer Code

| # | Path | Purpose | Status |
|---|------|---------|--------|
| 1 | `assets/js/amadex-booking.js` | Timer functions, state management, price refresh | **Modified** |
| 2 | `assets/css/amadex-booking.css` | Timer styling, expired state, warning modal | **Modified** |
| 3 | `includes/frontend/class-amadex-shortcodes.php` | Timer HTML markup | **Modified** |
| 4 | `includes/amadex-ajax.php` | `recalculate_price()` supports timer refresh | **Modified** |

### 13.2 Timer Dependencies (No Changes Needed)

| File | Purpose | Status |
|------|---------|--------|
| `assets/js/amadex.js` | Clears timer on search (line 347-348, 1069-1070) | **Pre-existing** |
| `assets/css/amadex-mobile-responsive.css` | Mobile timer styling | **Pre-existing** |

---

## Part 14: Timer Behavior Deep Dive

### 14.1 Timer Start Conditions

| Condition | Timer Behavior |
|-----------|----------------|
| **First visit to booking page** | Start fresh 20:00 |
| **Return to booking page (same flight)** | Start fresh 20:00 (navigation cleared state) |
| **Return to booking page (new flight)** | Start fresh 20:00 (new flight detected) |
| **Tab switch back (< 3 seconds)** | Resume from saved state |
| **Tab switch back (> 3 seconds)** | Start fresh 20:00 (treated as navigation) |

### 14.2 Timer Expiration Flow

```
Timer: 00:00
    ↓
updateTimer() detects expiration
    ↓
clearInterval(timerInterval)
    ↓
Display: "--:--", Subtitle: "Refreshing price..."
    ↓
refreshFlightPriceOnExpiry()
    ↓
Get flight/search data from sessionStorage
    ↓
syncCurrentPassengerCountsToSearchData()
    ↓
updatePriceWithAmadeusAPI(flight, searchData, 'timer_refresh', rawOffer)
    ↓
AJAX: amadex_recalculate_price
    ├─ Success → restartTimerAfterRefresh() → Timer: 20:00
    └─ Failure → Timer stays expired, error message
```

### 14.3 Timer Warning Modal Flow

```
Timer: 5:00 (300 seconds)
    ↓
updateTimer() detects <= 300 && > 295
    ↓
Check: !window.amadexWarningModalShown
    ↓
showFiveMinuteWarningModal()
    ↓
Create modal HTML, append to body
    ↓
Modal shows: "Your search is expiring soon... In [time] we'll need to recheck flight availability."
    ↓
Modal timer updates in real-time (syncs with main timer)
    ↓
User can close → refreshFlightPriceOnExpiry() → Price refresh
    ↓
OR Timer hits 00:00 → Modal auto-closes → refreshFlightPriceOnExpiry()
```

---

## Part 15: Timer State Persistence Analysis

### 15.1 sessionStorage Keys

| Key | Type | Purpose | Lifetime |
|-----|------|---------|----------|
| `amadex_booking_timer_start` | String (timestamp) | Timer start time | Cleared on navigation, new flight, restart |
| `amadex_booking_timer_remaining` | String (seconds) | Remaining time | Updated every second, cleared on navigation |
| `amadex_booking_timer_paused_at` | String (timestamp) | Pause timestamp | Cleared on navigation, resume, restart |
| `amadex_last_booking_flight_id` | String (flight ID) | Last flight ID | Cleared on new flight |

### 15.2 State Persistence Rules

1. **Navigation away:** All timer keys cleared (beforeunload)
2. **Tab switch (hidden):** `timer_remaining` and `paused_at` saved
3. **Tab switch (visible):** Resume if pause < 3 seconds, else start fresh
4. **New flight:** All timer keys cleared, new flight ID stored
5. **Price refresh success:** All timer keys cleared, fresh timer starts

### 15.3 State Recovery Logic

**Resume Logic (line 4202-4231):**
```javascript
if (shouldResume && timerStartTime && savedRemaining) {
    // Calculate elapsed time
    const elapsed = Math.floor((now - startTime) / 1000);
    
    // Account for pause duration
    if (pausedAt && savedRemaining) {
        const pauseDuration = Math.floor((now - pauseTime) / 1000);
        timeRemaining = Math.max(remainingBeforePause - pauseDuration, 0);
    }
    
    // If expired, refresh immediately
    if (timeRemaining <= 0) {
        refreshFlightPriceOnExpiry();
        return;
    }
}
```

---

## Part 16: Timer Integration with Price API

### 16.1 Price Refresh API Call

**Function:** `updatePriceWithAmadeusAPI()` (line 2523)

**Timer-Specific Parameters:**
- `passengerType: 'timer_refresh'` (line 4472)
- `rawOffer: flight.rawOffer || flight` (line 4468)

**AJAX Data:**
```javascript
{
    action: 'amadex_recalculate_price',
    raw_offer: JSON.stringify(rawOffer), // Only when timer_refresh
    // ... other params
}
```

### 16.2 Backend Price Refresh Handler

**Function:** `recalculate_price()` (line 5330)

**Timer-Specific Logic:**
1. Check if `raw_offer` provided (line 5350)
2. Decode JSON (line 5364)
3. Validate passenger counts match (line 5378-5389)
4. If match → Direct Price API (line 5396)
5. If no match → Re-search method (line 5462-5501)

**Response:** New price data (base, taxes, total, currency, travelerPricings)

### 16.3 Price Update Success Callback

**Location:** `updatePriceWithAmadeusAPI()` success handler (line 2716-2720)

**Timer Restart Logic:**
```javascript
if (window.amadexTimerRefreshInProgress) {
    window.amadexTimerRefreshInProgress = false;
    console.log('Price refresh completed successfully - resetting timer to 20:00');
    restartTimerAfterRefresh();
}
```

---

## Part 17: Timer Edge Cases

### 17.1 Multiple Tab Scenario

**Behavior:**
- Each tab has **independent** timer (sessionStorage is per-tab)
- Tab A: Timer running
- Tab B: Opens same page → Starts **fresh** timer (20:00)
- **No synchronization** between tabs

### 17.2 Timer and Browser DevTools

**Behavior:**
- Opening DevTools → `visibilitychange` may fire (browser-dependent)
- Timer may pause/resume
- **No special handling** — normal visibility change logic applies

### 17.3 Timer and Page Unload During Refresh

**Behavior:**
- Timer expires → Price refresh triggered
- User navigates away during refresh → `beforeunload` clears timer state
- Price refresh may complete, but timer state is lost
- **No issue** — timer will start fresh on return

### 17.4 Timer and Network Failure

**Behavior:**
- Timer expires → Price refresh triggered
- Network fails → AJAX error/timeout
- Timer shows error: "Price refresh failed. Please try again."
- Timer **does NOT restart** — user must retry manually

---

## Part 18: Timer Performance Considerations

### 18.1 Timer Interval Overhead

**Frequency:** Every 1 second (1000ms interval)

**Operations per second:**
- DOM update (timer display)
- sessionStorage write (remaining time)
- Global variable update (`window.amadexTimerRemaining`)
- Conditional checks (expiration, warning modal)

**Impact:** **Minimal** — standard countdown timer overhead

### 18.2 Timer State Storage

**Storage:** sessionStorage (client-side only)

**Size:** ~100 bytes (3 keys: start, remaining, paused_at)

**Impact:** **Negligible** — very small storage footprint

### 18.3 Timer and Memory Leaks

**Protection:**
- `clearInterval()` called on navigation (beforeunload)
- `clearInterval()` called before starting new timer (line 4168-4171)
- Interval ID stored in `window.amadexBookingTimerInterval` for cleanup

**Risk:** **Low** — proper cleanup on navigation and timer restart

---

## Part 19: Timer Accessibility

### 19.1 Screen Reader Support

**ARIA Labels:**
- Timer icon has `aria-label="Alarm clock"` (line 2150)
- Timer display updates in real-time (visible to screen readers)

**Announcements:**
- `announceToScreenReader('Booking is being processed. Please wait.')` (line 14449)
- Warning modal has proper ARIA attributes

### 19.2 Keyboard Navigation

**Timer Badge:**
- Not interactive — display only
- No keyboard focus required

**Warning Modal:**
- Close button is keyboard accessible
- Modal trap focus (standard modal behavior)

---

## Part 20: Timer Testing Scenarios

### 20.1 Normal Flow

1. User loads booking page → Timer starts 20:00
2. Timer counts down → 19:59, 19:58, ...
3. Timer reaches 5:00 → Warning modal shows
4. Timer reaches 00:00 → Price refresh triggered
5. Price refresh succeeds → Timer restarts 20:00

### 20.2 Tab Switch Flow

1. Timer running 15:00
2. User switches tab → Timer pauses, state saved
3. User switches back (< 3 seconds) → Timer resumes from 15:00
4. Timer continues countdown

### 20.3 Navigation Flow

1. Timer running 10:00
2. User navigates away → Timer state cleared
3. User returns → Timer starts fresh 20:00

### 20.4 Price Refresh Failure Flow

1. Timer expires 00:00
2. Price refresh triggered
3. Network fails → Error shown
4. Timer stays expired, no restart
5. User can retry manually

---

## Part 21: Timer Summary

### 21.1 Timer Architecture

- **Type:** Frontend-only countdown timer
- **Duration:** 20 minutes (1200 seconds) — hardcoded
- **Storage:** sessionStorage (client-side)
- **Backend:** No validation, no enforcement
- **Purpose:** UX urgency + automatic price refresh on expiry

### 21.2 Timer Key Behaviors

1. **Always starts fresh** on page load (20:00)
2. **Resets on navigation** (beforeunload clears state)
3. **Pauses/resumes on tab switch** (visibility change)
4. **Refreshes price on expiry** (via `amadex_recalculate_price`)
5. **Restarts only on success** (price refresh must succeed)
6. **Does NOT block booking** (submission proceeds regardless)

### 21.3 Timer Files

| File | Role |
|------|------|
| `assets/js/amadex-booking.js` | Core timer logic |
| `assets/css/amadex-booking.css` | Timer styling |
| `includes/frontend/class-amadex-shortcodes.php` | Timer HTML |
| `includes/amadex-ajax.php` | Price refresh API (timer support) |

---

**End of Level 5 Timer Analysis.**  
No code was modified; this is analysis only.
