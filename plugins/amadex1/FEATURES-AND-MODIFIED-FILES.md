# Amadex Plugin – Features Updated/Improved/Added & Modified Files

## 1. Features successfully updated, improved, or added

| # | Feature | Description |
|---|--------|-------------|
| 1 | **Duplicate booking prevention** | `wp_amadex_booking_locks` table + request-hash checks so double-clicks / repeated submits create only one booking. |
| 2 | **Request hash generation** | Fixed `generateRequestHash` so it no longer throws; booking AJAX submission can complete. |
| 3 | **JS syntax fixes** | Removed duplicate `let` declarations (`errorMessage`, `submitBtn`) that caused parse errors and broke the booking script. |
| 4 | **Confirm & Book / NMI flow** | CollectJS tokenization, “Processing your payment…” UI, and button handlers so “Confirm & Book” works end-to-end. |
| 5 | **Booking locks table lifecycle** | `ensure_tables_ready` + on-demand creation of `booking_locks` before `process_booking` so duplicate checks always run. |
| 6 | **Duplicate-request API response** | Backend returns `DUPLICATE_REQUEST` when a repeat submit is detected; frontend shows a friendly message instead of retrying. |
| 7 | **AJAX error handling** | Single `errorMessage` flow for API errors (including duplicate) and clearer user-facing error messages. |

---

## 2. Modified files (path + feature hint)

| # | Path | Feature hint |
|---|------|--------------|
| 1 | `includes/amadex-ajax.php` | Process booking, duplicate check, locks, `DUPLICATE_REQUEST`, ensure tables |
| 2 | `includes/class-amadex-database.php` | `wp_amadex_booking_locks` table create/schema |
| 3 | `assets/js/amadex-booking.js` | `generateRequestHash`, `errorMessage`/`submitBtn` fixes, Confirm & Book, CollectJS, AJAX errors |

---

**Plugin root:**  
`wp-content/plugins/amadex1 (3) seat working (3)/`

All paths above are relative to that root.
