<?php
/**
 * Agent Assignment Engine for Amadex Plugin
 * Automatically assigns leads to agents based on configurable rules
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assignment Engine Class
 */
class Amadex_Assignment_Engine {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Assignment rules table
     */
    private $rules_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->database = new Amadex_Database();
        $this->rules_table = $wpdb->prefix . 'amadex_assignment_rules';
    }
    
    /**
     * Auto-assign lead to agent based on rules
     *
     * @param int $lead_id Lead ID
     * @param array $lead_data Lead data
     * @return int|false Assigned agent ID or false
     */
    public function auto_assign_lead($lead_id, $lead_data = null) {
        if (!$lead_data) {
            $lead_data = $this->database->get_lead($lead_id);
            if (!$lead_data) {
                return false;
            }
        }
        
        // Get active rules ordered by priority
        $rules = $this->get_active_rules();
        
        foreach ($rules as $rule) {
            // Check if rule conditions match
            if ($this->evaluate_rule($rule, $lead_data)) {
                // Execute rule action (assign agent)
                $agent_id = $this->execute_rule($rule, $lead_data);
                if ($agent_id) {
                    // Assign lead to agent
                    $this->database->assign_lead_to_agent($lead_id, $agent_id);
                    
                    // Log activity
                    $this->log_assignment($lead_id, $agent_id, $rule['id']);
                    
                    return $agent_id;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get active assignment rules
     *
     * @return array Active rules
     */
    public function get_active_rules() {
        global $wpdb;
        
        $rules = $wpdb->get_results(
            "SELECT * FROM {$this->rules_table} 
             WHERE is_active = 1 
             ORDER BY priority DESC, id ASC",
            ARRAY_A
        );
        
        foreach ($rules as &$rule) {
            $rule['conditions'] = json_decode($rule['conditions'], true);
            $rule['actions'] = json_decode($rule['actions'], true);
        }
        
        return $rules;
    }
    
    /**
     * Evaluate if rule conditions match lead data
     *
     * @param array $rule Rule data
     * @param array $lead_data Lead data
     * @return bool True if conditions match
     */
    private function evaluate_rule($rule, $lead_data) {
        $conditions = $rule['conditions'] ?? array();
        
        if (empty($conditions)) {
            return true; // No conditions = always match
        }
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? '';
            
            $lead_value = $this->get_lead_field_value($lead_data, $field);
            
            if (!$this->compare_values($lead_value, $operator, $value)) {
                return false; // Condition doesn't match
            }
        }
        
        return true; // All conditions match
    }
    
    /**
     * Execute rule action (assign agent)
     *
     * @param array $rule Rule data
     * @param array $lead_data Lead data
     * @return int|false Agent ID or false
     */
    private function execute_rule($rule, $lead_data) {
        $actions = $rule['actions'] ?? array();
        $rule_type = $rule['rule_type'] ?? 'ROUND_ROBIN';
        
        switch ($rule_type) {
            case 'ROUND_ROBIN':
                return $this->assign_round_robin($actions);
            case 'TERRITORY':
                return $this->assign_by_territory($lead_data, $actions);
            case 'SKILL_BASED':
                return $this->assign_by_skill($lead_data, $actions);
            case 'LOAD_BASED':
                return $this->assign_by_load($actions);
            case 'VALUE_BASED':
                return $this->assign_by_value($lead_data, $actions);
            default:
                return $this->assign_round_robin($actions);
        }
    }
    
    /**
     * Round-robin assignment
     */
    private function assign_round_robin($actions) {
        $agent_ids = $actions['agent_ids'] ?? array();
        if (empty($agent_ids)) {
            return false;
        }
        
        $agent_ids = array_values(array_map('intval', $agent_ids));
        global $wpdb;
        $leads_table = $wpdb->prefix . 'amadex_leads';
        $placeholders = implode(',', array_fill(0, count($agent_ids), '%d'));
        
        // Get last assigned agent for this rule
        $last_agent = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT assigned_agent_id FROM {$leads_table} 
                 WHERE assigned_agent_id IN ($placeholders)
                 ORDER BY updated_at DESC LIMIT 1",
                ...$agent_ids
            )
        );
        
        if ($last_agent) {
            $current_index = array_search((int) $last_agent, $agent_ids, true);
            if ($current_index !== false) {
                $next_index = ($current_index + 1) % count($agent_ids);
                return $agent_ids[$next_index];
            }
        }
        
        return $agent_ids[0];
    }
    
    /**
     * Territory-based assignment
     */
    private function assign_by_territory($lead_data, $actions) {
        $territory_map = $actions['territory_map'] ?? array();
        $geo_location = $lead_data['geo_location'] ?? '';
        $ip_country = $lead_data['ip_country'] ?? '';
        
        $location = $geo_location ?: $ip_country;
        
        if (isset($territory_map[$location])) {
            return intval($territory_map[$location]);
        }
        
        // Fallback to default agent
        return isset($actions['default_agent_id']) ? intval($actions['default_agent_id']) : false;
    }
    
    /**
     * Skill-based assignment
     */
    private function assign_by_skill($lead_data, $actions) {
        $skill_map = $actions['skill_map'] ?? array();
        $flight_data = is_string($lead_data['flight_data']) ? json_decode($lead_data['flight_data'], true) : $lead_data['flight_data'];
        
        // Determine skill needed (e.g., domestic vs international)
        $is_international = false;
        if (!empty($flight_data['itineraries'][0]['segments'])) {
            $first_seg = $flight_data['itineraries'][0]['segments'][0];
            $last_seg = end($flight_data['itineraries'][0]['segments']);
            $origin_country = $this->get_country_from_airport($first_seg['departure']['iataCode'] ?? '');
            $dest_country = $this->get_country_from_airport($last_seg['arrival']['iataCode'] ?? '');
            $is_international = ($origin_country !== $dest_country);
        }
        
        $skill = $is_international ? 'international' : 'domestic';
        
        if (isset($skill_map[$skill])) {
            return intval($skill_map[$skill]);
        }
        
        return isset($actions['default_agent_id']) ? intval($actions['default_agent_id']) : false;
    }
    
    /**
     * Load-based assignment (least busy agent)
     */
    private function assign_by_load($actions) {
        $agent_ids = $actions['agent_ids'] ?? array();
        if (empty($agent_ids)) {
            return false;
        }
        
        global $wpdb;
        $leads_table = $wpdb->prefix . 'amadex_leads';
        $placeholders = implode(',', array_fill(0, count($agent_ids), '%d'));
        
        // Count active leads per agent
        $counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT assigned_agent_id, COUNT(*) as count 
                 FROM {$leads_table} 
                 WHERE assigned_agent_id IN ($placeholders)
                 AND status NOT IN ('CONVERTED', 'CANCELLED', 'EXPIRED')
                 GROUP BY assigned_agent_id",
                ...array_map('intval', $agent_ids)
            ),
            ARRAY_A
        );
        
        $agent_loads = array();
        foreach ($agent_ids as $agent_id) {
            $agent_loads[$agent_id] = 0;
        }
        
        foreach ($counts as $count) {
            $agent_loads[$count['assigned_agent_id']] = intval($count['count']);
        }
        
        // Return agent with least load
        asort($agent_loads);
        return intval(array_key_first($agent_loads));
    }
    
    /**
     * Value-based assignment (high value to senior agents)
     */
    private function assign_by_value($lead_data, $actions) {
        $total_amount = floatval($lead_data['total_amount'] ?? 0);
        $high_value_threshold = floatval($actions['high_value_threshold'] ?? 1000);
        $high_value_agents = $actions['high_value_agent_ids'] ?? array();
        $standard_agents = $actions['standard_agent_ids'] ?? array();
        
        if ($total_amount >= $high_value_threshold && !empty($high_value_agents)) {
            // Assign to high-value agent (round-robin among them)
            return $this->assign_round_robin(array('agent_ids' => $high_value_agents));
        }
        
        if (!empty($standard_agents)) {
            return $this->assign_round_robin(array('agent_ids' => $standard_agents));
        }
        
        return false;
    }
    
    /**
     * Get lead field value
     */
    private function get_lead_field_value($lead_data, $field) {
        $field_map = array(
            'status' => 'status',
            'lead_type' => 'lead_type',
            'source' => 'source',
            'amount' => 'total_amount',
            'currency' => 'currency',
            'environment' => 'environment',
            'fraud_score' => 'fraud_score',
            'country' => 'ip_country'
        );
        
        $actual_field = $field_map[$field] ?? $field;
        return $lead_data[$actual_field] ?? null;
    }
    
    /**
     * Compare values based on operator
     */
    private function compare_values($lead_value, $operator, $rule_value) {
        switch ($operator) {
            case 'equals':
                return $lead_value == $rule_value;
            case 'not_equals':
                return $lead_value != $rule_value;
            case 'greater_than':
                return floatval($lead_value) > floatval($rule_value);
            case 'less_than':
                return floatval($lead_value) < floatval($rule_value);
            case 'contains':
                return stripos($lead_value, $rule_value) !== false;
            case 'in':
                $values = is_array($rule_value) ? $rule_value : explode(',', $rule_value);
                return in_array($lead_value, $values);
            default:
                return false;
        }
    }
    
    /**
     * Get country from airport code (simplified)
     */
    private function get_country_from_airport($iata_code) {
        // Simplified - in production, use airport database
        $us_airports = array('JFK', 'LAX', 'ORD', 'DFW', 'DEN', 'SFO', 'SEA', 'MIA', 'ATL', 'PHX');
        return in_array($iata_code, $us_airports) ? 'US' : 'OTHER';
    }
    
    /**
     * Log assignment activity
     */
    private function log_assignment($lead_id, $agent_id, $rule_id) {
        global $wpdb;
        $activities_table = $wpdb->prefix . 'amadex_lead_activities';
        
        $wpdb->insert(
            $activities_table,
            array(
                'lead_id' => $lead_id,
                'activity_type' => 'ASSIGNED',
                'user_id' => get_current_user_id(),
                'description' => sprintf('Auto-assigned to agent #%d via rule #%d', $agent_id, $rule_id),
                'metadata' => wp_json_encode(array('agent_id' => $agent_id, 'rule_id' => $rule_id))
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Save assignment rule
     *
     * @param array $rule_data Rule data
     * @return int|false Rule ID or false
     */
    public function save_rule($rule_data) {
        global $wpdb;
        
        $data = array(
            'rule_name' => sanitize_text_field($rule_data['rule_name'] ?? ''),
            'rule_type' => sanitize_text_field($rule_data['rule_type'] ?? 'ROUND_ROBIN'),
            'priority' => intval($rule_data['priority'] ?? 0),
            'conditions' => wp_json_encode($rule_data['conditions'] ?? array()),
            'actions' => wp_json_encode($rule_data['actions'] ?? array()),
            'is_active' => isset($rule_data['is_active']) ? 1 : 0
        );
        
        if (isset($rule_data['id']) && $rule_data['id'] > 0) {
            // Update existing rule
            $wpdb->update(
                $this->rules_table,
                $data,
                array('id' => intval($rule_data['id'])),
                array('%s', '%s', '%d', '%s', '%s', '%d'),
                array('%d')
            );
            return intval($rule_data['id']);
        } else {
            // Insert new rule
            $wpdb->insert(
                $this->rules_table,
                $data,
                array('%s', '%s', '%d', '%s', '%s', '%d')
            );
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete assignment rule
     *
     * @param int $rule_id Rule ID
     * @return bool Success
     */
    public function delete_rule($rule_id) {
        global $wpdb;
        return $wpdb->delete(
            $this->rules_table,
            array('id' => intval($rule_id)),
            array('%d')
        ) !== false;
    }
    
    /**
     * Get rule by ID
     *
     * @param int $rule_id Rule ID
     * @return array|false Rule data or false
     */
    public function get_rule($rule_id) {
        global $wpdb;
        
        $rule = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->rules_table} WHERE id = %d", intval($rule_id)),
            ARRAY_A
        );
        
        if ($rule) {
            $rule['conditions'] = json_decode($rule['conditions'], true);
            $rule['actions'] = json_decode($rule['actions'], true);
        }
        
        return $rule;
    }
}
