# Travelay — WordPress wp-content

This repository contains the full `wp-content` directory for the Travelay website. Pushing to `main` auto-deploys to the staging server via GitHub Actions.

## Repository Structure

```
├── themes/
│   └── digitalsilk/          # Main theme (DigitalSilk DST)
├── plugins/                   # All plugins
├── mu-plugins/                # Must-use plugins
├── .github/workflows/
│   └── deploy.yml             # Auto-deploy on push to main
├── .gitignore
└── README.md
```

## What's NOT in the repo

- `uploads/` — Media files. Too large for Git. Back up separately.
- `node_modules/` / `vendor/` — Install via `npm install` / `composer install`.
- `.env` — Environment secrets. Copy `.env.default` and fill in values.
- `object-cache.php` / `advanced-cache.php` — Server-generated drop-ins.

## Local Development Setup

```bash
# 1. Clone the repo
git clone git@github.com:YOUR_ORG/travelay-wp-content.git wp-content

# 2. Set up a local WordPress installation (use Local, MAMP, Docker, etc.)
#    and point wp-content to this cloned directory.

# 3. Theme assets (from inside themes/digitalsilk/)
cd themes/digitalsilk
cp .env.default .env          # Edit with your local settings
npm install
npm run dev                   # Starts Vite dev server with HMR

# 4. Build for production
npm run build
```

## Deployment

Deployment is automatic via GitHub Actions. Push to `main` → deploys to Hostinger.

### First-time setup — GitHub Secrets

Go to your repo → Settings → Secrets and variables → Actions, and add:

| Secret            | Value                                                                                           |
|-------------------|-------------------------------------------------------------------------------------------------|
| `FTP_HOST`        | Your Hostinger FTP hostname (e.g. `ftp.hostinger.com` or the IP from hPanel → FTP Accounts)    |
| `FTP_USER`        | FTP username from hPanel                                                                        |
| `FTP_PASS`        | FTP password                                                                                    |
| `FTP_PORT`        | `21` (or `22` for SFTP if supported on your plan)                                               |
| `FTP_REMOTE_PATH` | `/home/u589068407/domains/mediumseagreen-owl-620611.hostingersite.com/public_html/wp-content/`  |

### Finding FTP credentials in Hostinger

1. Log in to hPanel
2. Go to **Websites** → select the test domain → **Dashboard**
3. Search for **FTP Accounts** in the sidebar
4. Your FTP hostname and username are listed there
5. Reset the password if needed

### Manual deploy

If you prefer manual deployment, use any FTP client (FileZilla, Cyberduck):

```
Host: (from FTP Accounts in hPanel)
Username: (from FTP Accounts)
Password: (your FTP password)
Port: 21
Remote path: /public_html/wp-content/
```

## Important Notes

- **Private repo recommended** — This repo contains paid/licensed plugins (ACF Pro, Gravity Forms). Keep the repo private.
- **Database changes** (new pages, menu edits, ACF field groups, widget settings) happen in wp-admin and are NOT tracked in Git. Use WP Migrate DB Pro or export/import for DB syncing between environments.
- **Theme CSS fix** — The `load_css_parser` method in `themes/digitalsilk/core/class-assets.php` has been patched to disable the broken preload optimization (incompatible with WordPress 7.0+). Don't revert this.

## Branch Strategy (suggested)

- `main` — Auto-deploys to staging (mediumseagreen-owl-620611.hostingersite.com)
- `production` — When ready, set up a second workflow to deploy to travelaystagging.com
- `feature/*` — Developer branches, merge via PR to main
