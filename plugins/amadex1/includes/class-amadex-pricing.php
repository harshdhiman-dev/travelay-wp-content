<?php
/**
 * Amadex Pricing Management
 * Handles price calculations with markup and adjustments
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pricing Management Class
 */
class Amadex_Pricing {
    
    /**
     * Get pricing settings
     */
    public static function get_pricing_settings() {
        $settings = get_option('amadex_pricing_settings', array());
        
        // Default settings
        $defaults = array(
            'enable_price_markup' => 0,
            'price_markup_type' => 'percentage',
            'price_markup_percentage' => 10,
            'price_markup_fixed' => 0,
            'airline_specific_markup' => '',
            'round_prices' => 'none'
        );
        
        return wp_parse_args($settings, $defaults);
    }
    
     /**
     * Calculate price with markup - PROPORTIONAL to base fare and taxes
     *
     * Applies the same percentage discount/markup to both base fare and taxes.
     * This keeps the fare structure clean and airline-safe.
     *
     * Example: 10% discount on total = 10% off base fare + 10% off taxes
     * - Base Fare: $155.00 - 10% = $139.50
     * - Taxes: $171.56 - 10% = $154.40
     * - Total: $139.50 + $154.40 = $293.90 (same as $326.56 × 90%)
     *
     * @param float $original_total Original total price from API
     * @param string $airline_code Airline code (e.g., 'AA', 'DL')
     * @param float $original_base Original base fare (optional, calculated if not provided)
     * @param float $original_taxes Original taxes (optional, calculated if not provided)
     * @return array Adjusted prices: ['total' => float, 'base' => float, 'taxes' => float]
     */
    public static function calculate_price_with_markup($original_total, $airline_code = '', $original_base = null, $original_taxes = null) {
        $settings = self::get_pricing_settings();
        
        // If markup is disabled, return original prices
        if (empty($settings['enable_price_markup'])) {
            // Calculate taxes if not provided
            if ($original_taxes === null) {
                $original_taxes = $original_base !== null ? ($original_total - $original_base) : 0;
            }
            if ($original_base === null) {
                $original_base = $original_total - $original_taxes;
            }
            
            return array(
                'total' => floatval($original_total),
                'base' => floatval($original_base),
                'taxes' => floatval($original_taxes)
            );
        }
        
        $total = floatval($original_total);
        
        // Calculate base and taxes if not provided
        if ($original_base === null || $original_taxes === null) {
            if ($original_base !== null) {
                $original_taxes = $total - $original_base;
            } elseif ($original_taxes !== null) {
                $original_base = $total - $original_taxes;
            } else {
                // Estimate: base = 90% of total, taxes = 10% (common airline structure)
                $original_base = $total * 0.9;
                $original_taxes = $total * 0.1;
            }
        }
        
        $base = floatval($original_base);
        $taxes = floatval($original_taxes);
        
        // Get markup percentage (can be negative for discounts)
        $markup_percentage = 0;
        $markup_fixed = 0;
        $markup_type = 'percentage'; // Default
        
        // Check for airline-specific markup
        $airline_markup = self::get_airline_specific_markup($airline_code, $settings);
        
        if ($airline_markup !== false) {
            // Use airline-specific markup percentage
            $markup_percentage = floatval($airline_markup);
        } else {
            // Use global markup settings
            $markup_type = $settings['price_markup_type'];
            $markup_percentage = floatval($settings['price_markup_percentage']);
            $markup_fixed = floatval($settings['price_markup_fixed']);
            
            // For 'fixed' or 'both' types, we need to convert fixed to percentage for proportional application
            if ($markup_type === 'fixed') {
                // Convert fixed amount to percentage of total
                if ($total > 0) {
                    $markup_percentage = ($markup_fixed / $total) * 100;
                }
                $markup_fixed = 0; // Will be applied after proportional adjustment
            } elseif ($markup_type === 'both') {
                // For 'both', apply percentage proportionally, then add fixed to total
                // Fixed will be added to total at the end
            }
        }
        
        // Apply SAME percentage to both base fare and taxes (proportional discount/markup)
        // This is the key requirement: same percentage on both components
        $base_multiplier = 1 + ($markup_percentage / 100);
        $taxes_multiplier = 1 + ($markup_percentage / 100);
        
        $adjusted_base = $base * $base_multiplier;
        $adjusted_taxes = $taxes * $taxes_multiplier;
        
        // For 'both' type, add fixed amount to total (distribute proportionally)
        if ($markup_type === 'both' && $markup_fixed != 0) {
            // Distribute fixed amount proportionally between base and taxes
            if ($total > 0) {
                $base_ratio = $base / $total;
                $taxes_ratio = $taxes / $total;
                
                $adjusted_base += ($markup_fixed * $base_ratio);
                $adjusted_taxes += ($markup_fixed * $taxes_ratio);
            } else {
                // If total is 0, add to base
                $adjusted_base += $markup_fixed;
            }
        }
        
        // Calculate new total
        $adjusted_total = $adjusted_base + $adjusted_taxes;
        
        // Ensure prices don't go below $0.01 (minimum price)
        if ($adjusted_total < 0.01) {
            $adjusted_total = 0.01;
            $adjusted_base = 0.01;
            $adjusted_taxes = 0;
        } else {
            // Ensure base and taxes are at least 0
            if ($adjusted_base < 0) {
                $adjusted_base = 0;
            }
            if ($adjusted_taxes < 0) {
                $adjusted_taxes = 0;
            }
            // Recalculate total to ensure consistency
            $adjusted_total = $adjusted_base + $adjusted_taxes;
        }
        
        // Apply rounding
        $rounding_type = $settings['round_prices'];
        $adjusted_base = self::round_price($adjusted_base, $rounding_type);
        $adjusted_taxes = self::round_price($adjusted_taxes, $rounding_type);
        $adjusted_total = self::round_price($adjusted_total, $rounding_type);
        
        // Final consistency check: ensure total = base + taxes (handle rounding differences)
        $calculated_total = $adjusted_base + $adjusted_taxes;
        if (abs($adjusted_total - $calculated_total) > 0.01) {
            // Adjust total to match base + taxes
            $adjusted_total = $calculated_total;
        }
        
        return array(
            'total' => $adjusted_total,
            'base' => $adjusted_base,
            'taxes' => $adjusted_taxes,
            'original_total' => $total,
            'original_base' => $base,
            'original_taxes' => $taxes
        );
    }
    
    /**
     * Legacy method for backward compatibility
     * Returns only the total price (for code that expects a single float)
     *
     * @param float $original_price Original price from API
     * @param string $airline_code Airline code (e.g., 'AA', 'DL')
     * @return float Adjusted total price with markup
     */
    public static function calculate_price_with_markup_legacy($original_price, $airline_code = '') {
        $result = self::calculate_price_with_markup($original_price, $airline_code);
        return is_array($result) ? $result['total'] : floatval($result);
    }
    
    /**
     * Get airline-specific markup percentage
     *
     * @param string $airline_code Airline code
     * @param array $settings Pricing settings
     * @return float|false Markup percentage or false if not found
     */
    private static function get_airline_specific_markup($airline_code, $settings) {
        if (empty($airline_code) || empty($settings['airline_specific_markup'])) {
            return false;
        }
        
        $airline_markups = $settings['airline_specific_markup'];
        $lines = explode("\n", $airline_markups);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $parts = explode(':', $line);
            if (count($parts) === 2) {
                $code = trim($parts[0]);
                $markup = floatval(trim($parts[1]));
                
                if (strtoupper($code) === strtoupper($airline_code)) {
                    return $markup;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Round price based on settings
     *
     * @param float $price Price to round
     * @param string $rounding_type Type of rounding
     * @return float Rounded price
     */
    private static function round_price($price, $rounding_type) {
        switch ($rounding_type) {
            case 'nearest_1':
                return round($price);
                
            case 'nearest_5':
                return round($price / 5) * 5;
                
            case 'nearest_10':
                return round($price / 10) * 10;
                
            case 'round_up_5':
                return ceil($price / 5) * 5;
                
            case 'round_up_10':
                return ceil($price / 10) * 10;
                
            case 'ending_99':
                return floor($price) + 0.99;
                
            case 'none':
            default:
                return round($price, 2);
        }
    }
    
    /**
     * Get pricing settings for JavaScript
     * Returns settings in a format suitable for JS
     */
    public static function get_pricing_settings_for_js() {
        $settings = self::get_pricing_settings();
        
        // Parse airline-specific markups into an object
        $airline_markups = array();
        if (!empty($settings['airline_specific_markup'])) {
            $lines = explode("\n", $settings['airline_specific_markup']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                $parts = explode(':', $line);
                if (count($parts) === 2) {
                    $code = strtoupper(trim($parts[0]));
                    $markup = floatval(trim($parts[1]));
                    $airline_markups[$code] = $markup;
                }
            }
        }
        
        return array(
            'enabled' => (bool) $settings['enable_price_markup'],
            'type' => $settings['price_markup_type'],
            'percentage' => floatval($settings['price_markup_percentage']),
            'fixed' => floatval($settings['price_markup_fixed']),
            'airlineMarkups' => $airline_markups,
            'rounding' => $settings['round_prices']
        );
    }
    
    /**
     * Unified Price Breakdown Calculation
     * 
     * This function matches the JavaScript populatePriceBreakdown logic exactly.
     * Used by both confirmation page and email templates to ensure consistency.
     * 
     * Formula: Total = Base Fare + Taxes + Seat Selection + Premium Service/Add-ons
     * 
     * @param array $booking Booking data array
     * @return array Price breakdown with: base_fare, taxes, premium_service, seat_selection, total, currency
     */
    public static function get_unified_price_breakdown($booking) {
        // PRIORITY 1: Use stored total_amount from booking (same as NMI transaction)
        // This ensures total matches exactly what was sent to NMI
        $stored_total = floatval($booking['total_amount'] ?? 0);
        $currency = $booking['currency'] ?? 'USD'; // This is USD (stored for NMI)
        
        // CRITICAL: Log for debugging currency conversion issues
        if (function_exists('amadex_log')) {
            amadex_log('Amadex Pricing: get_unified_price_breakdown called');
            amadex_log('  Stored Total (USD from DB): $' . $stored_total);
            amadex_log('  Booking Currency: ' . $currency);
            amadex_log('  Booking Reference: ' . ($booking['booking_reference'] ?? 'N/A'));
        }
        
        // Get flight_data (contains original and adjusted prices)
        // This is the EXACT same data structure used by the booking page JavaScript
        $flight_data = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();
        
        // Check for currency conversion info (display currency selected by customer)
        $display_currency = 'USD'; // Default
        $exchange_rate = 1.0;
        $charge_total_was_usd = false; // Track if charge_total was already in USD (from pricing rules)
        
        if (isset($flight_data['currency_conversion']) && is_array($flight_data['currency_conversion'])) {
            $conversion = $flight_data['currency_conversion'];
            $display_currency = $conversion['display_currency'] ?? 'USD';
            $exchange_rate = floatval($conversion['exchange_rate'] ?? 1.0);
            $charge_total_was_usd = isset($conversion['charge_total_was_usd']) && $conversion['charge_total_was_usd'] === true;
            
            // CRITICAL: Verify USD amount matches stored total (safety check)
            $conversion_usd_amount = floatval($conversion['usd_amount'] ?? 0);
            if ($conversion_usd_amount > 0 && abs($conversion_usd_amount - $stored_total) > 0.01) {
                // Mismatch detected - use stored_total (what was actually sent to NMI)
                if (function_exists('amadex_log')) {
                    amadex_log('Amadex Pricing WARNING: USD amount mismatch!');
                    amadex_log('  Stored Total (DB): $' . $stored_total);
                    amadex_log('  Conversion USD Amount: $' . $conversion_usd_amount);
                    amadex_log('  Using stored_total (what was sent to NMI)');
                }
                // Always use stored_total - it's the source of truth (what NMI charged)
            }
        } else {
            // Fallback for old bookings: Try to detect display currency from booking data or regional settings
            // This handles bookings created before currency_conversion info was stored
            if (class_exists('Amadex_Currency')) {
                $detected_currency = Amadex_Currency::get_user_selected_currency($booking);
                if ($detected_currency && $detected_currency !== 'USD') {
                    $display_currency = $detected_currency;
                    // Get exchange rate for conversion (USD -> display_currency)
                    $exchange_rate = Amadex_Currency::get_exchange_rate('USD', $display_currency);
                    // For old bookings, assume charge_total was USD (pricing rules always return USD)
                    $charge_total_was_usd = true;
                    
                    if (function_exists('amadex_log')) {
                        amadex_log('Amadex Pricing: Old booking detected - no currency_conversion info');
                        amadex_log('  Detected Currency: ' . $display_currency);
                        amadex_log('  Exchange Rate (USD -> ' . $display_currency . '): ' . $exchange_rate);
                        amadex_log('  charge_total_was_usd: true (assumed for old bookings)');
                    }
                }
            }
        }
        
        // Helper function to convert USD amounts to display currency
        // CRITICAL: Exchange rate direction depends on how conversion was done
        // - If charge_total_was_usd = true: rate is USD -> display_currency (multiply)
        // - If charge_total_was_usd = false: rate is display_currency -> USD (divide)
        $convert_to_display = function($usd_amount) use ($display_currency, $exchange_rate, $charge_total_was_usd) {
            if ($display_currency === 'USD' || $exchange_rate <= 0) {
                return $usd_amount;
            }
            
            if ($charge_total_was_usd) {
                // Rate is USD -> display_currency, so multiply
                $converted = round($usd_amount * $exchange_rate, 2);
                if (function_exists('amadex_log') && $usd_amount > 0) {
                    amadex_log('Amadex Pricing: Converting USD -> ' . $display_currency . ': $' . $usd_amount . ' × ' . $exchange_rate . ' = ' . $converted);
                }
                return $converted;
            } else {
                // Rate is display_currency -> USD, so divide
                $converted = round($usd_amount / $exchange_rate, 2);
                if (function_exists('amadex_log') && $usd_amount > 0) {
                    amadex_log('Amadex Pricing: Converting ' . $display_currency . ' -> USD: ' . $usd_amount . ' ÷ ' . $exchange_rate . ' = $' . $converted);
                }
                return $converted;
            }
        };
        
        // Log conversion details for debugging (after function is defined)
        if (function_exists('amadex_log') && $display_currency !== 'USD' && $stored_total > 0) {
            amadex_log('Amadex Pricing: Currency conversion summary');
            amadex_log('  Stored Total (USD from DB): $' . $stored_total);
            amadex_log('  Display Currency: ' . $display_currency);
            amadex_log('  Exchange Rate: ' . $exchange_rate);
            amadex_log('  charge_total_was_usd: ' . ($charge_total_was_usd ? 'true' : 'false'));
            $converted_total = $convert_to_display($stored_total);
            amadex_log('  Converted Total (' . $display_currency . '): ' . $converted_total);
        }
        
        // Check for premium service (matches JavaScript: sessionStorage.getItem('amadex_premium_service_added'))
        $premium_service_added = false;
        $premium_service_amount = 25.00;
        if (isset($flight_data['premium_service']) && is_array($flight_data['premium_service'])) {
            $premium_service_added = isset($flight_data['premium_service']['added']) && $flight_data['premium_service']['added'] === true;
            if (isset($flight_data['premium_service']['amount']) && $flight_data['premium_service']['amount'] > 0) {
                $premium_service_amount = floatval($flight_data['premium_service']['amount']);
            }
            // If marked as added but amount is 0 or missing, use default amount
            if ($premium_service_added && $premium_service_amount <= 0) {
                $premium_service_amount = 25.00;
            }
        }
        
        // Check for seat selection charges (matches JavaScript: window.AmadexSeatSelection.totalSeatCharges)
        $seat_charges = 0;
        $has_seat_selection_in_data = false;
        // $seat_selection_data = $flight_data['seat_selection'] ?? array();
        // if (!empty($seat_selection_data)) {
        //     $has_seat_selection_in_data = true;
         // Mark that seat selection exists in flight_data
        $seat_selection_data = $flight_data['seat_selection'] ?? array();
        // Only flag as true if seats were ACTUALLY selected (has segments with seats)
        $has_actual_seats = !empty($seat_selection_data['segments']) && is_array($seat_selection_data['segments']);
        if ($has_actual_seats) {
            foreach ($seat_selection_data['segments'] as $_seg) {
                if (!empty($_seg['seats'])) { $has_actual_seats = true; break; }
                $has_actual_seats = false;
            }
        }
        if (!empty($seat_selection_data) && $has_actual_seats) {
            $has_seat_selection_in_data = true;
         // Get total_seat_charges if available (primary source - matches JavaScript)
            if (isset($seat_selection_data['total_seat_charges'])) {
                $seat_charges = floatval($seat_selection_data['total_seat_charges']);
            }
            // If total_seat_charges is not available or 0, calculate from segments
            if ($seat_charges == 0 && isset($seat_selection_data['segments']) && is_array($seat_selection_data['segments']) && !empty($seat_selection_data['segments'])) {
                foreach ($seat_selection_data['segments'] as $segment_data) {
                    if (!empty($segment_data['seats']) && is_array($segment_data['seats'])) {
                        foreach ($segment_data['seats'] as $seat) {
                            // Calculate from seat price (matches JavaScript: seat.price)
                            if (isset($seat['price']['total'])) {
                                $seat_charges += floatval($seat['price']['total']);
                            } elseif (isset($seat['price'])) {
                                // Handle case where price is a number directly
                                $seat_charges += floatval($seat['price']);
                            }
                        }
                    }
                }
            }
        }
        
        // If seat_selection exists in flight_data but amount is still 0, calculate from difference
        // This handles cases where seat selection was selected but amount wasn't stored properly
        // if ($has_seat_selection_in_data && $seat_charges == 0 && $stored_total > 0) {
        if ($has_seat_selection_in_data && $seat_charges == 0 && $stored_total > 0 && !empty($seat_selection_data['total_seat_charges'])) {
            // We'll calculate this after we know the base_total
        }
        
        // CRITICAL FIX: Process addons from flight_data['addons'] array (new system)
        // Addons are NEVER affected by discount or price management - they are separate line items
        // They stay with the total throughout booking flow and are added to P_charge
        $addons_total = 0;
        $all_addons = array();
        if (isset($flight_data['addons']) && is_array($flight_data['addons']) && !empty($flight_data['addons'])) {
            foreach ($flight_data['addons'] as $addon) {
                if (is_array($addon) && isset($addon['price'])) {
                    $addon_price = floatval($addon['price'] ?? 0);
                    if ($addon_price > 0) {
                        $addons_total += $addon_price;
                        $all_addons[] = $addon;
                    }
                }
            }
            if (function_exists('amadex_log') && $addons_total > 0) {
                amadex_log('Amadex Pricing: Addons found in flight_data - Count: ' . count($all_addons) . ', Total: $' . $addons_total);
            }
        }
        
        // Calculate base total (without premium service, seat charges, and addons) for breakdown
        // Formula: base_total = stored_total - premium_service - seat_charges - addons
        // This gives us P_charge (from pricing rules) which is used to calculate base/taxes
        $base_total = $stored_total;
        if ($premium_service_added) {
            $base_total = $base_total - $premium_service_amount;
        }
        if ($seat_charges > 0) {
            $base_total = $base_total - $seat_charges;
        }
        if ($addons_total > 0) {
            $base_total = $base_total - $addons_total;
            if (function_exists('amadex_log')) {
                amadex_log('Amadex Pricing: Subtracted addons from base_total - Addons: $' . $addons_total . ', New base_total (P_charge): $' . $base_total);
            }
        }
        
        // If seat_selection exists in flight_data but amount is still 0, infer from difference
        // This ensures we show seat selection if it exists in flight_data, even if amount wasn't stored
        // if ($has_seat_selection_in_data && $seat_charges == 0 && $base_total > 0) {
        if ($has_seat_selection_in_data && $seat_charges == 0 && $base_total > 0 && !empty($seat_selection_data['total_seat_charges'])) {
            // Calculate what the base_total would be without seat charges
            $temp_base = $stored_total;
            if ($premium_service_added) {
                $temp_base = $temp_base - $premium_service_amount;
            }
            // If there's a difference, it's likely seat charges
            // We'll use a simple heuristic: if difference > $25, split between premium and seats
            // But since premium is already accounted for, the remainder is seats
            $potential_seat_charges = $temp_base - $base_total;
            if ($potential_seat_charges > 0) {
                $seat_charges = round($potential_seat_charges, 2);
                $base_total = $base_total - $seat_charges;
            }
        }
        
        // Get airline code (needed for price management calculation)
        $airline_code = '';
        if (!empty($flight_data['validating_airline_codes']) && is_array($flight_data['validating_airline_codes'])) {
            $airline_code = $flight_data['validating_airline_codes'][0] ?? '';
        } elseif (!empty($flight_data['validatingAirlineCodes']) && is_array($flight_data['validatingAirlineCodes'])) {
            $airline_code = $flight_data['validatingAirlineCodes'][0] ?? '';
        }
        
        // PRIORITY 2: Check if Pricing Rules Engine was used (has pricing snapshot)
        $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();
        $pricing_snapshot = null;
        $flat_fee_amount = 0;
        $markup_applied = 0;
        $display_total = 0;
        $charge_total = 0;
        
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;
            
            // Check for pricing snapshot (indicates Pricing Rules Engine was used)
            if (isset($flight_data['price']['pricing_snapshot']) && is_array($flight_data['price']['pricing_snapshot'])) {
                $pricing_snapshot = $flight_data['price']['pricing_snapshot'];
                $display_total = floatval($pricing_snapshot['display_total'] ?? 0);
                $charge_total = floatval($pricing_snapshot['charge_total'] ?? 0);
                $flat_fee_amount = floatval($pricing_snapshot['flat_fee_amount'] ?? 0);
                $markup_applied = floatval($pricing_snapshot['markup_applied'] ?? 0);
            } elseif (isset($flight_data['price']['pricing_charge_total']) && $flight_data['price']['pricing_charge_total'] > 0) {
                // Alternative: pricing data stored directly in price array
                $display_total = floatval($flight_data['price']['total'] ?? 0);
                $charge_total = floatval($flight_data['price']['pricing_charge_total'] ?? 0);
                $flat_fee_amount = floatval($flight_data['price']['flat_fee_amount'] ?? 0);
                $markup_applied = floatval($flight_data['price']['markup_applied'] ?? 0);
            }
        }
        
        // PRIORITY 3: If Pricing Rules Engine was used, use stored pricing snapshot
        if ($use_rules_engine && ($pricing_snapshot || $charge_total > 0)) {
            // Get original prices from pricing snapshot (most accurate source)
            $original_base = floatval($pricing_snapshot['original_base'] ?? $flight_data['price']['original_base'] ?? 0);
            $original_total = floatval($pricing_snapshot['original_total'] ?? $flight_data['price']['original_total'] ?? 0);
            
            // Fallback: try to get from flight_data price array
            if ($original_base <= 0) {
                $original_base = floatval($flight_data['price']['base'] ?? $flight_data['price']['grandTotal'] ?? 0);
            }
            if ($original_total <= 0) {
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? $display_total);
            }
            
            // Calculate ratio from original prices (to maintain base/taxes proportion)
            $base_ratio = ($original_total > 0) ? ($original_base / $original_total) : 0.9; // Default 90% base, 10% taxes
            
            // Break down base_total using original ratio
            $final_base = round($base_total * $base_ratio, 2);
            $final_taxes = round($base_total - $final_base, 2);
            
            // Absorb flat fee into taxes (don't show separately to users)
            if ($flat_fee_amount > 0) {
                $final_taxes = round($final_taxes + $flat_fee_amount, 2);
            }
            
            // Recalculate base if taxes are negative
            if ($final_taxes < 0) {
                $final_taxes = round($base_total * 0.10, 2);
                $final_base = round($base_total - $final_taxes, 2);
            }
            
            // Ensure final_base + final_taxes = base_total (accounting for rounding)
            $calculated_base_total = $final_base + $final_taxes;
            if (abs($calculated_base_total - $base_total) > 0.01) {
                $difference = $base_total - $calculated_base_total;
                $final_taxes = round($final_taxes + $difference, 2);
            }
            
            // Verify total: base_fare + taxes + premium_service + seat_charges + addons = stored_total
            // CRITICAL: Addons must be included in verification formula
            $calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges + $addons_total;
            if (abs($calculated_final_total - $stored_total) > 0.01) {
                $total_difference = $stored_total - $calculated_final_total;
                $final_taxes = round($final_taxes + $total_difference, 2);
                if (function_exists('amadex_log')) {
                    amadex_log('Amadex Pricing: Total verification adjustment - Difference: $' . $total_difference . ', Adjusted taxes: $' . $final_taxes);
                }
            }
            
            // Ensure convert_to_display function exists
            if (!isset($convert_to_display) || !is_callable($convert_to_display)) {
                $convert_to_display = function($usd_amount) use ($display_currency, $exchange_rate, $charge_total_was_usd) {
                    if ($display_currency === 'USD' || $exchange_rate <= 0) {
                        return $usd_amount;
                    }
                    if ($charge_total_was_usd) {
                        // Rate is USD -> display_currency, so multiply
                        return round($usd_amount * $exchange_rate, 2);
                    } else {
                        // Rate is display_currency -> USD, so divide
                        return round($usd_amount / $exchange_rate, 2);
                    }
                };
            }
            
            return array(
                'base_fare' => $convert_to_display($final_base),
                'taxes' => $convert_to_display($final_taxes),
                'premium_service' => $premium_service_added ? $convert_to_display($premium_service_amount) : 0,
                'premium_service_added' => $premium_service_added,
                'seat_selection' => $convert_to_display($seat_charges),
                'seat_selection_in_data' => $has_seat_selection_in_data, // Flag: exists in flight_data
                'addons' => $convert_to_display($addons_total), // CRITICAL FIX: Return addons total
                'addons_list' => $all_addons, // CRITICAL FIX: Return addons array for display
                'total' => $convert_to_display($stored_total),
                'currency' => $display_currency, // Return display currency, not USD
                'original_currency' => 'USD', // Original stored currency (always USD for NMI)
                'exchange_rate' => $exchange_rate
            );
        }
        
        // PRIORITY 4: Check if flight_data has already-calculated prices from booking page
        // The booking page JavaScript calculates prices and may store them - use them if available
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;
            
            // Check for stored calculated prices (exact prices shown on booking page)
            $stored_base = floatval($flight_data['price']['calculated_base'] ?? $flight_data['price']['base_with_markup'] ?? $flight_data['price']['display_base'] ?? 0);
            $stored_total_price = floatval($flight_data['price']['calculated_total'] ?? $flight_data['price']['total_with_markup'] ?? $flight_data['price']['display_total'] ?? 0);
            
            // If we have stored calculated prices, use them to maintain exact booking page values
            if ($stored_base > 0 && $stored_total_price > 0) {
                $stored_taxes = $stored_total_price - $stored_base;
                if ($stored_taxes < 0) {
                    $stored_taxes = $stored_total_price * 0.10;
                    $stored_base = $stored_total_price - $stored_taxes;
                }
                
                // Calculate ratio from stored prices to apply to base_total
                if ($base_total > 0 && $stored_total_price > 0) {
                    $price_ratio = $base_total / $stored_total_price;
                    $final_base = round($stored_base * $price_ratio, 2);
                    $final_taxes = round($base_total - $final_base, 2);
                    
                    // Ensure they add up correctly
                    if (abs(($final_base + $final_taxes) - $base_total) > 0.01) {
                        $final_taxes = round($base_total - $final_base, 2);
                    }
                    
                    // Verify total matches (include addons in formula)
                    $calculated_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges + $addons_total;
                    if (abs($calculated_total - $stored_total) > 0.01) {
                        $total_diff = $stored_total - $calculated_total;
                        $final_taxes = round($final_taxes + $total_diff, 2);
                    }
                    
                    // Ensure convert_to_display function exists
                    if (!isset($convert_to_display) || !is_callable($convert_to_display)) {
                        $convert_to_display = function($usd_amount) use ($display_currency, $exchange_rate, $charge_total_was_usd) {
                            if ($display_currency === 'USD' || $exchange_rate <= 0) {
                                return $usd_amount;
                            }
                            if ($charge_total_was_usd) {
                                // Rate is USD -> display_currency, so multiply
                                return round($usd_amount * $exchange_rate, 2);
                            } else {
                                // Rate is display_currency -> USD, so divide
                                return round($usd_amount / $exchange_rate, 2);
                            }
                        };
                    }
                    
                    return array(
                        'base_fare' => $convert_to_display($final_base),
                        'taxes' => $convert_to_display($final_taxes),
                        'premium_service' => $premium_service_added ? $convert_to_display($premium_service_amount) : 0,
                        'premium_service_added' => $premium_service_added,
                        'seat_selection' => $convert_to_display($seat_charges),
                        'seat_selection_in_data' => $has_seat_selection_in_data, // Flag: exists in flight_data
                        'addons' => $convert_to_display($addons_total), // CRITICAL FIX: Return addons total
                        'addons_list' => $all_addons, // CRITICAL FIX: Return addons array for display
                        'total' => $convert_to_display($stored_total),
                        'currency' => $display_currency, // Return display currency, not USD
                        'original_currency' => 'USD', // Original stored currency (always USD for NMI)
                        'exchange_rate' => $exchange_rate
                    );
                }
            }
        }
        
        // PRIORITY 5: Recalculate using SAME logic as booking page JavaScript
        // JavaScript: basePrice = calculatePriceWithMarkup(originalBasePrice, airlineCode)
        // JavaScript: totalPrice = calculatePriceWithMarkup(originalTotalPrice, airlineCode)
        // JavaScript: taxesAndFees = totalPrice - basePrice
        $original_base = 0;
        $original_total = 0;
        
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;
            
            // Try to get original prices first (most accurate)
            $original_base = floatval($flight_data['price']['original_base'] ?? 0);
            $original_total = floatval($flight_data['price']['original_total'] ?? 0);
            
            // If no original prices, use current prices as originals (fallback)
            if ($original_base <= 0) {
                $original_base = floatval($flight_data['price']['base'] ?? $flight_data['price']['grandTotal'] ?? $flight_data['price']['total'] ?? 0);
            }
            if ($original_total <= 0) {
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
            }
        }
        
        if ($original_base > 0 && $original_total > 0 && class_exists('Amadex_Pricing')) {
            // Apply markup to base separately (EXACTLY like JavaScript)
            $base_result = self::calculate_price_with_markup($original_base, $airline_code);
            $calculated_base = is_array($base_result) ? floatval($base_result['total'] ?? $original_base) : floatval($base_result);
            
            // Apply markup to total separately (EXACTLY like JavaScript)
            $total_result = self::calculate_price_with_markup($original_total, $airline_code);
            $calculated_total = is_array($total_result) ? floatval($total_result['total'] ?? $original_total) : floatval($total_result);
            
            // Calculate taxes (EXACTLY like booking page: taxesAndFees = totalPrice - basePrice)
            $calculated_taxes = $calculated_total - $calculated_base;
            if ($calculated_taxes < 0) {
                $calculated_taxes = $calculated_total * 0.10; // Default 10% if negative (same as booking page)
                $calculated_base = $calculated_total - $calculated_taxes;
            }
            
            // ALWAYS use base_total (without premium) for breakdown calculation
            $breakdown_total = $base_total;
            if ($breakdown_total > 0 && $calculated_total > 0) {
                // Calculate the ratio from calculated values (matching booking page structure)
                $base_ratio = $calculated_base / $calculated_total;
                
                // Apply the same ratio to breakdown_total to get base fare
                $final_base = $breakdown_total * $base_ratio;
                
                // Calculate taxes as difference (ensuring breakdown_total = base + taxes)
                $final_taxes = $breakdown_total - $final_base;
                
                // If taxes are negative, recalculate with default 10%
                if ($final_taxes < 0) {
                    $final_taxes = $breakdown_total * 0.10;
                    $final_base = $breakdown_total - $final_taxes;
                }
                
                // Verify total matches (include addons in formula)
                $calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges + $addons_total;
                if (abs($calculated_final_total - $stored_total) > 0.01) {
                    $total_diff = $stored_total - $calculated_final_total;
                    $final_taxes = round($final_taxes + $total_diff, 2);
                }
                
                // Ensure convert_to_display function exists
                if (!isset($convert_to_display) || !is_callable($convert_to_display)) {
                    $convert_to_display = function($usd_amount) use ($display_currency, $exchange_rate, $charge_total_was_usd) {
                        if ($display_currency === 'USD' || $exchange_rate <= 0) {
                            return $usd_amount;
                        }
                        if ($charge_total_was_usd) {
                            // Rate is USD -> display_currency, so multiply
                            return round($usd_amount * $exchange_rate, 2);
                        } else {
                            // Rate is display_currency -> USD, so divide
                            return round($usd_amount / $exchange_rate, 2);
                        }
                    };
                }
                
                return array(
                    'base_fare' => $convert_to_display($final_base),
                    'taxes' => $convert_to_display($final_taxes),
                    'premium_service' => $premium_service_added ? $convert_to_display($premium_service_amount) : 0,
                    'premium_service_added' => $premium_service_added,
                    'seat_selection' => $convert_to_display($seat_charges),
                    'seat_selection_in_data' => $has_seat_selection_in_data, // Flag: exists in flight_data
                    'addons' => $convert_to_display($addons_total), // CRITICAL FIX: Return addons total
                    'addons_list' => $all_addons, // CRITICAL FIX: Return addons array for display
                    'total' => $convert_to_display($stored_total),
                    'currency' => $display_currency, // Return display currency, not USD
                    'original_currency' => 'USD', // Original stored currency (always USD for NMI)
                    'exchange_rate' => $exchange_rate
                );
            }
        }
        
        // Final fallback: proportional split if all else fails
        $final_base = round($base_total * 0.90, 2);
        $final_taxes = round($base_total - $final_base, 2);
        
        // Ensure convert_to_display function exists (should be defined earlier, but safety check)
        if (!isset($convert_to_display) || !is_callable($convert_to_display)) {
            // Fallback conversion function if closure not available
            $convert_to_display = function($usd_amount) use ($display_currency, $exchange_rate, $charge_total_was_usd) {
                if ($display_currency === 'USD' || $exchange_rate <= 0) {
                    return $usd_amount;
                }
                if ($charge_total_was_usd) {
                    // Rate is USD -> display_currency, so multiply
                    return round($usd_amount * $exchange_rate, 2);
                } else {
                    // Rate is display_currency -> USD, so divide
                    return round($usd_amount / $exchange_rate, 2);
                }
            };
        }
        
        return array(
            'base_fare' => $convert_to_display($final_base),
            'taxes' => $convert_to_display($final_taxes),
            'addons' => $convert_to_display($addons_total), // CRITICAL FIX: Return addons total
            'addons_list' => $all_addons, // CRITICAL FIX: Return addons array for display
            'premium_service' => $premium_service_added ? $convert_to_display($premium_service_amount) : 0,
            'premium_service_added' => $premium_service_added,
            'seat_selection' => $convert_to_display($seat_charges),
            'seat_selection_in_data' => $has_seat_selection_in_data, // Flag: exists in flight_data
            'total' => $convert_to_display($stored_total),
            'currency' => $display_currency, // Return display currency, not USD
            'original_currency' => 'USD', // Original stored currency (always USD for NMI)
            'exchange_rate' => $exchange_rate
        );
    }
}






















