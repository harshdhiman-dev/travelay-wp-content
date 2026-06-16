<?php

/**
 * NMI Payment Gateway Integration
 * Handles authorization-only and payment capture for Travelay booking flow
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment Gateway Class
 */
class Amadex_Payment
{

    /**
     * NMI API endpoint
     */
    private $api_url = 'https://secure.networkmerchants.com/api/transact.php';

    /**
     * API credentials
     */
    private $api_key;
    private $sandbox_mode;

    /**
     * Constructor
     */
    public function __construct()
    {
        $payment_settings = get_option('amadex_payment_settings', array());
        // Trim whitespace from keys to avoid issues
        $this->api_key = isset($payment_settings['nmi_api_key']) ? trim($payment_settings['nmi_api_key']) : '';
        $this->sandbox_mode = isset($payment_settings['nmi_sandbox']) ? (bool) $payment_settings['nmi_sandbox'] : true;

        // Use sandbox URL if in test mode
        // Note: NMI uses the same endpoint for both test and production
        // Test mode is controlled by the API key (test keys vs production keys)
        // The URL remains the same: https://secure.networkmerchants.com/api/transact.php

        // Log key configuration (partial for security)
        if (!empty($this->api_key)) {
            error_log('Amadex Payment: API Key loaded - Length: ' . strlen($this->api_key) . ', Starts with: ' . substr($this->api_key, 0, 10) . '...');
        } else {
            error_log('Amadex Payment: WARNING - API Key is empty or not set');
        }
    }

    /**
     * Process authorization-only transaction
     * This validates the card and holds funds without charging
     */
    public function authorize_payment($payment_data)
    {
        if (empty($this->api_key)) {
            error_log('NMI Error: API key is empty or not configured');
            return new WP_Error('no_api_key', __('NMI API Key (Security Key) is not configured. Please go to Amadex Settings → Payment Settings and enter your NMI API Key (Security Key). This is the PRIVATE key from NMI Dashboard → Settings → Security Keys.', 'amadex'));
        }

        // Validate API key format (NMI security keys are typically 32 characters)
        if (strlen(trim($this->api_key)) < 20) {
            error_log('NMI Error: API key appears to be invalid (too short)');
            return new WP_Error('invalid_api_key', __('NMI API Key appears to be invalid. Please check your Security Key in Amadex Settings → Payment Settings.', 'amadex'));
        }

        // Validate amount
        $amount = floatval($payment_data['amount'] ?? 0);
        if ($amount <= 0) {
            error_log('NMI Error: Invalid amount: ' . $amount);
            return new WP_Error('invalid_amount', __('Invalid payment amount. Please check your booking total.', 'amadex'));
        }

        // Trim and validate API key before use
        $api_key = trim($this->api_key);
        if (empty($api_key)) {
            error_log('NMI Error: API key is empty after trim');
            return new WP_Error('no_api_key', __('NMI API Key (Security Key) is not configured or is empty. Please check your Security Key in Amadex Settings → Payment Settings.', 'amadex'));
        }

        // Build request parameters
        $params = array(
            'security_key' => $api_key,
            'type' => 'auth', // Authorization only - no capture
            'amount' => number_format($amount, 2, '.', ''),
        );

        // Currency is optional but recommended
        if (!empty($payment_data['currency'])) {
            $params['currency'] = $payment_data['currency'];
        } else {
            $params['currency'] = 'USD'; // Default to USD
        }

        // Use payment token if available (from Collect.js), otherwise use card details
        if (!empty($payment_data['payment_token'])) {
            // Log token (partial for security)
            $token_preview = substr($payment_data['payment_token'], 0, 20) . '...';
            error_log('NMI: Using payment token: ' . $token_preview);
            error_log('NMI: Token length: ' . strlen($payment_data['payment_token']));

            // Validate token format (NMI tokens are typically alphanumeric with dashes)
            $token = trim($payment_data['payment_token']);
            if (empty($token) || strlen($token) < 10) {
                error_log('NMI Error: Invalid payment token format');
                return array(
                    'success' => false,
                    'response_code' => '3',
                    'response_text' => 'Invalid payment token. Please check your card details and try again.',
                    'transaction_id' => '',
                    'auth_code' => '',
                    'avs_response' => '',
                    'cvv_response' => '',
                    'raw_response' => array('error' => 'Invalid token format')
                );
            }
            $token = sanitize_text_field($payment_data['payment_token']);
            if (empty($token) || strlen($token) < 10) {
                error_log('NMI Error: Payment token appears to be invalid (too short or empty)');
                return array(
                    'success' => false,
                    'response_code' => '3',
                    'response_text' => 'Invalid payment token format',
                    'transaction_id' => '',
                    'auth_code' => '',
                    'avs_response' => '',
                    'cvv_response' => '',
                    'raw_response' => array('error' => 'Invalid token format')
                );
            }

            $params['payment_token'] = $token;

            // IMPORTANT: When using payment_token, do NOT send card details (ccnumber, ccexp, cvv)
            // NMI will reject the request if both payment_token and card details are sent
            // The token already contains all card information securely

            // When using payment_token, we still need to send billing information for AVS
            // But we should NOT send avscheck or cvvcheck as those are handled by the token

            // Note: NMI requires billing information even when using payment_token
            // The token contains card data, but billing info is needed for AVS and fraud checks
        } else {
            // Fallback to card details if token not available
            error_log('NMI: No payment token, using card details');

            // Validate card details are present
            if (empty($payment_data['card_number']) || empty($payment_data['card_expiry'])) {
                error_log('NMI Error: Card details incomplete when token not available');
                return array(
                    'success' => false,
                    'response_code' => '3',
                    'response_text' => 'Card details are incomplete. Please check your card information.',
                    'transaction_id' => '',
                    'auth_code' => '',
                    'avs_response' => '',
                    'cvv_response' => '',
                    'raw_response' => array()
                );
            }

            $params['ccnumber'] = sanitize_text_field($payment_data['card_number']);
            $params['ccexp'] = sanitize_text_field($payment_data['card_expiry']); // Format: MMYY
            $params['cvv'] = sanitize_text_field($payment_data['card_cvv'] ?? '');

            // AVS and CVV validation only when using card details
            $params['avscheck'] = 'yes';
            $params['cvvcheck'] = 'yes';
        }

        // Cardholder info - REQUIRED for NMI
        $params['first_name'] = sanitize_text_field($payment_data['billing']['first_name'] ?? '');
        $params['last_name'] = sanitize_text_field($payment_data['billing']['last_name'] ?? '');
        $params['address1'] = sanitize_text_field($payment_data['billing']['address1'] ?? '');
        $params['address2'] = sanitize_text_field($payment_data['billing']['address2'] ?? '');
        $params['city'] = sanitize_text_field($payment_data['billing']['city'] ?? '');
        $params['state'] = sanitize_text_field($payment_data['billing']['state'] ?? '');
        $params['zip'] = sanitize_text_field($payment_data['billing']['postal'] ?? '');
        $params['country'] = sanitize_text_field($payment_data['billing']['country'] ?? 'US');
        $params['phone'] = sanitize_text_field($payment_data['contact']['phone'] ?? '');
        $params['email'] = sanitize_email($payment_data['contact']['email'] ?? '');

        // Validate required billing fields when using payment token
        if (!empty($params['payment_token'])) {
            if (empty($params['first_name']) || empty($params['last_name'])) {
                error_log('NMI Error: Missing required billing name fields when using payment token');
                return array(
                    'success' => false,
                    'response_code' => '3',
                    'response_text' => 'Billing information is incomplete. First name and last name are required.',
                    'transaction_id' => '',
                    'auth_code' => '',
                    'avs_response' => '',
                    'cvv_response' => '',
                    'raw_response' => array('error' => 'Missing billing name')
                );
            }
        }

// Additional security
        $params['ipaddress'] = $this->get_client_ip();

        // 3DS fields — pass through if provided
        if (!empty($payment_data['cavv'])) {
            $params['cavv'] = sanitize_text_field($payment_data['cavv']);
        }
        if (!empty($payment_data['xid'])) {
            $params['xid'] = sanitize_text_field($payment_data['xid']);
        }
        if (!empty($payment_data['eci'])) {
            $params['eci'] = sanitize_text_field($payment_data['eci']);
        }
        if (!empty($payment_data['cardholder_auth'])) {
            $params['cardholder_auth'] = sanitize_text_field($payment_data['cardholder_auth']);
        }
        if (!empty($payment_data['three_ds_version'])) {
            $params['three_ds_version'] = sanitize_text_field($payment_data['three_ds_version']);
        }
        if (!empty($payment_data['directory_server_id'])) {
            $params['directory_server_id'] = sanitize_text_field($payment_data['directory_server_id']);
        }

        // Order details - include booking reference for NMI portal visibility
        $order_id = sanitize_text_field($payment_data['order_id'] ?? '');
        $booking_ref = sanitize_text_field($payment_data['booking_reference'] ?? '');
        $params['orderid'] = !empty($booking_ref) ? $booking_ref : $order_id;

        // Build comprehensive order description for NMI portal
        $description = sanitize_text_field($payment_data['description'] ?? 'Flight Booking');
        if (!empty($booking_ref)) {
            $description = 'Flight Booking - ' . $booking_ref;
        }
        if (!empty($payment_data['flight_summary'])) {
            $description .= ' - ' . sanitize_text_field($payment_data['flight_summary']);
        }
        $params['orderdescription'] = substr($description, 0, 255); // NMI limit

        // Log request details (without sensitive data)
        error_log('NMI Auth Request:');
        error_log('  Type: ' . $params['type']);
        error_log('  Amount: ' . $params['amount']);
        error_log('  Currency: ' . $params['currency']);
        error_log('  Has Payment Token: ' . (!empty($params['payment_token']) ? 'Yes (' . substr($params['payment_token'], 0, 20) . '...)' : 'No'));
        error_log('  Order ID: ' . ($params['orderid'] ?? 'N/A'));
        error_log('  Billing Name: ' . ($params['first_name'] ?? '') . ' ' . ($params['last_name'] ?? ''));
        error_log('  Billing Address: ' . ($params['address1'] ?? '') . ', ' . ($params['city'] ?? '') . ', ' . ($params['state'] ?? '') . ' ' . ($params['zip'] ?? ''));
        error_log('  Billing Country: ' . ($params['country'] ?? 'N/A'));
        error_log('  Email: ' . ($params['email'] ?? 'N/A'));
        error_log('  Phone: ' . ($params['phone'] ?? 'N/A'));
        error_log('  API Key: ' . (empty($this->api_key) ? 'MISSING' : substr($this->api_key, 0, 10) . '...'));
        error_log('  Sandbox Mode: ' . ($this->sandbox_mode ? 'Yes' : 'No'));

        // Make API request to NMI
        // Note: NMI uses form-urlencoded format, not JSON
        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        ));

        if (is_wp_error($response)) {
            error_log('NMI API Request Error: ' . $response->get_error_message());
            return new WP_Error('api_error', $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        error_log('NMI API Response Code: ' . $response_code);
        error_log('NMI API Response Body: ' . $body);

        // Check if response is empty or invalid
        if (empty($body)) {
            error_log('NMI Error: Empty response from API');
            return array(
                'success' => false,
                'response_code' => '3',
                'response_text' => 'Empty response from payment gateway. Please check your NMI API key configuration.',
                'transaction_id' => '',
                'auth_code' => '',
                'avs_response' => '',
                'cvv_response' => '',
                'raw_response' => array('error' => 'Empty response')
            );
        }

        $result = $this->parse_response($body);

        // Log transaction result
        error_log('NMI Auth Response Parsed:');
        error_log('  Success: ' . ($result['success'] ? 'Yes' : 'No'));
        error_log('  Response Code: ' . ($result['response_code'] ?? 'N/A'));
        error_log('  Response Text: ' . ($result['response_text'] ?? 'N/A'));
        error_log('  Transaction ID: ' . ($result['transaction_id'] ?? 'N/A'));

        // Check for specific NMI error codes and provide helpful messages
        if (!$result['success']) {
            $response_code = $result['response_code'] ?? '';
            $raw_response_code = $result['raw_response']['response_code'] ?? '';
            $response_text = strtolower($result['response_text'] ?? '');

            error_log('NMI Error Details:');
            error_log('  Response Code: ' . $response_code);
            error_log('  Raw Response Code: ' . $raw_response_code);
            error_log('  Response Text: ' . $result['response_text']);
            error_log('  Transaction ID: ' . ($result['transaction_id'] ?? 'N/A'));

            // Check for explicit API key authentication errors first
            if (
                strpos($response_text, 'api key') !== false ||
                strpos($response_text, 'security key') !== false ||
                strpos($response_text, 'authentication') !== false ||
                strpos($response_text, 'invalid credentials') !== false ||
                strpos($response_text, 'api key not found') !== false
            ) {
                error_log('NMI Error: API key authentication failed');
                $result['response_text'] = 'NMI API key (Security Key) is invalid or not found. Please check your Security Key in Amadex Settings → Payment Settings. Make sure you are using the PRIVATE Security Key (API Key) from NMI Dashboard → Settings → Security Keys → Private Security Keys → API Key.';
            } elseif ($raw_response_code == '300') {
                // Response code 300 specifically indicates a configuration issue (often key mismatch)
                error_log('NMI Error: Transaction declined with code 300 - Configuration issue');
                error_log('  Possible causes:');
                error_log('  1. Tokenization Key and Security Key from different accounts');
                error_log('  2. Tokenization Key and Security Key from different environments');
                error_log('  3. Payment token invalid or expired');
                error_log('  4. Missing or invalid billing information');

                // Provide detailed error message for code 300
                $result['response_text'] = 'Payment declined (Code 300 - Configuration Error). This usually indicates a key mismatch. Please verify: 1) Your Tokenization Key and Security Key (API key) are from the same NMI account, 2) If using FLYTRAVELAY Tokenization Key, use FLYTRAVELAY API Key (not Default Cart Key), 3) Both keys are from the same environment (test/production). Check your keys in Amadex Settings → Payment Settings.';
            } elseif ($response_code == '2' && strpos($response_text, 'declined') !== false) {
                // Generic decline - likely card issue, not configuration
                error_log('NMI Error: Transaction declined by bank or gateway');
                $result['response_text'] = 'Payment declined. Please check: 1) Your card details are correct, 2) Sufficient funds available, 3) Card is not expired, 4) Billing address matches card address. If the problem persists, try a different card.';
            }
        }

        error_log('  Full Response: ' . print_r($result, true));

        return $result;
    }

    /**
     * Step 1 of NMI Three Step Redirect — initiates 3DS authentication
     * Returns form_url to redirect the user to for 3DS challenge
     */
    public function initiate_three_step_redirect($payment_data, $redirect_url)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API Key is not configured.', 'amadex'));
        }

        $amount = floatval($payment_data['amount'] ?? 0);
        if ($amount <= 0) {
            return new WP_Error('invalid_amount', __('Invalid payment amount.', 'amadex'));
        }

        $three_step_url = $this->sandbox_mode
            ? 'https://sandbox.nmi.com/api/v2/three-step'
            : 'https://secure.nmi.com/api/v2/three-step';

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<auth>
    <api-key>' . esc_xml(trim($this->api_key)) . '</api-key>
    <redirect-url>' . esc_url($redirect_url) . '</redirect-url>
    <amount>' . number_format($amount, 2, '.', '') . '</amount>
    <ip-address>' . esc_xml($this->get_client_ip()) . '</ip-address>
    <currency>' . esc_xml($payment_data['currency'] ?? 'USD') . '</currency>
    <order-id>' . esc_xml($payment_data['booking_reference'] ?? '') . '</order-id>
    <order-description>' . esc_xml(substr($payment_data['description'] ?? 'Flight Booking', 0, 255)) . '</order-description>
    <customer-receipt>true</customer-receipt>
    <billing>
        <first-name>' . esc_xml($payment_data['billing']['first_name'] ?? '') . '</first-name>
        <last-name>' . esc_xml($payment_data['billing']['last_name'] ?? '') . '</last-name>
        <address1>' . esc_xml($payment_data['billing']['address1'] ?? '') . '</address1>
        <city>' . esc_xml($payment_data['billing']['city'] ?? '') . '</city>
        <state>' . esc_xml($payment_data['billing']['state'] ?? '') . '</state>
        <postal>' . esc_xml($payment_data['billing']['postal'] ?? '') . '</postal>
        <country>' . esc_xml($payment_data['billing']['country'] ?? 'US') . '</country>
        <phone>' . esc_xml($payment_data['contact']['phone'] ?? '') . '</phone>
        <email>' . esc_xml($payment_data['contact']['email'] ?? '') . '</email>
    </billing>
</auth>';

        error_log('NMI Three Step: Initiating Step 1 for booking ' . ($payment_data['booking_reference'] ?? ''));

        $response = wp_remote_post($three_step_url, array(
            'body'    => $xml,
            'timeout' => 30,
            'headers' => array('Content-Type' => 'text/xml'),
        ));

        if (is_wp_error($response)) {
            error_log('NMI Three Step Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('NMI Three Step Step 1 Response: ' . $body);

        $xml_response = simplexml_load_string($body);
        if (!$xml_response) {
            return new WP_Error('parse_error', __('Invalid response from NMI Three Step API.', 'amadex'));
        }

        $result_code = (string) $xml_response->{'result-code'};
        $form_url    = (string) $xml_response->{'form-url'};

        if ($result_code !== '1' || empty($form_url)) {
            $error_text = (string) ($xml_response->{'result-text'} ?? 'Three Step initiation failed.');
            error_log('NMI Three Step Failed: code=' . $result_code . ' text=' . $error_text);
            return new WP_Error('three_step_error', $error_text);
        }

        return array(
            'success'  => true,
            'form_url' => $form_url,
            'token_id' => (string) ($xml_response->{'token-id'} ?? ''),
        );
    }

    /**
     * Step 3 of NMI Three Step Redirect — completes auth after user returns from 3DS
     */
    public function complete_three_step_redirect($token_id)
    {
        if (empty($this->api_key) || empty($token_id)) {
            return new WP_Error('missing_params', __('API key or token-id missing.', 'amadex'));
        }

$three_step_url = 'https://secure.nmi.com/api/v2/three-step';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<complete-action>
    <api-key>' . esc_xml(trim($this->api_key)) . '</api-key>
    <token-id>' . esc_xml($token_id) . '</token-id>
</complete-action>';

        error_log('NMI Three Step: Completing Step 3 with token-id=' . substr($token_id, 0, 20) . '...');

        $response = wp_remote_post($three_step_url, array(
            'body'    => $xml,
            'timeout' => 30,
            'headers' => array('Content-Type' => 'text/xml'),
        ));

        if (is_wp_error($response)) {
            error_log('NMI Three Step Step 3 Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('NMI Three Step Step 3 Response: ' . $body);

        $xml_response = simplexml_load_string($body);
        if (!$xml_response) {
            return new WP_Error('parse_error', __('Invalid response from NMI Three Step completion.', 'amadex'));
        }

        $result_code = (string) $xml_response->{'result-code'};
        $success     = ($result_code === '1' || $result_code === '100');

        return array(
            'success'        => $success,
            'response_code'  => $result_code,
            'response_text'  => (string) ($xml_response->{'result-text'} ?? ''),
            'transaction_id' => (string) ($xml_response->{'transaction-id'} ?? ''),
            'auth_code'      => (string) ($xml_response->{'authorization-code'} ?? ''),
            'avs_response'   => (string) ($xml_response->{'avs-result'} ?? ''),
            'cvv_response'   => (string) ($xml_response->{'cvv-result'} ?? ''),
            'raw_response'   => json_decode(json_encode($xml_response), true),
        );
    }

    /**
     * Capture previously authorized transaction
     * This is called by agents to actually charge the card
     */
    public function capture_payment($transaction_id, $amount = null)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API key not configured', 'amadex'));
        }

        $params = array(
            'security_key' => $this->api_key,
            'type' => 'capture',
            'transactionid' => sanitize_text_field($transaction_id)
        );

        // Optional: Capture partial amount
        if ($amount !== null) {
            $params['amount'] = number_format(floatval($amount), 2, '.', '');
        }

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = $this->parse_response($body);

        // Log transaction
        error_log('NMI Capture Response: ' . print_r($result, true));

        return $result;
    }

    /**
     * Void authorization
     * Cancel the authorization before capture
     */
    public function void_payment($transaction_id)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API key not configured', 'amadex'));
        }

        $params = array(
            'security_key' => $this->api_key,
            'type' => 'void',
            'transactionid' => sanitize_text_field($transaction_id)
        );

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = $this->parse_response($body);

        return $result;
    }

    /**
     * Refund captured transaction
     */
    public function refund_payment($transaction_id, $amount = null)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API key not configured', 'amadex'));
        }

        $params = array(
            'security_key' => $this->api_key,
            'type' => 'refund',
            'transactionid' => sanitize_text_field($transaction_id)
        );

        // Optional: Refund partial amount
        if ($amount !== null) {
            $params['amount'] = number_format(floatval($amount), 2, '.', '');
        }

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = $this->parse_response($body);

        return $result;
    }

    /**
     * Process direct sale (auth + capture in one step)
     * Used for agent-initiated phone sales
     */
    public function process_sale($payment_data)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API key not configured', 'amadex'));
        }

        $params = array(
            'security_key' => $this->api_key,
            'type' => 'sale', // Auth + Capture
            'ccnumber' => sanitize_text_field($payment_data['card_number']),
            'ccexp' => sanitize_text_field($payment_data['card_expiry']),
            'cvv' => sanitize_text_field($payment_data['card_cvv']),
            'amount' => number_format(floatval($payment_data['amount']), 2, '.', ''),
            'currency' => $payment_data['currency'] ?? 'USD',

            // Cardholder info
            'first_name' => sanitize_text_field($payment_data['billing']['first_name'] ?? ''),
            'last_name' => sanitize_text_field($payment_data['billing']['last_name'] ?? ''),
            'address1' => sanitize_text_field($payment_data['billing']['address1'] ?? ''),
            'city' => sanitize_text_field($payment_data['billing']['city'] ?? ''),
            'state' => sanitize_text_field($payment_data['billing']['state'] ?? ''),
            'zip' => sanitize_text_field($payment_data['billing']['postal'] ?? ''),
            'country' => sanitize_text_field($payment_data['billing']['country'] ?? 'US'),
            'phone' => sanitize_text_field($payment_data['contact']['phone'] ?? ''),
            'email' => sanitize_email($payment_data['contact']['email'] ?? ''),

            'ipaddress' => $this->get_client_ip(),
            'orderid' => sanitize_text_field($payment_data['order_id'] ?? ''),
            'orderdescription' => sanitize_text_field($payment_data['description'] ?? 'Flight Booking')
        );

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = $this->parse_response($body);

        return $result;
    }

    /**
     * Parse NMI response
     */
    private function parse_response($response_string)
    {
        parse_str($response_string, $response);

        $result = array(
            'success' => false,
            'response_code' => $response['response'] ?? '3',
            'response_text' => $response['responsetext'] ?? 'Unknown error',
            'transaction_id' => $response['transactionid'] ?? '',
            'auth_code' => $response['authcode'] ?? '',
            'avs_response' => $response['avsresponse'] ?? '',
            'cvv_response' => $response['cvvresponse'] ?? '',
            'order_id' => $response['orderid'] ?? '',
            'raw_response' => $response
        );

        // Response codes: 1 = Approved, 2 = Declined, 3 = Error
        if ($result['response_code'] == '1') {
            $result['success'] = true;
        }

        // Additional card info if available
        if (isset($response['cc_number'])) {
            $result['card_last4'] = substr($response['cc_number'], -4);
        }
        if (isset($response['cc_type'])) {
            $result['card_type'] = $response['cc_type'];
        }

        return $result;
    }

    /**
     * Validate card number using Luhn algorithm
     */
    public function validate_card_number($card_number)
    {
        $card_number = preg_replace('/\s+/', '', $card_number);

        if (!ctype_digit($card_number)) {
            return false;
        }

        $sum = 0;
        $length = strlen($card_number);

        for ($i = 0; $i < $length; $i++) {
            $digit = intval($card_number[$length - $i - 1]);

            if ($i % 2 == 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return ($sum % 10 == 0);
    }

    /**
     * Get card type from number
     */
    public function get_card_type($card_number)
    {
        $card_number = preg_replace('/\s+/', '', $card_number);

        $patterns = array(
            'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'mastercard' => '/^5[1-5][0-9]{14}$/',
            'amex' => '/^3[47][0-9]{13}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/'
        );

        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $card_number)) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * Get client IP address
     */
    private function get_client_ip()
    {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Query transaction details from NMI
     * Useful for verifying authorization status in NMI portal
     */
    public function query_transaction($transaction_id)
    {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('NMI API key not configured', 'amadex'));
        }

        $params = array(
            'security_key' => $this->api_key,
            'type' => 'query',
            'transactionid' => sanitize_text_field($transaction_id)
        );

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $result = $this->parse_response($body);

        return $result;
    }

    /**
     * Test payment gateway connection
     */
    public function test_connection()
    {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key not configured'
            );
        }

        // Test with a minimal request
        $params = array(
            'security_key' => $this->api_key,
            'type' => 'validate'
        );

        $response = wp_remote_post($this->api_url, array(
            'body' => $params,
            'timeout' => 10,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code == 200) {
            return array(
                'success' => true,
                'message' => 'NMI connection successful'
            );
        }

        return array(
            'success' => false,
            'message' => 'Connection failed with code: ' . $code
        );
    }
}
