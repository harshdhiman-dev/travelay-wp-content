<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
/**
 * Register custom REST API endpoint for fetching coin data.
 */
if (!function_exists('ccwfg_register_coinpaprika_api_endpoint')) { 
function ccwfg_register_coinpaprika_api_endpoint() {
    register_rest_route('coin-paprika/v1', '/coin-data', array(
        'methods'             => 'GET',
        'callback'            => 'ccwfg_fetch_coin_data',
        'permission_callback' => '__return_true', // Allow access to anyone
    ));
}
}
add_action('rest_api_init', 'ccwfg_register_coinpaprika_api_endpoint');

/**
 * Callback function for fetching coin data.
 *
 * @return array|WP_Error
 */
if (!function_exists('ccwfg_fetch_coin_data')) { 
function ccwfg_fetch_coin_data() {
    // Check if data exists in transient cache
    $coin_data = get_transient('ccwfg_coinpaprika_data');

    if (false === $coin_data) {
        // Fetch data from API
        $url      = 'https://api.coinpaprika.com/v1/tickers';
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return new WP_Error('error', 'Failed to fetch data from Coinpaprika API');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Slice data to get only the first 200 items
        $data = array_slice($data, 0, 200);

        // Save data in transient for 10 minutes
        set_transient('ccwfg_coinpaprika_data', $data, 10 * MINUTE_IN_SECONDS);

        return $data;
    }

    return $coin_data;
}
}
/**
 * Format number if less than one.
 *
 * @param float $number Number to format.
 * @return string Formatted number.
 */
if (!function_exists('ccwfg_format_number_if_less_than_one')) { 
function ccwfg_format_number_if_less_than_one($number) {
    $number = (float) $number;

    if ($number < 0.01 && $number != 0) {
        $number_str = sprintf('%f', $number);
        $parts      = explode('.', $number_str);
        $decimal_places = isset($parts[1]) ? strlen(rtrim($parts[1], '0')) : 0;

        return number_format($number, $decimal_places, '.', '');
    }

    return number_format($number, 2, '.', '');
}
}
/**
 * Format number with suffix.
 *
 * @param float $number Number to format.
 * @return string Formatted number with suffix.
 */
if (!function_exists('ccwfg_format_number_with_suffix')) { 
function ccwfg_format_number_with_suffix($number) {
    $number = (float) $number;
    $isNegative = $number < 0;
    $number = abs($number);

    $suffixes = array(
        array('value' => 1e12, 'suffix' => 'T'), // Trillion
        array('value' => 1e9, 'suffix' => 'B'),  // Billion
        array('value' => 1e6, 'suffix' => 'M'),  // Million
        array('value' => 1e3, 'suffix' => 'K'),  // Thousand
    );

    foreach ($suffixes as $suffix) {
        if ($number >= $suffix['value']) {
            $formatted_number = number_format($number / $suffix['value'], 2);
            return ($isNegative ? '-' : '') . $formatted_number . $suffix['suffix'];
        }
    }

    return ($isNegative ? '-' : '') . number_format($number, 2);
}
}
/**
 * Filter selected coins from all data.
 *
 * @param array $selectedValues Array of selected values.
 * @param array $allData Array of all data.
 * @return array Filtered data.
 */
if (!function_exists('ccwfg_filterSelectedCoins')) { 
function ccwfg_filterSelectedCoins($selectedValues, $allData) {
    $dataMap = array_column($allData, null, 'id');
    $filteredData = array();

    foreach ($selectedValues as $selected) {        
        if (isset($dataMap[$selected['value']])) {
            $filteredData[] = $dataMap[$selected['value']];
        }
    }

    return $filteredData;
}
}
/**
 * Display coin data in a table.
 *
 * @param array $data Array of coin data.
 * @param array $enabled_fields Array of enabled fields.
 */
if (!function_exists('ccwfg_display_coin_data_table')) { 
function ccwfg_display_coin_data_table($data, $enabled_fields) {
    if (empty($data)) {
        echo '<p>No data available</p>';
        return;
    }

    echo '<table class="wp-block-coinpaprika-block">';
    echo '<thead><tr>';

    foreach ($enabled_fields as $key => $value) {
        echo '<th>' . esc_html(ucfirst($value)) . '</th>';
    }

    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($data as $coin) {
        $usdprice=$coin['quotes']['USD'];
        $changescolor=($usdprice['market_cap_change_24h']< 0)?'red':'green';
        echo '<tr>';
        if (isset($enabled_fields['rank'])) {
            echo '<td>' . esc_html($coin['rank']) . '</td>';
        }
        if (isset($enabled_fields['name'])) {
            echo '<td class="logo-name">';
            echo '<img src="https://static.coinpaprika.com/coin/' . esc_attr($coin['id']) . '/logo.png" alt="' . esc_attr($coin['name']) . ' logo"> ';
            echo esc_html($coin['name']) . ' ' . esc_html($coin['symbol']);
            echo '</td>';
        }
        if (isset($enabled_fields['price'])) {
            echo '<td>$' . ccwfg_format_number_if_less_than_one(esc_html($usdprice['price'])) . '</td>';
        }
        if (isset($enabled_fields['24hchanges'])) {
            echo '<td style="color:'.esc_attr($changescolor).'">' . esc_html($usdprice['market_cap_change_24h']) . '%</td>';
        }
        if (isset($enabled_fields['volume'])) {
            echo '<td>$' . ccwfg_format_number_with_suffix(esc_html($usdprice['volume_24h'])) . '</td>';
        }
        if (isset($enabled_fields['marketcap'])) {
            echo '<td>$' . ccwfg_format_number_with_suffix(esc_html($usdprice['market_cap'])) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
}
/**
 * Display coin data with labels.
 *
 * @param array $data Array of coin data.
 * @param array $enabled_fields Array of enabled fields.
 * @param bool  $removestyle Whether to remove styles.
 */
if (!function_exists('ccwfg_display_coin_data_label')) { 
function ccwfg_display_coin_data_label($data) { 

    echo '<div class="wp-block-coinpaprika-block">';
    echo '<div class="coin-container">';

    foreach ($data as $coin) {
        echo '<div class="coin-stats">';       
            echo '<span class="label">';
            echo '<img src="https://static.coinpaprika.com/coin/' . esc_attr($coin['id']) . '/logo.png" alt="' . esc_attr($coin['name']) . ' logo"> ';
            echo '<span>' . esc_html($coin['name']) . ' ' . esc_html($coin['symbol']) . '</span>';
            echo '</span>';    
        echo '<span class="value">$' . esc_html(ccwfg_format_number_if_less_than_one($coin['quotes']['USD']['price'])) . '</span>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}
}
/**
 * Display coin data with Tickers.
 *
 * @param array $data Array of coin data.
 * @param array $enabled_fields Array of enabled fields.
 * @param bool  $removestyle Whether to remove styles.
 */
if (!function_exists('ccwfg_display_coin_data_ticker')) { 
function ccwfg_display_coin_data_ticker($data, $tickerspeed=30) {    


     // Add the inline style to the enqueued stylesheet


    echo '<div class="wp-block-coinpaprika-block">';
    echo '<div class="ticker-wrapper">';
    echo '<div class="ticker" style="animation:ticker '.esc_attr($tickerspeed).'s linear infinite">';
    echo '<div class="ticker-content">';
    
    // Print the ticker content twice to ensure seamless scrolling
    foreach ($data as $coin) {
        echo '<p>';

       
            echo '<span class="label">';
            echo '<img src="https://static.coinpaprika.com/coin/' . esc_attr($coin['id']) . '/logo.png" alt="' . esc_attr($coin['name']) . ' logo"> ';
            echo '<span>' . esc_html($coin['name']) . ' ' . esc_html($coin['symbol']) . '</span>';
            echo '</span>';
        
      
            echo '<span class="value">$' . esc_html(ccwfg_format_number_if_less_than_one($coin['quotes']['USD']['price'])) . '</span>';
        

        echo '</p>';
    }
    
    // Repeat the content to ensure a seamless loop
    foreach ($data as $coin) {
        echo '<p>';

  
            echo '<span class="label">';
            echo '<img src="https://static.coinpaprika.com/coin/' . esc_attr($coin['id']) . '/logo.png" alt="' . esc_attr($coin['name']) . ' logo"> ';
            echo '<span>' . esc_html($coin['name']) . ' ' . esc_html($coin['symbol']) . '</span>';
            echo '</span>';
        
      
            echo '<span class="value">$' . esc_html(ccwfg_format_number_if_less_than_one($coin['quotes']['USD']['price'])) . '</span>';
        

        echo '</p>';
    }

    echo '</div>'; // End ticker-content
    echo '</div>'; // End ticker
    echo '</div>'; // End ticker-wrapper
    echo '</div>'; // End wp-block-coinpaprika-block
}
}
if (!function_exists('ccwfg_display_coin_data_text')) { 
function ccwfg_display_coin_data_text($data,$texts) {    
    echo '<div className="coin-text-wrap">';
    foreach ($data as $coin) {  
              
        echo '<div class="coin-text-inner">'. wp_kses_post(ccwfg_replacePlaceholders($texts,$coin)).'</div>';      
    }
    echo '</div>';
  
}
}


/**
 * Replaces placeholders in the given text with values from the coinsuds array.
 *
 * @param string $text The text containing placeholders.
 * @param array $coinsuds An associative array with 'name' and 'price' keys.
 * @return string The text with placeholders replaced.
 */
if (!function_exists('ccwfg_replacePlaceholders')) { 
function ccwfg_replacePlaceholders($text, $coin) {
    $coinsuds=$coin['quotes']['USD'];
    // Check if the $coinsuds array has the required keys
    if (!isset($coinsuds['price'])) {
        throw new InvalidArgumentException('The coinsuds array must contain  "price" keys.');
    }
   
   
    // Replace placeholders with values from the coinsuds array
    $text = str_replace('[coin-rank]', '<b>'.$coin['rank'].'</b>', $text);
    $text = str_replace('[coin-name]', '<b>'.$coin['name'].'</b>', $text);
    $text = str_replace('[coin-price]', '<b>$'.ccwfg_format_number_if_less_than_one($coinsuds['price']).'</b>', $text);
    $text = str_replace('[coin-marketcap]', '<b>$'.ccwfg_format_number_with_suffix($coinsuds['market_cap']).'</b>' , $text);
    return $text;
}
}