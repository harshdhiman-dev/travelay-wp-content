<?php
/**
 * Amadex Travel Deals Feature
 * Display featured flight deals with slider and tabs
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Amadex_Deals {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('amadex_travel_deals', array($this, 'render_deals_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_amadex_get_deals', array($this, 'ajax_get_deals'));
        add_action('wp_ajax_nopriv_amadex_get_deals', array($this, 'ajax_get_deals'));
    }
    
    /**
     * Render deals shortcode
     */
    public function render_deals_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'Travel Deals Under $300',
            'price_limit' => 300,
            'destinations' => 'New York,Atlanta,Alaska,Washington,Sacramento',
            'show_tabs' => 'yes',
            'cards_per_row' => 4,
            'max_deals' => 8
        ), $atts, 'amadex_travel_deals');
        
        // Convert destinations to array
        $destinations = array_map('trim', explode(',', $atts['destinations']));
        
        ob_start();
        ?>
        <div class="amadex-deals-section" data-price-limit="<?php echo esc_attr($atts['price_limit']); ?>">
            <!-- Header with Title -->
            <div class="amadex-deals-header">
                <h2 class="amadex-deals-title">
                    <?php 
                    $title_parts = explode('$', $atts['title']);
                    if (count($title_parts) > 1) {
                        echo esc_html($title_parts[0]);
                        echo '<span class="amadex-price-highlight">$' . esc_html($title_parts[1]) . '</span>';
                    } else {
                        echo esc_html($atts['title']);
                    }
                    ?>
                </h2>
                
                <?php if ($atts['show_tabs'] === 'yes' && !empty($destinations)): ?>
                <!-- Tab Navigation -->
                <div class="amadex-deals-nav">
                    <div class="amadex-deals-tabs">
                        <?php foreach ($destinations as $index => $destination): ?>
                            <button class="amadex-deal-tab<?php echo $index === 0 ? ' active' : ''; ?>" 
                                    data-destination="<?php echo esc_attr($destination); ?>">
                                <?php echo esc_html($destination); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Slider Controls -->
                    <div class="amadex-deals-controls">
                        <button class="amadex-deals-prev" aria-label="Previous">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button class="amadex-deals-next" aria-label="Next">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Deals Grid -->
            <div class="amadex-deals-container">
                <div class="amadex-deals-loader">
                    <div class="amadex-spinner"></div>
                    <p>Loading deals...</p>
                </div>
                
                <div class="amadex-deals-grid" 
                     data-cards-per-row="<?php echo esc_attr($atts['cards_per_row']); ?>"
                     data-max-deals="<?php echo esc_attr($atts['max_deals']); ?>">
                    <!-- Deals will be populated via AJAX -->
                </div>
                
                <div class="amadex-deals-error" style="display: none;">
                    <p>Unable to load deals. Please try again later.</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get deals
     */
    public function ajax_get_deals() {
        // Verify nonce if needed
        // check_ajax_referer('amadex_nonce', 'nonce');
        
        $destination = sanitize_text_field($_POST['destination'] ?? 'New York');
        $price_limit = floatval($_POST['price_limit'] ?? 300);
        $max_deals = intval($_POST['max_deals'] ?? 8);
        
        // Get deals from API or cache
        $deals = $this->fetch_deals($destination, $price_limit, $max_deals);
        
        if ($deals) {
            wp_send_json_success(array(
                'deals' => $deals,
                'destination' => $destination
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No deals found'
            ));
        }
    }
    
    /**
     * Fetch deals from API
     */
    private function fetch_deals($destination, $price_limit, $max_deals) {
        // Check cache first
        $cache_key = 'amadex_deals_' . sanitize_key($destination) . '_' . $price_limit;
        $cached_deals = get_transient($cache_key);
        
        if ($cached_deals !== false) {
            return $cached_deals;
        }
        
        // For demo, generate sample deals
        // In production, you would call the Amadeus API here
        $deals = $this->generate_sample_deals($destination, $price_limit, $max_deals);
        
        // Cache for 1 hour
        set_transient($cache_key, $deals, HOUR_IN_SECONDS);
        
        return $deals;
    }
    
    /**
     * Generate sample deals for demo
     * Replace this with actual API call
     */
    private function generate_sample_deals($destination, $price_limit, $max_deals) {
        $airlines = array(
            array('code' => '6E', 'name' => 'Indigo Airlines'),
            array('code' => 'UA', 'name' => 'United Airlines'),
            array('code' => 'AA', 'name' => 'American Airlines'),
            array('code' => 'AS', 'name' => 'Alaska Airlines'),
            array('code' => 'DL', 'name' => 'Delta Air Lines'),
            array('code' => 'B6', 'name' => 'JetBlue Airways'),
            array('code' => 'WN', 'name' => 'Southwest Airlines'),
            array('code' => 'F9', 'name' => 'Frontier Airlines')
        );
        
        $destinations_map = array(
            'New York' => array('code' => 'JFK', 'city' => 'New York', 'common_to' => 'ATL,LAX,MIA,ORD'),
            'Atlanta' => array('code' => 'ATL', 'city' => 'Atlanta', 'common_to' => 'JFK,LAX,MIA,DFW'),
            'Alaska' => array('code' => 'ANC', 'city' => 'Anchorage', 'common_to' => 'SEA,PDX,SFO'),
            'Washington' => array('code' => 'IAD', 'city' => 'Washington', 'common_to' => 'LAX,ORD,ATL'),
            'Sacramento' => array('code' => 'SMF', 'city' => 'Sacramento', 'common_to' => 'LAX,SFO,LAS')
        );
        
        $origin = $destinations_map[$destination] ?? $destinations_map['New York'];
        $to_airports = explode(',', $origin['common_to']);
        
        $deals = array();
        
        for ($i = 0; $i < $max_deals; $i++) {
            $airline = $airlines[array_rand($airlines)];
            $to_code = $to_airports[array_rand($to_airports)];
            
            // Generate random dates
            $start_date = date('Y-m-d', strtotime('+' . rand(7, 60) . ' days'));
            $return_date = date('Y-m-d', strtotime($start_date . ' +' . rand(3, 14) . ' days'));
            
            // Generate random price under limit
            $price = rand(100, $price_limit - 1) + (rand(0, 99) / 100);
            
            $deals[] = array(
                'airline' => $airline,
                'origin' => array(
                    'code' => $origin['code'],
                    'city' => $origin['city']
                ),
                'destination' => array(
                    'code' => $to_code,
                    'city' => $this->get_city_name($to_code)
                ),
                'depart_date' => $start_date,
                'return_date' => $return_date,
                'price' => $price,
                'currency' => 'USD'
            );
        }
        
        return $deals;
    }
    
    /**
     * Get city name from airport code
     */
    private function get_city_name($code) {
        $cities = array(
            'ATL' => 'Atlanta',
            'LAX' => 'Los Angeles',
            'MIA' => 'Miami',
            'ORD' => 'Chicago',
            'DFW' => 'Dallas',
            'SEA' => 'Seattle',
            'PDX' => 'Portland',
            'SFO' => 'San Francisco',
            'LAS' => 'Las Vegas',
            'JFK' => 'New York'
        );
        
        return $cities[$code] ?? $code;
    }
}

// Initialize
new Amadex_Deals();






















