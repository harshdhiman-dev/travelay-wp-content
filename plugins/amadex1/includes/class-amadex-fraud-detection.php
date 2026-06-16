<?php
/**
 * Fraud Detection System for Amadex Plugin
 * Handles device fingerprinting, IP analysis, and fraud scoring
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fraud Detection Class
 */
class Amadex_Fraud_Detection {
    
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
     * Process fraud data and calculate score
     * 
     * @param array $device_data Device fingerprinting data from frontend
     * @param string $ip_address IP address
     * @param array $booking_data Booking data
     * @return array Complete fraud data with score
     */
    public function process_fraud_data($device_data, $ip_address, $booking_data) {
        // Get IP geolocation
        $geolocation = $this->get_ip_geolocation($ip_address);
        
        // Get IP risk assessment
        $ip_risk = $this->assess_ip_risk($ip_address, $geolocation);
        
        // Analyze device data
        $device_risk = $this->analyze_device_risk($device_data);
        
        // Analyze behavior
        $behavior_risk = $this->analyze_behavior_risk($device_data);
        
// Analyze payment data
        $payment_risk = $this->analyze_payment_risk($booking_data, $geolocation);
        
        // Analyze email/contact
        $contact_risk = $this->analyze_contact_risk($booking_data);

        // Analyze departure time risk
        $departure_risk = $this->analyze_departure_risk($booking_data);
        
        // Calculate fraud score
        $fraud_score = $this->calculate_fraud_score(
            $ip_risk,
            $device_risk,
            $behavior_risk,
            $payment_risk,
            $contact_risk,
            $departure_risk
        );
        
        // Determine risk level
        $risk_level = $this->determine_risk_level($fraud_score);
        
        // Identify risk factors
        $risk_factors = $this->identify_risk_factors(
            $ip_risk,
            $device_risk,
            $behavior_risk,
            $payment_risk,
            $contact_risk
        );
        
        // Generate device fingerprint hash
        $device_fingerprint = $this->generate_device_fingerprint_hash($device_data);
        
// Compile fraud data
        $fraud_data = array(
            'device' => $device_data,
            'ip' => $ip_address,
            'geolocation' => $geolocation,
            'ip_risk' => $ip_risk,
            'device_risk' => $device_risk,
            'behavior_risk' => $behavior_risk,
            'payment_risk' => $payment_risk,
            'contact_risk' => $contact_risk,
            'departure_risk' => $departure_risk,
            'device_fingerprint' => $device_fingerprint,
            'fraud_score' => $fraud_score,
            'risk_level' => $risk_level,
            'risk_factors' => $risk_factors,
            'collected_at' => current_time('mysql')
        );
        
        return $fraud_data;
    }
    
    /**
     * Get IP geolocation
     * 
     * @param string $ip_address IP address
     * @return array Geolocation data
     */
    private function get_ip_geolocation($ip_address) {
        // Check if IP is private/localhost
        if (!filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return array(
                'country' => 'LOCAL',
                'countryName' => 'Local Network',
                'city' => 'Local',
                'latitude' => 0,
                'longitude' => 0,
                'timezone' => wp_timezone_string(),
                'isp' => 'Local Network'
            );
        }
        
        // Try to get from cache first
        $cache_key = 'amadex_ip_geo_' . md5($ip_address);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // Use free IP geolocation service (ip-api.com)
        $url = "http://ip-api.com/json/{$ip_address}?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query";
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        $geolocation = array(
            'country' => 'UNKNOWN',
            'countryName' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => 0,
            'longitude' => 0,
            'timezone' => wp_timezone_string(),
            'isp' => 'Unknown'
        );
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if ($body && $body['status'] === 'success') {
                $geolocation = array(
                    'country' => $body['countryCode'] ?? 'UNKNOWN',
                    'countryName' => $body['country'] ?? 'Unknown',
                    'region' => $body['region'] ?? '',
                    'regionName' => $body['regionName'] ?? '',
                    'city' => $body['city'] ?? 'Unknown',
                    'postalCode' => $body['zip'] ?? '',
                    'latitude' => floatval($body['lat'] ?? 0),
                    'longitude' => floatval($body['lon'] ?? 0),
                    'timezone' => $body['timezone'] ?? wp_timezone_string(),
                    'isp' => $body['isp'] ?? 'Unknown',
                    'org' => $body['org'] ?? '',
                    'as' => $body['as'] ?? ''
                );
            }
        }
        
        // Cache for 24 hours
        set_transient($cache_key, $geolocation, DAY_IN_SECONDS);
        
        return $geolocation;
    }
    
    /**
     * Assess IP risk
     * 
     * @param string $ip_address IP address
     * @param array $geolocation Geolocation data
     * @return array IP risk assessment
     */
    private function assess_ip_risk($ip_address, $geolocation) {
        $risk = array(
            'isProxy' => false,
            'isVPN' => false,
            'isTor' => false,
            'isHosting' => false,
            'isDatacenter' => false,
            'isMobile' => false,
            'threatScore' => 0,
            'abuseScore' => 0,
            'riskLevel' => 'LOW'
        );
        
        // Check for known proxy/VPN indicators
        $isp_lower = strtolower($geolocation['isp'] ?? '');
        $org_lower = strtolower($geolocation['org'] ?? '');
        
        // VPN detection keywords
        $vpn_keywords = array('vpn', 'proxy', 'tunnel', 'anonymizer', 'tor', 'hosting', 'datacenter', 'server', 'cloud');
        foreach ($vpn_keywords as $keyword) {
            if (strpos($isp_lower, $keyword) !== false || strpos($org_lower, $keyword) !== false) {
                $risk['isVPN'] = true;
                $risk['isProxy'] = true;
                $risk['threatScore'] += 15;
                break;
            }
        }
        
        // Datacenter detection
        $datacenter_keywords = array('amazon', 'google', 'microsoft', 'digitalocean', 'linode', 'vultr', 'ovh', 'hetzner');
        foreach ($datacenter_keywords as $keyword) {
            if (strpos($isp_lower, $keyword) !== false || strpos($org_lower, $keyword) !== false) {
                $risk['isDatacenter'] = true;
                $risk['isHosting'] = true;
                $risk['threatScore'] += 10;
                break;
            }
        }
        
        // Mobile carrier detection
        $mobile_keywords = array('mobile', 'wireless', 'cellular', 'telecom', 'carrier');
        foreach ($mobile_keywords as $keyword) {
            if (strpos($isp_lower, $keyword) !== false) {
                $risk['isMobile'] = true;
                break;
            }
        }
        
        // Determine risk level
        if ($risk['threatScore'] >= 20) {
            $risk['riskLevel'] = 'HIGH';
        } elseif ($risk['threatScore'] >= 10) {
            $risk['riskLevel'] = 'MEDIUM';
        }
        
        return $risk;
    }
    
    /**
     * Analyze device risk
     * 
     * @param array $device_data Device data
     * @return array Device risk assessment
     */
    private function analyze_device_risk($device_data) {
        $risk = array(
            'isBot' => false,
            'botConfidence' => 0,
            'headlessBrowser' => false,
            'automationTools' => array(),
            'incognitoMode' => false,
            'riskScore' => 0
        );
        
        // Check bot detection data
        if (isset($device_data['botDetection'])) {
            $bot = $device_data['botDetection'];
            $risk['isBot'] = $bot['isBot'] ?? false;
            $risk['botConfidence'] = intval($bot['botConfidence'] ?? 0);
            $risk['headlessBrowser'] = $bot['headlessBrowser'] ?? false;
            $risk['automationTools'] = $bot['automationTools'] ?? array();
            
            if ($risk['isBot']) {
                $risk['riskScore'] += 20;
            }
            if ($risk['headlessBrowser']) {
                $risk['riskScore'] += 15;
            }
            if (!empty($risk['automationTools'])) {
                $risk['riskScore'] += 15;
            }
        }
        
        // Check privacy settings
        if (isset($device_data['privacy'])) {
            $privacy = $device_data['privacy'];
            $risk['incognitoMode'] = $privacy['incognitoMode'] ?? false;
            if ($risk['incognitoMode']) {
                $risk['riskScore'] += 5;
            }
        }
        
        return $risk;
    }
    
    /**
     * Analyze behavior risk
     * 
     * @param array $device_data Device data with behavior
     * @return array Behavior risk assessment
     */
    private function analyze_behavior_risk($device_data) {
        $risk = array(
            'unusualPattern' => false,
            'veryFastCompletion' => false,
            'noMouseMovements' => false,
            'suspiciousInteraction' => false,
            'riskScore' => 0
        );
        
        if (!isset($device_data['behavior'])) {
            return $risk;
        }
        
        $behavior = $device_data['behavior'];
        
        // Check for very fast form completion (less than 30 seconds)
        $timeToSubmit = floatval($behavior['timeToFormSubmit'] ?? 0);
        if ($timeToSubmit > 0 && $timeToSubmit < 30) {
            $risk['veryFastCompletion'] = true;
            $risk['riskScore'] += 5;
        }
        
        // Check for no mouse movements
        $mouseMovements = intval($behavior['mouseMovements'] ?? 0);
        if ($mouseMovements === 0 && $timeToSubmit > 10) {
            $risk['noMouseMovements'] = true;
            $risk['riskScore'] += 5;
        }
        
        // Check interaction pattern
        $pattern = $behavior['interactionPattern'] ?? 'NORMAL';
        if ($pattern === 'SUSPICIOUS' || $pattern === 'BOT') {
            $risk['suspiciousInteraction'] = true;
            $risk['unusualPattern'] = true;
            $risk['riskScore'] += 10;
        }
        
        return $risk;
    }
    
    /**
     * Analyze payment risk
     * 
     * @param array $booking_data Booking data
     * @param array $geolocation Geolocation data
     * @return array Payment risk assessment
     */
    private function analyze_payment_risk($booking_data, $geolocation) {
        $risk = array(
            'billingAddressMatch' => true,
            'billingCountryMatch' => true,
            'highVelocity' => false,
            'unusualAmount' => false,
            'cardCountryMismatch' => false,
            'riskScore' => 0
        );
        
        // Check billing address match (if available)
        $billing_country = $booking_data['billing']['country'] ?? '';
        $ip_country = $geolocation['country'] ?? '';
        
        if (!empty($billing_country) && !empty($ip_country) && $billing_country !== $ip_country) {
            $risk['billingCountryMatch'] = false;
            $risk['cardCountryMismatch'] = true;
            $risk['riskScore'] += 10;
        }
        
        // Check for high-value transactions (over $1000)
        $amount = floatval($booking_data['flight']['price']['total'] ?? 0);
        if ($amount > 1000) {
            $risk['unusualAmount'] = true;
            $risk['riskScore'] += 5;
        }
        
        return $risk;
    }
    
    /**
     * Analyze contact risk
     * 
     * @param array $booking_data Booking data
     * @return array Contact risk assessment
     */
    private function analyze_contact_risk($booking_data) {
        $risk = array(
            'disposableEmail' => false,
            'newEmailDomain' => false,
            'phoneMismatch' => false,
            'riskScore' => 0
        );
        
        $email = $booking_data['contact']['email'] ?? '';
        if (!empty($email)) {
            $domain = substr(strrchr($email, "@"), 1);
            
            // Check for disposable email domains
            $disposable_domains = array('tempmail', 'guerrillamail', 'mailinator', '10minutemail', 'throwaway');
            foreach ($disposable_domains as $disposable) {
                if (strpos(strtolower($domain), $disposable) !== false) {
                    $risk['disposableEmail'] = true;
                    $risk['riskScore'] += 8;
                    break;
                }
            }
        }
        
        return $risk;
    }
    
    /**
     * Calculate fraud score
     * 
     * @param array $ip_risk IP risk data
     * @param array $device_risk Device risk data
     * @param array $behavior_risk Behavior risk data
     * @param array $payment_risk Payment risk data
     * @param array $contact_risk Contact risk data
     * @return int Fraud score (0-100)
     */
    // private function calculate_fraud_score($ip_risk, $device_risk, $behavior_risk, $payment_risk, $contact_risk) {
private function calculate_fraud_score($ip_risk, $device_risk, $behavior_risk, $payment_risk, $contact_risk, $departure_risk = array()) {
    $score = 0;
        
        // IP Risk (0-30 points)
        if ($ip_risk['isProxy']) $score += 15;
        if ($ip_risk['isVPN']) $score += 15;
        if ($ip_risk['isTor']) $score += 20;
        if ($ip_risk['isDatacenter']) $score += 10;
        $score += min(15, intval($ip_risk['threatScore']));
        
        // Device Risk (0-25 points)
        if ($device_risk['isBot']) $score += 20;
        if ($device_risk['headlessBrowser']) $score += 15;
        if (!empty($device_risk['automationTools'])) $score += 15;
        if ($device_risk['incognitoMode']) $score += 5;
        
        // Behavior Risk (0-20 points)
        $score += min(20, intval($behavior_risk['riskScore']));
        
        // Payment Risk (0-15 points)
        $score += min(15, intval($payment_risk['riskScore']));
        
        // Contact Risk (0-10 points)
        $score += min(10, intval($contact_risk['riskScore']));
        
// Departure risk (0-15 points)
        if (!empty($departure_risk['within48h'])) $score += 15;
        elseif (!empty($departure_risk['within7days'])) $score += 5;

        return min(100, $score);
    }
    
    /**
     * Determine risk level from score
     * 
     * @param int $fraud_score Fraud score
     * @return string Risk level
     */
    private function determine_risk_level($fraud_score) {
        if ($fraud_score >= 61) {
            return 'CRITICAL';
        } elseif ($fraud_score >= 41) {
            return 'HIGH';
        } elseif ($fraud_score >= 21) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }
    
    /**
     * Identify risk factors
     * 
     * @param array $ip_risk IP risk
     * @param array $device_risk Device risk
     * @param array $behavior_risk Behavior risk
     * @param array $payment_risk Payment risk
     * @param array $contact_risk Contact risk
     * @return array Risk factors
     */

/**
     * Analyze departure time risk
     *
     * @param array $booking_data Booking data
     * @return array Departure risk assessment
     */
    private function analyze_departure_risk($booking_data) {
        $risk = array(
            'within48h'   => false,
            'within7days' => false,
            'departure_at' => '',
            'hours_until_departure' => null,
            'riskScore'   => 0
        );

        $departure_at = $booking_data['flight']['itineraries'][0]['segments'][0]['departure']['at']
            ?? $booking_data['flight']['itineraries'][0]['segments'][0]['departure']['iataCode']
            ?? '';

        // Also try nested structure
        if (empty($departure_at)) {
            $itineraries = $booking_data['flight']['itineraries'] ?? [];
            if (!empty($itineraries[0]['segments'][0]['departure']['at'])) {
                $departure_at = $itineraries[0]['segments'][0]['departure']['at'];
            }
        }

        if (empty($departure_at)) {
            return $risk;
        }

        $risk['departure_at'] = $departure_at;

        $departure_ts = strtotime($departure_at);
        if (!$departure_ts) {
            return $risk;
        }

        $now = current_time('timestamp');
        $hours_diff = ($departure_ts - $now) / 3600;
        $risk['hours_until_departure'] = round($hours_diff, 1);

        if ($hours_diff > 0 && $hours_diff <= 48) {
            $risk['within48h'] = true;
            $risk['riskScore'] = 15;
        } elseif ($hours_diff > 0 && $hours_diff <= 168) { // 7 days
            $risk['within7days'] = true;
            $risk['riskScore'] = 5;
        }

        return $risk;
    }

    private function identify_risk_factors($ip_risk, $device_risk, $behavior_risk, $payment_risk, $contact_risk, $departure_risk = array()) {
        $factors = array();
        
        if ($ip_risk['isProxy']) $factors[] = 'Proxy IP detected';
        if ($ip_risk['isVPN']) $factors[] = 'VPN detected';
        if ($ip_risk['isTor']) $factors[] = 'Tor network detected';
        if ($ip_risk['isDatacenter']) $factors[] = 'Datacenter IP';
        if ($device_risk['isBot']) $factors[] = 'Bot detected';
        if ($device_risk['headlessBrowser']) $factors[] = 'Headless browser';
        if (!empty($device_risk['automationTools'])) {
            $factors[] = 'Automation tools: ' . implode(', ', $device_risk['automationTools']);
        }
        if ($device_risk['incognitoMode']) $factors[] = 'Incognito/private mode';
        if ($behavior_risk['veryFastCompletion']) $factors[] = 'Very fast form completion';
        if ($behavior_risk['noMouseMovements']) $factors[] = 'No mouse movements detected';
        if ($behavior_risk['suspiciousInteraction']) $factors[] = 'Suspicious interaction pattern';
        if (!$payment_risk['billingCountryMatch']) $factors[] = 'Billing country mismatch';
        if ($payment_risk['cardCountryMismatch']) $factors[] = 'Card country mismatch';
        if ($payment_risk['unusualAmount']) $factors[] = 'Unusual transaction amount';
if ($contact_risk['disposableEmail']) $factors[] = 'Disposable email address';

        // Departure risk
        if (!empty($departure_risk['within48h']) && $departure_risk['within48h']) {
            $factors[] = 'Departure within 48 hours';
        }
        
        return $factors;
    }
    
    /**
     * Generate device fingerprint hash
     * 
     * @param array $device_data Device data
     * @return string Fingerprint hash
     */
    private function generate_device_fingerprint_hash($device_data) {
        // Create hash from key device characteristics
        $fingerprint_string = '';
        
        if (isset($device_data['browser'])) {
            $fingerprint_string .= ($device_data['browser']['name'] ?? '') . '|';
            $fingerprint_string .= ($device_data['browser']['version'] ?? '') . '|';
            $fingerprint_string .= ($device_data['browser']['platform'] ?? '') . '|';
        }
        
        if (isset($device_data['screen'])) {
            $fingerprint_string .= ($device_data['screen']['width'] ?? '') . 'x' . ($device_data['screen']['height'] ?? '') . '|';
            $fingerprint_string .= ($device_data['screen']['colorDepth'] ?? '') . '|';
        }
        
        if (isset($device_data['timezone'])) {
            $fingerprint_string .= ($device_data['timezone']['timezone'] ?? '') . '|';
        }
        
        if (isset($device_data['fingerprint'])) {
            $fingerprint_string .= ($device_data['fingerprint']['canvasHash'] ?? '') . '|';
        }
        
        return substr(md5($fingerprint_string), 0, 32);
    }
    
    /**
     * Log fraud data
     * 
     * @param int $lead_id Lead ID
     * @param int|null $booking_id Booking ID
     * @param array $fraud_data Fraud data
     * @return bool Success
     */
    public function log_fraud_data($lead_id, $booking_id, $fraud_data) {
        global $wpdb;
        
        $fraud_logs_table = $wpdb->prefix . 'amadex_fraud_logs';
        
        $data = array(
            'lead_id' => $lead_id,
            'booking_id' => $booking_id,
            'fraud_score' => $fraud_data['fraud_score'],
            'fraud_risk_level' => $fraud_data['risk_level'],
            'device_fingerprint' => $fraud_data['device_fingerprint'],
            'ip_address' => $fraud_data['ip'],
            'ip_country' => $fraud_data['geolocation']['country'] ?? '',
            'risk_factors' => wp_json_encode($fraud_data['risk_factors']),
            'action_taken' => 'ALLOWED'
        );
        
        return $wpdb->insert($fraud_logs_table, $data) !== false;
    }
}
