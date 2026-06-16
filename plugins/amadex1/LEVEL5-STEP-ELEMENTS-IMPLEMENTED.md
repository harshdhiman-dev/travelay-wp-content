# Level 5 Step Elements — Implementation Complete

**Status:** Implemented  
**Scope:** Search → Results → Booking (5 steps) → Payment page  
**Files:** `assets/css/amadex-step-elements.css`, `assets/js/amadex-step-elements.js`

---

## What Was Implemented

### 1. **Search**
- **Hero:** "Your next trip starts here" + plane icon (standalone search only; skipped on results page).
- **Popular routes:** Chips (NYC→Miami, LA→Las Vegas, Chicago→Orlando); click fills origin/destination.
- **Finding strip:** "Finding your flights…" + animated plane on form submit (before redirect).

### 2. **Results**
- **Hero:** "We found your flights" above search summary.
- **Select strip:** "Pick a flight to continue — you're one step away from booking." above flight cards.
- **Count animation:** Animated count when `#amadex-results-count` / `#amadex-mobile-results-count-display` updates (MutationObserver).
- **Book Now micro-celebration:** Small dot burst on "Book Now" click (respects `prefers-reduced-motion`).

### 3. **Booking Steps**
- **Flights:** "Your flight" hero, mini route (origin — ✈ — destination from `amadex_booking_flight`), "Looking good" badge after delay, "Next: Passenger details" teaser, section enter animation.
- **Passengers:** "Who's flying?" hero, "Next: Pick your seats" teaser, section enter.
- **Seats:** "Window or aisle?" hero, "Skip — I'll take any seat" under skip button, "Next: Add-ons" teaser, section enter.
- **Add-ons:** "Little extras for your trip" hero, "Optional" badge on subtitle, "Next: Review & pay" teaser, section enter.
- **Review:** "Final check" hero, "You're protected" strip before payment block, section enter. No "next" teaser.

### 4. **Payment Page (Stripe)**
- **Secure bar:** "Secure payment" + lock icon at top of payment container.

### 5. **Integration**
- **`amadex-booking.js`:** Fires `amadexBookingStepChanged` on step change (`navigateToStep`) and on initial load.
- **Shortcodes:** Enqueue `amadex-step-elements.css` (after creative-experience) and `amadex-step-elements.js` (after amadex-booking).

---

## Files Touched

| File | Changes |
|------|---------|
| `assets/css/amadex-step-elements.css` | **New** — Step visuals (hero, badge, teaser, strip, route, micro-progress, popular, finding, protected, secure bar, dots, section enter). |
| `assets/js/amadex-step-elements.js` | **New** — Inits for search, results, booking steps, payment; count animation; Book Now dots. |
| `includes/frontend/class-amadex-shortcodes.php` | Enqueue step-elements CSS/JS. |
| `assets/js/amadex-booking.js` | Fire `amadexBookingStepChanged` in `navigateToStep` and on initial step. |

---

## Behaviour

- **`prefers-reduced-motion`:** Animations disabled or simplified where relevant.
- **No double-inject:** Sections use `data-amadex-se-enhanced`; heroes/badges/teasers injected once per section.
- **Step-aware:** Step-elements runs only when relevant containers exist (search, results, booking, payment).

---

## How to Test

1. **Search:** Standalone search page → hero + popular chips; submit → "Finding your flights" strip.
2. **Results:** Load results → "We found your flights" + select strip; count animates when results load; "Book Now" → dot burst.
3. **Booking:** Go through steps 1–5 → each step shows hero, teaser (or protected), route (flights), badge (flights), optional (add-ons), skip copy (seats).
4. **Payment (Stripe):** Payment page → secure bar at top.
5. **Confirmation:** Existing creative experience (confetti, badge, P.S.) unchanged.

---

## Checklist

- [x] Search hero, popular, finding strip
- [x] Results hero, select strip, count animation, Book Now dots
- [x] Flights: hero, route, badge, teaser, section enter
- [x] Passengers: hero, teaser, section enter
- [x] Seats: hero, skip copy, teaser, section enter
- [x] Add-ons: hero, optional, teaser, section enter
- [x] Review: hero, protected, section enter
- [x] Payment page: secure bar
- [x] `amadexBookingStepChanged` fired; step-elements listen and enhance
- [x] Assets enqueued; no regressions intended

---

**Status:** Implementation complete. Run a full booking flow to verify.
