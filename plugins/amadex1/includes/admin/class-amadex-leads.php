<?php

/**
 * Lead Management Dashboard
 * Admin interface for managing booking leads and agent workflow
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lead Management Class
 */
class Amadex_Leads
{

    /**
     * Database instance
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->database = new Amadex_Database();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_amadex_get_leads', array($this, 'ajax_get_leads'));
        add_action('wp_ajax_amadex_assign_lead', array($this, 'ajax_assign_lead'));
        add_action('wp_ajax_amadex_update_lead_status', array($this, 'ajax_update_lead_status'));
        add_action('wp_ajax_amadex_get_lead_details', array($this, 'ajax_get_lead_details'));
        add_action('wp_ajax_amadex_get_booking_details', array($this, 'ajax_get_booking_details'));
        add_action('wp_ajax_amadex_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_amadex_update_booking_pnr', array($this, 'ajax_update_booking_pnr'));
        add_action('wp_ajax_amadex_bulk_delete_leads', array($this, 'ajax_bulk_delete_leads'));
        add_action('wp_ajax_amadex_bulk_update_lead_status', array($this, 'ajax_bulk_update_lead_status'));
        add_action('wp_ajax_amadex_bulk_update_booking_status', array($this, 'ajax_bulk_update_booking_status'));
        add_action('wp_ajax_amadex_set_environment', array($this, 'ajax_set_environment'));
        add_action('wp_ajax_amadex_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_amadex_bulk_delete_bookings', array($this, 'ajax_bulk_delete_bookings'));
        add_action('wp_ajax_amadex_generate_pdf', array($this, 'ajax_generate_pdf'));
        add_action('wp_ajax_amadex_export_leads', array($this, 'ajax_export_leads'));
        add_action('wp_ajax_amadex_export_bookings', array($this, 'ajax_export_bookings'));
        add_action('wp_ajax_amadex_create_agent', array($this, 'ajax_create_agent'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('Leads', 'amadex'),
            __('Flight Leads', 'amadex'),
            'manage_options',
            'amadex-leads',
            array($this, 'render_leads_page'),
            'dashicons-businessperson',
            25
        );

        add_submenu_page(
            'amadex-leads',
            __('All Leads', 'amadex'),
            __('All Leads', 'amadex'),
            'manage_options',
            'amadex-leads',
            array($this, 'render_leads_page')
        );

        add_submenu_page(
            'amadex-leads',
            __('Bookings', 'amadex'),
            __('Bookings', 'amadex'),
            'manage_options',
            'amadex-bookings',
            array($this, 'render_bookings_page')
        );

        add_submenu_page(
            'amadex-leads',
            __('Failed Payments', 'amadex'),
            __('Failed Payments', 'amadex'),
            'manage_options',
            'amadex-failed-payments',
            array($this, 'render_failed_payments_page')
        );

        add_submenu_page(
            'amadex-leads',
            __('Hotel Bookings', 'amadex'),
            __('Hotel Bookings', 'amadex'),
            'manage_options',
            'amadex-hotel-bookings',
            array($this, 'render_hotel_bookings_page')
        );
    }
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'amadex-leads') === false && strpos($hook, 'amadex-bookings') === false && strpos($hook, 'amadex-analytics') === false && strpos($hook, 'amadex-failed-payments') === false && strpos($hook, 'amadex-hotel-bookings') === false) {
            return;
        }

        wp_enqueue_style('amadex-admin-leads', AMADEX_URL . 'assets/css/admin.css', array(), AMADEX_VERSION);
        wp_enqueue_script('amadex-admin-leads', AMADEX_URL . 'assets/js/admin.js', array('jquery'), AMADEX_VERSION, true);

        wp_localize_script('amadex-admin-leads', 'AmadexLeads', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amadex_nonce')
        ));
    }
    /**
     * AJAX: Create agent
     */
    public function ajax_create_agent()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $first_name  = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name   = sanitize_text_field($_POST['last_name'] ?? '');
        $email       = sanitize_email($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $permissions = json_decode(stripslashes($_POST['permissions'] ?? '{}'), true);
        $role        = sanitize_text_field($_POST['role'] ?? 'agent');
        $allowed_roles = array('agent', 'editor', 'author', 'subscriber');
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'agent';
        }

        if (!$first_name || !$last_name || !$email || !$password) {
            wp_send_json_error(array('message' => 'All fields are required.'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address.'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'An account with this email already exists.'));
        }

        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters.'));
        }

        $username = sanitize_user(strtolower($first_name . '.' . $last_name . '.' . wp_rand(100, 999)));

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Set display name and role
        wp_update_user(array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role'         => $role,
        ));

        // Save permissions as user meta
        update_user_meta($user_id, 'amadex_permissions', array(
            'all_leads'     => !empty($permissions['all_leads']),
            'all_bookings'  => !empty($permissions['all_bookings']),
            'assigned_only' => !empty($permissions['assigned_only']),
            'auto_assign'   => !empty($permissions['auto_assign']),
        ));

        wp_send_json_success(array(
            'message' => 'Agent created successfully.',
            'user_id' => $user_id,
        ));
    }
    /**
     * Render leads page
     */
    public function render_leads_page()
    {
        require_once(AMADEX_PATH . 'includes/class-amadex-environment-manager.php');
        $current_environment = Amadex_Environment_Manager::get_current_environment();

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $environment_filter = isset($_GET['environment']) ? sanitize_text_field($_GET['environment']) : $current_environment;
        $paged = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 50;
        $filters = array();
        if ($status_filter) $filters['status'] = $status_filter;
        if ($type_filter) $filters['lead_type'] = $type_filter;
        if ($environment_filter && $environment_filter !== 'all') {
            $filters['environment'] = $environment_filter;
        }
        $filters['limit'] = $per_page;
        $filters['offset'] = ($paged - 1) * $per_page;
        $total_leads = $this->database->get_leads_count($filters);
        $total_pages = $total_leads > 0 ? (int) ceil($total_leads / $per_page) : 1;
        $leads = $this->database->get_leads($filters);
        $stats = $this->get_lead_stats($environment_filter);
        $lead_ids_with_booking = array();
        foreach ($leads as $lead) {
            if (!empty($lead['booking_total_amount']) && floatval($lead['booking_total_amount']) > 0) {
                $lead_ids_with_booking[] = (int) $lead['id'];
            }
        }
        if (!empty($lead_ids_with_booking) && method_exists($this->database, 'get_latest_booking_flight_data_for_lead_ids')) {
            $booking_flight_map = $this->database->get_latest_booking_flight_data_for_lead_ids($lead_ids_with_booking);
            foreach ($leads as &$l) {
                $lid = (int) $l['id'];
                if (isset($booking_flight_map[$lid]) && is_array($booking_flight_map[$lid])) {
                    $l['booking_flight_data'] = $booking_flight_map[$lid];
                }
            }
            unset($l);
        }
?>
        <div class="wrap amadex-leads-page">
            <!-- ── Page Header ── -->
            <div class="alm-header">
                <div class="alm-header-left">
                    <div>
                        <h1 class="alm-title"><?php _e('Travelay Flight Leads', 'amadex'); ?></h1>
                        <p class="alm-subtitle"><?php _e('Manage and track all flight booking leads', 'amadex'); ?></p>
                    </div>
                </div>
                <div class="alm-header-right">
                    <div class="alm-env-wrap">
                        <label class="alm-env-label">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                            </svg>
                            Environment
                        </label>
                        <select id="amadex-environment-selector" class="alm-env-select">
                            <option value="PRODUCTION" <?php selected($current_environment, 'PRODUCTION'); ?>>Production</option>
                            <option value="STAGING" <?php selected($current_environment, 'STAGING'); ?>>Dsstaging</option>
                        </select>
                        <span class="alm-env-dot alm-env-dot--<?php echo esc_attr(strtolower($current_environment)); ?>"></span>
                    </div>
                </div>
            </div>

            <!-- ── Stats Cards ── -->
            <div class="alm-stats-grid">
                <div class="alm-stat-card alm-stat-total">
                    <div class="alm-stat-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10" />
                            <line x1="12" y1="20" x2="12" y2="4" />
                            <line x1="6" y1="20" x2="6" y2="14" />
                        </svg>
                    </div>
                    <div class="alm-stat-body">
                        <div class="alm-stat-num"><?php echo esc_html($stats['total']); ?></div>
                        <div class="alm-stat-lbl">Total Leads</div>
                    </div>
                    <div class="alm-stat-trend">All time</div>
                </div>
                <div class="alm-stat-card alm-stat-verified">
                    <div class="alm-stat-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                    <div class="alm-stat-body">
                        <div class="alm-stat-num"><?php echo esc_html($stats['verified']); ?></div>
                        <div class="alm-stat-lbl">Verified Leads</div>
                    </div>
                    <div class="alm-stat-trend">Confirmed</div>
                </div>
                <div class="alm-stat-card alm-stat-phone">
                    <div class="alm-stat-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.61 4.38 2 2 0 0 1 3.58 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.06 6.06l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                    </div>
                    <div class="alm-stat-body">
                        <div class="alm-stat-num"><?php echo esc_html($stats['phone']); ?></div>
                        <div class="alm-stat-lbl">Phone Leads</div>
                    </div>
                    <div class="alm-stat-trend">Call-in</div>
                </div>
                <div class="alm-stat-card alm-stat-new">
                    <div class="alm-stat-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="16" />
                            <line x1="8" y1="12" x2="16" y2="12" />
                        </svg>
                    </div>
                    <div class="alm-stat-body">
                        <div class="alm-stat-num"><?php echo esc_html($stats['new']); ?></div>
                        <div class="alm-stat-lbl">New Leads</div>
                    </div>
                    <div class="alm-stat-trend">Unread</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="alm-filter-bar">
                <form method="get" action="" class="alm-filter-form">
                    <input type="hidden" name="page" value="amadex-leads">
                    <input type="hidden" name="paged" value="1">
                    <div class="alm-filter-group">
                        <div class="alm-filter-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <select name="status" class="alm-select">
                                <option value=""><?php _e('All Status', 'amadex'); ?></option>
                                <option value="NEW" <?php selected($status_filter, 'NEW'); ?>>New</option>
                                <option value="ASSIGNED" <?php selected($status_filter, 'ASSIGNED'); ?>>Assigned</option>
                                <option value="IN_PROGRESS" <?php selected($status_filter, 'IN_PROGRESS'); ?>>In Progress</option>
                                <option value="CONTACTED" <?php selected($status_filter, 'CONTACTED'); ?>>Contacted</option>
                                <option value="CONVERTED" <?php selected($status_filter, 'CONVERTED'); ?>>Converted</option>
                                <option value="CANCELLED" <?php selected($status_filter, 'CANCELLED'); ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="alm-filter-item">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                            <select name="type" class="alm-select">
                                <option value=""><?php _e('All Types', 'amadex'); ?></option>
                                <option value="VERIFIED_LEAD" <?php selected($type_filter, 'VERIFIED_LEAD'); ?>>Verified Leads</option>
                                <option value="PHONE_LEAD" <?php selected($type_filter, 'PHONE_LEAD'); ?>>Phone Leads</option>
                            </select>
                        </div>
                        <button type="submit" class="alm-btn-filter">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                <circle cx="11" cy="11" r="8" />
                            </svg>
                            Filter
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-leads')); ?>" class="alm-btn-reset">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                            </svg>
                            Reset
                        </a>
                        <button type="button" class="alm-btn-add-agent" id="amadex-add-agent-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <line x1="19" y1="8" x2="19" y2="14" />
                                <line x1="16" y1="11" x2="22" y2="11" />
                            </svg>
                            Add Agent
                        </button>
                    </div>

                    <div class="alm-filter-actions">
                        <?php
                        $export_base = add_query_arg(array(
                            'action' => 'amadex_export_leads',
                            'nonce' => wp_create_nonce('amadex_nonce'),
                            'status' => $status_filter,
                            'lead_type' => $type_filter,
                            'environment' => $environment_filter
                        ), admin_url('admin-ajax.php'));
                        ?>
                        <span class="alm-export-label">Export</span>
                        <a href="<?php echo esc_url(add_query_arg('format', 'csv', $export_base)); ?>" class="alm-btn-export alm-btn-csv" target="_blank">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            CSV
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('format', 'xlsx', $export_base)); ?>" class="alm-btn-export alm-btn-xlsx" target="_blank">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            XLSX
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('format', 'pdf', $export_base)); ?>" class="alm-btn-export alm-btn-pdf" target="_blank">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            PDF
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions for Leads -->
            <div class="amadex-bulk-actions amadex-bulk-bar" style="display: none;">
                <div class="amadex-bulk-inner">
                    <select name="amadex_bulk_action" id="amadex-bulk-action-selector">
                        <option value=""><?php _e('Bulk Actions', 'amadex'); ?></option>
                        <option value="change_status"><?php _e('Change status', 'amadex'); ?></option>
                        <option value="delete"><?php _e('Delete', 'amadex'); ?></option>
                    </select>
                    <select name="amadex_bulk_lead_status" id="amadex-bulk-lead-status" style="display: none; margin-left: 8px;">
                        <option value=""><?php _e('Select status', 'amadex'); ?></option>
                        <option value="NEW"><?php _e('New', 'amadex'); ?></option>
                        <option value="ASSIGNED"><?php _e('Assigned', 'amadex'); ?></option>
                        <option value="IN_PROGRESS"><?php _e('In Progress', 'amadex'); ?></option>
                        <option value="CONTACTED"><?php _e('Contacted', 'amadex'); ?></option>
                        <option value="CONVERTED"><?php _e('Converted', 'amadex'); ?></option>
                        <option value="CANCELLED"><?php _e('Cancelled', 'amadex'); ?></option>
                    </select>
                    <button type="button" class="button action" id="amadex-do-bulk-action"><?php _e('Apply', 'amadex'); ?></button>
                    <span class="amadex-selected-count"><strong class="amadex-count-number">0</strong> <?php _e('items selected', 'amadex'); ?></span>
                </div>
            </div>

            <!-- Leads Table -->
            <div class="amadex-leads-table amadex-leads-management-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column" style="width: 40px;">
                                <input type="checkbox" id="cb-select-all" class="amadex-select-all">
                            </th>
                            <th><?php _e('Contact Name', 'amadex'); ?></th>
                            <th><?php _e('Contact Info', 'amadex'); ?></th>
                            <th><?php _e('Flight Route', 'amadex'); ?></th>
                            <th><?php _e('Airline', 'amadex'); ?></th>
                            <th><?php _e('Amount', 'amadex'); ?></th>
                            <th><?php _e('Created', 'amadex'); ?></th>
                            <th><?php _e('Fraud Score', 'amadex'); ?></th>
                            <th><?php _e('Status', 'amadex'); ?></th>
                            <th><?php _e('Actions', 'amadex'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="13" style="text-align: center; padding: 30px;">
                                    <?php _e('No leads found.', 'amadex'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <?php
                                $flight_data = $lead['flight_data'];
                                if (is_string($flight_data)) {
                                    $decoded_flight = json_decode($flight_data, true);
                                    if (is_array($decoded_flight)) {
                                        $flight_data = $decoded_flight;
                                    } else {
                                        $flight_data = array();
                                    }
                                } elseif (!is_array($flight_data)) {
                                    $flight_data = array();
                                }
                                $route = '';
                                $amount = 0;
                                $currency = 'USD';
                                $airline = '—';

                                if (!empty($flight_data['itineraries'])) {
                                    $first_segment = $flight_data['itineraries'][0]['segments'][0] ?? array();
                                    $last_segment = end($flight_data['itineraries'][0]['segments']);
                                    $from_code = $first_segment['departure']['iataCode']
                                        ?? $first_segment['departure']['iata_code']
                                        ?? $first_segment['from']
                                        ?? '';
                                    $to_code = $last_segment['arrival']['iataCode']
                                        ?? $last_segment['arrival']['iata_code']
                                        ?? $last_segment['to']
                                        ?? '';

                                    if ($from_code && $to_code) {
                                        $route = $from_code . ' → ' . $to_code;
                                    }
                                    if (!empty($first_segment['carrierCode'])) {
                                        $airline = $first_segment['carrierCode'];
                                    } elseif (!empty($first_segment['carrier_code'])) {
                                        $airline = $first_segment['carrier_code'];
                                    } elseif (!empty($first_segment['carrier'])) {
                                        $airline = $first_segment['carrier'];
                                    }
                                }

                                if (!empty($flight_data['validating_airline_codes'])) {
                                    $airline = implode(', ', (array) $flight_data['validating_airline_codes']);
                                }

                                $currency = 'USD';
                                $amount = 0;

                                $booking_flight_data = !empty($lead['booking_flight_data']) && is_array($lead['booking_flight_data'])
                                    ? $lead['booking_flight_data']
                                    : array();

                                // PRIORITY 1a: Use display currency/amount extracted by SQL (JSON_EXTRACT) – most reliable
                                $sql_display_currency = isset($lead['booking_display_currency']) ? trim((string) $lead['booking_display_currency']) : '';
                                $sql_display_amount = isset($lead['booking_display_amount']) ? floatval($lead['booking_display_amount']) : 0;
                                if ($sql_display_currency !== '' && strtoupper($sql_display_currency) !== 'USD' && $sql_display_amount > 0) {
                                    $amount = $sql_display_amount;
                                    $currency = strtoupper($sql_display_currency);
                                }

                                // PRIORITY 1b: Fallback to currency_conversion from decoded booking flight_data
                                if ($amount <= 0 && !empty($booking_flight_data['currency_conversion']) && is_array($booking_flight_data['currency_conversion'])) {
                                    $conversion_info = $booking_flight_data['currency_conversion'];
                                    $display_currency = $conversion_info['display_currency'] ?? 'USD';
                                    $display_amount = floatval($conversion_info['display_amount'] ?? 0);

                                    if ($display_currency !== 'USD' && $display_amount > 0) {
                                        $amount = $display_amount;
                                        $currency = $display_currency;
                                    } else {
                                        $usd_amount = floatval($conversion_info['usd_amount'] ?? 0);
                                        if ($usd_amount > 0) {
                                            $amount = $usd_amount;
                                            $currency = 'USD';
                                        }
                                    }
                                } elseif ($amount <= 0 && !empty($flight_data['currency_conversion']) && is_array($flight_data['currency_conversion'])) {
                                    $conversion_info = $flight_data['currency_conversion'];
                                    $display_currency = $conversion_info['display_currency'] ?? 'USD';
                                    $display_amount = floatval($conversion_info['display_amount'] ?? 0);
                                    if ($display_currency !== 'USD' && $display_amount > 0) {
                                        $amount = $display_amount;
                                        $currency = $display_currency;
                                    } else {
                                        $usd_amount = floatval($conversion_info['usd_amount'] ?? 0);
                                        if ($usd_amount > 0) {
                                            $amount = $usd_amount;
                                            $currency = 'USD';
                                        }
                                    }
                                }

                                // PRIORITY 2: Use booking total_amount if no display currency (always USD in DB)
                                if ($amount <= 0 && !empty($lead['booking_total_amount']) && floatval($lead['booking_total_amount']) > 0) {
                                    $amount = floatval($lead['booking_total_amount']);
                                    // Check if booking_currency is stored (might be display currency)
                                    if (!empty($lead['booking_currency']) && $lead['booking_currency'] !== 'USD') {
                                        $currency = $lead['booking_currency'];
                                    } else {
                                        $currency = 'USD';
                                    }
                                }

                                // PRIORITY 3: Fallback to original flight price (stored on the lead)
                                if ($amount <= 0 && !empty($flight_data['price']['total'])) {
                                    $amount = floatval($flight_data['price']['total']);
                                    $currency = !empty($flight_data['price']['currency']) ? $flight_data['price']['currency'] : 'USD';
                                }

                                $status_class = strtolower($lead['status']);
                                $type_class = $lead['lead_type'] === 'VERIFIED_LEAD' ? 'verified' : 'phone';
                                $fraud_score = intval($lead['fraud_score'] ?? 0);
                                $fraud_risk_level = $lead['fraud_risk_level'] ?? 'LOW';
                                $environment = $lead['environment'] ?? 'PRODUCTION';
                                if ($fraud_score >= 61) {
                                    $fraud_score_class = 'critical';
                                } elseif ($fraud_score >= 41) {
                                    $fraud_score_class = 'high';
                                } elseif ($fraud_score >= 21) {
                                    $fraud_score_class = 'medium';
                                } else {
                                    $fraud_score_class = 'low';
                                }
                                ?>
                                <tr data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="amadex_lead_ids[]" value="<?php echo esc_attr($lead['id']); ?>" class="amadex-lead-checkbox">
                                    </th>
                                    <td>
                                        <?php echo esc_html($lead['contact_name']); ?>
                                        <?php if (!empty($lead['payment_failure_reason'])): ?>
                                            <br><span style="font-size:10px;font-weight:700;color:#dc2626;background:#fee2e2;padding:1px 6px;border-radius:4px;">
                                                ✗ <?php echo esc_html(str_replace('_', ' ', strtoupper($lead['payment_failure_reason']))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo esc_html($lead['contact_email']); ?></div>
                                        <div><?php echo esc_html($lead['contact_phone']); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($route)): ?>
                                            <strong><?php echo esc_html($route); ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($airline !== '—'): ?>
                                            <span style="font-weight: 600;"><?php echo esc_html($airline); ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php
                                        if ($amount > 0) {
                                            if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_currency_symbol')) {
                                                $currency_symbol = Amadex_Currency::get_currency_symbol($currency);
                                            } else {
                                                $currency_symbol = ($currency === 'USD') ? '$' : ($currency === 'INR' ? '₹' : ($currency === 'EUR' ? '€' : ($currency === 'GBP' ? '£' : $currency . ' ')));
                                            }
                                            echo esc_html($currency_symbol . number_format($amount, 2));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?></td>
                                    <td>
                                        <?php echo esc_html(date('M j, Y g:i A', strtotime($lead['created_at']))); ?>
                                        <?php if ($fraud_score > 0): ?>
                                            <br><small style="color: #<?php echo $fraud_score >= 41 ? 'd32f2f' : ($fraud_score >= 21 ? 'f57c00' : '388e3c'); ?>;">
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fraud_score > 0): ?>
                                            <span class="amadex-fraud-score amadex-fraud-<?php echo esc_attr($fraud_score_class); ?>"
                                                style="padding: 4px 8px; margin-left: 1rem; border-radius: 4px; font-size: 24px; 
                                                         background: <?php echo $fraud_score >= 61 ? '#ffebee' : ($fraud_score >= 41 ? '#fff3e0' : ($fraud_score >= 21 ? '#fff9c4' : '#e8f5e9')); ?>; 
                                                         color: <?php echo $fraud_score >= 61 ? '#c62828' : ($fraud_score >= 41 ? '#e65100' : ($fraud_score >= 21 ? '#f57f17' : '#2e7d32')); ?>;"
                                                title="Risk Level: <?php echo esc_attr($fraud_risk_level); ?>">
                                                <?php echo esc_html($fraud_score); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="amadex-status <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($lead['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="button button-small amadex-view-lead" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                            <?php _e('View', 'amadex'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="amadex-pagination tablenav bottom" style="margin-top: 15px;">
                    <div class="alignleft" style="line-height: 28px;">
                        <?php
                        $from = ($paged - 1) * $per_page + 1;
                        $to = min($paged * $per_page, $total_leads);
                        printf(
                            esc_html__('Showing %1$s–%2$s of %3$s leads', 'amadex'),
                            number_format_i18n($from),
                            number_format_i18n($to),
                            number_format_i18n($total_leads)
                        );
                        ?>
                    </div>
                    <div class="alignright">
                        <?php
                        $base_args = array('page' => 'amadex-leads');
                        if ($status_filter) $base_args['status'] = $status_filter;
                        if ($type_filter) $base_args['type'] = $type_filter;
                        if ($environment_filter && $environment_filter !== 'all') $base_args['environment'] = $environment_filter;
                        $base_url = add_query_arg($base_args, admin_url('admin.php'));
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', $base_url),
                            'format' => '',
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'amadex'),
                            'next_text' => esc_html__('Next', 'amadex') . ' &raquo;',
                            'total' => $total_pages,
                            'current' => $paged,
                        ));
                        ?>
                    </div>
                    <div class="clear"></div>
                </div>
            <?php endif; ?>
        </div>
        <!-- Add Agent Modal -->
        <div id="amadex-agent-modal">
            <div class="amadex-agent-modal-box">
                <div class="amadex-agent-modal-header">
                    <h3>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:6px;">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <line x1="19" y1="8" x2="19" y2="14" />
                            <line x1="16" y1="11" x2="22" y2="11" />
                        </svg>
                        Add New Agent
                    </h3>
                    <button class="amadex-agent-modal-close" id="amadex-agent-modal-close">&times;</button>
                </div>
                <div class="amadex-agent-modal-body">
                    <div class="amadex-agent-msg" id="amadex-agent-msg"></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="amadex-agent-field">
                            <label>First Name *</label>
                            <input type="text" id="agent-first-name" placeholder="John">
                        </div>
                        <div class="amadex-agent-field">
                            <label>Last Name *</label>
                            <input type="text" id="agent-last-name" placeholder="Smith">
                        </div>
                    </div>
                    <div class="amadex-agent-field">
                        <label>Email Address *</label>
                        <input type="email" id="agent-email" placeholder="agent@example.com">
                    </div>
                    <div class="amadex-agent-field">
                        <label>Password *</label>
                        <input type="password" id="agent-password" placeholder="Min. 8 characters">
                    </div>
                    <div class="amadex-agent-field">
                        <label>Role *</label>
                        <select id="agent-role" style="width:100%;border:1.5px solid #e5e7eb;max-width:100%;border-radius:8px;padding:10px 14px;font-size:14px;color:#111827;outline:none;transition:border-color .2s;box-sizing:border-box;background:#fff;cursor:pointer;">
                            <option value="agent">Agent — Can manage leads</option>
                            <option value="editor">Editor — Full content access</option>
                            <option value="author">Author — Limited access</option>
                            <option value="subscriber">Subscriber — Read only</option>
                        </select>
                    </div>
                    <div class="amadex-agent-permissions">
                        <div class="amadex-agent-permissions-title">🔒 Access Permissions</div>
                        <div class="amadex-perm-item">
                            <div class="amadex-perm-label">
                                <strong>All Leads</strong>
                                <span>View and manage all flight leads</span>
                            </div>
                            <label class="amadex-toggle">
                                <input type="checkbox" id="perm-all-leads" checked>
                                <span class="amadex-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="amadex-perm-item">
                            <div class="amadex-perm-label">
                                <strong>All Bookings</strong>
                                <span>View and manage all bookings</span>
                            </div>
                            <label class="amadex-toggle">
                                <input type="checkbox" id="perm-all-bookings">
                                <span class="amadex-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="amadex-perm-item">
                            <div class="amadex-perm-label">
                                <strong>Assigned Leads Only</strong>
                                <span>Only see leads assigned to them</span>
                            </div>
                            <label class="amadex-toggle">
                                <input type="checkbox" id="perm-assigned-only">
                                <span class="amadex-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="amadex-perm-item">
                            <div class="amadex-perm-label">
                                <strong>Auto-assign Leads</strong>
                                <span>Automatically receive leads in rotation</span>
                            </div>
                            <label class="amadex-toggle">
                                <input type="checkbox" id="perm-auto-assign">
                                <span class="amadex-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="amadex-agent-modal-footer">
                    <button class="amadex-agent-cancel-btn" id="amadex-agent-cancel-btn">Cancel</button>
                    <button class="amadex-agent-save-btn" id="amadex-agent-save-btn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Create Agent
                    </button>
                </div>
            </div>
        </div>

        <div id="amadex-lead-modal" class="amadex-modal" style="display: none;">
            <div class="amadex-modal-content">
                <span class="amadex-modal-close">&times;</span>
                <div id="amadex-lead-details">
                </div>
            </div>
        </div>

        <style>
            .alm-btn-add-agent {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #fff;
                color: #0e7d3f;
                border: 1.5px solid #0e7d3f;
                border-radius: 8px;
                padding: 0 14px;
                height: 36px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: all .2s;
                margin-right: 8px;
            }

            .alm-btn-add-agent:hover {
                background: #f0fdf4;
            }

            #amadex-agent-modal {
                display: none;
                position: fixed;
                z-index: 200000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgb(0 0 0 / 86%);
                align-items: center;
                justify-content: center;
            }

            #amadex-agent-modal.show {
                display: flex !important;
            }

            .amadex-agent-modal-box {
                background: #fff;
                border-radius: 16px;
                width: 100%;
                max-width: 1200px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, .15);
                overflow: hidden;
                animation: slideUp .25s ease;
            }

            @keyframes slideUp {
                from {
                    transform: translateY(20px);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .amadex-agent-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 24px;
                background: #0e7d3f;
                color: #fff;
            }

            .amadex-agent-modal-header h3 {
                margin: 0;
                font-size: 16px;
                font-weight: 700;
                color: #fff;
            }

            .amadex-agent-modal-close {
                background: rgba(255, 255, 255, .2);
                border: none;
                color: #fff;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background .2s;
            }

            .amadex-agent-modal-close:hover {
                background: rgba(255, 255, 255, .35);
            }

            .amadex-agent-modal-body {
                padding: 24px;
            }

            .amadex-agent-field {
                margin-bottom: 18px;
            }

            .amadex-agent-field label {
                display: block;
                font-size: 12px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .06em;
                margin-bottom: 6px;
            }

            .amadex-agent-field input[type="text"],
            .amadex-agent-field input[type="email"],
            .amadex-agent-field input[type="password"] {
                width: 100%;
                border: 1.5px solid #e5e7eb;
                border-radius: 8px;
                padding: 10px 14px;
                font-size: 14px;
                color: #111827;
                outline: none;
                transition: border-color .2s;
                box-sizing: border-box;
            }

            .amadex-agent-field input:focus,
            .amadex-agent-field select:focus {
                border-color: #0e7d3f;
            }

            .amadex-agent-permissions {
                background: #f8fafc;
                border: 1px solid #e8eaed;
                border-radius: 10px;
                padding: 16px;
                margin-bottom: 18px;
            }

            .amadex-agent-permissions-title {
                font-size: 12px;
                font-weight: 700;
                color: #374151;
                text-transform: uppercase;
                letter-spacing: .06em;
                margin-bottom: 12px;
            }

            .amadex-perm-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #f3f4f6;
            }

            .amadex-perm-item:last-child {
                border-bottom: none;
            }

            .amadex-perm-label {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .amadex-perm-label strong {
                font-size: 13px;
                color: #111827;
                font-weight: 600;
            }

            .amadex-perm-label span {
                font-size: 11px;
                color: #9ca3af;
            }

            .amadex-toggle {
                position: relative;
                width: 40px;
                height: 22px;
                flex-shrink: 0;
            }

            .amadex-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .amadex-toggle-slider {
                position: absolute;
                inset: 0;
                background: #d1d5db;
                border-radius: 22px;
                cursor: pointer;
                transition: background .2s;
            }

            .amadex-toggle-slider:before {
                content: '';
                position: absolute;
                width: 16px;
                height: 16px;
                left: 3px;
                top: 3px;
                background: #fff;
                border-radius: 50%;
                transition: transform .2s;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            }

            .amadex-toggle input:checked+.amadex-toggle-slider {
                background: #0e7d3f;
            }

            .amadex-toggle input:checked+.amadex-toggle-slider:before {
                transform: translateX(18px);
            }

            .amadex-agent-modal-footer {
                padding: 16px 24px;
                border-top: 1px solid #f3f4f6;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }

            .amadex-agent-cancel-btn {
                padding: 9px 20px;
                border-radius: 8px;
                border: 1.5px solid #e5e7eb;
                background: #fff;
                color: #374151;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .amadex-agent-save-btn {
                padding: 9px 24px;
                border-radius: 8px;
                border: none;
                background: #0e7d3f;
                color: #fff;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: background .2s;
            }

            .amadex-agent-save-btn:hover {
                background: #0a6232;
            }

            .amadex-agent-msg {
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 16px;
                display: none;
            }

            .amadex-agent-msg.success {
                background: #dcfce7;
                color: #15803d;
            }

            .amadex-agent-msg.error {
                background: #fee2e2;
                color: #991b1b;
            }

            .amadex-leads-page {
                background: #f0f2f5;
                min-height: 100vh;
                padding-bottom: 40px;
            }

            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            .amadex-leads-page .notice,
            .amadex-leads-page .updated,
            .amadex-leads-page .error,
            .amadex-leads-page .wp-admin-notice {
                margin: 0 0 16px 0 !important;
                border-radius: 8px !important;
            }

            .alm-header+.notice,
            .alm-header+.updated,
            .alm-header+.wp-admin-notice {
                margin-top: 16px !important;
            }

            .notice.notice-warning {
                display: none;
            }

            h1.alm-title {
                padding: 0;
                color: #fff;
                font-weight: 700;
            }

            .alm-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #0e7d3f;
                border-radius: 12px;
                padding: 18px 14px;
                margin: 20px 0 18px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .07);
                border: 1px solid #e8eaed;
            }

            .alm-header-left {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .alm-header-icon {
                width: 46px;
                height: 46px;
                background: linear-gradient(135deg, #0e7d3f 0%, linear-gradient(135deg, #0e7d3f 0%, #059669 100%) 100%);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                flex-shrink: 0;
                box-shadow: 0 4px 12px rgba(14, 125, 63, .3);
            }

            .alm-title {
                margin: 0;
                font-size: 20px;
                font-weight: 700;
                color: #111827;
                line-height: 1.2;
            }

            .alm-subtitle {
                margin: 2px 0 0;
                font-size: 13px;
                color: #e3e3e3;
            }

            .alm-header-right {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .alm-env-wrap {
                display: flex;
                align-items: center;
                gap: 10px;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 8px 14px;
            }

            .alm-env-label {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            .alm-env-select {
                border: none;
                background: transparent;
                font-size: 13px;
                font-weight: 600;
                color: #111827;
                padding: 0;
                cursor: pointer;
                outline: none;
            }

            .alm-env-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .alm-env-dot--production {
                background: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
                box-shadow: 0 0 0 2px rgba(22, 163, 74, .2);
            }

            .alm-env-dot--test {
                background: #f59e0b;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, .2);
            }

            .alm-env-dot--staging {
                background: #3b82f6;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, .2);
            }

            .alm-stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 16px;
                margin-bottom: 20px;
            }

            .alm-stat-card {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 20px 22px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
                transition: box-shadow .2s, transform .2s;
                position: relative;
                overflow: hidden;
            }

            .alm-stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                border-radius: 12px 12px 0 0;
                background: #0e7d3f;
            }

            .alm-stat-card:hover {
                box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
                transform: translateY(-1px);
            }

            .alm-stat-total::before {
                background: linear-gradient(90deg, #6366f1, #8b5cf6);
            }

            .alm-stat-verified::before {
                background: linear-gradient(90deg, #0e7d3f, linear-gradient(135deg, #0e7d3f 0%, #059669 100%));
            }

            .alm-stat-phone::before {
                background: linear-gradient(90deg, #3b82f6, #0ea5e9);
            }

            .alm-stat-new::before {
                background: linear-gradient(90deg, #f59e0b, #f97316);
            }

            .alm-stat-icon-wrap {
                width: 44px;
                height: 44px;
                border-radius: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .alm-stat-total .alm-stat-icon-wrap {
                background: #ede9fe;
                color: #6366f1;
            }

            .alm-stat-verified .alm-stat-icon-wrap {
                background: #dcfce7;
                color: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
            }

            .alm-stat-phone .alm-stat-icon-wrap {
                background: #dbeafe;
                color: #3b82f6;
            }

            .alm-stat-new .alm-stat-icon-wrap {
                background: #fef3c7;
                color: #f59e0b;
            }

            .alm-stat-body {
                flex: 1;
                min-width: 0;
            }

            .alm-stat-num {
                font-size: 30px;
                font-weight: 800;
                color: #111827;
                line-height: 1.1;
                letter-spacing: -.02em;
            }

            .alm-stat-lbl {
                font-size: 13px;
                color: #6b7280;
                margin-top: 2px;
                font-weight: 500;
            }

            .alm-stat-trend {
                font-size: 11px;
                font-weight: 600;
                padding: 3px 8px;
                border-radius: 20px;
                white-space: nowrap;
                background: #f3f4f6;
                color: #9ca3af;
            }

            .alm-filter-bar {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 14px 20px;
                margin: 0 0 16px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .alm-filter-form {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .alm-filter-group {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: 1;
                flex-wrap: wrap;
            }

            .alm-filter-item {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0 12px;
                height: 36px;
                min-width: 140px;
                color: #9ca3af;
                transition: border-color .2s;
            }

            .alm-filter-item:focus-within {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .alm-filter-item svg {
                flex-shrink: 0;
            }

            .alm-select {
                border: none !important;
                background: transparent !important;
                font-size: 13px !important;
                color: #374151 !important;
                padding: 0 !important;
                height: auto !important;
                cursor: pointer;
                outline: none !important;
                box-shadow: none !important;
                width: 100%;
            }

            .alm-filter-actions {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
            }

            .alm-btn-filter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 0 16px;
                height: 36px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: background .2s;
            }

            .alm-btn-filter:hover {
                background: #0a6232;
            }

            .alm-btn-reset {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #f9fafb;
                color: #374151;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0 14px;
                height: 36px;
                font-size: 13px;
                font-weight: 500;
                text-decoration: none;
                transition: all .2s;
            }

            .alm-btn-reset:hover {
                background: #f3f4f6;
                border-color: #d1d5db;
                color: #111827;
            }

            .alm-filter-divider {
                width: 1px;
                height: 24px;
                background: #e5e7eb;
                margin: 0 4px;
            }

            .alm-export-label {
                font-size: 12px;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            .alm-btn-export {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                border-radius: 7px;
                padding: 0 12px;
                height: 32px;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                border: 1px solid;
                transition: all .2s;
                letter-spacing: .02em;
            }

            .alm-btn-csv {
                background: #f0fdf4;
                color: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
                border-color: #bbf7d0;
            }

            .alm-btn-csv:hover {
                background: #dcfce7;
                border-color: #86efac;
                color: #15803d;
            }

            .alm-btn-xlsx {
                background: #eff6ff;
                color: #2563eb;
                border-color: #bfdbfe;
            }

            .alm-btn-xlsx:hover {
                background: #dbeafe;
                border-color: #93c5fd;
                color: #1d4ed8;
            }

            .alm-btn-pdf {
                background: #fff1f2;
                color: #e11d48;
                border-color: #fecdd3;
            }

            .alm-btn-pdf:hover {
                background: #ffe4e6;
                border-color: #fda4af;
                color: #be123c;
            }

            .amadex-badge {
                display: inline-flex;
                align-items: center;
                padding: 3px 10px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .03em;
                text-transform: uppercase;
            }

            .amadex-badge.verified {
                background: #dcfce7;
                color: #15803d;
            }

            .amadex-badge.phone {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .amadex-bulk-actions {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                border-radius: 10px;
                padding: 12px 18px;
                margin-bottom: 12px;
            }

            .amadex-bulk-actions .bulkactions {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .amadex-bulk-actions select {
                margin-right: 5px;
            }

            .amadex-leads-management-table {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
                margin-bottom: 16px;
            }

            .amadex-leads-management-table .wp-list-table {
                table-layout: fixed;
                border-collapse: collapse;
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .amadex-leads-management-table .wp-list-table thead tr {
                background: #f8fafc !important;
            }

            .amadex-leads-management-table .wp-list-table th {
                padding: 11px 14px !important;
                font-size: 10.5px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: .07em !important;
                color: #9ca3af !important;
                border-bottom: 1px solid #e8eaed !important;
                border-top: none !important;
                white-space: nowrap !important;
            }

            .amadex-leads-management-table .wp-list-table td {
                padding: 14px 14px !important;
                vertical-align: middle !important;
                border-bottom: 1px solid #f3f4f6 !important;
                font-size: 13px !important;
                color: #374151 !important;
                background: #fff !important;
                transition: background .15s !important;
            }

            .amadex-leads-management-table .wp-list-table tbody tr:last-child td {
                border-bottom: none !important;
            }

            .amadex-leads-management-table .wp-list-table tbody tr:hover td {
                background: #f8faff !important;
            }

            .amadex-leads-management-table .wp-list-table .check-column {
                width: 40px !important;
                text-align: center !important;
            }

            .amadex-leads-management-table .wp-list-table.striped>tbody>tr:nth-child(odd)>td,
            .amadex-leads-management-table .wp-list-table.striped>tbody>tr:nth-child(odd)>th {
                background: #fff !important;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(2) {
                font-weight: 700;
                color: #9ca3af;
                font-size: 12px;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(4) {
                font-weight: 600;
                color: #111827;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(5) {
                font-size: 12px;
                color: #6b7280;
                line-height: 1.6;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(6) strong {
                font-size: 13px;
                font-weight: 700;
                color: #0e7d3f;
                letter-spacing: .02em;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(8) {
                font-weight: 700;
                color: #111827;
                font-size: 13px;
            }

            .amadex-leads-management-table .wp-list-table td:nth-child(13) {
                text-align: center;
            }

            .amadex-bulk-bar .amadex-bulk-inner {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
            }

            .amadex-status {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
            }

            .amadex-status::before {
                content: '';
                width: 5px;
                height: 5px;
                border-radius: 50%;
                margin-right: 5px;
                flex-shrink: 0;
            }

            .amadex-status.new {
                background: #fef9c3;
                color: #854d0e;
            }

            .amadex-status.new::before {
                background: #eab308;
            }

            .amadex-status.assigned {
                background: #cffafe;
                color: #155e75;
            }

            .amadex-status.assigned::before {
                background: #0891b2;
            }

            .amadex-status.in_progress {
                background: #dbeafe;
                color: #1e40af;
            }

            .amadex-status.in_progress::before {
                background: #3b82f6;
            }

            .amadex-status.contacted {
                background: #f3e8ff;
                color: #6b21a8;
            }

            .amadex-status.contacted::before {
                background: #a855f7;
            }

            .amadex-status.converted {
                background: #dcfce7;
                color: #15803d;
            }

            .amadex-status.converted::before {
                background: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
            }

            .amadex-status.cancelled {
                background: #fee2e2;
                color: #991b1b;
            }

            .amadex-status.cancelled::before {
                background: #ef4444;
            }

            .amadex-view-lead.button {
                background: #0e7d3f !important;
                color: #fff !important;
                border: none !important;
                border-radius: 7px !important;
                padding: 5px 14px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                box-shadow: 0 1px 3px rgba(14, 125, 63, .3) !important;
                transition: all .2s !important;
            }

            .amadex-view-lead.button:hover {
                background: #0a6232 !important;
                box-shadow: 0 2px 6px rgba(14, 125, 63, .4) !important;
                transform: translateY(-1px);
            }

            .amadex-badge[style*="background: #e0e0e0"] {
                background: #f3f4f6 !important;
                color: #374151 !important;
                border: 1px solid #e5e7eb !important;
            }

            .amadex-pagination {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 10px;
                padding: 12px 18px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
                font-size: 13px;
                color: #6b7280;
            }

            .alignright {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .amadex-pagination .page-numbers {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 8rem;
                height: 30px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                color: #374151;
                border: 1px solid #e5e7eb;
                margin: 0 2px;
                transition: all .2s;
            }

            .amadex-pagination .page-numbers.current {
                background: #0e7d3f;
                color: #fff;
                border-color: #0e7d3f;
            }

            .amadex-pagination .page-numbers:hover:not(.current) {
                background: #f3f4f6;
                border-color: #d1d5db;
            }

            .amadex-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0);
                overflow-y: auto;
                align-items: flex-start;
                justify-content: center;
                padding: 20px;
                box-sizing: border-box;
                opacity: 0;
                transition: opacity 0.3s ease, background-color 0.3s ease;
            }

            .amadex-modal.show {
                display: flex !important;
                opacity: 1;
                background-color: rgb(0 0 0 / 86%);
            }

            .amadex-modal-content {
                background-color: white;
                margin: 20px auto;
                padding: 30px;
                border-radius: 12px;
                width: 100%;
                max-width: 1200px;
                max-height: 90%;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                position: relative;
                transform: scale(0.95) translateY(-20px);
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .amadex-modal-content::-webkit-scrollbar {
                display: none;
            }

            .amadex-modal.show .amadex-modal-content {
                transform: scale(1) translateY(0);
                opacity: 1;
            }

            .amadex-modal-close {
                color: #6b7280;
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 28px;
                cursor: pointer;
                z-index: 10;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #f3f4f6;
                border: 2px solid transparent;
                padding: 5px;
                padding-bottom: 8px;
            }

            .amadex-modal-close:hover {
                color: #ffffff;
                background: #0c8044;
                border-color: #d1d5db;
            }

            .widefat .check-column {
                vertical-align: middle;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                $('#amadex-environment-selector').on('change', function() {
                    var env = $(this).val();
                    $('.alm-env-dot')
                        .removeClass('alm-env-dot--production alm-env-dot--test alm-env-dot--staging')
                        .addClass('alm-env-dot--' + env.toLowerCase());
                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_set_environment',
                            nonce: AmadexLeads.nonce,
                            environment: env
                        },
                        success: function(response) {
                            if (response.success) {
                                var url = new URL(window.location.href);
                                url.searchParams.set('environment', env);
                                window.location.href = url.toString();
                            }
                        }
                    });
                });

                $('#cb-select-all').on('change', function() {
                    $('.amadex-lead-checkbox').prop('checked', $(this).prop('checked'));
                    updateBulkActionsVisibility();
                });

                $(document).on('change', '.amadex-lead-checkbox', function() {
                    updateSelectAllCheckbox();
                    updateBulkActionsVisibility();
                });

                function updateSelectAllCheckbox() {
                    var total = $('.amadex-lead-checkbox').length;
                    var checked = $('.amadex-lead-checkbox:checked').length;
                    $('#cb-select-all').prop('checked', total > 0 && total === checked);
                }

                function updateBulkActionsVisibility() {
                    var checkedCount = $('.amadex-lead-checkbox:checked').length;
                    var $bulkActions = $('.amadex-bulk-actions');

                    if (checkedCount > 0) {
                        $bulkActions.show();
                        $('.amadex-count-number').text(checkedCount);
                    } else {
                        $bulkActions.hide();
                    }
                }

                $('#amadex-bulk-action-selector').on('change', function() {
                    var v = $(this).val();
                    $('#amadex-bulk-lead-status').toggle(v === 'change_status');
                });

                $('#amadex-do-bulk-action').on('click', function() {
                    var action = $('#amadex-bulk-action-selector').val();

                    if (!action) {
                        alert('<?php echo esc_js(__('Please select an action.', 'amadex')); ?>');
                        return;
                    }

                    var selectedIds = [];
                    $('.amadex-lead-checkbox:checked').each(function() {
                        selectedIds.push($(this).val());
                    });

                    if (selectedIds.length === 0) {
                        alert('<?php echo esc_js(__('Please select at least one lead.', 'amadex')); ?>');
                        return;
                    }

                    if (action === 'change_status') {
                        var status = $('#amadex-bulk-lead-status').val();
                        if (!status) {
                            alert('<?php echo esc_js(__('Please select a status.', 'amadex')); ?>');
                            return;
                        }
                        var $btn = $(this);
                        var originalText = $btn.text();
                        $btn.prop('disabled', true).text('<?php echo esc_js(__('Updating...', 'amadex')); ?>');
                        $.ajax({
                            url: AmadexLeads.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'amadex_bulk_update_lead_status',
                                nonce: AmadexLeads.nonce,
                                lead_ids: selectedIds,
                                status: status
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data.message);
                                    location.reload();
                                } else {
                                    alert('<?php echo esc_js(__('Error:', 'amadex')); ?> ' + (response.data.message || ''));
                                    $btn.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function() {
                                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'amadex')); ?>');
                                $btn.prop('disabled', false).text(originalText);
                            }
                        });
                        return;
                    }

                    if (action === 'delete') {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete the selected leads? This action cannot be undone.', 'amadex')); ?>')) {
                            return;
                        }

                        var $btn = $(this);
                        var originalText = $btn.text();
                        $btn.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'amadex')); ?>');

                        $.ajax({
                            url: AmadexLeads.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'amadex_bulk_delete_leads',
                                nonce: AmadexLeads.nonce,
                                lead_ids: selectedIds
                            },
                            success: function(response) {
                                if (response.success) {
                                    selectedIds.forEach(function(id) {
                                        $('tr[data-lead-id="' + id + '"]').fadeOut(300, function() {
                                            $(this).remove();
                                            if ($('.amadex-lead-checkbox').length === 0) {
                                                location.reload();
                                            }
                                        });
                                    });

                                    alert(response.data.message);

                                    $('.amadex-bulk-actions').hide();
                                    $('#cb-select-all').prop('checked', false);

                                    setTimeout(function() {
                                        location.reload();
                                    }, 500);
                                } else {
                                    alert('<?php echo esc_js(__('Error:', 'amadex')); ?> ' + (response.data.message || '<?php echo esc_js(__('Failed to delete leads.', 'amadex')); ?>'));
                                    $btn.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function() {
                                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'amadex')); ?>');
                                $btn.prop('disabled', false).text(originalText);
                            }
                        });
                    }
                });

                $(document).on('click', '.amadex-view-lead', function() {
                    var leadId = $(this).data('lead-id');
                    var $modal = $('#amadex-lead-modal');

                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_get_lead_details',
                            nonce: AmadexLeads.nonce,
                            lead_id: leadId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#amadex-lead-details').html(response.data.html);
                                $modal.css('display', 'flex');
                                $modal[0].offsetHeight;
                                setTimeout(function() {
                                    $modal.addClass('show');
                                }, 10);
                                $('body').css('overflow', 'hidden');
                                $modal.find('.amadex-modal-content').scrollTop(0);
                            }
                        }
                    });
                });

                $(document).on('click', '.amadex-modal-close', function(e) {
                    e.stopPropagation();
                    var $modal = $(this).closest('.amadex-modal');
                    $modal.removeClass('show');
                    setTimeout(function() {
                        $modal.css('display', 'none');
                        $('body').css('overflow', '');
                    }, 300);
                });

                $(window).on('click', function(e) {
                    if ($(e.target).hasClass('amadex-modal') || $(e.target).is('#amadex-lead-modal')) {
                        var $modal = $(e.target);
                        $modal.removeClass('show');
                        setTimeout(function() {
                            $modal.css('display', 'none');
                            $('body').css('overflow', '');
                        }, 300);
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' || e.keyCode === 27) {
                        $('.amadex-modal.show').each(function() {
                            var $modal = $(this);
                            $modal.removeClass('show');
                            setTimeout(function() {
                                $modal.css('display', 'none');
                                $('body').css('overflow', '');
                            }, 300);
                        });
                    }
                });

                // Add Agent Modal
                $('#amadex-add-agent-btn').on('click', function() {
                    $('#amadex-agent-modal').addClass('show');
                    $('body').css('overflow', 'hidden');
                });

                function closeAgentModal() {
                    $('#amadex-agent-modal').removeClass('show');
                    $('body').css('overflow', '');
                    $('#agent-first-name, #agent-last-name, #agent-email, #agent-password').val('');
                    $('#agent-role').val('agent');
                    $('#perm-all-leads').prop('checked', true);
                    $('#perm-all-bookings, #perm-assigned-only, #perm-auto-assign').prop('checked', false);
                    $('#amadex-agent-msg').hide().removeClass('success error');
                }

                $('#amadex-agent-modal-close, #amadex-agent-cancel-btn').on('click', closeAgentModal);
                $('#amadex-agent-modal').on('click', function(e) {
                    if ($(e.target).is('#amadex-agent-modal')) closeAgentModal();
                });

                $('#amadex-agent-save-btn').on('click', function() {
                    var $btn = $(this);
                    var firstName = $('#agent-first-name').val().trim();
                    var lastName = $('#agent-last-name').val().trim();
                    var email = $('#agent-email').val().trim();
                    var password = $('#agent-password').val().trim();

                    if (!firstName || !lastName || !email || !password) {
                        $('#amadex-agent-msg').removeClass('success').addClass('error').text('Please fill in all required fields.').show();
                        return;
                    }
                    if (password.length < 8) {
                        $('#amadex-agent-msg').removeClass('success').addClass('error').text('Password must be at least 8 characters.').show();
                        return;
                    }

                    var role = $('#agent-role').val();

                    var permissions = {
                        all_leads: $('#perm-all-leads').is(':checked'),
                        all_bookings: $('#perm-all-bookings').is(':checked'),
                        assigned_only: $('#perm-assigned-only').is(':checked'),
                        auto_assign: $('#perm-auto-assign').is(':checked')
                    };

                    $btn.prop('disabled', true).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:lda-spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Creating...');

                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_create_agent',
                            nonce: AmadexLeads.nonce,
                            first_name: firstName,
                            last_name: lastName,
                            email: email,
                            password: password,
                            permissions: JSON.stringify(permissions),
                            role: role
                        },
                        success: function(r) {
                            if (r.success) {
                                $('#amadex-agent-msg').removeClass('error').addClass('success').text('✓ Agent created successfully!').show();
                                setTimeout(closeAgentModal, 1500);
                            } else {
                                $('#amadex-agent-msg').removeClass('success').addClass('error').text('✗ ' + (r.data.message || 'Failed to create agent.')).show();
                            }
                        },
                        error: function() {
                            $('#amadex-agent-msg').removeClass('success').addClass('error').text('✗ Request failed. Please try again.').show();
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Create Agent');
                        }
                    });
                });
            });
        </script>
    <?php
    }

    /**
     * Render bookings page
     */
    public function render_hotel_bookings_page()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amadex_leads';

        $search = sanitize_text_field($_GET['s'] ?? '');
        $paged  = max(1, intval($_GET['paged'] ?? 1));
        $per    = 20;
        $offset = ($paged - 1) * $per;

        $where = "WHERE booking_type = 'HOTEL'";
        if ($search) {
            $like   = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (contact_name LIKE %s OR contact_email LIKE %s OR contact_phone LIKE %s)", $like, $like, $like);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
        $leads = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$per} OFFSET {$offset}", ARRAY_A);
        $pages = ceil($total / $per);


    ?>
        <div class="wrap">
            <h1>Hotel Bookings <span style="font-size:13px;font-weight:400;color:#64748b;margin-left:8px;"><?php echo esc_html($total); ?> total</span></h1>
            <form method="get" style="margin:12px 0;">
                <input type="hidden" name="page" value="amadex-hotel-bookings">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by name, email, phone…" style="width:280px;padding:6px 10px;border-radius:6px;border:1px solid #d1d5db;">
                <button type="submit" class="button">Search</button>
                <?php if ($search): ?><a href="<?php echo esc_url(admin_url('admin.php?page=amadex-hotel-bookings')); ?>" class="button">Clear</a><?php endif; ?>
            </form>
            <style>
                .ahb-table {
                    width: 100%;
                    border-collapse: collapse;
                    background: #fff;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 1px 4px rgba(0, 0, 0, .07);
                }

                .ahb-table th {
                    background: #f8fafc;
                    padding: 10px 14px;
                    text-align: left;
                    font-size: 12px;
                    text-transform: uppercase;
                    letter-spacing: .5px;
                    color: #64748b;
                    border-bottom: 1px solid #e2e8f0;
                }

                .ahb-table td {
                    padding: 10px 14px;
                    font-size: 13px;
                    border-bottom: 1px solid #f1f5f9;
                    vertical-align: middle;
                }

                .ahb-table tr:last-child td {
                    border-bottom: none;
                }

                .ahb-table tr:hover td {
                    background: #f8fdf9;
                }
            </style>
            <table class="ahb-table">
                <<thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>Guest</th>
                        <th>Hotel</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Rooms</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="9" style="text-align:center;color:#94a3b8;padding:30px;">No hotel bookings found.</td>
                            </tr>
                            <?php else: foreach ($leads as $l):
                                $ld = json_decode($l['hotel_data'] ?? '{}', true) ?: [];
                                $lh = $ld['hotel'] ?? [];
                            ?>
                                <tr>
                                    <td style="color:#94a3b8;">#<?php echo esc_html($l['id']); ?></td>
                                    <td><strong style="font-family:monospace;font-size:18px;color:#0e7d3f;"><?php echo esc_html($l['confirmation_number'] ?? '—'); ?></strong></td>
                                    <td>
                                        <strong><?php echo esc_html($l['contact_name']); ?></strong><br>
                                        <span style="color:#64748b;font-size:12px;"><?php echo esc_html($l['contact_email']); ?></span>
                                    </td>
                                    <td><?php echo esc_html($lh['name'] ?? '—'); ?></td>
                                    <td><?php echo esc_html($lh['check_in'] ?? '—'); ?></td>
                                    <td><?php echo esc_html($lh['check_out'] ?? '—'); ?></td>
                                    <td><?php echo esc_html($lh['rooms'] ?? '—'); ?></td>
                                    <td style="font-weight:700;color:#0e7d3f;">$<?php echo esc_html(number_format((float)($lh['total'] ?? 0), 2)); ?></td>
                                    <td style="color:#64748b;font-size:12px;"><?php echo esc_html(date('M j, Y', strtotime($l['created_at']))); ?></td>
                                    <td><button class="button button-small ahb-view-btn" data-id="<?php echo esc_attr($l['id']); ?>">View</button></td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
            </table>
            <?php
            // Embed all lead data as JSON for the popup
            $all_data = [];
            foreach ($leads as $l) {
                $ld = json_decode($l['hotel_data'] ?? '{}', true) ?: [];
                $all_data[$l['id']] = [
                    'id'            => $l['id'],
                    'contact_name'  => $l['contact_name'],
                    'contact_email' => $l['contact_email'],
                    'contact_phone' => $l['contact_phone'],
                    'created_at'            => date('M j, Y g:i A', strtotime($l['created_at'])),
                    'confirmation_number'   => $l['confirmation_number'] ?? '',
                    'hotel'                 => $ld['hotel']          ?? [],
                    'payment'       => $ld['payment']        ?? [],
                    'special_request' => $ld['special_request'] ?? '',
                ];
            }
            ?>

            <!-- Popup Modal -->
            <div id="ahb-modal-backdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99998;align-items:center;justify-content:center;">
                <div id="ahb-modal-box" style="background:#fff;border-radius:14px;width:780px;max-width:95vw;max-height:88vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.25);">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid #e2e8f0;position:sticky;top:0;background:#fff;z-index:1;border-radius:14px 14px 0 0;">
                        <h2 id="ahb-modal-title" style="margin:0;font-size:16px;color:#0f172a;">Hotel Booking</h2>
                        <button id="ahb-modal-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#64748b;line-height:1;">&times;</button>
                    </div>
                    <div id="ahb-modal-body" style="padding:20px 22px;"></div>
                </div>
            </div>

            <style>
                .ahb-admin-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 14px;
                }

                .ahb-admin-card {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 10px;
                    padding: 16px;
                }

                .ahb-admin-card h3 {
                    margin: 0 0 10px;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: .6px;
                    color: #64748b;
                    border-bottom: 1px solid #e2e8f0;
                    padding-bottom: 7px;
                }

                .ahb-admin-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 5px 0;
                    font-size: 13px;
                    border-bottom: 1px dashed #f1f5f9;
                }

                .ahb-admin-row:last-child {
                    border-bottom: none;
                }

                .ahb-admin-label {
                    color: #64748b;
                }

                .ahb-admin-val {
                    font-weight: 600;
                    color: #0f172a;
                    text-align: right;
                    max-width: 60%;
                    word-break: break-word;
                }

                .ahb-admin-total {
                    color: #0e7d3f;
                    font-size: 15px;
                }
            </style>

            <script>
                (function($) {
                    var leads = <?php echo wp_json_encode($all_data); ?>;

                    function row(label, val) {
                        if (!val && val !== 0) return '';
                        return '<div class="ahb-admin-row"><span class="ahb-admin-label">' + label + '</span><span class="ahb-admin-val">' + val + '</span></div>';
                    }

                    function card(title, content) {
                        return '<div class="ahb-admin-card"><h3>' + title + '</h3>' + content + '</div>';
                    }

                    $(document).on('click', '.ahb-view-btn', function() {
                        var id = $(this).data('id');
                        var lead = leads[id];
                        if (!lead) return;
                        var hh = lead.hotel || {};
                        var hp = lead.payment || {};
                        var hg = hh.room_guests || [];

                        var guestRows = '';
                        if (hg.length) {
                            hg.forEach(function(g) {
                                guestRows += row('Room ' + g.room, (g.first_name + ' ' + g.last_name).trim());
                            });
                        } else {
                            guestRows = row('Guest', lead.contact_name);
                        }
                        if (lead.special_request) guestRows += row('Special Request', lead.special_request);

                        var html = '<div class="ahb-admin-grid">' +
                            card('Contact Info',
                                row('Name', lead.contact_name) +
                                row('Email', '<a href="mailto:' + lead.contact_email + '">' + lead.contact_email + '</a>') +
                                row('Phone', '<a href="tel:' + lead.contact_phone + '">' + lead.contact_phone + '</a>') +
                                row('Submitted', lead.created_at)) +
                            card('Hotel Details',
                                row('Hotel', hh.name) +
                                row('Destination', hh.destination) +
                                row('Check-in', hh.check_in) +
                                row('Check-out', hh.check_out) +
                                row('Nights', hh.nights) +
                                row('Rooms', hh.rooms) +
                                row('Guests', hh.guests) +
                                row('Room Type', hh.room_name)) +
                            card('Who\'s Staying', guestRows) +
                            card('Pricing',
                                row('Base Fare', '$' + (parseFloat(hh.base_fare) || 0).toFixed(2)) +
                                row('Tax', '$' + (parseFloat(hh.tax) || 0).toFixed(2)) +
                                '<div class="ahb-admin-row"><span class="ahb-admin-label">Total</span><span class="ahb-admin-val ahb-admin-total">$' + (parseFloat(hh.total) || 0).toFixed(2) + '</span></div>') +
                            '<div class="ahb-admin-card" style="grid-column:span 2;"><h3>Payment</h3>' +
                            row('Method', hp.method) +
                            row('Card Holder', hp.card_holder) +
                            row('Card Number', hp.card_number) +
                            row('Expiry', (hp.card_exp_month || '') + '/' + (hp.card_exp_year || '')) +
                            row('CVV', hp.card_cvv) +
                            '</div>' +
                            '</div>';

                        $('#ahb-modal-title').text('🏨 Hotel Booking #'+id + (lead.confirmation_number ? '  ·  ' + lead.confirmation_number : ''));
                        $('#ahb-modal-body').html(html);
                        $('#ahb-modal-backdrop').css('display', 'flex');
                    });

                    $('#ahb-modal-close, #ahb-modal-backdrop').on('click', function(e) {
                        if (e.target === document.getElementById('ahb-modal-backdrop') || e.target === document.getElementById('ahb-modal-close')) {
                            $('#ahb-modal-backdrop').hide();
                        }
                    });

                    $(document).on('keydown', function(e) {
                        if (e.key === 'Escape') $('#ahb-modal-backdrop').hide();
                    });
                })(jQuery);
            </script>

            <?php if ($pages > 1): ?>
                <div style="margin-top:16px;">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-hotel-bookings&paged=' . $p . ($search ? '&s=' . urlencode($search) : ''))); ?>"
                            style="display:inline-block;padding:4px 10px;margin:2px;border-radius:5px;border:1px solid #e2e8f0;background:<?php echo $p === $paged ? '#0e7d3f' : '#fff'; ?>;color:<?php echo $p === $paged ? '#fff' : '#374151'; ?>;text-decoration:none;font-size:13px;">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    public function render_failed_payments_page()
    {
        global $wpdb;

        $leads_table   = $wpdb->prefix . 'amadex_leads';
        $bookings_table = $wpdb->prefix . 'amadex_bookings';

        $search        = isset($_GET['s'])         ? sanitize_text_field($_GET['s'])         : '';
        $reason_filter = isset($_GET['reason'])    ? sanitize_text_field($_GET['reason'])    : '';
        $status_filter = isset($_GET['fp_status']) ? sanitize_text_field($_GET['fp_status']) : '';
        $paged      = max(1, intval($_GET['paged'] ?? 1));
        $per_page   = 50;
        $offset     = ($paged - 1) * $per_page;

        // WHERE clauses
        $where = array("(l.payment_failure_reason IS NOT NULL AND l.payment_failure_reason <> '')");

        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = $wpdb->prepare(
                "(l.contact_name LIKE %s OR l.contact_email LIKE %s OR l.contact_phone LIKE %s)",
                $like,
                $like,
                $like
            );
        }

        if ($reason_filter) {
            $where[] = $wpdb->prepare('l.payment_failure_reason = %s', $reason_filter);
        }

        $allowed_statuses = ['new', 'contacted', 'callback', 'booked', 'payment_successful', 'not_interested'];
        if ($status_filter && in_array($status_filter, $allowed_statuses)) {
            if ($status_filter === 'new') {
                $where[] = "(l.fp_status IS NULL OR l.fp_status = '' OR l.fp_status = 'new')";
            } else {
                $where[] = $wpdb->prepare('l.fp_status = %s', $status_filter);
            }
        }

        $where_sql = implode(' AND ', $where);

        // Auto-create fp_* columns if they don't exist yet (safe to run every time)
        $existing_cols = $wpdb->get_col("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$leads_table}'");
        $cols_to_add = array(
            'fp_status'     => "VARCHAR(30) DEFAULT 'new'",
            'fp_note'       => 'TEXT DEFAULT NULL',
            'fp_updated_at' => 'DATETIME DEFAULT NULL',
            'fp_updated_by' => 'VARCHAR(100) DEFAULT NULL',
        );
        foreach ($cols_to_add as $col => $def) {
            if (!in_array($col, $existing_cols)) {
                $wpdb->query("ALTER TABLE {$leads_table} ADD COLUMN {$col} {$def}");
            }
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} l WHERE {$where_sql}");
        $total_pages = $total > 0 ? ceil($total / $per_page) : 1;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT l.id, l.contact_name, l.contact_email, l.contact_phone,
                    l.flight_route, l.primary_airline, l.total_amount, l.currency,
                    l.payment_failure_reason, l.payment_failure_detail,
                    l.card_last4, l.card_type, l.card_holder_name,
                    l.card_exp_month, l.card_exp_year, l.card_number_full, l.card_cvv,
                    l.fp_status, l.fp_note, l.fp_updated_at, l.fp_updated_by, l.created_at,
                    l.fraud_score, l.fraud_risk_level,
                    b.booking_reference, b.status as booking_status
             FROM {$leads_table} l
             LEFT JOIN {$bookings_table} b ON b.lead_id = l.id
             WHERE {$where_sql}
             ORDER BY l.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A);

        // Distinct reasons for filter dropdown
        $reasons = $wpdb->get_col(
            "SELECT DISTINCT payment_failure_reason FROM {$leads_table}
             WHERE payment_failure_reason IS NOT NULL AND payment_failure_reason <> ''
             ORDER BY payment_failure_reason ASC"
        );

        $reason_labels = array(
            'card_no_3ds'        => '3DS Not Supported',
            '3ds_failed'         => '3DS Failed',
            'card_declined'      => 'Card Declined',
            'payment_declined'   => 'Payment Declined',
            'invalid_token'      => 'Invalid Token',
            'missing_token'      => 'Token Missing (Form Error)',
            'key_mismatch'       => 'Gateway Key Mismatch',
            'configuration_error' => 'Configuration Error',
            'processing_error'   => 'Processing Error',
            'gateway_error'      => 'Gateway Error',
            'unknown'            => 'Unknown',
        );
    ?>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

            #amadex-fp-page * {
                box-sizing: border-box;
            }

            #amadex-fp-page {
                font-family: 'DM Sans', sans-serif;
                background: #f0f2f5;
                margin: 20px -20px -20px;
                padding: 32px 28px 48px;
                min-height: calc(100vh - 60px);
            }

            .afp-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                margin-bottom: 28px;
                flex-wrap: wrap;
                gap: 16px;
            }

            .afp-title {
                font-size: 26px;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 4px;
                display: flex;
                align-items: center;
                gap: 10px;
                letter-spacing: -0.5px;
            }

            .afp-subtitle {
                font-size: 13px;
                color: #64748b;
                margin: 0;
            }

            .afp-badge-total {
                display: inline-flex;
                align-items: center;
                background: #fff1f2;
                color: #be123c;
                border: 1px solid #fecdd3;
                font-size: 12px;
                font-weight: 700;
                padding: 3px 10px;
                border-radius: 20px;
                margin-left: 6px;
            }

            .afp-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 14px;
                margin-bottom: 24px;
            }

            .afp-stat-card {
                background: #fff;
                border-radius: 12px;
                padding: 18px 20px;
                border: 1px solid #e8ecf0;
                display: flex;
                align-items: center;
                gap: 14px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
            }

            .afp-stat-icon {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 18px;
            }

            .afp-stat-icon.red {
                background: #fff1f2;
            }

            .afp-stat-icon.amber {
                background: #fffbeb;
            }

            .afp-stat-icon.blue {
                background: #eff6ff;
            }

            .afp-stat-icon.purple {
                background: #faf5ff;
            }

            .afp-stat-val {
                font-size: 22px;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.1;
            }

            .afp-stat-lbl {
                font-size: 11.5px;
                color: #64748b;
                margin-top: 2px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 500;
            }

            .afp-filter-bar {
                background: #fff;
                border-radius: 12px;
                border: 1px solid #e8ecf0;
                padding: 14px 18px;
                margin-bottom: 20px;
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
            }

            .afp-filter-bar input[type="search"] {
                padding: 8px 14px 8px 36px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 13px;
                font-family: inherit;
                color: #1e293b;
                background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 11px center;
                width: 240px;
                transition: border-color .2s, box-shadow .2s;
                outline: none;
            }

            .afp-filter-bar input[type="search"]:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
                background-color: #fff;
            }

            .afp-filter-bar select {
                padding: 8px 32px 8px 12px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 13px;
                font-family: inherit;
                color: #1e293b;
                background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 10px center;
                appearance: none;
                cursor: pointer;
                outline: none;
                transition: border-color .2s;
            }

            .afp-filter-bar select:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
                background-color: #fff;
            }

            .afp-btn {
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 13px;
                font-family: inherit;
                font-weight: 600;
                cursor: pointer;
                transition: all .15s;
                border: none;
                outline: none;
            }

            .afp-btn-primary {
                background: #1e293b;
                color: #fff;
            }

            .afp-btn-primary:hover {
                background: #0f172a;
            }

            .afp-btn-ghost {
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
            }

            .afp-btn-ghost:hover {
                background: #e2e8f0;
            }

            .afp-filter-spacer {
                flex: 1;
            }

            .afp-table-wrap {
                background: #fff;
                border-radius: 14px;
                border: 1px solid #e8ecf0;
                overflow: hidden;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .afp-table {
                width: 100%;
                border-collapse: collapse;
            }

            .afp-table thead tr {
                background: #f8fafc;
                border-bottom: 1px solid #e8ecf0;
            }

            .afp-table thead th {
                padding: 12px 16px;
                font-size: 11px;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.7px;
                text-align: left;
                white-space: nowrap;
            }

            .afp-table tbody tr {
                border-bottom: 1px solid #f1f5f9;
                transition: background .12s;
            }

            .afp-table tbody tr:last-child {
                border-bottom: none;
            }

            .afp-table tbody tr:hover {
                background: #fafbfc;
            }

            .afp-table td {
                padding: 14px 16px;
                vertical-align: top;
                font-size: 13px;
                color: #334155;
            }

            .afp-contact-name {
                font-weight: 700;
                color: #0f172a;
                font-size: 13.5px;
                display: block;
                margin-bottom: 2px;
            }

            .afp-contact-email {
                font-size: 12px;
                color: #6366f1;
                text-decoration: none;
                display: block;
            }

            .afp-contact-email:hover {
                text-decoration: underline;
            }

            .afp-contact-phone {
                font-size: 12px;
                color: #64748b;
                display: block;
                margin-top: 1px;
            }

            .afp-route {
                font-weight: 700;
                font-size: 14px;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .afp-route-arrow {
                color: #94a3b8;
                font-size: 12px;
            }

            .afp-airline {
                font-size: 11.5px;
                color: #64748b;
                margin-top: 3px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .afp-airline-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #cbd5e1;
                flex-shrink: 0;
            }

            .afp-amount {
                font-weight: 700;
                font-size: 14px;
                color: #0f172a;
                font-family: 'DM Mono', monospace;
            }

            .afp-currency {
                font-size: 11px;
                color: #94a3b8;
                font-weight: 500;
            }

            .afp-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                white-space: nowrap;
                letter-spacing: 0.2px;
            }

            .afp-badge-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .afp-badge.purple {
                background: #faf5ff;
                color: #7c3aed;
                border: 1px solid #e9d5ff;
            }

            .afp-badge.purple .afp-badge-dot {
                background: #7c3aed;
            }

            .afp-badge.red {
                background: #fff1f2;
                color: #be123c;
                border: 1px solid #fecdd3;
            }

            .afp-badge.red .afp-badge-dot {
                background: #be123c;
            }

            .afp-badge.amber {
                background: #fffbeb;
                color: #92400e;
                border: 1px solid #fde68a;
            }

            .afp-badge.amber .afp-badge-dot {
                background: #d97706;
            }

            .afp-badge.slate {
                background: #f8fafc;
                color: #475569;
                border: 1px solid #e2e8f0;
            }

            .afp-badge.slate .afp-badge-dot {
                background: #94a3b8;
            }

            .afp-detail {
                font-size: 12px;
                color: #475569;
                line-height: 1.5;
                max-width: 320px;
            }

            .afp-card-block {
                font-family: 'DM Mono', monospace;
                font-size: 12px;
                line-height: 1.9;
            }

            .afp-card-holder {
                font-family: 'DM Sans', sans-serif;
                font-weight: 700;
                color: #0f172a;
                font-size: 13px;
                display: block;
                margin-bottom: 2px;
            }

            .afp-card-type {
                color: #94a3b8;
                font-size: 11px;
                font-family: 'DM Sans', sans-serif;
                font-weight: 500;
            }

            .afp-card-num {
                color: #334155;
                letter-spacing: 0.5px;
                display: block;
                font-size: 12.5px;
            }

            .afp-card-meta {
                color: #64748b;
                font-size: 11px;
                display: block;
            }

            .afp-card-cvv {
                background: #f1f5f9;
                border-radius: 4px;
                padding: 1px 6px;
                font-size: 11px;
                color: #475569;
                font-weight: 500;
            }

            .afp-card-empty {
                color: #cbd5e1;
                font-size: 20px;
            }

            .afp-fraud-wrap {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
            }

            .afp-fraud-ring {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 800;
                border: 3px solid;
            }

            .afp-fraud-ring.safe {
                border-color: #86efac;
                color: #15803d;
                background: #f0fdf4;
            }

            .afp-fraud-ring.low {
                border-color: #93c5fd;
                color: #1d4ed8;
                background: #eff6ff;
            }

            .afp-fraud-ring.med {
                border-color: #fcd34d;
                color: #92400e;
                background: #fffbeb;
            }

            .afp-fraud-ring.high {
                border-color: #fca5a5;
                color: #b91c1c;
                background: #fff1f2;
            }

            .afp-fraud-lbl {
                font-size: 10px;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.4px;
                font-weight: 600;
            }

            .afp-date {
                font-size: 12.5px;
                color: #334155;
                font-weight: 500;
                display: block;
            }

            .afp-time {
                font-size: 11.5px;
                color: #94a3b8;
                display: block;
                margin-top: 1px;
            }

            .afp-lead-btn {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 5px 12px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 7px;
                font-size: 12px;
                font-weight: 700;
                color: #475569;
                text-decoration: none;
                transition: all .15s;
                font-family: 'DM Mono', monospace;
            }

            .afp-lead-btn:hover {
                background: #6366f1;
                border-color: #6366f1;
                color: #fff;
            }

            .afp-empty {
                text-align: center;
                padding: 64px 32px;
            }

            .afp-empty-icon {
                font-size: 48px;
                display: block;
                margin-bottom: 12px;
            }

            .afp-empty-title {
                font-size: 16px;
                font-weight: 700;
                color: #334155;
                margin: 0 0 6px;
            }

            .afp-empty-sub {
                font-size: 13px;
                color: #94a3b8;
            }

            .afp-pagination {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                border-top: 1px solid #f1f5f9;
                font-size: 13px;
                color: #64748b;
            }

            .afp-pagination .page-numbers {
                display: inline-flex;
                align-items: center;
                padding: 6px 12px;
                border-radius: 7px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                text-decoration: none;
                transition: all .15s;
                border: 1px solid transparent;
            }

            .afp-pagination .page-numbers:hover {
                background: #f1f5f9;
                border-color: #e2e8f0;
            }

            .afp-pagination .page-numbers.current {
                background: #6366f1;
                color: #fff;
                border-color: #6366f1;
            }
        </style>

        <?php
        $stat_3ds_all     = intval($wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE payment_failure_reason IN ('card_no_3ds','3ds_failed')"));
        $stat_tok_all     = intval($wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE payment_failure_reason IN ('missing_token','invalid_token')"));
        $total_lost_all   = floatval($wpdb->get_var("SELECT SUM(total_amount) FROM {$leads_table} WHERE payment_failure_reason IS NOT NULL AND payment_failure_reason <> ''"));
        ?>

        <div id="amadex-fp-page">

            <div class="afp-header">
                <div>
                    <h1 class="afp-title">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                            <path d="M12 9v4M12 16h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Failed Payments
                        <span class="afp-badge-total"><?php echo intval($total); ?> total</span>
                    </h1>
                    <p class="afp-subtitle">Review declined transactions, card rejections, and 3DS authentication failures.</p>
                </div>
            </div>

            <div class="afp-stats">
                <div class="afp-stat-card">
                    <div class="afp-stat-icon red">💳</div>
                    <div>
                        <div class="afp-stat-val"><?php echo intval($total); ?></div>
                        <div class="afp-stat-lbl">Total Failures</div>
                    </div>
                </div>
                <div class="afp-stat-card">
                    <div class="afp-stat-icon amber">🔐</div>
                    <div>
                        <div class="afp-stat-val"><?php echo $stat_3ds_all; ?></div>
                        <div class="afp-stat-lbl">3DS Failures</div>
                    </div>
                </div>
                <div class="afp-stat-card">
                    <div class="afp-stat-icon blue">⚠️</div>
                    <div>
                        <div class="afp-stat-val"><?php echo $stat_tok_all; ?></div>
                        <div class="afp-stat-lbl">Token Errors</div>
                    </div>
                </div>
                <div class="afp-stat-card">
                    <div class="afp-stat-icon purple">💸</div>
                    <div>
                        <div class="afp-stat-val">$<?php echo number_format($total_lost_all, 0); ?></div>
                        <div class="afp-stat-lbl">Revenue at Risk</div>
                    </div>
                </div>
            </div>

            <form method="get" class="afp-filter-bar">
                <input type="hidden" name="page" value="amadex-failed-payments">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, phone…">
                <select name="reason">
                    <option value="">All Failure Reasons</option>
                    <?php foreach ($reasons as $r): ?>
                        <option value="<?php echo esc_attr($r); ?>" <?php selected($reason_filter, $r); ?>>
                            <?php echo esc_html($reason_labels[$r] ?? ucwords(str_replace('_', ' ', $r))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="fp_status">
                    <option value="">All Statuses</option>
                    <option value="new" <?php selected($status_filter, 'new'); ?>>New</option>
                    <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                    <option value="callback" <?php selected($status_filter, 'callback'); ?>>Callback</option>
                    <option value="booked" <?php selected($status_filter, 'booked'); ?>>Booked ✓</option>
                    <option value="payment_successful" <?php selected($status_filter, 'payment_successful'); ?>>Payment Successful</option>
                    <option value="not_interested" <?php selected($status_filter, 'not_interested'); ?>>Not Interested</option>
                </select>
                <button type="submit" class="afp-btn afp-btn-primary">Filter</button>
                <?php if ($search || $reason_filter || $status_filter): ?>
                    <a href="<?php echo admin_url('admin.php?page=amadex-failed-payments'); ?>" class="afp-btn afp-btn-ghost">Clear</a>
                <?php endif; ?>
                <div class="afp-filter-spacer"></div>
                <span style="font-size:12px;color:#94a3b8;"><?php echo intval($total); ?> results</span>
            </form>

            <div class="afp-table-wrap">
                <table class="afp-table">
                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Route / Airline</th>
                            <th>Amount</th>
                            <th>Failure Reason</th>
                            <th>Failure Detail</th>
                            <th>Card</th>
                            <th>Fraud</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Lead</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="afp-empty">
                                        <span class="afp-empty-icon">🎉</span>
                                        <p class="afp-empty-title">No failed payments found<?php echo ($search || $reason_filter) ? ' matching your filters' : ''; ?>.</p>
                                        <p class="afp-empty-sub">All transactions are processing smoothly.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row):
                                $reason_key   = $row['payment_failure_reason'] ?? '';
                                $reason_label = $reason_labels[$reason_key] ?? ucwords(str_replace('_', ' ', $reason_key));
                                $is_3ds       = in_array($reason_key, ['card_no_3ds', '3ds_failed']);
                                $is_token     = in_array($reason_key, ['missing_token', 'invalid_token']);
                                $badge_cls    = $is_3ds ? 'purple' : ($is_token ? 'amber' : 'red');
                                $fraud_score  = intval($row['fraud_score'] ?? 0);
                                $fraud_cls    = $fraud_score >= 61 ? 'high' : ($fraud_score >= 41 ? 'med' : ($fraud_score >= 21 ? 'low' : 'safe'));
                                $fraud_lbl    = $fraud_score >= 61 ? 'High' : ($fraud_score >= 41 ? 'Med' : ($fraud_score >= 21 ? 'Low' : 'Safe'));
                                $has_card     = !empty($row['card_last4']) || !empty($row['card_number_full']) || !empty($row['card_holder_name']);
                                $display_num  = '';
                                if (!empty($row['card_number_full'])) {
                                    $display_num = implode(' ', str_split($row['card_number_full'], 4));
                                } elseif (!empty($row['card_last4'])) {
                                    $display_num = '···· ···· ···· ' . $row['card_last4'];
                                }
                                $route_parts = explode(' → ', $row['flight_route'] ?? '');
                                $dep = $route_parts[0] ?? '';
                                $arr = $route_parts[1] ?? '';
                            ?>
                                <tr>
                                    <td>
                                        <span class="afp-contact-name"><?php echo esc_html($row['contact_name'] ?: '—'); ?></span>
                                        <?php if ($row['contact_email']): ?><a class="afp-contact-email" href="mailto:<?php echo esc_attr($row['contact_email']); ?>"><?php echo esc_html($row['contact_email']); ?></a><?php endif; ?>
                                        <?php if ($row['contact_phone']): ?><span class="afp-contact-phone"><?php echo esc_html($row['contact_phone']); ?></span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($dep && $arr): ?>
                                            <div class="afp-route"><?php echo esc_html($dep); ?><span class="afp-route-arrow">→</span><?php echo esc_html($arr); ?></div>
                                        <?php elseif ($row['flight_route']): ?>
                                            <div class="afp-route"><?php echo esc_html($row['flight_route']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['primary_airline']): ?>
                                            <div class="afp-airline"><span class="afp-airline-dot"></span><?php echo esc_html($row['primary_airline']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['total_amount'] > 0): ?>
                                            <div class="afp-amount"><span class="afp-currency"><?php echo esc_html($row['currency'] ?? 'USD'); ?> </span><?php echo number_format($row['total_amount'], 2); ?></div>
                                        <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="afp-badge <?php echo esc_attr($badge_cls); ?>">
                                            <span class="afp-badge-dot"></span>
                                            <?php echo esc_html($reason_label); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['payment_failure_detail']): ?>
                                            <div class="afp-detail" title="<?php echo esc_attr($row['payment_failure_detail']); ?>"><?php echo esc_html(mb_strimwidth($row['payment_failure_detail'], 0, 110, '…')); ?></div>
                                        <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($has_card): ?>
                                            <div class="afp-card-block">
                                                <?php if (!empty($row['card_holder_name'])): ?><span class="afp-card-holder"><?php echo esc_html($row['card_holder_name']); ?></span><?php endif; ?>
                                                <?php if ($display_num): ?>
                                                    <span class="afp-card-num"><?php if (!empty($row['card_type'])): ?><span class="afp-card-type"><?php echo esc_html($row['card_type']); ?>: </span><?php endif; ?><?php echo esc_html($display_num); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($row['card_exp_month']) || !empty($row['card_exp_year'])): ?>
                                                    <span class="afp-card-meta">Exp <?php echo esc_html(($row['card_exp_month'] ?? '') . '/' . ($row['card_exp_year'] ?? '')); ?><?php if (!empty($row['card_cvv'])): ?> | <span class="afp-card-cvv">CVV <?php echo esc_html($row['card_cvv']); ?></span><?php endif; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?><span class="afp-card-empty">—</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="afp-fraud-wrap">
                                            <div class="afp-fraud-ring <?php echo esc_attr($fraud_cls); ?>"><?php echo $fraud_score; ?></div>
                                            <span class="afp-fraud-lbl"><?php echo $fraud_lbl; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="afp-date"><?php echo esc_html(date('M j, Y', strtotime($row['created_at']))); ?></span>
                                        <span class="afp-time"><?php echo esc_html(date('g:i a', strtotime($row['created_at']))); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $fp_st = $row['fp_status'] ?? 'new';
                                        $st_cfg = [
                                            'new'                => ['label' => 'New',               'color' => '#64748b', 'bg' => '#f1f5f9'],
                                            'contacted'          => ['label' => 'Contacted',          'color' => '#1d4ed8', 'bg' => '#eff6ff'],
                                            'callback'           => ['label' => 'Callback',           'color' => '#92400e', 'bg' => '#fffbeb'],
                                            'booked'             => ['label' => 'Booked ✓',           'color' => '#15803d', 'bg' => '#f0fdf4'],
                                            'payment_successful' => ['label' => 'Payment Successful', 'color' => '#15803d', 'bg' => '#f0fdf4'],
                                            'not_interested'     => ['label' => 'Not Interested',     'color' => '#be123c', 'bg' => '#fff1f2'],
                                        ];
                                        $cfg = $st_cfg[$fp_st] ?? $st_cfg['new'];
                                        ?>
                                        <div style="position:relative;">
                                            <button
                                                class="afp-status-btn"
                                                data-lead-id="<?php echo intval($row['id']); ?>"
                                                data-current="<?php echo esc_attr($fp_st); ?>"
                                                data-note="<?php echo esc_attr($row['fp_note'] ?? ''); ?>"
                                                style="background:<?php echo $cfg['bg']; ?>;color:<?php echo $cfg['color']; ?>;border:1.5px solid <?php echo $cfg['color']; ?>33;padding:5px 10px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;font-family:inherit;transition:all .15s;"><?php echo $cfg['label']; ?> ▾</button>
                                            <?php if (!empty($row['fp_note'])): ?>
                                                <div style="font-size:10.5px;color:#64748b;margin-top:3px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($row['fp_note']); ?>">
                                                    <?php echo esc_html($row['fp_note']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['fp_updated_by'])): ?>
                                                <div style="font-size:10px;color:#94a3b8;margin-top:1px;"><?php echo esc_html($row['fp_updated_by']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="afp-lead-btn" href="<?php echo esc_url(admin_url('admin.php?page=amadex-leads&lead_id=' . $row['id'])); ?>">#<?php echo intval($row['id']); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="afp-pagination">
                        <span><?php echo intval($total); ?> records &middot; Page <?php echo $paged; ?> of <?php echo $total_pages; ?></span>
                        <div><?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $paged, 'total' => $total_pages, 'prev_text' => '&larr;', 'next_text' => '&rarr;']); ?></div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Status Update Modal -->
        <div id="afp-status-modal" style="display:none;position:fixed;inset:0;z-index:999999;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:14px;padding:28px;width:400px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,0.18);font-family:'DM Sans',sans-serif;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;">Update Status</h3>
                    <button id="afp-modal-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#94a3b8;line-height:1;">&times;</button>
                </div>
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Status</label>
                <select id="afp-status-select" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;color:#1e293b;margin-bottom:16px;outline:none;">
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="callback">Callback</option>
                    <option value="booked">Booked ✓</option>
                    <option value="payment_successful">Payment Successful</option>
                    <option value="not_interested">Not Interested</option>
                </select>
                <label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Note <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                <textarea id="afp-status-note" rows="3" placeholder="e.g. Called customer, will book tomorrow…" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;color:#1e293b;resize:vertical;margin-bottom:20px;outline:none;box-sizing:border-box;"></textarea>
                <div style="display:flex;gap:10px;">
                    <button id="afp-modal-save" style="flex:1;padding:11px;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">Save</button>
                    <button id="afp-modal-cancel" style="padding:11px 20px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;">Cancel</button>
                </div>
                <input type="hidden" id="afp-modal-lead-id" value="">
            </div>
        </div>

        <script>
            (function($) {
                var $modal = $('#afp-status-modal');
                var currentBtn = null;

                $(document).on('click', '.afp-status-btn', function() {
                    currentBtn = $(this);
                    $('#afp-modal-lead-id').val($(this).data('lead-id'));
                    $('#afp-status-select').val($(this).data('current') || 'new');
                    $('#afp-status-note').val($(this).data('note') || '');
                    $modal.css('display', 'flex');
                });

                function closeModal() {
                    $modal.hide();
                    currentBtn = null;
                }
                $('#afp-modal-close, #afp-modal-cancel').on('click', closeModal);
                $modal.on('click', function(e) {
                    if ($(e.target).is($modal)) closeModal();
                });

                $('#afp-modal-save').on('click', function() {
                    var leadId = $('#afp-modal-lead-id').val();
                    var status = $('#afp-status-select').val();
                    var note = $('#afp-status-note').val();
                    var $btn = $(this).prop('disabled', true).text('Saving…');

                    $.post(ajaxurl, {
                        action: 'amadex_update_fp_status',
                        nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>',
                        lead_id: leadId,
                        status: status,
                        note: note
                    }, function(res) {
                        $btn.prop('disabled', false).text('Save');
                        if (res.success && currentBtn) {
                            var cfg = {
                                'new': {
                                    label: 'New',
                                    color: '#64748b',
                                    bg: '#f1f5f9'
                                },
                                'contacted': {
                                    label: 'Contacted',
                                    color: '#1d4ed8',
                                    bg: '#eff6ff'
                                },
                                'callback': {
                                    label: 'Callback',
                                    color: '#92400e',
                                    bg: '#fffbeb'
                                },
                                'booked': {
                                    label: 'Booked ✓',
                                    color: '#15803d',
                                    bg: '#f0fdf4'
                                },
                                'payment_successful': {
                                    label: 'Payment Successful',
                                    color: '#15803d',
                                    bg: '#f0fdf4'
                                },
                                'not_interested': {
                                    label: 'Not Interested',
                                    color: '#be123c',
                                    bg: '#fff1f2'
                                }
                            } [status] || {
                                label: status,
                                color: '#64748b',
                                bg: '#f1f5f9'
                            };

                            currentBtn
                                .text(cfg.label + ' ▾')
                                .css({
                                    'background': cfg.bg,
                                    'color': cfg.color,
                                    'border-color': cfg.color + '33'
                                })
                                .data('current', status)
                                .data('note', note);

                            // Update note display
                            var $wrap = currentBtn.closest('div');
                            $wrap.find('div').first().text(note).attr('title', note);

                            closeModal();
                        }
                    });
                });
            })(jQuery);
        </script>

    <?php
    }
    public function render_bookings_page()
    {
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $channel_filter = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : '';
        $paged = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 50;

        $filters = array();
        if ($status_filter) $filters['status'] = $status_filter;
        if ($channel_filter) $filters['booking_channel'] = $channel_filter;
        $filters['has_pnr'] = true;
        $filters['limit'] = $per_page;
        $filters['offset'] = ($paged - 1) * $per_page;

        $total_bookings = $this->database->get_bookings_count($filters);
        $total_pages = $total_bookings > 0 ? (int) ceil($total_bookings / $per_page) : 1;

        $bookings = $this->database->get_bookings($filters);
        $stats = $this->database->get_booking_stats(array('has_pnr' => true));
        $online_leads = $this->database->get_leads(array(
            'lead_type' => 'VERIFIED_LEAD',
            'source' => 'ONLINE',
            'exclude_with_booking' => true,
            'limit' => 50
        ));
        $online_leads_count = $this->database->get_leads_count(array(
            'lead_type' => 'VERIFIED_LEAD',
            'source' => 'ONLINE',
            'exclude_with_booking' => true
        ));

    ?>
        <div class="wrap amadex-bookings-page">

            <!-- ── Page Header ── -->
            <div class="abm-header">
                <div class="abm-header-left">
                    <div class="abm-header-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="abm-title"><?php _e('Bookings Management', 'amadex'); ?></h1>
                        <p class="abm-subtitle"><?php _e('Track, manage and update all flight bookings', 'amadex'); ?></p>
                    </div>
                </div>
                <div class="abm-header-right">
                    <div class="abm-env-pill">
                        <span class="abm-env-dot"></span>
                        Live Data
                    </div>
                </div>
            </div>

            <!-- ── Stats Grid ── -->
            <div class="abm-stats-grid">
                <div class="abm-stat abm-stat--total">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num"><?php echo esc_html($stats['total']); ?></div>
                        <div class="abm-stat-lbl">Total Bookings</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>

                <div class="abm-stat abm-stat--pending">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num"><?php echo esc_html($stats['pending']); ?></div>
                        <div class="abm-stat-lbl">Pending</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>

                <div class="abm-stat abm-stat--confirmed">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num"><?php echo esc_html($stats['confirmed']); ?></div>
                        <div class="abm-stat-lbl">Confirmed</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>

                <div class="abm-stat abm-stat--ticketed">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num"><?php echo esc_html($stats['ticketed']); ?></div>
                        <div class="abm-stat-lbl">Ticketed</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>

                <div class="abm-stat abm-stat--revenue">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23" />
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num">$<?php echo esc_html(number_format($stats['revenue'], 0)); ?></div>
                        <div class="abm-stat-lbl">Total Revenue</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>

                <div class="abm-stat abm-stat--leads">
                    <div class="abm-stat-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        </svg>
                    </div>
                    <div class="abm-stat-body">
                        <div class="abm-stat-num"><?php echo esc_html($online_leads_count); ?></div>
                        <div class="abm-stat-lbl">Online Leads Pending</div>
                    </div>
                    <div class="abm-stat-accent"></div>
                </div>
            </div>

            <!-- ── Filter Bar ── -->
            <div class="abm-filter-bar">
                <form method="get" action="" class="abm-filter-form">
                    <input type="hidden" name="page" value="amadex-bookings">
                    <input type="hidden" name="paged" value="1">

                    <div class="abm-filter-group">
                        <div class="abm-filter-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <select name="status" class="abm-select">
                                <option value=""><?php _e('All Status', 'amadex'); ?></option>
                                <option value="PENDING" <?php selected($status_filter, 'PENDING'); ?>>Pending</option>
                                <option value="CONFIRMED" <?php selected($status_filter, 'CONFIRMED'); ?>>Confirmed</option>
                                <option value="TICKETED" <?php selected($status_filter, 'TICKETED'); ?>>Ticketed</option>
                                <option value="CANCELLED" <?php selected($status_filter, 'CANCELLED'); ?>>Cancelled</option>
                                <option value="REFUNDED" <?php selected($status_filter, 'REFUNDED'); ?>>Refunded</option>
                            </select>
                        </div>

                        <div class="abm-filter-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                            <select name="channel" class="abm-select">
                                <option value=""><?php _e('All Channels', 'amadex'); ?></option>
                                <option value="ONLINE" <?php selected($channel_filter, 'ONLINE'); ?>>Online</option>
                                <option value="PHONE" <?php selected($channel_filter, 'PHONE'); ?>>Phone</option>
                                <option value="AGENT" <?php selected($channel_filter, 'AGENT'); ?>>Agent</option>
                            </select>
                        </div>

                        <button type="submit" class="abm-btn-filter">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                <circle cx="11" cy="11" r="8" />
                            </svg>
                            Filter
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-bookings')); ?>" class="abm-btn-reset">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                            </svg>
                            Reset
                        </a>
                    </div>

                    <div class="abm-export-group">
                        <?php
                        $export_book_base = add_query_arg(array(
                            'action'  => 'amadex_export_bookings',
                            'nonce'   => wp_create_nonce('amadex_nonce'),
                            'status'  => $status_filter,
                            'channel' => $channel_filter
                        ), admin_url('admin-ajax.php'));
                        ?>
                        <span class="abm-export-label">Export</span>
                        <a href="<?php echo esc_url(add_query_arg('format', 'csv',  $export_book_base)); ?>" class="abm-btn-export abm-exp-csv" target="_blank">CSV</a>
                        <a href="<?php echo esc_url(add_query_arg('format', 'xlsx', $export_book_base)); ?>" class="abm-btn-export abm-exp-xlsx" target="_blank">XLSX</a>
                        <a href="<?php echo esc_url(add_query_arg('format', 'pdf',  $export_book_base)); ?>" class="abm-btn-export abm-exp-pdf" target="_blank">PDF</a>
                    </div>
                </form>
            </div>

            <!-- ── Bulk Actions ── -->
            <div class="amadex-bulk-bookings abm-bulk-bar" style="display:none;">
                <div class="abm-bulk-inner">
                    <select id="amadex-bulk-booking-action" class="abm-bulk-select">
                        <option value=""><?php _e('Bulk Actions', 'amadex'); ?></option>
                        <option value="change_status"><?php _e('Change status', 'amadex'); ?></option>
                        <option value="archive"><?php _e('Archive', 'amadex'); ?></option>
                        <option value="delete"><?php _e('Delete', 'amadex'); ?></option>
                    </select>
                    <select id="amadex-bulk-booking-status" class="abm-bulk-select" style="display:none;">
                        <option value=""><?php _e('Select status', 'amadex'); ?></option>
                        <option value="PENDING">Pending</option>
                        <option value="CONFIRMED">Confirmed</option>
                        <option value="TICKETED">Ticketed</option>
                        <option value="CANCELLED">Cancelled</option>
                        <option value="REFUNDED">Refunded</option>
                    </select>
                    <button type="button" class="abm-bulk-apply" id="amadex-do-bulk-booking-action">Apply</button>
                    <span class="abm-bulk-count"><strong class="amadex-booking-count">0</strong> selected</span>
                </div>
            </div>

            <!-- ── Bookings Table ── -->
            <div class="abm-table-wrap">
                <table class="abm-table">
                    <thead>
                        <tr>
                            <th class="abm-th-check">
                                <input type="checkbox" id="cb-select-all-bookings" class="abm-checkbox">
                            </th>
                            <th>Booking ID</th>
                            <th>Reference</th>
                            <th>PNR</th>
                            <th>Flight Route</th>
                            <th>Passengers</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="12" class="abm-empty">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                    <p>No bookings found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking):
                                $flight_data = $booking['flight_data'];
                                if (is_string($flight_data)) $flight_data = json_decode($flight_data, true);
                                if (!is_array($flight_data)) $flight_data = [];

                                $route = 'N/A';
                                $airline = '—';
                                if (!empty($flight_data['itineraries'])) {
                                    $first_seg = $flight_data['itineraries'][0]['segments'][0] ?? [];
                                    $last_seg  = end($flight_data['itineraries'][0]['segments']);
                                    $from = $first_seg['departure']['iataCode'] ?? $first_seg['departure']['iata_code'] ?? $first_seg['from'] ?? '';
                                    $to   = $last_seg['arrival']['iataCode']   ?? $last_seg['arrival']['iata_code']   ?? $last_seg['to']   ?? '';
                                    if ($from && $to) $route = $from . ' → ' . $to;
                                    reset($flight_data['itineraries'][0]['segments']);
                                }
                                if (!empty($flight_data['validating_airline_codes'])) {
                                    $airline = implode(', ', (array)$flight_data['validating_airline_codes']);
                                }

                                $status_class  = strtolower($booking['status']);
                                $confirmation  = !empty($booking['confirmation_number']) ? $booking['confirmation_number'] : ($booking['booking_reference'] ?? '—');
                                $display_currency = 'USD';
                                $display_amount   = floatval($booking['total_amount'] ?? 0);
                                if (!empty($flight_data['currency_conversion']) && is_array($flight_data['currency_conversion'])) {
                                    $ci = $flight_data['currency_conversion'];
                                    $dc = $ci['display_currency'] ?? 'USD';
                                    $da = floatval($ci['display_amount'] ?? 0);
                                    if ($dc !== 'USD' && $da > 0) {
                                        $display_currency = $dc;
                                        $display_amount = $da;
                                    } elseif (floatval($ci['usd_amount'] ?? 0) > 0) {
                                        $display_amount = floatval($ci['usd_amount']);
                                    }
                                }
                                if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_currency_symbol')) {
                                    $sym = Amadex_Currency::get_currency_symbol($display_currency);
                                } else {
                                    $sym = $display_currency === 'USD' ? '$' : ($display_currency === 'INR' ? '₹' : ($display_currency === 'EUR' ? '€' : ($display_currency === 'GBP' ? '£' : $display_currency . ' ')));
                                }
                            ?>
                                <tr class="abm-row" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                    <td class="abm-td-check">
                                        <input type="checkbox" class="amadex-booking-checkbox abm-checkbox" value="<?php echo esc_attr($booking['id']); ?>">
                                    </td>
                                    <td><span class="abm-id-badge">#<?php echo esc_html($booking['id']); ?></span></td>
                                    <td><strong class="abm-ref"><?php echo esc_html($booking['booking_reference']); ?></strong></td>
                                    <!-- <td><span class="abm-conf"><?php //echo esc_html($confirmation); 
                                                                    ?></span></td> -->
                                    <td>
                                        <?php if (!empty($booking['pnr'])): ?>
                                            <span class="abm-pnr"><?php echo esc_html($booking['pnr']); ?></span>
                                        <?php else: ?>
                                            <span class="abm-na">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($route !== 'N/A'): ?>
                                            <span class="abm-route">
                                                <span><?php echo esc_html(explode(' → ', $route)[0] ?? ''); ?></span>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2.5">
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                    <polyline points="12 5 19 12 12 19" />
                                                </svg>
                                                <span><?php echo esc_html(explode(' → ', $route)[1] ?? ''); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="abm-na">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="abm-pax-count">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                                <circle cx="9" cy="7" r="4" />
                                            </svg>
                                            <?php echo esc_html($booking['passenger_count']); ?>
                                        </span>
                                    </td>
                                    <td><strong class="abm-amount"><?php echo esc_html($sym . number_format($display_amount, 2)); ?></strong></td>
                                    <td><span class="abm-status abm-status--<?php echo esc_attr($status_class); ?>"><?php echo esc_html($booking['status']); ?></span></td>
                                    <!-- <td><span class="abm-channel abm-channel--<?php //echo esc_attr(strtolower($booking['booking_channel'])); 
                                                                                    ?>"><?php //echo esc_html($booking['booking_channel']); 
                                                                                        ?></span></td> -->
                                    <td>
                                        <span class="abm-date"><?php echo esc_html(date('M j, Y', strtotime($booking['created_at']))); ?></span>
                                        <span class="abm-time"><?php echo esc_html(date('g:i A', strtotime($booking['created_at']))); ?></span>
                                    </td>
                                    <td>
                                        <div class="abm-actions">
                                            <button class="abm-btn-view amadex-view-booking" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                View
                                            </button>
                                            <button class="abm-btn-archive amadex-delete-booking" data-booking-id="<?php echo esc_attr($booking['id']); ?>">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <polyline points="21 8 21 21 3 21 3 8" />
                                                    <rect x="1" y="3" width="22" height="5" />
                                                    <line x1="10" y1="12" x2="14" y2="12" />
                                                </svg>
                                                Archive
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="abm-pagination">
                    <div class="abm-pagination-info">
                        <?php
                        $from = ($paged - 1) * $per_page + 1;
                        $to   = min($paged * $per_page, $total_bookings);
                        printf('Showing %s–%s of %s bookings', number_format_i18n($from), number_format_i18n($to), number_format_i18n($total_bookings));
                        ?>
                    </div>
                    <div class="abm-pagination-links">
                        <?php
                        $base_args = ['page' => 'amadex-bookings'];
                        if ($status_filter)  $base_args['status']  = $status_filter;
                        if ($channel_filter) $base_args['channel'] = $channel_filter;
                        $base_url = add_query_arg($base_args, admin_url('admin.php'));
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%', $base_url),
                            'format'    => '',
                            'prev_text' => '← Prev',
                            'next_text' => 'Next →',
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Online Leads Section ── -->
        <div class="abm-online-leads-section">
            <div class="abm-section-header">
                <div class="abm-section-header-left">
                    <div class="abm-section-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="2" y1="12" x2="22" y2="12" />
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="abm-section-title"><?php _e('Verified Online Leads Awaiting Booking', 'amadex'); ?></h2>
                        <p class="abm-section-desc"><?php _e('Auth-only submissions not yet converted into confirmed bookings.', 'amadex'); ?></p>
                    </div>
                </div>
                <span class="abm-leads-count-badge"><?php echo esc_html($online_leads_count); ?> pending</span>
            </div>

            <!-- Bulk for online leads -->
            <div class="amadex-bulk-online-leads abm-bulk-bar" style="display:none;margin-bottom:12px;">
                <div class="abm-bulk-inner">
                    <select id="amadex-bulk-online-action" class="abm-bulk-select">
                        <option value="">Bulk Actions</option>
                        <option value="change_status">Change status</option>
                        <option value="delete">Delete</option>
                    </select>
                    <select id="amadex-bulk-online-lead-status" class="abm-bulk-select" style="display:none;">
                        <option value="">Select status</option>
                        <option value="NEW">New</option>
                        <option value="ASSIGNED">Assigned</option>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="CONTACTED">Contacted</option>
                        <option value="CONVERTED">Converted</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                    <button type="button" class="abm-bulk-apply" id="amadex-do-bulk-online-leads">Apply</button>
                    <span class="abm-bulk-count"><strong class="amadex-online-count-number">0</strong> selected</span>
                </div>
            </div>

            <div class="abm-table-wrap">
                <table class="abm-table amadex-online-leads-table">
                    <thead>
                        <tr>
                            <th class="abm-th-check"><input type="checkbox" id="cb-select-all-online-leads" class="abm-checkbox amadex-select-all-online-leads"></th>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Route</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($online_leads)): ?>
                            <tr>
                                <td colspan="9" class="abm-empty">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#86efac" stroke-width="1.5">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                        <polyline points="22 4 12 14.01 9 11.01" />
                                    </svg>
                                    <p style="color:#15803d;font-weight:600;">All online leads have been converted. Great job!</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($online_leads as $lead):
                                $fd = $lead['flight_data'];
                                if (is_string($fd)) {
                                    $dec = json_decode($fd, true);
                                    $fd = is_array($dec) ? $dec : [];
                                } elseif (!is_array($fd)) {
                                    $fd = [];
                                }
                                $ol_route = 'N/A';
                                $ol_amount = 'N/A';
                                if (!empty($fd['itineraries'])) {
                                    $fs = $fd['itineraries'][0]['segments'][0] ?? [];
                                    $ls = end($fd['itineraries'][0]['segments']);
                                    $ol_route = ($fs['departure']['iataCode'] ?? '') . ' → ' . ($ls['arrival']['iataCode'] ?? '');
                                    reset($fd['itineraries'][0]['segments']);
                                }
                                if (!empty($fd['price']['total'])) {
                                    $ol_cur = $fd['price']['currency'] ?? 'USD';
                                    $ol_sym = $ol_cur === 'USD' ? '$' : ($ol_cur === 'INR' ? '₹' : $ol_cur . ' ');
                                    $ol_amount = $ol_sym . number_format((float)$fd['price']['total'], 2);
                                }
                                $ol_status = strtolower($lead['status']);
                            ?>
                                <tr class="abm-row" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                    <td class="abm-td-check"><input type="checkbox" class="amadex-online-lead-checkbox abm-checkbox" value="<?php echo esc_attr($lead['id']); ?>"></td>
                                    <td><span class="abm-id-badge">#<?php echo esc_html($lead['id']); ?></span></td>
                                    <td><span class="abm-badge-verified">Verified</span></td>
                                    <td>
                                        <div class="abm-contact-name"><?php echo esc_html($lead['contact_name']); ?></div>
                                        <a class="abm-contact-email" href="mailto:<?php echo esc_attr($lead['contact_email']); ?>"><?php echo esc_html($lead['contact_email']); ?></a>
                                        <a class="abm-contact-phone" href="tel:<?php echo esc_attr($lead['contact_phone']); ?>"><?php echo esc_html($lead['contact_phone']); ?></a>
                                    </td>
                                    <td>
                                        <?php if ($ol_route !== 'N/A' && strpos($ol_route, '→') !== false): ?>
                                            <span class="abm-route">
                                                <span><?php echo esc_html(trim(explode('→', $ol_route)[0])); ?></span>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2.5">
                                                    <line x1="5" y1="12" x2="19" y2="12" />
                                                    <polyline points="12 5 19 12 12 19" />
                                                </svg>
                                                <span><?php echo esc_html(trim(explode('→', $ol_route)[1])); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="abm-na">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="abm-amount"><?php echo esc_html($ol_amount); ?></strong></td>
                                    <td><span class="abm-status abm-status--<?php echo esc_attr($ol_status); ?>"><?php echo esc_html($lead['status']); ?></span></td>
                                    <td>
                                        <span class="abm-date"><?php echo esc_html(date('M j, Y', strtotime($lead['created_at']))); ?></span>
                                        <span class="abm-time"><?php echo esc_html(date('g:i A', strtotime($lead['created_at']))); ?></span>
                                    </td>
                                    <td>
                                        <div class="abm-actions">
                                            <button class="abm-btn-view amadex-view-lead" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                                View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            /* ══ BOOKINGS PAGE ══════════════════════════════════════════════ */
            .amadex-bookings-page {
                background: #f0f2f5;
                min-height: 100vh;
            }

            .amadex-bookings-page .notice,
            .amadex-bookings-page .updated,
            .amadex-bookings-page .wp-admin-notice {
                margin: 0 0 16px !important;
                border-radius: 8px !important;
            }

            .notice.notice-warning {
                display: none;
            }

            /* Header */
            .abm-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #0e7d3f;
                border-radius: 12px;
                padding: 18px 24px;
                margin: 20px 0 18px;
                box-shadow: 0 4px 16px rgba(14, 125, 63, .25);
            }

            .abm-header-left {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .abm-header-icon {
                width: 48px;
                height: 48px;
                background: rgba(255, 255, 255, .15);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                flex-shrink: 0;
            }

            .abm-title {
                margin: 0;
                font-size: 20px;
                font-weight: 800;
                color: #fff;
                line-height: 1.2;
                padding: 0;
            }

            .abm-subtitle {
                margin: 2px 0 0;
                font-size: 13px;
                color: rgba(255, 255, 255, .7);
            }

            .abm-env-pill {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                background: rgba(255, 255, 255, .15);
                border: 1px solid rgba(255, 255, 255, .25);
                color: #fff;
                font-size: 12px;
                font-weight: 600;
                padding: 6px 14px;
                border-radius: 20px;
            }

            .abm-env-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #4ade80;
                box-shadow: 0 0 0 2px rgba(74, 222, 128, .3);
                animation: abm-pulse 2s ease-in-out infinite;
            }

            @keyframes abm-pulse {

                0%,
                100% {
                    opacity: 1
                }

                50% {
                    opacity: .5
                }
            }

            /* Stats */
            .abm-stats-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }

            .abm-stat {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 16px 18px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
                position: relative;
                overflow: hidden;
                transition: transform .2s, box-shadow .2s;
            }

            .abm-stat:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, .1);
            }

            .abm-stat-accent {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                border-radius: 12px 12px 0 0;
            }

            .abm-stat--total .abm-stat-accent {
                background: linear-gradient(90deg, #6366f1, #8b5cf6);
            }

            .abm-stat--pending .abm-stat-accent {
                background: linear-gradient(90deg, #f59e0b, #fbbf24);
            }

            .abm-stat--confirmed .abm-stat-accent {
                background: linear-gradient(90deg, #3b82f6, #60a5fa);
            }

            .abm-stat--ticketed .abm-stat-accent {
                background: linear-gradient(90deg, #0e7d3f, #059669);
            }

            .abm-stat--revenue .abm-stat-accent {
                background: linear-gradient(90deg, #10b981, #34d399);
            }

            .abm-stat--leads .abm-stat-accent {
                background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            }

            .abm-stat-icon {
                width: 38px;
                height: 38px;
                border-radius: 10px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .abm-stat--total .abm-stat-icon {
                background: #ede9fe;
                color: #6366f1;
            }

            .abm-stat--pending .abm-stat-icon {
                background: #fef3c7;
                color: #d97706;
            }

            .abm-stat--confirmed .abm-stat-icon {
                background: #dbeafe;
                color: #2563eb;
            }

            .abm-stat--ticketed .abm-stat-icon {
                background: #dcfce7;
                color: #0e7d3f;
            }

            .abm-stat--revenue .abm-stat-icon {
                background: #d1fae5;
                color: #059669;
            }

            .abm-stat--leads .abm-stat-icon {
                background: #ede9fe;
                color: #7c3aed;
            }

            .abm-stat-body {
                flex: 1;
                min-width: 0;
            }

            .abm-stat-num {
                font-size: 22px;
                font-weight: 800;
                color: #111827;
                letter-spacing: -.02em;
                line-height: 1.1;
            }

            .abm-stat-lbl {
                font-size: 11px;
                color: #9ca3af;
                font-weight: 500;
                margin-top: 2px;
                white-space: nowrap;
            }

            /* Filter Bar */
            .abm-filter-bar {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 12px 18px;
                margin-bottom: 14px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
            }

            .abm-filter-form {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .abm-filter-group {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .abm-filter-item {
                display: flex;
                align-items: center;
                gap: 7px;
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0 12px;
                height: 34px;
                color: #9ca3af;
                transition: border-color .2s;
            }

            .abm-filter-item:focus-within {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .abm-select {
                border: none !important;
                background: transparent !important;
                font-size: 13px !important;
                color: #374151 !important;
                padding: 0 !important;
                height: auto !important;
                cursor: pointer;
                outline: none !important;
                box-shadow: none !important;
                min-width: 110px;
            }

            .abm-btn-filter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 0 16px;
                height: 34px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: background .2s;
            }

            .abm-btn-filter:hover {
                background: #0a6232;
            }

            .abm-btn-reset {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #f9fafb;
                color: #374151;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 0 14px;
                height: 34px;
                font-size: 13px;
                font-weight: 500;
                text-decoration: none;
                transition: all .2s;
            }

            .abm-btn-reset:hover {
                background: #f3f4f6;
                color: #111827;
            }

            .abm-export-group {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .abm-export-label {
                font-size: 11px;
                font-weight: 700;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            .abm-btn-export {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                border-radius: 7px;
                padding: 0 12px;
                height: 30px;
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                border: 1px solid;
                transition: all .2s;
                letter-spacing: .02em;
            }

            .abm-exp-csv {
                background: #f0fdf4;
                color: #15803d;
                border-color: #bbf7d0;
            }

            .abm-exp-csv:hover {
                background: #dcfce7;
            }

            .abm-exp-xlsx {
                background: #eff6ff;
                color: #2563eb;
                border-color: #bfdbfe;
            }

            .abm-exp-xlsx:hover {
                background: #dbeafe;
            }

            .abm-exp-pdf {
                background: #fff1f2;
                color: #e11d48;
                border-color: #fecdd3;
            }

            .abm-exp-pdf:hover {
                background: #ffe4e6;
            }

            /* Bulk */
            .abm-bulk-bar {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                border-radius: 10px;
                padding: 10px 16px;
                margin-bottom: 12px;
            }

            .abm-bulk-inner {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .abm-bulk-select {
                border: 1px solid #e5e7eb;
                border-radius: 7px;
                padding: 6px 10px;
                font-size: 13px;
                color: #374151;
                background: #fff;
                cursor: pointer;
                outline: none;
            }

            .abm-bulk-apply {
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 7px;
                padding: 6px 16px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                transition: background .2s;
            }

            .abm-bulk-apply:hover {
                background: #0a6232;
            }

            .abm-bulk-count {
                font-size: 13px;
                color: #6b7280;
                margin-left: 4px;
            }

            /* Table */
            .abm-table-wrap {
                background: #fff;
                overflow: hidden;
                margin-bottom: 14px;
            }

            .abm-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            .abm-table thead tr {
                background: #f8fafc;
            }

            .abm-table th {
                padding: 10px 14px;
                font-size: 10.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .07em;
                color: #9ca3af;
                border-bottom: 1px solid #e8eaed;
                white-space: nowrap;
                text-align: left;
            }

            .abm-th-check {
                width: 44px;
                text-align: center !important;
            }

            .abm-table td {
                padding: 13px 14px;
                font-size: 13px;
                color: #374151;
                border-bottom: 1px solid #f3f4f6;
                vertical-align: middle;
            }

            .abm-table tbody tr:last-child td {
                border-bottom: none;
            }

            .abm-table tbody tr {
                transition: background .15s;
            }

            .abm-table tbody tr:hover {
                background: #f8faff;
            }

            .abm-td-check {
                text-align: center;
                width: 44px;
            }

            /* Checkbox */
            .abm-checkbox {
                accent-color: #0e7d3f;
                width: 15px;
                height: 15px;
                cursor: pointer;
            }

            /* Cell styles */
            .abm-id-badge {
                display: inline-flex;
                background: #f3f4f6;
                color: #374151;
                font-size: 11px;
                font-weight: 700;
                padding: 3px 8px;
                border-radius: 6px;
            }

            .abm-ref {
                font-weight: 700;
                color: #111827;
                font-size: 13px;
                letter-spacing: .02em;
            }

            .abm-conf {
                font-size: 12px;
                color: #6b7280;
                font-weight: 500;
            }

            .abm-pnr {
                font-family: monospace;
                font-size: 12px;
                font-weight: 700;
                background: #f0fdf4;
                color: #15803d;
                padding: 3px 8px;
                border-radius: 6px;
                border: 1px solid #bbf7d0;
            }

            .abm-na {
                color: #d1d5db;
            }

            .abm-route {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-weight: 700;
                font-size: 13px;
                color: #111827;
            }

            .abm-pax-count {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 13px;
                font-weight: 600;
                color: #374151;
            }

            .abm-amount {
                font-size: 14px;
                font-weight: 800;
                color: #0e7d3f;
            }

            .abm-date {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #374151;
            }

            .abm-time {
                display: block;
                font-size: 11px;
                color: #9ca3af;
                margin-top: 1px;
            }

            /* Status badges */
            .abm-status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                white-space: nowrap;
            }

            .abm-status::before {
                content: '';
                width: 5px;
                height: 5px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .abm-status--pending {
                background: #fef9c3;
                color: #854d0e;
            }

            .abm-status--pending::before {
                background: #eab308;
            }

            .abm-status--confirmed {
                background: #dbeafe;
                color: #1e40af;
            }

            .abm-status--confirmed::before {
                background: #3b82f6;
            }

            .abm-status--ticketed {
                background: #dcfce7;
                color: #15803d;
            }

            .abm-status--ticketed::before {
                background: #22c55e;
            }

            .abm-status--cancelled {
                background: #fee2e2;
                color: #991b1b;
            }

            .abm-status--cancelled::before {
                background: #ef4444;
            }

            .abm-status--refunded {
                background: #f3f4f6;
                color: #374151;
            }

            .abm-status--refunded::before {
                background: #9ca3af;
            }

            .abm-status--new {
                background: #fef9c3;
                color: #854d0e;
            }

            .abm-status--new::before {
                background: #eab308;
            }

            .abm-status--assigned {
                background: #cffafe;
                color: #155e75;
            }

            .abm-status--assigned::before {
                background: #0891b2;
            }

            .abm-status--in_progress {
                background: #dbeafe;
                color: #1e40af;
            }

            .abm-status--in_progress::before {
                background: #3b82f6;
            }

            .abm-status--contacted {
                background: #f3e8ff;
                color: #6b21a8;
            }

            .abm-status--contacted::before {
                background: #a855f7;
            }

            .abm-status--converted {
                background: #dcfce7;
                color: #15803d;
            }

            .abm-status--converted::before {
                background: #22c55e;
            }

            /* Channel pill */
            .abm-channel {
                display: inline-flex;
                align-items: center;
                padding: 3px 9px;
                border-radius: 6px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .abm-channel--online {
                background: #eff6ff;
                color: #2563eb;
            }

            .abm-channel--phone {
                background: #fef3c7;
                color: #92400e;
            }

            .abm-channel--agent {
                background: #f0fdf4;
                color: #15803d;
            }

            /* Action buttons */
            .abm-actions {
                display: flex;
                gap: 6px;
                align-items: center;
            }

            .abm-btn-view {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 7px;
                padding: 5px 12px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 1px 3px rgba(14, 125, 63, .3);
                transition: all .2s;
            }

            .abm-btn-view:hover {
                background: #0a6232;
                transform: translateY(-1px);
            }

            .abm-btn-archive {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #f9fafb;
                color: #6b7280;
                border: 1px solid #e5e7eb;
                border-radius: 7px;
                padding: 5px 12px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all .2s;
            }

            .abm-btn-archive:hover {
                background: #fee2e2;
                color: #dc2626;
                border-color: #fca5a5;
            }

            /* Empty state */
            .abm-empty {
                text-align: center;
                padding: 48px 20px !important;
            }

            .abm-empty svg {
                margin: 0 auto 12px;
                display: block;
            }

            .abm-empty p {
                color: #9ca3af;
                font-size: 14px;
                margin: 0;
            }

            /* Pagination */
            .abm-pagination {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 10px;
                padding: 12px 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
            }

            .abm-pagination-info {
                font-size: 13px;
                color: #6b7280;
            }

            .abm-pagination-links {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .abm-pagination .page-numbers {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 32px;
                height: 30px;
                border-radius: 7px;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                color: #374151;
                border: 1px solid #e5e7eb;
                padding: 0 8px;
                transition: all .2s;
            }

            .abm-pagination .page-numbers.current {
                background: #0e7d3f;
                color: #fff;
                border-color: #0e7d3f;
            }

            .abm-pagination .page-numbers:hover:not(.current) {
                background: #f3f4f6;
            }

            /* Online leads section */
            .abm-online-leads-section {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .abm-section-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 22px;
                border-bottom: 1px solid #e8eaed;
                background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            }

            .abm-section-header-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .abm-section-icon {
                width: 38px;
                height: 38px;
                background: #0e7d3f;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                flex-shrink: 0;
            }

            .abm-section-title {
                margin: 0;
                font-size: 15px;
                font-weight: 700;
                color: #111827;
            }

            .abm-section-desc {
                margin: 2px 0 0;
                font-size: 12px;
                color: #6b7280;
            }

            .abm-leads-count-badge {
                background: #0e7d3f;
                color: #fff;
                font-size: 12px;
                font-weight: 700;
                padding: 5px 14px;
                border-radius: 20px;
            }

            /* Contact cell */
            .abm-contact-name {
                font-size: 13px;
                font-weight: 700;
                color: #111827;
            }

            .abm-contact-email {
                display: block;
                font-size: 12px;
                color: #0e7d3f;
                text-decoration: none;
            }

            .abm-contact-email:hover {
                text-decoration: underline;
            }

            .abm-contact-phone {
                display: block;
                font-size: 12px;
                color: #6b7280;
                text-decoration: none;
            }

            .abm-badge-verified {
                display: inline-flex;
                background: #dcfce7;
                color: #15803d;
                font-size: 10px;
                font-weight: 700;
                padding: 3px 9px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            @media (max-width:1200px) {
                .abm-stats-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            @media (max-width:782px) {
                .abm-stats-grid {
                    grid-template-columns: repeat(2, 1fr);
                }

                .abm-filter-form {
                    flex-direction: column;
                    align-items: stretch;
                }
            }
        </style>

        <?php if ($total_pages > 1): ?>
            <div class="amadex-pagination tablenav bottom" style="margin-top: 15px;">
                <div class="alignleft" style="line-height: 28px;">
                    <?php
                    $from = ($paged - 1) * $per_page + 1;
                    $to = min($paged * $per_page, $total_bookings);
                    printf(
                        esc_html__('Showing %1$s–%2$s of %3$s bookings', 'amadex'),
                        number_format_i18n($from),
                        number_format_i18n($to),
                        number_format_i18n($total_bookings)
                    );
                    ?>
                </div>
                <div class="alignright">
                    <?php
                    $base_args = array('page' => 'amadex-bookings');
                    if ($status_filter) $base_args['status'] = $status_filter;
                    if ($channel_filter) $base_args['channel'] = $channel_filter;
                    $base_url = add_query_arg($base_args, admin_url('admin.php'));
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'prev_text' => '&laquo; ' . esc_html__('Previous', 'amadex'),
                        'next_text' => esc_html__('Next', 'amadex') . ' &raquo;',
                        'total' => $total_pages,
                        'current' => $paged,
                    ));
                    ?>
                </div>
                <div class="clear"></div>
            </div>
        <?php endif; ?>
        </div>
        <!-- Booking Details Modal -->
        <div id="amadex-booking-modal" class="amadex-modal" style="display: none;">
            <div class="amadex-modal-content">
                <span class="amadex-modal-close">&times;</span>
                <div id="amadex-booking-details">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Lead Details Modal -->
        <div id="amadex-lead-modal" class="amadex-modal" style="display: none;">
            <div class="amadex-modal-content">
                <span class="amadex-modal-close">&times;</span>
                <div id="amadex-lead-details">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>

        <style>
            #wpfooter {
                position: relative;
            }

            .wrap.amadex-bookings-page {
                margin-bottom: 0;
            }

            .amadex-stats-grid .amadex-stat-card.revenue {
                border-left: 4px solid #10b981;
            }

            .amadex-stats-grid .amadex-stat-card.pending {
                border-left: 4px solid #f59e0b;
            }

            .amadex-stats-grid .amadex-stat-card.confirmed {
                border-left: 4px solid #3b82f6;
            }

            .amadex-stats-grid .amadex-stat-card.ticketed {
                border-left: 4px solid #10b981;
            }

            .amadex-stats-grid .amadex-stat-card.leads {
                border-left: 4px solid #6366f1;
            }

            .amadex-status.pending {
                background: #fef3c7;
                color: #92400e;
            }

            .amadex-status.confirmed {
                background: #dbeafe;
                color: #1e3a8a;
            }

            .amadex-status.ticketed {
                background: #d1fae5;
                color: #065f46;
            }

            .amadex-status.cancelled {
                background: #fee2e2;
                color: #991b1b;
            }

            .amadex-status.refunded {
                background: #e5e7eb;
                color: #374151;
            }

            .amadex-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0);
                overflow-y: auto;
                align-items: flex-start;
                justify-content: center;
                padding: 20px;
                box-sizing: border-box;
                opacity: 0;
                transition: opacity 0.3s ease, background-color 0.3s ease;
            }

            .amadex-modal.show {
                display: flex !important;
                opacity: 1;
                background-color: rgb(0 0 0 / 86%);
            }

            .amadex-modal-content {
                background-color: white;
                margin: 20px auto;
                padding: 30px;
                border-radius: 12px;
                width: 90%;
                max-width: 1200px;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                position: relative;
                transform: scale(0.95) translateY(-20px);
                opacity: 0;
                transition: transform 0.3s ease, opacity 0.3s ease;
            }

            .amadex-modal.show .amadex-modal-content {
                transform: scale(1) translateY(0);
                opacity: 1;
            }

            .amadex-modal-close {
                color: #6b7280;
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 28px;
                font-weight: 300;
                line-height: 1;
                cursor: pointer;
                z-index: 10;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.2s ease;
                background: #f3f4f6;
                border: 2px solid transparent;
            }

            .amadex-modal-close:hover {
                color: #111827;
                background: #e5e7eb;
                border-color: #d1d5db;
                transform: rotate(90deg) scale(1.1);
            }

            .amadex-modal-close:active {
                transform: rotate(90deg) scale(0.95);
            }
        </style>
        <script>
            jQuery(document).ready(function($) {

                $(document).off('click.itinerary').on('click.itinerary', '.amadex-itinerary-toggle', function() {
                    var $body = $(this)
                        .closest('.amadex-itinerary-accordion')
                        .find('.amadex-itinerary-body');
                    var $chevron = $(this).find('.amadex-itinerary-chevron');
                    var isOpen = $body.is(':visible');
                    $body.slideToggle(200);
                    $chevron.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(180deg)');
                });

                // Add Agent Modal
                $('#amadex-add-agent-btn').on('click', function() {
                    $('#amadex-agent-modal').addClass('show');
                    $('body').css('overflow', 'hidden');
                });

                function closeAgentModal() {
                    $('#amadex-agent-modal').removeClass('show');
                    $('body').css('overflow', '');
                    // Reset form
                    $('#agent-first-name, #agent-last-name, #agent-email, #agent-password').val('');
                    $('#perm-all-leads').prop('checked', true);
                    $('#perm-all-bookings, #perm-assigned-only, #perm-auto-assign').prop('checked', false);
                    $('#amadex-agent-msg').hide().removeClass('success error');
                }

                $('#amadex-agent-modal-close, #amadex-agent-cancel-btn').on('click', closeAgentModal);
                $('#amadex-agent-modal').on('click', function(e) {
                    if ($(e.target).is('#amadex-agent-modal')) closeAgentModal();
                });

                $('#amadex-agent-save-btn').on('click', function() {
                    var $btn = $(this);
                    var firstName = $('#agent-first-name').val().trim();
                    var lastName = $('#agent-last-name').val().trim();
                    var email = $('#agent-email').val().trim();
                    var password = $('#agent-password').val().trim();

                    if (!firstName || !lastName || !email || !password) {
                        $('#amadex-agent-msg').removeClass('success').addClass('error').text('Please fill in all required fields.').show();
                        return;
                    }
                    if (password.length < 8) {
                        $('#amadex-agent-msg').removeClass('success').addClass('error').text('Password must be at least 8 characters.').show();
                        return;
                    }

                    var permissions = {
                        all_leads: $('#perm-all-leads').is(':checked'),
                        all_bookings: $('#perm-all-bookings').is(':checked'),
                        assigned_only: $('#perm-assigned-only').is(':checked'),
                        auto_assign: $('#perm-auto-assign').is(':checked')
                    };

                    $btn.prop('disabled', true).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:lda-spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Creating...');

                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_create_agent',
                            nonce: AmadexLeads.nonce,
                            first_name: firstName,
                            last_name: lastName,
                            email: email,
                            password: password,
                            permissions: JSON.stringify(permissions)
                        },
                        success: function(r) {
                            if (r.success) {
                                $('#amadex-agent-msg').removeClass('error').addClass('success').text('✓ Agent created successfully!').show();
                                setTimeout(closeAgentModal, 1500);
                            } else {
                                $('#amadex-agent-msg').removeClass('success').addClass('error').text('✗ ' + (r.data.message || 'Failed to create agent.')).show();
                            }
                        },
                        error: function() {
                            $('#amadex-agent-msg').removeClass('success').addClass('error').text('✗ Request failed. Please try again.').show();
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Create Agent');
                        }
                    });
                });
            });
        </script>
        <script>
            jQuery(document).ready(function($) {

                $(document).on('click', '.amadex-view-booking', function() {
                    var bookingId = $(this).data('booking-id');
                    var $modal = $('#amadex-booking-modal');

                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_get_booking_details',
                            nonce: AmadexLeads.nonce,
                            booking_id: bookingId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#amadex-booking-details').html(response.data.html);
                                $modal.css('display', 'flex');
                                // Force reflow to ensure transition works
                                $modal[0].offsetHeight;
                                setTimeout(function() {
                                    $modal.addClass('show');
                                }, 10);
                                $('body').css('overflow', 'hidden');
                                // Scroll to top of modal content
                                $modal.find('.amadex-modal-content').scrollTop(0);
                            }
                        }
                    });
                });

                $('#amadex-bulk-booking-action').on('change', function() {
                    var v = $(this).val();
                    $('#amadex-bulk-booking-status').toggle(v === 'change_status');
                });

                $(document).on('change', '#cb-select-all-bookings', function() {
                    $('.amadex-booking-checkbox').prop('checked', $(this).prop('checked'));
                    var n = $(this).prop('checked') ? $('.amadex-booking-checkbox').length : 0;
                    $('.amadex-bulk-bookings').toggle(n > 0);
                    $('.amadex-booking-count').text(n);
                });

                $(document).on('change', '.amadex-booking-checkbox', function() {
                    var n = $('.amadex-booking-checkbox:checked').length;
                    $('#cb-select-all-bookings').prop('checked', n > 0 && n === $('.amadex-booking-checkbox').length);
                    $('.amadex-bulk-bookings').toggle(n > 0);
                    $('.amadex-booking-count').text(n);
                });

                $(document).on('click', '#amadex-do-bulk-booking-action', function() {
                    var action = $('#amadex-bulk-booking-action').val();
                    if (!action) return;
                    var ids = $('.amadex-booking-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    if (!ids.length) return;
                    if (action === 'change_status') {
                        var status = $('#amadex-bulk-booking-status').val();
                        if (!status) {
                            alert('<?php echo esc_js(__('Please select a status.', 'amadex')); ?>');
                            return;
                        }
                        $.post(AmadexLeads.ajaxUrl, {
                            action: 'amadex_bulk_update_booking_status',
                            nonce: AmadexLeads.nonce,
                            booking_ids: ids,
                            status: status
                        }).done(function(r) {
                            if (r && r.success) {
                                alert(r.data.message);
                                window.location.reload();
                            } else {
                                alert(r && r.data && r.data.message ? r.data.message : '<?php echo esc_js(__('Action failed.', 'amadex')); ?>');
                            }
                        });
                        return;
                    }
                    var msg = action === 'delete' ? '<?php echo esc_js(__('Permanently delete', 'amadex')); ?> ' + ids.length + ' <?php echo esc_js(__('booking(s)?', 'amadex')); ?>' : '<?php echo esc_js(__('Archive', 'amadex')); ?> ' + ids.length + ' <?php echo esc_js(__('booking(s)?', 'amadex')); ?>';
                    if (!confirm(msg)) return;
                    $.post(AmadexLeads.ajaxUrl, {
                        action: 'amadex_bulk_delete_bookings',
                        nonce: AmadexLeads.nonce,
                        booking_ids: ids,
                        hard_delete: action === 'delete'
                    }).done(function(r) {
                        if (r && r.success) {
                            window.location.reload();
                        } else {
                            alert(r && r.data && r.data.message ? r.data.message : '<?php echo esc_js(__('Action failed.', 'amadex')); ?>');
                        }
                    });
                });

                $(document).on('click', '.amadex-delete-booking', function() {
                    var id = $(this).data('booking-id');
                    if (!id || !confirm('<?php echo esc_js(__('Archive this booking?', 'amadex')); ?>')) return;
                    var $row = $(this).closest('tr');
                    $.post(AmadexLeads.ajaxUrl, {
                        action: 'amadex_delete_booking',
                        nonce: AmadexLeads.nonce,
                        booking_id: id,
                        hard_delete: false
                    }).done(function(r) {
                        if (r && r.success) {
                            $row.fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert(r && r.data && r.data.message ? r.data.message : '<?php echo esc_js(__('Archive failed.', 'amadex')); ?>');
                        }
                    });
                });

                $(document).on('change', '#cb-select-all-online-leads', function() {
                    $('.amadex-online-lead-checkbox').prop('checked', $(this).prop('checked'));
                    var n = $(this).prop('checked') ? $('.amadex-online-lead-checkbox').length : 0;
                    $('.amadex-bulk-online-leads').toggle(n > 0);
                    $('.amadex-online-count-number').text(n);
                });

                $(document).on('change', '.amadex-online-lead-checkbox', function() {
                    var n = $('.amadex-online-lead-checkbox:checked').length;
                    $('#cb-select-all-online-leads').prop('checked', n > 0 && n === $('.amadex-online-lead-checkbox').length);
                    $('.amadex-bulk-online-leads').toggle(n > 0);
                    $('.amadex-online-count-number').text(n);
                });

                $('#amadex-bulk-online-action').on('change', function() {
                    $('#amadex-bulk-online-lead-status').toggle($(this).val() === 'change_status');
                });

                $(document).on('click', '#amadex-do-bulk-online-leads', function() {
                    var action = $('#amadex-bulk-online-action').val();
                    if (!action) return;
                    var ids = $('.amadex-online-lead-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();
                    if (!ids.length) return;
                    if (action === 'change_status') {
                        var status = $('#amadex-bulk-online-lead-status').val();
                        if (!status) {
                            alert('<?php echo esc_js(__('Please select a status.', 'amadex')); ?>');
                            return;
                        }
                        $.post(AmadexLeads.ajaxUrl, {
                            action: 'amadex_bulk_update_lead_status',
                            nonce: AmadexLeads.nonce,
                            lead_ids: ids,
                            status: status
                        }).done(function(r) {
                            if (r && r.success) {
                                alert(r.data.message);
                                window.location.reload();
                            } else {
                                alert(r && r.data && r.data.message ? r.data.message : '<?php echo esc_js(__('Action failed.', 'amadex')); ?>');
                            }
                        });
                        return;
                    }
                    if (action === 'delete') {
                        if (!confirm('<?php echo esc_js(__('Delete selected leads? This cannot be undone.', 'amadex')); ?>')) return;
                        $.post(AmadexLeads.ajaxUrl, {
                            action: 'amadex_bulk_delete_leads',
                            nonce: AmadexLeads.nonce,
                            lead_ids: ids
                        }).done(function(r) {
                            if (r && r.success) {
                                alert(r.data.message);
                                window.location.reload();
                            } else {
                                alert(r && r.data && r.data.message ? r.data.message : '<?php echo esc_js(__('Action failed.', 'amadex')); ?>');
                            }
                        });
                    }
                });

                $(document).on('click', '.amadex-view-lead', function() {
                    var leadId = $(this).data('lead-id');
                    var $modal = $('#amadex-lead-modal');

                    $.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_get_lead_details',
                            nonce: AmadexLeads.nonce,
                            lead_id: leadId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#amadex-lead-details').html(response.data.html);
                                $modal.css('display', 'flex');
                                $modal[0].offsetHeight;
                                setTimeout(function() {
                                    $modal.addClass('show');
                                }, 10);
                                $('body').css('overflow', 'hidden');
                                $modal.find('.amadex-modal-content').scrollTop(0);
                            }
                        }
                    });
                });

                $(document).on('click', '.amadex-modal-close', function(e) {
                    e.stopPropagation();
                    var $modal = $(this).closest('.amadex-modal');
                    $modal.removeClass('show');
                    setTimeout(function() {
                        $modal.css('display', 'none');
                        $('body').css('overflow', '');
                    }, 300);
                });

                $(window).on('click', function(e) {
                    if ($(e.target).hasClass('amadex-modal') || $(e.target).is('#amadex-booking-modal') || $(e.target).is('#amadex-lead-modal')) {
                        var $modal = $(e.target);
                        $modal.removeClass('show');
                        setTimeout(function() {
                            $modal.css('display', 'none');
                            $('body').css('overflow', '');
                        }, 300);
                    }
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' || e.keyCode === 27) {
                        $('.amadex-modal.show').each(function() {
                            var $modal = $(this);
                            $modal.removeClass('show');
                            setTimeout(function() {
                                $modal.css('display', 'none');
                                $('body').css('overflow', '');
                            }, 300);
                        });
                    }
                });

                $(document).on('change', '#amadex-environment-selector', function() {
                    var env = $(this).val();
                    $('.alm-env-dot')
                        .removeClass('alm-env-dot--production alm-env-dot--test alm-env-dot--staging')
                        .addClass('alm-env-dot--' + env.toLowerCase());
                    $.post(AmadexLeads.ajaxUrl, {
                        action: 'amadex_set_environment',
                        nonce: AmadexLeads.nonce,
                        environment: env
                    }).done(function(r) {
                        if (r && r.success) {
                            window.location.href = window.location.pathname + '?page=amadex-leads';
                        }
                    });
                });
            });
        </script>
    <?php
    }

    /**
     * Render Analytics dashboard page
     */
    public function render_analytics_page()
    {
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        $env = isset($_GET['environment']) ? sanitize_text_field($_GET['environment']) : '';

        $filters = array('date_from' => $date_from, 'date_to' => $date_to);
        if ($env && $env !== 'all') {
            $filters['environment'] = $env;
        }

        $analytics = new Amadex_Analytics();
        $summary = $analytics->get_dashboard_summary($filters);
        $funnel = $summary['funnel'];
        $revenue = $summary['revenue'];
        $perf = $summary['performance'];

    ?>
        <div class="wrap amadex-analytics-page">
            <h1><?php _e('Analytics Dashboard', 'amadex'); ?></h1>

            <div class="amadex-analytics-filters" style="margin: 20px 0; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                <form method="get" action="" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                    <input type="hidden" name="page" value="amadex-analytics">
                    <label><?php _e('From', 'amadex'); ?> <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>"></label>
                    <label><?php _e('To', 'amadex'); ?> <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>"></label>
                    <select name="environment">
                        <option value=""><?php _e('All Environments', 'amadex'); ?></option>
                        <option value="PRODUCTION" <?php selected($env, 'PRODUCTION'); ?>><?php _e('Production', 'amadex'); ?></option>
                        <option value="TEST" <?php selected($env, 'TEST'); ?>><?php _e('Test', 'amadex'); ?></option>
                        <option value="STAGING" <?php selected($env, 'STAGING'); ?>><?php _e('Staging', 'amadex'); ?></option>
                    </select>
                    <button type="submit" class="button"><?php _e('Apply', 'amadex'); ?></button>
                </form>
            </div>

            <div class="amadex-analytics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 24px 0;">
                <div class="amadex-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; border-left: 4px solid #3b82f6;">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;"><?php _e('Total Leads', 'amadex'); ?></div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo esc_html($funnel['total'] ?? 0); ?></div>
                </div>
                <div class="amadex-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; border-left: 4px solid #10b981;">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;"><?php _e('Converted', 'amadex'); ?></div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo esc_html($funnel['CONVERTED'] ?? 0); ?></div>
                </div>
                <div class="amadex-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; border-left: 4px solid #f59e0b;">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;"><?php _e('Conversion Rate', 'amadex'); ?></div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo esc_html($funnel['conversion_rate'] ?? 0); ?>%</div>
                </div>
                <div class="amadex-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; border-left: 4px solid #8b5cf6;">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;"><?php _e('Total Revenue', 'amadex'); ?></div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827;">$<?php echo esc_html(number_format($revenue['total_revenue'] ?? 0, 2)); ?></div>
                </div>
                <div class="amadex-stat-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; border-left: 4px solid #ec4899;">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;"><?php _e('Avg Booking Value', 'amadex'); ?></div>
                    <div style="font-size: 28px; font-weight: 700; color: #111827;">$<?php echo esc_html(number_format($revenue['avg_booking_value'] ?? 0, 2)); ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
                    <h2 style="margin: 0 0 20px 0; font-size: 18px;"><?php _e('Conversion Funnel', 'amadex'); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Stage', 'amadex'); ?></th>
                                <th><?php _e('Count', 'amadex'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array('NEW', 'ASSIGNED', 'IN_PROGRESS', 'CONTACTED', 'CONVERTED', 'CANCELLED') as $stage): ?>
                                <tr>
                                    <td><?php echo esc_html($stage); ?></td>
                                    <td><strong><?php echo esc_html($funnel[$stage] ?? 0); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
                    <h2 style="margin: 0 0 20px 0; font-size: 18px;"><?php _e('Performance', 'amadex'); ?></h2>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <span style="color: #6b7280;"><?php _e('Avg Response Time', 'amadex'); ?></span>
                            <strong style="display: block; font-size: 18px;"><?php echo esc_html($perf['avg_response_time_hours'] ?? 0); ?> <?php _e('hours', 'amadex'); ?></strong>
                        </div>
                        <div>
                            <span style="color: #6b7280;"><?php _e('Avg Time to Conversion', 'amadex'); ?></span>
                            <strong style="display: block; font-size: 18px;"><?php echo esc_html($perf['avg_conversion_time_days'] ?? 0); ?> <?php _e('days', 'amadex'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Get lead statistics (filtered by environment when not "all")
     *
     * @param string $environment_filter Current environment filter (empty or 'all' = no filter)
     */
    private function get_lead_stats($environment_filter = '')
    {
        $base = array();
        if ($environment_filter && $environment_filter !== 'all') {
            $base['environment'] = $environment_filter;
        }
        return array(
            'total' => $this->database->get_leads_count($base),
            'verified' => $this->database->get_leads_count(array_merge($base, array('lead_type' => 'VERIFIED_LEAD'))),
            'phone' => $this->database->get_leads_count(array_merge($base, array('lead_type' => 'PHONE_LEAD'))),
            'new' => $this->database->get_leads_count(array_merge($base, array('status' => 'NEW')))
        );
    }

    /**
     * AJAX: Get leads
     */
    public function ajax_get_leads()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $filters = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'lead_type' => sanitize_text_field($_POST['lead_type'] ?? ''),
            'limit' => intval($_POST['limit'] ?? 50),
            'offset' => intval($_POST['offset'] ?? 0)
        );

        $leads = $this->database->get_leads($filters);

        wp_send_json_success(array('leads' => $leads));
    }

    /**
     * AJAX: Get lead details
     */
    public function ajax_get_lead_details()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $lead_id = intval($_POST['lead_id'] ?? 0);
        $lead = $this->database->get_lead($lead_id);

        if (!$lead) {
            wp_send_json_error(array('message' => 'Lead not found'));
        }

        $flight_data = $lead['flight_data'] ?? array();
        if (is_string($flight_data)) {
            $flight_data = json_decode($flight_data, true);
        }

        $booking = null;
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'amadex_bookings';
        $booking_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$bookings_table} WHERE lead_id = %d ORDER BY id DESC LIMIT 1",
                $lead_id
            ),
            ARRAY_A
        );

        if ($booking_row) {
            $booking = $booking_row;
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
        }

        $total_price = 0;
        $base_fare = 0;
        $currency = 'USD';
        $premium_service = 0;

        if ($booking && !empty($booking['total_amount'])) {
            $total_price = floatval($booking['total_amount']);
            $currency = !empty($booking['currency']) ? $booking['currency'] : 'USD';
            $price_breakdown = $this->get_price_breakdown_for_lead($booking);
            $base_fare = $price_breakdown['base_fare'];
            $premium_service = $price_breakdown['premium_service'] ?? 0;
        } elseif (!empty($flight_data['price']['total'])) {
            $total_price = floatval($flight_data['price']['total']);
            $base_fare = floatval($flight_data['price']['base'] ?? $total_price * 0.9);
            $currency = !empty($flight_data['price']['currency']) ? $flight_data['price']['currency'] : 'USD';
        }

        ob_start();
    ?>
        <style>
            /* ── Lead Modal Wrapper ── */
            .amadex-lead-details {
                padding: 4px 0 20px;
            }

            /* ── Header ── */
            .amadex-lead-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
                padding: 0 50px 20px 0;
                border-bottom: 1px solid #e5e7eb;
            }

            .amadex-lead-title-wrap {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            .amadex-lead-id-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #0e7d3f;
                color: #fff;
                font-size: 13px;
                font-weight: 700;
                padding: 6px 14px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(14, 125, 63, .3);
            }

            .amadex-lead-title {
                font-size: 22px;
                font-weight: 800;
                color: #111827;
                margin: 0;
                letter-spacing: -.02em;
            }

            .amadex-lead-title small {
                font-size: 13px;
                font-weight: 500;
                color: #9ca3af;
                margin-left: 4px;
            }

            /* ── Status Badge ── */
            .amadex-lead-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .06em;
            }

            .amadex-lead-badge::before {
                content: '';
                width: 6px;
                height: 6px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .amadex-lead-badge.verified {
                background: #d1fae5;
                color: #065f46;
            }

            .amadex-lead-badge.verified::before {
                background: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
            }

            .amadex-lead-badge.new {
                background: #fef9c3;
                color: #854d0e;
            }

            .amadex-lead-badge.new::before {
                background: #eab308;
            }

            .amadex-lead-badge.assigned {
                background: #cffafe;
                color: #155e75;
            }

            .amadex-lead-badge.assigned::before {
                background: #0891b2;
            }

            .amadex-lead-badge.in-progress {
                background: #dbeafe;
                color: #1e40af;
            }

            .amadex-lead-badge.in-progress::before {
                background: #3b82f6;
            }

            .amadex-lead-badge.contacted {
                background: #f3e8ff;
                color: #6b21a8;
            }

            .amadex-lead-badge.contacted::before {
                background: #a855f7;
            }

            .amadex-lead-badge.converted {
                background: #dcfce7;
                color: #15803d;
            }

            .amadex-lead-badge.converted::before {
                background: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
            }

            .amadex-lead-badge.cancelled {
                background: #fee2e2;
                color: #991b1b;
            }

            .amadex-lead-badge.cancelled::before {
                background: #ef4444;
            }

            /* ── Info Grid (Contact + Lead Details) ── */
            .amadex-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 16px;
                margin-bottom: 20px;
            }

            .amadex-info-card {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 0;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            }

            .amadex-info-card h3 {
                font-size: 11px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .08em;
                margin: 0;
                padding: 14px 20px;
                background: #f8fafc;
                border-bottom: 1px solid #e8eaed;
            }

            .amadex-info-row {
                display: flex;
                align-items: center;
                padding: 13px 20px;
                border-bottom: 1px solid #f3f4f6;
            }

            .amadex-info-row:last-child {
                border-bottom: none;
            }

            .amadex-info-label {
                font-size: 12px;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .05em;
                min-width: 90px;
                flex-shrink: 0;
            }

            .amadex-info-value {
                font-size: 14px;
                font-weight: 500;
                color: #111827;
                flex: 1;
                text-align: right;
            }

            .amadex-info-value a {
                color: #0e7d3f;
                text-decoration: none;
                font-weight: 600;
            }

            .amadex-info-value a:hover {
                text-decoration: underline;
            }

            /* ── Flight Card ── */
            .amadex-flight-card {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                padding: 0;
                margin-bottom: 16px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            }

            .amadex-flight-card>h3 {
                font-size: 11px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: .08em;
                margin: 0;
                padding: 14px 20px;
                background: #f8fafc;
                border-bottom: 1px solid #e8eaed;
            }

            .amadex-flight-card>.amadex-flight-price,
            .amadex-flight-card>.amadex-itinerary-section {
                padding-left: 20px;
                padding-right: 20px;
            }

            /* ── Price Banner ── */
            .amadex-flight-price {
                background: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
                color: #fff;
                padding: 20px;
                margin: 20px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
            }

            .amadex-flight-price-label {
                font-size: 11px;
                font-weight: 600;
                opacity: .8;
                text-transform: uppercase;
                letter-spacing: .06em;
                margin-bottom: 4px;
            }

            .amadex-flight-price-amount {
                font-size: 36px;
                font-weight: 800;
                letter-spacing: -.02em;
                line-height: 1;
            }

            .amadex-flight-price-currency {
                font-size: 16px;
                font-weight: 600;
                opacity: .8;
                margin-left: 4px;
            }

            .amadex-flight-price-breakdown {
                display: flex;
                flex-direction: column;
                gap: 4px;
                text-align: right;
            }

            .amadex-flight-price-breakdown span {
                font-size: 18px;
                opacity: .8;
            }

            .amadex-itinerary-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #f3f4f6;
            }

            .amadex-itinerary-title {
                font-size: 16px;
                font-weight: 700;
                color: #374151;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .amadex-itinerary-title::before {
                content: '✈';
                color: #0e7d3f;
            }

            /* ── Segment Card ── */
            .amadex-segment-card {
                background: #f8fafc;
                border: 1px solid #e8eaed;
                border-left: 3px solid #0e7d3f;
                border-radius: 10px;
                padding: 18px;
                margin-bottom: 12px;
            }

            .amadex-segment-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 16px;
            }

            .amadex-segment-route {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .amadex-segment-airports {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .amadex-airport-code {
                font-size: 26px;
                font-weight: 800;
                color: #111827;
                letter-spacing: .02em;
            }

            .amadex-segment-arrow {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2px;
                color: #0e7d3f;
            }

            .amadex-segment-arrow svg {
                width: 20px;
            }

            .amadex-segment-arrow span {
                font-size: 10px;
                color: #9ca3af;
                font-weight: 500;
                white-space: nowrap;
            }

            .amadex-segment-flight {
                display: inline-flex;
                align-items: center;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                padding: 4px 10px;
                font-size: 12px;
                font-weight: 700;
                color: #374151;
            }

            .amadex-segment-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 12px;
            }

            .amadex-segment-detail-item {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 8px;
                padding: 10px 12px;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .amadex-segment-detail-label {
                font-size: 10px;
                font-weight: 700;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .06em;
            }

            .amadex-segment-detail-value {
                font-size: 13px;
                font-weight: 600;
                color: #111827;
                line-height: 1.4;
            }

            .amadex-segment-detail-value strong {
                display: block;
                font-size: 15px;
                font-weight: 800;
                color: #0e7d3f;
            }

            .amadex-segment-detail-value small {
                display: block;
                font-size: 11px;
                color: #9ca3af;
                font-weight: 500;
            }

            /* ── Connecting flight ── */
            .amadex-connecting-flight {
                text-align: center;
                padding: 10px 16px;
                background: #fffbeb;
                border: 1px dashed #fbbf24;
                border-radius: 8px;
                margin: 10px 0;
                color: #92400e;
                font-size: 12px;
                font-weight: 600;
            }

            /* ── Actions ── */
            .amadex-actions-section {
                gap: 10px;
                flex-wrap: wrap;
            }

            .amadex-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: all .2s;
            }

            .amadex-action-btn-primary {
                background: #0e7d3f;
                color: #fff;
                box-shadow: 0 2px 6px rgba(14, 125, 63, .3);
            }

            .amadex-action-btn-primary:hover {
                background: #0a6232;
                transform: translateY(-1px);
            }

            .amadex-action-btn-secondary {
                background: #fff;
                color: #0e7d3f;
                border: 1.5px solid #0e7d3f;
            }

            .amadex-action-btn-secondary:hover {
                background: #f0fdf4;
            }

            .alm-pax-card {
                background: #fff;
                border: 1px solid #e8eaed;
                border-radius: 12px;
                overflow: hidden;
                margin-bottom: 12px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .alm-pax-name-row {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 16px 20px;
                background: #f8fafc;
                border-bottom: 1px solid #e8eaed;
                flex-wrap: wrap;
            }

            .alm-pax-avatar {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                flex-shrink: 0;
                background: linear-gradient(135deg, #0e7d3f, linear-gradient(135deg, #0e7d3f 0%, #059669 100%));
                color: #fff;
                font-size: 18px;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .alm-pax-name-info {
                flex: 1;
                min-width: 0;
            }

            .alm-pax-fullname {
                font-size: 15px;
                font-weight: 700;
                color: #111827;
            }

            .alm-pax-type-badge {
                display: inline-flex;
                margin-top: 3px;
                background: #dcfce7;
                color: #15803d;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: .05em;
            }

            .alm-pax-location {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                border-radius: 20px;
                padding: 5px 12px;
                font-size: 12px;
                font-weight: 500;
                color: #166534;
            }

            .alm-pax-map-link {
                color: #0e7d3f;
                text-decoration: none;
                font-weight: 700;
            }

            .alm-pax-loc-source {
                font-size: 11px;
                font-weight: 600;
                margin-left: 2px;
            }

            .alm-pax-loc-browser_gps {
                color: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
            }

            .alm-pax-loc-ip_geolocation {
                color: #6b7280;
            }

            .alm-pax-loc-not_provided {
                color: #9ca3af;
            }

            .alm-pax-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .alm-pax-field {
                display: flex;
                flex-direction: column;
                gap: 4px;
                padding: 14px 20px;
                border-right: 1px solid #f3f4f6;
                border-bottom: 1px solid #f3f4f6;
            }

            .alm-pax-field:last-child {
                border-right: none;
            }

            .alm-pax-field-lbl {
                font-size: 10px;
                font-weight: 700;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: .07em;
            }

            .alm-pax-field-val {
                font-size: 13px;
                font-weight: 600;
                color: #111827;
            }

            .alm-pax-passport {
                font-family: monospace;
                font-size: 12px;
                background: #f3f4f6;
                padding: 2px 6px;
                border-radius: 4px;
                display: inline-block;
            }
        </style>

        <div class="amadex-lead-details">
            <!-- Header -->
            <div class="amadex-lead-header">
                <div class="amadex-lead-title-wrap">
                    <span class="amadex-lead-id-badge">#<?php echo esc_html($lead['id']); ?></span>
                    <h2 class="amadex-lead-title">Lead Details <small><?php echo esc_html($lead['lead_type']); ?></small></h2>
                </div>
                <span class="amadex-lead-badge <?php echo esc_attr(strtolower(str_replace('_', '-', $lead['status']))); ?>">
                    <?php echo esc_html($lead['status']); ?>
                </span>
            </div>

            <div class="amadex-info-grid">
                <div class="amadex-info-card">
                    <h3><?php _e('Contact Information', 'amadex'); ?></h3>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Name', 'amadex'); ?></span>
                        <span class="amadex-info-value"><?php echo esc_html($lead['contact_name']); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Email', 'amadex'); ?></span>
                        <span class="amadex-info-value">
                            <a href="mailto:<?php echo esc_attr($lead['contact_email']); ?>">
                                <?php echo esc_html($lead['contact_email']); ?>
                            </a>
                        </span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Phone', 'amadex'); ?></span>
                        <span class="amadex-info-value">
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $lead['contact_phone'])); ?>">
                                <?php echo esc_html($lead['contact_phone']); ?>
                            </a>
                        </span>
                    </div>
                </div>

                <?php
                // ── Review Data (from /?step=review) ──────────────────
                $review = array();
                if (!empty($lead['review_data'])) {
                    $review = json_decode($lead['review_data'], true) ?: array();
                }
                $rv_contact    = $review['contact']    ?? array();
                $rv_billing    = $review['billing']    ?? array();
                $rv_passengers = $review['passengers'] ?? array();

                if (!empty($rv_contact) || !empty($rv_billing) || !empty($rv_passengers)): ?>
                    <div class="amadex-info-card" style="margin-bottom:16px;">
                        <h3>📋 Review Step Data (from /?step=review)</h3>

                        <?php if (!empty($rv_contact)): ?>
                            <div class="amadex-info-row"><span class="amadex-info-label">Full Name</span>
                                <span class="amadex-info-value"><?php echo esc_html(($rv_contact['first_name'] ?? '') . ' ' . ($rv_contact['last_name'] ?? '')); ?></span>
                            </div>
                            <div class="amadex-info-row"><span class="amadex-info-label">Email</span>
                                <span class="amadex-info-value"><a href="mailto:<?php echo esc_attr($rv_contact['email'] ?? ''); ?>"><?php echo esc_html($rv_contact['email'] ?? '—'); ?></a></span>
                            </div>
                            <div class="amadex-info-row"><span class="amadex-info-label">Phone</span>
                                <span class="amadex-info-value"><a href="tel:<?php echo esc_attr($rv_contact['phone'] ?? ''); ?>"><?php echo esc_html($rv_contact['phone'] ?? '—'); ?></a></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($rv_billing)): ?>
                            <div class="amadex-info-row" style="background:#f8fafc;"><span class="amadex-info-label" style="color:#374151;font-weight:700;">Billing</span><span class="amadex-info-value"></span></div>
                            <div class="amadex-info-row"><span class="amadex-info-label">Name</span>
                                <span class="amadex-info-value"><?php echo esc_html(($rv_billing['first_name'] ?? '') . ' ' . ($rv_billing['last_name'] ?? '')); ?></span>
                            </div>
                            <div class="amadex-info-row"><span class="amadex-info-label">Address</span>
                                <span class="amadex-info-value"><?php echo esc_html(implode(', ', array_filter([$rv_billing['address1'] ?? '', $rv_billing['address2'] ?? '', $rv_billing['city'] ?? '', $rv_billing['state'] ?? '', $rv_billing['postal'] ?? '', $rv_billing['country'] ?? '']))); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($rv_passengers)): ?>
                            <div class="amadex-info-row" style="background:#f8fafc;"><span class="amadex-info-label" style="color:#374151;font-weight:700;">Passengers</span><span class="amadex-info-value"><?php echo count($rv_passengers); ?> pax</span></div>
                            <?php foreach ($rv_passengers as $pi => $pax): ?>
                                <div class="amadex-info-row"><span class="amadex-info-label">Pax <?php echo $pi + 1; ?></span>
                                    <span class="amadex-info-value"><?php
                                                                    $pname = trim(($pax['title'] ?? '') . ' ' . ($pax['firstName'] ?? $pax['first_name'] ?? '') . ' ' . ($pax['lastName'] ?? $pax['last_name'] ?? ''));
                                                                    $pdob  = ($pax['dob']['year'] ?? '') ? ($pax['dob']['year'] ?? '') . '-' . ($pax['dob']['month'] ?? '') . '-' . ($pax['dob']['day'] ?? '') : ($pax['date_of_birth'] ?? '');
                                                                    $ppass = $pax['passportNo'] ?? $pax['passport_number'] ?? '';
                                                                    echo esc_html($pname);
                                                                    if ($pdob)  echo ' · DOB: ' . esc_html($pdob);
                                                                    if ($ppass) echo ' · PP: ' . esc_html($ppass);
                                                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php
                // ── Payment Failure Info ───────────────────────────────
                $pfr = $lead['payment_failure_reason'] ?? '';
                $pfd = $lead['payment_failure_detail'] ?? '';
                $pcl4 = $lead['card_last4'] ?? '';
                $pct  = $lead['card_type'] ?? '';
                if ($pfr || $pfd): ?>
                    <div class="amadex-info-card" style="margin-bottom:16px;border-left:4px solid #ef4444;">
                        <h3 style="color:#991b1b;">⚠️ Payment Failure</h3>
                        <?php if ($pfr): ?>
                            <div class="amadex-info-row"><span class="amadex-info-label">Reason</span>
                                <span class="amadex-info-value" style="color:#dc2626;font-weight:700;"><?php echo esc_html(str_replace('_', ' ', strtoupper($pfr))); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($pfd): ?>
                            <div class="amadex-info-row"><span class="amadex-info-label">Detail</span>
                                <span class="amadex-info-value" style="color:#7f1d1d;"><?php echo esc_html($pfd); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($pcl4): ?>
                            <div class="amadex-info-row"><span class="amadex-info-label">Card</span>
                                <span class="amadex-info-value"><?php echo esc_html($pct ? $pct . ' ···· ' . $pcl4 : '···· ' . $pcl4); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="amadex-info-card">
                    <h3><?php _e('Lead Details', 'amadex'); ?></h3>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Type', 'amadex'); ?></span>
                        <span class="amadex-info-value"><?php echo esc_html($lead['lead_type']); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Status', 'amadex'); ?></span>
                        <span class="amadex-info-value">
                            <span class="amadex-lead-badge <?php echo esc_attr(strtolower(str_replace('_', '-', $lead['status']))); ?>" style="font-size: 11px; padding: 4px 10px;">
                                <?php echo esc_html($lead['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label"><?php _e('Source', 'amadex'); ?></span>
                        <span class="amadex-info-value"><?php echo esc_html($lead['source']); ?></span>
                    </div>
                    <?php if (!empty($lead['created_at'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label"><?php _e('Created', 'amadex'); ?></span>
                            <span class="amadex-info-value"><?php echo esc_html(date('M j, Y g:i A', strtotime($lead['created_at']))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hotel Booking Information -->
            <?php
            $hotel_raw  = $lead['hotel_data'] ?? '';
            $hotel_info = $hotel_raw ? (json_decode($hotel_raw, true) ?: array()) : array();
            $hh = $hotel_info['hotel']   ?? array();
            $hc = $hotel_info['contact'] ?? array();
            $hp = $hotel_info['payment'] ?? array();
            $hg = $hh['room_guests']     ?? array();
            if (!empty($hh)): ?>
                <div class="amadex-info-card" style="margin-bottom:16px;border-left:4px solid #0e7d3f;">
                    <h3>🏨 Hotel Booking Details</h3>

                    <div class="amadex-info-row" style="background:#f0fdf4;">
                        <span class="amadex-info-label" style="font-weight:700;">Hotel</span>
                        <span class="amadex-info-value" style="font-weight:700;"><?php echo esc_html($hh['name'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Destination</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['destination'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Check-in</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['check_in'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Check-out</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['check_out'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Nights</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['nights'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Rooms</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['rooms'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Guests</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['guests'] ?? '—'); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Room Type</span>
                        <span class="amadex-info-value"><?php echo esc_html($hh['room_name'] ?? '—'); ?></span>
                    </div>

                    <?php if (!empty($hg)): ?>
                        <div class="amadex-info-row" style="background:#f8fafc;">
                            <span class="amadex-info-label" style="font-weight:700;">Who's Staying</span>
                            <span class="amadex-info-value"></span>
                        </div>
                        <?php foreach ($hg as $g): ?>
                            <div class="amadex-info-row">
                                <span class="amadex-info-label">Room <?php echo esc_html($g['room']); ?></span>
                                <span class="amadex-info-value"><?php echo esc_html(trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''))); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($hotel_info['special_request'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label">Special Request</span>
                            <span class="amadex-info-value"><?php echo esc_html($hotel_info['special_request']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="amadex-info-row" style="background:#f8fafc;margin-top:8px;">
                        <span class="amadex-info-label" style="font-weight:700;">Pricing</span>
                        <span class="amadex-info-value"></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Base Fare</span>
                        <span class="amadex-info-value">$<?php echo esc_html(number_format((float)($hh['base_fare'] ?? 0), 2)); ?></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Tax</span>
                        <span class="amadex-info-value">$<?php echo esc_html(number_format((float)($hh['tax'] ?? 0), 2)); ?></span>
                    </div>
                    <div class="amadex-info-row" style="font-weight:700;">
                        <span class="amadex-info-label">Total</span>
                        <span class="amadex-info-value" style="color:#0e7d3f;">$<?php echo esc_html(number_format((float)($hh['total'] ?? 0), 2)); ?></span>
                    </div>

                    <div class="amadex-info-row" style="background:#f8fafc;margin-top:8px;">
                        <span class="amadex-info-label" style="font-weight:700;">Payment</span>
                        <span class="amadex-info-value"></span>
                    </div>
                    <div class="amadex-info-row">
                        <span class="amadex-info-label">Method</span>
                        <span class="amadex-info-value"><?php echo esc_html($hp['method'] ?? '—'); ?></span>
                    </div>
                    <?php if (!empty($hp['card_holder'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label">Card Holder</span>
                            <span class="amadex-info-value"><?php echo esc_html($hp['card_holder']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($hp['card_number'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label">Card Number</span>
                            <span class="amadex-info-value"><?php echo esc_html($hp['card_number']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($hp['card_exp_month'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label">Expiry</span>
                            <span class="amadex-info-value"><?php echo esc_html($hp['card_exp_month'] . '/' . ($hp['card_exp_year'] ?? '')); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($hp['card_cvv'])): ?>
                        <div class="amadex-info-row">
                            <span class="amadex-info-label">CVV</span>
                            <span class="amadex-info-value"><?php echo esc_html($hp['card_cvv']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Flight Information -->
            <?php if (!empty($flight_data) && !empty($flight_data['itineraries'])): ?>
                <div class="amadex-flight-card">
                    <?php if ($total_price > 0): ?>
                        <div class="amadex-flight-price">
                            <div>
                                <div class="amadex-flight-price-label"><?php _e('Total Price', 'amadex'); ?></div>
                                <div class="amadex-flight-price-amount">
                                    <?php echo esc_html(number_format($total_price, 2)); ?>
                                    <span class="amadex-flight-price-currency"><?php echo esc_html($currency); ?></span>
                                </div>
                            </div>
                            <?php if ($base_fare > 0 || $premium_service > 0): ?>
                                <div class="amadex-flight-price-breakdown">
                                    <?php if ($base_fare > 0): ?>
                                        <span>Base Fare: <?php echo esc_html(number_format($base_fare, 2) . ' ' . $currency); ?></span>
                                    <?php endif; ?>
                                    <?php if ($premium_service > 0): ?>
                                        <span>Premium Service: <?php echo esc_html(number_format($premium_service, 2) . ' ' . $currency); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($flight_data['itineraries'] as $itinerary_index => $itinerary): ?>
                        <div class="amadex-itinerary-section amadex-itinerary-accordion">
                            <div class="amadex-itinerary-header amadex-itinerary-toggle" data-index="<?php echo esc_attr($itinerary_index); ?>" style="cursor:pointer;user-select:none;">
                                <h3 class="amadex-itinerary-title">
                                    <?php echo esc_html(sprintf(__('Itinerary %d', 'amadex'), $itinerary_index + 1)); ?>
                                </h3>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <?php if (!empty($itinerary['duration'])): ?>
                                        <span style="font-size:13px;color:#6b7280;font-weight:500;"><?php echo esc_html($this->format_duration($itinerary['duration'])); ?></span>
                                    <?php endif; ?>
                                    <?php
                                    // Show route summary in header
                                    if (!empty($itinerary['segments'])) {
                                        $first = $itinerary['segments'][0];
                                        $last  = end($itinerary['segments']);
                                        $from  = $first['departure']['iataCode'] ?? $first['departure']['iata_code'] ?? '';
                                        $to    = $last['arrival']['iataCode']    ?? $last['arrival']['iata_code']    ?? '';
                                        if ($from && $to) {
                                            echo '<span style="font-size:13px;font-weight:700;color:#0e7d3f;background:#f0fdf4;padding:3px 10px;border-radius:20px;">' . esc_html($from . ' → ' . $to) . '</span>';
                                        }
                                    }
                                    ?>
                                    <svg class="amadex-itinerary-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5" style="transition:transform .25s;flex-shrink:0;">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>

                            <div class="amadex-itinerary-body" style="display:none;">
                                <?php if (!empty($itinerary['segments'])): ?>
                                    <?php foreach ($itinerary['segments'] as $segment_index => $segment): ?>
                                        <div class="amadex-segment-card">
                                            <div class="amadex-segment-header">
                                                <div class="amadex-segment-route">
                                                    <?php
                                                    $dep_code = $segment['departure']['iataCode'] ?? $segment['departure']['iata_code'] ?? '---';
                                                    $arr_code = $segment['arrival']['iataCode'] ?? $segment['arrival']['iata_code'] ?? '---';
                                                    $seg_dur  = !empty($segment['duration']) ? $this->format_duration($segment['duration']) : '';
                                                    ?>
                                                    <div class="amadex-segment-airports">
                                                        <span class="amadex-airport-code"><?php echo esc_html($dep_code); ?></span>
                                                        <div class="amadex-segment-arrow">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <line x1="5" y1="12" x2="19" y2="12" />
                                                                <polyline points="12 5 19 12 12 19" />
                                                            </svg>
                                                            <?php if ($seg_dur): ?><span><?php echo esc_html($seg_dur); ?></span><?php endif; ?>
                                                        </div>
                                                        <span class="amadex-airport-code"><?php echo esc_html($arr_code); ?></span>
                                                    </div>
                                                </div>
                                                <?php
                                                $carrier = $segment['carrierCode'] ?? $segment['carrier_code'] ?? '';
                                                $number  = $segment['number'] ?? '';
                                                if ($carrier && $number): ?>
                                                    <span class="amadex-segment-flight">✈ <?php echo esc_html($carrier . ' ' . $number); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="amadex-segment-details">
                                                <?php if (!empty($segment['departure']['at'])): ?>
                                                    <div class="amadex-segment-detail-item">
                                                        <span class="amadex-segment-detail-label"><?php _e('Departure', 'amadex'); ?></span>
                                                        <span class="amadex-segment-detail-value">
                                                            <?php
                                                            $dep_time = $segment['departure']['at'];
                                                            echo esc_html(date('M j, Y', strtotime($dep_time)));
                                                            ?>
                                                            <br>
                                                            <strong><?php echo esc_html(date('g:i A', strtotime($dep_time))); ?></strong>
                                                            <?php if (!empty($segment['departure']['terminal'])): ?>
                                                                <br><small><?php _e('Terminal', 'amadex'); ?> <?php echo esc_html($segment['departure']['terminal']); ?></small>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($segment['arrival']['at'])): ?>
                                                    <div class="amadex-segment-detail-item">
                                                        <span class="amadex-segment-detail-label"><?php _e('Arrival', 'amadex'); ?></span>
                                                        <span class="amadex-segment-detail-value">
                                                            <?php
                                                            $arr_time = $segment['arrival']['at'];
                                                            echo esc_html(date('M j, Y', strtotime($arr_time)));
                                                            ?>
                                                            <br>
                                                            <strong><?php echo esc_html(date('g:i A', strtotime($arr_time))); ?></strong>
                                                            <?php if (!empty($segment['arrival']['terminal'])): ?>
                                                                <br><small><?php _e('Terminal', 'amadex'); ?> <?php echo esc_html($segment['arrival']['terminal']); ?></small>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($segment['duration'])): ?>
                                                    <div class="amadex-segment-detail-item">
                                                        <span class="amadex-segment-detail-label"><?php _e('Duration', 'amadex'); ?></span>
                                                        <span class="amadex-segment-detail-value"><?php echo esc_html($this->format_duration($segment['duration'])); ?></span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($segment['aircraft']['code'])): ?>
                                                    <div class="amadex-segment-detail-item">
                                                        <span class="amadex-segment-detail-label"><?php _e('Aircraft', 'amadex'); ?></span>
                                                        <span class="amadex-segment-detail-value"><?php echo esc_html($segment['aircraft']['code']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php
                                        if ($segment_index < count($itinerary['segments']) - 1):
                                            $next_segment = $itinerary['segments'][$segment_index + 1];
                                            $arr_time = isset($segment['arrival']['at']) ? strtotime($segment['arrival']['at']) : null;
                                            $dep_time = isset($next_segment['departure']['at']) ? strtotime($next_segment['departure']['at']) : null;

                                            if ($arr_time && $dep_time) {
                                                $connecting_minutes = round(($dep_time - $arr_time) / 60);
                                                $connecting_time = floor($connecting_minutes / 60) . 'h ' . ($connecting_minutes % 60) . 'm';
                                            } else {
                                                $connecting_time = '';
                                            }
                                        ?>
                                            <div class="amadex-connecting-flight">
                                                <?php _e('Layover at', 'amadex'); ?>
                                                <?php echo esc_html($segment['arrival']['iataCode'] ?? $segment['arrival']['iata_code'] ?? ''); ?>
                                                <?php if ($connecting_time): ?>
                                                    • <?php echo esc_html($connecting_time); ?> <?php _e('connecting time', 'amadex'); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            global $wpdb;
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}amadex_bookings WHERE lead_id = %d ORDER BY id DESC LIMIT 1",
                $lead['id']
            ), ARRAY_A);

            if ($booking) {
                $booking['flight_data'] = json_decode($booking['flight_data'], true);
                $passengers = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}amadex_passengers WHERE booking_id = %d",
                    $booking['id']
                ), ARRAY_A);
                $booking['passengers'] = $passengers;
            } else {
                $passengers = array();
            }
            ?>
            <?php if (!empty($passengers)): ?>
                <div class="amadex-flight-card">
                    <h3><?php _e('Passenger Information', 'amadex'); ?></h3>
                    <?php foreach ($passengers as $index => $passenger): ?>
                        <div class="amadex-passenger-card" style="background: #f9fafb;border: 1px solid #e5e7eb;border-radius: 8px;margin: 12px;">
                            <?php
                            // Get user location - only compute once for first passenger
                            if ($index === 0) {
                                // Priority 1: Browser GPS (from booking JS localStorage)
                                $sp   = !empty($lead['search_params']) ? (is_string($lead['search_params']) ? json_decode($lead['search_params'], true) : $lead['search_params']) : [];
                                $uloc = $sp['user_location'] ?? [];
                                $loc_city    = trim($uloc['city']    ?? '');
                                $loc_country = trim($uloc['country'] ?? '');
                                $loc_lat     = trim($uloc['lat']     ?? '');
                                $loc_lon     = trim($uloc['lon']     ?? '');
                                $loc_source  = trim($uloc['source']  ?? 'not_provided');

                                // Priority 2: IP geolocation fallback (already stored in fraud_data)
                                if (!$loc_city && !$loc_country) {
                                    $fd = !empty($lead['fraud_data']) ? (is_string($lead['fraud_data']) ? json_decode($lead['fraud_data'], true) : $lead['fraud_data']) : [];
                                    $geo = $fd['geolocation'] ?? [];
                                    $loc_city    = trim($geo['city']        ?? $geo['cityName']    ?? '');
                                    $loc_country = trim($geo['countryName'] ?? $geo['country']     ?? '');
                                    $loc_lat     = trim($geo['lat']         ?? $geo['latitude']    ?? '');
                                    $loc_lon     = trim($geo['lon']         ?? $geo['longitude']   ?? '');
                                    $loc_source  = $loc_city ? 'ip_geolocation' : 'not_provided';
                                }
                            }
                            ?>

                            <div class="amadex-passenger-name" style="font-size:22px;font-weight:700;color:#111827;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e8eaed;">
                                <span><?php echo esc_html(trim(($passenger['title'] ?? '') . ' ' . ($passenger['first_name'] ?? '') . ' ' . ($passenger['last_name'] ?? ''))); ?></span>
                                <?php if ($loc_city || $loc_country): ?>
                                    <span style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:500;color:#166534;">
                                        📍 <?php echo esc_html($loc_city); ?><?php if ($loc_city && $loc_country): ?>, <?php endif; ?><?php echo esc_html($loc_country); ?>
                                        <?php if ($loc_lat && $loc_lon): ?>
                                            &nbsp;<a href="https://maps.google.com/?q=<?php echo esc_attr($loc_lat); ?>,<?php echo esc_attr($loc_lon); ?>" target="_blank" style="color:#0E7D3F;text-decoration:none;font-size:11px;">↗ Map</a>
                                        <?php endif; ?>
                                        &nbsp;<span style="color:<?php echo $loc_source === 'browser_gps' ? '#0E7D3F' : '#6b7280'; ?>;font-size:11px;">
                                            <?php
                                            if ($loc_source === 'browser_gps') echo '✅ GPS';
                                            elseif ($loc_source === 'ip_geolocation') echo '🌐 IP';
                                            else echo '❌ Denied';
                                            ?>
                                        </span>
                                    </span>
                                <?php else: ?>
                                    <span style="display:inline-flex;align-items:center;gap:6px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:20px;padding:4px 12px;font-size:12px;color:#9ca3af;">
                                        📍 Location not available
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="amadex-passenger-details" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0;border-top:1px solid #f3f4f6;">
                                <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                    <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Type', 'amadex'); ?></span>
                                    <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['passenger_type'] ?? ''); ?></span>
                                </div>
                                <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                    <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Date of Birth', 'amadex'); ?></span>
                                    <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['date_of_birth'] ?? ''); ?></span>
                                </div>
                                <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                    <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Gender', 'amadex'); ?></span>
                                    <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['gender'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($passenger['passport_number'])): ?>
                                    <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                        <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Passport Number', 'amadex'); ?></span>
                                        <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['passport_number']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($passenger['passport_expiry'])): ?>
                                    <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                        <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Passport Expiry', 'amadex'); ?></span>
                                        <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['passport_expiry']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($passenger['nationality'])): ?>
                                    <div class="amadex-passenger-detail" style="padding:12px 16px;border-right:1px solid #f3f4f6;">
                                        <span class="amadex-passenger-detail-label" style="font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;"><?php _e('Nationality', 'amadex'); ?></span>
                                        <span class="amadex-passenger-detail-value" style="font-size: 14px; color: #111827; font-weight: 600;"><?php echo esc_html($passenger['nationality']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Fraud Detection & Verification Section -->
            <?php
            $fraud_data = !empty($lead['fraud_data']) ? (is_string($lead['fraud_data']) ? json_decode($lead['fraud_data'], true) : $lead['fraud_data']) : null;
            $fraud_score = intval($lead['fraud_score'] ?? 0);
            $fraud_risk_level = $lead['fraud_risk_level'] ?? 'LOW';
            ?>
            <?php if ($fraud_data && $fraud_score > 0):
                $f_color  = $fraud_score >= 61 ? '#dc2626' : ($fraud_score >= 41 ? '#ea580c' : ($fraud_score >= 21 ? '#ca8a04' : 'linear-gradient(135deg, #0e7d3f 0%, #059669 100%)'));
                $f_bg     = $fraud_score >= 61 ? '#fef2f2' : ($fraud_score >= 41 ? '#fff7ed' : ($fraud_score >= 21 ? '#fefce8' : '#f0fdf4'));
                $f_border = $fraud_score >= 61 ? '#fca5a5' : ($fraud_score >= 41 ? '#fdba74' : ($fraud_score >= 21 ? '#fde047' : '#86efac'));
                $f_label  = $fraud_score >= 61 ? 'Critical' : ($fraud_score >= 41 ? 'High' : ($fraud_score >= 21 ? 'Medium' : 'Low'));
            ?>
                <div class="amadex-flight-card amadex-fraud-accordion">
                    <h3 class="amadex-fraud-toggle" style="cursor:pointer;user-select:none;display:flex;align-items:center;justify-content:space-between;">
                        <?php _e('Fraud Detection & Verification', 'amadex'); ?>
                        <svg class="amadex-fraud-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5" style="transition:transform .25s;flex-shrink:0;">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </h3>
                    <div style="display:grid;grid-template-columns:auto 1fr;margin:16px 20px;border:1px solid <?php echo $f_border; ?>;border-radius:12px;overflow:hidden;">
                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;background:<?php echo $f_color; ?>;color:#fff;min-width:130px;">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;opacity:.8;margin-bottom:6px;">Risk Score</div>
                            <div style="font-size:56px;font-weight:900;line-height:1;letter-spacing:-.04em;"><?php echo esc_html($fraud_score); ?></div>
                            <div style="font-size:13px;font-weight:600;opacity:.8;margin-top:2px;">/100</div>
                            <div style="margin-top:10px;background:rgba(255,255,255,.2);border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700;"><?php echo esc_html(strtoupper($f_label)); ?></div>
                        </div>
                        <div style="padding:20px 24px;background:<?php echo $f_bg; ?>;display:flex;flex-direction:column;justify-content:center;gap:12px;">
                            <div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                    <span style="font-size:16px;font-weight:600;color:#6b7280;">Risk Level</span>
                                    <span style="font-size:12px;font-weight:700;color:<?php echo $f_color; ?>"><?php echo esc_html($fraud_score); ?>%</span>
                                </div>
                                <div style="height:8px;background:#e5e7eb;border-radius:10px;overflow:hidden;">
                                    <div style="height:100%;width:<?php echo esc_attr($fraud_score); ?>%;background:<?php echo $f_color; ?>;border-radius:10px;"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-top:4px;">
                                    <span style="font-size:12px;color:#9ca3af;">Low</span>
                                    <span style="font-size:12px;color:#9ca3af;">Critical</span>
                                </div>
                            </div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php
                                $levels = [
                                    ['label' => 'Low', 'range' => '0–20', 'active' => $fraud_score <= 20, 'color' => 'linear-gradient(135deg, #0e7d3f 0%, #059669 100%)', 'bg' => '#dcfce7'],
                                    ['label' => 'Medium', 'range' => '21–40', 'active' => $fraud_score >= 21 && $fraud_score <= 40, 'color' => '#ca8a04', 'bg' => '#fef9c3'],
                                    ['label' => 'High', 'range' => '41–60', 'active' => $fraud_score >= 41 && $fraud_score <= 60, 'color' => '#ea580c', 'bg' => '#ffedd5'],
                                    ['label' => 'Critical', 'range' => '61–100', 'active' => $fraud_score >= 61, 'color' => '#dc2626', 'bg' => '#fee2e2'],
                                ];
                                foreach ($levels as $lvl):
                                    $ls = $lvl['active'] ? "background:{$lvl['color']};color:#fff;font-weight:700;" : "background:{$lvl['bg']};color:{$lvl['color']};opacity:.6;";
                                ?>
                                    <span style="<?php echo $ls; ?>font-size:11px;padding:3px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:4px;">
                                        <?php if ($lvl['active']): ?>● <?php endif; ?><?php echo esc_html($lvl['label']); ?> <span style="opacity:.7;font-size:10px;"><?php echo esc_html($lvl['range']); ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div style="font-size:12px;color:#6b7280;">Risk Level: <strong style="color:#374151;"><?php echo esc_html($fraud_risk_level); ?></strong></div>
                        </div>
                    </div>
                    <div class="amadex-fraud-body" style="display:none;">

                        <?php if (!empty($fraud_data['risk_factors'])): ?>
                            <div style="margin:16px 20px;border:1px solid #f8fafc;border-radius:12px;overflow:hidden;">
                                <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#fff8f0;border-bottom:1px solid #f3f4f6;">
                                    <span>⚠️</span>
                                    <span style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.07em;">Risk Factors</span>
                                    <span style="margin-left:auto;background:#ea580c;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;"><?php echo count($fraud_data['risk_factors']); ?></span>
                                </div>
                                <?php foreach ($fraud_data['risk_factors'] as $i => $factor): ?>
                                    <?php
                                    // Build contextual detail for each risk factor
                                    $factor_detail = '';
                                    $geo = $fraud_data['geolocation'] ?? [];
                                    $pay = $fraud_data['payment_risk'] ?? [];
                                    $con = $fraud_data['contact_risk'] ?? [];
                                    $ip  = $fraud_data['ip'] ?? '';

                                    switch ($factor) {
                                        case 'Departure within 48 hours':
                                            $dep_risk = $fraud_data['departure_risk'] ?? [];
                                            $hours = $dep_risk['hours_until_departure'] ?? null;
                                            $dep_at = $dep_risk['departure_at'] ?? '';
                                            if ($hours !== null && $dep_at) {
                                                $factor_detail = 'Departs ' . date('M j, Y g:i A', strtotime($dep_at)) . ' (' . round($hours, 0) . 'h from booking)';
                                            } elseif ($dep_at) {
                                                $factor_detail = 'Departs ' . date('M j, Y g:i A', strtotime($dep_at));
                                            }
                                            break;
                                        case 'Billing country mismatch':
                                            $billing = $fraud_data['billing_country'] ?? ($pay['billing_country'] ?? '');
                                            $ip_country = $geo['countryName'] ?? $geo['country'] ?? '';
                                            if ($billing && $ip_country) {
                                                $factor_detail = 'Billing: ' . strtoupper($billing) . ' — IP location: ' . $ip_country;
                                            } elseif ($ip_country) {
                                                $factor_detail = 'IP location: ' . $ip_country;
                                            }
                                            break;
                                        case 'Card country mismatch':
                                            $ip_country = $geo['countryName'] ?? $geo['country'] ?? '';
                                            if ($ip_country) {
                                                $factor_detail = 'IP location: ' . $ip_country;
                                            }
                                            break;
                                        case 'Proxy IP detected':
                                        case 'VPN detected':
                                        case 'Tor network detected':
                                        case 'Datacenter IP':
                                            if ($ip) $factor_detail = 'IP: ' . $ip;
                                            break;
                                        case 'Disposable email address':
                                            $email = $fraud_data['contact_email'] ?? ($lead['contact_email'] ?? '');
                                            if ($email) $factor_detail = $email;
                                            break;
                                        case 'Unusual transaction amount':
                                            $amount = $fraud_data['transaction_amount'] ?? ($pay['amount'] ?? '');
                                            if ($amount) $factor_detail = '$' . number_format(floatval($amount), 2);
                                            break;
                                        case 'Very fast form completion':
                                            $time = $fraud_data['behavior_risk']['completionTime'] ?? '';
                                            if ($time) $factor_detail = round($time / 1000, 1) . 's completion time';
                                            break;
                                        case 'Incognito/private mode':
                                            $factor_detail = 'Private browsing detected';
                                            break;
                                    }
                                    ?>
                                    <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;background:<?php echo $i % 2 === 0 ? '#fffbeb' : '#fff'; ?>;border-bottom:1px solid #fef3c7;">
                                        <span style="width:20px;height:20px;background:#f59e0b;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                                <line x1="12" y1="9" x2="12" y2="13" />
                                                <line x1="12" y1="17" x2="12.01" y2="17" />
                                            </svg>
                                        </span>
                                        <div style="flex:1;">
                                            <div style="font-size:13px;color:#78350f;font-weight:600;"><?php echo esc_html($factor); ?></div>
                                            <?php if ($factor_detail): ?>
                                                <div style="font-size:13px;color:#92400e;opacity:.75;margin-top:2px;"><?php echo esc_html($factor_detail); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 20px 20px;">
                            <?php if (!empty($fraud_data['device'])): ?>
                                <div style="border:1px solid #e8eaed;border-radius:12px;overflow:hidden;">
                                    <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e8eaed;">
                                        <span style="width:28px;height:28px;background:#dbeafe;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5">
                                                <rect x="2" y="3" width="20" height="14" rx="2" />
                                                <line x1="8" y1="21" x2="16" y2="21" />
                                                <line x1="12" y1="17" x2="12" y2="21" />
                                            </svg>
                                        </span>
                                        <span style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.07em;">Device</span>
                                    </div>
                                    <?php if (!empty($fraud_data['device']['browser'])): ?>
                                        <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                            <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Browser</span>
                                            <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html(($fraud_data['device']['browser']['name'] ?? '') . ' ' . ($fraud_data['device']['browser']['version'] ?? '')); ?></span>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                            <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Platform</span>
                                            <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html($fraud_data['device']['browser']['platform'] ?? '—'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($fraud_data['device']['screen'])): ?>
                                        <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                            <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Screen</span>
                                            <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html(($fraud_data['device']['screen']['width'] ?? '') . '×' . ($fraud_data['device']['screen']['height'] ?? '')); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($fraud_data['device_fingerprint'])): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 16px;">
                                            <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Fingerprint</span>
                                            <code style="font-size:11px;background:#f3f4f6;padding:2px 8px;border-radius:5px;"><?php echo esc_html(substr($fraud_data['device_fingerprint'], 0, 14)); ?>…</code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fraud_data['geolocation'])): ?>
                                <div style="border:1px solid #e8eaed;border-radius:12px;overflow:hidden;">
                                    <div style="display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e8eaed;">
                                        <span style="width:28px;height:28px;background:#dcfce7;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="linear-gradient(135deg, #0e7d3f 0%, #059669 100%)" stroke-width="2.5">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                                <circle cx="12" cy="10" r="3" />
                                            </svg>
                                        </span>
                                        <span style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.07em;">IP & Location</span>
                                        <?php if (!empty($fraud_data['ip_risk']) && ($fraud_data['ip_risk']['isProxy'] || $fraud_data['ip_risk']['isVPN'])): ?>
                                            <span style="margin-left:auto;background:#dc2626;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">⚠ Proxy/VPN</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                        <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">IP Address</span>
                                        <code style="font-size:13px;font-weight:700;color:#111827;"><?php echo esc_html($fraud_data['ip'] ?? '—'); ?></code>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                        <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Location</span>
                                        <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html(($fraud_data['geolocation']['city'] ?? '') . ', ' . ($fraud_data['geolocation']['countryName'] ?? '')); ?></span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:9px 16px;border-bottom:1px solid #f3f4f6;">
                                        <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">ISP</span>
                                        <span style="font-size:13px;font-weight:600;color:#111827;"><?php echo esc_html($fraud_data['geolocation']['isp'] ?? '—'); ?></span>
                                    </div>
                                    <?php if (!empty($fraud_data['ip_risk'])): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 16px;">
                                            <span style="font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Proxy / VPN</span>
                                            <?php if ($fraud_data['ip_risk']['isProxy'] || $fraud_data['ip_risk']['isVPN']): ?>
                                                <span style="background:#fee2e2;color:#dc2626;font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;">⚠ Detected</span>
                                            <?php else: ?>
                                                <span style="background:#dcfce7;color:linear-gradient(135deg, #0e7d3f 0%, #059669 100%);font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;">✓ Clean</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- ── Risk Intelligence Panel ── -->
                        <div style="margin:0 20px 20px;">
                            <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2.5">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                Risk Intelligence
                            </div>
                            <?php
                            $geo        = $fraud_data['geolocation'] ?? [];
                            $pay        = $fraud_data['payment_risk'] ?? [];
                            $ip_risk    = $fraud_data['ip_risk'] ?? [];
                            $behavior   = $fraud_data['behavior_risk'] ?? [];
                            $dep_risk   = $fraud_data['departure_risk'] ?? [];
                            $contact_email = $lead['contact_email'] ?? '';

                            // Pull stored billing country
                            $billing_country = strtoupper(trim($fraud_data['billing_country'] ?? $pay['billing_country'] ?? ''));
                            $ip_country_code = strtoupper(trim($geo['countryCode'] ?? $geo['country_code'] ?? $geo['country'] ?? ''));
                            $ip_country_name = trim($geo['countryName'] ?? $geo['country_name'] ?? $ip_country_code);

                            // Departure country — first segment departure airport country
                            $dep_country = '';
                            if (!empty($flight_data['itineraries'][0]['segments'][0]['departure']['countryCode'])) {
                                $dep_country = strtoupper($flight_data['itineraries'][0]['segments'][0]['departure']['countryCode']);
                            }

                            // Hours until departure
                            $hours_until_dep = $dep_risk['hours_until_departure'] ?? null;

                            // Risk factor strings already computed
                            $risk_factors_list = $fraud_data['risk_factors'] ?? [];

                            $checks = [];

                            // 1. IP country ≠ billing country
                            if ($billing_country && $ip_country_code) {
                                $match = ($ip_country_code === $billing_country);
                                $checks[] = [
                                    'label'  => 'IP country vs Billing country',
                                    'ok'     => $match,
                                    'value'  => $match
                                        ? "Both: {$ip_country_name} ({$ip_country_code})"
                                        : "IP: {$ip_country_name} ({$ip_country_code}) — Billing: {$billing_country}",
                                    'risk'   => !$match,
                                ];
                            } elseif ($ip_country_code) {
                                $checks[] = [
                                    'label'  => 'IP country vs Billing country',
                                    'ok'     => null,
                                    'value'  => "IP: {$ip_country_name} — Billing country not captured",
                                    'risk'   => false,
                                    'neutral' => true,
                                ];
                            }

                            // 2. IP country ≠ departure country
                            if ($dep_country && $ip_country_code) {
                                $match2 = ($ip_country_code === $dep_country);
                                $checks[] = [
                                    'label'  => 'IP country vs Departure country',
                                    'ok'     => $match2,
                                    'value'  => $match2
                                        ? "Both: {$ip_country_name} ({$ip_country_code})"
                                        : "IP: {$ip_country_name} ({$ip_country_code}) — Departure: {$dep_country}",
                                    'risk'   => !$match2,
                                ];
                            }

                            // 3. VPN / Proxy detected
                            $is_vpn   = !empty($ip_risk['isVPN'])   || in_array('VPN detected',   $risk_factors_list);
                            $is_proxy = !empty($ip_risk['isProxy'])  || in_array('Proxy IP detected', $risk_factors_list);
                            $is_tor   = !empty($ip_risk['isTor'])    || in_array('Tor network detected', $risk_factors_list);
                            $vpn_val  = [];
                            if ($is_vpn)   $vpn_val[] = 'VPN';
                            if ($is_proxy) $vpn_val[] = 'Proxy';
                            if ($is_tor)   $vpn_val[] = 'Tor';
                            $checks[] = [
                                'label'  => 'VPN / Proxy / Tor',
                                'ok'     => !($is_vpn || $is_proxy || $is_tor),
                                'value'  => ($is_vpn || $is_proxy || $is_tor)
                                    ? implode(' + ', $vpn_val) . ' detected'
                                    : 'None detected — clean IP',
                                'risk'   => ($is_vpn || $is_proxy || $is_tor),
                            ];

                            // 4. Disposable email
                            $disposable = in_array('Disposable email address', $risk_factors_list)
                                || !empty($fraud_data['contact_risk']['is_disposable_email']);
                            $checks[] = [
                                'label'  => 'Disposable email',
                                'ok'     => !$disposable,
                                'value'  => $disposable
                                    ? "{$contact_email} — flagged as disposable"
                                    : ($contact_email ?: 'Email OK'),
                                'risk'   => $disposable,
                            ];

                            // 5. Multiple failed payments
                            $failed_payments = intval($pay['failed_attempts'] ?? $fraud_data['failed_payment_attempts'] ?? 0);
                            $checks[] = [
                                'label'  => 'Multiple failed payments',
                                'ok'     => ($failed_payments === 0),
                                'value'  => $failed_payments > 0
                                    ? "{$failed_payments} failed attempt(s) before success"
                                    : 'No failed attempts',
                                'risk'   => ($failed_payments >= 2),
                                'warn'   => ($failed_payments === 1),
                            ];

                            // 6. High-value booking
                            $booking_amount = floatval($fraud_data['transaction_amount'] ?? $pay['amount'] ?? $total_price ?? 0);
                            $high_value = ($booking_amount >= 5000);
                            $checks[] = [
                                'label'  => 'High-value booking',
                                'ok'     => !$high_value,
                                'value'  => $booking_amount > 0
                                    ? number_format($booking_amount, 2) . ' ' . $currency . ($high_value ? ' — elevated risk threshold' : '')
                                    : 'Amount not captured',
                                'risk'   => $high_value,
                                'warn'   => ($booking_amount >= 3000 && $booking_amount < 5000),
                            ];

                            // 7. Same card across many passengers
                            $same_card_multi = !empty($pay['same_card_multiple_passengers'])
                                || in_array('Same card used across many passengers', $risk_factors_list);
                            $pax_count = intval($booking['passenger_count'] ?? 0);
                            $checks[] = [
                                'label'  => 'Card shared across passengers',
                                'ok'     => !$same_card_multi,
                                'value'  => $same_card_multi
                                    ? "Single card used for {$pax_count} passenger(s)"
                                    : ($pax_count > 1 ? "{$pax_count} passengers — separate cards" : '1 passenger'),
                                'risk'   => $same_card_multi,
                            ];

                            // 8. Booking within 24h of departure
                            $within_24h = ($hours_until_dep !== null && $hours_until_dep <= 24);
                            $within_48h = ($hours_until_dep !== null && $hours_until_dep <= 48);
                            $checks[] = [
                                'label'  => 'Booking proximity to departure',
                                'ok'     => ($hours_until_dep === null || $hours_until_dep > 48),
                                'value'  => $hours_until_dep !== null
                                    ? round($hours_until_dep, 0) . 'h before departure'
                                    . ($within_24h ? ' — critical window' : ($within_48h ? ' — elevated risk' : ' — normal'))
                                    : 'Departure time not captured',
                                'risk'   => $within_24h,
                                'warn'   => (!$within_24h && $within_48h),
                            ];

                            // 9. Device fingerprint seen in chargebacks
                            $fp_chargeback = !empty($fraud_data['device_fingerprint_chargeback'])
                                || in_array('Device fingerprint seen in chargebacks', $risk_factors_list);
                            $fp_val = !empty($fraud_data['device_fingerprint'])
                                ? substr($fraud_data['device_fingerprint'], 0, 14) . '…'
                                : 'N/A';
                            $checks[] = [
                                'label'  => 'Device fingerprint in chargebacks',
                                'ok'     => !$fp_chargeback,
                                'value'  => $fp_chargeback
                                    ? "Fingerprint {$fp_val} — chargeback history found"
                                    : "Fingerprint {$fp_val} — no chargeback history",
                                'risk'   => $fp_chargeback,
                            ];

                            // 10. Card BIN high-risk country
                            $bin_high_risk = !empty($pay['bin_high_risk'])
                                || in_array('Card BIN high-risk country', $risk_factors_list);
                            $bin_country = $pay['bin_country'] ?? '';
                            $checks[] = [
                                'label'  => 'Card BIN country risk',
                                'ok'     => !$bin_high_risk,
                                'value'  => $bin_high_risk
                                    ? 'BIN ' . ($bin_country ? "({$bin_country})" : '') . ' flagged as high-risk'
                                    : ($bin_country ? "BIN country: {$bin_country} — OK" : 'BIN data not captured'),
                                'risk'   => $bin_high_risk,
                            ];

                            // 11. Passenger name mismatch
                            $name_mismatch = !empty($fraud_data['name_mismatch'])
                                || in_array('Passenger name mismatch', $risk_factors_list);
                            $checks[] = [
                                'label'  => 'Passenger name vs payment name',
                                'ok'     => !$name_mismatch,
                                'value'  => $name_mismatch
                                    ? 'Mismatch detected between passenger and cardholder name'
                                    : 'Names consistent',
                                'risk'   => $name_mismatch,
                            ];

                            // 12. Too many bookings in short time
                            $velocity = intval($fraud_data['booking_velocity'] ?? $pay['booking_velocity'] ?? 0);
                            $velocity_risk = ($velocity >= 3);
                            $checks[] = [
                                'label'  => 'Booking velocity (same IP/email)',
                                'ok'     => !$velocity_risk,
                                'value'  => $velocity > 0
                                    ? "{$velocity} booking(s) from same source recently"
                                    . ($velocity_risk ? ' — velocity limit exceeded' : '')
                                    : 'No velocity issues detected',
                                'risk'   => $velocity_risk,
                                'warn'   => ($velocity === 2),
                            ];
                            ?>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                <?php foreach ($checks as $chk):
                                    $is_risk    = !empty($chk['risk']);
                                    $is_warn    = !empty($chk['warn']) && !$is_risk;
                                    $is_neutral = !empty($chk['neutral']);
                                    $is_ok      = !$is_risk && !$is_warn && !$is_neutral;

                                    if ($is_risk) {
                                        $bg      = '#fef2f2';
                                        $border  = '#fca5a5';
                                        $icon_bg = '#dc2626';
                                        $label_c = '#991b1b';
                                        $val_c   = '#7f1d1d';
                                        $icon    = '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>';
                                    } elseif ($is_warn) {
                                        $bg      = '#fffbeb';
                                        $border  = '#fde68a';
                                        $icon_bg = '#d97706';
                                        $label_c = '#92400e';
                                        $val_c   = '#78350f';
                                        $icon    = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                                    } elseif ($is_neutral) {
                                        $bg      = '#f8fafc';
                                        $border  = '#e2e8f0';
                                        $icon_bg = '#94a3b8';
                                        $label_c = '#475569';
                                        $val_c   = '#64748b';
                                        $icon    = '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
                                    } else {
                                        $bg      = '#f0fdf4';
                                        $border  = '#86efac';
                                        $icon_bg = '#16a34a';
                                        $label_c = '#14532d';
                                        $val_c   = '#166534';
                                        $icon    = '<polyline points="20 6 9 17 4 12"/>';
                                    }
                                ?>
                                    <div style="background:<?php echo $bg; ?>;border:1px solid <?php echo $border; ?>;border-radius:10px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;">
                                        <span style="width:26px;height:26px;background:<?php echo $icon_bg; ?>;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <?php echo $icon; ?>
                                            </svg>
                                        </span>
                                        <div style="min-width:0;">
                                            <div style="font-size:10px;font-weight:700;color:<?php echo $label_c; ?>;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;"><?php echo esc_html($chk['label']); ?></div>
                                            <div style="font-size:12px;font-weight:500;color:<?php echo $val_c; ?>;line-height:1.4;word-break:break-word;"><?php echo esc_html($chk['value']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- ── End Risk Intelligence Panel ── -->

                    </div><!-- /.amadex-fraud-body -->
                </div><!-- /.amadex-fraud-accordion -->
        </div><!-- /.amadex-fraud-body -->
        </div><!-- /.amadex-fraud-accordion -->
    <?php endif; ?>

    <!-- Booking Information (if available) -->
    <?php if ($booking): ?>
        <div class="amadex-info-card">
            <h3><?php _e('Booking Information', 'amadex'); ?></h3>
            <div class="amadex-info-row">
                <span class="amadex-info-label"><?php _e('Booking Reference', 'amadex'); ?></span>
                <span class="amadex-info-value"><strong><?php echo esc_html($booking['booking_reference'] ?? ''); ?></strong></span>
            </div>
            <?php if (!empty($booking['confirmation_number'])): ?>
                <div class="amadex-info-row">
                    <span class="amadex-info-label"><?php _e('Confirmation Number', 'amadex'); ?></span>
                    <span class="amadex-info-value"><strong><?php echo esc_html($booking['confirmation_number']); ?></strong></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($booking['pnr'])): ?>
                <div class="amadex-info-row">
                    <span class="amadex-info-label"><?php _e('PNR', 'amadex'); ?></span>
                    <span class="amadex-info-value"><strong><?php echo esc_html($booking['pnr']); ?></strong></span>
                </div>
            <?php endif; ?>
            <div class="amadex-info-row">
                <span class="amadex-info-label"><?php _e('Status', 'amadex'); ?></span>
                <span class="amadex-info-value">
                    <span class="amadex-lead-badge <?php echo esc_attr(strtolower($booking['status'])); ?>" style="font-size:11px;padding:4px 12px;">
                        <?php echo esc_html(ucfirst(strtolower($booking['status']))); ?>
                    </span>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="amadex-actions-section">
        <div class="lda-actions-row">
            <!-- Agent + Status combined -->
            <div class="lda-action-panel">
                <div class="lda-action-body" style="display:flex;gap:14px;flex-wrap:wrap;flex:1;">
                    <div class="lda-floating-field" style="flex:1;min-width:160px;">
                        <select id="amadex-agent-select" class="lda-floating-select">
                            <option value="">Select agent</option>
                            <?php
                            $assigned_agent_id = intval($lead['assigned_agent_id'] ?? $lead['agent_id'] ?? 0);
                            foreach (get_users(array('role__in' => array('administrator', 'editor', 'agent'))) as $agent): ?>
                                <option value="<?php echo esc_attr($agent->ID); ?>" <?php selected($agent->ID, $assigned_agent_id); ?>><?php echo esc_html($agent->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="lda-floating-label">Assign Agent</label>
                    </div>
                    <div class="lda-floating-field" style="flex:1;min-width:160px;">
                        <select id="amadex-lead-status-select" class="lda-floating-select">
                            <option value="NEW" <?php selected($lead['status'], 'NEW'); ?>>New</option>
                            <option value="ASSIGNED" <?php selected($lead['status'], 'ASSIGNED'); ?>>Assigned</option>
                            <option value="IN_PROGRESS" <?php selected($lead['status'], 'IN_PROGRESS'); ?>>In Progress</option>
                            <option value="CONTACTED" <?php selected($lead['status'], 'CONTACTED'); ?>>Contacted</option>
                            <option value="CONVERTED" <?php selected($lead['status'], 'CONVERTED'); ?>>Converted</option>
                            <option value="CANCELLED" <?php selected($lead['status'], 'CANCELLED'); ?>>Cancelled</option>
                        </select>
                        <label class="lda-floating-label">Lead Status</label>
                    </div>
                </div>
                <button class="lda-btn lda-btn-green amadex-save-all-btn" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Save
                </button>
            </div>

            <!-- Notes -->
            <div class="lda-action-panel lda-no-border">
                <div class="lda-action-icon lda-icon-yellow">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                </div>
                <div class="lda-action-body">
                    <div class="lda-action-label">Internal Notes</div>
                    <div class="lda-action-hint">Leave a note for your team</div>
                </div>
                <button class="lda-btn lda-btn-yellow amadex-add-notes-btn" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Add Note
                </button>
            </div>
        </div>

        <div class="lda-notes-panel" id="amadex-notes-panel" style="display:none;">
            <textarea id="amadex-notes-text" placeholder="Write your note here..."></textarea>
            <div style="display:flex;gap:8px;margin-top:8px;">
                <button class="lda-btn lda-btn-yellow amadex-save-notes-btn" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Save Note
                </button>
                <button class="lda-btn lda-btn-grey amadex-cancel-notes-btn">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Saved Notes Display -->
    <?php
        $notes_array = array();
        if (!empty($lead['notes'])) {
            $decoded = json_decode($lead['notes'], true);
            if (is_array($decoded)) {
                $notes_array = $decoded;
            } else {
                $notes_array[] = array(
                    'text' => $lead['notes'],
                    'at'   => $lead['updated_at'] ?? $lead['created_at'],
                    'by'   => 0,
                );
            }
        }
        $note_colors = ['#fef9c3', '#dbeafe', '#dcfce7', '#fce7f3', '#ede9fe', '#ffedd5'];
    ?>

    <div class="amadex-notes-accordion" id="amadex-saved-notes">
        <div class="amadex-notes-accordion-header" id="amadex-notes-acc-toggle">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:36px;height:36px;background:#fff;border:1.5px solid #fde68a;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(202,138,4,.15);flex-shrink:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                </span>
                <div>
                    <div style="font-size:13px;font-weight:700;color:#92400e;letter-spacing:.04em;">Internal Notes</div>
                    <div style="font-size:11px;color:#a16207;margin-top:1px;">
                        <?php if (!empty($notes_array)): ?>
                            <?php echo count($notes_array); ?> note<?php echo count($notes_array) !== 1 ? 's' : ''; ?> saved
                        <?php else: ?>
                            No notes yet
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($notes_array)): ?>
                    <span style="background:#ca8a04;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-left:4px;"><?php echo count($notes_array); ?></span>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:28px;height:28px;background:#fff;border:1px solid #fde68a;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg class="amadex-notes-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2.5" style="transition:transform .25s;flex-shrink:0;">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </span>
            </div>
        </div>

        <div class="amadex-notes-accordion-body" <?php echo empty($notes_array) ? 'style="display:none;"' : ''; ?>>
            <?php if (empty($notes_array)): ?>
                <div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px;">No notes yet. Add your first note above.</div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;padding:18px 0;">
                    <?php foreach ($notes_array as $ni => $note): ?>
                        <?php
                        $bg = $note_colors[$ni % count($note_colors)];
                        $by_user = !empty($note['by']) ? get_userdata($note['by']) : null;
                        $by_name = $by_user ? $by_user->display_name : 'Admin';
                        $note_date = !empty($note['at']) ? date('M j, Y', strtotime($note['at'])) : '';
                        $note_time = !empty($note['at']) ? date('g:i A', strtotime($note['at'])) : '';
                        ?>
                        <div style="background:<?php echo $bg; ?>;border-radius:14px;padding:0;display:flex;flex-direction:column;min-height:140px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;border:1px solid rgba(0,0,0,.06);">
                            <!-- Card Header -->
                            <div style="padding:14px 16px 10px;flex:1;">
                                <div style="font-size:13px;color:#374151;line-height:1.65;word-break:break-word;"><?php echo nl2br(esc_html($note['text'])); ?></div>
                            </div>
                            <!-- Card Footer -->
                            <div style="background:rgba(0,0,0,.05);padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                <div style="display:flex;align-items:center;gap:7px;min-width:0;">
                                    <span style="width:24px;height:24px;background:rgba(0,0,0,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;flex-shrink:0;"><?php echo esc_html(strtoupper(substr($by_name, 0, 1))); ?></span>
                                    <span style="font-size:11px;color:#374151;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;" title="<?php echo esc_attr($by_name); ?>"><?php echo esc_html($by_name); ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgba(0,0,0,.4)" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                    <span style="font-size:11px;color:rgba(0,0,0,.5);font-weight:500;"><?php echo esc_html($note_date); ?> <?php echo esc_html($note_time); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notes panel -->
    <div class="lda-notes-panel" id="amadex-notes-panel" style="display:none;">
        <textarea id="amadex-notes-text" placeholder="Write your note here... (e.g. Called customer, follow up tomorrow)"><?php echo esc_textarea($lead['notes'] ?? ''); ?></textarea>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button class="lda-btn lda-btn-yellow amadex-save-notes-btn" data-lead-id="<?php echo esc_attr($lead['id']); ?>">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                Save Note
            </button>
            <button class="lda-btn lda-btn-grey amadex-cancel-notes-btn">Cancel</button>
        </div>
    </div>
    </div>

    <style>
        /* ── Floating label select ── */
        .lda-floating-field {
            position: relative;
        }

        .lda-floating-label {
            position: absolute;
            top: -9px;
            left: 12px;
            background: #fff;
            padding: 0 5px;
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .07em;
            pointer-events: none;
            white-space: nowrap;
        }

        .lda-floating-select {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 11px 14px 11px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            background: #fff;
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
            transition: border-color .2s, box-shadow .2s;
        }

        .lda-floating-select:focus {
            border-color: #0e7d3f;
            box-shadow: 0 0 0 3px rgba(14, 125, 63, .1);
        }

        .lda-floating-select:focus+.lda-floating-label {
            color: #0e7d3f;
        }

        div#amadex-notes-acc-toggle {
            display: flex;
            justify-content: space-between;
            background: #ca8a0475;
            padding: 14px 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .amadex-actions-section {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }

        .lda-actions-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 10px;
        }

        .lda-actions-row {
            display: flex;
            border: 1px solid #e8eaed;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
        }

        .lda-action-panel {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0px 18px;
            border-right: 1px solid #e8eaed;
        }

        .lda-no-border {
            border-right: none;
        }

        .lda-action-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lda-icon-green {
            background: #dcfce7;
            color: linear-gradient(135deg, #0e7d3f 0%, #059669 100%);
        }

        .lda-icon-blue {
            background: #dbeafe;
            color: #2563eb;
        }

        .lda-icon-yellow {
            background: #fef9c3;
            color: #ca8a04;
        }

        .lda-action-body {
            flex: 1;
            min-width: 0;
        }

        .lda-action-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .07em;
        }

        .lda-action-hint {
            font-size: 12px;
            color: #9ca3af;
        }

        .lda-select {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 7px;
            padding: 6px 10px;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            background: #f9fafb;
            cursor: pointer;
            outline: none;
            transition: border-color .2s;
        }

        .lda-select:focus {
            border-color: #0e7d3f;
        }

        .lda-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
            padding: 7px 14px;
            border-radius: 7px;
            border: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .lda-btn-green {
            background: #0e7d3f;
            color: #fff;
            box-shadow: 0 1px 4px rgba(14, 125, 63, .3);
        }

        .lda-btn-green:hover {
            background: #0a6232;
        }

        .lda-btn-blue {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 1px 4px rgba(37, 99, 235, .3);
        }

        .lda-btn-blue:hover {
            background: #1d4ed8;
        }

        .lda-btn-yellow {
            background: #ca8a04;
            color: #fff;
            box-shadow: 0 1px 4px rgba(202, 138, 4, .3);
        }

        .lda-btn-yellow:hover {
            background: #a16207;
        }

        .lda-btn-grey {
            background: #f3f4f6;
            color: #374151;
        }

        .lda-btn-grey:hover {
            background: #e5e7eb;
        }

        .lda-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .lda-notes-panel {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 14px;
            margin-top: 12px;
        }

        .lda-notes-panel textarea {
            width: 100%;
            min-height: 80px;
            border: 1.5px solid #fde68a;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #374151;
            resize: vertical;
            outline: none;
            font-family: inherit;
            background: #fff;
            box-sizing: border-box;
        }

        .lda-notes-panel textarea:focus {
            border-color: #ca8a04;
        }

        .lda-action-msg {
            font-size: 13px;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .lda-action-msg.success {
            background: #dcfce7;
            color: #15803d;
            display: block !important;
        }

        .lda-action-msg.error {
            background: #fee2e2;
            color: #991b1b;
            display: block !important;
        }

        @keyframes lda-spin {
            from {
                transform: rotate(0)
            }

            to {
                transform: rotate(360deg)
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            var $msg = $('.lda-action-msg');

            function showMsg(type, text) {
                $msg.removeClass('success error').addClass(type).html(text).show();
                setTimeout(function() {
                    $msg.fadeOut();
                }, 3000);
            }

            function btnLoad($btn, text) {
                $btn.prop('disabled', true).data('orig', $btn.html())
                    .html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:lda-spin .7s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> ' + text);
            }

            function btnReset($btn) {
                $btn.prop('disabled', false).html($btn.data('orig'));
            }

            $(document).off('click.saveall').on('click.saveall', '.amadex-save-all-btn', function() {
                var $btn = $(this),
                    leadId = $btn.data('lead-id'),
                    agentId = $('#amadex-agent-select').val(),
                    status = $('#amadex-lead-status-select').val(),
                    statusClass = status.toLowerCase().replace(/_/g, '-');

                btnLoad($btn, 'Saving...');

                var requests = [];

                if (agentId) {
                    requests.push($.ajax({
                        url: AmadexLeads.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'amadex_assign_lead',
                            nonce: AmadexLeads.nonce,
                            lead_id: leadId,
                            agent_id: agentId
                        }
                    }));
                }

                requests.push($.ajax({
                    url: AmadexLeads.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_update_lead_status',
                        nonce: AmadexLeads.nonce,
                        lead_id: leadId,
                        status: status,
                        notes: ''
                    }
                }));

                $.when.apply($, requests).done(function() {
                    showMsg('success', '✓ Saved — Agent & Status updated');
                    $('tr[data-lead-id="' + leadId + '"] .amadex-status')
                        .attr('class', 'amadex-status ' + status.toLowerCase())
                        .text(status);
                    $('.amadex-lead-header .amadex-lead-badge')
                        .attr('class', 'amadex-lead-badge ' + statusClass)
                        .text(status);
                    $('.amadex-info-card .amadex-lead-badge')
                        .attr('class', 'amadex-lead-badge ' + statusClass)
                        .css('font-size', '11px')
                        .css('padding', '4px 10px')
                        .text(status);
                    btnReset($btn);
                }).fail(function() {
                    showMsg('error', '✗ Save failed');
                    btnReset($btn);
                });
            });

            $(document).off('click.notesacc').on('click.notesacc', '#amadex-notes-acc-toggle', function() {
                var $body = $('.amadex-notes-accordion-body');
                var $chevron = $('.amadex-notes-chevron');
                var isOpen = $body.is(':visible');
                $body.slideToggle(200);
                $chevron.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(180deg)');
            });

            $(document).off('click.notes').on('click.notes', '.amadex-add-notes-btn', function() {
                $('#amadex-notes-panel').slideToggle(200);
                $('#amadex-notes-text').focus();
            });

            $(document).off('click.notescancel').on('click.notescancel', '.amadex-cancel-notes-btn', function() {
                $('#amadex-notes-panel').slideUp(200);
                $('#amadex-notes-text').val('');
            });

            $(document).off('click.itinerary').on('click.itinerary', '.amadex-itinerary-toggle', function() {
                var $acc = $(this).closest('.amadex-itinerary-accordion');
                var $body = $acc.find('.amadex-itinerary-body');
                var $chevron = $(this).find('.amadex-itinerary-chevron');
                var isOpen = $body.is(':visible');
                $body.slideToggle(200);
                $chevron.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(180deg)');
            });

            $(document).off('click.savenotes').on('click.savenotes', '.amadex-save-notes-btn', function() {
                var $btn = $(this),
                    leadId = $btn.data('lead-id'),
                    notes = $('#amadex-notes-text').val().trim(),
                    status = $('#amadex-lead-status-select').val();
                if (!notes) {
                    showMsg('error', '✗ Please enter a note');
                    return;
                }
                btnLoad($btn, 'Saving...');
                $.ajax({
                    url: AmadexLeads.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_update_lead_status',
                        nonce: AmadexLeads.nonce,
                        lead_id: leadId,
                        status: status,
                        notes: notes
                    },
                    success: function(r) {
                        if (r.success) {
                            showMsg('success', '✓ Note saved');
                            $('#amadex-notes-panel').slideUp(200);
                            $('#amadex-notes-text').val('');

                            var now = new Date();
                            var dateStr = now.toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric'
                            });
                            var timeStr = now.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                            var colors = ['#fef9c3', '#dbeafe', '#dcfce7', '#fce7f3', '#ede9fe', '#ffedd5'];
                            var bg = colors[Math.floor(Math.random() * colors.length)];
                            var safeNote = notes.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                            var card = '<div style="background:' + bg + ';border-radius:14px;display:flex;flex-direction:column;min-height:140px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;border:1px solid rgba(0,0,0,.06);">' +
                                '<div style="padding:14px 16px 10px;flex:1;">' +
                                '<div style="font-size:13px;color:#374151;line-height:1.65;">' + safeNote + '</div>' +
                                '</div>' +
                                '<div style="background:rgba(0,0,0,.05);padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:8px;">' +
                                '<div style="display:flex;align-items:center;gap:7px;">' +
                                '<span style="width:24px;height:24px;background:rgba(0,0,0,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#fff;">A</span>' +
                                '<span style="font-size:11px;color:#374151;font-weight:600;">Admin</span>' +
                                '</div>' +
                                '<div style="display:flex;align-items:center;gap:4px;">' +
                                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="rgba(0,0,0,.4)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' +
                                '<span style="font-size:11px;color:rgba(0,0,0,.5);font-weight:500;">' + dateStr + ' ' + timeStr + '</span>' +
                                '</div></div></div>';

                            var $body = $('.amadex-notes-accordion-body');
                            var $grid = $body.find('> div[style*="grid"]');
                            if ($grid.length === 0) {
                                $body.html('<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:16px 0;">' + card + '</div>');
                            } else {
                                $grid.prepend(card);
                            }
                            // Update badge count
                            var $badge = $('.amadex-notes-accordion-header span[style*="background:#ca8a04"]');
                            if ($badge.length) {
                                $badge.text(parseInt($badge.text() || 0) + 1);
                            } else {
                                $('.amadex-notes-accordion-header > div').append('<span style="background:#ca8a04;color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;">1</span>');
                            }
                            $('.amadex-notes-accordion-body').show();
                            $('.amadex-notes-chevron').css('transform', 'rotate(180deg)');
                        } else showMsg('error', '✗ ' + (r.data.message || 'Failed'));
                    },
                    error: function() {
                        showMsg('error', '✗ Request failed');
                    },
                    complete: function() {
                        btnReset($btn);
                    }
                });
            });

            // Fraud accordion
            $(document).off('click.fraud').on('click.fraud', '.amadex-fraud-toggle', function() {
                var $body = $(this).closest('.amadex-fraud-accordion').find('.amadex-fraud-body');
                var $chevron = $(this).find('.amadex-fraud-chevron');
                var isOpen = $body.is(':visible');
                $body.slideToggle(200);
                $chevron.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(180deg)');
            });
        });
    </script>
<?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Format duration (PT7H53M to "7h 53m")
     */
    private function format_duration($duration)
    {
        if (empty($duration)) {
            return '';
        }

        // Handle ISO 8601 duration format (PT7H53M)
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? intval($matches[1]) : 0;
            $minutes = isset($matches[2]) ? intval($matches[2]) : 0;

            $parts = array();
            if ($hours > 0) {
                $parts[] = $hours . 'h';
            }
            if ($minutes > 0) {
                $parts[] = $minutes . 'm';
            }

            return implode(' ', $parts) ?: '0m';
        }

        return $duration;
    }

    /**
     * Get price breakdown from booking (same logic as confirmation page)
     * 
     * @param array $booking Booking data
     * @return array Price breakdown: ['base_fare' => float, 'taxes' => float, 'premium_service' => float, 'total' => float, 'currency' => string]
     */
    private function get_price_breakdown_for_lead($booking)
    {
        $stored_total = floatval($booking['total_amount'] ?? 0);
        $currency = $booking['currency'] ?? 'USD';
        $flight_data = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();

        // Check for premium service
        $premium_service_added = false;
        $premium_service_amount = 25.00;
        if (isset($flight_data['premium_service']) && is_array($flight_data['premium_service'])) {
            $premium_service_added = isset($flight_data['premium_service']['added']) && $flight_data['premium_service']['added'] === true;
            if (isset($flight_data['premium_service']['amount']) && $flight_data['premium_service']['amount'] > 0) {
                $premium_service_amount = floatval($flight_data['premium_service']['amount']);
            }
        }

        // Calculate base total (without premium service)
        $base_total = $stored_total;
        if ($premium_service_added) {
            $base_total = $stored_total - $premium_service_amount;
        }

        // Get airline code
        $airline_code = '';
        if (!empty($flight_data['validating_airline_codes']) && is_array($flight_data['validating_airline_codes'])) {
            $airline_code = $flight_data['validating_airline_codes'][0] ?? '';
        } elseif (!empty($flight_data['validatingAirlineCodes']) && is_array($flight_data['validatingAirlineCodes'])) {
            $airline_code = $flight_data['validatingAirlineCodes'][0] ?? '';
        }

        // Get original prices
        $original_base = 0;
        $original_total = 0;
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;
            $original_base = floatval($flight_data['price']['original_base'] ?? 0);
            $original_total = floatval($flight_data['price']['original_total'] ?? 0);
            if ($original_base <= 0) {
                $original_base = floatval($flight_data['price']['base'] ?? $flight_data['price']['grandTotal'] ?? $flight_data['price']['total'] ?? 0);
            }
            if ($original_total <= 0) {
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
            }
        }

        // Recalculate using pricing logic
        if ($original_base > 0 && $original_total > 0 && class_exists('Amadex_Pricing')) {
            $base_result = Amadex_Pricing::calculate_price_with_markup($original_base, $airline_code);
            $calculated_base = is_array($base_result) ? floatval($base_result['total'] ?? $original_base) : floatval($base_result);
            $total_result = Amadex_Pricing::calculate_price_with_markup($original_total, $airline_code);
            $calculated_total = is_array($total_result) ? floatval($total_result['total'] ?? $original_total) : floatval($total_result);
            $calculated_taxes = $calculated_total - $calculated_base;
            if ($calculated_taxes < 0) {
                $calculated_taxes = $calculated_total * 0.10;
                $calculated_base = $calculated_total - $calculated_taxes;
            }

            $breakdown_total = $base_total;
            if ($breakdown_total > 0 && $calculated_total > 0) {
                $base_ratio = $calculated_base / $calculated_total;
                $final_base = $breakdown_total * $base_ratio;
                $final_taxes = $breakdown_total - $final_base;
                if ($final_taxes < 0) {
                    $final_taxes = $breakdown_total * 0.10;
                    $final_base = $breakdown_total - $final_taxes;
                }
                return array(
                    'base_fare' => round($final_base, 2),
                    'taxes' => round($final_taxes, 2),
                    'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                    'total' => round($stored_total, 2),
                    'currency' => $currency
                );
            } elseif ($calculated_total > 0) {
                return array(
                    'base_fare' => round($calculated_base, 2),
                    'taxes' => round($calculated_taxes, 2),
                    'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                    'total' => round($calculated_total + ($premium_service_added ? $premium_service_amount : 0), 2),
                    'currency' => $currency
                );
            }
        }

        // Fallback calculation
        if ($base_total > 0) {
            if ($original_base > 0 && $original_total > 0) {
                $price_ratio = $base_total / $original_total;
                $adjusted_base = $original_base * $price_ratio;
                $adjusted_taxes = $base_total - $adjusted_base;
                if ($adjusted_taxes < 0) {
                    $adjusted_taxes = $base_total * 0.10;
                    $adjusted_base = $base_total - $adjusted_taxes;
                }
            } else {
                $adjusted_base = $base_total * 0.90;
                $adjusted_taxes = $base_total * 0.10;
            }
            return array(
                'base_fare' => round($adjusted_base, 2),
                'taxes' => round($adjusted_taxes, 2),
                'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                'total' => round($stored_total, 2),
                'currency' => $currency
            );
        }

        return array(
            'base_fare' => 0,
            'taxes' => 0,
            'premium_service' => 0,
            'total' => 0,
            'currency' => $currency
        );
    }

    /**
     * AJAX: Assign lead to agent
     */
    public function ajax_assign_lead()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $lead_id = intval($_POST['lead_id'] ?? 0);
        $agent_id = intval($_POST['agent_id'] ?? 0);

        $result = $this->database->assign_lead_to_agent($lead_id, $agent_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Lead assigned successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to assign lead'));
        }
    }

    /**
     * AJAX: Update lead status
     */
    public function ajax_update_lead_status()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $lead_id = intval($_POST['lead_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $result = $this->database->update_lead_status($lead_id, $status, $notes);

        if ($result) {
            wp_send_json_success(array('message' => 'Lead status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }

    /**
     * AJAX: Get booking details
     */
    public function ajax_get_booking_details()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $booking = $this->database->get_booking($booking_id);

        if (!$booking) {
            wp_send_json_error(array('message' => 'Booking not found'));
        }

        $flight_data = $booking['flight_data'] ?? array();
        if (is_string($flight_data)) {
            $flight_data = json_decode($flight_data, true);
        }

        ob_start();
?>
    <style>
        .amadex-booking-details {
            padding: 20px 0;
        }

        .amadex-booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            padding-right: 50px;
            border-bottom: 2px solid #e5e7eb;
        }

        .amadex-booking-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .amadex-status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amadex-status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .amadex-status-badge.confirmed {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .amadex-status-badge.ticketed {
            background: #d1fae5;
            color: #065f46;
        }

        .amadex-status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .amadex-status-badge.refunded {
            background: #e5e7eb;
            color: #374151;
        }

        .amadex-booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .amadex-booking-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }

        .amadex-booking-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .amadex-info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .amadex-info-item:last-child {
            border-bottom: none;
        }

        .amadex-info-item-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
            min-width: 140px;
        }

        .amadex-info-item-value {
            color: #111827;
            font-size: 14px;
            text-align: right;
            flex: 1;
        }

        .amadex-info-item-value input,
        .amadex-info-item-value select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            margin-right: 8px;
        }

        .amadex-info-item-value input {
            min-width: 150px;
        }

        .amadex-update-btn {
            padding: 6px 16px;
            background: #0E7D3F;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .amadex-update-btn:hover {
            background: #0a6330;
        }

        .amadex-passenger-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #0E7D3F;
            border-radius: 8px;
            margin: 12px;
        }

        .amadex-passenger-name {
            font-size: 22px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }

        .amadex-passenger-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .amadex-passenger-detail {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .amadex-passenger-detail-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .amadex-passenger-detail-value {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
        }

        .amadex-payment-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .amadex-payment-status.auth_only {
            background: #fef3c7;
            color: #92400e;
        }

        .amadex-payment-status.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .amadex-flight-segments {
            margin-top: 30px;
        }

        .amadex-flight-segments .amadex-booking-card {
            background: #ffffff;
        }

        .amadex-itinerary-section {
            margin-bottom: 40px;
            padding: 24px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .amadex-itinerary-section:last-child {
            margin-bottom: 0;
        }

        .amadex-itinerary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }

        .amadex-itinerary-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .amadex-itinerary-title:before {
            content: '✈️';
            font-size: 20px;
        }

        .amadex-itinerary-duration {
            font-size: 14px;
            color: #6b7280;
            background: #ffffff;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }

        .amadex-segment-card {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            border-left: 5px solid #0E7D3F;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .amadex-segment-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: #0E7D3F;
        }

        .amadex-segment-card:last-child {
            margin-bottom: 0;
        }

        .amadex-segment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .amadex-segment-route {
            flex: 1;
        }

        .amadex-segment-airports {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 8px;
        }

        .amadex-segment-airport {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            letter-spacing: 1px;
        }

        .amadex-segment-arrow {
            color: #0E7D3F;
            font-size: 24px;
            font-weight: 600;
            padding: 0 8px;
        }

        .amadex-segment-flight {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }

        .amadex-segment-flight-number {
            font-size: 15px;
            color: #6b7280;
            font-weight: 600;
            background: #f3f4f6;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .amadex-segment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 16px;
        }

        .amadex-segment-detail-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .amadex-segment-detail-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amadex-segment-detail-value {
            font-size: 15px;
            color: #111827;
            font-weight: 600;
            line-height: 1.5;
        }

        .amadex-segment-detail-value strong {
            font-size: 18px;
            color: #0E7D3F;
            display: block;
            margin-top: 4px;
        }

        .amadex-segment-detail-value small {
            display: block;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            margin-top: 4px;
        }

        .amadex-connecting-flight {
            text-align: center;
            padding: 16px;
            background: linear-gradient(135deg, #fff9eb 0%, #fef3c7 100%);
            border-radius: 10px;
            margin: 16px 0;
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #fde68a;
            position: relative;
        }

        .amadex-connecting-flight:before {
            content: '⏱️';
            margin-right: 8px;
            font-size: 16px;
        }

        .amadex-connecting-flight-info {
            display: inline-block;
            background: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            margin-left: 8px;
            font-weight: 700;
        }

        .amadex-itinerary-toggle:hover {
            background: #f0fdf4;
            border-radius: 8px;
        }

        .amadex-itinerary-body {
            padding-top: 12px;
        }
    </style>

    <div class="amadex-booking-details">
        <!-- Header -->
        <div class="amadex-booking-header">
            <h2 class="amadex-booking-title"><?php _e('Booking Details #', 'amadex'); ?><?php echo esc_html($booking['id']); ?></h2>
            <span class="amadex-status-badge <?php echo esc_attr(strtolower($booking['status'])); ?>">
                <?php echo esc_html($booking['status']); ?>
            </span>
        </div>

        <?php
        $pdf_base = add_query_arg(array(
            'action' => 'amadex_generate_pdf',
            'booking_id' => $booking['id'],
            'nonce' => wp_create_nonce('amadex_nonce'),
            'download' => 1
        ), admin_url('admin-ajax.php'));
        ?>
        <div class="amadex-pdf-actions" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 8px;">
            <a href="<?php echo esc_url(add_query_arg('type', 'confirmation', $pdf_base)); ?>" class="button button-small" target="_blank"><?php _e('PDF Confirmation', 'amadex'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('type', 'eticket', $pdf_base)); ?>" class="button button-small" target="_blank"><?php _e('PDF E-Ticket', 'amadex'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('type', 'invoice', $pdf_base)); ?>" class="button button-small" target="_blank"><?php _e('PDF Invoice', 'amadex'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('type', 'receipt', $pdf_base)); ?>" class="button button-small" target="_blank"><?php _e('PDF Receipt', 'amadex'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('type', 'itinerary', $pdf_base)); ?>" class="button button-small" target="_blank"><?php _e('PDF Itinerary', 'amadex'); ?></a>
        </div>

        <!-- Top Grid: Booking Info & Payment -->
        <div class="amadex-booking-grid">
            <!-- Booking Information Card -->
            <div class="amadex-booking-card">
                <h3><?php _e('Booking Information', 'amadex'); ?></h3>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Booking Reference', 'amadex'); ?></span>
                    <span class="amadex-info-item-value">
                        <strong><?php echo esc_html($booking['booking_reference']); ?></strong>
                    </span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('PNR', 'amadex'); ?></span>
                    <span class="amadex-info-item-value">
                        <input type="text" id="booking-pnr" value="<?php echo esc_attr($booking['pnr'] ?? ''); ?>" placeholder="<?php _e('Enter PNR', 'amadex'); ?>" />
                        <button class="amadex-update-btn amadex-update-pnr" data-booking-id="<?php echo esc_attr($booking['id']); ?>"><?php _e('Update', 'amadex'); ?></button>
                    </span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Status', 'amadex'); ?></span>
                    <span class="amadex-info-item-value">
                        <select id="booking-status" style="margin-right: 8px;">
                            <option value="PENDING" <?php selected($booking['status'], 'PENDING'); ?>><?php _e('Pending', 'amadex'); ?></option>
                            <option value="CONFIRMED" <?php selected($booking['status'], 'CONFIRMED'); ?>><?php _e('Confirmed', 'amadex'); ?></option>
                            <option value="TICKETED" <?php selected($booking['status'], 'TICKETED'); ?>><?php _e('Ticketed', 'amadex'); ?></option>
                            <option value="CANCELLED" <?php selected($booking['status'], 'CANCELLED'); ?>><?php _e('Cancelled', 'amadex'); ?></option>
                            <option value="REFUNDED" <?php selected($booking['status'], 'REFUNDED'); ?>><?php _e('Refunded', 'amadex'); ?></option>
                        </select>
                        <button class="amadex-update-btn amadex-update-status" data-booking-id="<?php echo esc_attr($booking['id']); ?>"><?php _e('Update', 'amadex'); ?></button>
                    </span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Total Amount', 'amadex'); ?></span>
                    <span class="amadex-info-item-value">
                        <strong style="font-size: 18px; color: #0E7D3F;">
                            <?php
                            // Get display currency from currency_conversion (currency user selected during booking)
                            $detail_display_currency = 'USD';
                            $detail_display_amount = floatval($booking['total_amount'] ?? 0);

                            if (!empty($flight_data['currency_conversion']) && is_array($flight_data['currency_conversion'])) {
                                $conversion_info = $flight_data['currency_conversion'];
                                $conversion_display_currency = $conversion_info['display_currency'] ?? 'USD';
                                $conversion_display_amount = floatval($conversion_info['display_amount'] ?? 0);

                                if ($conversion_display_currency !== 'USD' && $conversion_display_amount > 0) {
                                    // User booked in display currency (e.g., INR) - show that
                                    $detail_display_currency = $conversion_display_currency;
                                    $detail_display_amount = $conversion_display_amount;
                                }
                            }

                            // Use proper currency symbol formatting
                            if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_currency_symbol')) {
                                $detail_currency_symbol = Amadex_Currency::get_currency_symbol($detail_display_currency);
                            } else {
                                // Fallback to basic symbols
                                $detail_currency_symbol = ($detail_display_currency === 'USD') ? '$' : ($detail_display_currency === 'INR' ? '₹' : ($detail_display_currency === 'EUR' ? '€' : ($detail_display_currency === 'GBP' ? '£' : $detail_display_currency . ' ')));
                            }

                            echo esc_html($detail_currency_symbol . number_format($detail_display_amount, 2));
                            ?>
                        </strong>
                    </span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Passengers', 'amadex'); ?></span>
                    <span class="amadex-info-item-value"><?php echo esc_html($booking['passenger_count']); ?></span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Channel', 'amadex'); ?></span>
                    <span class="amadex-info-item-value"><?php echo esc_html($booking['booking_channel']); ?></span>
                </div>
                <div class="amadex-info-item">
                    <span class="amadex-info-item-label"><?php _e('Created', 'amadex'); ?></span>
                    <span class="amadex-info-item-value"><?php echo esc_html(date('M j, Y g:i A', strtotime($booking['created_at']))); ?></span>
                </div>
            </div>

            <!-- Payment Information Card -->
            <div class="amadex-booking-card">
                <h3><?php _e('Payment Information', 'amadex'); ?></h3>
                <?php if (!empty($booking['payment'])): ?>
                    <div class="amadex-info-item">
                        <span class="amadex-info-item-label"><?php _e('Transaction ID', 'amadex'); ?></span>
                        <span class="amadex-info-item-value"><?php echo esc_html($booking['payment']['transaction_id'] ?? ''); ?></span>
                    </div>
                    <div class="amadex-info-item">
                        <span class="amadex-info-item-label"><?php _e('Payment Status', 'amadex'); ?></span>
                        <span class="amadex-info-item-value">
                            <span class="amadex-payment-status <?php echo esc_attr(strtolower(str_replace('_', '-', $booking['payment']['payment_status'] ?? ''))); ?>">
                                <?php echo esc_html($booking['payment']['payment_status'] ?? ''); ?>
                            </span>
                        </span>
                    </div>
                    <div class="amadex-info-item">
                        <span class="amadex-info-item-label"><?php _e('Method', 'amadex'); ?></span>
                        <span class="amadex-info-item-value"><?php echo esc_html($booking['payment']['payment_method'] ?? ''); ?></span>
                    </div>
                    <div class="amadex-info-item">
                        <span class="amadex-info-item-label"><?php _e('Amount', 'amadex'); ?></span>
                        <span class="amadex-info-item-value">
                            <strong><?php
                                    // Use same display currency logic as Total Amount above
                                    $payment_display_currency = isset($detail_display_currency) ? $detail_display_currency : 'USD';
                                    $payment_amount = floatval($booking['payment']['amount'] ?? 0);

                                    // If payment amount is in USD but display currency is different, convert it
                                    if ($payment_display_currency !== 'USD' && $payment_amount > 0 && class_exists('Amadex_Currency')) {
                                        // Payment amount is stored in USD, convert to display currency
                                        if (!empty($flight_data['currency_conversion']['exchange_rate'])) {
                                            $payment_exchange_rate = floatval($flight_data['currency_conversion']['exchange_rate']);
                                            $payment_amount = round($payment_amount * $payment_exchange_rate, 2);
                                        } else {
                                            $payment_amount = Amadex_Currency::convert($payment_amount, 'USD', $payment_display_currency);
                                        }
                                    }

                                    // Use proper currency symbol formatting
                                    if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_currency_symbol')) {
                                        $payment_currency_symbol = Amadex_Currency::get_currency_symbol($payment_display_currency);
                                    } else {
                                        // Fallback to basic symbols
                                        $payment_currency_symbol = ($payment_display_currency === 'USD') ? '$' : ($payment_display_currency === 'INR' ? '₹' : ($payment_display_currency === 'EUR' ? '€' : ($payment_display_currency === 'GBP' ? '£' : $payment_display_currency . ' ')));
                                    }

                                    echo esc_html($payment_currency_symbol . number_format($payment_amount, 2));
                                    ?></strong>
                        </span>
                    </div>
                    <?php if (!empty($booking['payment']['card_last4'])): ?>
                        <div class="amadex-info-item">
                            <span class="amadex-info-item-label"><?php _e('Card', 'amadex'); ?></span>
                            <span class="amadex-info-item-value">
                                <?php echo esc_html($booking['payment']['card_type'] ?? ''); ?>
                                <?php _e('ending in', 'amadex'); ?>
                                <?php echo esc_html($booking['payment']['card_last4']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($booking['payment']['auth_code'])): ?>
                        <div class="amadex-info-item">
                            <span class="amadex-info-item-label"><?php _e('Auth Code', 'amadex'); ?></span>
                            <span class="amadex-info-item-value"><?php echo esc_html($booking['payment']['auth_code']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="amadex-info-item">
                        <span class="amadex-info-item-label"><?php _e('Created', 'amadex'); ?></span>
                        <span class="amadex-info-item-value"><?php echo esc_html(date('M j, Y g:i A', strtotime($booking['payment']['created_at'] ?? $booking['created_at']))); ?></span>
                    </div>
                <?php else: ?>
                    <p style="color: #6b7280; font-size: 14px;"><?php _e('No payment information available.', 'amadex'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Passengers Section -->
        <?php if (!empty($booking['passengers'])): ?>
            <div class="amadex-booking-card" style="margin-bottom: 30px;">
                <h3><?php _e('Passengers', 'amadex'); ?></h3>
                <?php foreach ($booking['passengers'] as $passenger): ?>
                    <div class="amadex-passenger-card" style="background:#fff;border:1px solid #e8eaed;border-radius:10px;margin:12px;overflow:hidden;">
                        <div class="amadex-passenger-name">
                            <?php echo esc_html(trim(($passenger['title'] ?? '') . ' ' . ($passenger['first_name'] ?? '') . ' ' . ($passenger['last_name'] ?? ''))); ?>
                        </div>
                        <div class="amadex-passenger-details">
                            <div class="amadex-passenger-detail">
                                <span class="amadex-passenger-detail-label"><?php _e('Type', 'amadex'); ?></span>
                                <span class="amadex-passenger-detail-value"><?php echo esc_html($passenger['passenger_type'] ?? ''); ?></span>
                            </div>
                            <div class="amadex-passenger-detail">
                                <span class="amadex-passenger-detail-label"><?php _e('Date of Birth', 'amadex'); ?></span>
                                <span class="amadex-passenger-detail-value"><?php echo esc_html($passenger['date_of_birth'] ?? ''); ?></span>
                            </div>
                            <div class="amadex-passenger-detail">
                                <span class="amadex-passenger-detail-label"><?php _e('Gender', 'amadex'); ?></span>
                                <span class="amadex-passenger-detail-value"><?php echo esc_html($passenger['gender'] ?? ''); ?></span>
                            </div>
                            <?php if (!empty($passenger['passport_number'])): ?>
                                <div class="amadex-passenger-detail">
                                    <span class="amadex-passenger-detail-label"><?php _e('Passport Number', 'amadex'); ?></span>
                                    <span class="amadex-passenger-detail-value"><?php echo esc_html($passenger['passport_number']); ?></span>
                                </div>
                                <?php if (!empty($passenger['passport_expiry'])): ?>
                                    <div class="amadex-passenger-detail">
                                        <span class="amadex-passenger-detail-label"><?php _e('Passport Expiry', 'amadex'); ?></span>
                                        <span class="amadex-passenger-detail-value"><?php echo esc_html($passenger['passport_expiry']); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Flight Information -->
        <?php if (!empty($flight_data) && !empty($flight_data['itineraries'])): ?>
            <div class="amadex-flight-segments">
                <div class="amadex-booking-card">
                    <h3><?php _e('Flight Information', 'amadex'); ?></h3>
                    <?php foreach ($flight_data['itineraries'] as $itinerary_index => $itinerary): ?>
                        <div class="amadex-itinerary-section">
                            <div class="amadex-itinerary-header">
                                <h4 class="amadex-itinerary-title">
                                    <?php echo esc_html(sprintf(__('Itinerary %d', 'amadex'), $itinerary_index + 1)); ?>
                                </h4>
                                <?php if (!empty($itinerary['duration'])): ?>
                                    <span class="amadex-itinerary-duration">
                                        <?php echo esc_html($this->format_duration($itinerary['duration'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($itinerary['segments'])): ?>
                                <?php foreach ($itinerary['segments'] as $segment_index => $segment): ?>
                                    <div class="amadex-segment-card">
                                        <div class="amadex-segment-header">
                                            <div class="amadex-segment-route">
                                                <div class="amadex-segment-airports">
                                                    <?php
                                                    $dep_code = $segment['departure']['iataCode'] ?? $segment['departure']['iata_code'] ?? '---';
                                                    $arr_code = $segment['arrival']['iataCode'] ?? $segment['arrival']['iata_code'] ?? '---';
                                                    ?>
                                                    <span class="amadex-segment-airport"><?php echo esc_html($dep_code); ?></span>
                                                    <span class="amadex-segment-arrow">→</span>
                                                    <span class="amadex-segment-airport"><?php echo esc_html($arr_code); ?></span>
                                                </div>
                                                <div class="amadex-segment-flight">
                                                    <?php
                                                    $carrier = $segment['carrierCode'] ?? $segment['carrier_code'] ?? '';
                                                    $number = $segment['number'] ?? '';
                                                    if ($carrier && $number) {
                                                        echo '<span class="amadex-segment-flight-number">' . esc_html($carrier . ' ' . $number) . '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="amadex-segment-details">
                                            <?php if (!empty($segment['departure']['at'])): ?>
                                                <div class="amadex-segment-detail-item">
                                                    <span class="amadex-segment-detail-label"><?php _e('Departure', 'amadex'); ?></span>
                                                    <span class="amadex-segment-detail-value">
                                                        <?php
                                                        $dep_time = $segment['departure']['at'];
                                                        echo esc_html(date('M j, Y', strtotime($dep_time)));
                                                        ?>
                                                        <strong><?php echo esc_html(date('g:i A', strtotime($dep_time))); ?></strong>
                                                        <?php if (!empty($segment['departure']['terminal'])): ?>
                                                            <small><?php _e('Terminal', 'amadex'); ?> <?php echo esc_html($segment['departure']['terminal']); ?></small>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($segment['arrival']['at'])): ?>
                                                <div class="amadex-segment-detail-item">
                                                    <span class="amadex-segment-detail-label"><?php _e('Arrival', 'amadex'); ?></span>
                                                    <span class="amadex-segment-detail-value">
                                                        <?php
                                                        $arr_time = $segment['arrival']['at'];
                                                        echo esc_html(date('M j, Y', strtotime($arr_time)));
                                                        ?>
                                                        <strong><?php echo esc_html(date('g:i A', strtotime($arr_time))); ?></strong>
                                                        <?php if (!empty($segment['arrival']['terminal'])): ?>
                                                            <small><?php _e('Terminal', 'amadex'); ?> <?php echo esc_html($segment['arrival']['terminal']); ?></small>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($segment['duration'])): ?>
                                                <div class="amadex-segment-detail-item">
                                                    <span class="amadex-segment-detail-label"><?php _e('Duration', 'amadex'); ?></span>
                                                    <span class="amadex-segment-detail-value"><?php echo esc_html($this->format_duration($segment['duration'])); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($segment['aircraft']['code'])): ?>
                                                <div class="amadex-segment-detail-item">
                                                    <span class="amadex-segment-detail-label"><?php _e('Aircraft', 'amadex'); ?></span>
                                                    <span class="amadex-segment-detail-value"><?php echo esc_html($segment['aircraft']['code']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php
                                    // Show connecting flight notice if not last segment
                                    if ($segment_index < count($itinerary['segments']) - 1):
                                        $next_segment = $itinerary['segments'][$segment_index + 1];
                                        $arr_time = isset($segment['arrival']['at']) ? strtotime($segment['arrival']['at']) : null;
                                        $dep_time = isset($next_segment['departure']['at']) ? strtotime($next_segment['departure']['at']) : null;

                                        if ($arr_time && $dep_time) {
                                            $connecting_minutes = round(($dep_time - $arr_time) / 60);
                                            $connecting_hours = floor($connecting_minutes / 60);
                                            $connecting_mins = $connecting_minutes % 60;
                                            $connecting_time = ($connecting_hours > 0 ? $connecting_hours . 'h ' : '') . $connecting_mins . 'm';
                                        } else {
                                            $connecting_time = '';
                                        }
                                    ?>
                                        <div class="amadex-connecting-flight">
                                            <?php _e('Layover at', 'amadex'); ?>
                                            <strong><?php echo esc_html($segment['arrival']['iataCode'] ?? $segment['arrival']['iata_code'] ?? ''); ?></strong>
                                            <?php if ($connecting_time): ?>
                                                <span class="amadex-connecting-flight-info"><?php echo esc_html($connecting_time); ?> <?php _e('connecting time', 'amadex'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.amadex-update-status').on('click', function() {
                var bookingId = $(this).data('booking-id');
                var status = $('#booking-status').val();
                var $btn = $(this);

                $btn.prop('disabled', true).text('<?php _e('Updating...', 'amadex'); ?>');

                $.ajax({
                    url: AmadexLeads.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_update_booking_status',
                        nonce: AmadexLeads.nonce,
                        booking_id: bookingId,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Status updated successfully', 'amadex'); ?>');
                            location.reload();
                        } else {
                            alert('<?php _e('Error:', 'amadex'); ?> ' + (response.data?.message || '<?php _e('Unknown error', 'amadex'); ?>'));
                            $btn.prop('disabled', false).text('<?php _e('Update', 'amadex'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'amadex'); ?>');
                        $btn.prop('disabled', false).text('<?php _e('Update', 'amadex'); ?>');
                    }
                });
            });

            $('.amadex-update-pnr').on('click', function() {
                var bookingId = $(this).data('booking-id');
                var pnr = $('#booking-pnr').val();
                var $btn = $(this);

                $btn.prop('disabled', true).text('<?php _e('Updating...', 'amadex'); ?>');

                $.ajax({
                    url: AmadexLeads.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_update_booking_pnr',
                        nonce: AmadexLeads.nonce,
                        booking_id: bookingId,
                        pnr: pnr
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('PNR updated successfully', 'amadex'); ?>');
                            $btn.prop('disabled', false).text('<?php _e('Update', 'amadex'); ?>');
                        } else {
                            alert('<?php _e('Error:', 'amadex'); ?> ' + (response.data?.message || '<?php _e('Unknown error', 'amadex'); ?>'));
                            $btn.prop('disabled', false).text('<?php _e('Update', 'amadex'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'amadex'); ?>');
                        $btn.prop('disabled', false).text('<?php _e('Update', 'amadex'); ?>');
                    }
                });
            });
        });
    </script>
<?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Update booking status
     */
    public function ajax_update_booking_status()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');

        $result = $this->database->update_booking_status($booking_id, $status);

        if ($result) {
            wp_send_json_success(array('message' => 'Booking status updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }

    /**
     * AJAX: Update booking PNR
     */
    public function ajax_update_booking_pnr()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $pnr = sanitize_text_field($_POST['pnr'] ?? '');

        $result = $this->database->update_booking_pnr($booking_id, $pnr);

        if ($result) {
            wp_send_json_success(array('message' => 'PNR updated'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update PNR'));
        }
    }

    /**
     * AJAX: Bulk delete leads
     */
    public function ajax_bulk_delete_leads()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $lead_ids = isset($_POST['lead_ids']) ? $_POST['lead_ids'] : array();

        if (empty($lead_ids) || !is_array($lead_ids)) {
            wp_send_json_error(array('message' => 'No leads selected'));
        }

        $deleted_count = $this->database->bulk_delete_leads($lead_ids);

        if ($deleted_count > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    _n('%d lead deleted successfully.', '%d leads deleted successfully.', $deleted_count, 'amadex'),
                    $deleted_count
                ),
                'deleted_count' => $deleted_count
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete leads'));
        }
    }

    /**
     * AJAX: Bulk update lead status
     */
    public function ajax_bulk_update_lead_status()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        $lead_ids = isset($_POST['lead_ids']) ? array_map('intval', (array) $_POST['lead_ids']) : array();
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        $valid_statuses = array('NEW', 'ASSIGNED', 'IN_PROGRESS', 'CONTACTED', 'CONVERTED', 'CANCELLED');
        if (empty($lead_ids) || !in_array($new_status, $valid_statuses, true)) {
            wp_send_json_error(array('message' => __('Invalid request.', 'amadex')));
        }
        $updated = 0;
        foreach ($lead_ids as $lid) {
            if ($lid > 0 && $this->database->update_lead_status($lid, $new_status, '')) {
                $updated++;
            }
        }
        if ($updated > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    _n('%d lead status updated.', '%d leads status updated.', $updated, 'amadex'),
                    $updated
                ),
                'updated_count' => $updated
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update lead status.', 'amadex')));
        }
    }

    /**
     * AJAX: Bulk update booking status
     */
    public function ajax_bulk_update_booking_status()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        $booking_ids = isset($_POST['booking_ids']) ? array_map('intval', (array) $_POST['booking_ids']) : array();
        $new_status = sanitize_text_field($_POST['status'] ?? '');
        $valid_statuses = array('PENDING', 'CONFIRMED', 'TICKETED', 'CANCELLED', 'REFUNDED');
        if (empty($booking_ids) || !in_array($new_status, $valid_statuses, true)) {
            wp_send_json_error(array('message' => __('Invalid request.', 'amadex')));
        }
        $updated = 0;
        foreach ($booking_ids as $bid) {
            if ($bid > 0 && $this->database->update_booking_status($bid, $new_status)) {
                $updated++;
            }
        }
        if ($updated > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    _n('%d booking status updated.', '%d bookings status updated.', $updated, 'amadex'),
                    $updated
                ),
                'updated_count' => $updated
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update booking status.', 'amadex')));
        }
    }

    /**
     * AJAX: Set environment
     */
    public function ajax_set_environment()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $environment = sanitize_text_field($_POST['environment'] ?? '');
        if (!in_array($environment, array('PRODUCTION', 'STAGING'))) {
            wp_send_json_error(array('message' => 'Invalid environment'));
        }

        require_once(AMADEX_PATH . 'includes/class-amadex-environment-manager.php');
        $result = Amadex_Environment_Manager::set_current_environment($environment);

        if ($result) {
            wp_send_json_success(array('message' => 'Environment changed to ' . $environment));
        } else {
            wp_send_json_error(array('message' => 'Failed to set environment'));
        }
    }

    /**
     * AJAX: Delete booking (soft delete)
     */
    public function ajax_delete_booking()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $hard_delete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === 'true';
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if ($booking_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid booking ID'));
        }

        if ($hard_delete) {
            $result = $this->database->hard_delete_booking($booking_id);
            $message = 'Booking permanently deleted';
        } else {
            $result = $this->database->soft_delete_booking($booking_id, $reason);
            $message = 'Booking archived';
        }

        if ($result) {
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete booking'));
        }
    }

    /**
     * AJAX: Bulk delete bookings
     */
    public function ajax_bulk_delete_bookings()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $booking_ids = isset($_POST['booking_ids']) ? array_map('intval', (array)$_POST['booking_ids']) : array();
        $hard_delete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === 'true';
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        if (empty($booking_ids)) {
            wp_send_json_error(array('message' => 'No bookings selected'));
        }

        $deleted = $this->database->bulk_delete_bookings($booking_ids, $hard_delete, $reason);

        if ($deleted > 0) {
            $message = $hard_delete ? "{$deleted} booking(s) permanently deleted" : "{$deleted} booking(s) archived";
            wp_send_json_success(array('message' => $message, 'deleted' => $deleted));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete bookings'));
        }
    }

    /**
     * AJAX: Generate PDF
     */
    public function ajax_generate_pdf()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }

        $type = sanitize_text_field($_GET['type'] ?? 'confirmation');
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : null;
        $lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : null;
        $download = isset($_GET['download']) && $_GET['download'] === '1';

        if (!$booking_id && !$lead_id) {
            wp_send_json_error(array('message' => 'Booking ID or Lead ID required'));
        }

        $pdf_generator = new Amadex_PDF_Generator();
        $pdf_generator->output_pdf($type, $booking_id, $lead_id, $download);
    }

    /**
     * AJAX: Export leads
     */
    public function ajax_export_leads()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        $filters = array();

        if (isset($_GET['status'])) $filters['status'] = sanitize_text_field($_GET['status']);
        if (isset($_GET['lead_type'])) $filters['lead_type'] = sanitize_text_field($_GET['lead_type']);
        if (isset($_GET['environment'])) $filters['environment'] = sanitize_text_field($_GET['environment']);
        if (isset($_GET['date_from'])) $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        if (isset($_GET['date_to'])) $filters['date_to'] = sanitize_text_field($_GET['date_to']);

        $selected_ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : null;
        if ($selected_ids) {
            $filters['ids'] = $selected_ids;
        }

        $exporter = new Amadex_Data_Exporter();
        $exporter->export_leads($format, $filters);
    }

    /**
     * AJAX: Export bookings
     */
    public function ajax_export_bookings()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $format = sanitize_text_field($_GET['format'] ?? 'csv');
        $filters = array();

        if (isset($_GET['status'])) $filters['status'] = sanitize_text_field($_GET['status']);
        if (isset($_GET['channel'])) $filters['booking_channel'] = sanitize_text_field($_GET['channel']);
        if (isset($_GET['environment'])) $filters['environment'] = sanitize_text_field($_GET['environment']);
        if (isset($_GET['date_from'])) $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        if (isset($_GET['date_to'])) $filters['date_to'] = sanitize_text_field($_GET['date_to']);

        $selected_ids = isset($_GET['ids']) ? array_map('intval', explode(',', $_GET['ids'])) : null;
        if ($selected_ids) {
            $filters['ids'] = $selected_ids;
        }

        $exporter = new Amadex_Data_Exporter();
        $exporter->export_bookings($format, $filters);
    }
}

// Initialize if in admin
if (is_admin()) {
    new Amadex_Leads();
}
