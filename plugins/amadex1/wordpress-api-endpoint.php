<?php
/**
 * WordPress Plugin File
 * Add this to your WordPress Amadex plugin or functions.php
 * Creates REST API endpoints for the dashboard
 * 
 * Place this in: wp-content/plugins/amadex/wordpress-api-endpoint.php
 * Or add to your theme's functions.php
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
});

/**
 * Permission check (add your authentication here)
 * For now, allowing access - you should add proper authentication
 */
function amadex_api_permission_check() {
    // TODO: Add API key or token authentication
    // For security, implement proper authentication
    return true; // Change this to implement proper auth
}

/**
 * Get bookings
 */
function amadex_get_bookings($request) {
    global $wpdb;
    
    $status = $request->get_param('status');
    $channel = $request->get_param('channel');
    $agent_id = $request->get_param('agent_id');
    $limit = intval($request->get_param('limit') ?: 50);
    $offset = intval($request->get_param('offset') ?: 0);
    
    $table = $wpdb->prefix . 'amadex_bookings';
    $users_table = $wpdb->prefix . 'amadex_dashboard_users';
    
    $where = array('1=1');
    
    if ($status) {
        $where[] = $wpdb->prepare("status = %s", $status);
    }
    
    if ($channel) {
        $where[] = $wpdb->prepare("booking_channel = %s", $channel);
    }
    
    if ($agent_id) {
        $where[] = $wpdb->prepare("assigned_agent_id = %d", $agent_id);
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT b.*, u.full_name as agent_name 
            FROM {$table} b 
            LEFT JOIN {$users_table} u ON b.assigned_agent_id = u.id 
            WHERE {$where_clause} 
            ORDER BY b.created_at DESC 
            LIMIT %d OFFSET %d";
    
    $bookings = $wpdb->get_results($wpdb->prepare($sql, $limit, $offset), ARRAY_A);
    
    // Decode JSON fields
    foreach ($bookings as &$booking) {
        if (isset($booking['flight_data'])) {
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
        }
    }
    
    // Get total count
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
    
    return array(
        'success' => true,
        'data' => $bookings,
        'total' => intval($total)
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
        $wpdb->prepare("SELECT * FROM {$passengers_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1", $id),
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
    
    $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $leads = $wpdb->get_results($wpdb->prepare($sql, $limit, $offset), ARRAY_A);
    
    // Decode JSON fields
    foreach ($leads as &$lead) {
        if (isset($lead['flight_data'])) {
            $lead['flight_data'] = json_decode($lead['flight_data'], true);
        }
    }
    
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}");
    
    return array(
        'success' => true,
        'data' => $leads,
        'total' => intval($total)
    );
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



