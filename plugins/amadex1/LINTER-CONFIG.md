# Linter Configuration for Amadex Plugin

This directory contains configuration files to help IDEs and static analysis tools understand WordPress functions and suppress false positive warnings.

## Files

- `.phpstan.neon` - PHPStan configuration file that ignores WordPress function warnings
- `.phpstan-baseline.neon` - Baseline configuration for PHPStan
- `.phpcs.xml` - PHP CodeSniffer configuration
- `wordpress-stubs.php` - WordPress function stubs (for IDE autocomplete only, NOT included in production)

## Setup

### For PHPStan

If you're using PHPStan, the `.phpstan.neon` file will automatically suppress WordPress function warnings.

```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse includes/amadex-ajax.php
```

### For VS Code / Cursor

1. Install the PHP Intelephense extension
2. The `.vscode/settings.json` file (if created) will configure Intelephense to recognize WordPress functions
3. Alternatively, add this to your workspace settings:

```json
{
    "intelephense.diagnostics.undefinedFunctions": false,
    "intelephense.stubs": ["wordpress", "wordpress-globals"]
}
```

### For PHPStorm / PhpStorm

1. Go to Settings → Languages & Frameworks → PHP
2. Add WordPress stubs (usually available via plugin or download)
3. Or configure inspection to ignore WordPress function warnings

## Important Notes

- The `wordpress-stubs.php` file is **NOT** included in production code
- These configurations only affect development tools, not runtime behavior
- WordPress functions are available at runtime - these are just IDE/linter warnings

## WordPress Functions Used

This plugin uses standard WordPress functions including:
- `add_action()`, `add_filter()`
- `wp_send_json_success()`, `wp_send_json_error()`
- `sanitize_text_field()`, `sanitize_email()`
- `get_option()`, `get_bloginfo()`
- `wp_mail()`, `wp_remote_get()`, `wp_remote_post()`
- And many more standard WordPress functions

All these functions are available at runtime when WordPress is loaded.

