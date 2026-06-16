# Level 5 Deep Check: Fraud Detection On/Off Switch

**Date:** Report generated from codebase analysis  
**Scope:** How fraud is wired (frontend + backend), what happens when ON vs OFF, and feasibility of a switch under “Bypass Payment for Testing”  
**No code changes** — analysis only.

---

## 1. What I Understand

You want:

1. **Fraud Detection On/Off switch**  
   - **ON:** When user clicks “Confirm & Book,” the system runs a fraud check, collects user/device info, then lets the transaction complete.  
   - **OFF:** That fraud logic is **fully disabled**. The flow reverts to the **original** booking path (no fraud check, no fraud collection).  
2. **Placement:** The switch lives under **“Bypass Payment for Testing”** in General Payment Settings (same section as the bypass checkbox).  
3. **Clarification:** Fraud today **does not block** bookings. It only **collects and stores** device/behavior data, computes a score, and logs it. The transaction always proceeds. “Runs a fraud check and collect… to make transaction go through” = run check + collect data → then complete booking. That matches current behavior.

---

## 2. How Fraud Is Wired Today

### 2.1 Frontend

| Location | What Happens |
|----------|--------------|
| **Script loading** | `amadex-fraud-detection.js` is **always** enqueued on the booking page (shortcodes ~71 and ~1572). `amadex-booking.js` depends on it. |
| **Fraud init** | `AmadexFraudDetection.init()` runs on DOM ready. It starts behavior tracking (mouse, clicks, keystrokes, scroll, form interactions, timings). |
| **Submit paths** | **Two** AJAX submit paths in `amadex-booking.js`: (1) ~4968–4991 (bypass / no-token flow), (2) ~7284–7309 (tokenized payment flow). |
| **Fingerprint collection** | In **both** paths, before sending the request, the code checks `window.AmadexFraudDetection` and `getCompleteFraudData`. If present, it calls `getCompleteFraudData()`, then sends `device_fingerprint: JSON.stringify(deviceFingerprintData)` (or `null` if no data) in the POST body. |
| **Gating** | **None.** There is no “fraud enabled” flag. Fraud is always attempted when the booking script runs. If the fraud module is missing, we just don’t send `device_fingerprint` and log a warning. |

**Summary:** Fraud is “on” whenever the booking page loads: we always load the fraud script, always try to collect fingerprint, and always send it (when available) with **both** bypass and tokenized flows. The **payment page** (`amadex-payment-page.js`) does **not** use fraud at all.

### 2.2 Backend

| Location | What Happens |
|----------|--------------|
| **Entry point** | `process_booking()` in `amadex-ajax.php` handles `amadex_process_booking` (logged + nopriv). |
| **Fraud trigger** | **Only** when `$_POST['device_fingerprint']` is non-empty. Backend decodes JSON, loads `Amadex_Fraud_Detection`, gets client IP via `get_client_ip()`, then calls `process_fraud_data(device_data, ip, booking_data)` → `$fraud_data`. |
| **If no fingerprint** | `$fraud_data` stays `null`. No fraud class is loaded, no fraud processing, no fraud logging. |
| **Usage of `$fraud_data`** | Passed into `create_lead()` (as `fraud_data` in lead payload), and `create_booking()` (same key). Also `log_fraud_data(lead_id, null, $fraud_data)` when fraud ran. |
| **Blocking?** | **No.** There is no check on `fraud_score` or `fraud_risk_level` that rejects the booking. Fraud is **informational only**: collect → store → log → continue. |

**Summary:** Fraud runs **only** when the frontend sends `device_fingerprint`. No fingerprint ⇒ no fraud. No setting today to “turn fraud off” on the backend; it’s implicitly off when nothing is sent.

### 2.3 Database & Admin

| Item | Details |
|------|---------|
| **Leads** | `fraud_data`, `fraud_score`, `fraud_risk_level`, `device_fingerprint`, IP-related columns. Migration adds these if missing. |
| **Bookings** | Same fraud-related fields plus `billing_country`, `billing_city`, `billing_match_ip`. |
| **`wp_amadex_fraud_logs`** | Stores fraud runs (score, risk, fingerprint, IP, etc.). Created by `class-amadex-database`. |
| **Admin** | Leads list shows “Fraud Score”; lead modal has “Fraud Detection & Verification” (score, risk, device, geo, etc.). Export (CSV/XLSX) includes fraud columns. |

All of this **displays** stored data. When fraud is off and no fingerprint is sent, those fields stay empty; the UI just shows nothing.

### 2.4 Bypass Payment vs Fraud

| Setting | Purpose | Stored In | Passed to Frontend |
|---------|---------|-----------|--------------------|
| **Bypass Payment** | Skip real payment auth; create booking without charging. | `amadex_payment_settings['nmi_bypass_for_testing']` | `AmadexConfig.bypassPayment` |
| **Fraud** | Collect device/behavior data, score, log. | **No dedicated setting.** Implicitly “on” when fingerprint is sent. | N/A |

They are **independent**. Bypass only affects payment; fraud only affects whether we collect and store fraud data. Fraud runs regardless of bypass.

---

## 3. What Happens When Fraud Is ON vs OFF

### 3.1 Current Behavior (Equivalent to “ON”)

1. User is on booking page → fraud script loads → `AmadexFraudDetection.init()` runs → behavior tracking starts.  
2. User clicks “Confirm & Book” → booking JS calls `getCompleteFraudData()` → sends `device_fingerprint` in POST.  
3. Backend receives `device_fingerprint` → runs fraud (IP, device, behavior, etc.) → `$fraud_data` populated.  
4. Lead created with `fraud_data` → `log_fraud_data` called → booking created with `fraud_data`.  
5. Payment (or bypass) runs → booking completes.  

**Result:** Full fraud pipeline. Data in leads, bookings, and `fraud_logs`. Admin sees scores and fraud section.

### 3.2 Desired “OFF” Behavior (Original Flow, No Fraud)

1. User is on booking page → **no** fraud collection, **no** fingerprint sent.  
2. User clicks “Confirm & Book” → request sent **without** `device_fingerprint`.  
3. Backend sees no `device_fingerprint` → **no** fraud processing → `$fraud_data` remains `null`.  
4. Lead and booking created **without** fraud fields (or with nulls). No `log_fraud_data`.  
5. Payment (or bypass) runs → booking completes.  

**Result:** Same user-visible flow as today, but **no** fraud check, **no** fraud data. Matches “original” behavior before fraud existed.

### 3.3 Summary Table

| | **Fraud ON** | **Fraud OFF** |
|--|--------------|---------------|
| **Frontend** | Load fraud script, init, collect fingerprint, send in both submit paths | Don’t load fraud script **or** don’t collect/send fingerprint |
| **Backend** | Process fraud when `device_fingerprint` present; store in lead/booking; log | No `device_fingerprint` ⇒ no fraud; `$fraud_data` null; no log |
| **Transaction** | Completes (payment or bypass) | Completes (payment or bypass) |
| **Admin** | Fraud score, fraud section, export columns populated | Same UI, but empty / no fraud data |

---

## 4. How Deeply Fraud Is Rooted

### 4.1 Frontend Touchpoints

| File | Role |
|------|------|
| `includes/frontend/class-amadex-shortcodes.php` | Enqueues `amadex-fraud-detection.js` (two shortcode flows). `amadex-booking` depends on it. |
| `assets/js/amadex-fraud-detection.js` | Defines `AmadexFraudDetection`, `init()`, `getCompleteFraudData()`. |
| `assets/js/amadex-booking.js` | Calls `getCompleteFraudData()` and sends `device_fingerprint` in **both** AJAX submit paths (~4971–4990 and ~7287–7308). |

**To turn OFF:** Either (a) **don’t enqueue** fraud JS when the switch is off, or (b) **enqueue** it but **don’t call** `getCompleteFraudData` / **don’t add** `device_fingerprint` to the request. Option (a) also stops all behavior tracking when off.

### 4.2 Backend Touchpoints

| File | Role |
|------|------|
| `includes/amadex-ajax.php` | `process_booking`: reads `device_fingerprint`, runs fraud, passes `$fraud_data` to `create_lead` and `create_booking`, calls `log_fraud_data`. |
| `includes/class-amadex-fraud-detection.php` | `process_fraud_data`, `log_fraud_data`, score/risk logic. |
| `includes/class-amadex-database.php` | `create_lead` / `create_booking` accept and store fraud fields; `wp_amadex_fraud_logs` table. |

**To turn OFF:** Backend already effectively “off” when no `device_fingerprint` is sent. Optional extra: respect a “fraud enabled” setting and **skip** fraud even if fingerprint is sent (defense in depth).

### 4.3 Admin / Display Only

| File | Role |
|------|------|
| `includes/admin/class-amadex-leads.php` | Fraud column in leads list; “Fraud Detection & Verification” in lead modal. |
| `includes/class-amadex-data-exporter.php` | Fraud columns in CSV/XLSX export. |

**To turn OFF:** No code change required. When fraud is off, stored values are empty; UI and export just show blank.

---

## 5. Turning the Switch ON vs OFF — Summary

| Aspect | **ON** | **OFF** |
|--------|--------|---------|
| **Switch** | New “Enable Fraud Detection” (or similar) **checked**, under “Bypass Payment for Testing.” | **Unchecked.** |
| **Frontend** | Same as now: load fraud JS, collect fingerprint, send `device_fingerprint` for both submit paths. | Don’t load fraud JS **or** don’t collect/send; **no** `device_fingerprint` in POST. |
| **Backend** | Same as now: if fingerprint sent, run fraud, store, log. | No fingerprint ⇒ no fraud. Optionally skip even if fingerprint sent when setting is off. |
| **Flow** | Create lead → (fraud) → create booking → payment/bypass → done. | Create lead → create booking → payment/bypass → done. **No** fraud step. |
| **Data** | Leads/bookings/fraud_logs contain fraud data. | No new fraud data; existing DB columns can stay, just unused. |

The switch does **not** change payment or bypass behavior. It only controls whether the fraud **check and collection** run.

---

## 6. Level 5 Deep-Check Outcome

- **Wiring:** Fraud is clearly defined: frontend collects and sends `device_fingerprint`; backend runs fraud only when it’s present; results stored in leads, bookings, and `fraud_logs`.  
- **Blocking:** Fraud **never** blocks. It only collects and stores.  
- **OFF behavior:** Achievable by not sending `device_fingerprint` (and optionally not loading fraud JS). Backend already behaves as “off” when no fingerprint is received.  
- **Switch location:** Adding a new checkbox under “Bypass Payment for Testing” in `amadex_payment_general_section` is consistent with current settings structure.  
- **Bypass vs Fraud:** Independent. The fraud switch can be implemented without changing bypass logic.

**Conclusion:** A fraud on/off switch under “Bypass Payment for Testing” is **feasible**. When **on**, behavior stays as today (fraud check + collection, then transaction). When **off**, the flow reverts to the original, non-fraud path (no check, no collection, no fraud data), while payment and bypass continue to work as they do now.

---

**End of Level 5 Deep Check.**  
No code was modified; this is analysis only.
