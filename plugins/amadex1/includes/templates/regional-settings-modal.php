<?php
/**
 * Regional Settings Modal Template
 * Skyscanner-style regional settings modal
 * 
 * When Regional Settings System is disabled, this template still loads but
 * the modal will be hidden/disabled by JavaScript based on AmadexConfig settings.
 * 
 * @package Amadex
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if regional settings system is enabled (using cached helper method)
$regional_settings_enabled = true; // Default to enabled for backward compatibility
if (class_exists('Amadex_Currency')) {
    $regional_settings_enabled = Amadex_Currency::is_regional_settings_enabled();
}

// Get current settings (will return USA/USD/en-US if regional settings disabled)
$current_language = 'en-US';
$current_country = 'US';
$current_currency = 'USD';

if (class_exists('Amadex_Currency')) {
    $regional_settings = Amadex_Currency::get_user_regional_settings();
    $current_language = $regional_settings['language'];
    $current_country = $regional_settings['country'];
    $current_currency = $regional_settings['currency'];
}

$languages = class_exists('Amadex_Currency') ? Amadex_Currency::get_supported_languages() : array();
$countries = class_exists('Amadex_Currency') ? Amadex_Currency::get_supported_countries() : array();
$currencies = class_exists('Amadex_Currency') ? Amadex_Currency::get_supported_currencies() : array();
?>

<!-- Regional Settings Modal -->
<div id="amadex-regional-settings-modal" class="amadex-regional-modal" style="display: none;">
    <div class="amadex-regional-modal-overlay"></div>
    <div class="amadex-regional-modal-content">
        <div class="amadex-regional-modal-header">
            <h2 class="amadex-regional-modal-title"><?php echo esc_html__('Regional settings', 'amadex'); ?></h2>
            <button type="button" class="amadex-regional-modal-close" aria-label="<?php echo esc_attr__('Close', 'amadex'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <div class="amadex-regional-modal-body">
            <!-- Language Selection -->
            <div class="amadex-regional-field">
                <label for="amadex-regional-language" class="amadex-regional-label">
                    <svg class="amadex-regional-label-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                    </svg>
                    <?php echo esc_html__('Language', 'amadex'); ?>
                </label>
                <select id="amadex-regional-language" class="amadex-regional-select" name="language">
                    <?php foreach ($languages as $code => $info): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($current_language, $code); ?>>
                            <?php echo esc_html($info['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Country/Region Selection -->
            <div class="amadex-regional-field">
                <label for="amadex-regional-country" class="amadex-regional-label">
                    <svg class="amadex-regional-label-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
                    </svg>
                    <?php echo esc_html__('Country / Region', 'amadex'); ?>
                </label>
                <p class="amadex-regional-description">
                    <?php echo esc_html__('Selecting the country you\'re in will give you local deals and information.', 'amadex'); ?>
                </p>
                <select id="amadex-regional-country" class="amadex-regional-select" name="country">
                    <?php foreach ($countries as $code => $info): ?>
                        <option value="<?php echo esc_attr($code); ?>" 
                                data-currency="<?php echo esc_attr($info['currency']); ?>"
                                data-language="<?php echo esc_attr($info['language']); ?>"
                                <?php selected($current_country, $code); ?>>
                            <?php echo esc_html($info['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Currency Selection -->
            <div class="amadex-regional-field">
                <label for="amadex-regional-currency" class="amadex-regional-label">
                    <svg class="amadex-regional-label-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="currentColor"/>
                    </svg>
                    <?php echo esc_html__('Currency', 'amadex'); ?>
                </label>
                <select id="amadex-regional-currency" class="amadex-regional-select" name="currency">
                    <?php foreach ($currencies as $code => $info): 
                        $symbol = isset($info['symbol']) ? $info['symbol'] : $code;
                        $display_text = $code . ' - ' . $symbol;
                    ?>
                        <option value="<?php echo esc_attr($code); ?>" 
                                data-symbol="<?php echo esc_attr($symbol); ?>"
                                <?php selected($current_currency, $code); ?>>
                            <?php echo esc_html($display_text); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="amadex-regional-modal-footer">
            <button type="button" class="amadex-regional-btn amadex-regional-btn-cancel">
                <?php echo esc_html__('Cancel', 'amadex'); ?>
            </button>
            <button type="button" class="amadex-regional-btn amadex-regional-btn-save">
                <?php echo esc_html__('Save', 'amadex'); ?>
            </button>
        </div>
    </div>
</div>
