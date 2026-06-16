# Level 5 — Visible Elements Per Step: Creative Ideas
## Fun, Connected, Indulging — No Boring Flows

**Goal:** One visible, delightful element (or set) per step so users feel connected and engaged.  
**Scope:** Search → Results → 5 booking steps → Payment (Stripe) → Confirmation.  
**Tech:** Level 5 expert/god-mode coding only (CSS, JS, SVG, Canvas — current stack).  
**No coding in this doc** — ideas and research only.

---

## Design principles

1. **Visible** — Every idea is something users **see** (no hidden Easter eggs as primary feature).
2. **Contextual** — Copy and visuals match the step (e.g. “Who’s flying?” on passengers).
3. **Light touch** — Delight, not distraction. No clutter.
4. **Responsive** — Works on desktop and mobile.
5. **Accessible** — `prefers-reduced-motion` respected, focus states, semantics.

---

## Step 1 — Search

**User state:** Planning, curiosity, “where to?”  
**Risk:** Feels like a generic form.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **1.1** | **Hero line** | A short headline above/beside the form: e.g. *“Where to?”* or *“Your next trip starts here”*. Optional small plane icon. | Sets a journey tone, not “form”. |
| **1.2** | **Animated Search CTA** | Primary button: “Find flights” or “Search” with a subtle plane icon. Hover: slight lift + glow. Click: ripple. Optional idle pulse. | Makes the main action feel alive. |
| **1.3** | **Per-field micro-success** | When origin/destination/dates are valid, a small checkmark or green border animates in next to the field (or on blur). | Instant positive feedback, reduces doubt. |
| **1.4** | **“Popular routes” chips** | 3–4 clickable chips (e.g. “NYC → Miami”, “LA → Vegas”). Click fills origin/destination. Hover: scale + shadow. | Reduces friction, adds discovery. |
| **1.5** | **Search-as-journey strip** | After submit (before redirect): a thin bar like *“Finding your flights…”* with a small animated plane moving across. | Bridges wait time, keeps user in “journey” mode. |

### Suggested combo (all visible)

- **Always:** Hero line (1.1) + Animated Search CTA (1.2).
- **Nice add:** Per-field micro-success (1.3).
- **If space:** Popular routes (1.4) and/or “Finding your flights…” strip (1.5).

---

## Step 2 — Results

**User state:** Comparing, deciding, “which one?”  
**Risk:** Feels like a dull list.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **2.1** | **Animated “X flights found”** | Count animates (e.g. 0 → 47) when results load. Optional “We found your flights” line above. | Feels responsive and tangible. |
| **2.2** | **Search summary strip** | Sticky or near-top strip: *“New York → Paris · 2 Adults · Dec 15–22”* with an “Edit” button. Clear, always visible. | Reminds user what they’re choosing for. |
| **2.3** | **Flight card lift + badge** | Cards: hover lift + shadow (existing). Add optional badges: “Best value”, “Popular”, “Fastest” (if data exists). Badge gets a small scale-in on load. | Cards feel premium; badges aid choice. |
| **2.4** | **“Select your flight” CTA strip** | Thin bar above or below results: *“Pick a flight to continue — you’re one step away from booking.”* | Gentle nudge without pressure. |
| **2.5** | **Skeleton loaders** | While loading, skeleton cards match real card layout (image, lines, buttons). Smooth fade to real content. | Professional loading, no blank wall. |
| **2.6** | **Book Now micro-celebration** | On “Book Now” click: brief button scale-down, optional very subtle particle burst (2–3 dots) or checkmark before redirect. | Marks “choice made” before leaving. |

### Suggested combo

- **Always:** Animated count (2.1) + Search summary strip (2.2) + Card lift + badges (2.3).
- **Nice add:** Skeleton loaders (2.5), “Select your flight” strip (2.4).
- **Optional:** Subtle micro-celebration on Book (2.6).

---

## Step 3 — Flights (Check your flights)

**User state:** “This is my flight” — confirming choice.  
**Risk:** Feels like a repeat of results.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **3.1** | **“Your flight” headline** | Clear H2/H3: *“Your flight”* or *“Here’s your flight”* with a small plane or ticket icon. Slight fade-in on step enter. | Owns the itinerary. |
| **3.2** | **Mini route line (SVG)** | Compact strip: *Origin — — — ✈️ — — — Destination* with a short animated path (e.g. `stroke-dasharray` draw). Optional departure/arrival times. | Visual “journey” in one glance. |
| **3.3** | **“Looking good” badge** | After a short delay or on first “Next” focus, a small badge: *“Looking good ✓”* near the itinerary. Fade-in. | Positive reinforcement. |
| **3.4** | **Section enter animation** | When step becomes active, itinerary cards fade-in and slide up slightly (stagger per card). | Clearly “new” step, not static. |
| **3.5** | **“Next: Passenger details” teaser** | Under Next button or at bottom: *“Next: Passenger details”* with a small user icon. | Sets expectation, reduces uncertainty. |

### Suggested combo

- **Always:** “Your flight” headline (3.1) + Mini route line (3.2) + Section enter (3.4).
- **Nice add:** “Looking good” badge (3.3) + “Next: Passenger details” teaser (3.5).

---

## Step 4 — Passengers (Fill passenger details)

**User state:** Form fatigue, “how many more fields?”  
**Risk:** Feels bureaucratic.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **4.1** | **“Who’s flying?” header** | Friendly headline instead of “Passenger details”. Optional multi-person icon. | Warmer, more human. |
| **4.2** | **Per-passenger block + avatar** | Each passenger in a card. Placeholder avatar (initials or generic icon). Optional colored left border per passenger. | Feels like “people”, not rows. |
| **4.3** | **Block-level completion check** | When a passenger block is valid, a green checkmark or “Complete” chip animates in. | Clear progress, less anxiety. |
| **4.4** | **“1 of 2 complete” micro-progress** | Small text or thin progress bar: *“1 of 2 passengers complete”*. Updates as they fill. | Visible progress through the step. |
| **4.5** | **“As on passport” reminder** | Short line with icon: *“Use names exactly as on passport.”* Kept visible but subtle. | Helpful, not naggy. |
| **4.6** | **“Next: Pick your seats” teaser** | Under Next: *“Next: Pick your seats”* with seat icon. | Forward-looking, fun. |

### Suggested combo

- **Always:** “Who’s flying?” (4.1) + Per-passenger blocks with avatar (4.2) + Block completion (4.3).
- **Nice add:** “1 of 2 complete” (4.4) + “As on passport” (4.5) + “Next: Pick your seats” (4.6).

---

## Step 5 — Seats (Select seats)

**User state:** Optional step, “do I bother?”  
**Risk:** Feels like an upsell or skip.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **5.1** | **“Window or aisle?” headline** | Playful header. Optional seat icon. | Light, low pressure. |
| **5.2** | **Seat map hover + select** | Hover: seat highlights (glow). Select: scale + fill transition. Selected seats list updates live. | Feels interactive, not static. |
| **5.3** | **“Skip is okay” CTA** | Skip button styled as secondary but friendly: *“Skip — I’ll take any seat”* or *“No preference”*. No guilt. | Makes skip feel valid. |
| **5.4** | **Selected seats summary** | Small list: *“12A, 12B”* with icons. Optional “Change” link. | Clear what they chose. |
| **5.5** | **“Next: Add-ons” teaser** | *“Next: Add-ons”* with gift/plus icon. | Smooth transition. |

### Suggested combo

- **Always:** “Window or aisle?” (5.1) + Seat hover/select feedback (5.2) + “Skip is okay” (5.3).
- **If seats available:** Selected summary (5.4). **Always:** “Next: Add-ons” (5.5).

---

## Step 6 — Add-ons

**User state:** “Do I need this?”  
**Risk:** Feels pushy or irrelevant.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **6.1** | **“Little extras for your trip” header** | Soft upsell tone. Optional gift icon. | Framed as perks, not must-buys. |
| **6.2** | **Add-on cards** | Each add-on in a card: icon, title, short description, price. Hover: lift + light shadow. Toggle (checkbox/chip) with clear selected state. | Scannable, optional. |
| **6.3** | **“Optional” badge** | Small “Optional” on each card or on the section. | Reduces pressure. |
| **6.4** | **“Recommended for you”** | For 1–2 add-ons (e.g. insurance): small “Recommended” chip. | Guidance without forcing. |
| **6.5** | **“Next: Review & pay” teaser** | *“Next: Review & pay”* with checkmark or shield icon. | Clear what’s next. |

### Suggested combo

- **Always:** “Little extras” (6.1) + Add-on cards (6.2) + “Optional” (6.3) + “Next: Review & pay” (6.5).
- **Nice add:** “Recommended” (6.4) where relevant.

---

## Step 7 — Review & Pay

**User state:** Committing, slight anxiety.  
**Risk:** Feels stressful or untrustworthy.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **7.1** | **“Final check” headline** | *“Final check”* or *“Review & confirm”* with shield or checkmark icon. | Reassurance. |
| **7.2** | **Review blocks + checkmarks** | Summaries for passengers, seats, add-ons. Each with a small ✓ or “Confirmed” when valid. Optional short entrance animation. | “Everything’s in order.” |
| **7.3** | **“You’re protected” strip** | Thin bar: lock icon + *“Secure payment”* or *“You’re protected.”* Near payment form. | Trust. |
| **7.4** | **Animated total** | Total amount counts up when section is in view (existing creative behaviour). | Feels considered, not random. |
| **7.5** | **Confirm & Book CTA** | Button: clear label, hover lift, optional very subtle pulse or glow. Primary, unmissable. | Clear “commit” moment. |
| **7.6** | **Trust badges** | Small icons (e.g. SSL, card brands) near footer or payment. | Extra credibility. |

### Suggested combo

- **Always:** “Final check” (7.1) + Review blocks with checkmarks (7.2) + “You’re protected” (7.3) + Animated total (7.4) + Confirm CTA (7.5).
- **Nice add:** Trust badges (7.6).

---

## Step 8 — Payment page (Stripe only)

**User state:** Paying, focus on security.  
**Risk:** Feels disconnected from the rest of the journey.

### Visible element ideas

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **8.1** | **“Secure payment” bar** | Top bar: lock icon + *“Secure payment”*. Sticky. | Reassurance at all times. |
| **8.2** | **Booking recap strip** | Short line: route + dates + total. Collapsible on mobile. | Reminds what they’re paying for. |
| **8.3** | **Same “Final check” tone** | Reuse “Final check” or “Complete your booking” with shield. | Consistency with Review step. |
| **8.4** | **Submit button styling** | Same as Confirm & Book: lift, ripple, clear label. | Familiar, confident. |

### Suggested combo

- **Always:** Secure bar (8.1) + Booking recap (8.2) + Final check tone (8.3) + Submit styling (8.4).

---

## Step 9 — Confirmation (already enhanced)

**User state:** Relief, excitement.  
**Current:** Confetti, “Adventure Awaits” badge, “P.S. You’re going to love this trip.”

### Extra visible element ideas (optional)

| # | Element | What it is | Why it works |
|---|--------|------------|--------------|
| **9.1** | **“What’s next” checklist** | Short list: *“Check email”*, *“We’ll call to confirm”*, *“Add to calendar”* (if you add that CTA). | Reduces “what now?” feeling. |
| **9.2** | **“Add to calendar” button** | Secondary CTA that opens `.ics` or calendar link. | Practical, shareable. |
| **9.3** | **Share strip** | “Share your trip” with Twitter/Facebook/WhatsApp (or copy link). | Social, word-of-mouth. |

### Suggested combo

- **Keep:** Current confetti + badge + P.S.
- **Optional add:** “What’s next” (9.1) + “Add to calendar” (9.2) and/or Share (9.3).

---

## Summary — visible elements per step

| Step | Must-have visible elements | Nice-to-have |
|------|----------------------------|--------------|
| **Search** | Hero line, Animated Search CTA | Per-field success, Popular routes, “Finding flights…” strip |
| **Results** | Animated count, Search strip, Card lift + badges | Skeletons, “Select your flight” strip, Micro-celebration on Book |
| **Flights** | “Your flight” headline, Mini route line, Section enter | “Looking good” badge, “Next: Passenger details” |
| **Passengers** | “Who’s flying?”, Passenger blocks + avatar, Block completion | “1 of 2 complete”, “As on passport”, “Next: Seats” |
| **Seats** | “Window or aisle?”, Hover/select feedback, “Skip is okay” | Selected summary, “Next: Add-ons” |
| **Add-ons** | “Little extras”, Add-on cards, “Optional”, “Next: Review & pay” | “Recommended” |
| **Review & Pay** | “Final check”, Review blocks + ✓, “You’re protected”, Animated total, Confirm CTA | Trust badges |
| **Payment (Stripe)** | Secure bar, Booking recap, Final-check tone, Submit styling | — |
| **Confirmation** | (Existing) Confetti, Badge, P.S. | “What’s next”, Add to calendar, Share |

---

## Implementation notes (high level)

- **Copy:** All headlines, teasers, and badges are explicit UI strings (or i18n keys). No logic-only tricks.
- **Animation:** CSS transitions + `@keyframes`; JS for count-up, stagger, optional particles. Respect `prefers-reduced-motion`.
- **Icons:** Inline SVG or icon font. Plane, user, seat, gift, shield, lock, checkmark — all visible.
- **Layout:** Use existing sections (`#amadex-section-*`, etc.). Add small wrapper divs or spans where needed for badges, strips, teasers.
- **State:** Use `data-step`, `amadex_booking_step`, or step emitted events to show/hide step-specific elements.

---

## Principles checklist

- [x] **Visible** — Every idea is something users see.
- [x] **Per-step** — Each step has at least 2–3 concrete elements.
- [x] **Fun & connected** — Headlines, teasers, and micro-moments keep users in a “journey” mindset.
- [x] **Level 5 implementable** — CSS, JS, SVG, Canvas only; no new stack.
- [x] **Not boring** — Variety: copy, icons, animation, progress, trust. Avoid “generic form” feel.

---

**Status:** Research and ideas only. No code changes. Ready for prioritisation and implementation.
