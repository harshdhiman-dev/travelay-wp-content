# Debug Logs Analysis & Redis TLS Research

**Date:** 2026-02-02  
**Scope:** Amadex plugin errors from debug.log + Redis Cloud TLS connectivity

---

## 1. Debug Log Analysis

### 1.1 Translation loading too early (amadex, complianz-gdpr)
- **Error:** `_load_textdomain_just_in_time was called incorrectly. Translation loading for the amadex domain was triggered too early.`
- **Cause:** Code uses `__('amadex')` or `_e('amadex')` before `load_plugin_textdomain('amadex')` runs. WordPress 6.7+ reports this.
- **Fix applied:** Bootstrap moved to `plugins_loaded:0` with `load_plugin_textdomain` called first. If it still appears, another plugin/theme may load Amadex or use translations before plugins_loaded.
- **Complianz:** Third-party plugin; must be fixed by Complianz author.

### 1.2 Block type already registered (acf/top-deals-flights)
- **Error:** `WP_Block_Type_Registry::register was called incorrectly. Block type "acf/top-deals-flights" is already registered.`
- **Cause:** Block is registered twice—either by Top Deals on `acf/init` firing twice, or by another plugin/theme also registering the same block.
- **Fix applied:** Static `$attempted` flag + `is_registered()` check before `acf_register_block_type`. If another plugin registers first, Top Deals skips. If warning persists, another plugin/theme is registering it second.

### 1.3 Redis SSL "packet length too long"
- **Error:** `stream_socket_enable_crypto(): SSL operation failed with code 1. OpenSSL Error messages: error:0A0000C6:SSL routines::packet length too long; error:0A000139:SSL routines::record layer failure`
- **Location:** `predis/.../StreamFactory.php` line 179 (inside `stream_socket_enable_crypto`)
- **Cause:** Predis uses TCP connect first, then `stream_socket_enable_crypto` to upgrade to TLS. Redis Cloud expects **implicit TLS** (TLS from first byte). When Predis connects with plain TCP, the server sends TLS ServerHello; the upgrade step can misinterpret data and trigger "packet length too long." Alternatively, a protocol/TLS version mismatch causes the error.

### 1.4 json_decode(null) deprecated
- **Error:** `json_decode(): Passing null to parameter #1 ($json) of type string is deprecated`
- **Location:** `class-amadex-redis-cache.php` line 243
- **Cause:** Predis `get()` can return `null` for missing keys; `json_decode(null, true)` is deprecated in PHP 8.1+.
- **Fix applied:** Guard: `if ($value !== false && $value !== null && is_string($value))` before `json_decode`.

### 1.5 Amadeus API "Access token expired" (401)
- **Error:** `Amadex API: Full Error Response: Access token expired`
- **Cause:** OAuth token for Amadeus API expired.
- **Fix:** Plugin should clear token cache and retry. Handled in `class-amadex-api.php`; no change needed unless it recurs often.

---

## 2. Redis TLS Research Summary

### 2.1 "Packet length too long" meaning
- Often indicates **protocol mismatch**: TLS client talking to non-TLS port, or TCP client talking to TLS port.
- For Redis Cloud port 10000 (TLS): Predis connects with `tcp://`, then upgrades. Server expects TLS from the start. The upgrade sequence can fail with this error.

### 2.2 Predis TLS behavior
- Predis `tls`/`rediss` scheme: `tcpStreamInitializer` (connect with `tcp://host:port`) → `stream_socket_enable_crypto` to upgrade.
- This is STARTTLS-style. Redis uses **implicit TLS**, not STARTTLS, so this approach is inherently fragile for Redis Cloud.

### 2.3 What works better
1. **PHP Redis extension** with `tls://host` — establishes TLS from the first byte (implicit TLS).
2. **Force TLS 1.2** — some setups need `STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT` instead of `STREAM_CRYPTO_METHOD_TLS_CLIENT`.
3. **Redis Labs TLS troubleshooting** — certificate validity, cipher/protocol compatibility, key strength ≥2048-bit for RHEL9/OpenSSL 3.0+.

### 2.4 Fixes applied
1. **json_decode:** Guard against `null` and non-string values.
2. **Predis SSL:** Added `crypto_type => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT` to force TLS 1.2.
3. **Connection order:** Predis tried first for Redis Cloud (as per revert); PHP Redis kept as fallback.

### 2.5 If SSL errors continue
- Confirm Redis Cloud endpoint and port (typically TLS on 10000).
- Test with `redis-cli -h HOST -p PORT --tls --insecure ping`.
- Try PHP Redis with `tls://host` if Predis keeps failing.
- Check host TLS/OpenSSL and PHP version compatibility.

---

## 3. Files Modified
- `includes/class-amadex-redis-cache.php`: json_decode guard + ssl `crypto_type` for TLS 1.2
