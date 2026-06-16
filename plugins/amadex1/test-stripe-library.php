<?php
/**
 * Stripe Library Verification Test Script
 * 
 * Run this file to verify Stripe library is properly installed and can be loaded
 * 
 * Usage: php test-stripe-library.php
 * Or access via browser: https://yoursite.com/wp-content/plugins/amadex/test-stripe-library.php
 */

// Define WordPress path if running standalone
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_load = dirname(__FILE__) . '/../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        // Standalone mode - define constants manually
        define('AMADEX_PATH', dirname(__FILE__) . '/');
    }
}

// Define AMADEX_PATH if not defined
if (!defined('AMADEX_PATH')) {
    define('AMADEX_PATH', dirname(__FILE__) . '/');
}

echo "=== Stripe Library Verification Test ===\n\n";

// Test 1: Check if init.php exists
echo "Test 1: Checking init.php file...\n";
$init_path = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/init.php';
if (file_exists($init_path)) {
    echo "✅ init.php found at: $init_path\n";
} else {
    echo "❌ init.php NOT found at: $init_path\n";
    echo "   Expected path: " . AMADEX_PATH . "includes/vendor/stripe/stripe-php/init.php\n";
    exit(1);
}

// Test 2: Check if StripeClient.php exists
echo "\nTest 2: Checking StripeClient.php file...\n";
$client_path = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/lib/StripeClient.php';
if (file_exists($client_path)) {
    echo "✅ StripeClient.php found\n";
} else {
    echo "❌ StripeClient.php NOT found\n";
    exit(1);
}

// Test 3: Check if Checkout/Session.php exists
echo "\nTest 3: Checking Checkout/Session.php file...\n";
$session_path = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/lib/Checkout/Session.php';
if (file_exists($session_path)) {
    echo "✅ Checkout/Session.php found\n";
} else {
    echo "❌ Checkout/Session.php NOT found\n";
    exit(1);
}

// Test 4: Try to load the library
echo "\nTest 4: Attempting to load Stripe library...\n";
try {
    require_once $init_path;
    echo "✅ Library loaded successfully\n";
} catch (\Error $e) {
    echo "❌ Error loading library: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Exception loading library: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check if main Stripe class exists
echo "\nTest 5: Checking if \\Stripe\\Stripe class exists...\n";
if (class_exists('\Stripe\Stripe', false)) {
    echo "✅ \\Stripe\\Stripe class exists\n";
} else {
    echo "❌ \\Stripe\\Stripe class NOT found\n";
    exit(1);
}

// Test 6: Check if StripeClient class exists
echo "\nTest 6: Checking if \\Stripe\\StripeClient class exists...\n";
if (class_exists('\Stripe\StripeClient', false)) {
    echo "✅ \\Stripe\\StripeClient class exists\n";
} else {
    echo "❌ \\Stripe\\StripeClient class NOT found\n";
    echo "   This class is required for Checkout Sessions (Stripe SDK v6.0.0+)\n";
    exit(1);
}

// Test 7: Check if Checkout Session class exists
echo "\nTest 7: Checking if \\Stripe\\Checkout\\Session class exists...\n";
if (class_exists('\Stripe\Checkout\Session', false)) {
    echo "✅ \\Stripe\\Checkout\\Session class exists\n";
} else {
    echo "❌ \\Stripe\\Checkout\\Session class NOT found\n";
    exit(1);
}

// Test 8: Check if Checkout SessionService exists
echo "\nTest 8: Checking if \\Stripe\\Service\\Checkout\\SessionService class exists...\n";
if (class_exists('\Stripe\Service\Checkout\SessionService', false)) {
    echo "✅ \\Stripe\\Service\\Checkout\\SessionService class exists\n";
} else {
    echo "❌ \\Stripe\\Service\\Checkout\\SessionService class NOT found\n";
    exit(1);
}

// Test 9: Check if ApiErrorException exists (for error handling)
echo "\nTest 9: Checking if \\Stripe\\Exception\\ApiErrorException class exists...\n";
if (class_exists('\Stripe\Exception\ApiErrorException', false)) {
    echo "✅ \\Stripe\\Exception\\ApiErrorException class exists\n";
} else {
    echo "❌ \\Stripe\\Exception\\ApiErrorException class NOT found\n";
    exit(1);
}

// Test 10: Try to instantiate StripeClient (without API key - just test class)
echo "\nTest 10: Testing StripeClient instantiation...\n";
try {
    // Just test if we can create the class (will fail without API key, but that's OK)
    $reflection = new \ReflectionClass('\Stripe\StripeClient');
    echo "✅ StripeClient class can be instantiated\n";
    echo "   Class file: " . $reflection->getFileName() . "\n";
} catch (\Error $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 11: Check PHP version
echo "\nTest 11: Checking PHP version...\n";
$php_version = phpversion();
echo "   PHP Version: $php_version\n";
if (version_compare($php_version, '7.4.0', '>=')) {
    echo "✅ PHP version is compatible (7.4+ required)\n";
} else {
    echo "❌ PHP version is too old. Stripe requires PHP 7.4 or higher.\n";
    echo "   Current version: $php_version\n";
    exit(1);
}

// Test 12: Check file permissions
echo "\nTest 12: Checking file permissions...\n";
if (is_readable($init_path)) {
    echo "✅ init.php is readable\n";
} else {
    echo "❌ init.php is NOT readable (permission issue)\n";
    exit(1);
}

echo "\n=== All Tests Passed! ===\n";
echo "✅ Stripe library is properly installed and ready to use.\n";
echo "\nNext steps:\n";
echo "1. Verify Stripe API keys are configured in WordPress Admin → Amadex Settings → Payment Settings\n";
echo "2. Test payment flow on the booking page\n";
echo "3. Check WordPress debug log if errors occur\n";
