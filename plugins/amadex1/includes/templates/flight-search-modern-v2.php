<?php
/**
 * Modern Flight Search Form Template - V2 (Flytravelay Style)
 *
 * @package Amadex
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$form_id = isset($atts['is_results_page']) && $atts['is_results_page'] ? 'amadex-search-v2-results' : 'amadex-search-v2';
$results_page = isset($atts['results_page']) ? $atts['results_page'] : site_url('/flight-results/');
$button_text = isset($atts['button_text']) ? $atts['button_text'] : 'SEARCH';
?>

<div class="amadex-search-v2">
    <form class="amadex-search-v2__form" id="<?php echo esc_attr($form_id); ?>" data-results="<?php echo esc_url($results_page); ?>">
        
        <!-- Trip Type Selector -->
        <div class="amadex-search-v2__trip-types">
            <label class="amadex-trip-type">
                <input type="radio" name="tripType" value="oneway" checked>
                <span class="amadex-trip-type__label"><?php _e('ONE WAY', 'amadex'); ?></span>
            </label>
            <label class="amadex-trip-type">
                <input type="radio" name="tripType" value="round">
                <span class="amadex-trip-type__label"><?php _e('ROUND TRIP', 'amadex'); ?></span>
            </label>
            <label class="amadex-trip-type">
                <input type="radio" name="tripType" value="multi-city">
                <span class="amadex-trip-type__label"><?php _e('MULTI-CITY', 'amadex'); ?></span>
            </label>
        </div>

        <!-- Single Trip Fields (One Way / Round Trip) -->
        <div class="amadex-search-v2__fields amadex-single-trip-fields">
            
            <!-- Origin Field -->
            <div class="amadex-search-v2__field amadex-location-field">
                <div class="amadex-field__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
                    </svg>
                </div>
                <div class="amadex-field__input-wrapper">
                    <input 
                        type="text" 
                        id="origin-input" 
                        class="amadex-field__input amadex-autocomplete" 
                        placeholder="New York, United States"
                        autocomplete="off"
                    >
                    <input type="hidden" id="origin-code" name="origin">
                    <div class="amadex-field__code" id="origin-code-display">NYC</div>
                </div>
                <div class="amadex-autocomplete-dropdown" id="origin-dropdown"></div>
            </div>

            <!-- Swap Button -->
            <button type="button" class="amadex-search-v2__swap" id="swap-button" aria-label="Swap origin and destination">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 16V4M7 4L3 8M7 4l4 4M17 8v12M17 20l4-4M17 20l-4-4"/>
                </svg>
            </button>

            <!-- Destination Field -->
            <div class="amadex-search-v2__field amadex-location-field">
                <div class="amadex-field__icon amadex-field__icon--reverse">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>
                    </svg>
                </div>
                <div class="amadex-field__input-wrapper">
                    <input 
                        type="text" 
                        id="destination-input" 
                        class="amadex-field__input amadex-autocomplete" 
                        placeholder="Dubai, United Arab Emirates"
                        autocomplete="off"
                    >
                    <input type="hidden" id="destination-code" name="destination">
                    <div class="amadex-field__code" id="destination-code-display">DXB</div>
                </div>
                <div class="amadex-autocomplete-dropdown" id="destination-dropdown"></div>
            </div>

            <!-- Departure Date -->
            <div class="amadex-search-v2__field amadex-date-field">
                <div class="amadex-field__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="amadex-field__input-wrapper">
                    <input 
                        type="date" 
                        id="departure-date" 
                        name="departure_date" 
                        class="amadex-field__input amadex-field__input--date"
                        required
                    >
                </div>
            </div>

            <!-- Return Date -->
            <div class="amadex-search-v2__field amadex-date-field amadex-return-field">
                <div class="amadex-field__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="amadex-field__input-wrapper">
                    <input 
                        type="date" 
                        id="return-date" 
                        name="return_date" 
                        class="amadex-field__input amadex-field__input--date"
                    >
                </div>
            </div>

            <!-- Passengers -->
            <div class="amadex-search-v2__field amadex-passengers-field">
                <div class="amadex-field__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="amadex-field__input-wrapper">
                    <div class="amadex-passengers-trigger" id="passengers-trigger">
                        <span id="passengers-display">1 Passenger</span>
                    </div>
                </div>
                
                <!-- Passengers Dropdown -->
                <div class="amadex-passengers-dropdown" id="passengers-dropdown">
                    <div class="amadex-passenger-row">
                        <div class="amadex-passenger-label">
                            <strong>Adults</strong>
                            <small>12+ years</small>
                        </div>
                        <div class="amadex-passenger-controls">
                            <button type="button" class="amadex-counter-btn" data-action="decrease" data-type="adults">−</button>
                            <span class="amadex-counter-value" id="adults-count">1</span>
                            <button type="button" class="amadex-counter-btn" data-action="increase" data-type="adults">+</button>
                        </div>
                    </div>
                    <div class="amadex-passenger-row">
                        <div class="amadex-passenger-label">
                            <strong>Children</strong>
                            <small>2-11 years</small>
                        </div>
                        <div class="amadex-passenger-controls">
                            <button type="button" class="amadex-counter-btn" data-action="decrease" data-type="children">−</button>
                            <span class="amadex-counter-value" id="children-count">0</span>
                            <button type="button" class="amadex-counter-btn" data-action="increase" data-type="children">+</button>
                        </div>
                    </div>
                    <div class="amadex-passenger-row">
                        <div class="amadex-passenger-label">
                            <strong>Infants</strong>
                            <small>Under 2 years</small>
                        </div>
                        <div class="amadex-passenger-controls">
                            <button type="button" class="amadex-counter-btn" data-action="decrease" data-type="infants">−</button>
                            <span class="amadex-counter-value" id="infants-count">0</span>
                            <button type="button" class="amadex-counter-btn" data-action="increase" data-type="infants">+</button>
                        </div>
                    </div>
                    <button type="button" class="amadex-passengers-apply" id="passengers-apply">Done</button>
                </div>

                <input type="hidden" id="adults-input" name="adults" value="1">
                <input type="hidden" id="children-input" name="children" value="0">
                <input type="hidden" id="infants-input" name="infants" value="0">
            </div>

            <!-- Search Button -->
            <button type="submit" class="amadex-search-v2__submit">
                <?php echo esc_html($button_text); ?>
            </button>
        </div>

        <!-- Multi-City Fields Container (Hidden by default) -->
        <div class="amadex-search-v2__multi-city" id="multi-city-container" style="display: none;">
            <div class="amadex-multi-city-segment" data-segment="1">
                <div class="amadex-multi-city-fields">
                    <div class="amadex-search-v2__field">
                        <input type="text" class="amadex-field__input amadex-autocomplete" placeholder="From" data-type="origin" data-segment="1">
                        <input type="hidden" class="multi-origin-code" data-segment="1">
                    </div>
                    <div class="amadex-search-v2__field">
                        <input type="text" class="amadex-field__input amadex-autocomplete" placeholder="To" data-type="destination" data-segment="1">
                        <input type="hidden" class="multi-destination-code" data-segment="1">
                    </div>
                    <div class="amadex-search-v2__field">
                        <input type="date" class="amadex-field__input" data-segment="1" name="multi_date_1">
                    </div>
                </div>
            </div>

            <div class="amadex-multi-city-segment" data-segment="2">
                <div class="amadex-multi-city-fields">
                    <div class="amadex-search-v2__field">
                        <input type="text" class="amadex-field__input amadex-autocomplete" placeholder="From" data-type="origin" data-segment="2">
                        <input type="hidden" class="multi-origin-code" data-segment="2">
                    </div>
                    <div class="amadex-search-v2__field">
                        <input type="text" class="amadex-field__input amadex-autocomplete" placeholder="To" data-type="destination" data-segment="2">
                        <input type="hidden" class="multi-destination-code" data-segment="2">
                    </div>
                    <div class="amadex-search-v2__field">
                        <input type="date" class="amadex-field__input" data-segment="2" name="multi_date_2">
                    </div>
                    <button type="button" class="amadex-remove-segment" data-segment="2" aria-label="Remove flight">×</button>
                </div>
            </div>

            <button type="button" class="amadex-add-flight-btn" id="add-flight-segment">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <?php _e('Add Another Flight', 'amadex'); ?>
            </button>

            <!-- Passengers & Search for Multi-City -->
            <div class="amadex-multi-city-footer">
                <div class="amadex-search-v2__field amadex-passengers-field">
                    <div class="amadex-field__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="amadex-field__input-wrapper">
                        <div class="amadex-passengers-trigger" id="passengers-trigger-multi">
                            <span id="passengers-display-multi">1 Passenger</span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="amadex-search-v2__submit">
                    <?php echo esc_html($button_text); ?>
                </button>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="amadex-search-v2__loading" id="search-loading" style="display: none;">
            <div class="amadex-spinner"></div>
            <span><?php _e('Searching flights...', 'amadex'); ?></span>
        </div>
    </form>
</div>


