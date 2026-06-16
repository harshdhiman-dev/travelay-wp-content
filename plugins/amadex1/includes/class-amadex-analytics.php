<?php
/**
 * Analytics Engine for Amadex Plugin
 * Provides metrics, conversion funnels, revenue analytics, and reporting
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Class
 */
class Amadex_Analytics {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Amadex_Database();
    }
    
    /**
     * Get conversion funnel data
     *
     * @param array $filters Filters (date range, environment, etc.)
     * @return array Funnel data
     */
    public function get_conversion_funnel($filters = array()) {
        global $wpdb;
        $leads_table = $wpdb->prefix . 'amadex_leads';
        
        $where = array('1=1');
        
        // Environment filter
        if (isset($filters['environment'])) {
            $where[] = $wpdb->prepare('environment = %s', $filters['environment']);
        }
        
        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare('DATE(created_at) >= %s', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare('DATE(created_at) <= %s', $filters['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get counts by status
        $funnel = array(
            'NEW' => 0,
            'ASSIGNED' => 0,
            'IN_PROGRESS' => 0,
            'CONTACTED' => 0,
            'CONVERTED' => 0,
            'CANCELLED' => 0
        );
        
        foreach (array_keys($funnel) as $status) {
            $count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$leads_table} 
                 WHERE {$where_clause} AND status = '{$status}'"
            );
            $funnel[$status] = intval($count);
        }
        
        // Calculate conversion rates
        $total = array_sum($funnel);
        $converted = $funnel['CONVERTED'];
        
        $funnel['total'] = $total;
        $funnel['conversion_rate'] = $total > 0 ? round(($converted / $total) * 100, 2) : 0;
        $funnel['conversion_rate_by_stage'] = array();
        
        $prev_count = $total;
        foreach ($funnel as $stage => $count) {
            if (in_array($stage, array('total', 'conversion_rate', 'conversion_rate_by_stage'))) {
                continue;
            }
            if ($prev_count > 0) {
                $funnel['conversion_rate_by_stage'][$stage] = round(($count / $prev_count) * 100, 2);
            }
            $prev_count = $count;
        }
        
        return $funnel;
    }
    
    /**
     * Get revenue analytics
     *
     * @param array $filters Filters
     * @return array Revenue data
     */
    public function get_revenue_analytics($filters = array()) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'amadex_bookings';
        
        $where = array('1=1', "(deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')");
        
        if (isset($filters['environment'])) {
            $where[] = $wpdb->prepare('environment = %s', $filters['environment']);
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare('DATE(created_at) >= %s', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare('DATE(created_at) <= %s', $filters['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Total revenue
        $total_revenue = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM {$bookings_table} 
             WHERE {$where_clause} AND status IN ('CONFIRMED', 'TICKETED')"
        );
        
        // Revenue by source
        $revenue_by_source = $wpdb->get_results(
            "SELECT 
                l.source,
                SUM(b.total_amount) as revenue,
                COUNT(b.id) as bookings
             FROM {$bookings_table} b
             LEFT JOIN {$wpdb->prefix}amadex_leads l ON b.lead_id = l.id
             WHERE {$where_clause} AND b.status IN ('CONFIRMED', 'TICKETED')
             GROUP BY l.source",
            ARRAY_A
        );
        
        // Revenue by agent
        $revenue_by_agent = $wpdb->get_results(
            "SELECT 
                l.assigned_agent_id,
                SUM(b.total_amount) as revenue,
                COUNT(b.id) as bookings
             FROM {$bookings_table} b
             LEFT JOIN {$wpdb->prefix}amadex_leads l ON b.lead_id = l.id
             WHERE {$where_clause} AND b.status IN ('CONFIRMED', 'TICKETED')
             AND l.assigned_agent_id IS NOT NULL
             GROUP BY l.assigned_agent_id",
            ARRAY_A
        );
        
        // Revenue trends (by day)
        $revenue_trends = $wpdb->get_results(
            "SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as revenue,
                COUNT(id) as bookings
             FROM {$bookings_table}
             WHERE {$where_clause} AND status IN ('CONFIRMED', 'TICKETED')
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );
        
        // Average booking value
        $avg_booking_value = $wpdb->get_var(
            "SELECT AVG(total_amount) FROM {$bookings_table}
             WHERE {$where_clause} AND status IN ('CONFIRMED', 'TICKETED')"
        );
        
        return array(
            'total_revenue' => floatval($total_revenue ?? 0),
            'avg_booking_value' => floatval($avg_booking_value ?? 0),
            'revenue_by_source' => $revenue_by_source,
            'revenue_by_agent' => $revenue_by_agent,
            'revenue_trends' => $revenue_trends,
            'total_bookings' => count($revenue_trends) > 0 ? array_sum(array_column($revenue_trends, 'bookings')) : 0
        );
    }
    
    /**
     * Get performance metrics
     *
     * @param array $filters Filters
     * @return array Performance data
     */
    public function get_performance_metrics($filters = array()) {
        global $wpdb;
        $leads_table = $wpdb->prefix . 'amadex_leads';
        $activities_table = $wpdb->prefix . 'amadex_lead_activities';
        
        $where = array('1=1');
        if (isset($filters['environment'])) {
            $where[] = $wpdb->prepare('environment = %s', $filters['environment']);
        }
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare('DATE(created_at) >= %s', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare('DATE(created_at) <= %s', $filters['date_to']);
        }
        $where_clause = implode(' AND ', $where);
        
        // Average response time (time from creation to first contact)
        $response_times = $wpdb->get_results(
            "SELECT 
                l.id,
                l.created_at as lead_created,
                MIN(a.created_at) as first_contact
             FROM {$leads_table} l
             LEFT JOIN {$activities_table} a ON l.id = a.lead_id 
             AND a.activity_type = 'CONTACTED'
             WHERE {$where_clause}
             GROUP BY l.id
             HAVING first_contact IS NOT NULL",
            ARRAY_A
        );
        
        $avg_response_time = 0;
        if (!empty($response_times)) {
            $total_seconds = 0;
            foreach ($response_times as $rt) {
                $created = strtotime($rt['lead_created']);
                $contacted = strtotime($rt['first_contact']);
                $total_seconds += ($contacted - $created);
            }
            $avg_response_time = round($total_seconds / count($response_times) / 3600, 2); // Hours
        }
        
        // Time to conversion
        $conversion_times = $wpdb->get_results(
            "SELECT 
                l.id,
                l.created_at as lead_created,
                l.updated_at as converted_at
             FROM {$leads_table} l
             WHERE {$where_clause} AND l.status = 'CONVERTED'",
            ARRAY_A
        );
        
        $avg_conversion_time = 0;
        if (!empty($conversion_times)) {
            $total_seconds = 0;
            foreach ($conversion_times as $ct) {
                $created = strtotime($ct['lead_created']);
                $converted = strtotime($ct['converted_at']);
                $total_seconds += ($converted - $created);
            }
            $avg_conversion_time = round($total_seconds / count($conversion_times) / 86400, 2); // Days
        }
        
        // Source performance
        $source_performance = $wpdb->get_results(
            "SELECT 
                source,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'CONVERTED' THEN 1 ELSE 0 END) as converted,
                AVG(CASE WHEN status = 'CONVERTED' THEN 1 ELSE 0 END) * 100 as conversion_rate
             FROM {$leads_table}
             WHERE {$where_clause}
             GROUP BY source",
            ARRAY_A
        );
        
        return array(
            'avg_response_time_hours' => $avg_response_time,
            'avg_conversion_time_days' => $avg_conversion_time,
            'source_performance' => $source_performance
        );
    }
    
    /**
     * Get dashboard summary
     *
     * @param array $filters Filters
     * @return array Summary data
     */
    public function get_dashboard_summary($filters = array()) {
        $funnel = $this->get_conversion_funnel($filters);
        $revenue = $this->get_revenue_analytics($filters);
        $performance = $this->get_performance_metrics($filters);
        
        return array(
            'funnel' => $funnel,
            'revenue' => $revenue,
            'performance' => $performance,
            'period' => array(
                'from' => $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
                'to' => $filters['date_to'] ?? date('Y-m-d')
            )
        );
    }
}
