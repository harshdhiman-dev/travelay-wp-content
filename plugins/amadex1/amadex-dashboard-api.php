<?php
/**
 * Plugin Name: Amadex Dashboard API
 * Plugin URI: https://www.flytravelay.com
 * Description: REST API endpoints for Amadex External Dashboard
 * Version: 1.0.0
 * Author: Travelay
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom REST API routes for Amadex Dashboard
 */
add_action('rest_api_init', function() {
    // Register bookings endpoint
    register_rest_route('amadex/v1', '/bookings', array(
        'methods' => 'GET',
        'callback' => 'amadex_get_bookings',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Register single booking endpoint
    register_rest_route('amadex/v1', '/bookings/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'amadex_get_booking',
        'permission_callback' => 'amadex_api_permission_check',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
    
    // Register leads endpoint
    register_rest_route('amadex/v1', '/leads', array(
        'methods' => 'GET',
        'callback' => 'amadex_get_leads',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Get single lead endpoint
    register_rest_route('amadex/v1', '/leads/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'amadex_get_lead',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Update lead endpoint
    register_rest_route('amadex/v1', '/leads/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'amadex_update_lead',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Assign agent to lead endpoint
    register_rest_route('amadex/v1', '/leads/(?P<id>\d+)/assign-agent', array(
        'methods' => 'POST',
        'callback' => 'amadex_assign_agent_to_lead',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Register stats endpoint
    register_rest_route('amadex/v1', '/stats', array(
        'methods' => 'GET',
        'callback' => 'amadex_get_stats',
        'permission_callback' => 'amadex_api_permission_check',
    ));
    
    // Update booking endpoint
    register_rest_route('amadex/v1', '/bookings/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'amadex_update_booking',
        'permission_callback' => 'amadex_api_permission_check',
    ));

    // MoonPay Commerce (Hel.io) Pay Link webhook – Hel.io POSTs here on CREATED (payment success).
    register_rest_route('amadex/v1', '/helio-webhook', array(
        'methods' => 'POST',
        'callback' => 'amadex_helio_webhook',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Permission check (add your authentication here)
 * For now, allowing access - you should add proper authentication
 */
function amadex_api_permission_check() {
    // TODO: Add API key or token authentication
    // For security, implement proper authentication
    // Example: Check API key from request
    // $api_key = $_GET['api_key'] ?? '';
    // return $api_key === 'your-secret-api-key';
    return true; // Change this to implement proper auth
}

/**
 * Get bookings
 * Uses only wp_amadex_bookings (no JOIN) so it works with core Amadex schema.
 */
function amadex_get_bookings($request) {
    global $wpdb;

    $status = $request->get_param('status');
    $channel = $request->get_param('channel');
    $limit = max(1, min(100, intval($request->get_param('limit') ?: 50)));
    $offset = max(0, intval($request->get_param('offset') ?: 0));

    $table = $wpdb->prefix . 'amadex_bookings';

    $where = array('1=1');
    $params = array();

    if (!empty($status)) {
        $where[] = 'status = %s';
        $params[] = $status;
    }

    if (!empty($channel)) {
        $where[] = 'booking_channel = %s';
        $params[] = $channel;
    }

    $where_clause = implode(' AND ', $where);

    // Main query: only from bookings table (no JOIN – core table has no assigned_agent_id)
    $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $params[] = $limit;
    $params[] = $offset;

    $prepared_sql = $wpdb->prepare($sql, array_values($params));
    $bookings = $wpdb->get_results($prepared_sql, ARRAY_A);

    if (!is_array($bookings)) {
        $bookings = array();
    }

    foreach ($bookings as &$booking) {
        if (isset($booking['flight_data']) && is_string($booking['flight_data'])) {
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
        }
        $booking['agent_name'] = null;
    }
    unset($booking);

    // Total count (same WHERE, no limit/offset)
    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
    $count_params = array_slice($params, 0, -2);
    if (!empty($count_params)) {
        $count_sql = $wpdb->prepare($count_sql, array_values($count_params));
    }
    $total = $wpdb->get_var($count_sql);

    return array(
        'success' => true,
        'data' => $bookings,
        'total' => intval($total),
    );
}

/**
 * Get single booking
 */
function amadex_get_booking($request) {
    global $wpdb;
    
    $id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'amadex_bookings';
    $passengers_table = $wpdb->prefix . 'amadex_passengers';
    $payments_table = $wpdb->prefix . 'amadex_payments';
    
    $booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
    
    if (!$booking) {
        return new WP_Error('not_found', 'Booking not found', array('status' => 404));
    }
    
    // Decode flight data
    if (isset($booking['flight_data'])) {
        $booking['flight_data'] = json_decode($booking['flight_data'], true);
    }
    
    // Get passengers
    $booking['passengers'] = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$passengers_table} WHERE booking_id = %d", $id),
        ARRAY_A
    );
    
    // Get payment
    $booking['payment'] = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$payments_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1", $id),
        ARRAY_A
    );
    
    return array('success' => true, 'data' => $booking);
}

/**
 * Get leads
 */
function amadex_get_leads($request) {
    global $wpdb;
    
    $status = $request->get_param('status');
    $type = $request->get_param('type');
    $exclude_status = $request->get_param('exclude_status');
    $limit = intval($request->get_param('limit') ?: 50);
    $offset = intval($request->get_param('offset') ?: 0);
    
    $table = $wpdb->prefix . 'amadex_leads';
    $bookings_table = $wpdb->prefix . 'amadex_bookings';
    
    $where = array('1=1');
    
    // Handle status filtering
    // If status is explicitly set to CANCELLED, show only CANCELLED (for Restore Deleted Leads view)
    // Otherwise, exclude CANCELLED by default (unless explicitly requested)
    if ($status === 'CANCELLED') {
        // User is viewing "Restore Deleted Leads" - show only CANCELLED
        $where[] = $wpdb->prepare("status = %s", $status);
    } elseif ($status) {
        // User filtered by a specific status (NEW, ASSIGNED, etc.) - show only that status (CANCELLED excluded)
        $where[] = $wpdb->prepare("status = %s", $status);
    } elseif ($exclude_status) {
        // Explicitly exclude a status (e.g., CANCELLED)
        $where[] = $wpdb->prepare("status != %s", $exclude_status);
    } else {
        // Default: exclude CANCELLED leads (only show active leads)
        $where[] = "status != 'CANCELLED'";
    }
    
    if ($type) {
        $where[] = $wpdb->prepare("lead_type = %s", $type);
    }
    
    $where_clause = implode(' AND ', $where);
    
    // Include latest booking totals so external dashboards match internal amounts
    $sql = "SELECT l.*,
                   (SELECT b.total_amount FROM {$bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_total_amount,
                   (SELECT b.currency FROM {$bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_currency,
                   (SELECT b.flight_data FROM {$bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_flight_data
            FROM {$table} l
            WHERE {$where_clause}
            ORDER BY l.created_at DESC
            LIMIT %d OFFSET %d";
    $leads = $wpdb->get_results($wpdb->prepare($sql, $limit, $offset), ARRAY_A);
    
    // Decode JSON fields
    foreach ($leads as &$lead) {
        if (isset($lead['flight_data']) && is_string($lead['flight_data'])) {
            $lead['flight_data'] = json_decode($lead['flight_data'], true);
        }
        if (isset($lead['booking_flight_data']) && is_string($lead['booking_flight_data'])) {
            $lead['booking_flight_data'] = json_decode($lead['booking_flight_data'], true);
        }
    }
    unset($lead);
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
    
    return array(
        'success' => true,
        'data' => $leads,
        'total' => intval($total)
    );
}

/**
 * Get single lead
 */
function amadex_get_lead($request) {
    global $wpdb;
    
    $id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'amadex_leads';
    
    $lead = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
    
    if (!$lead) {
        return new WP_Error('not_found', 'Lead not found', array('status' => 404));
    }
    
    // Decode JSON fields
    if (isset($lead['flight_data'])) {
        $lead['flight_data'] = json_decode($lead['flight_data'], true);
    }
    if (isset($lead['search_params'])) {
        $lead['search_params'] = json_decode($lead['search_params'], true);
    }
    
    return array('success' => true, 'data' => $lead);
}

/**
 * Update lead
 */
function amadex_update_lead($request) {
    global $wpdb;
    
    $id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'amadex_leads';
    
    // Get JSON body data
    $body = $request->get_json_params();
    if (empty($body)) {
        // Fallback to POST data
        $body = $_POST;
    }
    
    $data = array();
    
    if (isset($body['status'])) {
        $data['status'] = sanitize_text_field($body['status']);
    }
    
    if (isset($body['notes'])) {
        // Use sanitize_text_field if sanitize_textarea_field doesn't exist
        if (function_exists('sanitize_textarea_field')) {
            $data['notes'] = sanitize_textarea_field($body['notes']);
        } else {
            $data['notes'] = sanitize_text_field($body['notes']);
        }
    }
    
    if (empty($data)) {
        return new WP_Error('no_data', 'No data to update', array('status' => 400));
    }
    
    $result = $wpdb->update($table, $data, array('id' => $id));
    
    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to update lead', array('status' => 500));
    }
    
    return array('success' => true, 'message' => 'Lead updated successfully');
}

/**
 * Assign agent to lead
 */
function amadex_assign_agent_to_lead($request) {
    global $wpdb;
    
    $lead_id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'amadex_leads';
    
    // Get JSON body data
    $body = $request->get_json_params();
    if (empty($body)) {
        // Fallback to POST data
        $body = $_POST;
    }
    
    $agent_id = intval($body['agent_id'] ?? 0);
    
    if (!$agent_id) {
        return new WP_Error('missing_agent', 'Agent ID is required', array('status' => 400));
    }
    
    // Update lead with assigned agent and set status to ASSIGNED
    $data = array('status' => 'ASSIGNED');
    
    // Try to add assigned_agent_id if column exists
    // Check if column exists by trying to update it
    $columns = $wpdb->get_col("DESC {$table}", 0);
    if (in_array('assigned_agent_id', $columns)) {
        $data['assigned_agent_id'] = $agent_id;
    }
    
    $result = $wpdb->update($table, $data, array('id' => $lead_id));
    
    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to assign agent to lead', array('status' => 500));
    }
    
    return array('success' => true, 'message' => 'Agent assigned successfully');
}

/**
 * Get statistics
 */
function amadex_get_stats($request) {
    global $wpdb;
    
    $bookings_table = $wpdb->prefix . 'amadex_bookings';
    $leads_table = $wpdb->prefix . 'amadex_leads';
    
    // Exclude CANCELLED leads from totals to match external dashboard behavior
    $stats = array(
        'bookings' => array(
            'total' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table}")),
            'pending' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'PENDING'")),
            'confirmed' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'CONFIRMED'")),
            'ticketed' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$bookings_table} WHERE status = 'TICKETED'")),
            'total_revenue' => floatval($wpdb->get_var("SELECT SUM(total_amount) FROM {$bookings_table}") ?: 0)
        ),
        'leads' => array(
            // Exclude CANCELLED leads from total count (only count active leads)
            'total' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status != 'CANCELLED'")),
            'online_pending' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE status = 'NEW' AND lead_type = 'ONLINE_LEAD' AND status != 'CANCELLED'"))
        )
    );
    
    return array('success' => true, 'data' => $stats);
}

/**
 * Update booking
 */
function amadex_update_booking($request) {
    global $wpdb;
    
    $id = intval($request->get_param('id'));
    $table = $wpdb->prefix . 'amadex_bookings';
    
    $data = array();
    
    if ($request->get_param('pnr')) {
        $data['pnr'] = sanitize_text_field($request->get_param('pnr'));
    }
    
    if ($request->get_param('status')) {
        $data['status'] = sanitize_text_field($request->get_param('status'));
    }
    
    if (empty($data)) {
        return new WP_Error('no_data', 'No data to update', array('status' => 400));
    }
    
    $result = $wpdb->update($table, $data, array('id' => $id));
    
    if ($result === false) {
        return new WP_Error('update_failed', 'Failed to update booking', array('status' => 500));
    }
    
    return array('success' => true, 'message' => 'Booking updated successfully');
}

/**
 * MoonPay Commerce (Hel.io) Pay Link webhook.
 * Hel.io sends POST with event CREATED when a payment succeeds. We confirm the booking and send emails.
 */
function amadex_helio_webhook($request) {
    $body = $request->get_body();
    if (empty($body)) {
        return new WP_REST_Response(array('received' => true), 200);
    }
    $payload = json_decode($body, true);
    if (!is_array($payload) || empty($payload['event']) || $payload['event'] !== 'CREATED') {
        return new WP_REST_Response(array('received' => true), 200);
    }
    $payment_settings = get_option('amadex_payment_settings', array());
    $webhook_secret = isset($payment_settings['moonpay_helio_webhook_secret']) ? trim($payment_settings['moonpay_helio_webhook_secret']) : '';
    if (!empty($webhook_secret)) {
        $auth = $request->get_header('Authorization');
        if (!$auth || strpos($auth, 'Bearer ') !== 0 || trim(substr($auth, 7)) !== $webhook_secret) {
            return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
        }
    }
    $transaction_object = isset($payload['transactionObject']) ? $payload['transactionObject'] : null;
    if (!is_array($transaction_object)) {
        $transaction_object = isset($payload['transaction']) ? json_decode($payload['transaction'], true) : null;
    }
    $paylink_id = null;
    if (is_array($transaction_object) && !empty($transaction_object['paylinkId'])) {
        $paylink_id = $transaction_object['paylinkId'];
    } elseif (is_array($transaction_object) && !empty($transaction_object['paylink_id'])) {
        $paylink_id = $transaction_object['paylink_id'];
    }
    if (empty($paylink_id)) {
        return new WP_REST_Response(array('received' => true), 200);
    }
    $booking_reference = get_transient('amadex_moonpay_booking_by_paylink_' . $paylink_id);
    if (empty($booking_reference)) {
        return new WP_REST_Response(array('received' => true), 200);
    }
    $database = new Amadex_Database();
    $database->ensure_tables_ready();
    $booking = $database->get_booking_by_reference($booking_reference);
    if (!$booking || (isset($booking['status']) && $booking['status'] === 'CONFIRMED')) {
        delete_transient('amadex_moonpay_booking_by_paylink_' . $paylink_id);
        return new WP_REST_Response(array('received' => true), 200);
    }
    $booking_id = isset($booking['id']) ? (int) $booking['id'] : 0;
    $database->update_booking_status($booking_id, 'CONFIRMED');
    $txn_id = is_array($transaction_object) && !empty($transaction_object['id']) ? $transaction_object['id'] : ('helio_' . $paylink_id);
    $amount = isset($booking['total_amount']) ? floatval($booking['total_amount']) : 0;
    $database->create_payment($booking_id, array(
        'transaction_id' => $txn_id,
        'payment_status' => 'CAPTURED',
        'payment_method' => 'MOONPAY_COMMERCE',
        'amount' => $amount,
        'currency' => isset($booking['currency']) ? $booking['currency'] : 'USD',
    ));
    delete_transient('amadex_moonpay_booking_by_paylink_' . $paylink_id);
    delete_transient('amadex_moonpay_paylink_' . $booking_reference);
    $booking = $database->get_booking_by_reference($booking_reference);
    do_action('amadex_booking_confirmed_moonpay_commerce', $booking_id, $booking, $database);
    return new WP_REST_Response(array('received' => true), 200);
}