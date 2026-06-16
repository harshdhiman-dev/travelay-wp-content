<?php
/**
 * Test Stripe AJAX Endpoint
 * 
 * This script tests if the create_elements_session AJAX endpoint is working
 * Run this from your browser: https://yoursite.com/wp-content/plugins/amadex/test-stripe-ajax.php
 * 
 * WARNING: This is for testing only. Remove or protect this file in production.
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin (optional security)
if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

echo "<h1>Stripe AJAX Endpoint Test</h1>";
echo "<pre>";

// Test 1: Check if function exists
echo "Test 1: Checking if create_elements_session function exists...\n";
if (class_exists('Amadex_Ajax')) {
    $ajax_handler = new Amadex_Ajax();
    if (method_exists($ajax_handler, 'create_elements_session')) {
        echo "✓ Function exists\n\n";
    } else {
        echo "✗ Function does not exist\n\n";
        die();
    }
} else {
    echo "✗ Amadex_Ajax class not found\n\n";
    die();
}

// Test 2: Check if AJAX action is registered
echo "Test 2: Checking if AJAX action is registered...\n";
global $wp_filter;
$ajax_action = 'wp_ajax_amadex_create_elements_session';
$nopriv_action = 'wp_ajax_nopriv_amadex_create_elements_session';

if (isset($wp_filter[$ajax_action]) || isset($wp_filter[$nopriv_action])) {
    echo "✓ AJAX action is registered\n\n";
} else {
    echo "✗ AJAX action is NOT registered\n";
    echo "Looking for: $ajax_action or $nopriv_action\n\n";
}

// Test 3: Check Stripe library
echo "Test 3: Checking Stripe library...\n";
$stripe_path = plugin_dir_path(__FILE__) . 'includes/vendor/stripe/stripe-php/init.php';
if (file_exists($stripe_path)) {
    echo "✓ Stripe init.php exists: $stripe_path\n";
    try {
        require_once $stripe_path;
        if (class_exists('\Stripe\Stripe')) {
            echo "✓ Stripe library loaded successfully\n";
            if (class_exists('\Stripe\StripeClient')) {
                echo "✓ StripeClient class available\n\n";
            } else {
                echo "✗ StripeClient class NOT found\n\n";
            }
        } else {
            echo "✗ Stripe library failed to load\n\n";
        }
    } catch (Exception $e) {
        echo "✗ Error loading Stripe library: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ Stripe init.php NOT found at: $stripe_path\n\n";
}

// Test 4: Check payment settings
echo "Test 4: Checking payment settings...\n";
$payment_settings = get_option('amadex_payment_settings', array());
$default_gateway = isset($payment_settings['default_card_gateway']) ? $payment_settings['default_card_gateway'] : 'not set';
echo "Default gateway: $default_gateway\n";

$stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? $payment_settings['stripe_secret_key'] : '';
$stripe_publishable_key = isset($payment_settings['stripe_publishable_key']) ? $payment_settings['stripe_publishable_key'] : '';

if (!empty($stripe_secret_key)) {
    $key_prefix = substr($stripe_secret_key, 0, 7);
    echo "✓ Stripe Secret Key is set (starts with: $key_prefix...)\n";
} else {
    echo "✗ Stripe Secret Key is NOT set\n";
}

if (!empty($stripe_publishable_key)) {
    $key_prefix = substr($stripe_publishable_key, 0, 7);
    echo "✓ Stripe Publishable Key is set (starts with: $key_prefix...)\n\n";
} else {
    echo "✗ Stripe Publishable Key is NOT set\n\n";
}

// Test 5: Check AMADEX_PATH constant
echo "Test 5: Checking AMADEX_PATH constant...\n";
if (defined('AMADEX_PATH')) {
    echo "✓ AMADEX_PATH is defined: " . AMADEX_PATH . "\n";
    if (file_exists(AMADEX_PATH)) {
        echo "✓ AMADEX_PATH directory exists\n\n";
    } else {
        echo "✗ AMADEX_PATH directory does NOT exist\n\n";
    }
} else {
    echo "✗ AMADEX_PATH is NOT defined\n\n";
}

// Test 6: Check class-amadex-payment-stripe.php
echo "Test 6: Checking Amadex_Payment_Stripe class file...\n";
if (defined('AMADEX_PATH')) {
    $stripe_class_path = AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
    if (file_exists($stripe_class_path)) {
        echo "✓ File exists: $stripe_class_path\n";
        if (class_exists('Amadex_Payment_Stripe')) {
            echo "✓ Class is already loaded\n\n";
        } else {
            echo "Class is not loaded (will be loaded on demand)\n\n";
        }
    } else {
        echo "✗ File does NOT exist: $stripe_class_path\n\n";
    }
} else {
    echo "Cannot check - AMADEX_PATH not defined\n\n";
}

// Test 7: Simulate AJAX call (without actually calling it)
echo "Test 7: Simulating AJAX call parameters...\n";
$_POST['action'] = 'amadex_create_elements_session';
$_POST['nonce'] = wp_create_nonce('amadex_nonce');
$_POST['amount'] = '100.00';
$_POST['currency'] = 'usd';
$_POST['booking_reference'] = 'test-' . time();

echo "POST data prepared:\n";
echo "  action: " . $_POST['action'] . "\n";
echo "  nonce: " . substr($_POST['nonce'], 0, 10) . "...\n";
echo "  amount: " . $_POST['amount'] . "\n";
echo "  currency: " . $_POST['currency'] . "\n";
echo "  booking_reference: " . $_POST['booking_reference'] . "\n\n";

echo "=== Test Complete ===\n";
echo "</pre>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Check WordPress debug log: wp-content/debug.log</li>";
echo "<li>Check browser console (F12) for AJAX errors</li>";
echo "<li>Verify Stripe API keys are correct in WordPress Admin → Amadex Settings</li>";
echo "<li>Check server error logs</li>";
echo "</ol>";

echo "<h2>To test the actual AJAX endpoint:</h2>";
echo "<p>Open browser console (F12) and run:</p>";
echo "<pre>";
echo "jQuery.ajax({\n";
echo "    url: '" . admin_url('admin-ajax.php') . "',\n";
echo "    type: 'POST',\n";
echo "    data: {\n";
echo "        action: 'amadex_create_elements_session',\n";
echo "        nonce: '" . wp_create_nonce('amadex_nonce') . "',\n";
echo "        amount: '100.00',\n";
echo "        currency: 'usd',\n";
echo "        booking_reference: 'test-" . time() . "'\n";
echo "    },\n";
echo "    dataType: 'json',\n";
echo "    success: function(response) {\n";
echo "        console.log('Success:', response);\n";
echo "    },\n";
echo "    error: function(xhr, status, error) {\n";
echo "        console.error('Error:', xhr.status, xhr.responseText);\n";
echo "    }\n";
echo "});\n";
echo "</pre>";
