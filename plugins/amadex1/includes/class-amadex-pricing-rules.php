<?php
/**
 * Amadex Pricing Rules Engine
 * Handles dynamic pricing rules with discount and flat fee calculations
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pricing Rules Engine Class
 */
class Amadex_Pricing_Rules {
    
    /**
     * Table name
     */
    private static $table_name;
    
    /**
     * Initialize
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'amadex_pricing_rules';
    }
    
    /**
     * Check if pricing rules engine is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option('amadex_pricing_rules_settings', array());
        return isset($settings['enable_pricing_rules_engine']) && $settings['enable_pricing_rules_engine'] == 1;
    }
    
    /**
     * Get all rules for a currency
     *
     * @param string $currency Currency code (default: USD)
     * @return array
     */
    public static function get_rules($currency = 'USD') {
        global $wpdb;
        if (empty(self::$table_name)) {
            self::init();
        }
        
        if (empty(self::$table_name)) {
            return array();
        }
        
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " 
            WHERE currency = %s AND is_enabled = 1 
            ORDER BY sort_order ASC, id ASC",
            $currency
        ), ARRAY_A);
        
        return $rules ? $rules : array();
    }
    
    /**
     * Find matching rule for a base price
     *
     * @param float $base_price Base price (B)
     * @param string $currency Currency code (default: USD)
     * @return array|null Matching rule or null
     */
    public static function find_matching_rule($base_price, $currency = 'USD') {
        if (empty(self::$table_name)) {
            self::init();
        }
        $rules = self::get_rules($currency);
        
        if (empty($rules)) {
            return null;
        }
        
        // First, try to find a rule that matches the price range
        foreach ($rules as $rule) {
            $min = floatval($rule['min_amount']);
            $max = $rule['max_amount'] !== null ? floatval($rule['max_amount']) : null;
            
            // Check if base_price falls within this rule's range
            if ($base_price >= $min && ($max === null || $base_price <= $max)) {
                return $rule;
            }
        }
        
        // If no match found, try to find default rule
        foreach ($rules as $rule) {
            if (isset($rule['is_default']) && $rule['is_default'] == 1) {
                return $rule;
            }
        }
        
        return null;
    }
    
    /**
     * Calculate pricing using rules engine
     * 
     * Integrates with Price Markup settings:
     * - Applies markup percentage/fixed/airline-specific from settings first
     * - Then applies discount from matching rule
     * - Finally adds flat fee from matching rule
     *
     * Formula:
     * B_markup = B × (1 + markup%) + fixed_markup
     * P_display = B_markup × (1 - discount%)
     * P_charge = B_markup + flat_fee
     *
     * @param float $base_price Base price (B) from API
     * @param string $currency Currency code (default: USD)
     * @param string $airline_code Airline code for airline-specific markup (optional)
     * @return array Pricing result with P_display and P_charge
     */
    public static function calculate_pricing($base_price, $currency = 'USD', $airline_code = '') {
        // If rules engine is not enabled, return original price
        if (!self::is_enabled()) {
            return array(
                'original_total' => $base_price,
                'display_total' => $base_price,
                'charge_total' => $base_price,
                'discount_percent' => 0,
                'flat_fee_amount' => 0,
                'rule_id' => null,
                'rule_name' => null,
                'markup_applied' => 0,
                'price_after_markup' => $base_price
            );
        }
        
        // Step 1: Apply Price Markup settings first (if enabled)
        $markup_applied = 0;
        $price_after_markup = $base_price;
        
        if (class_exists('Amadex_Pricing')) {
            $pricing_settings = Amadex_Pricing::get_pricing_settings();
            
            if (!empty($pricing_settings['enable_price_markup'])) {
                $markup_percentage = 0;
                $markup_fixed = 0;
                $markup_type = $pricing_settings['price_markup_type'] ?? 'percentage';
                
                // Check for airline-specific markup
                if (!empty($airline_code) && !empty($pricing_settings['airline_specific_markup'])) {
                    $airline_markup = self::get_airline_specific_markup($airline_code, $pricing_settings['airline_specific_markup']);
                    if ($airline_markup !== false) {
                        $markup_percentage = floatval($airline_markup);
                    }
                }
                
                // Use global markup if no airline-specific markup
                if ($markup_percentage == 0) {
                    $markup_percentage = floatval($pricing_settings['price_markup_percentage'] ?? 0);
                    $markup_fixed = floatval($pricing_settings['price_markup_fixed'] ?? 0);
                }
                
                // Apply markup
                if ($markup_type === 'percentage' || $markup_type === 'both') {
                    $price_after_markup = $base_price * (1 + ($markup_percentage / 100));
                    $markup_applied = $price_after_markup - $base_price;
                }
                
                if ($markup_type === 'fixed' || $markup_type === 'both') {
                    $price_after_markup += $markup_fixed;
                    $markup_applied += $markup_fixed;
                }
            }
        }
        
        // Step 2: Find matching rule based on price AFTER markup
        $rule = self::find_matching_rule($price_after_markup, $currency);
        
        if (!$rule) {
            // No rule found, return price with markup but no discount/flat fee
            return array(
                'original_total' => $base_price,
                'display_total' => round($price_after_markup, 2),
                'charge_total' => round($price_after_markup, 2),
                'discount_percent' => 0,
                'flat_fee_amount' => 0,
                'rule_id' => null,
                'rule_name' => null,
                'markup_applied' => round($markup_applied, 2),
                'price_after_markup' => round($price_after_markup, 2)
            );
        }
        
        // Step 3: Calculate P_display and P_charge using price after markup
        $discount_percent = floatval($rule['discount_percent']);
        $flat_fee = floatval($rule['flat_fee_amount']);
        
        // Handle discount/markup percentage:
        // Positive values = discount (decrease): P_display = B_markup × (1 - discount%)
        // Negative values = markup (increase): P_display = B_markup × (1 + |markup%|)
        if ($discount_percent >= 0) {
            // Positive = discount (decrease price)
            $p_display = round($price_after_markup * (1 - ($discount_percent / 100)), 2);
        } else {
            // Negative = markup (increase price)
            $p_display = round($price_after_markup * (1 + (abs($discount_percent) / 100)), 2);
        }
        
        // P_charge = B_markup + flat_fee
        $p_charge = round($price_after_markup + $flat_fee, 2);
        
        // Ensure P_charge > B_markup (flat_fee must be > 0)
        if ($p_charge <= $price_after_markup) {
            error_log('Amadex Pricing Rules: Warning - P_charge (' . $p_charge . ') is not greater than B_markup (' . $price_after_markup . '). Flat fee: ' . $flat_fee);
        }
        
        return array(
            'original_total' => $base_price,
            'display_total' => $p_display,
            'charge_total' => $p_charge,
            'discount_percent' => $discount_percent,
            'flat_fee_amount' => $flat_fee,
            'rule_id' => intval($rule['id']),
            'rule_name' => $rule['name'],
            'markup_applied' => round($markup_applied, 2),
            'price_after_markup' => round($price_after_markup, 2)
        );
    }
    
    /**
     * Create a new pricing rule
     *
     * @param array $data Rule data
     * @return int|WP_Error Rule ID or error
     */
    public static function create_rule($data) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::init();
        }
        
        if (empty(self::$table_name)) {
            return new WP_Error('table_not_exists', __('Pricing rules table does not exist. Please deactivate and reactivate the plugin.', 'amadex'));
        }
        
        // Validate data
        $validation = self::validate_rule_data($data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'currency' => strtoupper(sanitize_text_field($data['currency'] ?? 'USD')),
            'min_amount' => floatval($data['min_amount'] ?? 0),
            'max_amount' => isset($data['max_amount']) && $data['max_amount'] !== '' ? floatval($data['max_amount']) : null,
            'discount_percent' => floatval($data['discount_percent'] ?? 0),
            'flat_fee_amount' => floatval($data['flat_fee_amount'] ?? 0),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'is_enabled' => isset($data['is_enabled']) ? intval($data['is_enabled']) : 1,
            'is_default' => isset($data['is_default']) ? intval($data['is_default']) : 0
        );
        
        // Verify table exists before inserting
        $wpdb->suppress_errors(true);
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", self::$table_name));
        $wpdb->suppress_errors(false);
        
        if ($table_check !== self::$table_name) {
            error_log('Amadex Pricing Rules: Table does not exist - ' . self::$table_name);
            return new WP_Error('table_not_exists', __('Pricing rules table does not exist. Please go to Database Setup and create the tables.', 'amadex'));
        }
        
        // Use format array for proper data types
        $format = array('%s', '%s', '%f', '%f', '%f', '%f', '%d', '%d', '%d');
        
        error_log('Amadex Pricing Rules: Attempting to insert into table: ' . self::$table_name);
        error_log('Amadex Pricing Rules: Insert data: ' . print_r($insert_data, true));
        
        $result = $wpdb->insert(self::$table_name, $insert_data, $format);
        
        if ($result === false) {
            $error_message = $wpdb->last_error ? $wpdb->last_error : __('Database error occurred', 'amadex');
            error_log('Amadex Pricing Rules: Database insert failed - ' . $error_message);
            error_log('Amadex Pricing Rules: Insert data - ' . print_r($insert_data, true));
            error_log('Amadex Pricing Rules: Table name - ' . self::$table_name);
            error_log('Amadex Pricing Rules: SQL - ' . $wpdb->last_query);
            return new WP_Error('db_error', __('Failed to create pricing rule: ', 'amadex') . $error_message);
        }
        
        $insert_id = $wpdb->insert_id;
        if (empty($insert_id)) {
            error_log('Amadex Pricing Rules: Insert succeeded but no ID returned');
            error_log('Amadex Pricing Rules: Last query - ' . $wpdb->last_query);
            return new WP_Error('db_error', __('Rule created but could not retrieve ID. Please refresh the page.', 'amadex'));
        }
        
        error_log('Amadex Pricing Rules: Rule created successfully with ID: ' . $insert_id);
        return $insert_id;
    }
    
    /**
     * Update a pricing rule
     *
     * @param int $rule_id Rule ID
     * @param array $data Rule data
     * @return bool|WP_Error
     */
    public static function update_rule($rule_id, $data) {
        global $wpdb;
        self::init();
        
        // Validate data
        $validation = self::validate_rule_data($data, $rule_id);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $update_data = array(
            'name' => sanitize_text_field($data['name']),
            'currency' => strtoupper(sanitize_text_field($data['currency'] ?? 'USD')),
            'min_amount' => floatval($data['min_amount'] ?? 0),
            'max_amount' => isset($data['max_amount']) && $data['max_amount'] !== '' ? floatval($data['max_amount']) : null,
            'discount_percent' => floatval($data['discount_percent'] ?? 0),
            'flat_fee_amount' => floatval($data['flat_fee_amount'] ?? 0),
            'sort_order' => intval($data['sort_order'] ?? 0),
            'is_enabled' => isset($data['is_enabled']) ? intval($data['is_enabled']) : 1,
            'is_default' => isset($data['is_default']) ? intval($data['is_default']) : 0
        );
        
        $result = $wpdb->update(
            self::$table_name,
            $update_data,
            array('id' => intval($rule_id)),
            array('%s', '%s', '%f', '%f', '%f', '%f', '%d', '%d', '%d'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update pricing rule.', 'amadex'));
        }
        
        return true;
    }
    
    /**
     * Delete a pricing rule
     *
     * @param int $rule_id Rule ID
     * @return bool|WP_Error
     */
    public static function delete_rule($rule_id) {
        global $wpdb;
        self::init();
        
        $result = $wpdb->delete(
            self::$table_name,
            array('id' => intval($rule_id)),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete pricing rule.', 'amadex'));
        }
        
        return true;
    }
    
    /**
     * Get airline-specific markup percentage
     *
     * @param string $airline_code Airline code (e.g., 'AA', 'DL')
     * @param string $airline_markup_string Markup string from settings
     * @return float|false Markup percentage or false if not found
     */
    private static function get_airline_specific_markup($airline_code, $airline_markup_string) {
        if (empty($airline_code) || empty($airline_markup_string)) {
            return false;
        }
        
        $lines = explode("\n", $airline_markup_string);
        $airline_code_upper = strtoupper(trim($airline_code));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Format: AA:15 or AA:15.5
            if (preg_match('/^([A-Z0-9]+):\s*(-?\d+\.?\d*)$/i', $line, $matches)) {
                $code = strtoupper(trim($matches[1]));
                $markup = floatval($matches[2]);
                
                if ($code === $airline_code_upper) {
                    return $markup;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get a single rule by ID
     *
     * @param int $rule_id Rule ID
     * @return array|null
     */
    public static function get_rule($rule_id) {
        global $wpdb;
        self::init();
        
        $rule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            intval($rule_id)
        ), ARRAY_A);
        
        return $rule ? $rule : null;
    }
    
    /**
     * Validate rule data
     *
     * @param array $data Rule data
     * @param int $exclude_id Rule ID to exclude from overlap check
     * @return bool|WP_Error
     */
    public static function validate_rule_data($data, $exclude_id = null) {
        $min_amount = floatval($data['min_amount'] ?? 0);
        $max_amount = isset($data['max_amount']) && $data['max_amount'] !== '' ? floatval($data['max_amount']) : null;
        $discount_percent = floatval($data['discount_percent'] ?? 0);
        $flat_fee = floatval($data['flat_fee_amount'] ?? 0);
        $currency = strtoupper(sanitize_text_field($data['currency'] ?? 'USD'));
        
        // Validate discount/markup (-100% to +100%)
        // Positive values = discount (decrease price)
        // Negative values = markup (increase price)
        if ($discount_percent < -100 || $discount_percent > 100) {
            return new WP_Error('invalid_discount', __('Percentage must be between -100% and +100%. Use positive values for discount (decrease) or negative values for markup (increase).', 'amadex'));
        }
        
        // Validate flat fee (must be > 0)
        if ($flat_fee <= 0) {
            return new WP_Error('invalid_flat_fee', __('Flat fee amount must be greater than 0.', 'amadex'));
        }
        
        // Validate min/max amounts
        if ($min_amount < 0) {
            return new WP_Error('invalid_min_amount', __('Minimum amount cannot be negative.', 'amadex'));
        }
        
        if ($max_amount !== null && $max_amount < $min_amount) {
            return new WP_Error('invalid_max_amount', __('Maximum amount must be greater than or equal to minimum amount.', 'amadex'));
        }
        
        // Check for overlapping rules (same currency)
        $overlap = self::check_rule_overlap($min_amount, $max_amount, $currency, $exclude_id);
        if (is_wp_error($overlap)) {
            return $overlap;
        }
        
        return true;
    }
    
    /**
     * Check if a rule overlaps with existing rules
     *
     * @param float $min_amount Minimum amount
     * @param float|null $max_amount Maximum amount (null = unlimited)
     * @param string $currency Currency code
     * @param int $exclude_id Rule ID to exclude from check
     * @return bool|WP_Error
     */
    private static function check_rule_overlap($min_amount, $max_amount, $currency, $exclude_id = null) {
        global $wpdb;
        self::init();
        
        $exclude_clause = $exclude_id ? $wpdb->prepare(" AND id != %d", intval($exclude_id)) : '';
        
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT min_amount, max_amount FROM " . self::$table_name . " 
            WHERE currency = %s AND is_enabled = 1" . $exclude_clause,
            $currency
        ), ARRAY_A);
        
        foreach ($rules as $rule) {
            $rule_min = floatval($rule['min_amount']);
            $rule_max = $rule['max_amount'] !== null ? floatval($rule['max_amount']) : null;
            
            // Check for overlap
            $overlaps = false;
            
            if ($max_amount === null && $rule_max === null) {
                // Both are unlimited - they overlap
                $overlaps = true;
            } elseif ($max_amount === null) {
                // New rule is unlimited, check if it starts before existing rule ends
                $overlaps = ($min_amount <= $rule_max);
            } elseif ($rule_max === null) {
                // Existing rule is unlimited, check if it starts before new rule ends
                $overlaps = ($rule_min <= $max_amount);
            } else {
                // Both have limits - check for overlap
                $overlaps = !($max_amount < $rule_min || $min_amount > $rule_max);
            }
            
            if ($overlaps) {
                return new WP_Error('rule_overlap', sprintf(
                    __('This rule overlaps with an existing rule (Range: %s - %s). Rules cannot overlap for the same currency.', 'amadex'),
                    number_format($rule_min, 2),
                    $rule_max !== null ? number_format($rule_max, 2) : '∞'
                ));
            }
        }
        
        return true;
    }
    
    /**
     * Simulate pricing calculation
     *
     * @param float $base_price Base price to test
     * @param string $currency Currency code
     * @return array Simulation result
     */
    public static function simulate($base_price, $currency = 'USD') {
        $result = self::calculate_pricing($base_price, $currency);
        
        return array(
            'base_price' => $base_price,
            'matched_rule' => $result['rule_id'] ? self::get_rule($result['rule_id']) : null,
            'p_display' => $result['display_total'],
            'p_charge' => $result['charge_total'],
            'discount_percent' => $result['discount_percent'],
            'flat_fee' => $result['flat_fee_amount'],
            'discount_amount' => abs($base_price - $result['display_total']), // Absolute difference
            'markup_amount' => $result['charge_total'] - $base_price
        );
    }
}

// Initialize on load
Amadex_Pricing_Rules::init();
