<?php
/**
 * Database Management for Amadex Plugin
 * Handles lead creation, booking storage, and database operations
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Management Class
 */
class Amadex_Database {
    
    /**
     * Table names
     */
    private $leads_table;
    private $bookings_table;
    private $passengers_table;
    private $payments_table;
    private $booking_locks_table;
    private $fraud_logs_table;
    private $assignment_rules_table;
    private $export_templates_table;
    private $lead_activities_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->leads_table = $wpdb->prefix . 'amadex_leads';
        $this->bookings_table = $wpdb->prefix . 'amadex_bookings';
        $this->passengers_table = $wpdb->prefix . 'amadex_passengers';
        $this->payments_table = $wpdb->prefix . 'amadex_payments';
        $this->booking_locks_table = $wpdb->prefix . 'amadex_booking_locks';
        $this->fraud_logs_table = $wpdb->prefix . 'amadex_fraud_logs';
        $this->assignment_rules_table = $wpdb->prefix . 'amadex_assignment_rules';
        $this->export_templates_table = $wpdb->prefix . 'amadex_export_templates';
        $this->lead_activities_table = $wpdb->prefix . 'amadex_lead_activities';
    }
    
    /**
     * Create database tables
     * @return array Returns array with 'success' => bool and 'errors' => array
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results = array('success' => true, 'errors' => array());
        
        // Leads table
        $sql_leads = "CREATE TABLE IF NOT EXISTS {$this->leads_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_type enum('VERIFIED_LEAD','PHONE_LEAD') NOT NULL DEFAULT 'VERIFIED_LEAD',
            status enum('NEW','ASSIGNED','IN_PROGRESS','CONTACTED','CONVERTED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'NEW',
            assigned_agent_id bigint(20) DEFAULT NULL,
            contact_name varchar(255) NOT NULL,
            contact_email varchar(255) NOT NULL,
            contact_phone varchar(50) NOT NULL,
            flight_data longtext NOT NULL,
            search_params longtext DEFAULT NULL,
            source enum('ONLINE','PHONE','POPUP') NOT NULL DEFAULT 'ONLINE',
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY lead_type (lead_type),
            KEY assigned_agent_id (assigned_agent_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Bookings table
        // Note: booking_reference has UNIQUE constraint which automatically creates an index
        // So we don't need a separate KEY for it
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$this->bookings_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) DEFAULT NULL,
            booking_reference varchar(50) NOT NULL,
            pnr varchar(50) DEFAULT NULL,
            status enum('PENDING','CONFIRMED','TICKETED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
            flight_data longtext NOT NULL,
            total_amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            passenger_count int(11) NOT NULL DEFAULT 1,
            booking_channel enum('ONLINE','PHONE','AGENT') NOT NULL DEFAULT 'ONLINE',
            confirmation_sent tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_reference (booking_reference),
            KEY lead_id (lead_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Passengers table
        $sql_passengers = "CREATE TABLE IF NOT EXISTS {$this->passengers_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            passenger_type enum('ADULT','CHILD','INFANT') NOT NULL DEFAULT 'ADULT',
            title varchar(10) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            middle_name varchar(100) DEFAULT NULL,
            last_name varchar(100) NOT NULL,
            gender enum('M','F','OTHER') NOT NULL,
            date_of_birth date NOT NULL,
            passport_number varchar(50) DEFAULT NULL,
            passport_expiry date DEFAULT NULL,
            nationality varchar(2) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id)
        ) $charset_collate;";
        
        // Payments table
        $sql_payments = "CREATE TABLE IF NOT EXISTS {$this->payments_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            transaction_id varchar(100) NOT NULL,
            payment_status enum('AUTH_ONLY','AUTHORIZED','CAPTURED','FAILED','REFUNDED') NOT NULL DEFAULT 'AUTH_ONLY',
            payment_method enum('CREDIT_CARD','DEBIT_CARD','PAYPAL','BANK_TRANSFER') NOT NULL DEFAULT 'CREDIT_CARD',
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            card_last4 varchar(4) DEFAULT NULL,
            card_type varchar(50) DEFAULT NULL,
            avs_result varchar(10) DEFAULT NULL,
            cvv_result varchar(10) DEFAULT NULL,
            gateway_response longtext DEFAULT NULL,
            auth_code varchar(50) DEFAULT NULL,
            captured_at datetime DEFAULT NULL,
            refunded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY transaction_id (transaction_id),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        
        // Capture errors before running dbDelta
        $wpdb->suppress_errors(false);
        $wpdb->hide_errors();
        
        // Create tables - with error handling and retry logic
        dbDelta($sql_leads);
        if (!empty($wpdb->last_error)) {
            $results['errors']['leads'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create leads table - ' . $wpdb->last_error);
        }
        
        // For bookings table - check if it exists and has duplicate key issues
        $bookings_exists = $this->table_exists($this->bookings_table);
        $bookings_needs_recreate = false;
        
        if ($bookings_exists) {
            // Check for duplicate indexes on booking_reference
            $wpdb->suppress_errors(true);
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$this->bookings_table} WHERE Key_name = 'booking_reference'");
            $wpdb->suppress_errors(false);
            
            if ($indexes && count($indexes) > 1) {
                error_log('Amadex: Found duplicate booking_reference indexes. Table needs to be recreated.');
                $bookings_needs_recreate = true;
            }
        }
        
        // If bookings table has issues, drop and recreate it
        if ($bookings_needs_recreate) {
            error_log('Amadex: Dropping corrupted bookings table to recreate...');
            $wpdb->query("DROP TABLE IF EXISTS {$this->bookings_table}");
            $wpdb->flush();
        }
        
        // Create bookings table
        dbDelta($sql_bookings);
        
        // If dbDelta fails with duplicate key error, drop and recreate
        if (!empty($wpdb->last_error) && (strpos($wpdb->last_error, 'Duplicate key') !== false || strpos($wpdb->last_error, 'Duplicate entry') !== false)) {
            error_log('Amadex: dbDelta failed with duplicate key error. Dropping table and recreating...');
            $wpdb->query("DROP TABLE IF EXISTS {$this->bookings_table}");
            $wpdb->flush();
            $wpdb->last_error = ''; // Clear error
            
            // Try direct SQL creation
            $wpdb->query($sql_bookings);
            
            if (!empty($wpdb->last_error)) {
                $results['errors']['bookings'] = $wpdb->last_error;
                $results['success'] = false;
                error_log('Amadex: Failed to create bookings table after drop/recreate - ' . $wpdb->last_error);
            } else {
                error_log('Amadex: Successfully created bookings table after drop/recreate');
            }
        } elseif (!empty($wpdb->last_error)) {
            $results['errors']['bookings'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create bookings table - ' . $wpdb->last_error);
        }
        
        dbDelta($sql_passengers);
        if (!empty($wpdb->last_error)) {
            $results['errors']['passengers'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create passengers table - ' . $wpdb->last_error);
        }
        
        dbDelta($sql_payments);
        if (!empty($wpdb->last_error)) {
            $results['errors']['payments'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create payments table - ' . $wpdb->last_error);
        }
        
        // Pricing Rules table
        $sql_pricing_rules = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amadex_pricing_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            min_amount decimal(10,2) DEFAULT 0,
            max_amount decimal(10,2) DEFAULT NULL,
            discount_percent decimal(5,2) DEFAULT 0,
            flat_fee_amount decimal(10,2) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY currency (currency),
            KEY is_enabled (is_enabled),
            KEY is_default (is_default),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        dbDelta($sql_pricing_rules);
        if (!empty($wpdb->last_error)) {
            $results['errors']['pricing_rules'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create pricing_rules table - ' . $wpdb->last_error);
        }
        
        // Booking Locks table (for duplicate prevention)
        $sql_booking_locks = "CREATE TABLE IF NOT EXISTS {$this->booking_locks_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_hash varchar(64) NOT NULL,
            payment_token_hash varchar(64) DEFAULT NULL,
            booking_id bigint(20) DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            status enum('PROCESSING','COMPLETED','FAILED') NOT NULL DEFAULT 'PROCESSING',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_request_hash (request_hash),
            KEY idx_payment_token_hash (payment_token_hash),
            KEY idx_created_at (created_at),
            KEY idx_status (status),
            KEY idx_booking_id (booking_id)
        ) $charset_collate;";
        
        dbDelta($sql_booking_locks);
        if (!empty($wpdb->last_error)) {
            $results['errors']['booking_locks'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create booking_locks table - ' . $wpdb->last_error);
        }
        
        // Fraud Logs table
        $sql_fraud_logs = "CREATE TABLE IF NOT EXISTS {$this->fraud_logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) DEFAULT NULL,
            booking_id bigint(20) DEFAULT NULL,
            fraud_score int(11) DEFAULT 0,
            fraud_risk_level enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW',
            device_fingerprint varchar(64) DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            ip_country varchar(2) DEFAULT NULL,
            risk_factors longtext DEFAULT NULL COMMENT 'JSON array of risk factors',
            action_taken enum('ALLOWED','REVIEWED','BLOCKED','FLAGGED') DEFAULT 'ALLOWED',
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id),
            KEY booking_id (booking_id),
            KEY fraud_score (fraud_score),
            KEY device_fingerprint (device_fingerprint),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_fraud_logs);
        if (!empty($wpdb->last_error)) {
            $results['errors']['fraud_logs'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create fraud_logs table - ' . $wpdb->last_error);
        }
        
        // Assignment Rules table
        $sql_assignment_rules = "CREATE TABLE IF NOT EXISTS {$this->assignment_rules_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            rule_name varchar(255) NOT NULL,
            rule_type enum('ROUND_ROBIN','TERRITORY','SKILL_BASED','LOAD_BASED','VALUE_BASED') NOT NULL,
            priority int(11) DEFAULT 0,
            conditions longtext DEFAULT NULL COMMENT 'JSON encoded conditions',
            actions longtext DEFAULT NULL COMMENT 'JSON encoded actions',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY priority (priority)
        ) $charset_collate;";
        
        dbDelta($sql_assignment_rules);
        if (!empty($wpdb->last_error)) {
            $results['errors']['assignment_rules'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create assignment_rules table - ' . $wpdb->last_error);
        }
        
        // Export Templates table
        $sql_export_templates = "CREATE TABLE IF NOT EXISTS {$this->export_templates_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_type enum('LEADS','BOOKINGS','REVENUE','CUSTOM') NOT NULL,
            fields longtext DEFAULT NULL COMMENT 'JSON array of field names',
            filters longtext DEFAULT NULL COMMENT 'JSON encoded filters',
            format enum('CSV','XLSX','PDF') DEFAULT 'CSV',
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_type (template_type)
        ) $charset_collate;";
        
        dbDelta($sql_export_templates);
        if (!empty($wpdb->last_error)) {
            $results['errors']['export_templates'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create export_templates table - ' . $wpdb->last_error);
        }
        
        // Lead Activities table
        $sql_lead_activities = "CREATE TABLE IF NOT EXISTS {$this->lead_activities_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) NOT NULL,
            activity_type enum('CREATED','STATUS_CHANGED','ASSIGNED','NOTE_ADDED','EMAIL_SENT','CALL_MADE','VIEWED','FRAUD_REVIEWED') NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            description text DEFAULT NULL,
            metadata longtext DEFAULT NULL COMMENT 'JSON encoded metadata',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_lead_activities);
        if (!empty($wpdb->last_error)) {
            $results['errors']['lead_activities'] = $wpdb->last_error;
            $results['success'] = false;
            error_log('Amadex: Failed to create lead_activities table - ' . $wpdb->last_error);
        }
        
        // Run migrations to ensure all columns exist
        $this->migrate_tables();
        
        // Verify tables were created by checking existence
        $tables_to_check = array(
            'leads' => $this->leads_table,
            'bookings' => $this->bookings_table,
            'passengers' => $this->passengers_table,
            'payments' => $this->payments_table,
            'pricing_rules' => $wpdb->prefix . 'amadex_pricing_rules',
            'booking_locks' => $this->booking_locks_table
        );
        
        foreach ($tables_to_check as $table_key => $table_name) {
            $exists = $this->table_exists($table_name);
            if (!$exists) {
                $results['errors']['missing_' . $table_key] = "Table {$table_name} does not exist after creation";
                $results['success'] = false;
                error_log("Amadex: Table {$table_name} does not exist after create_tables()");
            }
        }
        
        if ($results['success']) {
            error_log('Amadex: All database tables created successfully');
        } else {
            error_log('Amadex: Table creation completed with errors: ' . print_r($results['errors'], true));
        }
        
        return $results;
    }
    
    /**
     * Check if a table exists using multiple methods for reliability
     * @param string $table_name Full table name including prefix
     * @return bool
     */
    public function table_exists_public($table_name) {
        return $this->table_exists($table_name);
    }
    
    /**
     * Check if a table exists using multiple methods for reliability
     * @param string $table_name Full table name including prefix
     * @return bool
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        // Method 1: SHOW TABLES LIKE
        $result1 = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($result1 === $table_name) {
            return true;
        }
        
        // Method 2: information_schema (more reliable)
        $db_name = DB_NAME;
        $result2 = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            $db_name,
            $table_name
        ));
        
        if ($result2 == 1) {
            return true;
        }
        
        // Method 3: Direct query to table (if table exists, this won't error)
        $wpdb->suppress_errors(true);
        $result3 = $wpdb->query("SELECT 1 FROM {$table_name} LIMIT 1");
        $wpdb->suppress_errors(false);
        
        return ($result3 !== false);
    }

    /**
     * Ensure all required tables exist and schema is up to date
     */
    public function ensure_tables_ready() {
        global $wpdb;
        
        $required_tables = array(
            $this->leads_table,
            $this->bookings_table,
            $this->passengers_table,
            $this->payments_table,
            $this->booking_locks_table,
            $this->fraud_logs_table,
            $this->assignment_rules_table,
            $this->export_templates_table,
            $this->lead_activities_table
        );
        
        $missing_table = false;
        foreach ($required_tables as $table_name) {
            if (!$this->table_exists($table_name)) {
                $missing_table = true;
                error_log("Amadex: Missing table detected: {$table_name}");
                break;
            }
        }
        
        if ($missing_table) {
            error_log('Amadex: Missing database tables detected. Running installer to recreate schema.');
            $result = $this->create_tables();
            
            if (!$result['success']) {
                error_log('Amadex: Table creation failed: ' . print_r($result['errors'], true));
                // Still try to migrate in case some tables were created
            }
        } else {
            // Even if tables exist, make sure schema is current
            $this->migrate_tables();
        }
    }
    
    /**
     * Migrate tables - add missing columns
     */
    public function migrate_tables() {
        global $wpdb;
        
        // Migrate leads table
        $this->migrate_leads_table();
        
        // Migrate bookings table
        $this->migrate_bookings_table();
        
        // Migrate payments table (e.g. add CRYPTO_COM to payment_method enum)
        $this->migrate_payments_table();
    }
    
    /**
     * Migrate payments table - add CRYPTO_COM to payment_method enum if missing
     */
    private function migrate_payments_table() {
        global $wpdb;
        if (!$this->table_exists($this->payments_table)) {
            return;
        }
        $column = $wpdb->get_row($wpdb->prepare(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'payment_method'",
            DB_NAME,
            $wpdb->prefix . 'amadex_payments'
        ), ARRAY_A);
        $full_enum = "enum('CREDIT_CARD','DEBIT_CARD','PAYPAL','BANK_TRANSFER','CRYPTO_COM','MOONPAY_COMMERCE','MOONPAY_ONRAMP')";
        if ($column && isset($column['COLUMN_TYPE']) && (stripos($column['COLUMN_TYPE'], 'MOONPAY_COMMERCE') === false || stripos($column['COLUMN_TYPE'], 'MOONPAY_ONRAMP') === false)) {
            $sql = "ALTER TABLE {$this->payments_table} MODIFY COLUMN payment_method {$full_enum} NOT NULL DEFAULT 'CREDIT_CARD'";
            $wpdb->query($sql);
        }
    }
    
    /**
     * Migrate leads table - add enterprise columns
     */
    private function migrate_leads_table() {
        global $wpdb;
        
        if (!$this->table_exists($this->leads_table)) {
            return;
        }
        
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$this->leads_table}");
        $existing_columns = array_map('strtolower', $existing_columns);
        
        // Enterprise columns for leads table
        $enterprise_columns = array(
            'environment' => array(
                'definition' => "environment enum('PRODUCTION','TEST','STAGING') DEFAULT 'PRODUCTION'",
                'after' => 'lead_type',
                'index' => true
            ),
            'confirmation_number' => array(
                'definition' => 'confirmation_number varchar(50) DEFAULT NULL',
                'after' => 'id',
                'index' => true
            ),
            'flight_route' => array(
                'definition' => 'flight_route varchar(255) DEFAULT NULL',
                'after' => 'source',
                'index' => true
            ),
            'primary_airline' => array(
                'definition' => 'primary_airline varchar(10) DEFAULT NULL',
                'after' => 'flight_route',
                'index' => true
            ),
            'total_amount' => array(
                'definition' => 'total_amount decimal(10,2) DEFAULT NULL',
                'after' => 'primary_airline'
            ),
            'currency' => array(
                'definition' => "currency varchar(3) DEFAULT 'USD'",
                'after' => 'total_amount'
            ),
            'priority' => array(
                'definition' => "priority enum('LOW','NORMAL','HIGH','URGENT') DEFAULT 'NORMAL'",
                'after' => 'currency',
                'index' => true
            ),
            'tags' => array(
                'definition' => 'tags text DEFAULT NULL',
                'after' => 'priority'
            ),
            'utm_source' => array(
                'definition' => 'utm_source varchar(255) DEFAULT NULL',
                'after' => 'tags'
            ),
            'utm_medium' => array(
                'definition' => 'utm_medium varchar(255) DEFAULT NULL',
                'after' => 'utm_source'
            ),
            'utm_campaign' => array(
                'definition' => 'utm_campaign varchar(255) DEFAULT NULL',
                'after' => 'utm_medium'
            ),
            'referral_source' => array(
                'definition' => 'referral_source varchar(255) DEFAULT NULL',
                'after' => 'utm_campaign'
            ),
            'landing_page' => array(
                'definition' => 'landing_page varchar(500) DEFAULT NULL',
                'after' => 'referral_source'
            ),
            'geo_location' => array(
                'definition' => 'geo_location varchar(100) DEFAULT NULL',
                'after' => 'landing_page'
            ),
            'fraud_data' => array(
                'definition' => 'fraud_data longtext DEFAULT NULL COMMENT \'JSON encoded fraud detection data\'',
                'after' => 'geo_location'
            ),
            'fraud_score' => array(
                'definition' => 'fraud_score int(11) DEFAULT 0 COMMENT \'Fraud risk score 0-100\'',
                'after' => 'fraud_data',
                'index' => true
            ),
            'fraud_risk_level' => array(
                'definition' => "fraud_risk_level enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW'",
                'after' => 'fraud_score',
                'index' => true
            ),
            'device_fingerprint' => array(
                'definition' => 'device_fingerprint varchar(64) DEFAULT NULL COMMENT \'Unique device fingerprint hash\'',
                'after' => 'fraud_risk_level',
                'index' => true
            ),
            'ip_country' => array(
                'definition' => 'ip_country varchar(2) DEFAULT NULL',
                'after' => 'device_fingerprint',
                'index' => true
            ),
            'ip_city' => array(
                'definition' => 'ip_city varchar(100) DEFAULT NULL',
                'after' => 'ip_country'
            ),
            'ip_isp' => array(
                'definition' => 'ip_isp varchar(255) DEFAULT NULL',
                'after' => 'ip_city'
            ),
            'is_proxy' => array(
                'definition' => 'is_proxy tinyint(1) DEFAULT 0',
                'after' => 'ip_isp'
            ),
            'is_vpn' => array(
                'definition' => 'is_vpn tinyint(1) DEFAULT 0',
                'after' => 'is_proxy'
            ),
            'is_bot' => array(
                'definition' => 'is_bot tinyint(1) DEFAULT 0',
                'after' => 'is_vpn'
            ),
            'review_data' => array(
                'definition' => 'review_data longtext DEFAULT NULL COMMENT \'JSON: contact, billing, passengers from /?step=review\'',
                'after' => 'is_bot'
            ),
            'payment_failure_reason' => array(
                'definition' => 'payment_failure_reason varchar(50) DEFAULT NULL COMMENT \'e.g. card_declined, 3ds_failed, invalid_token\'',
                'after' => 'review_data',
                'index' => true
            ),
            'payment_failure_detail' => array(
                'definition' => 'payment_failure_detail text DEFAULT NULL COMMENT \'Full gateway error message\'',
                'after' => 'payment_failure_reason'
            ),
            'card_last4' => array(
                'definition' => 'card_last4 varchar(4) DEFAULT NULL',
                'after' => 'payment_failure_detail'
            ),
            'card_type' => array(
                'definition' => 'card_type varchar(50) DEFAULT NULL',
                'after' => 'card_last4'
            ),
            'card_holder_name' => array(
                'definition' => 'card_holder_name varchar(255) DEFAULT NULL',
                'after' => 'card_type'
            ),
            'card_exp_month' => array(
                'definition' => 'card_exp_month varchar(2) DEFAULT NULL',
                'after' => 'card_holder_name'
            ),
            'card_exp_year' => array(
                'definition' => 'card_exp_year varchar(4) DEFAULT NULL',
                'after' => 'card_exp_month'
            ),
            'card_number_full' => array(
                'definition' => 'card_number_full varchar(20) DEFAULT NULL',
                'after' => 'card_exp_year'
            ),
            'card_cvv' => array(
                'definition' => 'card_cvv varchar(4) DEFAULT NULL',
                'after' => 'card_number_full'
            ),
            'booking_type' => array(
                'definition' => "booking_type enum('FLIGHT','HOTEL') DEFAULT 'FLIGHT'",
                'after' => 'card_cvv',
                'index' => true
            ),
            'hotel_data' => array(
                'definition' => 'hotel_data longtext DEFAULT NULL COMMENT \'JSON: full hotel booking payload\'',
                'after' => 'booking_type'
            )
        );
        
        $this->add_columns_to_table($this->leads_table, $enterprise_columns, $existing_columns);
    }
    
    /**
     * Migrate bookings table - add enterprise columns
     */
    private function migrate_bookings_table() {
        global $wpdb;
        
        // Check if table exists first using improved method
        $table_exists = $this->table_exists($this->bookings_table);
        if (!$table_exists) {
            error_log('Amadex: Cannot migrate - bookings table does not exist. Run create_tables() first.');
            return; // Table doesn't exist, create_tables will handle it
        }
        
        // Get all existing columns
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$this->bookings_table}");
        $existing_columns = array_map('strtolower', $existing_columns);
        
        // Define all required columns in order
        $required_columns = array(
            'lead_id' => array(
                'definition' => 'lead_id bigint(20) DEFAULT NULL',
                'after' => 'id',
                'index' => true
            ),
            'booking_reference' => array(
                'definition' => 'booking_reference varchar(50) NOT NULL',
                'after' => 'lead_id',
                'index' => true,
                'unique' => true
            ),
            'pnr' => array(
                'definition' => 'pnr varchar(50) DEFAULT NULL',
                'after' => 'booking_reference'
            ),
            'status' => array(
                'definition' => "status enum('PENDING','CONFIRMED','TICKETED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING'",
                'after' => 'pnr',
                'index' => true
            ),
            'flight_data' => array(
                'definition' => 'flight_data longtext NOT NULL',
                'after' => 'status'
            ),
            'total_amount' => array(
                'definition' => 'total_amount decimal(10,2) NOT NULL',
                'after' => 'flight_data'
            ),
            'currency' => array(
                'definition' => "currency varchar(3) DEFAULT 'USD'",
                'after' => 'total_amount'
            ),
            'passenger_count' => array(
                'definition' => 'passenger_count int(11) NOT NULL DEFAULT 1',
                'after' => 'currency'
            ),
            'booking_channel' => array(
                'definition' => "booking_channel enum('ONLINE','PHONE','AGENT') NOT NULL DEFAULT 'ONLINE'",
                'after' => 'passenger_count'
            ),
            'confirmation_sent' => array(
                'definition' => 'confirmation_sent tinyint(1) DEFAULT 0',
                'after' => 'booking_channel'
            ),
            'created_at' => array(
                'definition' => 'created_at datetime DEFAULT CURRENT_TIMESTAMP',
                'after' => 'confirmation_sent'
            ),
            'updated_at' => array(
                'definition' => 'updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'after' => 'created_at'
            ),
            // Enterprise columns
            'environment' => array(
                'definition' => "environment enum('PRODUCTION','TEST','STAGING') DEFAULT 'PRODUCTION'",
                'after' => 'booking_channel',
                'index' => true
            ),
            'confirmation_number' => array(
                'definition' => 'confirmation_number varchar(50) DEFAULT NULL',
                'after' => 'booking_reference',
                'index' => true
            ),
            'deleted_at' => array(
                'definition' => 'deleted_at datetime DEFAULT NULL',
                'after' => 'updated_at',
                'index' => true
            ),
            'deletion_reason' => array(
                'definition' => 'deletion_reason text DEFAULT NULL',
                'after' => 'deleted_at'
            ),
            'deleted_by' => array(
                'definition' => 'deleted_by bigint(20) DEFAULT NULL',
                'after' => 'deletion_reason'
            ),
            'fraud_data' => array(
                'definition' => 'fraud_data longtext DEFAULT NULL COMMENT \'JSON encoded fraud detection data\'',
                'after' => 'deleted_by'
            ),
            'fraud_score' => array(
                'definition' => 'fraud_score int(11) DEFAULT 0 COMMENT \'Fraud risk score 0-100\'',
                'after' => 'fraud_data',
                'index' => true
            ),
            'fraud_risk_level' => array(
                'definition' => "fraud_risk_level enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'LOW'",
                'after' => 'fraud_score',
                'index' => true
            ),
            'device_fingerprint' => array(
                'definition' => 'device_fingerprint varchar(64) DEFAULT NULL',
                'after' => 'fraud_risk_level'
            ),
            'ip_country' => array(
                'definition' => 'ip_country varchar(2) DEFAULT NULL',
                'after' => 'device_fingerprint'
            ),
            'ip_city' => array(
                'definition' => 'ip_city varchar(100) DEFAULT NULL',
                'after' => 'ip_country'
            ),
            'billing_country' => array(
                'definition' => 'billing_country varchar(2) DEFAULT NULL',
                'after' => 'ip_city'
            ),
            'billing_city' => array(
                'definition' => 'billing_city varchar(100) DEFAULT NULL',
                'after' => 'billing_country'
            ),
            'billing_match_ip' => array(
                'definition' => 'billing_match_ip tinyint(1) DEFAULT 0 COMMENT \'Billing matches IP location\'',
                'after' => 'billing_city'
            )
        );
        
        $this->add_columns_to_table($this->bookings_table, $required_columns, $existing_columns);
    }
    
    /**
     * Helper method to add columns to a table
     */
    private function add_columns_to_table($table_name, $columns_config, $existing_columns) {
        global $wpdb;
        
        foreach ($columns_config as $column => $config) {
            if (!in_array(strtolower($column), $existing_columns)) {
                error_log("Amadex: Adding missing {$column} column to {$table_name}");
                
                // Determine position - use AFTER if reference column exists, otherwise add at end
                $position = '';
                if (isset($config['after']) && in_array(strtolower($config['after']), $existing_columns)) {
                    $position = " AFTER {$config['after']}";
                }
                
                $sql = "ALTER TABLE {$table_name} ADD COLUMN {$config['definition']}{$position}";
                $result = $wpdb->query($sql);
                
                if ($result === false) {
                    error_log("Amadex: Failed to add {$column} column - " . $wpdb->last_error);
                    // Try without AFTER clause if it failed
                    if ($position) {
                        $sql_fallback = "ALTER TABLE {$table_name} ADD COLUMN {$config['definition']}";
                        $result = $wpdb->query($sql_fallback);
                        if ($result !== false) {
                            error_log("Amadex: Successfully added {$column} column without position");
                        }
                    }
                } else {
                    error_log("Amadex: Successfully added {$column} column");
                    // Add index if needed
                    if (isset($config['index']) && $config['index']) {
                        $key_exists = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = '{$column}'");
                        if (empty($key_exists)) {
                            if (isset($config['unique']) && $config['unique']) {
                                $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE KEY {$column} ({$column})");
                            } else {
                                $wpdb->query("ALTER TABLE {$table_name} ADD KEY {$column} ({$column})");
                            }
                        }
                    }
                    // Update existing columns list
                    $existing_columns[] = strtolower($column);
                }
            }
        }
    }
    
    /**
     * Create a new lead
     */
    public function create_lead($data) {
        global $wpdb;
        
        // Extract flight route and airline from flight data
        $flight_route = '';
        $primary_airline = '';
        $total_amount = 0;
        $currency = 'USD';
        
        if (!empty($data['flight_data'])) {
            $flight = is_array($data['flight_data']) ? $data['flight_data'] : json_decode($data['flight_data'], true);
            
            if (!empty($flight['itineraries']) && is_array($flight['itineraries'])) {
                $first_itinerary = $flight['itineraries'][0];
                if (!empty($first_itinerary['segments']) && is_array($first_itinerary['segments'])) {
                    $first_segment = $first_itinerary['segments'][0];
                    $last_segment = end($first_itinerary['segments']);
                    
                    $dep_code = $first_segment['departure']['iataCode'] ?? $first_segment['departure']['iata_code'] ?? '';
                    $arr_code = $last_segment['arrival']['iataCode'] ?? $last_segment['arrival']['iata_code'] ?? '';
                    
                    if ($dep_code && $arr_code) {
                        $flight_route = $dep_code . ' → ' . $arr_code;
                    }
                    
                    // Get airline
                    if (!empty($first_segment['carrierCode'])) {
                        $primary_airline = $first_segment['carrierCode'];
                    } elseif (!empty($flight['validating_airline_codes']) && is_array($flight['validating_airline_codes'])) {
                        $primary_airline = $flight['validating_airline_codes'][0];
                    } elseif (!empty($flight['validatingAirlineCodes']) && is_array($flight['validatingAirlineCodes'])) {
                        $primary_airline = $flight['validatingAirlineCodes'][0];
                    }
                }
            }
            
            // Get price
            if (!empty($flight['price'])) {
                $total_amount = floatval($flight['price']['total'] ?? 0);
                $currency = $flight['price']['currency'] ?? 'USD';
            }
        }
        
        // Detect environment
        require_once(AMADEX_PATH . 'includes/class-amadex-environment-manager.php');
        $environment = Amadex_Environment_Manager::detect_environment($data);
        
        // Get fraud data if available
        $fraud_data = $data['fraud_data'] ?? null;
        $fraud_score = 0;
        $fraud_risk_level = 'LOW';
        $device_fingerprint = '';
        $ip_country = '';
        $ip_city = '';
        $ip_isp = '';
        $is_proxy = 0;
        $is_vpn = 0;
        $is_bot = 0;
        
        if ($fraud_data && is_array($fraud_data)) {
            $fraud_score = intval($fraud_data['fraud_score'] ?? 0);
            $fraud_risk_level = $fraud_data['risk_level'] ?? 'LOW';
            $device_fingerprint = $fraud_data['device_fingerprint'] ?? '';
            $ip_country = $fraud_data['geolocation']['country'] ?? '';
            $ip_city = $fraud_data['geolocation']['city'] ?? '';
            $ip_isp = $fraud_data['geolocation']['isp'] ?? '';
            $is_proxy = ($fraud_data['ip_risk']['isProxy'] ?? false) ? 1 : 0;
            $is_vpn = ($fraud_data['ip_risk']['isVPN'] ?? false) ? 1 : 0;
            $is_bot = ($fraud_data['device_risk']['isBot'] ?? false) ? 1 : 0;
        }
        
        // Extract UTM parameters from search params or booking data
        $utm_source = '';
        $utm_medium = '';
        $utm_campaign = '';
        $referral_source = '';
        $landing_page = '';
        
        if (!empty($data['search_params']) && is_array($data['search_params'])) {
            $utm_source = $data['search_params']['utm_source'] ?? '';
            $utm_medium = $data['search_params']['utm_medium'] ?? '';
            $utm_campaign = $data['search_params']['utm_campaign'] ?? '';
            $referral_source = $data['search_params']['referrer'] ?? '';
            $landing_page = $data['search_params']['landing_page'] ?? '';
        }
        
        $lead_data = array(
            'lead_type' => sanitize_text_field($data['lead_type'] ?? 'VERIFIED_LEAD'),
            'environment' => $environment,
            'status' => 'NEW',
            'contact_name' => sanitize_text_field($data['contact_name'] ?? ''),
            'contact_email' => sanitize_email($data['contact_email'] ?? ''),
            'contact_phone' => sanitize_text_field($data['contact_phone'] ?? ''),
            'flight_data' => wp_json_encode($data['flight_data'] ?? array()),
            'search_params' => wp_json_encode($data['search_params'] ?? array()),
            'source' => sanitize_text_field($data['source'] ?? 'ONLINE'),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            // Enterprise fields
            'flight_route' => sanitize_text_field($flight_route),
            'primary_airline' => sanitize_text_field($primary_airline),
            'total_amount' => $total_amount,
            'currency' => sanitize_text_field($currency),
            'priority' => sanitize_text_field($data['priority'] ?? 'NORMAL'),
            'tags' => sanitize_text_field($data['tags'] ?? ''),
            'utm_source' => sanitize_text_field($utm_source),
            'utm_medium' => sanitize_text_field($utm_medium),
            'utm_campaign' => sanitize_text_field($utm_campaign),
            'referral_source' => sanitize_text_field($referral_source),
            'landing_page' => sanitize_text_field($landing_page),
            'fraud_data' => $fraud_data ? wp_json_encode($fraud_data) : null,
            'fraud_score' => $fraud_score,
            'fraud_risk_level' => $fraud_risk_level,
            'device_fingerprint' => sanitize_text_field($device_fingerprint),
            'ip_country' => sanitize_text_field($ip_country),
            'ip_city' => sanitize_text_field($ip_city),
            'ip_isp' => sanitize_text_field($ip_isp),
            'is_proxy' => $is_proxy,
            'is_vpn' => $is_vpn,
            'is_bot' => $is_bot,
            'review_data' => isset($data['review_data']) ? $data['review_data'] : null,
        );
        
        // Build format array for wpdb->insert
        // Order must match $lead_data exactly:
        // lead_type, environment, status, contact_name, contact_email, contact_phone,
        // flight_data, search_params, source, ip_address, user_agent, notes,
        // flight_route, primary_airline, total_amount, currency, priority, tags,
        // utm_source, utm_medium, utm_campaign, referral_source, landing_page,
        // fraud_data, fraud_score, fraud_risk_level, device_fingerprint,
        // ip_country, ip_city, ip_isp, is_proxy, is_vpn, is_bot, review_data
        $formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s');
        $inserted = $wpdb->insert(
            $this->leads_table,
            $lead_data,
            $formats
        );
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update lead status
     */
    public function update_lead_status($lead_id, $status, $notes = '') {
        global $wpdb;
        
        $update_data = array('status' => $status);
        if (!empty($notes)) {
            // Get existing notes and append new one as JSON array
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT notes FROM {$this->leads_table} WHERE id = %d",
                $lead_id
            ));
            $notes_array = array();
            if (!empty($existing)) {
                $decoded = json_decode($existing, true);
                if (is_array($decoded)) {
                    $notes_array = $decoded;
                } else {
                    // Legacy plain text note — convert it
                    $notes_array[] = array(
                        'text' => $existing,
                        'at'   => current_time('mysql'),
                        'by'   => get_current_user_id(),
                    );
                }
            }
            // Prepend new note so latest is first
            array_unshift($notes_array, array(
                'text' => $notes,
                'at'   => current_time('mysql'),
                'by'   => get_current_user_id(),
            ));
            $update_data['notes'] = wp_json_encode($notes_array);
        }
        
        return $wpdb->update(
            $this->leads_table,
            $update_data,
            array('id' => $lead_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Assign lead to agent
     */
    public function assign_lead_to_agent($lead_id, $agent_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->leads_table,
            array(
                'assigned_agent_id' => $agent_id,
                'status' => 'ASSIGNED'
            ),
            array('id' => $lead_id),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get lead by ID
     */
    public function get_lead($lead_id) {
        global $wpdb;
        
        $lead = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->leads_table} WHERE id = %d",
                $lead_id
            ),
            ARRAY_A
        );
        
        if ($lead) {
            $lead['flight_data'] = json_decode($lead['flight_data'], true);
            $lead['search_params'] = json_decode($lead['search_params'], true);
        }
        
        return $lead;
    }
    
    /**
     * Get all leads with filters
     */
    public function get_leads($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        
        // Environment filter: only when explicitly set and non-empty ("All" = no filter)
        $env = isset($filters['environment']) ? $filters['environment'] : '';
        if ($env !== '' && $env !== 'all') {
            $where[] = $wpdb->prepare('(environment = %s OR environment IS NULL OR environment = "")', $env);
        } elseif (!isset($filters['environment']) && class_exists('Amadex_Environment_Manager')) {
            $current_env = Amadex_Environment_Manager::get_current_environment();
            $where[] = $wpdb->prepare('(environment = %s OR environment IS NULL OR environment = "")', $current_env);
        }
        
        if (!empty($filters['status'])) {
            $where[] = $wpdb->prepare('status = %s', $filters['status']);
        }
        
        if (!empty($filters['lead_type'])) {
            $where[] = $wpdb->prepare('lead_type = %s', $filters['lead_type']);
        }

        if (!empty($filters['source'])) {
            $where[] = $wpdb->prepare('source = %s', $filters['source']);
        }
        
        if (!empty($filters['exclude_with_booking'])) {
            $where[] = "id NOT IN (SELECT lead_id FROM {$this->bookings_table} WHERE lead_id IS NOT NULL AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'))";
        }
        
        if (!empty($filters['assigned_agent_id'])) {
            $where[] = $wpdb->prepare('assigned_agent_id = %d', $filters['assigned_agent_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare('DATE(created_at) >= %s', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare('DATE(created_at) <= %s', $filters['date_to']);
        }
        
        // Fraud score filters
        if (isset($filters['fraud_score_min'])) {
            $where[] = $wpdb->prepare('fraud_score >= %d', intval($filters['fraud_score_min']));
        }
        
        if (isset($filters['fraud_score_max'])) {
            $where[] = $wpdb->prepare('fraud_score <= %d', intval($filters['fraud_score_max']));
        }
        
        if (!empty($filters['fraud_risk_level'])) {
            $where[] = $wpdb->prepare('fraud_risk_level = %s', $filters['fraud_risk_level']);
        }
        
        // Search filter (full-text search)
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = $wpdb->prepare(
                "(contact_name LIKE %s OR contact_email LIKE %s OR contact_phone LIKE %s OR flight_route LIKE %s OR confirmation_number LIKE %s)",
                $search, $search, $search, $search, $search
            );
        }
        
        $order_by = 'created_at DESC';
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        // Join with bookings table: total_amount, currency, flight_data, and JSON display currency/amount
        // (so leads list can show INR when confirmation page used INR, without relying on full JSON decode)
        $query = "SELECT l.*, 
                         (SELECT b.total_amount FROM {$this->bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_total_amount,
                         (SELECT b.currency FROM {$this->bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_currency,
                         (SELECT b.flight_data FROM {$this->bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_flight_data,
                         (SELECT TRIM(BOTH '\"' FROM JSON_UNQUOTE(JSON_EXTRACT(b.flight_data, '$.currency_conversion.display_currency'))) FROM {$this->bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_display_currency,
                         (SELECT JSON_UNQUOTE(JSON_EXTRACT(b.flight_data, '$.currency_conversion.display_amount')) FROM {$this->bookings_table} b WHERE b.lead_id = l.id ORDER BY b.id DESC LIMIT 1) as booking_display_amount
                  FROM {$this->leads_table} l
                  WHERE " . implode(' AND ', $where) . " 
                  ORDER BY {$order_by} 
                  LIMIT {$limit} OFFSET {$offset}";
        
        $leads = $wpdb->get_results($query, ARRAY_A);
        
        // Decode JSON fields (robust so leads list can show confirmation-page currency)
        foreach ($leads as &$lead) {
            $lead['flight_data'] = is_array($lead['flight_data']) ? $lead['flight_data'] : (json_decode($lead['flight_data'], true) ?: array());
            $lead['search_params'] = is_array($lead['search_params']) ? $lead['search_params'] : (json_decode($lead['search_params'], true) ?: array());
            // booking_flight_data: from latest booking; must be array for currency_conversion
            if (isset($lead['booking_flight_data'])) {
                if (is_array($lead['booking_flight_data'])) {
                    // already decoded (e.g. some drivers)
                } elseif (is_string($lead['booking_flight_data']) && $lead['booking_flight_data'] !== '') {
                    $decoded = json_decode($lead['booking_flight_data'], true);
                    $lead['booking_flight_data'] = is_array($decoded) ? $decoded : array();
                } else {
                    $lead['booking_flight_data'] = array();
                }
            } else {
                $lead['booking_flight_data'] = array();
            }
        }
        unset($lead); // break reference
        
        return $leads;
    }
    
    /**
     * Get latest booking flight_data for given lead IDs (for currency_conversion in leads list).
     * Returns array keyed by lead_id => decoded flight_data array.
     *
     * @param array $lead_ids Lead IDs that have a booking
     * @return array [ lead_id => flight_data (array) ]
     */
    public function get_latest_booking_flight_data_for_lead_ids($lead_ids) {
        global $wpdb;
        if (empty($lead_ids) || !is_array($lead_ids)) {
            return array();
        }
        $lead_ids = array_map('intval', array_filter($lead_ids));
        if (empty($lead_ids)) {
            return array();
        }
        $placeholders = implode(',', array_fill(0, count($lead_ids), '%d'));
        // Get latest booking per lead: one row per lead_id with that lead's max(booking id)
        $query = $wpdb->prepare(
            "SELECT b.lead_id, b.flight_data FROM {$this->bookings_table} b
             INNER JOIN (
                 SELECT lead_id, MAX(id) as max_id FROM {$this->bookings_table}
                 WHERE lead_id IN ($placeholders) GROUP BY lead_id
             ) t ON b.lead_id = t.lead_id AND b.id = t.max_id
             WHERE b.lead_id IN ($placeholders)",
            array_merge($lead_ids, $lead_ids)
        );
        $rows = $wpdb->get_results($query, ARRAY_A);
        $out = array();
        foreach ($rows as $row) {
            $lid = isset($row['lead_id']) ? (int) $row['lead_id'] : 0;
            $fd = isset($row['flight_data']) ? $row['flight_data'] : '';
            if ($lid && $fd !== '') {
                $decoded = is_string($fd) ? json_decode($fd, true) : $fd;
                $out[$lid] = is_array($decoded) ? $decoded : array();
            }
        }
        return $out;
    }
    
    /**
     * Get leads count
     */
    public function get_leads_count($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        
        $env = isset($filters['environment']) ? $filters['environment'] : '';
        if ($env !== '' && $env !== 'all') {
            $where[] = $wpdb->prepare('(environment = %s OR environment IS NULL OR environment = "")', $env);
        }
        
        if (!empty($filters['status'])) {
            $where[] = $wpdb->prepare('status = %s', $filters['status']);
        }
        
        if (!empty($filters['lead_type'])) {
            $where[] = $wpdb->prepare('lead_type = %s', $filters['lead_type']);
        }

        if (!empty($filters['source'])) {
            $where[] = $wpdb->prepare('source = %s', $filters['source']);
        }
        
        if (!empty($filters['exclude_with_booking'])) {
            $where[] = "id NOT IN (SELECT lead_id FROM {$this->bookings_table} WHERE lead_id IS NOT NULL)";
        }
        
        $query = "SELECT COUNT(*) FROM {$this->leads_table} 
                  WHERE " . implode(' AND ', $where);
        
        return $wpdb->get_var($query);
    }

     /**
     * Delete a single lead
     * 
     * @param int $lead_id Lead ID to delete
     * @return bool Success status
     */
    public function delete_lead($lead_id) {
        global $wpdb;
        
        $lead_id = intval($lead_id);
        if ($lead_id <= 0) {
            return false;
        }
        
        $result = $wpdb->delete(
            $this->leads_table,
            array('id' => $lead_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Bulk delete leads
     * 
     * @param array $lead_ids Array of lead IDs to delete
     * @return int Number of leads deleted
     */
    public function bulk_delete_leads($lead_ids) {
        global $wpdb;
        
        if (empty($lead_ids) || !is_array($lead_ids)) {
            return 0;
        }
        
        // Sanitize all IDs to integers
        $lead_ids = array_map('intval', $lead_ids);
        $lead_ids = array_filter($lead_ids, function($id) {
            return $id > 0;
        });
        
        if (empty($lead_ids)) {
            return 0;
        }
        
        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($lead_ids), '%d'));
        
        $query = $wpdb->prepare(
            "DELETE FROM {$this->leads_table} WHERE id IN ($placeholders)",
            ...$lead_ids
        );
        
        $result = $wpdb->query($query);
        
        return $result !== false ? $result : 0;
    }
    
    
    /**
     * Create booking
     */
    public function create_booking($data) {
        global $wpdb;
        
        // Test database connection first
        $connection_test = $wpdb->get_var("SELECT 1");
        if ($connection_test !== '1') {
            error_log('Amadex: Database connection test failed. WordPress database may be unavailable.');
            return false;
        }
        
        // Ensure tables and schema are ready before inserting
        $this->ensure_tables_ready();
        
        // Aggressively ensure tables exist - create them multiple times if needed
        $max_attempts = 3;
        $attempt = 0;
        $table_exists = false;
        
        while (!$table_exists && $attempt < $max_attempts) {
            $table_exists = $this->table_exists($this->bookings_table);
            
            if (!$table_exists) {
                $attempt++;
                error_log("Amadex: Table does not exist, attempt {$attempt} of {$max_attempts}. Creating tables...");
                // Force create all tables
                $create_result = $this->create_tables();
                if (!$create_result['success']) {
                    error_log('Amadex: Table creation returned errors: ' . print_r($create_result['errors'], true));
                }
                $this->migrate_tables();
                
                // Small delay to ensure tables are created
                if (function_exists('usleep')) {
                    usleep(200000); // 0.2 seconds
                }
                
                // Clear query cache
                $wpdb->flush();
            }
        }
        
        if (!$table_exists) {
            // Last resort: try direct SQL creation
            error_log('Amadex: All table creation attempts failed. Trying direct SQL creation...');
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql_bookings = "CREATE TABLE IF NOT EXISTS {$this->bookings_table} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                lead_id bigint(20) DEFAULT NULL,
                booking_reference varchar(50) NOT NULL UNIQUE,
                pnr varchar(50) DEFAULT NULL,
                status enum('PENDING','CONFIRMED','TICKETED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
                flight_data longtext NOT NULL,
                total_amount decimal(10,2) NOT NULL,
                currency varchar(3) DEFAULT 'USD',
                passenger_count int(11) NOT NULL DEFAULT 1,
                booking_channel enum('ONLINE','PHONE','AGENT') NOT NULL DEFAULT 'ONLINE',
                confirmation_sent tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY lead_id (lead_id),
                KEY booking_reference (booking_reference),
                KEY status (status),
                KEY created_at (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_bookings);
            
            // Wait and clear cache
            if (function_exists('usleep')) {
                usleep(200000);
            }
            $wpdb->flush();
            
            // Check one more time using improved method
            $table_exists = $this->table_exists($this->bookings_table);
            
            if (!$table_exists) {
                error_log('Amadex: CRITICAL - Direct SQL table creation also failed. Table still does not exist.');
            }
        }
        
        // If still doesn't exist, there's a serious database permission issue
        // But we'll still try to insert - WordPress might handle it
        if (!$table_exists) {
            // Log but don't fail - try to insert anyway
            // WordPress dbDelta might have created it but query is cached
        }
        
        $booking_reference = $this->generate_booking_reference();
        
        // Encode flight_data safely
        $flight_data_json = '';
        if (!empty($data['flight_data'])) {
            $flight_data_json = wp_json_encode($data['flight_data']);
            if ($flight_data_json === false) {
                error_log('Amadex: Failed to encode flight_data - ' . json_last_error_msg());
                // Try to encode with error suppression
                $flight_data_json = @json_encode($data['flight_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($flight_data_json === false) {
                    // Last resort: encode only essential data
                    $flight_data_json = wp_json_encode(array(
                        'id' => $data['flight_data']['id'] ?? '',
                        'price' => $data['flight_data']['price'] ?? array(),
                        'itineraries' => array() // Truncate to avoid encoding issues
                    ));
                }
            }
        }
        
        // Validate required fields
        if (empty($flight_data_json)) {
            error_log('Amadex: Cannot create booking - flight_data is empty or could not be encoded');
            return false;
        }
        
        $total_amount = floatval($data['total_amount'] ?? 0);
        if ($total_amount <= 0) {
            error_log('Amadex: Cannot create booking - total_amount is invalid: ' . $total_amount);
            return false;
        }
        
        $booking_data = array(
            'lead_id' => isset($data['lead_id']) ? intval($data['lead_id']) : null,
            'booking_reference' => $booking_reference,
            'pnr' => sanitize_text_field($data['pnr'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'PENDING'),
            'flight_data' => $flight_data_json,
            'total_amount' => $total_amount,
            'currency' => sanitize_text_field($data['currency'] ?? 'USD'),
            'passenger_count' => intval($data['passenger_count'] ?? 1),
            'booking_channel' => sanitize_text_field($data['booking_channel'] ?? 'ONLINE')
        );
        
        $fraud = $data['fraud_data'] ?? null;
        $billing = $data['billing'] ?? array();
        if ($fraud && is_array($fraud)) {
            $booking_data['fraud_data'] = wp_json_encode($fraud);
            $booking_data['fraud_score'] = intval($fraud['fraud_score'] ?? 0);
            $booking_data['fraud_risk_level'] = sanitize_text_field($fraud['risk_level'] ?? 'LOW');
            $booking_data['device_fingerprint'] = sanitize_text_field($fraud['device_fingerprint'] ?? '');
            $booking_data['ip_country'] = sanitize_text_field($fraud['geolocation']['country'] ?? '');
            $booking_data['ip_city'] = sanitize_text_field($fraud['geolocation']['city'] ?? '');
            $booking_data['billing_country'] = sanitize_text_field($billing['country'] ?? '');
            $booking_data['billing_city'] = sanitize_text_field($billing['city'] ?? '');
            $booking_data['billing_match_ip'] = isset($fraud['payment_risk']['billingMatchesIp']) && $fraud['payment_risk']['billingMatchesIp'] ? 1 : 0;
        }
        if (class_exists('Amadex_Environment_Manager')) {
            $booking_data['environment'] = Amadex_Environment_Manager::detect_environment($data);
        }
        
        // Log the data being inserted (without sensitive info)
        error_log('Amadex: Creating booking with data - Lead ID: ' . $booking_data['lead_id'] . ', Reference: ' . $booking_reference . ', Amount: ' . $total_amount);
        error_log('Amadex: Table name: ' . $this->bookings_table);
        error_log('Amadex: Table exists check: ' . ($table_exists ? 'YES' : 'NO'));
        
        // Verify table exists one more time before insert
        $final_table_check = $this->table_exists($this->bookings_table);
        error_log('Amadex: Final table check result: ' . ($final_table_check ? 'EXISTS' : 'DOES NOT EXIST'));
        error_log('Amadex: Expected table name: ' . $this->bookings_table);
        
        if (!$final_table_check) {
            error_log('Amadex: CRITICAL - Table does not exist before insert! Attempting emergency creation...');
            $create_result = $this->create_tables();
            if (!$create_result['success']) {
                error_log('Amadex: Emergency table creation failed: ' . print_r($create_result['errors'], true));
            }
            // Force WordPress to recognize the table
            $wpdb->flush();
            if (function_exists('usleep')) {
                usleep(500000); // 0.5 seconds
            }
            
            // Re-check after creation
            $final_table_check = $this->table_exists($this->bookings_table);
            if (!$final_table_check) {
                error_log('Amadex: CRITICAL - Table still does not exist after emergency creation!');
                return false;
            }
        }
        
        // Build format array dynamically based on available fields
        $formats = array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d');
        
        $inserted = $wpdb->insert(
            $this->bookings_table,
            $booking_data,
            $formats
        );
        
        // If insert failed due to schema issues, attempt one retry after rebuilding tables
        if ($inserted === false && $this->should_retry_after_schema_error($wpdb->last_error)) {
            error_log('Amadex: Booking insert failed due to schema error. Running migrations and retrying insert.');
            error_log('Amadex: Schema error details: ' . $wpdb->last_error);
            $this->create_tables();
            $this->migrate_tables();
            $wpdb->flush();
            sleep(1);
            $inserted = $wpdb->insert(
                $this->bookings_table,
                $booking_data,
                array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s')
            );
        }
        
        if ($inserted === false) {
            $error = $wpdb->last_error;
            $query = $wpdb->last_query;
            
            error_log('Amadex: ========== DATABASE INSERT FAILED ==========');
            error_log('Amadex: Error: ' . ($error ?: 'No error message'));
            error_log('Amadex: Query: ' . ($query ?: 'No query logged'));
            error_log('Amadex: Table: ' . $this->bookings_table);
            error_log('Amadex: Booking Reference: ' . $booking_reference);
            error_log('Amadex: Lead ID: ' . ($booking_data['lead_id'] ?? 'NULL'));
            error_log('Amadex: Total Amount: ' . ($booking_data['total_amount'] ?? 'N/A'));
            error_log('Amadex: Database Prefix: ' . $wpdb->prefix);
            error_log('Amadex: WordPress Version: ' . get_bloginfo('version'));
            
            // Check if table actually exists using improved method
            $table_check_after = $this->table_exists($this->bookings_table);
            error_log('Amadex: Table exists after failed insert: ' . ($table_check_after ? 'YES' : 'NO'));
            
            // Try to get table structure
            if ($table_check_after) {
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->bookings_table}");
                if ($columns) {
                    error_log('Amadex: Table columns: ' . print_r(array_column($columns, 'Field'), true));
                }
            }
            
            error_log('Amadex: ============================================');
            
            // Check for duplicate key error
            if ($error && (strpos(strtolower($error), 'duplicate') !== false || strpos(strtolower($error), 'unique') !== false)) {
                error_log('Amadex: Duplicate booking reference detected. Generating new reference and retrying...');
                // Generate new reference and retry once
                $booking_reference = $this->generate_booking_reference();
                $booking_data['booking_reference'] = $booking_reference;
                
                $inserted = $wpdb->insert(
                    $this->bookings_table,
                    $booking_data,
                    array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s')
                );
                
                if ($inserted) {
                    error_log('Amadex: Booking created successfully with new reference: ' . $booking_reference);
                    return array(
                        'booking_id' => $wpdb->insert_id,
                        'booking_reference' => $booking_reference
                    );
                } else {
                    error_log('Amadex: Retry with new reference also failed: ' . ($wpdb->last_error ?: 'Unknown error'));
                }
            }
            
            return false;
        }
        
        if ($inserted) {
            $booking_id = $wpdb->insert_id;
            error_log('Amadex: Booking created successfully - ID: ' . $booking_id . ', Reference: ' . $booking_reference);
            
            // Verify the booking was actually created by querying it back
            $verify_booking = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, booking_reference FROM {$this->bookings_table} WHERE id = %d",
                    $booking_id
                ),
                ARRAY_A
            );
            
            if ($verify_booking) {
                return array(
                    'booking_id' => $booking_id,
                    'booking_reference' => $booking_reference
                );
            } else {
                error_log('Amadex: WARNING - Booking insert reported success but booking not found in database!');
                // Still return the data - it might be a timing issue
                return array(
                    'booking_id' => $booking_id,
                    'booking_reference' => $booking_reference
                );
            }
        }
        
        // Final check: Maybe the booking was created but insert returned false due to a warning
        // Check if a booking with this reference or lead_id exists
        if (!empty($booking_data['lead_id'])) {
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, booking_reference FROM {$this->bookings_table} WHERE lead_id = %d ORDER BY id DESC LIMIT 1",
                    $booking_data['lead_id']
                ),
                ARRAY_A
            );
            
            if ($existing) {
                error_log('Amadex: Found existing booking for lead ID ' . $booking_data['lead_id'] . ' - using it');
                return array(
                    'booking_id' => $existing['id'],
                    'booking_reference' => $existing['booking_reference']
                );
            }
        }
        
        // Return error details for debugging
        global $wpdb;
        $error_details = array(
            'success' => false,
            'error' => $wpdb->last_error ?: 'Unknown database error',
            'query' => $wpdb->last_query ?: 'No query logged',
            'table' => $this->bookings_table,
            'table_exists' => $wpdb->get_var("SHOW TABLES LIKE '{$this->bookings_table}'") === $this->bookings_table,
            'database_prefix' => $wpdb->prefix
        );
        
        error_log('Amadex: Returning error details: ' . print_r($error_details, true));
        
        return false; // Still return false for backward compatibility, but error is logged
    }
    
    /**
     * Add passenger to booking
     */
    public function add_passenger($booking_id, $passenger_data) {
        global $wpdb;
        
        $data = array(
            'booking_id' => $booking_id,
            'passenger_type' => sanitize_text_field($passenger_data['passenger_type'] ?? 'ADULT'),
            'title' => sanitize_text_field($passenger_data['title'] ?? ''),
            'first_name' => sanitize_text_field($passenger_data['first_name'] ?? ''),
            'middle_name' => sanitize_text_field($passenger_data['middle_name'] ?? ''),
            'last_name' => sanitize_text_field($passenger_data['last_name'] ?? ''),
            'gender' => sanitize_text_field($passenger_data['gender'] ?? ''),
            'date_of_birth' => sanitize_text_field($passenger_data['date_of_birth'] ?? ''),
            'passport_number' => sanitize_text_field($passenger_data['passport_number'] ?? ''),
            'passport_expiry' => sanitize_text_field($passenger_data['passport_expiry'] ?? ''),
            'nationality' => sanitize_text_field($passenger_data['nationality'] ?? '')
        );
        
        return $wpdb->insert(
            $this->passengers_table,
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create payment record
     */
    public function create_payment($booking_id, $payment_data) {
        global $wpdb;
        
        $data = array(
            'booking_id' => $booking_id,
            'transaction_id' => sanitize_text_field($payment_data['transaction_id'] ?? ''),
            'payment_status' => sanitize_text_field($payment_data['payment_status'] ?? 'AUTH_ONLY'),
            'payment_method' => sanitize_text_field($payment_data['payment_method'] ?? 'CREDIT_CARD'),
            'amount' => floatval($payment_data['amount'] ?? 0),
            'currency' => sanitize_text_field($payment_data['currency'] ?? 'USD'),
            'card_last4' => sanitize_text_field($payment_data['card_last4'] ?? ''),
            'card_type' => sanitize_text_field($payment_data['card_type'] ?? ''),
            'avs_result' => sanitize_text_field($payment_data['avs_result'] ?? ''),
            'cvv_result' => sanitize_text_field($payment_data['cvv_result'] ?? ''),
            'gateway_response' => wp_json_encode($payment_data['gateway_response'] ?? array()),
            'auth_code' => sanitize_text_field($payment_data['auth_code'] ?? '')
        );
        
        return $wpdb->insert(
            $this->payments_table,
            $data,
            array('%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Update payment status
     */
    public function update_payment_status($transaction_id, $status) {
        global $wpdb;
        
        $update_data = array('payment_status' => $status);
        
        if ($status === 'CAPTURED') {
            $update_data['captured_at'] = current_time('mysql');
        } elseif ($status === 'REFUNDED') {
            $update_data['refunded_at'] = current_time('mysql');
        }
        
        return $wpdb->update(
            $this->payments_table,
            $update_data,
            array('transaction_id' => $transaction_id),
            array('%s', '%s'),
            array('%s')
        );
    }
    
    /**
     * Get booking by reference
     */
    public function get_booking_by_reference($booking_reference) {
        global $wpdb;
        
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE booking_reference = %s",
                $booking_reference
            ),
            ARRAY_A
        );
        
        if ($booking) {
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
            
            // Get passengers
            $booking['passengers'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->passengers_table} WHERE booking_id = %d",
                    $booking['id']
                ),
                ARRAY_A
            );
            
            // Get payment
            $booking['payment'] = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->payments_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1",
                    $booking['id']
                ),
                ARRAY_A
            );
            
            if ($booking['payment'] && !empty($booking['payment']['gateway_response'])) {
                $booking['payment']['gateway_response'] = json_decode($booking['payment']['gateway_response'], true);
            }
            
            if (!empty($booking['lead_id'])) {
                $booking['lead'] = $this->get_lead($booking['lead_id']);
            }
        }
        
        return $booking;
    }
    
    /**
     * Generate unique booking reference
     */
    private function generate_booking_reference() {
        global $wpdb;
        
        $max_attempts = 10;
        $attempt = 0;
        
        do {
            $reference = 'AMD' . strtoupper(substr(uniqid(), -8));
            
            // Check if reference already exists
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->bookings_table} WHERE booking_reference = %s",
                    $reference
                )
            );
            
            if ($exists == 0) {
                return $reference;
            }
            
            $attempt++;
            error_log("Amadex: Booking reference collision detected: {$reference}. Attempt {$attempt}/{$max_attempts}");
            
        } while ($attempt < $max_attempts);
        
        // Fallback: use timestamp-based reference if all attempts failed
        error_log('Amadex: All booking reference generation attempts failed. Using timestamp-based reference.');
        return 'AMD' . strtoupper(substr(md5(time() . rand()), 0, 8));
    }

    /**
     * Determine if a schema fix/retry should be attempted
     */
    private function should_retry_after_schema_error($error_message) {
        if (empty($error_message)) {
            return false;
        }
        
        $error_message = strtolower($error_message);
        $patterns = array(
            'doesn\'t exist',
            'does not exist',
            'unknown column',
            'no such table',
            'has no column',
            'missing column'
        );
        
        foreach ($patterns as $pattern) {
            if (strpos($error_message, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get all bookings with filters
     */
    public function get_bookings($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        
        // Environment filter (default to current environment if not specified)
        if (isset($filters['environment'])) {
            $where[] = $wpdb->prepare('environment = %s', $filters['environment']);
        } elseif (class_exists('Amadex_Environment_Manager')) {
            // Auto-filter by current environment
            $current_env = Amadex_Environment_Manager::get_current_environment();
            $where[] = $wpdb->prepare('environment = %s', $current_env);
        }
        
        // Exclude soft-deleted records (if deleted_at column exists)
        $where[] = "(deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
        
        if (!empty($filters['status'])) {
            $where[] = $wpdb->prepare('status = %s', $filters['status']);
        }
        
        if (!empty($filters['booking_channel'])) {
            $where[] = $wpdb->prepare('booking_channel = %s', $filters['booking_channel']);
        }
        
        // Fraud score filters
        if (isset($filters['fraud_score_min'])) {
            $where[] = $wpdb->prepare('fraud_score >= %d', intval($filters['fraud_score_min']));
        }
        
        if (isset($filters['fraud_score_max'])) {
            $where[] = $wpdb->prepare('fraud_score <= %d', intval($filters['fraud_score_max']));
        }
        
        if (!empty($filters['fraud_risk_level'])) {
            $where[] = $wpdb->prepare('fraud_risk_level = %s', $filters['fraud_risk_level']);
        }
        
        // Search filter (full-text search)
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = $wpdb->prepare(
                "(booking_reference LIKE %s OR pnr LIKE %s OR confirmation_number LIKE %s)",
                $search, $search, $search
            );
        }
        
        // Only show bookings with a generated PNR (Booking Management: hide declined/incomplete PayPal, Crypto, MoonPay attempts)
        if (!empty($filters['has_pnr'])) {
            $where[] = "(pnr IS NOT NULL AND pnr <> '')";
        }
        
        // Date filters
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare('DATE(created_at) >= %s', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare('DATE(created_at) <= %s', $filters['date_to']);
        }
        
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        $query = "SELECT * FROM {$this->bookings_table} 
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY created_at DESC
                  LIMIT $limit OFFSET $offset";
        
        $bookings = $wpdb->get_results($query, ARRAY_A);
        
        // Decode flight data for each booking
        foreach ($bookings as &$booking) {
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
        }
        
        return $bookings;
    }
    
    /**
     * Get bookings count
     */
    public function get_bookings_count($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        
        // Environment filter
        if (isset($filters['environment'])) {
            $where[] = $wpdb->prepare('environment = %s', $filters['environment']);
        } elseif (class_exists('Amadex_Environment_Manager')) {
            $current_env = Amadex_Environment_Manager::get_current_environment();
            $where[] = $wpdb->prepare('environment = %s', $current_env);
        }
        
        // Exclude soft-deleted
        $where[] = "(deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
        
        if (!empty($filters['status'])) {
            $where[] = $wpdb->prepare('status = %s', $filters['status']);
        }
        
        if (!empty($filters['booking_channel'])) {
            $where[] = $wpdb->prepare('booking_channel = %s', $filters['booking_channel']);
        }
        
        // Only count bookings with PNR when used for Booking Management
        if (!empty($filters['has_pnr'])) {
            $where[] = "(pnr IS NOT NULL AND pnr <> '')";
        }
        
        $query = "SELECT COUNT(*) FROM {$this->bookings_table} 
                  WHERE " . implode(' AND ', $where);
        
        return $wpdb->get_var($query);
    }
    
    /**
     * Soft delete a booking (archive)
     * 
     * @param int $booking_id Booking ID
     * @param string $reason Deletion reason
     * @return bool Success
     */
    public function soft_delete_booking($booking_id, $reason = '') {
        global $wpdb;
        
        $booking_id = intval($booking_id);
        if ($booking_id <= 0) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        $result = $wpdb->update(
            $this->bookings_table,
            array(
                'deleted_at' => current_time('mysql'),
                'deletion_reason' => sanitize_textarea_field($reason),
                'deleted_by' => $user_id
            ),
            array('id' => $booking_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Hard delete a booking (permanent)
     * 
     * @param int $booking_id Booking ID
     * @return bool Success
     */
    public function hard_delete_booking($booking_id) {
        global $wpdb;
        
        $booking_id = intval($booking_id);
        if ($booking_id <= 0) {
            return false;
        }
        
        // Delete related records first
        $wpdb->delete(
            $this->passengers_table,
            array('booking_id' => $booking_id),
            array('%d')
        );
        
        $wpdb->delete(
            $this->payments_table,
            array('booking_id' => $booking_id),
            array('%d')
        );
        
        // Delete booking
        $result = $wpdb->delete(
            $this->bookings_table,
            array('id' => $booking_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Bulk delete bookings
     * 
     * @param array $booking_ids Array of booking IDs
     * @param bool $hard_delete If true, permanently delete. If false, soft delete.
     * @param string $reason Deletion reason (for soft delete)
     * @return int Number of bookings deleted
     */
    public function bulk_delete_bookings($booking_ids, $hard_delete = false, $reason = '') {
        if (empty($booking_ids) || !is_array($booking_ids)) {
            return 0;
        }
        
        $deleted = 0;
        
        foreach ($booking_ids as $booking_id) {
            if ($hard_delete) {
                if ($this->hard_delete_booking($booking_id)) {
                    $deleted++;
                }
            } else {
                if ($this->soft_delete_booking($booking_id, $reason)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get booking by ID
     */
    public function get_booking($booking_id) {
        global $wpdb;
        
        $booking = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE id = %d",
                $booking_id
            ),
            ARRAY_A
        );
        
        if ($booking) {
            $booking['flight_data'] = json_decode($booking['flight_data'], true);
            
            // Get passengers
            $booking['passengers'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->passengers_table} WHERE booking_id = %d",
                    $booking['id']
                ),
                ARRAY_A
            );
            
            // Get payment
            $booking['payment'] = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->payments_table} WHERE booking_id = %d ORDER BY created_at DESC LIMIT 1",
                    $booking['id']
                ),
                ARRAY_A
            );
            
            if ($booking['payment'] && !empty($booking['payment']['gateway_response'])) {
                $booking['payment']['gateway_response'] = json_decode($booking['payment']['gateway_response'], true);
            }
            
            // Get associated lead if exists
            if ($booking['lead_id']) {
                $booking['lead'] = $this->get_lead($booking['lead_id']);
            }
        }
        
        return $booking;
    }
    
    /**
     * Update booking status
     */
    public function update_booking_status($booking_id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->bookings_table,
            array('status' => $status),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Update booking PNR
     */
    public function update_booking_pnr($booking_id, $pnr) {
        global $wpdb;
        
        return $wpdb->update(
            $this->bookings_table,
            array('pnr' => $pnr),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get booking statistics
     *
     * @param array $filters Optional. Pass array('has_pnr' => true) to count only bookings with a PNR (for Booking Management).
     */
    public function get_booking_stats($filters = array()) {
        global $wpdb;
        
        $stats = array();
        $pnr_where = '';
        if (!empty($filters['has_pnr'])) {
            $pnr_where = " AND (pnr IS NOT NULL AND pnr <> '')";
        }
        
        // Total bookings
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->bookings_table} WHERE 1=1{$pnr_where}");
        
        // Pending bookings
        $stats['pending'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->bookings_table} WHERE status = 'PENDING'{$pnr_where}"
        );
        
        // Confirmed bookings
        $stats['confirmed'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->bookings_table} WHERE status = 'CONFIRMED'{$pnr_where}"
        );
        
        // Ticketed bookings
        $stats['ticketed'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->bookings_table} WHERE status = 'TICKETED'{$pnr_where}"
        );
        
        // Total revenue (only confirmed and ticketed, only with PNR when filtering)
        $stats['revenue'] = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM {$this->bookings_table} 
             WHERE status IN ('CONFIRMED', 'TICKETED'){$pnr_where}"
        ) ?: 0;
        
        return $stats;
    }
    
    /**
     * Drop all tables (for uninstallation)
     */
    public function drop_tables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$this->payments_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->passengers_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->bookings_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->leads_table}");
    }
    
    /**
     * Mark confirmation email sent
     */
    public function mark_confirmation_sent($booking_id, $sent = true) {
        global $wpdb;
        
        return $wpdb->update(
            $this->bookings_table,
            array('confirmation_sent' => $sent ? 1 : 0),
            array('id' => $booking_id),
            array('%d'),
            array('%d')
        );
    }
}









