# Level 5 — Booking Flow: Frontend Steps (Deep Analysis)

**Scope:** Frontend only. No backend logic.  
**Purpose:** Map every user-visible step from search to confirmation.  
**No coding** — research and documentation only.

---

## 1. Overview

The booking flow has **three phases** before confirmation:

1. **Pre-booking** — Search → Results → Select flight → redirect to booking.
2. **Booking page** — Multi-step form (5 steps) with progress indicator.
3. **Payment (Stripe only)** — Separate payment page, then redirect to confirmation.

**Confirmation** is a distinct page (success state), not a "step" in the step engine.

---

## 2. Phase 1 — Pre-Booking (Before /flight-booking/)

### 2.1 Step: **Search**

| Attribute | Detail |
|-----------|--------|
| **Shortcode** | `amadex_flight_search` or `amadex_search_modern` |
| **Typical URL** | Homepage or dedicated search page (site-dependent) |
| **User action** | Enter origin, destination, dates, passengers, cabin, etc. → Submit |
| **Frontend** | Form (modern or legacy). Submit triggers AJAX `amadex_search` or similar. |
| **Outcome** | User is sent to **Results** page (same tab or redirect). |
| **Step-like?** | No step indicator. Standalone page/section. |

**Notes:**

- Search UI can be embedded elsewhere (e.g. results “Modify” uses `render_modern_search`).
- No `amadex_booking_step` or `BOOKING_STEPS` here; this is pre-booking.

---

### 2.2 Step: **Results**

| Attribute | Detail |
|-----------|--------|
| **Shortcode** | `amadex_flight_results` |
| **Typical URL** | e.g. `/flights/` or results permalink (site-dependent) |
| **User action** | View flights, optionally filter/sort. **Select flight** via “Book Now” or “Review Details” → “Book”. |
| **Frontend** | Results list, filters, search summary (“Modify”), flight cards. |
| **Outcome** | Flight stored in `sessionStorage` (`amadex_booking_flight`), `amadex_results_page_url` stored, redirect to **Booking** page (`/flight-booking/` or `AmadexConfig.bookingPageUrl`). |
| **Step-like?** | No step indicator. Distinct page. |

**Select-flight actions:**

- **“Book Now”** — `a.amadex-book-now-btn` with `data-flight-data`. Click → store flight → redirect to booking.
- **“Review Details”** — `button.amadex-select-flight-btn` opens modal. From modal, user can proceed to book → same store + redirect.

**Multi-city:** User selects one flight per segment; when all segments chosen, same storage + redirect. `amadex_multi_city_bookings` / `amadex_booking_all_segments` used.

**Notes:**

- Results page includes embedded search (Modify). So “Search” exists as UI here too, but the **step** we care about is “Results / Select flight”.
- No `amadex_booking_step` or `BOOKING_STEPS` on this page.

---

## 3. Phase 2 — Booking Page (/flight-booking/)

**Shortcode:** `amadex_flight_booking`  
**Container:** `#amadex-booking-page`, `.amadex-booking-page`  
**Step engine:** `BOOKING_STEPS` in `amadex-booking.js`; `data-section` / `data-step` in PHP.

Steps are **linear**: user moves **Next** / **Back** (or stepper clicks). Only **one** section visible at a time (mobile and desktop). URL syncs via `?step=`.

---

### 3.1 Booking Step 1 — **Flights** (Check your flights)

| Attribute | Detail |
|-----------|--------|
| **Internal id** | `flights` |
| **Order** | `0` (first) |
| **Section** | `#amadex-section-flights`, `data-section="flights"`, `data-step="1"` |
| **Label** | “Check your flights” |
| **User action** | Review itinerary (and price). Collapsible flight cards. Click **Next** to continue. |
| **Navigation** | “Next” → `passengers`. “Back to search results” → results page (using `amadex_results_page_url` when available). |
| **Visibility** | Shown only when `step === 'flights'`. |

**Mobile vs desktop:**

- **Initial load:** Both start at `flights` when arriving from results (no `?step=`).
- **Back/forward:** On `popstate`, if `?step=flights` and mobile (≤767px), step is **overridden to `passengers`** (flights “skipped” when navigating back on mobile).

---

### 3.2 Booking Step 2 — **Passengers** (Fill passenger details)

| Attribute | Detail |
|-----------|--------|
| **Internal id** | `passengers` |
| **Order** | `1` |
| **Section** | `#amadex-section-passengers`, `data-section="passengers"`, `data-step="2"` |
| **Label** | “Fill passenger details” / “Enter passenger details” |
| **User action** | Fill passenger forms (names, etc. per passport). **Next** → seats. |
| **Navigation** | **Back** → `flights`. **Next** → `seats`. |
| **Visibility** | Shown only when `step === 'passengers'`. |

---

### 3.3 Booking Step 3 — **Seats** (Select seats)

| Attribute | Detail |
|-----------|--------|
| **Internal id** | `seats` |
| **Order** | `2` |
| **Section** | `#amadex-seat-selection-section`, `data-section="seats"`, `data-step="3"` |
| **Label** | “Select seats” |
| **User action** | Use seat map (if available) or **Skip seat selection**. **Next** → add-ons. |
| **Navigation** | **Back** → `passengers`. **Next** → `addons`. |
| **Visibility** | Shown only when `step === 'seats'`. |

**States:** Loading, “Seat selection not available”, or seat map + selected seats summary.

---

### 3.4 Booking Step 4 — **Add-ons**

| Attribute | Detail |
|-----------|--------|
| **Internal id** | `addons` |
| **Order** | `3` |
| **Section** | `#amadex-addons-section`, `data-section="addons"`, `data-step="4"` |
| **Label** | “Add-ons” |
| **User action** | Opt in to add-ons (e.g. TravelayGent, TravelaySurance). **Next** → review. |
| **Navigation** | **Back** → `seats`. **Next** → `review`. |
| **Visibility** | Shown only when `step === 'addons'`. |

---

### 3.5 Booking Step 5 — **Review & Pay**

| Attribute | Detail |
|-----------|--------|
| **Internal id** | `review` |
| **Order** | `4` |
| **Sections** | `#amadex-review-section`, `#amadex-contact-section`, `#amadex-billing-section`, `#amadex-payment-section`, `#amadex-agreement-section`. All share `data-section="review"`. |
| **Label** | “Review & Pay” / “Review & Confirm” |
| **User action** | Review itinerary, passengers, seats, add-ons; fill **contact** and **billing**; choose **payment method** and complete payment (NMI inline or Stripe redirect); accept **agreement**. Then **Confirm & Book**. |
| **Navigation** | **Back** → `addons`. **Edit** links for passengers, seats, add-ons → `navigateToStep(passengers|seats|addons)`. No “Next”; **Confirm & Book** submits. |
| **Visibility** | All review sub-sections shown together when `step === 'review'`. |

**Sub-sections (same step, same view):**

1. **Review** — Passenger summary, seats summary, add-ons summary, price breakdown.
2. **Contact** — Contact details.
3. **Billing** — Billing address.
4. **Payment** — Method tabs (e.g. Credit card, Crypto, PayPal). For **NMI**, card form inline (CollectJS iframes). For **Stripe**, no card form here; user is sent to **payment page** after **Confirm & Book**.
5. **Agreement** — Terms etc. + **Confirm & Book** (and **#amadex-step-next** on mobile).

**Notes:**

- Single **Review** step; no sub-step navigation. Contact, billing, payment, agreement are just blocks in that step.
- **Confirm & Book** — `#amadex-confirm-book` (desktop, can be hidden depending on layout) and `#amadex-step-next` (mobile). Flow depends on gateway (NMI vs Stripe).

---

## 4. Phase 3 — Payment Page (Stripe Only)

| Attribute | Detail |
|-----------|--------|
| **Shortcode** | `amadex_payment` |
| **Typical URL** | Dedicated payment page (from `response.data.payment_url` after storing booking for Stripe). |
| **When** | Only when **Stripe** is the active gateway. User clicks **Confirm & Book** on **Review** → booking stored via AJAX → redirect to this page. |
| **User action** | Enter card (or other Stripe payment), submit. |
| **Outcome** | Success → redirect to **Confirmation** (`reference=` or similar). |
| **Step-like?** | Separate page. Not part of `BOOKING_STEPS`. |

**NMI:** No separate payment page. Card form is inline on **Review**; tokenize → `process_booking` → redirect to confirmation.

---

## 5. Phase 4 — Confirmation

| Attribute | Detail |
|-----------|--------|
| **Shortcode** | `amadex_booking_confirmation` |
| **Typical URL** | e.g. `/booking-confirmation/?reference=...` or configured confirmation page. |
| **When** | After successful payment (NMI or Stripe) and backend processing. |
| **User action** | View confirmation, optional print/copy reference. |
| **Step-like?** | No. Success / terminal page. |

---

## 6. Summary — Count of Frontend “Steps”

### 6.1 By **page / phase**

| # | Phase | Page / Step | Shortcode / Section | Navigable step? |
|---|--------|-------------|---------------------|------------------|
| 1 | Pre-booking | **Search** | `amadex_flight_search` / `amadex_search_modern` | No (standalone page) |
| 2 | Pre-booking | **Results** | `amadex_flight_results` | No (standalone page) |
| 3 | Booking | **Flights** | `#amadex-section-flights` | Yes (step 1) |
| 4 | Booking | **Passengers** | `#amadex-section-passengers` | Yes (step 2) |
| 5 | Booking | **Seats** | `#amadex-seat-selection-section` | Yes (step 3) |
| 6 | Booking | **Add-ons** | `#amadex-addons-section` | Yes (step 4) |
| 7 | Booking | **Review & Pay** | `#amadex-review-section` + contact, billing, payment, agreement | Yes (step 5) |
| 8 | Payment (Stripe only) | **Payment page** | `amadex_payment` | Yes (separate page) |
| 9 | Post-booking | **Confirmation** | `amadex_booking_confirmation` | No (terminal page) |

So there are **9** distinct frontend stages. Of these, **6** are “navigable steps” in the narrow sense (5 on booking + 1 Stripe payment page when applicable).

### 6.2 By **progress bar** (booking page only)

The booking **progress stepper** shows **5** steps:

1. Check your flights  
2. Fill passenger details  
3. Select seats  
4. Add-ons  
5. Review & Pay  

So **5** user-visible steps on the booking page itself.

### 6.3 By **gateway**

- **NMI:**  
  Search → Results → Booking (5 steps) → **Confirm & Book** on Review → Confirmation.  
  **No** separate payment page.

- **Stripe:**  
  Search → Results → Booking (5 steps) → **Confirm & Book** on Review → **Payment page** → Confirmation.  
  **One** extra frontend page (payment).

---

## 7. Technical Reference (Frontend)

### 7.1 Step definitions (`amadex-booking.js`)

```text
BOOKING_STEPS = {
  flights:   { order: 0, label: 'Check your flights',    section: 'flights' },
  passengers: { order: 1, label: 'Fill passenger details', section: 'passengers' },
  seats:     { order: 2, label: 'Select seats',           section: 'seats' },
  addons:    { order: 3, label: 'Add-ons',                section: 'addons' },
  review:    { order: 4, label: 'Review & Pay',           section: 'review' }
}
```

### 7.2 Section → step mapping (shortcodes)

| Section ID | `data-section` | `data-step` |
|------------|----------------|-------------|
| `#amadex-section-flights` | `flights` | `1` |
| `#amadex-section-passengers` | `passengers` | `2` |
| `#amadex-seat-selection-section` | `seats` | `3` |
| `#amadex-addons-section` | `addons` | `4` |
| `#amadex-review-section` (+ contact, billing, payment, agreement) | `review` | `5` |

### 7.3 Navigation

- **Next / Back:** `navigateToStep(stepName, addToHistory)`, `updateSectionVisibility(stepName)`, `updateProgressStepper(stepName)`.
- **Stepper clicks:** `initProgressNavigation` → click on `.booking-step[data-step]` → `navigateToStep(step)`.
- **Edit links:** `a.amadex-step-link[data-step]` → `navigateToStep(step)`.
- **URL:** `?step=flights|passengers|seats|addons|review`.
- **Storage:** `amadex_booking_step`, `amadexBookingStage`.

### 7.4 Mobile specifics

- **Flights skip:** On `popstate`, if mobile and `?step=flights`, step is switched to `passengers`.
- **Confirm & Book:** Mobile uses `#amadex-step-next`; desktop may use `#amadex-confirm-book`. Both can trigger the same submit logic.

---

## 8. End-to-end flow (high level)

```text
[ Search ] → [ Results ] → (Select flight) → [ Booking: Flights → Passengers → Seats → Add-ons → Review ]
    → (Confirm & Book)
        → [ Payment page ] (Stripe only)
        → [ Confirmation ]
```

---

**Document version:** 1.0  
**Last updated:** From codebase analysis (shortcodes, `amadex-booking.js`, `amadex.js`).  
**Status:** Level 5 frontend-only analysis, no code changes.
