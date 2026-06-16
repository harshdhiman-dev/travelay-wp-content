#!/bin/bash
# ==============================================
# Travelay — Initial Repo Setup Script
# ==============================================
# Run this AFTER downloading wp-content from Hostinger
# and creating an empty GitHub repo.
#
# Usage:
#   chmod +x init-repo.sh
#   ./init-repo.sh /path/to/downloaded/wp-content https://github.com/YOUR_ORG/travelay-wp-content.git
# ==============================================

set -e

WP_CONTENT_PATH="${1:?Usage: ./init-repo.sh /path/to/wp-content https://github.com/your/repo.git}"
GITHUB_REPO="${2:?Please provide GitHub repo URL as second argument}"

echo "============================================"
echo "  Travelay Repo Setup"
echo "============================================"

# Check if wp-content path exists
if [ ! -d "$WP_CONTENT_PATH/themes" ] || [ ! -d "$WP_CONTENT_PATH/plugins" ]; then
    echo "ERROR: $WP_CONTENT_PATH doesn't look like a wp-content directory."
    echo "       Expected to find themes/ and plugins/ inside it."
    exit 1
fi

# Create working directory
REPO_DIR="travelay-wp-content"
echo ""
echo "→ Creating repo directory: $REPO_DIR"
mkdir -p "$REPO_DIR"
cd "$REPO_DIR"

# Initialize git
echo "→ Initializing git..."
git init
git branch -M main

# Copy repo config files (these should be in the same directory as this script)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "→ Copying config files..."
cp "$SCRIPT_DIR/.gitignore" .
cp -r "$SCRIPT_DIR/.github" .
cp "$SCRIPT_DIR/README.md" .

# Copy wp-content contents
echo "→ Copying themes..."
cp -r "$WP_CONTENT_PATH/themes" .

echo "→ Copying plugins..."
cp -r "$WP_CONTENT_PATH/plugins" .

if [ -d "$WP_CONTENT_PATH/mu-plugins" ]; then
    echo "→ Copying mu-plugins..."
    cp -r "$WP_CONTENT_PATH/mu-plugins" .
fi

# Remove uploads if accidentally included
if [ -d "uploads" ]; then
    echo "→ Removing uploads/ (not tracked in git)..."
    rm -rf uploads
fi

# Remove node_modules if present
find . -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null || true
find . -name "vendor" -type d -exec rm -rf {} + 2>/dev/null || true

# Stage and commit
echo ""
echo "→ Staging files..."
git add -A

FILE_COUNT=$(git diff --cached --numstat | wc -l)
echo "  $FILE_COUNT files staged"

echo "→ Creating initial commit..."
git commit -m "Initial commit: wp-content from travelaystagging.com

Includes:
- DigitalSilk theme (digitalsilk)
- All plugins
- GitHub Actions deploy workflow
- CSS preload fix for WP 7.0 compatibility"

# Add remote and push
echo ""
echo "→ Adding GitHub remote..."
git remote add origin "$GITHUB_REPO"

echo "→ Pushing to GitHub..."
git push -u origin main

echo ""
echo "============================================"
echo "  Done! Repo pushed to: $GITHUB_REPO"
echo "============================================"
echo ""
echo "NEXT STEPS:"
echo "  1. Go to your GitHub repo → Settings → Secrets → Actions"
echo "  2. Add these secrets: FTP_HOST, FTP_USER, FTP_PASS, FTP_PORT, FTP_REMOTE_PATH"
echo "  3. See README.md for details"
echo ""
