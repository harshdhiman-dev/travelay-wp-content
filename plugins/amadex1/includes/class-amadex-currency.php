<?php
/**
 * Amadex Currency Conversion Service
 * Handles multi-currency support with automatic conversion to USD for NMI payments
 * 
 * @package Amadex
 * @since 1.0.0
 */

if (!class_exists('Amadex_Currency')) {
    class Amadex_Currency {
        
        /**
         * Supported currencies
         */
        private static $supported_currencies = array(
            'USD' => array('name' => 'US Dollar', 'symbol' => '$'),
            'EUR' => array('name' => 'Euro', 'symbol' => '€'),
            'GBP' => array('name' => 'British Pound', 'symbol' => '£'),
            'INR' => array('name' => 'Indian Rupee', 'symbol' => '₹'),
            'CAD' => array('name' => 'Canadian Dollar', 'symbol' => 'C$'),
            'AUD' => array('name' => 'Australian Dollar', 'symbol' => 'A$'),
            'JPY' => array('name' => 'Japanese Yen', 'symbol' => '¥'),
            'CNY' => array('name' => 'Chinese Yuan', 'symbol' => '¥'),
            'SGD' => array('name' => 'Singapore Dollar', 'symbol' => 'S$'),
            'AED' => array('name' => 'UAE Dirham', 'symbol' => 'د.إ'),
            'BDT' => array('name' => 'Bangladeshi Taka', 'symbol' => '৳'),
            'PKR' => array('name' => 'Pakistani Rupee', 'symbol' => '₨'),
            'MXN' => array('name' => 'Mexican Peso', 'symbol' => '$'),
            'BRL' => array('name' => 'Brazilian Real', 'symbol' => 'R$'),
        );
        
        /**
         * Supported languages
         */
        private static $supported_languages = array(
            'en-US' => array('name' => 'English (United States)', 'code' => 'en', 'country' => 'US'),
            'en-GB' => array('name' => 'English (United Kingdom)', 'code' => 'en', 'country' => 'GB'),
            'en-IN' => array('name' => 'English (India)', 'code' => 'en', 'country' => 'IN'),
            'en-AU' => array('name' => 'English (Australia)', 'code' => 'en', 'country' => 'AU'),
            'en-CA' => array('name' => 'English (Canada)', 'code' => 'en', 'country' => 'CA'),
            'es-ES' => array('name' => 'Español (España)', 'code' => 'es', 'country' => 'ES'),
            'es-MX' => array('name' => 'Español (México)', 'code' => 'es', 'country' => 'MX'),
            'fr-FR' => array('name' => 'Français (France)', 'code' => 'fr', 'country' => 'FR'),
            'de-DE' => array('name' => 'Deutsch (Deutschland)', 'code' => 'de', 'country' => 'DE'),
            'it-IT' => array('name' => 'Italiano (Italia)', 'code' => 'it', 'country' => 'IT'),
            'pt-BR' => array('name' => 'Português (Brasil)', 'code' => 'pt', 'country' => 'BR'),
            'ja-JP' => array('name' => '日本語 (日本)', 'code' => 'ja', 'country' => 'JP'),
            'zh-CN' => array('name' => '中文 (简体)', 'code' => 'zh', 'country' => 'CN'),
            'ar-AE' => array('name' => 'العربية (الإمارات)', 'code' => 'ar', 'country' => 'AE'),
            'hi-IN' => array('name' => 'हिन्दी (भारत)', 'code' => 'hi', 'country' => 'IN'),
        );
        
        /**
         * Supported countries/regions
         */
        private static $supported_countries = array(
            'US' => array('name' => 'United States', 'currency' => 'USD', 'language' => 'en-US', 'flag' => 'us'),
            'GB' => array('name' => 'United Kingdom', 'currency' => 'GBP', 'language' => 'en-GB', 'flag' => 'gb'),
            'IN' => array('name' => 'India', 'currency' => 'INR', 'language' => 'en-IN', 'flag' => 'in'),
            'CA' => array('name' => 'Canada', 'currency' => 'CAD', 'language' => 'en-CA', 'flag' => 'ca'),
            'AU' => array('name' => 'Australia', 'currency' => 'AUD', 'language' => 'en-AU', 'flag' => 'au'),
            'MX' => array('name' => 'Mexico', 'currency' => 'MXN', 'language' => 'es-MX', 'flag' => 'mx'),
            'BR' => array('name' => 'Brazil', 'currency' => 'BRL', 'language' => 'pt-BR', 'flag' => 'br'),
            'DE' => array('name' => 'Germany', 'currency' => 'EUR', 'language' => 'de-DE', 'flag' => 'de'),
            'FR' => array('name' => 'France', 'currency' => 'EUR', 'language' => 'fr-FR', 'flag' => 'fr'),
            'IT' => array('name' => 'Italy', 'currency' => 'EUR', 'language' => 'it-IT', 'flag' => 'it'),
            'ES' => array('name' => 'Spain', 'currency' => 'EUR', 'language' => 'es-ES', 'flag' => 'es'),
            'JP' => array('name' => 'Japan', 'currency' => 'JPY', 'language' => 'ja-JP', 'flag' => 'jp'),
            'CN' => array('name' => 'China', 'currency' => 'CNY', 'language' => 'zh-CN', 'flag' => 'cn'),
            'AE' => array('name' => 'United Arab Emirates', 'currency' => 'AED', 'language' => 'en-GB', 'flag' => 'ae'),
            'SG' => array('name' => 'Singapore', 'currency' => 'SGD', 'language' => 'en-GB', 'flag' => 'sg'),
            'BD' => array('name' => 'Bangladesh', 'currency' => 'BDT', 'language' => 'en-GB', 'flag' => 'bd'),
            'PK' => array('name' => 'Pakistan', 'currency' => 'PKR', 'language' => 'en-GB', 'flag' => 'pk'),
        );

        private static function get_cookie_value($key) {
            if (!isset($_COOKIE[$key])) {
                return '';
            }
            return sanitize_text_field(wp_unslash($_COOKIE[$key]));
        }

        private static function get_saved_region_settings() {
            $language = self::get_cookie_value('amadex_region_language');
            $country = strtoupper(self::get_cookie_value('amadex_region_country'));
            $currency = strtoupper(self::get_cookie_value('amadex_region_currency'));

            if ($language && !isset(self::$supported_languages[$language])) {
                $language = '';
            }

            if ($country && !isset(self::$supported_countries[$country])) {
                $country = '';
            }

            if ($currency && !self::is_valid_currency($currency)) {
                $currency = '';
            }

            if (!$language && !$country && !$currency) {
                return null;
            }

            return array(
                'language' => $language ?: null,
                'country' => $country ?: null,
                'currency' => $currency ?: null
            );
        }
        
        /**
         * Get supported currencies
         */
        public static function get_supported_currencies() {
            return self::$supported_currencies;
        }
        
        /**
         * Get currency symbol
         */
        public static function get_currency_symbol($currency_code) {
            $currency_code = strtoupper($currency_code);
            if (isset(self::$supported_currencies[$currency_code])) {
                return self::$supported_currencies[$currency_code]['symbol'];
            }
            return $currency_code . ' ';
        }
        
        /**
         * Get currency name
         */
        public static function get_currency_name($currency_code) {
            $currency_code = strtoupper($currency_code);
            if (isset(self::$supported_currencies[$currency_code])) {
                return self::$supported_currencies[$currency_code]['name'];
            }
            return $currency_code;
        }
        
        /**
         * Get exchange rate from cache or API
         * 
         * @param string $from_currency Source currency code
         * @param string $to_currency Target currency code (default: USD)
         * @return float Exchange rate
         */
        public static function get_exchange_rate($from_currency, $to_currency = 'USD') {
            $from_currency = strtoupper($from_currency);
            $to_currency = strtoupper($to_currency);
            
            // Same currency
            if ($from_currency === $to_currency) {
                return 1.0;
            }
            
            // Check cache first (stored in WordPress options, updated daily)
            // Use function_exists check for WordPress functions
            if (function_exists('get_transient')) {
                $cache_key = 'amadex_exchange_rate_' . $from_currency . '_' . $to_currency;
                $cached_rate = get_transient($cache_key);
                
                if ($cached_rate !== false) {
                    return floatval($cached_rate);
                }
            }
            
            // Fetch from API
            $rate = self::fetch_exchange_rate($from_currency, $to_currency);
            
            // Cache for 24 hours (if WordPress functions available)
            if ($rate > 0 && function_exists('set_transient')) {
                $cache_key = 'amadex_exchange_rate_' . $from_currency . '_' . $to_currency;
                set_transient($cache_key, $rate, DAY_IN_SECONDS);
            }
            
            return $rate;
        }
        
        /**
         * Fetch exchange rate from API
         * Uses exchangerate-api.com (free tier: 1,500 requests/month)
         * Fallback to manual rates if API fails
         */
        private static function fetch_exchange_rate($from_currency, $to_currency) {
            // Try to use exchangerate-api.com (free, no API key needed for basic usage)
            // Only use wp_remote_get if WordPress function is available
            if (function_exists('wp_remote_get')) {
                $api_url = 'https://api.exchangerate-api.com/v4/latest/' . $from_currency;
                
                $response = wp_remote_get($api_url, array(
                    'timeout' => 5,
                    'sslverify' => true
                ));
                
                if (!is_wp_error($response) && function_exists('wp_remote_retrieve_body')) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    
                    if (isset($data['rates'][$to_currency])) {
                        $rate = floatval($data['rates'][$to_currency]);
                        if (function_exists('error_log')) {
                            error_log('Amadex Currency: Fetched rate from API - ' . $from_currency . ' to ' . $to_currency . ': ' . $rate);
                        }
                        return $rate;
                    }
                }
            }
            
            // Fallback to manual rates (can be configured in settings)
            $manual_rates = self::get_manual_exchange_rates();
            $rate_key = $from_currency . '_' . $to_currency;
            
            if (isset($manual_rates[$rate_key])) {
                if (function_exists('error_log')) {
                    error_log('Amadex Currency: Using manual rate - ' . $from_currency . ' to ' . $to_currency . ': ' . $manual_rates[$rate_key]);
                }
                return floatval($manual_rates[$rate_key]);
            }
            
            // EXPERT/GOD MODE FIX: Calculate inverse if reverse rate exists
            // Manual rates are stored as "Currency → USD" (e.g., INR_USD = 0.012 means 1 INR = 0.012 USD)
            // But the system needs "USD → Currency" rates for display conversions (e.g., USD_INR = 83.33 means 1 USD = 83.33 INR)
            // Solution: When direct rate not found, check for reverse rate and calculate inverse
            // This ensures USD → Currency conversions work correctly even when API fails
            $reverse_rate_key = $to_currency . '_' . $from_currency;  // e.g., 'INR_USD' when looking for 'USD_INR'
            if (isset($manual_rates[$reverse_rate_key])) {
                $reverse_rate = floatval($manual_rates[$reverse_rate_key]);
                if ($reverse_rate > 0) {
                    $inverse_rate = 1.0 / $reverse_rate;
                    if (function_exists('error_log')) {
                        error_log('Amadex Currency: Calculated inverse rate - ' . $from_currency . ' to ' . $to_currency . ': ' . $inverse_rate . ' (from reverse rate ' . $reverse_rate_key . ': ' . $reverse_rate . ')');
                    }
                    return $inverse_rate;
                }
            }
            
            // Last resort: return 1.0 (no conversion)
            if (function_exists('error_log')) {
                error_log('Amadex Currency: Warning - No exchange rate found for ' . $from_currency . ' to ' . $to_currency . ', using 1.0');
            }
            return 1.0;
        }
        
        /**
         * Get manual exchange rates (fallback)
         * Can be configured in admin settings
         */
        private static function get_manual_exchange_rates() {
            // Default manual rates (approximate, should be updated regularly)
            $default_rates = array(
                'EUR_USD' => 1.08,
                'GBP_USD' => 1.27,
                'INR_USD' => 0.012,
                'CAD_USD' => 0.74,
                'AUD_USD' => 0.66,
                'JPY_USD' => 0.0067,
                'CNY_USD' => 0.14,
                'SGD_USD' => 0.74,
                'AED_USD' => 0.27,
                'BDT_USD' => 0.0091,
                'PKR_USD' => 0.0036,
                'MXN_USD' => 0.059,
                'BRL_USD' => 0.20,
            );
            
            // Get settings only if WordPress function is available
            if (function_exists('get_option')) {
                $settings = get_option('amadex_currency_settings', array());
                
                // Merge with custom rates from settings (only non-empty values override defaults)
                if (isset($settings['manual_rates']) && is_array($settings['manual_rates'])) {
                    foreach ($settings['manual_rates'] as $key => $rate) {
                        if (!empty($rate) && is_numeric($rate) && $rate > 0) {
                            $default_rates[$key] = floatval($rate);
                        }
                    }
                }
            }
            
            return $default_rates;
        }
        
        /**
         * Convert amount from one currency to another
         * 
         * @param float $amount Amount to convert
         * @param string $from_currency Source currency
         * @param string $to_currency Target currency (default: USD)
         * @return float Converted amount
         */
        public static function convert($amount, $from_currency, $to_currency = 'USD') {
            if ($amount <= 0) {
                return 0.0;
            }
            
            $rate = self::get_exchange_rate($from_currency, $to_currency);
            $converted = $amount * $rate;
            
            // Round to 2 decimal places
            return round($converted, 2);
        }
        
        /**
         * Convert to USD (for NMI payment processing)
         * This is the critical function that ensures NMI receives USD amounts
         * 
         * @param float $amount Amount in any currency
         * @param string $from_currency Source currency
         * @return array Array with 'amount' (USD) and 'original_amount' (original currency)
         */
        public static function convert_to_usd($amount, $from_currency) {
            $from_currency = strtoupper($from_currency);
            
            if ($from_currency === 'USD') {
                return array(
                    'amount' => round($amount, 2),
                    'original_amount' => round($amount, 2),
                    'currency' => 'USD',
                    'original_currency' => 'USD',
                    'exchange_rate' => 1.0
                );
            }
            
            $rate = self::get_exchange_rate($from_currency, 'USD');
            $usd_amount = round($amount * $rate, 2);
            
            return array(
                'amount' => $usd_amount,
                'original_amount' => round($amount, 2),
                'currency' => 'USD',
                'original_currency' => $from_currency,
                'exchange_rate' => $rate
            );
        }
        
        /**
         * Format currency amount for display
         * 
         * @param float $amount Amount to format
         * @param string $currency Currency code
         * @param bool $show_symbol Whether to show currency symbol
         * @return string Formatted amount
         */
        public static function format($amount, $currency = 'USD', $show_symbol = true) {
            $currency = strtoupper($currency);
            $symbol = $show_symbol ? self::get_currency_symbol($currency) : '';
            
            // Format number with 2 decimal places
            $formatted = number_format($amount, 2, '.', ',');
            
            // ALWAYS format with currency symbol FIRST, then amount (consistent across all currencies)
            // Changed from: "3505.19 ₹" to "₹3505.19" for all currencies
            return $symbol . $formatted;
        }
        
        /**
         * Get user's country code from IP address
         * 
         * When Regional Settings System is disabled, this method immediately returns 'US'
         * without performing any IP geolocation API calls, improving performance.
         * 
         * @since 1.0.0
         * @return string Country code (e.g., 'US', 'IN', 'GB')
         * 
         * @example
         * // Get country from IP (respects regional settings toggle)
         * $country = Amadex_Currency::get_country_from_ip();
         * // Returns 'US' if regional settings disabled, otherwise detected country
         */
        public static function get_country_from_ip() {
            // Check if regional settings system is enabled (using cached helper method)
            if (!self::is_regional_settings_enabled()) {
                amadex_log('Regional Settings System disabled - returning US as default country (skipping IP geolocation)');
                return 'US';
            }
            
            // Check if we have cached country in session/transient
            if (function_exists('get_transient')) {
                $ip = self::get_user_ip();
                $cache_key = 'amadex_country_' . md5($ip);
                $cached_country = get_transient($cache_key);
                
                if ($cached_country !== false) {
                    return $cached_country;
                }
            }
            
            // Try to get country from IP using free API
            $country_code = self::detect_country_from_ip();
            
            // Cache for 24 hours
            if ($country_code && function_exists('set_transient')) {
                $ip = self::get_user_ip();
                $cache_key = 'amadex_country_' . md5($ip);
                set_transient($cache_key, $country_code, DAY_IN_SECONDS);
            }
            
            return $country_code ?: 'US'; // Default to US if detection fails
        }
        
        /**
         * Get user's IP address
         * 
         * @return string IP address
         */
        private static function get_user_ip() {
            $ip_keys = array(
                'HTTP_CF_CONNECTING_IP', // Cloudflare
                'HTTP_X_REAL_IP',       // Nginx proxy
                'HTTP_X_FORWARDED_FOR',  // Proxy
                'REMOTE_ADDR'            // Standard
            );
            
            foreach ($ip_keys as $key) {
                if (!empty($_SERVER[$key])) {
                    $ip = $_SERVER[$key];
                    // Handle comma-separated IPs (from X-Forwarded-For)
                    if (strpos($ip, ',') !== false) {
                        $ip = trim(explode(',', $ip)[0]);
                    }
                    // Validate IP
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
            
            // Fallback to REMOTE_ADDR even if private
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        }
        
        /**
         * Detect country from IP address using multiple free APIs with fallbacks
         * 
         * @return string|false Country code or false on failure
         */
        private static function detect_country_from_ip() {
            $ip = self::get_user_ip();
            
            // Skip localhost/private IPs
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
            
            if (!function_exists('wp_remote_get')) {
                return false;
            }
            
            // API 1: Try ipapi.co (free, no API key needed, 1000 requests/day)
            $country_code = self::detect_via_ipapi_co($ip);
            if ($country_code) {
                return $country_code;
            }
            
            // API 2: Try ip-api.com (free, no API key needed, 45 requests/minute)
            $country_code = self::detect_via_ip_api_com($ip);
            if ($country_code) {
                return $country_code;
            }
            
            // API 3: Try ipgeolocation.io (free tier: 1000 requests/month, requires API key but has free endpoint)
            $country_code = self::detect_via_ipgeolocation($ip);
            if ($country_code) {
                return $country_code;
            }
            
            // API 4: Try ip-api.io (free, no API key needed)
            $country_code = self::detect_via_ip_api_io($ip);
            if ($country_code) {
                return $country_code;
            }
            
            return false;
        }
        
        /**
         * Detect country via ipapi.co
         */
        private static function detect_via_ipapi_co($ip) {
            $api_url = 'https://ipapi.co/' . $ip . '/country_code/';
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 3,
                'sslverify' => true,
                'user-agent' => 'Amadex-WordPress-Plugin'
            ));
            
            if (!is_wp_error($response) && function_exists('wp_remote_retrieve_body')) {
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code === 200) {
                    $country_code = trim(wp_remote_retrieve_body($response));
                    if (strlen($country_code) === 2 && ctype_alpha($country_code)) {
                        return strtoupper($country_code);
                    }
                }
            }
            
            return false;
        }
        
        /**
         * Detect country via ip-api.com
         */
        private static function detect_via_ip_api_com($ip) {
            $api_url = 'http://ip-api.com/json/' . $ip . '?fields=countryCode';
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 3,
                'sslverify' => false, // HTTP endpoint
                'user-agent' => 'Amadex-WordPress-Plugin'
            ));
            
            if (!is_wp_error($response) && function_exists('wp_remote_retrieve_body')) {
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (isset($data['countryCode']) && strlen($data['countryCode']) === 2) {
                        return strtoupper($data['countryCode']);
                    }
                }
            }
            
            return false;
        }
        
        /**
         * Detect country via ip-api.io
         */
        private static function detect_via_ip_api_io($ip) {
            $api_url = 'https://ip-api.io/json/' . $ip;
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 3,
                'sslverify' => true,
                'user-agent' => 'Amadex-WordPress-Plugin'
            ));
            
            if (!is_wp_error($response) && function_exists('wp_remote_retrieve_body')) {
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (isset($data['country_code']) && strlen($data['country_code']) === 2) {
                        return strtoupper($data['country_code']);
                    }
                }
            }
            
            return false;
        }
        
        /**
         * Detect country via ipgeolocation.io (basic endpoint, no API key required for country only)
         */
        private static function detect_via_ipgeolocation($ip) {
            // Using the free endpoint that doesn't require API key for basic country detection
            $api_url = 'https://api.ipgeolocation.io/ipgeo?ip=' . $ip;
            
            $response = wp_remote_get($api_url, array(
                'timeout' => 3,
                'sslverify' => true,
                'user-agent' => 'Amadex-WordPress-Plugin'
            ));
            
            if (!is_wp_error($response) && function_exists('wp_remote_retrieve_body')) {
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    // Check for error message
                    if (!isset($data['message']) && isset($data['country_code2']) && strlen($data['country_code2']) === 2) {
                        return strtoupper($data['country_code2']);
                    }
                }
            }
            
            return false;
        }
        
        /**
         * Map country code to currency
         * 
         * @param string $country_code Country code (e.g., 'US', 'IN', 'GB')
         * @return string Currency code
         */
        public static function get_currency_by_country($country_code) {
            $country_code = strtoupper($country_code);
            
            // Country to currency mapping
            $country_currency_map = array(
                'US' => 'USD', 'CA' => 'CAD', 'MX' => 'MXN',
                'GB' => 'GBP', 'IE' => 'EUR',
                'IN' => 'INR', 'BD' => 'BDT', 'PK' => 'PKR',
                'AU' => 'AUD', 'NZ' => 'NZD',
                'JP' => 'JPY', 'CN' => 'CNY', 'SG' => 'SGD',
                'AE' => 'AED', 'SA' => 'SAR', 'QA' => 'QAR',
                'BR' => 'BRL', 'AR' => 'ARS',
                'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
                'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
                'GR' => 'EUR', 'FI' => 'EUR', 'DK' => 'EUR', 'SE' => 'EUR',
                'PL' => 'EUR', 'CZ' => 'EUR', 'HU' => 'EUR', 'RO' => 'EUR',
            );
            
            return isset($country_currency_map[$country_code]) ? $country_currency_map[$country_code] : 'USD';
        }
        
        /**
         * Clear cached regional settings enabled status
         * 
         * This method clears the transient cache for regional settings enabled status.
         * Should be called when the toggle is changed in admin settings to ensure
         * immediate effect across all pages.
         * 
         * @since 1.1.0
         * @return bool True if cache was cleared, false otherwise
         * 
         * @example
         * // Clear cache when toggle changes
         * Amadex_Currency::clear_regional_settings_cache();
         */
        public static function clear_regional_settings_cache() {
            $cache_key = 'amadex_regional_settings_enabled';
            $cleared = delete_transient($cache_key);
            if ($cleared) {
                amadex_log('Regional Settings cache cleared successfully');
            } else {
                amadex_log('Regional Settings cache clear attempted (may not have existed)');
            }
            return $cleared;
        }
        
        /**
         * Check if Regional Settings System is enabled
         * 
         * This method provides a centralized, cached way to check the regional settings toggle status.
         * Uses WordPress transients for performance optimization (cached for 1 hour).
         * 
         * @since 1.1.0
         * @return bool True if regional settings are enabled, false if disabled
         * 
         * @example
         * // Check if regional settings are enabled before performing geolocation
         * if (Amadex_Currency::is_regional_settings_enabled()) {
         *     $country = Amadex_Currency::get_country_from_ip();
         * } else {
         *     $country = 'US'; // Force USA when disabled
         * }
         */
        public static function is_regional_settings_enabled() {
            // Use transient cache for performance (1 hour cache)
            $cache_key = 'amadex_regional_settings_enabled';
            $cached = get_transient($cache_key);
            
            // Return cached value if available
            if ($cached !== false) {
                return (bool) $cached;
            }
            
            // Get settings with fallback
            if (!function_exists('get_option')) {
                // WordPress not available - default to enabled for backward compatibility
                amadex_log('WordPress get_option() not available - defaulting regional settings to enabled', 'warning');
                return true;
            }
            
            $settings = get_option('amadex_currency_settings', array());
            
            // Validate settings structure
            if (!is_array($settings)) {
                amadex_log('Invalid currency settings structure - defaulting regional settings to enabled', 'warning');
                // Cache the default value
                set_transient($cache_key, true, HOUR_IN_SECONDS);
                return true;
            }
            
            // Check toggle status (default to enabled for backward compatibility)
            $regional_settings_enabled = isset($settings['enable_regional_settings']) 
                ? (bool) $settings['enable_regional_settings'] 
                : true;
            
            // Cache the result for 1 hour
            set_transient($cache_key, $regional_settings_enabled, HOUR_IN_SECONDS);
            
            return $regional_settings_enabled;
        }
        
        /**
         * Get default currency (from settings, IP detection, or browser locale)
         * Priority: 1) IP Geolocation, 2) Settings, 3) USD (fallback)
         * 
         * @since 1.0.0
         * @return string Currency code (ISO 4217 format, e.g., 'USD', 'EUR', 'INR')
         * 
         * @example
         * // Get default currency (respects regional settings toggle)
         * $currency = Amadex_Currency::get_default_currency();
         * // Returns 'USD' if regional settings disabled, otherwise detected/saved currency
         */
        public static function get_default_currency() {
            // Check if regional settings system is enabled (using cached helper method)
            if (!self::is_regional_settings_enabled()) {
                amadex_log('Regional Settings System disabled - returning USD as default currency');
                return 'USD';
            }
            
            $saved = self::get_saved_region_settings();
            if ($saved && !empty($saved['currency'])) {
                return $saved['currency'];
            }
            
            // Priority 1: Detect from IP address (geolocation - FIRST PRIORITY)
            if (function_exists('get_option')) {
                $auto_detect = isset($settings['auto_detect_currency']) ? $settings['auto_detect_currency'] : true;
                
                if ($auto_detect) {
                    $country_code = self::get_country_from_ip();
                    // If geolocation fails, get_country_from_ip() returns 'US' as fallback
                    $currency = self::get_currency_by_country($country_code);
                    if (self::is_valid_currency($currency)) {
                        return $currency;
                    }
                }
            }
            
            // Priority 2: Check admin settings
            if (function_exists('get_option')) {
                $settings = get_option('amadex_currency_settings', array());
                if (isset($settings['default_currency']) && !empty($settings['default_currency'])) {
                    $currency = strtoupper($settings['default_currency']);
                    // Validate currency is supported
                    if (self::is_valid_currency($currency)) {
                        return $currency;
                    }
                }
            }
            
            // Priority 3: Default to USD (fallback if geolocation fails)
            return 'USD';
        }
        
        /**
         * Validate currency code
         */
        public static function is_valid_currency($currency_code) {
            $currency_code = strtoupper($currency_code);
            return isset(self::$supported_currencies[$currency_code]);
        }
        
        /**
         * Get supported languages
         */
        public static function get_supported_languages() {
            return self::$supported_languages;
        }
        
        /**
         * Get supported countries
         */
        public static function get_supported_countries() {
            return self::$supported_countries;
        }
        
        /**
         * Get country info by code
         */
        public static function get_country_info($country_code) {
            $country_code = strtoupper($country_code);
            return isset(self::$supported_countries[$country_code]) ? self::$supported_countries[$country_code] : null;
        }
        
        /**
         * Get language info by code
         */
        public static function get_language_info($language_code) {
            return isset(self::$supported_languages[$language_code]) ? self::$supported_languages[$language_code] : null;
        }
        
        /**
         * Get default language from browser or IP
         * Priority: 1) IP Geolocation, 2) Browser, 3) Settings, 4) en-US (fallback)
         * 
         * @since 1.0.0
         * @return string Language code (e.g., 'en-US', 'en-GB', 'en-IN')
         * 
         * @example
         * // Get default language (respects regional settings toggle)
         * $language = Amadex_Currency::get_default_language();
         * // Returns 'en-US' if regional settings disabled, otherwise detected/saved language
         */
        public static function get_default_language() {
            // Check if regional settings system is enabled (using cached helper method)
            if (!self::is_regional_settings_enabled()) {
                amadex_log('Regional Settings System disabled - returning en-US as default language');
                return 'en-US';
            }
            
            $saved = self::get_saved_region_settings();
            if ($saved && !empty($saved['language'])) {
                return $saved['language'];
            }
            
            // Priority 1: Detect from IP country (geolocation - FIRST PRIORITY)
            $country_code = self::get_country_from_ip();
            // If geolocation fails, get_country_from_ip() returns 'US' as fallback
            $country_info = self::get_country_info($country_code);
            if ($country_info && isset($country_info['language'])) {
                return $country_info['language'];
            }
            
            // Priority 2: Try to detect from browser
            if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);
                if (isset(self::$supported_languages[$browser_lang])) {
                    return $browser_lang;
                }
                
                // Try to match by language code
                $lang_code = substr($browser_lang, 0, 2);
                foreach (self::$supported_languages as $code => $info) {
                    if ($info['code'] === $lang_code) {
                        return $code;
                    }
                }
            }
            
            // Priority 3: Check admin settings
            if (function_exists('get_option')) {
                $settings = get_option('amadex_currency_settings', array());
                if (isset($settings['default_language']) && !empty($settings['default_language'])) {
                    $lang = $settings['default_language'];
                    if (isset(self::$supported_languages[$lang])) {
                        return $lang;
                    }
                }
            }
            
            // Priority 4: Default to English (United States) - fallback if geolocation fails
            return 'en-US';
        }
        
        /**
         * Get user's regional settings (language, country, currency)
         * Priority: 1) Saved settings, 2) IP Geolocation, 3) USA/USD/en-US (fallback)
         * 
         * When Regional Settings System is disabled, always returns USA/USD/en-US regardless
         * of user's location, saved preferences, or IP geolocation.
         * 
         * @since 1.0.0
         * @return array Associative array with keys: language, language_name, country, country_name, currency, currency_name, currency_symbol
         * 
         * @example
         * // Get user's regional settings (respects regional settings toggle)
         * $settings = Amadex_Currency::get_user_regional_settings();
         * // Returns USA/USD/en-US array if regional settings disabled
         * // Otherwise returns detected/saved settings
         */
        public static function get_user_regional_settings() {
            // Check if regional settings system is enabled (using cached helper method)
            if (!self::is_regional_settings_enabled()) {
                amadex_log('Regional Settings System disabled - returning USA/USD/en-US as default regional settings');
                return array(
                    'language' => 'en-US',
                    'language_name' => 'English (United States)',
                    'country' => 'US',
                    'country_name' => 'United States',
                    'currency' => 'USD',
                    'currency_name' => 'US Dollar',
                    'currency_symbol' => '$'
                );
            }
            
            $saved = self::get_saved_region_settings();
            if ($saved) {
                // User has saved settings - use them, but fallback to geolocation if missing
                $country_code = $saved['country'] ?: self::get_country_from_ip();
                $country_info = self::get_country_info($country_code);
                $language = $saved['language'] ?: self::get_default_language();
                $currency = $saved['currency'] ?: self::get_default_currency();
            } else {
                // No saved settings - use IP geolocation (FIRST PRIORITY)
                $country_code = self::get_country_from_ip();
                // get_country_from_ip() returns 'US' if geolocation fails
                $country_info = self::get_country_info($country_code);

                // Get defaults (which prioritize IP geolocation)
                $language = self::get_default_language();
                $currency = self::get_default_currency();

                // Override with country defaults if available (from geolocation)
                if ($country_info) {
                    if (isset($country_info['language'])) {
                        $language = $country_info['language'];
                    }
                    if (isset($country_info['currency'])) {
                        $currency = $country_info['currency'];
                    }
                }
            }
            
            return array(
                'language' => $language,
                'language_name' => isset(self::$supported_languages[$language]) ? self::$supported_languages[$language]['name'] : 'English (United States)',
                'country' => $country_code,
                'country_name' => $country_info ? $country_info['name'] : 'United States',
                'currency' => $currency,
                'currency_name' => self::get_currency_name($currency),
                'currency_symbol' => self::get_currency_symbol($currency)
            );
        }
        
        /**
         * Get user's selected currency from booking data or session
         * Priority: cookies (regional settings) > booking data > flight_data > detected currency
         * 
         * @param array $booking_data Optional booking data array
         * @return string Currency code (e.g., 'USD', 'CAD', 'EUR')
         */
        public static function get_user_selected_currency($booking_data = null) {
            // Priority 1: Check regional settings cookie (user's saved preference)
            // This is the MOST IMPORTANT as it represents user's explicit selection
            $cookie_currency = self::get_cookie_value('amadex_region_currency');
            if ($cookie_currency) {
                $currency = strtoupper($cookie_currency);
                if (self::is_valid_currency($currency)) {
                    return $currency;
                }
            }
            
            // Priority 2: Check saved regional settings (from get_saved_region_settings)
            $saved_settings = self::get_saved_region_settings();
            if ($saved_settings && !empty($saved_settings['currency'])) {
                $currency = strtoupper($saved_settings['currency']);
                if (self::is_valid_currency($currency)) {
                    return $currency;
                }
            }
            
            // Priority 3: Check booking data (if provided)
            if ($booking_data && isset($booking_data['selected_currency'])) {
                $currency = strtoupper($booking_data['selected_currency']);
                if (self::is_valid_currency($currency)) {
                    return $currency;
                }
            }
            
            // Priority 4: Check booking flight_data for currency
            if ($booking_data && isset($booking_data['flight_data'])) {
                $flight_data = is_string($booking_data['flight_data']) 
                    ? json_decode($booking_data['flight_data'], true) 
                    : $booking_data['flight_data'];
                
                if (isset($flight_data['selected_currency'])) {
                    $currency = strtoupper($flight_data['selected_currency']);
                    if (self::is_valid_currency($currency)) {
                        return $currency;
                    }
                }
                
                if (isset($flight_data['price']['currency'])) {
                    $currency = strtoupper($flight_data['price']['currency']);
                    if (self::is_valid_currency($currency)) {
                        return $currency;
                    }
                }
            }
            
            // Priority 5: Use detected currency from IP (fallback)
            return self::get_default_currency();
        }
        
        /**
         * Convert and format price for display based on user's selected currency
         * 
         * @param float $usd_amount Amount in USD
         * @param string $target_currency Target currency code
         * @param bool $format Whether to format with currency symbol
         * @return array|string If format=true, returns formatted string; else returns array with amount, currency, rate
         */
        public static function convert_and_format_price($usd_amount, $target_currency = null, $format = true) {
            // Get target currency if not provided
            if (!$target_currency) {
                $target_currency = self::get_default_currency();
            }
            
            $target_currency = strtoupper($target_currency);
            
            // If USD or invalid currency, return as-is
            if ($target_currency === 'USD' || !self::is_valid_currency($target_currency)) {
                if ($format) {
                    return self::format($usd_amount, 'USD');
                }
                return array(
                    'amount' => $usd_amount,
                    'currency' => 'USD',
                    'exchange_rate' => 1.0
                );
            }
            
            // Convert from USD to target currency
            $rate = self::get_exchange_rate('USD', $target_currency);
            $converted_amount = $usd_amount * $rate;
            
            if ($format) {
                return self::format($converted_amount, $target_currency);
            }
            
            return array(
                'amount' => round($converted_amount, 2),
                'currency' => $target_currency,
                'exchange_rate' => $rate,
                'original_amount' => $usd_amount,
                'original_currency' => 'USD'
            );
        }
    }
}
