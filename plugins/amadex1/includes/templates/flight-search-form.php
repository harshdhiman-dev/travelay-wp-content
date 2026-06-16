<?php
/**
 * Flight search form template
 *
 * @package Amadex
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get theme class
$theme_class = 'amadex-theme-' . esc_attr($atts['theme']);
?>

<div class="amadex-flight-search-container <?php echo $theme_class; ?>">
    <div class="amadex-flight-search-header">
        <h3 class="amadex-flight-search-title"><?php echo esc_html($atts['title']); ?></h3>
    </div>
    
    <div class="amadex-flight-search-form-container">
    <form id="amadex-flight-search-form" class="amadex-flight-search-form" action="<?php echo esc_url(site_url('/flight-results/')); ?>" method="get">
    <?php wp_nonce_field('amadex_frontend_nonce', 'amadex_nonce'); ?>
            
            <div class="amadex-form-row">
                <div class="amadex-form-group">
                    <label for="amadex-origin-search"><?php _e('Origin', 'amadex'); ?></label>
                    <div class="amadex-autocomplete-wrapper">
                        <input type="text" id="amadex-origin-search" class="amadex-airport-search" placeholder="<?php _e('Search city or airport', 'amadex'); ?>">
                        <input type="hidden" id="amadex-origin" name="origin" required>
                        <div class="amadex-autocomplete-results"></div>
                    </div>
                    <small class="amadex-form-help"><?php _e('Search by city, airport name, or code', 'amadex'); ?></small>
                </div>
                
                <div class="amadex-form-group">
                    <label for="amadex-destination-search"><?php _e('Destination', 'amadex'); ?></label>
                    <div class="amadex-autocomplete-wrapper">
                        <input type="text" id="amadex-destination-search" class="amadex-airport-search" placeholder="<?php _e('Search city or airport', 'amadex'); ?>">
                        <input type="hidden" id="amadex-destination" name="destination" required>
                        <div class="amadex-autocomplete-results"></div>
                    </div>
                    <small class="amadex-form-help"><?php _e('Search by city, airport name, or code', 'amadex'); ?></small>
                </div>
            </div>
            
            <div class="amadex-form-row">
                <div class="amadex-form-group">
                    <label for="amadex-departure-date"><?php _e('Departure Date', 'amadex'); ?></label>
                    <input type="text" id="amadex-departure-date" name="departure_date" class="amadex-datepicker" placeholder="<?php _e('YYYY-MM-DD', 'amadex'); ?>" required>
                </div>
                
                <div class="amadex-form-group">
                    <label for="amadex-return-date"><?php _e('Return Date', 'amadex'); ?></label>
                    <input type="text" id="amadex-return-date" name="return_date" class="amadex-datepicker" placeholder="<?php _e('YYYY-MM-DD (Optional)', 'amadex'); ?>">
                    <small class="amadex-form-help"><?php _e('Leave empty for one-way flights', 'amadex'); ?></small>
                </div>
            </div>
            
            <div class="amadex-form-row">
                <div class="amadex-form-group">
                    <label for="amadex-adults"><?php _e('Passengers', 'amadex'); ?></label>
                    <input type="number" id="amadex-adults" name="adults" min="1" max="9" value="1">
                </div>
                
                <div class="amadex-form-group amadex-form-submit">
                    <button type="submit" class="amadex-search-button"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <div id="amadex-loading" class="amadex-loading" style="display: none;">
        <div class="amadex-spinner"></div>
        <p><?php _e('Searching for flights...', 'amadex'); ?></p>
    </div>
    
    <div id="amadex-flight-results" class="amadex-flight-results"></div>
</div>

