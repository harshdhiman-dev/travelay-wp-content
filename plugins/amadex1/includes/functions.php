<?php
if (!function_exists('amadex_convert_price')) {
    function amadex_convert_price($price) {
        $options = get_option('amadex_currency_settings');

        $from = $options['from_currency'] ?? 'INR';
        $to   = $options['to_currency'] ?? 'USD';
        $rate = 1;

        if ($from === 'INR' && $to === 'USD') {
            $rate = $options['inr_to_usd'] ?? 0.0081;
        } elseif ($from === 'USD' && $to === 'INR') {
            $rate = $options['usd_to_inr'] ?? 123.31;
        } elseif ($from === $to) {
            $rate = 1;
        }

        $converted_price = $price * $rate;
        return number_format($converted_price, 2) . ' ' . esc_html($to);
    }
}
