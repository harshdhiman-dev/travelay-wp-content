# Amadex Implementation Status & Redis Confirmation

**Date:** February 3, 2026  
**Status:** Implementations complete | Redis verified working

---

## Redis: Working ✓

- **Connection:** Predis (tcp) – successful
- **Cache HIT confirmed:** `flight_search:9b158fb329d23b86c8d1cb060a9e1811`
- **Flow:** SIN → MIA search returned cached results from Redis; no Amadeus API call needed
- **No SSL/TLS errors** (TCP connection used; Redis Cloud Free tier)

---

## Completed Implementations

| Area | Fix / Change |
|------|--------------|
| **Recent Search Performance** | `save_metrics()` now receives route params (origin, destination, dates, passengers, flights_found) – admin table shows full route info for new searches |
| **Amadex Textdomain** | Loaded at `init:0` – no more "translation loading too early" for Amadex |
| **API Timeout** | Default increased from 10s to 30s |
| **Redis Cloud** | TLS not forced for Free tier; TCP-first connection; error suppression during attempts |
| **Promotional Containers** | `position: relative; overflow: hidden` added inline and in CSS |
| **Loading Templates** | `loading-animation.php`, `loading-skeleton.php`, `amadex-loading-animations.css` created |

---

## Remaining Notices (Non-Critical)

| Notice | Source | Action |
|--------|--------|--------|
| `acf/top-deals-flights` already registered | Theme / Top Deals plugin | Guard checks in place; may need deployment or further ordering fix |
| `complianz-gdpr` translation too early | Complianz plugin | Third-party; outside Amadex scope |

---

## Final Note

**Redis is working.** Flight search results are cached and served from Redis as expected. Cache HITs return instantly without calling the Amadeus API.
