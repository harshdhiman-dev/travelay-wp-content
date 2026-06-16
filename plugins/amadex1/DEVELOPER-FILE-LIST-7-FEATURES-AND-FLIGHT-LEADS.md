# Developer File List: 7 Features + Flight Leads Management

**Purpose:** Use this list to update or add **only** the files below when deploying the 7 booking features and Flight Leads Management changes.  
**Plugin root:** `wp-content/plugins/amadex1 (3) seat working (3)/`  
All paths are relative to that root.

---

## Part A — Our 7 Features (Added/Improved/Modified)

| # | Path | Feature(s) |
|---|------|------------|
| 1 | `includes/amadex-ajax.php` | Duplicate booking prevention (locks, request hash, check/acquire/release); Request hash generation; Booking locks lifecycle (ensure_tables_ready before process_booking); DUPLICATE_REQUEST API response; AJAX error handling (backend). |
| 2 | `includes/class-amadex-database.php` | Booking locks table (`wp_amadex_booking_locks`) create/schema; `ensure_tables_ready` includes booking_locks; lifecycle for duplicate prevention. |
| 3 | `assets/js/amadex-booking.js` | Request hash generation fix (`generateRequestHash`); JS syntax fixes (`errorMessage`, `submitBtn`); Confirm & Book / NMI CollectJS flow; DUPLICATE_REQUEST handling; AJAX error handling (frontend); **Processing booking modal** (`showBookingProcessingModal`, `hideBookingProcessingModal`, `updateProcessingMessage`, `buildBookingProcessingModalHTML`). |
| 4 | `assets/css/amadex-booking.css` | **Processing booking modal styles** (`.amadex-booking-processing-overlay`, `.amadex-booking-processing-modal`, spinner, animations, responsive design). |
| 5 | `amadex.php` | `ensure_tables_ready` on `plugins_loaded` (amadex_force_db_check) for booking locks lifecycle. |

---

## Part B — Flight Leads Management (Added/Improved/Modified)

### New files (add these)

| # | Path | Feature(s) |
|---|------|------------|
| 1 | `includes/class-amadex-pdf-generator.php` | PDF generation for leads/bookings. |
| 2 | `includes/class-amadex-data-exporter.php` | CSV/XLSX/PDF export for leads and bookings. |
| 3 | `includes/class-amadex-assignment-engine.php` | Agent auto-assignment (round-robin, territory, skill, load, value). |
| 4 | `includes/class-amadex-analytics.php` | Analytics dashboard for leads/bookings. |
| 5 | `includes/class-amadex-fraud-detection.php` | Fraud detection (device fingerprint, IP, behavior, score, risk level). |
| 6 | `includes/class-amadex-environment-manager.php` | Environment detection (production/test/staging). |
| 7 | `assets/js/amadex-fraud-detection.js` | Frontend fraud: device fingerprint, behavior tracking, `getCompleteFraudData()`. |

### Modified files (update these)

| # | Path | Feature(s) |
|---|------|------------|
| 1 | `amadex.php` | Require PDF, Data Exporter, Assignment Engine, Analytics (Lines 50–53). |
| 2 | `composer.json` | Dependencies: `dompdf/dompdf`, `phpoffice/phpspreadsheet` (for PDF & export). |
| 3 | `includes/amadex-ajax.php` | Fraud detection in `process_booking` (device_fingerprint, process_fraud_data, log_fraud_data); Auto-assign lead (Assignment Engine); `get_client_ip()`. |
| 4 | `includes/class-amadex-database.php` | Fraud columns on leads/bookings; `wp_amadex_fraud_logs`; fraud data in `create_lead` / `create_booking`; migrations for fraud schema. |
| 5 | `includes/admin/class-amadex-leads.php` | Fraud Score column; Fraud Detection & Verification in lead modal; PDF links in booking modal; Export (CSV/XLSX/PDF); Analytics page; AJAX: `ajax_generate_pdf`, `ajax_export_leads`, `ajax_export_bookings`. |
| 6 | `includes/frontend/class-amadex-shortcodes.php` | Enqueue `amadex-fraud-detection.js`; `amadex-booking` depends on fraud-detection; `bypassPayment` (and related config) for booking page. |
| 7 | `assets/js/amadex-booking.js` | Device fingerprint collection (`AmadexFraudDetection.getCompleteFraudData`); send `device_fingerprint` in AJAX for `amadex_process_booking`; **Processing booking modal** functions. |
| 8 | `assets/css/amadex-booking.css` | **Processing booking modal styles** (overlay, modal, spinner, animations, responsive). |

---

## Part C — Consolidated List (All Paths, No Duplicates)

Use this for a single checklist. Each file appears once; some apply to both the 7 features and Flight Leads Management.

| # | Path | Heading (feature hint) |
|---|------|------------------------|
| 1 | `amadex.php` | Plugin bootstrap; ensure_tables_ready (7 features); require Flight Leads classes (PDF, exporter, assignment, analytics). |
| 2 | `composer.json` | Composer deps: dompdf, phpspreadsheet (PDF & export). |
| 3 | `includes/amadex-ajax.php` | process_booking: duplicate prevention, locks, request hash, DUPLICATE_REQUEST (7); fraud detection, assignment engine (Flight Leads). |
| 4 | `includes/class-amadex-database.php` | booking_locks table, ensure_tables_ready (7); fraud columns, fraud_logs, create_lead/create_booking fraud handling (Flight Leads). |
| 5 | `includes/class-amadex-pdf-generator.php` | PDF generation for leads/bookings. |
| 6 | `includes/class-amadex-data-exporter.php` | CSV/XLSX/PDF export. |
| 7 | `includes/class-amadex-assignment-engine.php` | Agent auto-assignment. |
| 8 | `includes/class-amadex-analytics.php` | Analytics dashboard. |
| 9 | `includes/class-amadex-fraud-detection.php` | Fraud detection (backend). |
| 10 | `includes/class-amadex-environment-manager.php` | Environment manager. |
| 11 | `includes/admin/class-amadex-leads.php` | Leads admin: fraud UI, PDF, export, analytics, AJAX handlers. |
| 12 | `includes/frontend/class-amadex-shortcodes.php` | Enqueue fraud-detection JS; booking config (e.g. bypassPayment). |
| 13 | `assets/js/amadex-booking.js` | 7 features: request hash, Confirm & Book, CollectJS, DUPLICATE_REQUEST, error handling, **Processing booking modal**; Flight Leads: device_fingerprint. |
| 14 | `assets/css/amadex-booking.css` | **Processing booking modal styles** (overlay, modal, spinner, animations, responsive design). |
| 15 | `assets/js/amadex-fraud-detection.js` | Frontend fraud: fingerprint, behavior, getCompleteFraudData. |

---

## Part D — Summary

- **New files to add:** 7 (PDF, exporter, assignment, analytics, fraud-detection PHP, environment-manager, fraud-detection JS).  
- **Modified files to update:** 8 (amadex.php, composer.json, amadex-ajax, database, leads admin, shortcodes, amadex-booking.js, **amadex-booking.css**).  
- **Total unique files:** 15.

**Deploy steps for developer:**

1. Add the 7 new files under Part B.  
2. Update the 7 modified files (Parts A & B) with the versions that implement both the 7 features and Flight Leads Management.  
3. Run `composer install` (or `composer update`) so `dompdf` and `phpspreadsheet` are installed.  
4. Ensure DB migrations run (e.g. via `ensure_tables_ready` / plugin activation) so `wp_amadex_booking_locks` and fraud-related tables/columns exist.

---

**End of Developer File List.**
