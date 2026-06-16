# Advanced Settings — Backend & Frontend Verification Report

**Date:** Current  
**Scope:** All Advanced Settings + Performance Optimization Settings  
**Method:** Full codebase trace (backend PHP + frontend JS)

---

## Summary: What’s Working vs Not Yet Wired

| Setting | Saved? | Backend Used? | Frontend Used? | Status |
|---------|--------|---------------|----------------|--------|
| **API Timeout** | ✅ Yes | ❌ No (hardcoded 30s) | N/A | ⚠️ Not wired |
| **Error Logging** | ✅ Yes | ✅ Yes (amadex_advanced_settings) | N/A | ✅ Working |
| **Debug Mode** | ✅ Yes | ✅ Yes (amadex_advanced_settings) | N/A | ✅ Working |
| **Enable Debug Logging** | ✅ Yes | ✅ Yes (amadex_log in helpers) | N/A | ✅ Working |
| **Initial Results Count** | ✅ Yes | ✅ Yes (amadex-helpers, API) | N/A | ✅ Working |
| **Enable Performance Logging** | ✅ Yes | ✅ Yes (class-amadex-admin) | N/A | ✅ Working |
| **Enable Redis Cache** | ✅ Yes | ✅ Yes (class-amadex-redis-cache) | N/A | ✅ Working |
| **Redis Host/Port/Password/DB** | ✅ Yes | ✅ Yes (class-amadex-redis-cache) | N/A | ⚠️ See Redis notes |
| **Progressive Loading** | ✅ Yes | ✅ Yes (amadex-ajax, API) | ✅ Yes (amadex.js) | ✅ Working |
| **Progressive Load Count** | ✅ Yes | ✅ Yes (shortcodes → AmadexConfig) | ✅ Yes (amadex.js) | ✅ Working |
| **Streaming Response** | ✅ Yes | ✅ Yes (class-amadex-streaming) | N/A (server-side) | ✅ Working |
| **Streaming Initial Count** | ✅ Yes | ✅ Yes (streaming class, shortcodes) | ✅ Yes (streaming loader) | ✅ Working |
| **Virtual Scrolling** | ✅ Yes | N/A | ⚠️ Config passed but loader never instantiated | ⚠️ Not wired |
| **Skeleton UI** | ✅ Yes | N/A | ⚠️ Config passed but loader never instantiated | ⚠️ Not wired |
| **Loading Animation** | ✅ Yes | N/A | ⚠️ Config passed but loader never instantiated | ⚠️ Not wired |

---

## Backend Verification (PHP)

### ✅ Working

1. **Error Logging / Debug Mode**  
   - Stored in `amadex_advanced_settings`.  
   - Used where those options are read.

2. **Enable Debug Logging**  
   - `amadex-helpers.php`: `amadex_log()` reads `enable_debug_logging` from `amadex_performance_settings` and skips non-error logs when disabled.

3. **Initial Results Count**  
   - `amadex-helpers.php`: `amadex_get_initial_results_count()` reads `initial_results_count`.  
   - `class-amadex-api.php`: Uses this for flight search.

4. **Enable Performance Logging**  
   - `class-amadex-admin.php`: Uses `enable_performance_logging` for the Performance Metrics page.

5. **Redis (Settings Flow)**  
   - `class-amadex-redis-cache.php` reads `enable_redis_cache`, `redis_host`, `redis_port`, `redis_password`, `redis_database` from `amadex_performance_settings`.  
   - Connection attempt and fallback to transients are implemented.

6. **Progressive Loading**  
   - `amadex-ajax.php`: Reads `enable_progressive_loading`, passes `progressive_loading` to API.  
   - `class-amadex-api.php`: Uses it for progressive search mode.

7. **Streaming Response**  
   - `class-amadex-streaming.php`: `is_enabled()` reads `enable_streaming_response`.  
   - `amadex-ajax.php`: Uses streaming when enabled.

8. **Streaming Initial Count / Progressive Load Count**  
   - Both read from settings and used in API and streaming logic.

### ⚠️ Not Yet Wired

1. **API Timeout**  
   - Stored in `amadex_advanced_settings['timeout']`.  
   - `class-amadex-api.php` uses hardcoded `'timeout' => 30` (and `15` in one place) instead of this setting.  
   - **Fix:** Read timeout from `get_option('amadex_advanced_settings')` and use it in all `wp_remote_post()` / `wp_remote_get()` calls.

---

## Frontend Verification (JavaScript)

### ✅ Working

1. **Progressive Loading**  
   - `class-amadex-shortcodes.php` passes `progressiveLoading` to `AmadexConfig`.  
   - `amadex.js` (around lines 1231, 1240) uses `AmadexConfig.progressiveLoading` for the `progressive_load` AJAX param and response handling.

2. **Progressive Load Count / Streaming Initial Count**  
   - Both passed via `AmadexConfig` and used by backend and streaming logic.

### ⚠️ Partially Wired (Config Passed, Loader Not Used)

1. **Skeleton UI, Loading Animation, Virtual Scrolling**  
   - All three are passed to `AmadexConfig` in `class-amadex-shortcodes.php`.  
   - `amadex-streaming-loader.js` reads `AmadexConfig.enableSkeletonUi`, `enableLoadingAnimation`, `enableVirtualScrolling` in its constructor.  
   - `AmadexStreamingLoader` is never instantiated anywhere in the codebase (`new AmadexStreamingLoader(...)` not found).  
   - Result: Config is correct, but skeleton UI, loading animation, and virtual scrolling do not run because nothing creates the loader.  
   - **Fix:** Instantiate `AmadexStreamingLoader` in `amadex.js` (or equivalent) when starting a flight search, and wire it to show/hide skeleton/animation and handle progressive/streaming results.

---

## Redis: “Redis is not available”

Your config:

- Host: `redis-10000.c285.us-west-2-2.ec2.cloud.redislabs.com:10000`  
- Port: `10000`  
- Password: set  
- Database: `0`

Possible causes:

### 1. Host Format

The plugin expects:

- **Host:** `redis-10000.c285.us-west-2-2.ec2.cloud.redislabs.com`  
- **Port:** `10000`

The Redis PHP extension’s `connect($host, $port)` expects host and port separately. If the host field contains `:10000`, it can cause connection problems.

### 2. TLS Required

Redis Cloud public endpoints usually require TLS. The current Redis class uses plain TCP without TLS, which will fail on Redis Cloud.

### 3. Other Checks

- PHP Redis or Predis extension installed and enabled.  
- Your server IP allowed in Redis Cloud IP access list.

---

## Recommendations

1. **API Timeout:** Wire `amadex_advanced_settings['timeout']` into `class-amadex-api.php` for all HTTP calls.  
2. **Streaming Loader:** Add `new AmadexStreamingLoader(...)` in `amadex.js` when a search starts and wire it to the search flow.  
3. **Redis Host:** Use host without port (e.g. `redis-10000.c285.us-west-2-2.ec2.cloud.redislabs.com`) in the Host field, and keep Port `10000`.  
4. **Redis TLS:** Add TLS support in `class-amadex-redis-cache.php` for Redis Cloud compatibility.

---

## Conclusion

- All Advanced Settings and Performance Optimization settings are saved correctly.  
- Most are used on the backend; progressive loading and streaming are also used on the frontend.  
- Gaps: API Timeout not used, streaming loader (skeleton/animation/virtual scroll) never instantiated, Redis likely needs host format and TLS adjustments for Redis Cloud.
