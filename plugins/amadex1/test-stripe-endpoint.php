<?php
/**
 * Test Stripe AJAX Endpoint Directly
 * 
 * This script tests the create_elements_session AJAX endpoint
 * Run from browser: https://yoursite.com/wp-content/plugins/amadex/test-stripe-endpoint.php
 * 
 * WARNING: Remove this file in production or add authentication
 */

// Load WordPress
$wp_load = dirname(__FILE__) . '/../../wp-load.php';
if (!file_exists($wp_load)) {
    die('WordPress not found. Please ensure this file is in the plugin directory.');
}

require_once($wp_load);

// Check if user is admin (optional security)
if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

echo "<h1>Stripe AJAX Endpoint Direct Test</h1>";
echo "<pre>";

// Simulate AJAX request
$_POST['action'] = 'amadex_create_elements_session';
$_POST['nonce'] = wp_create_nonce('amadex_nonce');
$_POST['amount'] = '100.00';
$_POST['currency'] = 'usd';
$_POST['booking_reference'] = 'test-' . time();

echo "=== Simulating AJAX Request ===\n\n";
echo "POST data:\n";
echo "  action: " . $_POST['action'] . "\n";
echo "  nonce: " . substr($_POST['nonce'], 0, 10) . "...\n";
echo "  amount: " . $_POST['amount'] . "\n";
echo "  currency: " . $_POST['currency'] . "\n";
echo "  booking_reference: " . $_POST['booking_reference'] . "\n\n";

// Check if AJAX handler exists
echo "=== Checking AJAX Handler ===\n";
if (class_exists('Amadex_Ajax')) {
    echo "✓ Amadex_Ajax class exists\n";
    
    $ajax_handler = new Amadex_Ajax();
    if (method_exists($ajax_handler, 'create_elements_session')) {
        echo "✓ create_elements_session method exists\n\n";
    } else {
        echo "✗ create_elements_session method NOT found\n\n";
        die();
    }
} else {
    echo "✗ Amadex_Ajax class NOT found\n\n";
    die();
}

// Check payment settings
echo "=== Checking Payment Settings ===\n";
$payment_settings = get_option('amadex_payment_settings', array());
$default_gateway = isset($payment_settings['default_card_gateway']) ? $payment_settings['default_card_gateway'] : 'not set';
echo "Default gateway: $default_gateway\n";

if ($default_gateway !== 'stripe') {
    echo "⚠️ WARNING: Default gateway is not 'stripe'. Setting it to 'stripe' for this test...\n";
    $payment_settings['default_card_gateway'] = 'stripe';
    update_option('amadex_payment_settings', $payment_settings);
}

$stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? $payment_settings['stripe_secret_key'] : '';
$stripe_publishable_key = isset($payment_settings['stripe_publishable_key']) ? $payment_settings['stripe_publishable_key'] : '';

if (empty($stripe_secret_key)) {
    echo "✗ Stripe Secret Key is NOT set\n";
    echo "  Please set it in WordPress Admin → Amadex Settings → Payment Settings\n\n";
} else {
    $key_prefix = substr($stripe_secret_key, 0, 7);
    echo "✓ Stripe Secret Key is set (starts with: $key_prefix...)\n";
}

if (empty($stripe_publishable_key)) {
    echo "✗ Stripe Publishable Key is NOT set\n\n";
} else {
    $key_prefix = substr($stripe_publishable_key, 0, 7);
    echo "✓ Stripe Publishable Key is set (starts with: $key_prefix...)\n\n";
}

// Check AMADEX_PATH
echo "=== Checking AMADEX_PATH ===\n";
if (defined('AMADEX_PATH')) {
    echo "✓ AMADEX_PATH is defined: " . AMADEX_PATH . "\n";
} else {
    echo "✗ AMADEX_PATH is NOT defined\n\n";
    die();
}

// Check Stripe library
echo "\n=== Checking Stripe Library ===\n";
$stripe_init = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/init.php';
if (file_exists($stripe_init)) {
    echo "✓ Stripe init.php exists\n";
} else {
    echo "✗ Stripe init.php NOT found at: $stripe_init\n\n";
    die();
}

// Now try to call the function directly (capture output)
echo "\n=== Calling create_elements_session Function ===\n";
echo "Note: This will attempt to create a real Stripe session (if keys are valid)\n";
echo "If keys are invalid, you'll see a Stripe API error\n\n";

// Capture any output
ob_start();

try {
    // Call the function
    $ajax_handler->create_elements_session();
    
    // Get output
    $output = ob_get_clean();
    
    echo "Function executed. Output:\n";
    echo $output;
    
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "Exception caught:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n\n";
    echo "Output before exception:\n";
    echo $output;
} catch (\Error $e) {
    $output = ob_get_clean();
    echo "PHP Error caught:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n\n";
    echo "Output before error:\n";
    echo $output;
} catch (\Throwable $e) {
    $output = ob_get_clean();
    echo "Throwable caught:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Type: " . get_class($e) . "\n\n";
    echo "Output before throwable:\n";
    echo $output;
}

echo "\n=== Test Complete ===\n";
echo "</pre>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check the output above for any errors</li>";
echo "<li>If you see Stripe API errors, verify your API keys are correct</li>";
echo "<li>Check WordPress debug log: wp-content/debug.log</li>";
echo "<li>Look for entries starting with [Amadex]</li>";
echo "</ol>";
