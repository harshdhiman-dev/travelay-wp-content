# Travelay Cultural Welcome — Production Install

Version **3.9.0**

## Requirements

- WordPress 5.8+
- PHP 7.4+ (ZipArchive recommended for `.lottie` uploads)
- Optional: Google Cloud API key (Text-to-Speech for voice welcome)
- Optional: `.riv` / `.lottie` avatar files per profile slug
- Optional: Amadex flight plugin (booking-flow guard activates automatically when detected)

## 1. Deploy the plugin files

### Option A — ZIP upload (recommended)

1. Upload `travelay-cultural-welcome.zip` via **Plugins → Add New → Upload Plugin**.
2. Activate **Travelay Cultural Welcome**.

### Option B — SFTP / File Manager

Upload the folder to:

```
wp-content/plugins/travelay-cultural-welcome/
```

Then activate in **Plugins → Installed Plugins**.

## 2. Post-activation checklist

1. **Cultural Welcome → Settings**
   - Enable the plugin
   - Choose an **Experience preset**
   - Optional: set **Sync Exclude Slugs** (comma-separated slugs to skip during bulk sync)

2. **Cultural Welcome → Profiles**
   - Fresh installs start with **zero profiles**
   - Click **Sync all pages** and choose scope:
     - Pages only
     - Posts & custom post types
     - Everything
   - New profiles are **Reviewed** and **disabled** — edit each profile (or bulk-enable) and set **Status → Live** when ready
   - For Travelay country landing pages, click **Sync Country Templates** instead

3. **Google TTS (optional)**
   - Add API key in Settings or `wp-config.php`:
     ```php
     define( 'TCW_GOOGLE_API_KEY', 'your-google-cloud-api-key' );
     ```
   - Click **Refresh Voice Catalog**

4. **Rive / Lottie avatars (optional)**
   - Upload `{slug}.riv` or `{slug}.lottie` under `assets/avatars/rive/` or `assets/avatars/lottie/`
   - Edit profile → scan Rive inputs → Save

5. **Test**
   - Visit a synced page with the profile **Enabled** and **Live**
   - On Travelay + Amadex sites, confirm welcome is hidden on booking/payment pages

## 3. Generic vs travel sites

| Use case | Action |
|----------|--------|
| Any WordPress site | **Sync all pages** |
| Travelay country pages | **Sync Country Templates** (gestures + cultural copy) |
| Mixed | Run both; profiles are linked by WordPress post ID |

## 4. Database migration

- Cloning the DB brings `tcw_profile` posts and `tcw_settings`
- Files-only deploy: activate → **Sync all pages** or **Sync Country Templates** → reconfigure Settings

## 5. Caching

Purge page cache after deploy. Assets use `?ver=3.9.0` for cache busting.

## 6. Uninstall

Deleting the plugin removes settings and voice catalog cache only. Profiles are kept unless deleted manually.
