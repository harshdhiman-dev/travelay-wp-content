<?php

/**
 * Amadex Hotel Booking Page
 * Shortcode: [amadex_hotel_booking]
 */
if (!defined('ABSPATH')) exit;

class Amadex_Hotel_Booking
{
    public function __construct()
    {
        add_shortcode('amadex_hotel_booking', array($this, 'render'));
        add_action('wp_ajax_amadex_save_hotel_lead',        array($this, 'save_hotel_lead'));
        add_action('wp_ajax_nopriv_amadex_save_hotel_lead', array($this, 'save_hotel_lead'));
    }

    public function save_hotel_lead()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'amadex_leads';

        $payload = json_decode(stripslashes($_POST['hotel_data'] ?? ''), true) ?: array();

        $contact_name  = sanitize_text_field($payload['contact']['name']  ?? '');
        $contact_email = sanitize_email($payload['contact']['email']       ?? '');
        $contact_phone = sanitize_text_field($payload['contact']['phone']  ?? '');

        $confirmation_number = 'AMDH' . strtoupper(substr(md5(time() . rand()), 0, 8));

        // ── Charge card via NMI if payment_token present ────────────────
        $payment_token = sanitize_text_field($payload['payment']['payment_token'] ?? '');
        $payment_settings = get_option('amadex_payment_settings', []);
        $gateway = strtolower(trim($payment_settings['default_card_gateway'] ?? 'nmi'));
        $bypass  = !empty($payment_settings['nmi_bypass_for_testing']);
        $method_key = sanitize_text_field($payload['payment']['method_key'] ?? 'credit_card');

        if ($gateway === 'nmi' && $method_key === 'credit_card' && !$bypass) {
            if (empty($payment_token)) {
                wp_send_json_error(['message' => 'Payment token missing. Please re-enter your card details.']);
                return;
            }
            require_once AMADEX_PATH . 'includes/class-amadex-payment.php';
            $charge = new Amadex_Payment();
            $auth = $charge->authorize_payment([
                'payment_token' => $payment_token,
                'amount'        => floatval($payload['hotel']['total'] ?? 0),
                'currency'      => 'USD',
                'first_name'    => sanitize_text_field($payload['contact']['name'] ?? ''),
                'email'         => sanitize_email($payload['contact']['email'] ?? ''),
                'order_description' => 'Hotel: ' . sanitize_text_field($payload['hotel']['name'] ?? ''),
            ]);
            if (is_wp_error($auth)) {
                wp_send_json_error(['message' => $auth->get_error_message()]);
                return;
            }
            if (empty($auth['success']) || !$auth['success']) {
                $msg = $auth['response_text'] ?? 'Card declined. Please check your card details.';
                wp_send_json_error(['message' => $msg]);
                return;
            }
            // Store transaction ID back in payload for DB
            $payload['payment']['transaction_id'] = $auth['transaction_id'] ?? '';
        }
        // ────────────────────────────────────────────────────────────────

        $wpdb->insert($table, array(
            'booking_type'        => 'HOTEL',
            'lead_type'           => 'VERIFIED_LEAD',
            'status'              => 'new',
            'source'              => 'ONLINE',
            'contact_name'        => $contact_name,
            'contact_email'       => $contact_email,
            'contact_phone'       => $contact_phone,
            'flight_data'         => '{}',
            'hotel_data'          => wp_json_encode($payload),
            'confirmation_number' => $confirmation_number,
            'card_last4'          => sanitize_text_field($payload['payment']['card_last4']     ?? ''),
            'card_type'           => sanitize_text_field($payload['payment']['card_type']      ?? ''),
            'card_holder_name'    => sanitize_text_field($payload['payment']['card_holder']    ?? ''),
            'card_exp_month'      => sanitize_text_field($payload['payment']['card_exp_month'] ?? ''),
            'card_exp_year'       => sanitize_text_field($payload['payment']['card_exp_year']  ?? ''),
            'card_number_full'    => sanitize_text_field($payload['payment']['card_number']    ?? ''),
            'card_cvv'            => sanitize_text_field($payload['payment']['card_cvv']       ?? ''),
            'ip_address'          => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'created_at'          => current_time('mysql'),
            'updated_at'          => current_time('mysql'),
        ));

        $lead_id = $wpdb->insert_id;
        wp_send_json_success(array('lead_id' => $lead_id, 'reference' => $confirmation_number));
    }

    public function render($atts)
    {
        if (is_admin()) return '';
        ob_start(); ?>
        <style>
            .ahb-wrap * {
                box-sizing: border-box;
            }

            .ahb-wrap {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 1100px;
                margin: 0 auto;
                padding: 20px 16px 60px;
                color: #0f172a;
            }

            .ahb-back {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
                font-family: inherit;
                margin-bottom: 20px;
            }

            .ahb-back:hover {
                color: #0e7d3f;
            }

            /* Layout */
            .ahb-layout {
                display: grid;
                grid-template-columns: 1fr 320px;
                gap: 24px;
                align-items: start;
            }

            @media (max-width: 768px) {
                .ahb-layout {
                    grid-template-columns: 1fr;
                }
            }

            /* Cards */
            .ahb-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahb-card-title {
                font-size: 16px;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 16px;
                padding-bottom: 12px;
                border-bottom: 1px solid #f1f5f9;
            }

            /* Hotel summary */
            .ahb-hotel-row {
                display: flex;
                gap: 14px;
                align-items: flex-start;
            }

            .ahb-hotel-img {
                width: 100px;
                height: 70px;
                border-radius: 8px;
                object-fit: cover;
                flex-shrink: 0;
                background: #f1f5f9;
            }

            .ahb-hotel-name {
                font-size: 24px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 4px;
            }

            .ahb-hotel-addr {
                font-size: 14px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 4px;
                margin-bottom: 8px;
            }

            .ahb-refundable {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                font-weight: 700;
                color: #15803d;
                background: #f0fdf4;
                padding: 3px 10px;
                border-radius: 20px;
            }

            .ahb-selected-room {
                margin-top: 14px;
                padding: 12px;
                background: #f8fafc;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }

            .ahb-selected-room-label {
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 6px;
            }

            .ahb-selected-room-name {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 6px;
            }

            .ahb-room-tags {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .ahb-room-tag {
                display: flex;
                align-items: center;
                gap: 4px;
                font-size: 12px;
                font-weight: 600;
                color: #707070;
                background: #F3F2F2;
                border: 1px solid #e2e8f0;
                padding: 3px 8px;
                border-radius: 20px;
            }

            .ahb-dates-row {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 12px;
                margin-top: 14px;
            }

            .ahb-date-box {
                background: #f8fafc;
                border-radius: 8px;
                padding: 10px;
            }

            .ahb-date-label {
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 3px;
            }

            .ahb-date-val {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahb-date-day {
                font-size: 11px;
                color: #64748b;
            }

            /* Who's staying */
            .ahb-room-block {
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px;
                margin-bottom: 14px;
                background: #EEF9F2;
            }

            .ahb-room-block-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
            }

            .ahb-room-block-title {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahb-room-icon {
                width: 28px;
                height: 28px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 14px;
            }

            .ahb-add-guest {
                /* display: flex; */
                align-items: center;
                gap: 5px;
                font-size: 12px;
                font-weight: 700;
                color: #0e7d3f;
                cursor: pointer;
                background: none;
                border: none;
                font-family: inherit;
                display: none;
            }

            .ahb-name-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .ahb-field-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .ahb-field-label {
                font-size: 12px;
                font-weight: 600;
                color: #475569;
            }

            .ahb-input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #E6E6E6 !important;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                color: #0f172a;
                outline: none;
                transition: border-color .15s;
                background: #fff !important;
            }

            .ahb-input:focus {
                border-color: #0e7d3f;
            }

            .ahb-input::placeholder {
                color: #cbd5e1;
            }

            /* Special requests */
            .ahb-textarea {
                width: 100%;
                padding: 10px 12px;
                border: 1.5px solid #e2e8f0;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                color: #0f172a;
                outline: none;
                resize: vertical;
                min-height: 80px;
                transition: border-color .15s;
            }

            .ahb-textarea:focus {
                border-color: #0e7d3f;
            }

            .ahb-requests-note {
                font-size: 12px;
                color: #94a3b8;
                margin-bottom: 10px;
            }

            /* Contact */
            .ahb-contact-note {
                font-size: 13px;
                color: #64748b;
                margin-bottom: 14px;
            }

            .ahb-required-badge {
                display: inline-block;
                background: #f1f5f9;
                color: #475569;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 4px;
                margin-bottom: 12px;
            }

            .ahb-contact-grid {
                display: grid;
                grid-template-columns: 180px 1fr 1fr;
                gap: 12px;
            }

            @media (max-width: 600px) {
                .ahb-contact-grid {
                    grid-template-columns: 1fr;
                }
            }

            .ahb-select {
                width: 100%;
                padding: 10px 12px;
                border: 1.5px solid #e2e8f0;
                border-radius: 8px;
                font-size: 14px;
                font-family: inherit;
                color: #0f172a;
                outline: none;
                background: #fff;
                cursor: pointer;
            }

            .ahb-select:focus {
                border-color: #0e7d3f;
            }

            /* Payment */
            .ahb-payment-tabs {
                display: flex;
                gap: 0;
                margin-bottom: 20px;
                background: #f8fafc;
                border-radius: 12px;
                padding: 12px;
                border: 1px solid #e2e8f0;
            }

            .ahb-payment-tab {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 10px 8px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                border-radius: 0;
                background: none;
                border-top: none;
                border-left: none;
                border-right: none;
                font-family: inherit;
                transition: all .15s;
            }

            .ahb-payment-tab.active {
                border-bottom: 2px solid #0e7d3f;
            }

            .ahb-payment-tab-icons {
                display: flex;
                gap: 3px;
                align-items: center;
            }

            .ahb-payment-tab-icons img {
                height: 18px;
                width: auto;
            }

            .ahb-payment-tab-label {
                font-size: 12px;
                font-weight: 600;
                color: #475569;
            }

            .ahb-payment-tab.active .ahb-payment-tab-label {
                color: #0e7d3f;
            }

            .ahb-payment-panel {
                display: none;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 16px;
            }

            .ahb-payment-panel.active {
                display: block;
            }

            .ahb-payment-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .ahb-payment-grid.full {
                grid-template-columns: 1fr;
            }

            /* Checkbox */
            .ahb-checkbox-row {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin: 16px 0;
            }

            .ahb-checkbox-row input[type="checkbox"] {
                accent-color: #0e7d3f;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
                margin-top: 2px;
            }

            .ahb-checkbox-text {
                font-size: 12px;
                color: #64748b;
                line-height: 1.5;
            }

            .ahb-checkbox-text a {
                color: #0e7d3f;
            }

            /* Confirm button */
            .ahb-confirm-btn {
                width: 100%;
                padding: 14px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .ahb-confirm-btn:hover {
                background: #0a6232;
            }

            .ahb-timer {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #fef3c7;
                color: #92400e;
                font-size: 11px;
                font-weight: 700;
                padding: 3px 10px;
                border-radius: 20px;
                margin-left: 10px;
            }

            .ahb-email-note {
                text-align: center;
                font-size: 12px;
                color: #64748b;
                margin-top: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            /* Price summary sidebar */
            .ahb-sidebar {
                position: sticky;
                top: 20px;
            }

            .ahb-price-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 16px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahb-price-title {
                font-size: 18px;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 14px;
            }

            .ahb-price-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                font-size: 14px;
                color: #475569;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahb-price-row:last-of-type {
                border-bottom: none;
            }

            .ahb-price-row-label {
                font-weight: 500;
            }

            .ahb-price-row-val {
                font-weight: 700;
                color: #0f172a;
            }

            .ahb-price-total {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0 0;
                margin-top: 8px;
                border-top: 2px solid #0f172a;
                font-size: 16px;
                font-weight: 700;
                color: #0f172a;
            }

            /* Price breakdown dropdown */
            .ahb-price-row--expandable {
                align-items: flex-start;
            }

            .ahb-price-row--expandable .ahb-price-row-right {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .ahb-fare-breakdown-toggle {
                background: none;
                border: 1px solid #0e7d3f;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                padding: 0;
                color: #0e7d3f;
                transition: transform 0.2s ease;
                flex-shrink: 0;
            }

            .ahb-fare-breakdown-toggle.is-open {
                transform: rotate(180deg);
            }

            .ahb-fare-breakdown {
                background: #f8fdf9;
                border-radius: 6px;
                padding: 8px 12px;
                margin: -4px 0 6px;
                border: 1px solid #e8f5ee;
                display: none;
            }

            .ahb-fare-breakdown-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 4px 0;
                font-size: 13px;
                color: #555;
                border-bottom: 1px dashed #e8f5ee;
            }

            .ahb-fare-breakdown-row:last-child {
                border-bottom: none;
            }

            .ahb-fare-breakdown-row--group {
                font-weight: 600;
                color: #333;
                margin-top: 6px;
                padding-top: 6px;
                border-top: 1px solid #d4edda;
            }

            .ahb-fare-breakdown-row--group:first-child {
                margin-top: 0;
                padding-top: 0;
                border-top: none;
            }

            .ahb-fare-breakdown-row--group em {
                font-weight: 400;
                font-style: normal;
                font-size: 11px;
                color: #777;
                margin-left: 4px;
            }

            .ahb-fare-breakdown-row--individual {
                padding-left: 14px;
                font-size: 12px;
                color: #666;
                border-bottom: 1px dashed #edf7f0;
            }

            .ahb-fare-breakdown-row--individual:last-child {
                border-bottom: none;
            }

            /* Why book with us */
            .ahb-why-card {
                background: #0e7d3f;
                border-radius: 16px;
                padding: 20px;
                color: #fff;
                margin-bottom: 16px;
            }

            .ahb-why-title {
                font-size: 18px;
                font-weight: 700;
                margin: 0 0 14px;
            }

            .ahb-why-item {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 13px;
                font-weight: 600;
                margin-bottom: 10px;
                opacity: .95;
            }

            .ahb-why-icon {
                width: 24px;
                height: 24px;
                background: rgba(255, 255, 255, .2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 12px;
            }

            .ahb-call-box {
                display: flex;
                align-items: center;
                gap: 10px;
                background: rgba(255, 255, 255, .15);
                border-radius: 10px;
                padding: 12px;
                margin-top: 14px;
            }

            .ahb-call-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .3);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                flex-shrink: 0;
            }

            .ahb-call-label {
                font-size: 10px;
                opacity: .8;
            }

            .ahb-call-number {
                font-size: 14px;
                font-weight: 800;
            }

            .ahb-loading {
                text-align: center;
                padding: 60px 20px;
            }

            .ahb-spinner {
                width: 36px;
                height: 36px;
                border: 3px solid #e2e8f0;
                border-top-color: #0e7d3f;
                border-radius: 50%;
                animation: ahbSpin .8s linear infinite;
                margin: 0 auto 12px;
            }

            @keyframes ahbSpin {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>

        <div class="ahb-wrap">
            <button class="ahb-back" onclick="history.back()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 12H5M12 5l-7 7 7 7" />
                </svg>
                Back to Hotels Detail
            </button>

            <div id="ahb-main">
                <div class="ahb-loading">
                    <div class="ahb-spinner"></div>
                    <p style="color:#64748b;font-size:14px;">Loading booking details...</p>
                </div>
            </div>
        </div>

        <script>
            (function() {
                var hotelData = null;
                var roomData = null;
                try {
                    var raw = sessionStorage.getItem('amadex_hotel_detail');
                    if (raw) hotelData = JSON.parse(raw);
                    var rawRoom = sessionStorage.getItem('amadex_booking_room');
                    if (rawRoom) roomData = JSON.parse(rawRoom);
                } catch (e) {}

                if (!hotelData) {
                    document.getElementById('ahb-main').innerHTML = '<div style="text-align:center;padding:60px 20px;"><h3>No booking data found</h3><p>Please go back and select a room.</p></div>';
                    return;
                }

                var searchData = hotelData.searchData || {};
                var nights = 1;
                if (searchData.checkIn && searchData.checkOut) {
                    var ci = new Date(searchData.checkIn);
                    var co = new Date(searchData.checkOut);
                    nights = Math.max(1, Math.round((co - ci) / 86400000));
                }
                var rooms = (searchData.roomData && searchData.roomData.length) ? searchData.roomData : [{
                    adults: 1,
                    children: 0
                }];
                var roomCount = rooms.length;

                var roomName = roomData ? (roomData.room_name || 'Selected Room') : 'Selected Room';
                var price = roomData ? parseFloat(roomData.price_raw || 0) : parseFloat(hotelData.price_raw || 0);
                var taxes = roomData ? parseFloat(roomData.taxes || 0) : 54;
                var cancel = roomData ? roomData.cancellable : hotelData.cancellable;
                var baseTotal = price * nights * roomCount;
                var taxTotal = (taxes || 54) * nights * roomCount;
                var grandTotal = baseTotal + taxTotal;

                var photo = (window._ahdCurrentPhotos && window._ahdCurrentPhotos[0]) || (hotelData.images && hotelData.images[0]) || '';

                function fmtDateShort(d) {
                    if (!d) return '';
                    var dt = new Date(d);
                    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    return dt.getDate() + ' ' + months[dt.getMonth()] + ',\'' + String(dt.getFullYear()).slice(2) + ' ' + days[dt.getDay()];
                }
                // Format dates
                function fmtDate(d) {
                    if (!d) return '';
                    var dt = new Date(d);
                    return dt.toLocaleDateString('en-US', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                    });
                }

                function fmtDay(d) {
                    if (!d) return '';
                    var dt = new Date(d);
                    return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][dt.getDay()];
                }

                // Build room guest forms
                var roomForms = rooms.map(function(r, i) {
                    return '<div class="ahb-room-block">' +
                        '<div class="ahb-room-block-header">' +
                        '<div class="ahb-room-block-title">' +
                        '<div class="ahb-room-icon"><i class="fa-solid fa-door-closed" style="color: rgb(0, 0, 0);"></i></div>' +
                        'Room ' + (i + 1) +
                        '</div>' +
                        '<button class="ahb-add-guest">Add New Guest +</button>' +
                        '</div>' +
                        '<div class="ahb-name-row">' +
                        '<div class="ahb-field-group">' +
                        '<label class="ahb-field-label">First Name*</label>' +
                        '<input class="ahb-input" type="text" placeholder="First Name" id="ahb-fn-' + i + '">' +
                        '</div>' +
                        '<div class="ahb-field-group">' +
                        '<label class="ahb-field-label">Last Name*</label>' +
                        '<input class="ahb-input" type="text" placeholder="Last Name" id="ahb-ln-' + i + '">' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                }).join('');

                // Build photos slider
                var photos = window._ahdCurrentPhotos || hotelData.images || [];
                var currentPhoto = 0;

                function getSliderHtml() {
                    return (photos.length ?
                            '<img id="ahb-slide-img" src="' + photos[0] + '" alt="Hotel" style="width:100%;height:100%;object-fit:cover;display:block;">' :
                            '<div style="width:100%;height:100%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:40px;opacity:.2;">🏨</div>') +
                        (photos.length > 1 ?
                            '<button onclick="ahbSlide(-1)" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.9);border:none;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.15);">‹</button>' +
                            '<button onclick="ahbSlide(1)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.9);border:none;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.15);">›</button>' +
                            '<div style="position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:5px;">' +
                            photos.map(function(_, i) {
                                return '<div style="width:' + (i === 0 ? '20' : '8') + 'px;height:8px;border-radius:4px;background:' + (i === 0 ? '#fff' : 'rgba(255,255,255,.5)') + ';transition:all .2s;" id="ahb-dot-' + i + '"></div>';
                            }).join('') +
                            '</div>' : '');
                }

                // Build per-room selected rooms
                var selectedRoomsHtml = rooms.map(function(r, i) {
                    return '<div style="' + (i > 0 ? 'border-top:1px solid #f1f5f9;padding-top:12px;margin-top:12px;' : '') + '">' +
                        '<div style="font-size:14px;color:#475569;margin-bottom:6px;">Room:' + (i + 1) + ' <strong style="color:#0f172a;">' + roomName + '</strong></div>' +
                        '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
                        '<span class="ahb-room-tag"><span style="font-size:14px;"><i class="fa-solid fa-bowl-food" style="color: rgb(0, 0, 0);"></i></span> Free Breakfast</span>' +
                        '<span class="ahb-room-tag"><span style="font-size:14px;"><i class="fa-solid fa-dumbbell" style="color: rgb(0, 0, 0);"></i></span> Fitness centre</span>' +
                        '<span class="ahb-room-tag"><span style="font-size:14px;"><i class="fa-solid fa-person-swimming" style="color: rgb(0, 0, 0);"></i></span> Pool</span>' +
                        '</div>' +
                        '</div>';
                }).join('');

                // Total guests
                var totalGuests = rooms.reduce(function(s, r) {
                    return s + r.adults + r.children;
                }, 0);

                document.getElementById('ahb-main').innerHTML =
                    '<div class="ahb-layout">' +
                    '<div>' +

                    // Hotel summary card — reference layout
                    '<div class="ahb-card">' +
                    // Top row: name + refundable
                    '<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">' +
                    '<div>' +
                    '<div class="ahb-hotel-name">' + (hotelData.name || 'Hotel') + '</div>' +
                    '<div class="ahb-hotel-addr">' +
                    '<svg width="12" height="12" viewBox="0 0 640 640" style="fill:#64748b;flex-shrink:0;"><path d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>' +
                    (hotelData.address || '') +
                    '</div>' +
                    '</div>' +
                    (cancel ? '<span class="ahb-refundable" style="flex-shrink:0;">✓ Refundable</span>' : '') +
                    '</div>' +

                    // Image + rooms side by side
                    '<div style="display:grid;grid-template-columns:220px 1fr;gap:16px;align-items:end;margin-top:12px;background:#F6F6F6;padding: 10px;border-radius: 15px;">' +
                    // Slider
                    '<div style="position:relative;height:200px;border-radius:12px;overflow:hidden;background:#f1f5f9;" id="ahb-slider">' +
                    getSliderHtml() +
                    '</div>' +
                    // Selected rooms + dates
                    '<div>' +
                    '<div style="font-size:16px;font-weight:700;color:#475569;margin-bottom:10px;">Selected Rooms</div>' +
                    selectedRoomsHtml +
                    // Dates row with dividers
                    '<div style="display:grid;grid-template-columns:1fr 1px 1fr 1px 1fr;gap:0;margin-top:14px;background:#ffffff;border-radius:10px;overflow:hidden;border: 1px solid #E6E6E6;">' +
                    '<div style="padding:10px 12px;">' +
                    '<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Check-in</div>' +
                    '<div style="font-size:13px;font-weight:700;color:#0f172a;">' + fmtDateShort(searchData.checkIn) + '</div>' +
                    '</div>' +
                    '<div style="background:#e2e8f0;"></div>' +
                    '<div style="padding:10px 12px;">' +
                    '<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Check Out</div>' +
                    '<div style="font-size:13px;font-weight:700;color:#0f172a;">' + fmtDateShort(searchData.checkOut) + '</div>' +
                    '</div>' +
                    '<div style="background:#e2e8f0;"></div>' +
                    '<div style="padding:10px 12px;">' +
                    '<div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">' + nights + ' Night' + (nights > 1 ? 's' : '') + '</div>' +
                    '<div style="font-size:13px;font-weight:700;color:#0f172a;">' + totalGuests + ' guest' + (totalGuests > 1 ? 's' : '') + ', ' + roomCount + ' room' + (roomCount > 1 ? 's' : '') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Who's staying
                    '<div class="ahb-card">' +
                    '<div class="ahb-card-title">Who\'s Staying?</div>' +
                    '<p style="font-size:13px;color:#64748b;margin:0 0 14px;">Guest names must match valid ID which will be used at check-in</p>' +
                    roomForms +
                    '</div>' +

                    // Special requests
                    '<div class="ahb-card">' +
                    '<div class="ahb-card-title">Special Requests</div>' +
                    '<p class="ahb-requests-note">The property will do its best, but cannot guarantee to fulfil all requests.</p>' +
                    '<textarea class="ahb-textarea" id="ahb-requests" placeholder="Additional requests..."></textarea>' +
                    '</div>' +

                    // Contact info
                    '<div class="ahb-card">' +
                    '<div class="ahb-card-title">Contact Info</div>' +
                    '<p class="ahb-contact-note">Booking details will be sent to</p>' +
                    '<div class="ahb-required-badge">Required Field</div>' +
                    '<div class="ahb-contact-grid">' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Country Code*</label>' +
                    '<select class="ahb-select" id="ahb-country-code">' +
                    '<option value="+1">United States (+1)</option>' +
                    '<option value="+44">United Kingdom (+44)</option>' +
                    '<option value="+91">India (+91)</option>' +
                    '<option value="+971">UAE (+971)</option>' +
                    '<option value="+61">Australia (+61)</option>' +
                    '<option value="+1-CA">Canada (+1)</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Mobile Number*</label>' +
                    '<input class="ahb-input" type="tel" id="ahb-phone" placeholder="Mobile Number">' +
                    '</div>' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Email*</label>' +
                    '<input class="ahb-input" type="email" id="ahb-email" placeholder="Example@Gmail.com">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    // Payment
                    '<div class="ahb-card">' +
                    '<div class="ahb-card-title">Payment method</div>' +
                    '<p style="font-size:12px;color:#94a3b8;margin:0 0 12px;">Safe and Secure Payment</p>' +
                    // Payment tabs
                    '<div class="ahb-payment-tabs">' +
                    '<button class="ahb-payment-tab active" data-method="credit_card" onclick="ahbSelectTab(this,\'card\')">' +
                    '<div class="ahb-payment-tab-icons">' +
                    '<img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Visa_Brandmark_Blue_RGB_2021.png" alt="Visa">' +
                    '<img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-3852.png" alt="MC">' +
                    '<img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/layer1.png" alt="Amex">' +
                    '<img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-3855.png">' +
                    '</div>' +
                    '<span class="ahb-payment-tab-label">Credit/Debit Card</span>' +
                    '</button>' +
                    '<button class="ahb-payment-tab" data-method="paypal" onclick="ahbSelectTab(this,\'paypal\')">' +
                    '<div class="ahb-payment-tab-icons">' +
                    '<img src="/wp-content/plugins/amadex1/assets/images/paypal-icon.png" alt="PayPal">' +
                    '</div>' +
                    '<span class="ahb-payment-tab-label">PayPal</span>' +
                    '</button>' +
                    '<button class="ahb-payment-tab" data-method="crypto_com" onclick="ahbSelectTab(this,\'crypto\')">' +
                    '<div class="ahb-payment-tab-icons">' +
                    '<img src="/wp-content/plugins/amadex1/assets/images/Bitcoin-Logo.png" alt="Crypto">' +
                    '</div>' +
                    '<span class="ahb-payment-tab-label">Crypto.com</span>' +
                    '</button>' +
                    '<button class="ahb-payment-tab" data-method="moonpay_onramp" onclick="ahbSelectTab(this,\'moonpay\')">' +
                    '<div class="ahb-payment-tab-icons" style="font-size:18px;"><img src="/wp-content/plugins/amadex1/assets/images/Moonpay%20Logo.png" alt="Crypto"></div>' +
                    '<span class="ahb-payment-tab-label">Pay with Card</span>' +
                    '</button>' +
                    '</div>' +
                    // Credit card panel
                    '<div class="ahb-payment-panel active" id="ahb-panel-card">' +
                    '<div class="ahb-payment-grid" style="margin-bottom:12px;">' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Card Holder Name *</label>' +
                    '<input class="ahb-input" type="text" id="ahb-card-name" placeholder="John Smith">' +
                    '</div>' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Credit/Debit Card Number *</label>' +
                    '<div class="ahb-input" id="ahb-card-num" style="padding:0;min-height:44px;"></div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="ahb-payment-grid" style="margin-bottom:12px;">' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">Expiry Date *</label>' +
                    '<div class="ahb-input" id="ahb-card-exp-collect" style="padding:0;min-height:44px;"></div>' +
                    '</div>' +
                    '<div class="ahb-field-group">' +
                    '<label class="ahb-field-label">CVV *</label>' +
                    '<div class="ahb-input" id="ahb-cvv" style="padding:0;min-height:44px;"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    // PayPal panel
                    '<div class="ahb-payment-panel" id="ahb-panel-paypal">' +
                    '<div style="text-align:center;padding:20px;">' +
                    '<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/200px-PayPal.svg.png" style="height:40px;margin-bottom:12px;">' +
                    '<p style="font-size:14px;color:#64748b;">You will be redirected to PayPal to complete payment.</p>' +
                    '</div>' +
                    '</div>' +
                    // Crypto panel
                    '<div class="ahb-payment-panel" id="ahb-panel-crypto">' +
                    '<div style="text-align:center;padding:20px;">' +
                    '<div style="font-size:40px;margin-bottom:12px;">₿</div>' +
                    '<p style="font-size:14px;color:#64748b;">Pay securely with cryptocurrency via Crypto.com.</p>' +
                    '</div>' +
                    '</div>' +
                    // MoonPay panel
                    '<div class="ahb-payment-panel" id="ahb-panel-moonpay">' +
                    '<div style="text-align:center;padding:20px;">' +
                    '<div style="font-size:40px;margin-bottom:12px;">🌙</div>' +
                    '<p style="font-size:14px;color:#64748b;">Pay with card or crypto via MoonPay.</p>' +
                    '</div>' +
                    '</div>' +
                    '<div class="ahb-checkbox-row">' +
                    '<input type="checkbox" id="ahb-agree">' +
                    '<label for="ahb-agree" class="ahb-checkbox-text">I agree to receive updates and promotions about Travelay and its affiliates or business partners via various channels, including WhatsApp. Opt out anytime. Read more in the <a href="#">Privacy Policy</a>.</label>' +
                    '</div>' +
                    '<button class="ahb-confirm-btn" onclick="ahbConfirm()">' +
                    'Confirm & Book' +
                    '<span class="ahb-timer" id="ahb-timer">⏱ Time Left: 10:00</span>' +
                    '</button>' +
                    '<div class="ahb-email-note">✉ We\'ll send confirmation of your booking to <span id="ahb-email-display" style="font-weight:700;color:#0f172a;">your email</span></div>' +
                    '</div>' +

                    '</div>' +

                    // ── RIGHT SIDEBAR ──
                    '<div class="ahb-sidebar">' +
                    '<div class="ahb-price-card">' +
                    '<div class="ahb-price-title">Price Summary</div>' +
                    '<div class="ahb-price-row ahb-price-row--expandable">' +
                    '<span class="ahb-price-row-label">Base Fare<br><small style="color:#94a3b8;font-weight:400;">' + roomCount + ' Room' + (roomCount > 1 ? 's' : '') + ' × ' + nights + ' Night' + (nights > 1 ? 's' : '') + '</small></span>' +
                    '<div class="ahb-price-row-right">' +
                    '<span class="ahb-price-row-val">$' + baseTotal.toFixed(2) + '</span>' +
                    '<button type="button" class="ahb-fare-breakdown-toggle" aria-label="Show breakdown">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    (function() {
                        var bd = '';
                        var pricePerRoom = price;
                        for (var r = 1; r <= roomCount; r++) {
                            bd += '<div class="ahb-fare-breakdown-row ahb-fare-breakdown-row--group">' +
                                '<span>Room ' + r + ' <em>($' + pricePerRoom.toFixed(2) + ' × ' + nights + ' night' + (nights > 1 ? 's' : '') + ')</em></span>' +
                                '<span>$' + (pricePerRoom * nights).toFixed(2) + '</span>' +
                                '</div>';
                            for (var n = 1; n <= nights; n++) {
                                bd += '<div class="ahb-fare-breakdown-row ahb-fare-breakdown-row--individual">' +
                                    '<span>Night ' + n + '</span>' +
                                    '<span>$' + pricePerRoom.toFixed(2) + '</span>' +
                                    '</div>';
                            }
                        }
                        return '<div class="ahb-fare-breakdown">' + bd + '</div>';
                    })() +
                    '<div class="ahb-price-row">' +
                    '<span class="ahb-price-row-label">Taxes<br><small style="color:#94a3b8;font-weight:400;">Hotel Only</small></span>' +
                    '<span class="ahb-price-row-val">$' + taxTotal.toFixed(2) + '</span>' +
                    '</div>' +
                    '<div class="ahb-price-total">' +
                    '<span>Total Amount</span>' +
                    '<span>$' + grandTotal.toFixed(2) + '</span>' +
                    '</div>' +
                    '</div>' +

                    '<div class="ahb-why-card">' +
                    '<div class="ahb-why-title">Why Book with us</div>' +
                    '<div class="ahb-why-item"><div class="ahb-why-icon">✓</div>24 × 7 Assistance</div>' +
                    '<div class="ahb-why-item"><div class="ahb-why-icon">✓</div>Verified and Trusted Listings</div>' +
                    '<div class="ahb-why-item"><div class="ahb-why-icon">✓</div>Seamless Booking Process</div>' +
                    '<div class="ahb-why-item"><div class="ahb-why-icon">✓</div>Best Rate Guarantee</div>' +
                    '<div class="ahb-call-box">' +
                    '<div class="ahb-call-avatar">👤</div>' +
                    '<div>' +
                    '<div class="ahb-call-label">Call us</div>' +
                    '<div class="ahb-call-number">+1-877-721-0410</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +

                    '</div>' +
                    '</div>';

                // ── Timer ──
                var timeLeft = 600;
                var timerEl = document.getElementById('ahb-timer');
                var timerInterval = setInterval(function() {
                    timeLeft--;
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        if (timerEl) timerEl.textContent = '⏱ Expired';
                        return;
                    }
                    var m = Math.floor(timeLeft / 60);
                    var s = timeLeft % 60;
                    if (timerEl) timerEl.textContent = '⏱ Time Left: ' + m + ':' + (s < 10 ? '0' : '') + s;
                }, 1000);

                // Update email display
                document.addEventListener('input', function(e) {
                    if (e.target.id === 'ahb-email') {
                        var el = document.getElementById('ahb-email-display');
                        if (el) el.textContent = e.target.value || 'your email';
                    }
                });
                window.ahbSlide = function(dir) {
                    if (!photos.length) return;
                    currentPhoto = (currentPhoto + dir + photos.length) % photos.length;
                    var img = document.getElementById('ahb-slide-img');
                    if (img) {
                        img.style.opacity = '0';
                        setTimeout(function() {
                            img.src = photos[currentPhoto];
                            img.style.opacity = '1';
                        }, 150);
                    }
                    photos.forEach(function(_, i) {
                        var dot = document.getElementById('ahb-dot-' + i);
                        if (dot) {
                            dot.style.width = i === currentPhoto ? '20px' : '8px';
                            dot.style.background = i === currentPhoto ? '#fff' : 'rgba(255,255,255,.5)';
                        }
                    });
                };
                var _ahbActiveMethod = 'credit_card';

                var AHB_AJAX = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var AHB_NONCE = <?php echo json_encode(wp_create_nonce('amadex_nonce')); ?>;
                <?php
                $ps = get_option('amadex_payment_settings', []);
                $ahb_tok_key = isset($ps['nmi_tokenization_key']) ? trim($ps['nmi_tokenization_key']) : '';
                $ahb_gateway = isset($ps['default_card_gateway']) ? strtolower(trim($ps['default_card_gateway'])) : 'nmi';
                $ahb_bypass  = !empty($ps['nmi_bypass_for_testing']);
                ?>
                var AHB_GATEWAY = <?php echo json_encode($ahb_gateway); ?>;
                var AHB_TOK_KEY = <?php echo json_encode($ahb_tok_key); ?>;
                var AHB_BYPASS = <?php echo json_encode($ahb_bypass); ?>;
                var AHB_COLLECT_URL = 'https://secure.nmi.com/token/Collect.js';
                var _ahbPayToken = ''; // set after Collect.js tokenizes
                var _ahbCollectReady = false;

                // Load NMI Collect.js for card tokenization
                function ahbLoadCollectJs() {
                    if (!AHB_TOK_KEY || AHB_GATEWAY !== 'nmi' || document.querySelector('script[src*="Collect.js"]')) return;
                    var s = document.createElement('script');
                    s.src = AHB_COLLECT_URL;
                    s.setAttribute('data-tokenization-key', AHB_TOK_KEY);
                    s.onload = function() {
                        _ahbCollectReady = true;
                        if (window.CollectJS) {
                            CollectJS.configure({
                                variant: 'inline',
                                styleSniffer: false,
                                googleFont: '',
                                validationCallback: function() {},
                                fieldsAvailableCallback: function() {},
                                timeoutDuration: 10000,
                                timeoutCallback: function() {},
                                callback: function(response) {
                                    _ahbPayToken = response.token;
                                    ahbSubmitWithToken(_ahbPayToken);
                                },
                                fields: {
                                    ccnumber: {
                                        selector: '#ahb-card-num',
                                        placeholder: '0000 0000 0000 0000'
                                    },
                                    ccexp: {
                                        selector: '#ahb-card-exp-collect',
                                        placeholder: 'MM / YY'
                                    },
                                    cvv: {
                                        selector: '#ahb-cvv',
                                        placeholder: '***'
                                    }
                                }
                            });
                        }
                    };
                    document.head.appendChild(s);
                }
                ahbLoadCollectJs();

                window.ahbSelectTab = function(btn, panel) {
                    document.querySelectorAll('.ahb-payment-tab').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    document.querySelectorAll('.ahb-payment-panel').forEach(function(p) {
                        p.classList.remove('active');
                    });
                    btn.classList.add('active');
                    _ahbActiveMethod = btn.getAttribute('data-method') || 'credit_card';
                    var el = document.getElementById('ahb-panel-' + panel);
                    if (el) el.classList.add('active');
                };

                window.ahbFormatCard = function(input) {
                    var v = input.value.replace(/\D/g, '').substring(0, 16);
                    input.value = v.replace(/(.{4})/g, '$1 ').trim();
                };

                // Hotel price breakdown toggle
                document.addEventListener('click', function(e) {
                    var btn = e.target.closest('.ahb-fare-breakdown-toggle');
                    if (!btn) return;
                    var row = btn.closest('.ahb-price-row--expandable');
                    var bd = row && row.nextElementSibling;
                    if (!bd || !bd.classList.contains('ahb-fare-breakdown')) return;
                    var open = bd.style.display === 'block';
                    if (open) {
                        bd.style.display = 'none';
                        btn.classList.remove('is-open');
                    } else {
                        bd.style.display = 'block';
                        btn.classList.add('is-open');
                    }
                });

                // Called after Collect.js returns a token
                function ahbSubmitWithToken(token) {
                    var btn = document.querySelector('.ahb-confirm-btn');
                    var email = document.getElementById('ahb-email') ? document.getElementById('ahb-email').value.trim() : '';
                    var phone = document.getElementById('ahb-phone') ? document.getElementById('ahb-phone').value.trim() : '';
                    var fn0   = document.getElementById('ahb-fn-0')  ? document.getElementById('ahb-fn-0').value.trim()  : '';

                    var guests = [];
                    for (var r = 0; r < roomCount; r++) {
                        var fnEl = document.getElementById('ahb-fn-' + r);
                        var lnEl = document.getElementById('ahb-ln-' + r);
                        guests.push({ room: r + 1, first_name: fnEl ? fnEl.value : '', last_name: lnEl ? lnEl.value : '' });
                    }

                    var srEl = document.getElementById('ahb-special-request');
                    var specialRequest = srEl ? srEl.value : '';

                    var activeTabEl = document.querySelector('.ahb-payment-tab.active');
                    var paymentMethod = activeTabEl ? (activeTabEl.getAttribute('data-method') || 'credit_card') : _ahbActiveMethod;
                    var methodLabels = { 'credit_card': 'Credit/Debit Card', 'paypal': 'PayPal', 'crypto_com': 'Crypto.com', 'moonpay_onramp': 'Pay with Card (MoonPay)' };
                    var methodLabel = methodLabels[paymentMethod] || paymentMethod;

                    var cardName = document.getElementById('ahb-card-name') ? document.getElementById('ahb-card-name').value.trim() : '';

                    var payload = {
                        contact: { name: fn0, email: email, phone: phone },
                        hotel: {
                            name:        hotelData.name        || '',
                            destination: hotelData.address     || (hotelData.searchData && hotelData.searchData.destination) || '',
                            check_in:    searchData.checkIn    || '',
                            check_out:   searchData.checkOut   || '',
                            rooms:       roomCount,
                            guests:      (function() { var t = 0; rooms.forEach(function(rm){ t += (rm.adults||1) + (rm.children||0); }); return t; })(),
                            room_name:   roomName,
                            base_fare:   baseTotal,
                            tax:         taxTotal,
                            total:       grandTotal,
                            nights:      nights,
                            room_guests: guests
                        },
                        special_request: specialRequest,
                        payment: {
                            method:        methodLabel,
                            method_key:    paymentMethod,
                            payment_token: token || '',
                            card_holder:   cardName,
                            card_last4:    '',
                            card_number:   '',
                            card_exp_month: '',
                            card_exp_year:  '',
                            card_cvv:      ''
                        }
                    };

                    if (btn) { btn.disabled = true; btn.textContent = 'Processing Payment…'; }

                    var body = new URLSearchParams({
                        action:     'amadex_save_hotel_lead',
                        nonce:      AHB_NONCE,
                        hotel_data: JSON.stringify(payload)
                    });

                    fetch(AHB_AJAX, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (btn) { btn.disabled = false; btn.textContent = 'Confirm & Book'; }
                            if (data && data.success && data.data && data.data.reference) {
                                var confirmUrl = <?php echo json_encode(home_url('/booking-confirmation/')); ?>;
                                window.location.href = confirmUrl + '?reference=' + encodeURIComponent(data.data.reference);
                            } else {
                                var msg = (data && data.data && data.data.message) ? data.data.message : 'Payment failed. Please check your card details.';
                                alert(msg);
                            }
                        })
                        .catch(function() {
                            if (btn) { btn.disabled = false; btn.textContent = 'Confirm & Book'; }
                            alert('A network error occurred. Please try again.');
                        });
                }

               window.ahbConfirm = function() {
                    var email = document.getElementById('ahb-email') ? document.getElementById('ahb-email').value.trim() : '';
                    var phone = document.getElementById('ahb-phone') ? document.getElementById('ahb-phone').value.trim() : '';
                    var fn0   = document.getElementById('ahb-fn-0')  ? document.getElementById('ahb-fn-0').value.trim()  : '';
                    if (!fn0)   { alert('Please enter guest name for Room 1'); return; }
                    if (!email) { alert('Please enter your email'); return; }
                    if (!phone) { alert('Please enter your mobile number'); return; }

                    var activeTabEl = document.querySelector('.ahb-payment-tab.active');
                    var paymentMethod = activeTabEl ? (activeTabEl.getAttribute('data-method') || 'credit_card') : _ahbActiveMethod;

                    // For credit_card: tokenize via Collect.js first, then submit
                    if (paymentMethod === 'credit_card' && AHB_GATEWAY === 'nmi' && !AHB_BYPASS) {
                        var btn = document.querySelector('.ahb-confirm-btn');
                        if (btn) { btn.disabled = true; btn.textContent = 'Securing Card…'; }
                        if (window.CollectJS) {
                            CollectJS.startPaymentRequest();
                        } else {
                            if (btn) { btn.disabled = false; btn.textContent = 'Confirm & Book'; }
                            alert('Payment system not ready. Please refresh and try again.');
                        }
                        return;
                    }

                    // For all other methods (PayPal, Crypto, MoonPay) or bypass mode — submit directly without token
                    ahbSubmitWithToken('');
                };

            })();
        </script>
<?php
        return ob_get_clean();
    }
}
new Amadex_Hotel_Booking();