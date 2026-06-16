<?php
/**
 * Stripe Payment Gateway Integration
 * Handles PaymentIntent creation and verification for Travelay booking flow
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Global flag to track if Stripe SDK has been loaded by this plugin
// DO NOT trigger autoloaders here - that causes "already in use" errors
if (!defined('AMADEX_STRIPE_SDK_READY')) {
    define('AMADEX_STRIPE_SDK_READY', false);
}

/**
 * Stripe Payment Gateway Class
 */
class Amadex_Payment_Stripe {
    
    /**
     * Stripe secret key
     */
    private $secret_key;
    
    /**
     * Stripe mode (test/live)
     */
    private $mode;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load payment settings first (needed regardless of library state)
        $payment_settings = get_option('amadex_payment_settings', array());
        $this->mode = isset($payment_settings['stripe_mode']) ? sanitize_text_field($payment_settings['stripe_mode']) : 'test';
        $this->secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';
        
        // CRITICAL: Check if Stripe is already loaded WITHOUT triggering autoloaders
        // Using autoload=false prevents triggering autoloaders that might load Stripe twice
        $stripe_already_loaded = false;
        
        // Check declared classes first (fastest, no autoload trigger)
        $declared_classes = get_declared_classes();
        foreach ($declared_classes as $class) {
            if (strpos($class, 'Stripe\\') === 0) {
                $stripe_already_loaded = true;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Amadex Stripe Payment: Stripe library already loaded (detected in declared classes: ' . $class . ')');
                }
                break;
            }
        }
        
        // If not found in declared classes, check with autoload=false (safe check)
        if (!$stripe_already_loaded) {
            $stripe_indicators = array(
                '\Stripe\Exception\RateLimitException',
                '\Stripe\Stripe',
                '\Stripe\PaymentIntent',
                '\Stripe\StripeClient',
                '\Stripe\Exception\ApiErrorException'
            );
            
            foreach ($stripe_indicators as $class_name) {
                // Use autoload=false to avoid triggering autoloaders
                if (class_exists($class_name, false)) {
                    $stripe_already_loaded = true;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Amadex Stripe Payment: Stripe class detected (' . $class_name . ') - skipping load_stripe_library() to prevent fatal error');
                    }
                    break;
                }
            }
        }
        
        // CRITICAL: If still not found, check if Stripe can be autoloaded (another plugin may have registered Composer autoload)
        // If autoload=true returns true, Stripe is available - DO NOT load our init.php
        if (!$stripe_already_loaded) {
            $can_autoload = class_exists('\Stripe\Exception\RateLimitException', true) ||
                            class_exists('\Stripe\Stripe', true) ||
                            class_exists('\Stripe\PaymentIntent', true) ||
                            class_exists('\Stripe\StripeClient', true);
            
            if ($can_autoload) {
                $stripe_already_loaded = true;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Amadex Stripe Payment: Stripe available via autoload (another plugin). Using existing Stripe library.');
                }
            }
        }
        
        if ($stripe_already_loaded) {
            // Library is already loaded - DO NOT call load_stripe_library()
            // Calling it will cause "class already declared" fatal error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Stripe Payment: Stripe library already loaded - using existing library');
            }
            // Mark as ready since it's already loaded
            if (!defined('AMADEX_STRIPE_SDK_READY') || !AMADEX_STRIPE_SDK_READY) {
                define('AMADEX_STRIPE_SDK_READY', true);
            }
        } else {
            // Safe to attempt loading (no Stripe classes detected)
            // Load Stripe PHP library (will skip if already loaded by another plugin)
            $this->load_stripe_library();
        }
        
        // Set API key (only if Stripe library is available)
        if (!empty($this->secret_key)) {
            // Check if Stripe class exists and has setApiKey method (use autoload=false to avoid triggering)
            if (class_exists('\Stripe\Stripe', false) && method_exists('\Stripe\Stripe', 'setApiKey')) {
                try {
                    \Stripe\Stripe::setApiKey($this->secret_key);
                    
                    // Log configuration (partial for security)
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Amadex Stripe Payment: Secret Key loaded - Mode: ' . $this->mode . ', Length: ' . strlen($this->secret_key) . ', Starts with: ' . substr($this->secret_key, 0, 10) . '...');
                    }
                } catch (Exception $e) {
                    error_log('Amadex Stripe Payment: Error setting API key - ' . $e->getMessage());
                }
            } else {
                error_log('Amadex Stripe Payment: Stripe class not fully loaded - API key not set');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Stripe Payment: WARNING - Secret Key is empty or not set');
            }
        }
    }
    
   /**
 * Load Stripe PHP SDK safely (single place).
 * This method MUST be the only place Stripe is loaded from in this plugin.
 *
 * Rules:
 * - If Stripe is already present (other plugin), do NOT load ours.
 * - If Stripe is not present, load via composer autoload if available.
 * - Fallback to bundled init.php only if Stripe is not present.
 *
 * @return bool True if Stripe is available (loaded or already present)
 */
private function load_stripe_library() {
    // Static variable to ensure this method only loads Stripe once per request
    static $stripe_loaded = false;
    
    // Already confirmed loaded for this request
    if ($stripe_loaded || (defined('AMADEX_STRIPE_SDK_READY') && AMADEX_STRIPE_SDK_READY)) {
        return true;
    }

    // CRITICAL FIRST CHECK: Check get_declared_classes() for ANY Stripe class
    // This is the most reliable check - catches classes already declared
    $declared_classes = get_declared_classes();
    foreach ($declared_classes as $class) {
        if (strpos($class, 'Stripe\\') === 0 || 
            $class === 'Stripe\Exception\RateLimitException' ||
            strpos($class, 'Stripe\Exception\\') === 0) {
            // Stripe class already declared - DO NOT load our init.php
            $stripe_loaded = true;
            if (!defined('AMADEX_STRIPE_SDK_READY')) {
                define('AMADEX_STRIPE_SDK_READY', true);
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Stripe Payment: Stripe class already declared in get_declared_classes(): ' . $class . '. Using existing Stripe library.');
            }
            return true;
        }
    }

    // Check if Stripe classes already exist (without triggering autoload)
    if (class_exists('\Stripe\Stripe', false) ||
        class_exists('\Stripe\StripeClient', false) ||
        class_exists('\Stripe\Exception\RateLimitException', false) ||
        class_exists('\Stripe\PaymentIntent', false)) {
        $stripe_loaded = true;
        if (!defined('AMADEX_STRIPE_SDK_READY')) {
            define('AMADEX_STRIPE_SDK_READY', true);
        }
        return true;
    }

    // Check if Stripe can be autoloaded (available via Composer autoload)
    $can_autoload = class_exists('\Stripe\Exception\RateLimitException', true) ||
                    class_exists('\Stripe\Stripe', true) ||
                    class_exists('\Stripe\PaymentIntent', true) ||
                    class_exists('\Stripe\StripeClient', true);
    
    if ($can_autoload) {
        $stripe_loaded = true;
        if (!defined('AMADEX_STRIPE_SDK_READY')) {
            define('AMADEX_STRIPE_SDK_READY', true);
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Amadex Stripe Payment: Stripe available via autoload. Using existing Stripe library.');
        }
        return true;
    }

    // 2) If ANY Stripe SDK file is already included, do NOT include our init.php.
    // Another plugin may have registered a composer autoloader; let it load classes.
    foreach (get_included_files() as $f) {
        $lf = strtolower($f);
        if (strpos($lf, 'stripe-php') !== false || strpos($lf, '/stripe/') !== false) {
            // Check with autoload=false first (safe check)
            if (class_exists('\Stripe\StripeClient', false) || class_exists('\Stripe\Stripe', false)) {
                if (!defined('AMADEX_STRIPE_SDK_READY')) {
                    define('AMADEX_STRIPE_SDK_READY', true);
                }
                return true;
            }
            
            // If not found with autoload=false, try autoload=true ONLY if files are already included
            // This is safe because files are already included, we're just triggering autoload
            if (class_exists('\Stripe\StripeClient', true) || class_exists('\Stripe\Stripe', true)) {
                if (!defined('AMADEX_STRIPE_SDK_READY')) {
                    define('AMADEX_STRIPE_SDK_READY', true);
                }
                return true;
            }

            // Stripe files exist but classes still not loadable
            error_log('Amadex Stripe Payment: Stripe files already included by another plugin, but Stripe classes are not loadable. Aborting to avoid class redeclare fatal.');
            return false;
        }
    }

    // 3) Prefer composer autoload inside this plugin (best practice)
    $composer_autoload = AMADEX_PATH . 'vendor/autoload.php';
    if (file_exists($composer_autoload)) {
        require_once $composer_autoload;

        if (class_exists('\Stripe\StripeClient', true) || class_exists('\Stripe\Stripe', true)) {
            $stripe_loaded = true;
            if (!defined('AMADEX_STRIPE_SDK_READY')) {
                define('AMADEX_STRIPE_SDK_READY', true);
            }
            return true;
        }

        error_log('Amadex Stripe Payment: vendor/autoload.php loaded, but Stripe classes still not available.');
        return false;
    }

    // 4) Fallback: bundled Stripe SDK
    $init_path = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/init.php';
    if (file_exists($init_path)) {
        // Check if init.php is already included (absolute path check)
        $init_path_real = realpath($init_path);
        if ($init_path_real) {
            foreach (get_included_files() as $included_file) {
                $included_real = realpath($included_file);
                if ($included_real && $init_path_real === $included_real) {
                    // init.php already included - use existing
                    $stripe_loaded = true;
                    if (!defined('AMADEX_STRIPE_SDK_READY')) {
                        define('AMADEX_STRIPE_SDK_READY', true);
                    }
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Amadex Stripe Payment: init.php already included. Using existing Stripe library.');
                    }
                    return true;
                }
            }
        }
        
        // Final check: Are Stripe classes already declared?
        $declared_final = get_declared_classes();
        foreach ($declared_final as $class) {
            if (strpos($class, 'Stripe\\') === 0) {
                $stripe_loaded = true;
                if (!defined('AMADEX_STRIPE_SDK_READY')) {
                    define('AMADEX_STRIPE_SDK_READY', true);
                }
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Amadex Stripe Payment: Stripe class already declared: ' . $class . '. Using existing Stripe library.');
                }
                return true;
            }
        }

        // One final check before including: Is RateLimitException already declared?
        // This is the class causing the fatal error
        if (class_exists('\Stripe\Exception\RateLimitException', false) || 
            class_exists('Stripe\Exception\RateLimitException', false)) {
            $stripe_loaded = true;
            if (!defined('AMADEX_STRIPE_SDK_READY')) {
                define('AMADEX_STRIPE_SDK_READY', true);
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Stripe Payment: RateLimitException already exists. Using existing Stripe library.');
            }
            return true;
        }

        // Safe to include - use require_once to prevent double-inclusion
        require_once $init_path;
        $stripe_loaded = true;

        if (class_exists('\Stripe\StripeClient', true) || class_exists('\Stripe\Stripe', true)) {
            if (!defined('AMADEX_STRIPE_SDK_READY')) {
                define('AMADEX_STRIPE_SDK_READY', true);
            }
            return true;
        }

        error_log('Amadex Stripe Payment: init.php loaded but Stripe classes still not available.');
        return false;
    }

    error_log('Amadex Stripe Payment: Stripe SDK not found. Install via Composer or place SDK in includes/vendor/stripe/stripe-php/.');
    return false;
}

    
    /**
     * Create PaymentIntent (auth-only, manual capture)
     */
    public function create_payment_intent($payment_data) {
        if (empty($this->secret_key)) {
            error_log('Stripe Error: Secret key is empty or not configured');
            return new WP_Error('no_secret_key', __('Stripe Secret Key is not configured. Please go to Amadex Settings → Payment Settings and enter your Stripe Secret Key.', 'amadex'));
        }
        
        if (!class_exists('\Stripe\Stripe')) {
            error_log('Stripe Error: Stripe PHP library not loaded');
            
            // Try to load library one more time (in case it was installed after page load)
            $this->load_stripe_library();
            
            // Check again
            if (!class_exists('\Stripe\Stripe')) {
                $install_msg = __('Stripe PHP library is not installed. ', 'amadex');
                $install_msg .= __('Please install it via Composer in your plugin directory: "composer require stripe/stripe-php"', 'amadex');
                $install_msg .= __(' Or download from: https://github.com/stripe/stripe-php', 'amadex');
                
                return new WP_Error('no_library', $install_msg);
            }
        }
        
        // Validate amount
        $amount = floatval($payment_data['amount'] ?? 0);
        if ($amount <= 0) {
            error_log('Stripe Error: Invalid amount: ' . $amount);
            return new WP_Error('invalid_amount', __('Invalid payment amount. Please check your booking total.', 'amadex'));
        }
        
        // Note: For manual capture with frontend confirmation, payment_method_id is optional
        // The payment method will be attached during confirmCardPayment on the frontend
        $payment_method_id = sanitize_text_field($payment_data['payment_method_id'] ?? '');
        if (!empty($payment_method_id) && strpos($payment_method_id, 'pm_') !== 0) {
            error_log('Stripe Error: Invalid payment method ID format');
            return new WP_Error('invalid_payment_method', __('Invalid payment method format. Please try again.', 'amadex'));
        }
        
        // Get booking reference for idempotency
        $booking_reference = sanitize_text_field($payment_data['booking_reference'] ?? 'temp-' . time());
        
        // Ensure Stripe API key is set before any API call (handles late library load or other plugins)
        if (class_exists('\Stripe\Stripe', false) && method_exists('\Stripe\Stripe', 'setApiKey')) {
            \Stripe\Stripe::setApiKey($this->secret_key);
        }
        
        // Log request (safe - no card data)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Stripe PaymentIntent Creation Request:');
            error_log('  Amount: ' . $amount);
            error_log('  Currency: USD');
            error_log('  Payment Method ID: ' . substr($payment_method_id, 0, 20) . '...');
            error_log('  Booking Reference: ' . $booking_reference);
            error_log('  Mode: ' . $this->mode);
        }
        
        try {
            // Create PaymentIntent with manual capture (auth-only, like NMI)
            // For manual capture with confirmation on frontend:
            // 1. Don't set payment_method on creation (attach it during confirmation)
            // 2. Set payment_method_types to indicate card payment
            // 3. Set capture_method to 'manual' for auth-only
            // 4. 3DS will be handled automatically during confirmCardPayment on frontend
            $intent_params = array(
                'amount' => round($amount * 100), // Convert to cents
                'currency' => 'usd',
                'payment_method_types' => array('card'), // Specify card payment method
                'capture_method' => 'manual', // Auth-only (no automatic capture)
                'metadata' => array(
                    'booking_reference' => $booking_reference,
                    'source' => 'flytravelay_booking',
                    'mode' => $this->mode,
                    'payment_method_id' => $payment_method_id // Store PM ID in metadata for reference
                )
            );
            
            // Add flight_data to metadata (industry metadata for flight bookings)
            // Note: Stripe supports industry-specific metadata for better authorization rates
            $flight_data = $payment_data['flight_data'] ?? null;
            if ($flight_data && is_array($flight_data) && !empty($flight_data['departure_airport'])) {
                // Add flight information to metadata (always supported by Stripe)
                $intent_params['metadata']['flight_departure'] = $flight_data['departure_airport'] ?? '';
                $intent_params['metadata']['flight_arrival'] = $flight_data['arrival_airport'] ?? '';
                $intent_params['metadata']['flight_carrier'] = $flight_data['carrier_name'] ?? $flight_data['carrier_iata'] ?? '';
                $intent_params['metadata']['passenger_name'] = $flight_data['passenger_name'] ?? '';
                $intent_params['metadata']['ticket_class'] = $flight_data['ticket_class'] ?? 'ECONOMY';
                $intent_params['metadata']['departure_date'] = $flight_data['departure_date'] ?? '';
                $intent_params['metadata']['arrival_date'] = $flight_data['arrival_date'] ?? '';
                $intent_params['metadata']['ticketing_agent'] = $flight_data['ticketing_agent'] ?? 'Travelay';
                
                // Log that flight_data is being added (for debugging)
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Stripe PaymentIntent: Adding flight_data to metadata');
                    error_log('  Departure: ' . ($flight_data['departure_airport'] ?? 'N/A') . ' -> ' . ($flight_data['arrival_airport'] ?? 'N/A'));
                    error_log('  Carrier: ' . ($flight_data['carrier_name'] ?? 'N/A'));
                    error_log('  Passenger: ' . ($flight_data['passenger_name'] ?? 'N/A'));
                }
            }
            
            // Add idempotency key as separate parameter (not in main params)
            $intent = \Stripe\PaymentIntent::create($intent_params, array(
                'idempotency_key' => $booking_reference
            ));
            
            // Log success (safe)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Stripe PaymentIntent Created Successfully:');
                error_log('  Intent ID: ' . $intent->id);
                error_log('  Status: ' . $intent->status);
                error_log('  Amount: ' . ($intent->amount / 100));
                error_log('  Currency: ' . $intent->currency);
            }
            
            return array(
                'success' => true,
                'payment_intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'status' => $intent->status,
                'raw_response' => $intent->toArray()
            );
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'error_type' => $e->getStripeCode(),
                'response_text' => 'Stripe API error: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            error_log('Stripe Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'response_text' => 'Payment processing error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Verify PaymentIntent (already authorized on frontend)
     */
    public function verify_payment_intent($payment_intent_id, $expected_amount) {
        if (empty($this->secret_key)) {
            error_log('Stripe Error: Secret key is empty or not configured');
            return array(
                'success' => false,
                'response_text' => 'Stripe Secret Key is not configured.'
            );
        }
        
        if (!class_exists('\Stripe\Stripe')) {
            error_log('Stripe Error: Stripe PHP library not loaded');
            return array(
                'success' => false,
                'response_text' => 'Stripe PHP library is not installed.'
            );
        }
        
        // Validate PaymentIntent ID format
        if (empty($payment_intent_id) || strpos($payment_intent_id, 'pi_') !== 0) {
            error_log('Stripe Error: Invalid PaymentIntent ID format');
            return array(
                'success' => false,
                'response_text' => 'Invalid PaymentIntent ID format.'
            );
        }
        
        // Ensure Stripe API key is set before any API call
        if (class_exists('\Stripe\Stripe', false) && method_exists('\Stripe\Stripe', 'setApiKey')) {
            \Stripe\Stripe::setApiKey($this->secret_key);
        }
        
        // Log request (safe)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Stripe PaymentIntent Verification Request:');
            error_log('  PaymentIntent ID: ' . $payment_intent_id);
            error_log('  Expected Amount: ' . $expected_amount);
        }
        
        try {
            // Retrieve PaymentIntent from Stripe
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            // Verify status (must be requires_capture for auth-only)
            if (!in_array($intent->status, array('requires_capture', 'succeeded'))) {
                error_log('Stripe Error: PaymentIntent status is not authorized. Status: ' . $intent->status);
                return array(
                    'success' => false,
                    'response_text' => 'Payment Intent status is not authorized: ' . $intent->status,
                    'transaction_id' => $intent->id,
                    'raw_response' => $intent->toArray()
                );
            }
            
            // Verify amount matches (allow small floating point differences)
            $actual_amount = $intent->amount / 100; // Convert from cents
            if (abs($actual_amount - $expected_amount) > 0.01) {
                error_log('Stripe Error: Amount mismatch. Expected: ' . $expected_amount . ', Actual: ' . $actual_amount);
                return array(
                    'success' => false,
                    'response_text' => 'Payment amount mismatch. Expected: ' . $expected_amount . ', Actual: ' . $actual_amount,
                    'transaction_id' => $intent->id,
                    'raw_response' => $intent->toArray()
                );
            }
            
            // Extract card details from latest charge
            $last_charge = null;
            $auth_code = '';
            $card_last4 = '';
            $card_type = '';
            
            if (!empty($intent->charges->data)) {
                $last_charge = $intent->charges->data[0];
                $auth_code = $last_charge->authorization_code ?? '';
                $card_last4 = $last_charge->payment_method_details->card->last4 ?? '';
                $card_type = $last_charge->payment_method_details->card->brand ?? '';
            } elseif (!empty($intent->charges->data)) {
                // Fallback: try to get from payment method if charge not available
                $payment_method = $intent->payment_method;
                if (is_string($payment_method)) {
                    $pm = \Stripe\PaymentMethod::retrieve($payment_method);
                    if (!empty($pm->card)) {
                        $card_last4 = $pm->card->last4 ?? '';
                        $card_type = $pm->card->brand ?? '';
                    }
                }
            }
            
            // Log success (safe)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Stripe PaymentIntent Verified Successfully:');
                error_log('  Intent ID: ' . $intent->id);
                error_log('  Status: ' . $intent->status);
                error_log('  Amount: ' . $actual_amount);
                error_log('  Card Last 4: ' . ($card_last4 ? '****' . $card_last4 : 'N/A'));
                error_log('  Card Type: ' . ($card_type ?: 'N/A'));
            }
            
            return array(
                'success' => true,
                'transaction_id' => $intent->id,
                'auth_code' => $auth_code,
                'card_last4' => $card_last4,
                'card_type' => ucfirst($card_type),
                'avs_response' => '', // Stripe doesn't provide AVS in same format as NMI
                'cvv_response' => '', // Stripe doesn't provide CVV in same format as NMI
                'raw_response' => $intent->toArray()
            );
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe API Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'response_text' => 'Stripe API error: ' . $e->getMessage(),
                'transaction_id' => '',
                'auth_code' => '',
                'avs_response' => '',
                'cvv_response' => '',
                'raw_response' => array('error' => $e->getMessage())
            );
        } catch (Exception $e) {
            error_log('Stripe Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'response_text' => 'Payment verification error: ' . $e->getMessage(),
                'transaction_id' => '',
                'auth_code' => '',
                'avs_response' => '',
                'cvv_response' => '',
                'raw_response' => array('error' => $e->getMessage())
            );
        }
    }
    
    /**
     * Refund a PaymentIntent (full or partial)
     */
    public function refund_payment_intent($payment_intent_id, $amount = null, $reason = null) {
        if (empty($this->secret_key)) {
            error_log('Stripe Error: Secret key is empty or not configured');
            return array(
                'success' => false,
                'response_text' => 'Stripe Secret Key is not configured.'
            );
        }
        
        if (!class_exists('\Stripe\Stripe')) {
            error_log('Stripe Error: Stripe PHP library not loaded');
            return array(
                'success' => false,
                'response_text' => 'Stripe PHP library is not installed.'
            );
        }
        
        // Validate PaymentIntent ID format
        if (empty($payment_intent_id) || strpos($payment_intent_id, 'pi_') !== 0) {
            error_log('Stripe Error: Invalid PaymentIntent ID format for refund');
            return array(
                'success' => false,
                'response_text' => 'Invalid PaymentIntent ID format.'
            );
        }
        
        try {
            // Retrieve PaymentIntent to get charge ID
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            if (empty($intent->charges->data)) {
                error_log('Stripe Error: No charges found for PaymentIntent: ' . $payment_intent_id);
                return array(
                    'success' => false,
                    'response_text' => 'No charge found for this payment intent.'
                );
            }
            
            // Get the charge ID (latest charge)
            $charge_id = $intent->charges->data[0]->id;
            
            // Build refund parameters
            $refund_params = array();
            if ($amount !== null && $amount > 0) {
                // Partial refund (convert to cents)
                $refund_params['amount'] = round($amount * 100);
            }
            // Full refund if amount is null
            
            if ($reason) {
                $refund_params['reason'] = sanitize_text_field($reason); // 'duplicate', 'fraudulent', or 'requested_by_customer'
            }
            
            // Create refund
            $refund = \Stripe\Refund::create(
                array_merge(array('charge' => $charge_id), $refund_params)
            );
            
            // Log success (safe)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Stripe Refund Created Successfully:');
                error_log('  Refund ID: ' . $refund->id);
                error_log('  PaymentIntent ID: ' . $payment_intent_id);
                error_log('  Charge ID: ' . $charge_id);
                error_log('  Amount: ' . ($refund->amount / 100));
                error_log('  Status: ' . $refund->status);
            }
            
            return array(
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100, // Convert from cents
                'status' => $refund->status,
                'raw_response' => $refund->toArray()
            );
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe Refund API Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'response_text' => 'Stripe API error: ' . $e->getMessage(),
                'raw_response' => array('error' => $e->getMessage())
            );
        } catch (Exception $e) {
            error_log('Stripe Refund Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'response_text' => 'Refund processing error: ' . $e->getMessage(),
                'raw_response' => array('error' => $e->getMessage())
            );
        }
    }
    
    /**
     * Create Stripe Checkout Session (Standard Redirect Flow)
     * Follows Stripe PHP documentation: https://docs.stripe.com/payments/accept-a-payment
     * 
     * @param array $session_data {
     *     @type float  $amount          Payment amount
     *     @type string $currency        Currency code (default: 'usd')
     *     @type string $success_url     URL to redirect after successful payment
     *     @type string $cancel_url      URL to redirect if payment is cancelled
     *     @type array  $metadata        Optional metadata to attach to session
     *     @type string $customer_email  Optional customer email to prefill
     * }
     * @return array|WP_Error Response with session_id and url, or WP_Error on failure
     */
    public function create_checkout_session($session_data) {
        if (empty($this->secret_key)) {
            error_log('Stripe Checkout Session Error: Secret key is empty or not configured');
            return new WP_Error('no_secret_key', __('Stripe Secret Key is not configured. Please go to Amadex Settings → Payment Settings and enter your Stripe Secret Key.', 'amadex'));
        }
        
        // Verify Stripe library is loaded
        if (!class_exists('\Stripe\StripeClient', false) && !class_exists('\Stripe\Stripe', false)) {
            // Try to trigger autoload if available
            if (!class_exists('\Stripe\StripeClient', true) && !class_exists('\Stripe\Stripe', true)) {
                error_log('Stripe Checkout Session Error: Stripe PHP library not loaded');
                return new WP_Error('no_library', __('Stripe PHP library is not installed. Please install it via Composer: "composer require stripe/stripe-php"', 'amadex'));
            }
        }
        
        // Validate required parameters
        $amount = floatval($session_data['amount'] ?? 0);
        if ($amount <= 0) {
            error_log('Stripe Checkout Session Error: Invalid amount: ' . $amount);
            return new WP_Error('invalid_amount', __('Invalid payment amount. Please check your booking total.', 'amadex'));
        }
        
        $currency = strtolower(sanitize_text_field($session_data['currency'] ?? 'usd'));
        // Use success_url as-is: Stripe requires literal {CHECKOUT_SESSION_ID} (do not use esc_url_raw - it can strip/encode the placeholder)
        $success_url = isset($session_data['success_url']) ? $session_data['success_url'] : '';
        $cancel_url = esc_url_raw($session_data['cancel_url'] ?? '');
        if (empty($success_url) || empty($cancel_url)) {
            error_log('Stripe Checkout Session Error: Missing success_url or cancel_url');
            return new WP_Error('missing_urls', __('Payment redirect URLs are not configured. Please check your payment page settings.', 'amadex'));
        }
        
        // Prepare line items (required for Checkout Session)
        $line_items = array(
            array(
                'price_data' => array(
                    'currency' => $currency,
                    'product_data' => array(
                        'name' => sanitize_text_field($session_data['product_name'] ?? 'Flight Booking'),
                        'description' => sanitize_text_field($session_data['product_description'] ?? 'Flight booking payment')
                    ),
                    'unit_amount' => round($amount * 100) // Convert to cents
                ),
                'quantity' => 1
            )
        );
        
        // Prepare session parameters (standard Stripe Checkout redirect flow)
        $session_params = array(
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url
        );
        
        // Add metadata if provided
        if (!empty($session_data['metadata']) && is_array($session_data['metadata'])) {
            $session_params['metadata'] = array_map('sanitize_text_field', $session_data['metadata']);
        }
        
        // Add customer email if provided (prefills Checkout form)
        if (!empty($session_data['customer_email']) && is_email($session_data['customer_email'])) {
            $session_params['customer_email'] = sanitize_email($session_data['customer_email']);
        }
        
        // Optional: Set manual capture (auth-only) if needed
        if (isset($session_data['capture_method']) && $session_data['capture_method'] === 'manual') {
            $session_params['payment_intent_data'] = array(
                'capture_method' => 'manual'
            );
        }
        
        // Log request (always log for debugging)
        error_log('Stripe Checkout Session Creation:');
        error_log('  Amount: ' . $amount . ' ' . strtoupper($currency));
        error_log('  Success URL: ' . $success_url);
        error_log('  Cancel URL: ' . $cancel_url);
        error_log('  Metadata count: ' . (isset($session_params['metadata']) ? count($session_params['metadata']) : 0));
        error_log('  Session params keys: ' . implode(', ', array_keys($session_params)));
        
        try {
            error_log('Stripe: Starting create_checkout_session');
            error_log('Stripe: Checking for Stripe classes');
            
            // Verify Stripe library is actually loaded and usable
            $stripe_available = false;
            $stripe_client_available = false;
            $stripe_checkout_available = false;
            
            // Check if Stripe classes exist
            if (class_exists('\Stripe\Stripe', false) || class_exists('\Stripe\Stripe', true)) {
                $stripe_available = true;
                error_log('Stripe: \Stripe\Stripe class available');
            }
            
            if (class_exists('\Stripe\StripeClient', false) || class_exists('\Stripe\StripeClient', true)) {
                $stripe_client_available = true;
                error_log('Stripe: \Stripe\StripeClient class available');
            }
            
            if (class_exists('\Stripe\Checkout\Session', false) || class_exists('\Stripe\Checkout\Session', true)) {
                $stripe_checkout_available = true;
                error_log('Stripe: \Stripe\Checkout\Session class available');
            }
            
            if (!$stripe_available && !$stripe_client_available) {
                error_log('Stripe: ERROR - No Stripe classes available');
                return new WP_Error('stripe_api_unavailable', __('Stripe PHP library is not loaded. Please ensure Stripe library is properly installed.', 'amadex'));
            }
            
            // Set API key first (required for both old and new API)
            if ($stripe_available) {
                try {
                    \Stripe\Stripe::setApiKey($this->secret_key);
                    error_log('Stripe: API key set successfully');
                } catch (\Throwable $e) {
                    error_log('Stripe: ERROR setting API key: ' . $e->getMessage());
                    return new WP_Error('stripe_api_error', __('Failed to set Stripe API key: ', 'amadex') . $e->getMessage());
                }
            }
            
            // Create Checkout Session using StripeClient (recommended) or older API
            $checkout_session = null;
            
            // Try StripeClient first (Stripe PHP SDK 6.0.0+)
            if ($stripe_client_available) {
                try {
                    error_log('Stripe: Attempting to use StripeClient');
                    $stripe = new \Stripe\StripeClient($this->secret_key);
                    $checkout_session = $stripe->checkout->sessions->create($session_params);
                    error_log('Stripe: Successfully created session using StripeClient');
                } catch (\Throwable $e) {
                    error_log('Stripe: StripeClient failed: ' . $e->getMessage());
                    error_log('Stripe: Error type: ' . get_class($e));
                    // Fall through to older API
                }
            }
            
            // Fallback to older API if StripeClient didn't work
            if (!$checkout_session && $stripe_checkout_available) {
                try {
                    error_log('Stripe: Attempting to use older API (Checkout\Session::create)');
                    $checkout_session = \Stripe\Checkout\Session::create($session_params);
                    error_log('Stripe: Successfully created session using older API');
                } catch (\Throwable $e) {
                    error_log('Stripe: Older API also failed: ' . $e->getMessage());
                    error_log('Stripe: Error type: ' . get_class($e));
                }
            }
            
            if (!$checkout_session) {
                error_log('Stripe Checkout Session Error: Failed to create session with both methods');
                error_log('Stripe: StripeClient available: ' . ($stripe_client_available ? 'YES' : 'NO'));
                error_log('Stripe: \Stripe\Checkout\Session available: ' . ($stripe_checkout_available ? 'YES' : 'NO'));
                return new WP_Error('stripe_api_unavailable', __('Failed to create Stripe Checkout Session. Please check your Stripe API keys and ensure Stripe library is properly installed.', 'amadex'));
            }
            
            // Validate response
            if (empty($checkout_session->id)) {
                error_log('Stripe Checkout Session Error: session_id missing from response');
                return new WP_Error('invalid_response', __('Stripe did not return a valid session ID. Please try again.', 'amadex'));
            }
            
            if (empty($checkout_session->url)) {
                error_log('Stripe Checkout Session Error: checkout URL missing from response');
                return new WP_Error('invalid_response', __('Stripe did not return a checkout URL. Please try again.', 'amadex'));
            }
            
            // Log success
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Stripe Checkout Session Created Successfully:');
                error_log('  Session ID: ' . $checkout_session->id);
                error_log('  Checkout URL: ' . $checkout_session->url);
            }
            
            // Return session data for redirect
            return array(
                'success' => true,
                'session_id' => $checkout_session->id,
                'url' => $checkout_session->url
            );
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('Stripe Checkout Session API Error: ' . $e->getMessage());
            return new WP_Error('stripe_api_error', __('Stripe payment error: ', 'amadex') . $e->getMessage(), array(
                'stripe_code' => $e->getStripeCode(),
                'http_status' => $e->getHttpStatus()
            ));
        } catch (\Error $e) {
            $error_msg = $e->getMessage();
            $is_class_error = (strpos($error_msg, 'Cannot declare class') !== false && strpos($error_msg, 'Stripe') !== false);
            
            error_log('Stripe Checkout Session PHP Error: ' . $error_msg);
            return new WP_Error(
                $is_class_error ? 'stripe_class_conflict' : 'php_error',
                $is_class_error 
                    ? __('Stripe library conflict detected. Another plugin has already loaded Stripe.', 'amadex')
                    : __('Payment processing error: ', 'amadex') . $error_msg
            );
        } catch (Exception $e) {
            error_log('Stripe Checkout Session Exception: ' . $e->getMessage());
            return new WP_Error('exception', __('Error creating payment session: ', 'amadex') . $e->getMessage());
        }
    }
}
