# Amadex Complete Live Test Plan

**Generated:** February 3, 2026  
**Scope:** Full manual test plan for all Amadex functions  
**Automated tests:** None found (Amadex has no PHPUnit config)

---

## URL Verification (Static Fetch)

| URL | Status | Notes |
|-----|--------|-------|
| https://travelaystagging.com/ | ✅ Loads | Homepage with search form (Round Trip, Origin, Destination, Dates) |
| https://travelaystagging.com/flight-results/ | ✅ Loads | Results page structure; filters; "Searching for flights…" placeholder |
| https://travelaystagging.com/flight-results/?origin_iata=SIN&destination_iata=MIA&depart_date=2026-02-10&return_date=2026-02-17 | ✅ Loads | Same; JS fetches results via AJAX after load |
| admin-ajax.php | ⏱ Timeout | Expected – requires POST; not testable via static fetch |

---

## 1. Shortcodes

| Shortcode | Page/Use | Test Steps |
|-----------|----------|------------|
| `[amadex_flight_search]` | Legacy search form | Add to page; submit search; verify redirect to flight-results |
| `[amadex_search_modern]` | **Primary** search form | Add to page; fill origin, destination, dates; submit; verify results or redirect |
| `[amadex_flight_results]` | Results page | Use on /flight-results/; verify flight cards, filters, sort |
| `[amadex_flight_booking]` | Booking page | After selecting flight, go to booking; verify passenger form, flight summary |
| `[amadex_booking_confirmation]` | Confirmation page | After booking; verify reference, itinerary, totals |
| `[amadex_payment]` | Payment page | After booking; verify Stripe/NMI/PayPal options |
| `[amadex_test_form]` | Dev/test | Submit; verify API response |
| `[amadex_api_test]` | Dev/test | Run; verify API connectivity |
| `[amadex_regional_settings]` | Regional modal button | Click; verify modal; change currency/language; save |
| `[amadex_travel_deals]` | Deals block | Verify deals display |

---

## 2. AJAX Handlers (Frontend)

| Action | Purpose | Test Method |
|--------|---------|-------------|
| `amadex_search_flights` | Flight search | Search form → verify JSON response with flights |
| `amadex_search_airports` / `amadex_airports` | Airport autocomplete | Type in origin/destination → verify suggestions |
| `amadex_get_flight_details` | Flight detail modal | Click flight → verify detail popup |
| `amadex_filter_flights` | Filter/sort | Apply filters on results → verify filtered list |
| `amadex_get_promotional_containers` | Promo blocks | Results page → verify promo containers between flights |
| `amadex_get_skeleton` | Loading skeleton | Trigger search → verify skeleton UI |
| `amadex_get_loading_animation` | Loading animation | Trigger search → verify loading animation |
| `amadex_recalculate_price` | Price recalc | Change pax/seats → verify price update |
| `amadex_get_seatmap` | Seat map | On booking → select seats → verify seatmap |
| `amadex_price_selected_seats` | Seat pricing | Select seats → verify price change |
| `amadex_convert_currency` | Currency conversion | Change currency → verify converted prices |
| `amadex_get_exchange_rate` | Exchange rate | Verify rate returned |
| `amadex_get_user_location` | Geo/IP | Verify country/currency suggestion |
| `amadex_store_booking_for_payment` | Store booking | Proceed to payment → verify token stored |
| `amadex_get_booking_for_payment` | Retrieve booking | Payment page load → verify booking data |
| `amadex_delete_booking_token` | Clear token | After payment/cancel → verify token cleared |
| `amadex_aircraft_details` | Aircraft info | Click aircraft code → verify details |
| `amadex_create_payment_intent` | Stripe PaymentIntent | Payment flow → verify intent created |
| `amadex_create_elements_session` | Stripe Elements | Payment page → verify session |
| `amadex_checkout_session_status` | Stripe status | Poll during checkout |
| `amadex_stripe_webhook` | Stripe webhook | Stripe dashboard → send test event |
| `amadex_process_booking` | Submit booking | Fill passenger details → submit → verify lead/booking |
| `amadex_confirm_booking` | Confirm (NMI) | NMI flow confirmation |
| `amadex_diagnose_booking` | Debug | Admin/diagnostic use |
| `amadex_create_lead` | Create lead | Form submit → verify lead in admin |
| `amadex_get_country_states` | Country states | Address form → verify state dropdown |
| `amadex_get_deals` | Deals data | Deals shortcode → verify deals load |

---

## 3. AJAX Handlers (Admin)

| Action | Purpose | Test Method |
|--------|---------|-------------|
| `amadex_create_database_tables` | DB setup | Database Setup page → Create tables |
| `amadex_check_database_status` | DB status | Database Setup → verify status |
| `amadex_apply_performance_settings` | Perf settings | Performance Metrics → save |
| `amadex_delete_performance_metrics` | Delete metrics | Performance Metrics → delete selected |
| `amadex_get_leads` | List leads | Leads page → verify list |
| `amadex_assign_lead` | Assign agent | Lead → Assign |
| `amadex_update_lead_status` | Update status | Lead → Change status |
| `amadex_get_lead_details` | Lead details | Click lead → view |
| `amadex_get_booking_details` | Booking details | Bookings → view |
| `amadex_update_booking_status` | Booking status | Booking → Update status |
| `amadex_update_booking_pnr` | PNR | Booking → Add PNR |
| `amadex_bulk_delete_leads` | Bulk delete | Select leads → Delete |
| `amadex_set_environment` | Env switch | Toggle prod/test |
| `amadex_delete_booking` | Delete booking | Booking → Delete |
| `amadex_bulk_delete_bookings` | Bulk delete | Select bookings → Delete |
| `amadex_generate_pdf` | PDF | Booking → Generate PDF |
| `amadex_export_leads` | Export leads | Leads → Export |
| `amadex_export_bookings` | Export bookings | Bookings → Export |
| `amadex_test_api` | API test | Settings → Test API |
| `amadex_clear_token_cache` | Clear token | Settings → Clear cache |
| `amadex_test_email` | Email test | Settings → Send test email |
| `amadex_preview_email` | Email preview | Settings → Preview email |
| `amadex_get_pricing_rule` | Pricing rule | Pricing → Get rule |
| `amadex_save_pricing_rule` | Save rule | Pricing → Save |
| `amadex_delete_pricing_rule` | Delete rule | Pricing → Delete |
| `amadex_simulate_pricing` | Simulate | Pricing → Simulate |

---

## 4. Admin Pages

| Page | Path | Test Steps |
|------|------|------------|
| Dashboard | Amadex → Dashboard | Load; verify widgets, shortcode |
| API Settings | Amadex → API Settings | Load; save; verify Amadeus keys |
| Documentation | Amadex → Documentation | Load; verify content |
| Airports | Amadex → Airports | Load; verify airport list/import |
| Database Setup | Amadex → Database Setup | Create tables; verify success |
| Performance Metrics | Amadex → Performance Metrics | Load; verify Recent Search Performance; check route info for new searches |
| All Leads | Flight Leads → All Leads | Load; list; assign; update status |
| Bookings | Flight Leads → Bookings | Load; list; view; update PNR; PDF |
| Settings (General, Payment, etc.) | Amadex → API Settings (tabs) | Load each tab; save; verify |

---

## 5. REST API Endpoints

| Endpoint | Method | Purpose | Test |
|----------|--------|---------|------|
| `/amadex/v1/bookings` | GET | List bookings | `GET /wp-json/amadex/v1/bookings` (auth) |
| `/amadex/v1/bookings/{id}` | GET | Booking detail | `GET /wp-json/amadex/v1/bookings/1` |
| `/amadex/v1/leads` | GET | List leads | `GET /wp-json/amadex/v1/leads` (auth) |
| `/amadex/v1/stats` | GET | Stats | `GET /wp-json/amadex/v1/stats` |
| `/tdf/v1/offers` | GET | Top deals offers | `GET /wp-json/tdf/v1/offers` |

---

## 6. Redis Verification

| Check | Steps |
|-------|-------|
| Connection | See debug.log: `Redis: Successfully connected using Predis (tcp)` |
| Cache HIT | Search SIN→MIA; second identical search within TTL; log: `Redis: Cache HIT for key: flight_search:...` |
| Cache MISS | New route or after TTL; log: `Amadex Search: Cache MISS - Fetching from API` |
| Keys in Redis | Redis Insight/CLI: `KEYS *amadex*` or `KEYS flight_search:*` |
| TTL | `TTL flight_search:9b158fb329d23b86c8d1cb060a9e1811` (default 300s) |

---

## 7. Critical User Flows

### Flow A: Search → Results → Booking → Payment

1. Homepage: enter origin, destination, dates → Search  
2. Flight-results: verify flights; apply filters; select flight → Book  
3. Booking: fill passengers; add seats/addons if any → Proceed to payment  
4. Payment: Stripe/NMI; complete → Confirmation page  

### Flow B: Direct URL to Results

1. Open `/flight-results/?origin_iata=SIN&destination_iata=MIA&depart_date=2026-02-10&return_date=2026-02-17`  
2. Verify: `fetchResultsFromURL` runs; flights load; promos appear  

### Flow C: Cache HIT

1. Search SIN→MIA (cache MISS first time)  
2. Immediately search SIN→MIA again  
3. Verify: instant response; debug.log shows Cache HIT  

---

## 8. Edge Cases

| Case | Test |
|------|------|
| One-way | Trip type One-way; no return date; search |
| Multi-city | Add segments; search each |
| Empty results | Route with no flights; verify "No flights found" |
| Invalid dates | Past date; return before departure; verify validation |
| Nonce expiry | Wait; submit form; verify nonce error |
| Session/storage blocked | Browser Tracking Prevention on; verify fallback (fetchResultsFromURL) |

---

## 9. Automated Tests

**Result:** Amadex has no PHPUnit or similar test suite.  
The `vendor/maennchen/zipstream-php/phpunit.xml.dist` belongs to a dependency, not Amadex.

---

## 10. Summary

- **Static URLs:** Homepage and flight-results load.  
- **AJAX:** 40+ handlers; test via UI or browser Network tab.  
- **Shortcodes:** 10 shortcodes; verify on assigned pages.  
- **Admin:** 8+ pages under Amadex and Flight Leads.  
- **REST:** 5 endpoints; test with auth.  
- **Redis:** Confirmed working (Cache HIT in logs).  
- **Manual execution:** Follow sections 1–8 for full coverage.
