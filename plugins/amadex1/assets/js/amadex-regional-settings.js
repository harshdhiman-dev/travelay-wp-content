/**
 * Regional Settings Modal JavaScript
 * Handles modal display, auto-detection, and settings persistence
 */

(function($) {
    'use strict';
    
    let currentSettings = {
        language: 'en-US',
        country: 'US',
        currency: 'USD'
    };

    function readCookie(name) {
        const nameEQ = name + '=';
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let c = cookies[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
        }
        return '';
    }

    function writeCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 86400000).toUTCString();
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
    }

    function hasRegionalCookies() {
        return !!(readCookie('amadex_region_language') || readCookie('amadex_region_country') || readCookie('amadex_region_currency'));
    }
    
    /**
     * Initialize Regional Settings
     * Enhanced with immediate auto-detection and smooth currency application
     */
    function initRegionalSettings() {
        // Check if regional settings system is enabled
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
            const regionalSettingsEnabled = AmadexConfig.currency.regionalSettingsEnabled !== false; // Default to true if not set
            
            if (!regionalSettingsEnabled) {
                // Regional settings disabled - force USA/USD/en-US
                                currentSettings = {
                    language: 'en-US',
                    country: 'US',
                    currency: 'USD'
                };
                saveSettings();
                updateButtonDisplay();
                return; // Don't proceed with initialization
            }
        }
        
        // Load saved settings from localStorage
        loadSavedSettings();
        
        // Check if user has saved preferences
        const hasSavedSettings = localStorage.getItem('amadex_regional_settings_saved') || hasRegionalCookies();
        
        if (!hasSavedSettings) {
            // First visit or no saved settings - IMMEDIATE auto-detection and application
                        
            // IMMEDIATELY trigger AJAX detection for accurate geolocation
            // This runs in parallel with server-detected values for best user experience
            autoDetectLocation(true); // Pass true to auto-apply
            
            // Also apply server-detected currency immediately as fallback
            // This provides instant currency application while AJAX detection runs
            if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
                const serverCurrency = AmadexConfig.currency.detected || AmadexConfig.currency.default || 'USD';
                const serverCountry = AmadexConfig.currency.detectedCountry || 'US';
                const serverLanguage = AmadexConfig.currency.detectedLanguage || 'en-US';
                
                // Update current settings with server-detected values
                currentSettings = {
                    language: serverLanguage,
                    country: serverCountry,
                    currency: serverCurrency
                };
                
                // Update modal fields immediately
                updateModalFields();
                
                // Auto-save server-detected settings
                saveSettings();
                
                // Apply currency immediately if different from USD
                if (serverCurrency && serverCurrency !== 'USD') {
                    // Apply currency immediately (no delay for better UX)
                    setTimeout(function() {
                        triggerCurrencyChange();
                        if (typeof window.amadexConvertAllPrices === 'function') {
                                                        window.amadexConvertAllPrices(serverCurrency);
                        }
                    }, 100); // Reduced delay for faster application
                }
                
                // Save to sessionStorage for this session
                sessionStorage.setItem('amadex_selected_currency', serverCurrency);
                sessionStorage.setItem('amadex_selected_language', serverLanguage);
                sessionStorage.setItem('amadex_selected_country', serverCountry);
            }
        } else {
            // User has saved settings - use them

            
            // Apply saved currency immediately on page load
            if (currentSettings.currency && currentSettings.currency !== 'USD') {

                // Reduced delay for faster application
                setTimeout(function() {
                    triggerCurrencyChange();
                    // Also directly trigger conversion if function exists
                    if (typeof window.amadexConvertAllPrices === 'function') {

                        window.amadexConvertAllPrices(currentSettings.currency);
                    }
                }, 300); // Reduced from 800ms to 300ms for faster response
            }
        }
        
        // Setup modal triggers
        setupModalTriggers();
        
        // Setup country change handler (auto-update currency)
        setupCountryChangeHandler();
        
        // Update button display
        updateButtonDisplay();
    }
    
    /**
     * Update modal fields with current settings
     * This can be called anytime to refresh the modal fields
     */
    function updateModalFields() {
        const $languageSelect = $('#amadex-regional-language');
        const $countrySelect = $('#amadex-regional-country');
        const $currencySelect = $('#amadex-regional-currency');
        
        if ($languageSelect.length && currentSettings.language) {
            $languageSelect.val(currentSettings.language);
            // Trigger change event to ensure any dependent logic runs
            $languageSelect.trigger('change');
        }
        
        if ($countrySelect.length && currentSettings.country) {
            $countrySelect.val(currentSettings.country);
            // Trigger change event to auto-update currency/language suggestions
            $countrySelect.trigger('change');
        }
        
        if ($currencySelect.length && currentSettings.currency) {
            $currencySelect.val(currentSettings.currency);
            // Trigger change event
            $currencySelect.trigger('change');
        }
        

    }
    
    /**
     * Load saved settings from localStorage
     */
    function loadSavedSettings() {
        const saved = localStorage.getItem('amadex_regional_settings');
        if (saved) {
            try {
                currentSettings = JSON.parse(saved);
                // Update modal fields
                updateModalFields();
            } catch (e) {
                console.error('Error loading saved regional settings:', e);
            }
        } else if (hasRegionalCookies()) {
            const cookieLanguage = readCookie('amadex_region_language');
            const cookieCountry = readCookie('amadex_region_country');
            const cookieCurrency = readCookie('amadex_region_currency');

            if (cookieLanguage) currentSettings.language = cookieLanguage;
            if (cookieCountry) currentSettings.country = cookieCountry;
            if (cookieCurrency) currentSettings.currency = cookieCurrency;

            updateModalFields();
        } else {
            // Use defaults from server
            if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
                currentSettings = {
                    language: AmadexConfig.currency.detectedLanguage || 'en-GB',
                    country: AmadexConfig.currency.detectedCountry || 'US',
                    currency: AmadexConfig.currency.detected || AmadexConfig.currency.default || 'USD'
                };
                // Update modal fields with server-detected values
                updateModalFields();
            }
        }
    }
    
    /**
     * Auto-detect user location via AJAX
     * Enhanced with faster timeout and smoother application
     * @param {boolean} autoApply - If true, automatically apply detected currency without saving to localStorage
     */
    function autoDetectLocation(autoApply) {
        if (typeof AmadexConfig === 'undefined' || !AmadexConfig.ajaxUrl) {
                        // Try to use server-detected values if available
            if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
                applyServerDetectedSettings(autoApply);
            }
            return;
        }
        
                
        $.ajax({
            url: AmadexConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'amadex_get_user_location',
                nonce: AmadexConfig.currency?.nonce || ''
            },
            timeout: 3000, // Reduced timeout to 3 seconds for faster response
            success: function(response) {
                if (response.success && response.data) {
                    const detectedSettings = {
                        language: response.data.language || currentSettings.language,
                        country: response.data.country_code || currentSettings.country,
                        currency: response.data.currency || currentSettings.currency
                    };
                    
                                        
                    // Update current settings
                    currentSettings = detectedSettings;
                    
                    // Always update modal selects (even if modal is closed, they'll be ready when opened)
                    updateModalFields();
                    
                    // CRITICAL: Check if user has saved settings before auto-applying
                    // Don't override user's explicit selection with auto-detection
                    const hasSavedSettings = localStorage.getItem('amadex_regional_settings_saved') || hasRegionalCookies();
                    
                    if (autoApply && !hasSavedSettings) {
                        // Only auto-apply if NO saved settings exist (first-time visitor)
                        // Auto-apply detected currency immediately and auto-save to localStorage
                        // This ensures the detected settings persist and are shown in the modal
                                                
                        // Auto-save detected settings to localStorage so they persist
                        saveSettings();
                        
                        // Apply currency immediately with smooth transition
                        setTimeout(function() {
                            triggerCurrencyChange();
                            // Also directly trigger conversion if function exists
                            if (typeof window.amadexConvertAllPrices === 'function') {
                                                                window.amadexConvertAllPrices(currentSettings.currency);
                            }
                        }, 200); // Reduced delay for faster, smoother application
                        
                        // Save to sessionStorage for this session
                        sessionStorage.setItem('amadex_selected_currency', currentSettings.currency);
                        sessionStorage.setItem('amadex_selected_language', currentSettings.language);
                        sessionStorage.setItem('amadex_selected_country', currentSettings.country);
                        
                        // Update button display
                        updateButtonDisplay();
                    } else if (hasSavedSettings) {
                        // User has saved settings - respect them, don't override with auto-detection
                                                // Reload saved settings to ensure we're using user's preference
                        loadSavedSettings();
                        updateButtonDisplay();
                    } else {
                        // Save to localStorage (user has explicitly saved settings)
                        saveSettings();
                        updateButtonDisplay();
                        
                        // Trigger currency change event
                        triggerCurrencyChange();
                    }
                } else {
                                        applyServerDetectedSettings(autoApply);
                }
            },
            error: function(xhr, status, error) {
                                // Fallback to server-detected values
                applyServerDetectedSettings(autoApply);
            }
        });
    }
    
    /**
     * Apply server-detected settings (from PHP) as fallback
     */
    function applyServerDetectedSettings(autoApply) {
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
            const serverSettings = {
                language: AmadexConfig.currency.detectedLanguage || 'en-US',
                country: AmadexConfig.currency.detectedCountry || 'US',
                currency: AmadexConfig.currency.detected || AmadexConfig.currency.default || 'USD'
            };
            
                        
            currentSettings = serverSettings;
            
            // Always update modal selects
            updateModalFields();
            
                    // CRITICAL: Only auto-apply if user has NO saved settings
                    // Respect user's explicit selection over auto-detection
                    const hasSavedSettings = localStorage.getItem('amadex_regional_settings_saved') || hasRegionalCookies();
                    
                    if (autoApply && !hasSavedSettings) {
                        // Only save and apply if no saved settings exist
                        saveSettings();
                        
                        if (currentSettings.currency && currentSettings.currency !== 'USD') {
                        // Check if currency is different from what's already applied
                        const currentAppliedCurrency = sessionStorage.getItem('amadex_selected_currency');
                        if (currentAppliedCurrency !== currentSettings.currency) {
                                                            
                            // Apply currency immediately
                            setTimeout(function() {
                                triggerCurrencyChange();
                                if (typeof window.amadexConvertAllPrices === 'function') {
                                    window.amadexConvertAllPrices(currentSettings.currency);
                                }
                            }, 500);
                            
                            // Save to sessionStorage for this session
                            sessionStorage.setItem('amadex_selected_currency', currentSettings.currency);
                            sessionStorage.setItem('amadex_selected_language', currentSettings.language);
                            sessionStorage.setItem('amadex_selected_country', currentSettings.country);
                        } else {
                                                    }
                        }
                    } else if (hasSavedSettings) {
                        // User has saved settings - don't override with auto-detection
                                            }
        }
    }
    
    /**
     * Setup modal open/close handlers
     */
    function setupModalTriggers() {
        // Open modal
        $(document).on('click', '#amadex-regional-settings-trigger, .amadex-regional-settings-trigger', function(e) {
            e.preventDefault();
            openModal();
        });
        
        // Close modal - close button
        $(document).on('click', '.amadex-regional-modal-close', function(e) {
            e.preventDefault();
            closeModal();
        });
        
        // Close modal - cancel button
        $(document).on('click', '.amadex-regional-btn-cancel', function(e) {
            e.preventDefault();
            closeModal();
        });
        
        // Close modal - overlay click
        $(document).on('click', '.amadex-regional-modal-overlay', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal - ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#amadex-regional-settings-modal').is(':visible')) {
                closeModal();
            }
        });
        
        // Save settings - use multiple selectors to ensure it works
        $(document).on('click', '.amadex-regional-btn-save', function(e) {
            e.preventDefault();
            e.stopPropagation();
                                    saveRegionalSettings();
            return false;
        });
        
        // Also bind directly when modal opens (backup)
        $(document).on('click', '#amadex-regional-settings-modal .amadex-regional-btn-save', function(e) {
            e.preventDefault();
            e.stopPropagation();
                        saveRegionalSettings();
            return false;
        });
        
        // Additional handler for any save button in modal (triple backup)
        $('#amadex-regional-settings-modal').on('click', '.amadex-regional-btn-save', function(e) {
            e.preventDefault();
            e.stopPropagation();
                        saveRegionalSettings();
            return false;
        });
    }
    
    /**
     * Open modal
     */
    function openModal() {
        let $modal = $('#amadex-regional-settings-modal');
        
        // If modal not found, try multiple times with increasing delays
        if (!$modal.length) {
                        
            // Try multiple times with increasing delays
            let attempts = 0;
            const maxAttempts = 5;
            const checkModal = function() {
                attempts++;
                $modal = $('#amadex-regional-settings-modal');
                
                if ($modal.length) {
                                        proceedWithOpenModal($modal);
                    return;
                }
                
                if (attempts < maxAttempts) {
                                        setTimeout(checkModal, 200 * attempts); // Increasing delay
                } else {
                    console.error('✗ Modal not found after ' + maxAttempts + ' attempts');
                    console.error('Modal HTML should be included with the button shortcode.');
                    showNotification('Modal not found. Please refresh the page or contact support.', 'error');
                }
            };
            
            setTimeout(checkModal, 100);
            return;
        }
        
        proceedWithOpenModal($modal);
    }
    
    /**
     * Proceed with opening modal (helper function)
     */
    function proceedWithOpenModal($modal) {
        
                
        // Refresh current settings from localStorage or use latest detected values
        // This ensures modal shows the most up-to-date values
        const saved = localStorage.getItem('amadex_regional_settings');
        if (saved) {
            try {
                const savedSettings = JSON.parse(saved);
                // Only use saved if they exist, otherwise keep currentSettings (which may have auto-detected values)
                if (savedSettings.language && savedSettings.country && savedSettings.currency) {
                    currentSettings = savedSettings;
                }
            } catch (e) {
                console.error('Error loading saved settings when opening modal:', e);
            }
        }
        
        // Update modal fields with current settings
        updateModalFields();
        
        // Show modal
        $modal.css('display', 'flex').fadeIn(200);
        $('body').css('overflow', 'hidden');
        
        // Verify save button exists
        const $saveButton = $modal.find('.amadex-regional-btn-save');
        if ($saveButton.length) {
                    } else {
            console.error('Save button not found in modal!');
        }
        
            }
    
    // Expose openModal for external calls
    window.AmadexRegionalSettings = window.AmadexRegionalSettings || {};
    window.AmadexRegionalSettings.openModal = openModal;
    
    /**
     * Close modal
     */
    function closeModal() {
        $('#amadex-regional-settings-modal').fadeOut(200);
        $('body').css('overflow', '');
        
        // Reset to saved values if cancelled
        loadSavedSettings();
    }
    
    /**
     * Save regional settings
     */
    function saveRegionalSettings() {
                
        // Get values from selects
        const language = $('#amadex-regional-language').val();
        const country = $('#amadex-regional-country').val();
        const currency = $('#amadex-regional-currency').val();
        
                
        if (!language || !country || !currency) {
            console.error('Missing values:', { language, country, currency });
            showNotification('Please select all options before saving.', 'error');
            return;
        }
        
        const newSettings = {
            language: language,
            country: country,
            currency: currency
        };
        
                        
        // Check if settings changed
        const currencyChanged = newSettings.currency !== currentSettings.currency;
        const languageChanged = newSettings.language !== currentSettings.language;
        const countryChanged = newSettings.country !== currentSettings.country;
        const settingsChanged = currencyChanged || languageChanged || countryChanged;
        
                
        // Always save, even if values are the same (to ensure persistence)
        currentSettings = newSettings;
        saveSettings();
                
        updateButtonDisplay();
        
        // Always trigger currency change (even if only language/country changed)
        triggerCurrencyChange();
        
        // If only currency changed (no language/country change), convert prices smoothly without reload
        if (currencyChanged && !languageChanged && !countryChanged) {
            const currencySymbol = $('#amadex-regional-currency option:selected').data('symbol') || newSettings.currency;
            showNotification('Currency updated to ' + newSettings.currency + ' (' + currencySymbol + ')!', 'success');
            closeModal();
            
            // Smoothly convert prices without page reload
            setTimeout(function() {
                triggerCurrencyChange();
                if (typeof window.amadexConvertAllPrices === 'function') {
                    window.amadexConvertAllPrices(newSettings.currency);
                }
            }, 100);
        } else if (settingsChanged) {
            // Language or country changed, reload page
            showNotification('Regional settings saved successfully!', 'success');
            closeModal();
            
            // Reload page to apply language/currency changes
            setTimeout(function() {
                window.location.reload();
            }, 500);
        } else {
            // No changes but still show success
            showNotification('Settings saved!', 'success');
            closeModal();
        }
    }
    
    /**
     * Save settings to localStorage
     */
    function saveSettings() {
        try {
            const settingsJson = JSON.stringify(currentSettings);
            localStorage.setItem('amadex_regional_settings', settingsJson);
            localStorage.setItem('amadex_regional_settings_saved', 'true');
            
                        
            // Also save to sessionStorage for immediate use
            sessionStorage.setItem('amadex_selected_currency', currentSettings.currency);
            sessionStorage.setItem('amadex_selected_language', currentSettings.language);
            sessionStorage.setItem('amadex_selected_country', currentSettings.country);
            
            
            writeCookie('amadex_region_language', currentSettings.language, 365);
            writeCookie('amadex_region_country', currentSettings.country, 365);
            writeCookie('amadex_region_currency', currentSettings.currency, 365);
        } catch (e) {
            console.error('Error saving settings:', e);
            showNotification('Error saving settings. Please try again.', 'error');
        }
    }
    
    /**
     * Build a map from currency code to (first) country code that uses it.
     * Used to auto-select matching country when user selects a currency.
     */
    function buildCurrencyToCountryMap() {
        const map = {};
        $('#amadex-regional-country option').each(function() {
            const countryCode = $(this).val();
            const currency = $(this).data('currency');
            if (currency && countryCode && !map[currency]) {
                map[currency] = countryCode;
            }
        });
        return map;
    }
    
    /**
     * Setup country change handler (auto-update currency) and currency change (auto-update country)
     * - Select country → auto-update currency and language to match
     * - Select currency → auto-update country to first matching country (no trigger to avoid loop)
     */
    function setupCountryChangeHandler() {
        // Build map fresh so it works for PHP-rendered modal or fallback modal
        const currencyToCountryMap = buildCurrencyToCountryMap();
        
        // Country change → auto-update currency and language
        $('#amadex-regional-country').off('change.amadexRegional').on('change.amadexRegional', function() {
            const countryCode = $(this).val();
            const $option = $(this).find('option:selected');
            const suggestedCurrency = $option.data('currency');
            const suggestedLanguage = $option.data('language');
            
            if (suggestedCurrency && $('#amadex-regional-currency option[value="' + suggestedCurrency + '"]').length) {
                $('#amadex-regional-currency').val(suggestedCurrency).trigger('change');
                            }
            
            if (suggestedLanguage && $('#amadex-regional-language option[value="' + suggestedLanguage + '"]').length) {
                $('#amadex-regional-language').val(suggestedLanguage).trigger('change');
                            }
            
            currentSettings.country = countryCode;
            if (suggestedCurrency) currentSettings.currency = suggestedCurrency;
            if (suggestedLanguage) currentSettings.language = suggestedLanguage;
        });
        
        // Currency change → auto-update country to matching country (without triggering country change to avoid loop)
        $('#amadex-regional-currency').off('change.amadexRegional').on('change.amadexRegional', function() {
            const selectedCurrency = $(this).val();
            const $selectedOption = $(this).find('option:selected');
            const symbol = $selectedOption.data('symbol') || selectedCurrency;
                        
            const matchingCountry = currencyToCountryMap[selectedCurrency];
            if (matchingCountry && $('#amadex-regional-country option[value="' + matchingCountry + '"]').length) {
                $('#amadex-regional-country').val(matchingCountry);
                currentSettings.country = matchingCountry;
                            }
            currentSettings.currency = selectedCurrency;
        });
    }
    
    /**
     * Update button display with current settings
     */
    function updateButtonDisplay() {
        const $button = $('.amadex-regional-settings-btn');
        if (!$button.length) {
            return;
        }

        $button.find('.amadex-regional-settings-language').text(getDisplayLanguageName());
        $button.find('.amadex-regional-settings-country').text(getDisplayCountryName());
        $button.find('.amadex-regional-settings-currency').text(currentSettings.currency || 'USD');
        $button.find('.amadex-regional-settings-code--country').text((currentSettings.country || 'US').toUpperCase());
        $button.find('.amadex-regional-settings-code--currency').text((currentSettings.currency || 'USD').toUpperCase());
        $button.find('.amadex-regional-settings-code--language').text(getLanguageShortCode());
    }

    function getDisplayLanguageName() {
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency && AmadexConfig.currency.languages) {
            const langEntry = AmadexConfig.currency.languages[currentSettings.language];
            if (langEntry && typeof langEntry === 'object' && langEntry.name) {
                return langEntry.name;
            }
            if (typeof langEntry === 'string') {
                return langEntry;
            }
        }
        return currentSettings.language || 'English (United States)';
    }

    function getDisplayCountryName() {
        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency && AmadexConfig.currency.countries) {
            const countryEntry = AmadexConfig.currency.countries[currentSettings.country];
            if (countryEntry && typeof countryEntry === 'object' && countryEntry.name) {
                return countryEntry.name;
            }
            if (typeof countryEntry === 'string') {
                return countryEntry;
            }
        }
        return currentSettings.country || 'United States';
    }

    function getLanguageShortCode() {
        const language = currentSettings.language || 'en';
        const shortCode = language.split('-')[0] || language;
        return shortCode.toUpperCase();
    }
    
    /**
     * Trigger currency change event for other scripts
     * 
     * This function handles currency changes by:
     * 1. Triggering events for other scripts to listen
     * 2. Converting prices on current page
     * 3. Updating flight data in sessionStorage (if exists) to prevent stale values
     * 
     * Expert/God mode fix: Updates flight data immediately when currency changes,
     * preventing stale currency values from persisting when user changes currency
     * after flight search results are displayed.
     * 
     * @since 1.1.0
     * @fires amadex:currency-changed
     * @fires amadex:convert-prices-now
     * @fires amadex:currency-updated
     * 
     * @example
     * // Triggered when user changes currency via regional settings modal
     * triggerCurrencyChange();
     * // Updates storage, converts prices, and updates flight data
     */
    function triggerCurrencyChange() {
        const newCurrency = currentSettings.currency;
        

        
        // Check if regional settings system is enabled
        const regionalSettingsEnabled = typeof AmadexConfig !== 'undefined' && 
                                       AmadexConfig.currency && 
                                       AmadexConfig.currency.regionalSettingsEnabled !== false;
        
        // Trigger custom event for currency change
        $(document).trigger('amadex:currency-changed', [newCurrency]);
        
        // If on results page, convert prices immediately
        // Check if conversion function exists (from amadex.js)
        if (typeof window.amadexConvertAllPrices === 'function') {
            window.amadexConvertAllPrices(newCurrency);
        } else if (typeof convertAllPricesToCurrency === 'function') {
            convertAllPricesToCurrency(newCurrency);
        } else {
            // Trigger event for amadex.js to listen
            $(document).trigger('amadex:convert-prices-now', [newCurrency]);
        }
        
        // CRITICAL FIX: Update flight data in sessionStorage if it exists
        // This prevents stale currency values from persisting when user changes currency
        // after flight search results are displayed
        // Only update if regional settings are enabled
        if (regionalSettingsEnabled) {
            try {
                const storedFlightKey = 'amadex_booking_flight';
                const storedFlight = sessionStorage.getItem(storedFlightKey);
                
                if (storedFlight) {
                    try {
                        const flight = JSON.parse(storedFlight);
                        const previousCurrency = flight.selected_currency || 'none';
                        
                        // Update currency in flight data
                        flight.selected_currency = newCurrency;
                        if (!flight.price) {
                            flight.price = {};
                        }
                        flight.price.selected_currency = newCurrency;
                        
                        // Save updated flight data back to sessionStorage
                        sessionStorage.setItem(storedFlightKey, JSON.stringify(flight));
                        
                        // Log if currency was changed
                        if (previousCurrency !== 'none' && previousCurrency !== newCurrency) {
                                                        if (typeof amadex_log === 'function') {
                                amadex_log('Flight data currency updated from ' + previousCurrency + ' to ' + newCurrency + ' (currency change)');
                            }
                        } else {
                        }
                    } catch (parseError) {
                                                // Non-critical error - continue with currency change
                    }
                } else {

                }
                
                // Also check for multi-city flights
                const storedAllSegmentsKey = 'amadex_booking_all_segments';
                const storedAllSegments = sessionStorage.getItem(storedAllSegmentsKey);
                
                if (storedAllSegments) {
                    try {
                        const allSegments = JSON.parse(storedAllSegments);
                        if (Array.isArray(allSegments)) {
                            let updatedCount = 0;
                            allSegments.forEach((segment, index) => {
                                if (segment) {
                                    const previousCurrency = segment.selected_currency || 'none';
                                    segment.selected_currency = newCurrency;
                                    if (!segment.price) {
                                        segment.price = {};
                                    }
                                    segment.price.selected_currency = newCurrency;
                                    
                                    if (previousCurrency !== 'none' && previousCurrency !== newCurrency) {
                                        updatedCount++;
                                    }
                                }
                            });
                            
                            // Save updated segments back to sessionStorage
                            sessionStorage.setItem(storedAllSegmentsKey, JSON.stringify(allSegments));
                            
                            if (updatedCount > 0) {
                                                                if (typeof amadex_log === 'function') {
                                    amadex_log('Multi-city flight segments currency updated to ' + newCurrency);
                                }
                            }
                        }
                    } catch (parseError) {
                                                // Non-critical error - continue with currency change
                    }
                }
            } catch (storageError) {
                                // Non-critical error - continue with currency change
                // Storage might be disabled or quota exceeded
            }
        } else {
                    }
        
        // Also trigger global currency change for any other listeners
        $(window).trigger('amadex:currency-updated', [newCurrency]);
        

    }
    
    /**
     * Show notification (optional helper)
     */
    function showNotification(message, type) {
        type = type || 'success';
        // Remove any existing notifications
        $('.amadex-regional-notification').remove();
        
        // Simple notification - can be enhanced
        const $notification = $('<div class="amadex-regional-notification amadex-regional-notification-' + type + '">' + message + '</div>');
        $('body').append($notification);
        
                
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Build a minimal fallback modal in case PHP template was not rendered
     * This ensures the currency popup can still open even if the shortcode
     * did not output the modal HTML for some reason.
     */
    function buildFallbackModalFromConfig() {
        if ($('#amadex-regional-settings-modal').length) {
            return;
        }



        // Prepare options from AmadexConfig if available
        let languageOptions = '';
        let countryOptions = '';
        let currencyOptions = '';

        if (typeof AmadexConfig !== 'undefined' && AmadexConfig.currency) {
            const cfg = AmadexConfig.currency;

            // Languages
            if (cfg.languages) {
                Object.keys(cfg.languages).forEach(function(code) {
                    const langEntry = cfg.languages[code];
                    let name = code;
                    if (typeof langEntry === 'string') {
                        name = langEntry;
                    } else if (langEntry && typeof langEntry === 'object' && langEntry.name) {
                        name = langEntry.name;
                    }
                    languageOptions += '<option value="' + code + '">' + name + '</option>';
                });
            }

            // Countries (with data-currency and data-language for country/currency sync)
            if (cfg.countries) {
                Object.keys(cfg.countries).forEach(function(code) {
                    const countryEntry = cfg.countries[code];
                    let name = code;
                    let currency = '';
                    let language = '';
                    if (typeof countryEntry === 'string') {
                        name = countryEntry;
                    } else if (countryEntry && typeof countryEntry === 'object') {
                        if (countryEntry.name) name = countryEntry.name;
                        if (countryEntry.currency) currency = countryEntry.currency;
                        if (countryEntry.language) language = countryEntry.language;
                    }
                    countryOptions += '<option value="' + code + '" data-currency="' + (currency || '') + '" data-language="' + (language || '') + '">' + name + '</option>';
                });
            }

            // Currencies
            if (cfg.currencies) {
                Object.keys(cfg.currencies).forEach(function(code) {
                    const info = cfg.currencies[code] || {};
                    const symbol = info.symbol || code;
                    const displayText = code + ' - ' + symbol;
                    currencyOptions += '<option value="' + code + '" data-symbol="' + symbol + '">' + displayText + '</option>';
                });
            }
        }

        // Basic fallbacks if config missing
        if (!languageOptions) {
            languageOptions = '<option value="en-US">English (United States)</option>';
        }
        if (!countryOptions) {
            countryOptions = '<option value="US">United States</option>';
        }
        if (!currencyOptions) {
            currencyOptions = '<option value="USD" data-symbol="$">USD - $</option>';
        }

        const modalHtml =
            '<div id="amadex-regional-settings-modal" class="amadex-regional-modal" style="display: none;">' +
                '<div class="amadex-regional-modal-overlay"></div>' +
                '<div class="amadex-regional-modal-content">' +
                    '<div class="amadex-regional-modal-header">' +
                        '<h2 class="amadex-regional-modal-title">Regional settings</h2>' +
                        '<button type="button" class="amadex-regional-modal-close" aria-label="Close">' +
                            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                                '<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                            '</svg>' +
                        '</button>' +
                    '</div>' +
                    '<div class="amadex-regional-modal-body">' +
                        '<div class="amadex-regional-field">' +
                            '<label for="amadex-regional-language" class="amadex-regional-label">Language</label>' +
                            '<select id="amadex-regional-language" class="amadex-regional-select" name="language">' +
                                languageOptions +
                            '</select>' +
                        '</div>' +
                        '<div class="amadex-regional-field">' +
                            '<label for="amadex-regional-country" class="amadex-regional-label">Country / Region</label>' +
                            '<p class="amadex-regional-description">Selecting the country you\'re in will give you local deals and information.</p>' +
                            '<select id="amadex-regional-country" class="amadex-regional-select" name="country">' +
                                countryOptions +
                            '</select>' +
                        '</div>' +
                        '<div class="amadex-regional-field">' +
                            '<label for="amadex-regional-currency" class="amadex-regional-label">Currency</label>' +
                            '<select id="amadex-regional-currency" class="amadex-regional-select" name="currency">' +
                                currencyOptions +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="amadex-regional-modal-footer">' +
                        '<button type="button" class="amadex-regional-btn amadex-regional-btn-cancel">Cancel</button>' +
                        '<button type="button" class="amadex-regional-btn amadex-regional-btn-save">Save</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(modalHtml);

    }
    
    /**
     * Get current regional settings
     */
    function getCurrentSettings() {
        return currentSettings;
    }
    
    // Initialize on document ready
    $(document).ready(function() {

        
        // Function to check if modal exists and retry if needed
        function ensureModalExists(retries = 10) {
            const $modal = $('#amadex-regional-settings-modal');
            if ($modal.length) {
                                initRegionalSettings();
                return true;
            } else if (retries > 0) {

                setTimeout(function() {
                    ensureModalExists(retries - 1);
                }, 300); // Increased delay to 300ms
                return false;
            } else {
                // Check if button exists but modal doesn't - this means modal wasn't included
                const $button = $('#amadex-regional-settings-trigger, .amadex-regional-settings-trigger');
                if ($button.length && !$modal.length) {
                    buildFallbackModalFromConfig();
                    // After building fallback modal, try initialization again
                    const $newModal = $('#amadex-regional-settings-modal');
                    if ($newModal.length) {

                        initRegionalSettings();
                        return true;
                    }
                }
                
                // Still initialize settings even if modal is missing
                initRegionalSettings();
                return false;
            }
        }
        
        // Start checking for modal immediately
        ensureModalExists();
        
        // Also check again after a longer delay (in case modal loads late)
        setTimeout(function() {
            const $modal = $('#amadex-regional-settings-modal');
            if (!$modal.length) {

            } else {
                            }
        }, 2000);
        
        // Listen for currency changes from other sources (like results page selector)
        $(document).on('amadex:currency-changed', function(event, currency) {
            if (currency && currency !== currentSettings.currency) {
                currentSettings.currency = currency;
                saveSettings();
            }
        });
    });
    
    // Expose conversion function globally so it can be called from amadex.js
    window.convertAllPricesToCurrency = function(targetCurrency) {
        // Check if function exists in amadex.js scope
        if (typeof window.amadexConvertAllPrices === 'function') {
            return window.amadexConvertAllPrices(targetCurrency);
        }
        
        // Fallback: trigger the conversion via event
        $(document).trigger('amadex:convert-prices', [targetCurrency]);
    };
    
    // Expose API for other scripts
    window.AmadexRegionalSettings = {
        getCurrentSettings: getCurrentSettings,
        openModal: openModal,
        closeModal: closeModal,
        convertPrices: function(currency) {
            triggerCurrencyChange();
        }
    };
    
})(jQuery);