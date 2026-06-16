<?php
/**
 * Amadex Database SQL Generator
 * 
 * This script generates the database schema SQL with the correct WordPress table prefix.
 * 
 * Usage:
 * 1. Access via browser: https://yoursite.com/wp-content/plugins/amadex1/install/generate-sql.php
 * 2. Download the generated SQL file
 * 3. Import into phpMyAdmin
 * 
 * Security: This file should be removed after use or protected with .htaccess
 */

// Load WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('WordPress not found. Please ensure this file is in the correct location.');
}

// Security check - only allow admin users or remove this file after use
if (!current_user_can('manage_options')) {
    die('Access denied. Only administrators can access this file.');
}

global $wpdb;
$prefix = $wpdb->prefix;

// Get charset and collation
$charset_collate = $wpdb->get_charset_collate();

// Generate SQL content
$sql_content = <<<SQL
-- =====================================================
-- Amadex Plugin Database Schema
-- Generated with table prefix: {$prefix}
-- =====================================================
-- This file contains all required database tables for the Amadex plugin.
-- 
-- Instructions:
-- 1. Go to phpMyAdmin
-- 2. Select your WordPress database
-- 3. Click on "SQL" tab
-- 4. Copy and paste this entire file OR import this file
-- 5. Click "Go" to execute
-- 6. Verify tables are created
--
-- Generated on: {$wpdb->dbname}
-- Table prefix: {$prefix}
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- =====================================================
-- Table: {$prefix}amadex_leads
-- Stores flight search leads and inquiries
-- =====================================================
CREATE TABLE IF NOT EXISTS `{$prefix}amadex_leads` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lead_type` enum('VERIFIED_LEAD','PHONE_LEAD') NOT NULL DEFAULT 'VERIFIED_LEAD',
  `status` enum('NEW','ASSIGNED','IN_PROGRESS','CONTACTED','CONVERTED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'NEW',
  `assigned_agent_id` bigint(20) DEFAULT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `contact_phone` varchar(50) NOT NULL,
  `flight_data` longtext NOT NULL,
  `search_params` longtext DEFAULT NULL,
  `source` enum('ONLINE','PHONE','POPUP') NOT NULL DEFAULT 'ONLINE',
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `lead_type` (`lead_type`),
  KEY `assigned_agent_id` (`assigned_agent_id`),
  KEY `created_at` (`created_at`)
) {$charset_collate};

-- =====================================================
-- Table: {$prefix}amadex_bookings
-- Stores confirmed flight bookings
-- =====================================================
CREATE TABLE IF NOT EXISTS `{$prefix}amadex_bookings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) DEFAULT NULL,
  `booking_reference` varchar(50) NOT NULL,
  `pnr` varchar(50) DEFAULT NULL,
  `status` enum('PENDING','CONFIRMED','TICKETED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
  `flight_data` longtext NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `passenger_count` int(11) NOT NULL DEFAULT 1,
  `booking_channel` enum('ONLINE','PHONE','AGENT') NOT NULL DEFAULT 'ONLINE',
  `confirmation_sent` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `lead_id` (`lead_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) {$charset_collate};

-- =====================================================
-- Table: {$prefix}amadex_passengers
-- Stores passenger information for each booking
-- =====================================================
CREATE TABLE IF NOT EXISTS `{$prefix}amadex_passengers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) NOT NULL,
  `passenger_type` enum('ADULT','CHILD','INFANT') NOT NULL DEFAULT 'ADULT',
  `title` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('M','F','OTHER') NOT NULL,
  `date_of_birth` date NOT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `nationality` varchar(2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`)
) {$charset_collate};

-- =====================================================
-- Table: {$prefix}amadex_payments
-- Stores payment transaction information
-- =====================================================
CREATE TABLE IF NOT EXISTS `{$prefix}amadex_payments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_status` enum('AUTH_ONLY','AUTHORIZED','CAPTURED','FAILED','REFUNDED') NOT NULL DEFAULT 'AUTH_ONLY',
  `payment_method` enum('CREDIT_CARD','DEBIT_CARD','PAYPAL','BANK_TRANSFER') NOT NULL DEFAULT 'CREDIT_CARD',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `card_last4` varchar(4) DEFAULT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `avs_result` varchar(10) DEFAULT NULL,
  `cvv_result` varchar(10) DEFAULT NULL,
  `gateway_response` longtext DEFAULT NULL,
  `auth_code` varchar(50) DEFAULT NULL,
  `captured_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `payment_status` (`payment_status`)
) {$charset_collate};

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Verification Query
-- Run this to check if all tables were created successfully:
-- =====================================================
-- SELECT 
--   TABLE_NAME, 
--   TABLE_ROWS, 
--   CREATE_TIME 
-- FROM 
--   information_schema.TABLES 
-- WHERE 
--   TABLE_SCHEMA = DATABASE() 
--   AND TABLE_NAME LIKE '{$prefix}amadex_%'
-- ORDER BY TABLE_NAME;
-- =====================================================
SQL;

// Output headers for download
if (isset($_GET['download'])) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="amadex-database-schema-' . $prefix . date('Y-m-d') . '.sql"');
    header('Content-Length: ' . strlen($sql_content));
    echo $sql_content;
    exit;
}

// Display in browser with download option
?>
<!DOCTYPE html>
<html>
<head>
    <title>Amadex Database SQL Generator</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #23282d;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 10px 10px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #005177;
        }
        .button-secondary {
            background: #666;
        }
        .button-secondary:hover {
            background: #444;
        }
        textarea {
            width: 100%;
            height: 400px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
        }
        .instructions {
            background: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .instructions ol {
            margin-left: 20px;
        }
        .instructions li {
            margin: 10px 0;
        }
        code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Amadex Database SQL Generator</h1>
        
        <div class="success-box">
            <strong>✅ SQL Generated Successfully!</strong><br>
            Database: <code><?php echo esc_html(DB_NAME); ?></code><br>
            Table Prefix: <code><?php echo esc_html($prefix); ?></code><br>
            Tables will be created with prefix: <code><?php echo esc_html($prefix); ?>amadex_*</code>
        </div>
        
        <div class="info-box">
            <strong>⚠️ Security Note:</strong> Delete this file (<code>generate-sql.php</code>) after you're done generating the SQL file to prevent unauthorized access.
        </div>
        
        <div class="instructions">
            <h3>📋 Instructions:</h3>
            <ol>
                <li><strong>Download the SQL file:</strong> Click the "Download SQL File" button below</li>
                <li><strong>Go to phpMyAdmin:</strong> Log into your hosting control panel and open phpMyAdmin</li>
                <li><strong>Select your database:</strong> Click on your WordPress database (<code><?php echo esc_html(DB_NAME); ?></code>)</li>
                <li><strong>Import SQL:</strong>
                    <ul>
                        <li>Click on the "SQL" tab</li>
                        <li>Either upload the downloaded file OR copy-paste the SQL below</li>
                        <li>Click "Go" to execute</li>
                    </ul>
                </li>
                <li><strong>Verify:</strong> Check that these tables were created:
                    <ul>
                        <li><code><?php echo esc_html($prefix); ?>amadex_leads</code></li>
                        <li><code><?php echo esc_html($prefix); ?>amadex_bookings</code></li>
                        <li><code><?php echo esc_html($prefix); ?>amadex_passengers</code></li>
                        <li><code><?php echo esc_html($prefix); ?>amadex_payments</code></li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <div style="margin: 20px 0;">
            <a href="?download=1" class="button">📥 Download SQL File</a>
            <a href="<?php echo admin_url(); ?>" class="button button-secondary">← Back to WordPress Admin</a>
        </div>
        
        <h3>📄 SQL Content (Copy-Paste Option):</h3>
        <textarea readonly><?php echo esc_textarea($sql_content); ?></textarea>
        
        <p><small>After importing, refresh your WordPress admin page. The database notice should disappear and bookings will work.</small></p>
    </div>
</body>
</html>

