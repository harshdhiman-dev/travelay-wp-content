<?php

/**
 * Amadex Tabbed Search Widget
 * Shortcode: [amadex_tabbed_search]
 * Shows Flight | Hotels | Cars | Cruise tabs
 * Flight tab wraps existing [amadex_flight_search]
 * Hotels tab has full date picker + rooms/guests
 * Cars & Cruise show "Coming Soon"
 */

if (!defined('ABSPATH')) exit;

class Amadex_Tabbed_Search
{
    public function __construct()
    {
        add_shortcode('amadex_tabbed_search', array($this, 'render'));
        add_action('wp_ajax_amadex_hotel_search',        array($this, 'hotel_search'));
        add_action('wp_ajax_nopriv_amadex_hotel_search', array($this, 'hotel_search'));
        add_action('wp_ajax_amadex_hotel_autocomplete',        array($this, 'hotel_autocomplete'));
        add_action('wp_ajax_nopriv_amadex_hotel_autocomplete', array($this, 'hotel_autocomplete'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'results_page'  => site_url('/flight-results/'),
            'hotel_results' => site_url('/hotel-results/'),
            'default_tab'   => 'flight',
        ), $atts, 'amadex_tabbed_search');

        $uid = 'ats-' . uniqid();
        ob_start();
?>
        <style>
            #<?php echo esc_attr($uid); ?> {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                width: 100%;
                max-width: 920px;
                margin: 0 auto;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            #<?php echo esc_attr($uid); ?>.ats-ready {
                opacity: 1 !important;
            }

            div.room-selection {
                border-top-right-radius: 5px;
                border-bottom-right-radius: 5px;
            }

            .ats-tabs i {
                color: #000000 !important;
                font-size: 14px;
            }

            .ats-tab.active i {
                color: #ffffff !important;
                font-size: 14px;
            }

            .vsb-wrap,
            .vsb-wrap *,
            .amadex-modern-form,
            .amadex-modern-form * {
                animation: none !important;
                transition: none !important;
            }

            div.ats-ready {
                max-width: 1200px !important;
            }

            .ats-tabs-main {
                display: flex;
                justify-content: center;
            }

            .vsb-wrap {
                animation: none !important;
                transition: none !important;
                transform: none !important;
            }

            .visit-main-nav {
                width: 100%;
            }

            /* ── Tab pills ──────────────────────────────── */
            .ats-tabs {
                display: flex !important;
                gap: 6px !important;
                flex-wrap: wrap !important;
                justify-content: center;
                background: #fff;
                padding: 10px;
                position: relative;
                bottom: -24px;
                border-top-right-radius: 12px;
                border-top-left-radius: 12px;
                padding-bottom: 0 !important;
                z-index: 9999;
            }

            .ats-tab {
                display: flex !important;
                align-items: center !important;
                gap: 7px !important;
                padding: 7px 15px !important;
                border-radius: 100px !important;
                border: 1.5px solid #e2e8f0 !important;
                background: #F3F3F3 !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #000000 !important;
                cursor: pointer !important;
                transition: all .15s !important;
                font-family: inherit !important;
            }

            div.destination-stay {
                min-width: 45% !important;
                border-top-left-radius: 5px;
                border-bottom-left-radius: 5px;
            }

            .ats-tab:hover {
                background: #0e7d3f1a !important;
            }

            .ats-tab.active {
                background: #0e7d3f !important;
                border-color: #0e7d3f !important;
                color: #fff !important;
            }

            .ats-tab svg {
                width: 16px !important;
                height: 16px !important;
                flex-shrink: 0 !important;
            }

            /* .ats-box {
            background: #fff !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 32px rgba(0,0,0,.10) !important;
        } */
            .vsb-card {
                border-top-right-radius: 0;
            }

            /* ── Tab panels ─────────────────────────────── */
            .ats-panel {
                display: none !important;
            }

            .ats-panel.active {
                display: block !important;
            }

            /* ── Hotel form ─────────────────────────────── */
            .ats-hotel-form {
                display: flex !important;
                gap: 0 !important;
                background: #fff;
                align-items: stretch !important;
                border-radius: 12px !important;
                padding: 10px;
                overflow: visible !important;
                position: relative !important;
                padding-top: 32px;
            }

            .ats-field {
                flex: 1 !important;
                padding: 12px 16px !important;
                border: 1.5px solid #e2e8f0 !important;
                cursor: pointer !important;
                position: relative !important;
                min-width: 0 !important;
                transition: background .12s !important;
            }

            .ats-field:hover {
                background: #f8fafc !important;
            }

            .ats-field-label {
                font-size: 11px !important;
                font-weight: 700 !important;
                color: #0e7d3f !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                display: block !important;
                margin-bottom: 4px !important;
            }

            .ats-field-value {
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                display: block !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .ats-field-sub {
                font-size: 12px !important;
                color: #94a3b8 !important;
                display: block !important;
                margin-top: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .ats-dest-input {
                border: none !important;
                outline: none !important;
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                width: 100% !important;
                font-family: inherit !important;
                background: transparent !important;
                padding: 0 !important;
            }

            .ats-dest-input::placeholder {
                color: #94a3b8 !important;
                font-weight: 500 !important;
            }

            /* Search button */
            .ats-search-btn {
                padding: 0 28px !important;
                background: #0e7d3f !important;
                color: #fff !important;
                border: none !important;
                border-radius: 10px !important;
                font-size: 15px !important;
                font-weight: 700 !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                font-family: inherit !important;
                transition: background .15s !important;
                white-space: nowrap !important;
                flex-shrink: 0 !important;
                margin-left: 8px;
            }

            .ats-search-btn:hover {
                background: #0a6232 !important;
            }

            /* ── Autocomplete dropdown ──────────────────── */
            .ats-autocomplete {
                position: absolute !important;
                top: calc(100% + 6px) !important;
                left: -17px !important;
                right: 0 !important;
                background: #fff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 12px !important;
                box-shadow: 0 8px 32px rgba(0, 0, 0, .15) !important;
                z-index: 99999 !important;
                max-height: 320px !important;
                overflow-y: auto !important;
                display: none !important;
                min-width: 320px !important;
            }

            .ats-autocomplete.open {
                display: block !important;
            }

            .ats-autocomplete-item {
                padding: 10px 14px !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
                transition: background .1s !important;
                border-bottom: 1px solid #f8fafc !important;
            }

            .ats-autocomplete-item:last-child {
                border-bottom: none !important;
            }

            .ats-autocomplete-item:hover {
                background: #f0fdf4 !important;
            }

            .ats-autocomplete-name {
                font-size: 14px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .ats-autocomplete-sub {
                font-size: 12px !important;
                color: #64748b !important;
                margin-top: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            /* ── Date Picker ─────────────────────────────── */
            .ats-datepicker-wrap {
                position: absolute !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                background: #fff !important;
                border-radius: 16px !important;
                box-shadow: 0 8px 40px rgba(0, 0, 0, .15) !important;
                border: 1.5px solid #e2e8f0 !important;
                padding: 20px !important;
                z-index: 9999 !important;
                display: none !important;
                min-width: 620px !important;
            }

            .ats-datepicker-wrap.open {
                display: flex !important;
                gap: 24px !important;
            }

            .ats-cal {
                flex: 1 !important;
            }

            .ats-cal-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-bottom: 14px !important;
            }

            .ats-cal-title {
                font-size: 15px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
            }

            .ats-cal-nav {
                width: 28px !important;
                height: 28px !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 50% !important;
                background: #fff !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 14px !important;
                color: #475569 !important;
                transition: all .12s !important;
            }

            .ats-cal-nav:hover {
                border-color: #0e7d3f !important;
                color: #0e7d3f !important;
            }

            .ats-cal-grid {
                display: grid !important;
                grid-template-columns: repeat(7, 1fr) !important;
                gap: 2px !important;
            }

            .ats-cal-day-name {
                text-align: center !important;
                font-size: 11px !important;
                font-weight: 700 !important;
                color: #94a3b8 !important;
                padding: 4px 0 !important;
                text-transform: uppercase !important;
            }

            .ats-cal-day {
                text-align: center !important;
                padding: 7px 4px !important;
                font-size: 13px !important;
                border-radius: 50% !important;
                cursor: pointer !important;
                color: #334155 !important;
                transition: all .12s !important;
                font-weight: 500 !important;
            }

            .ats-cal-day:hover:not(.empty):not(.past) {
                background: #f0fdf4 !important;
                color: #0e7d3f !important;
            }

            .ats-cal-day.empty {
                cursor: default !important;
            }

            .ats-cal-day.past {
                color: #cbd5e1 !important;
                cursor: not-allowed !important;
            }

            .ats-cal-day.selected {
                background: #0e7d3f !important;
                color: #fff !important;
                border-radius: 50% !important;
                font-weight: 700 !important;
            }

            .ats-cal-day.in-range {
                background: #f0fdf4 !important;
                border-radius: 0 !important;
                color: #0e7d3f !important;
            }

            .ats-cal-day.range-start {
                background: #0e7d3f !important;
                color: #fff !important;
                border-radius: 50% 0 0 50% !important;
            }

            .ats-cal-day.range-end {
                background: #0e7d3f !important;
                color: #fff !important;
                border-radius: 0 50% 50% 0 !important;
            }

            .ats-cal-divider {
                width: 1px !important;
                background: #f1f5f9 !important;
            }

            /* ── Rooms & Guests Dropdown ────────────────── */
            .ats-rooms-dropdown {
                position: absolute !important;
                top: calc(100% + 10px) !important;
                right: 0 !important;
                background: #fff !important;
                border-radius: 14px !important;
                box-shadow: 0 8px 40px rgba(0, 0, 0, .15) !important;
                border: 1.5px solid #e2e8f0 !important;
                padding: 20px !important;
                z-index: 9999 !important;
                display: none !important;
                min-width: 300px !important;
                max-width: 340px !important;
                max-height: 480px !important;
                overflow-y: auto !important;
                scrollbar-width: none !important;
            }

            .ats-rooms-dropdown::-webkit-scrollbar {
                display: none !important;
            }

            .ats-rooms-dropdown.open {
                display: block !important;
            }

            .ats-counter-row {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 12px 0 !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }

            .ats-counter-row:last-of-type {
                border-bottom: none !important;
            }

            .ats-counter-label {
                font-size: 14px !important;
                font-weight: 600 !important;
                color: #0f172a !important;
            }

            .ats-counter-sub {
                font-size: 11px !important;
                color: #94a3b8 !important;
                margin-top: 2px !important;
            }

            .ats-counter-ctrl {
                display: flex !important;
                align-items: center !important;
                gap: 12px !important;
            }

            /* .ats-counter-btn {
                width: 30px !important;
                height: 30px !important;
                border-radius: 50% !important;
                border: 1.5px solid #e2e8f0 !important;
                background: #fff !important;
                font-size: 18px !important;
                cursor: pointer !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                color: #475569 !important;
                transition: all .12s !important;
                line-height: 1 !important;
                font-family: inherit !important;
            } */
            button.ats-counter-btn {
                padding: 5px 8px;
                background: #0e7d3f;
                border-radius: 5px;
                color: #fff;
            }

            .ats-counter-btn:hover:not(:disabled) {
                border-color: #0e7d3f !important;
                color: #0e7d3f !important;
            }

            .ats-counter-btn:disabled {
                opacity: .35 !important;
                cursor: not-allowed !important;
            }

            .ats-counter-val {
                font-size: 15px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                min-width: 20px !important;
                text-align: center !important;
            }

            .ats-rooms-done {
                width: 100% !important;
                margin-top: 16px !important;
                padding: 10px !important;
                background: #0e7d3f !important;
                color: #fff !important;
                border: none !important;
                border-radius: 8px !important;
                font-size: 14px !important;
                font-weight: 700 !important;
                cursor: pointer !important;
                font-family: inherit !important;
                transition: background .15s !important;
            }

            .ats-rooms-done:hover {
                background: #0a6232 !important;
            }

            /* ── Coming Soon panel ──────────────────────── */
            .ats-coming-soon {
                text-align: center !important;
                padding: 40px 20px !important;
            }

            .ats-coming-soon-icon {
                font-size: 48px !important;
                display: block !important;
                margin-bottom: 14px !important;
            }

            .ats-coming-soon h3 {
                font-size: 20px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                margin-bottom: 8px !important;
            }

            .ats-coming-soon p {
                font-size: 14px !important;
                color: #64748b !important;
            }

            .ats-coming-soon-badge {
                display: inline-block !important;
                background: #fef3c7 !important;
                color: #92400e !important;
                font-size: 12px !important;
                font-weight: 700 !important;
                padding: 4px 12px !important;
                border-radius: 20px !important;
                margin-top: 12px !important;
            }

            /* ── Flight panel wraps existing shortcode ──── */
            .ats-flight-wrap .amadex-search,
            .ats-flight-wrap .amadex-search-modern {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
            }

            @media (max-width: 768px) {
                .ats-box {
                    padding: 16px !important;
                }

                .ats-hotel-form {
                    flex-direction: column !important;
                    border-radius: 12px !important;
                }

                .ats-field {
                    border-right: none !important;
                    border-bottom: 1.5px solid #e2e8f0 !important;
                }

                .ats-search-btn {
                    border-radius: 10px !important;
                    padding: 14px !important;
                    justify-content: center !important;
                    margin-top: 8px !important;
                }

                .ats-datepicker-wrap {
                    min-width: 320px !important;
                    flex-direction: column !important;
                    left: 0 !important;
                    transform: none !important;
                }

                .ats-cal-divider {
                    display: none !important;
                }
            }
        </style>

        <div id="<?php echo esc_attr($uid); ?>" class="ats-container">

            <!-- Box -->
            <div class="ats-box">

                <!-- Tabs inside box -->
                <div class="ats-tabs-main">
                    <div class="ats-tabs">
                        <button class="ats-tab active" data-tab="flight" onclick="atsTab_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>('flight')">
                            <i class="fa-solid fa-plane-up fa-rotate-by fa-xs" style="color: rgb(255, 255, 255); --fa-rotate-angle: 40deg;"></i>
                            Flight
                        </button>
                        <button class="ats-tab" data-tab="hotels" onclick="atsTab_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>('hotels')">
                            <i class="fa-solid fa-hotel" style="color: rgb(255, 255, 255);"></i>
                            Hotels
                        </button>
                        <button class="ats-tab" data-tab="cars" onclick="atsTab_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>('cars')">
                            <i class="fa-solid fa-car" style="color: rgb(255, 255, 255);"></i>
                            Cars
                        </button>
                        <button class="ats-tab" data-tab="cruise" onclick="atsTab_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>('cruise')">
                            <i class="fa-solid fa-ship" style="color: rgb(255, 255, 255);"></i>
                            Cruise
                        </button>
                    </div>
                </div>


                <!-- Flight Panel -->
                <div class="ats-panel active ats-flight-wrap" id="<?php echo esc_attr($uid); ?>-flight">
                    <?php echo do_shortcode('[amadex_search_modern results_page="' . esc_url($atts['results_page']) . '"]'); ?>
                </div>

                <!-- Hotels Panel -->
                <div class="ats-panel" id="<?php echo esc_attr($uid); ?>-hotels">
                    <div class="ats-hotel-form" id="<?php echo esc_attr($uid); ?>-hform">

                        <!-- Destination -->
                        <div class="ats-field destination-stay" style="flex:2.5;min-width:200px;" id="<?php echo esc_attr($uid); ?>-dest-wrap">
                            <span class="ats-field-label">Destination</span>
                            <input class="ats-dest-input" id="<?php echo esc_attr($uid); ?>-dest-input"
                                placeholder="City, airport, region, landmark or hotel name"
                                autocomplete="off">
                            <span class="ats-field-sub" id="<?php echo esc_attr($uid); ?>-dest-sub"></span>
                            <input type="hidden" id="<?php echo esc_attr($uid); ?>-dest-id">
                            <div class="ats-autocomplete" id="<?php echo esc_attr($uid); ?>-autocomplete"></div>
                        </div>

                        <!-- Check In -->
                        <div class="ats-field" id="<?php echo esc_attr($uid); ?>-checkin-field" onclick="atsOpenDates_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>()">
                            <span class="ats-field-label">Check In</span>
                            <span class="ats-field-value" id="<?php echo esc_attr($uid); ?>-checkin-val">Select date</span>
                            <span class="ats-field-sub" id="<?php echo esc_attr($uid); ?>-checkin-day"></span>
                        </div>

                        <!-- Check Out -->
                        <div class="ats-field" id="<?php echo esc_attr($uid); ?>-checkout-field" onclick="atsOpenDates_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>()">
                            <span class="ats-field-label">Check Out</span>
                            <span class="ats-field-value" id="<?php echo esc_attr($uid); ?>-checkout-val">Select date</span>
                            <span class="ats-field-sub" id="<?php echo esc_attr($uid); ?>-checkout-day"></span>
                        </div>

                        <!-- Rooms & Guests -->
                        <div class="ats-field room-selection" id="<?php echo esc_attr($uid); ?>-rooms-field" onclick="atsOpenRooms_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>()">
                            <span class="ats-field-label">Rooms and guests</span>
                            <span class="ats-field-value" id="<?php echo esc_attr($uid); ?>-rooms-val">1 room, 1 adult</span>
                            <span class="ats-field-sub" id="<?php echo esc_attr($uid); ?>-children-val">0 children</span>

                            <!-- Rooms dropdown -->
                            <div class="ats-rooms-dropdown" id="<?php echo esc_attr($uid); ?>-rooms-drop">
                                <div id="<?php echo esc_attr($uid); ?>-rooms-list"></div>
                                <button class="" style="width:100%;margin-top:8px;border:1.5px dashed #0e7d3f;color:#0e7d3f;background:#fff;border-radius:8px;padding:8px;font-size:13px;font-weight:700;cursor:pointer;" onclick="atsAddRoom_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>(event)">+ Add New Room</button>
                                <button class="ats-rooms-done" onclick="atsCloseRooms_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>(event)">Done</button>
                            </div>
                        </div>

                        <!-- Search Button -->
                        <button class="ats-search-btn" onclick="atsHotelSearch_<?php echo esc_attr(str_replace('-', '_', $uid)); ?>()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            Search
                        </button>
                    </div>

                    <!-- Date Picker -->
                    <div class="ats-datepicker-wrap" id="<?php echo esc_attr($uid); ?>-datepicker">
                        <div class="ats-cal" id="<?php echo esc_attr($uid); ?>-cal-left"></div>
                        <div class="ats-cal-divider"></div>
                        <div class="ats-cal" id="<?php echo esc_attr($uid); ?>-cal-right"></div>
                    </div>

                    <!-- Hotel Results -->
                    <div id="<?php echo esc_attr($uid); ?>-hotel-results" style="margin-top:20px;"></div>
                </div>

                <!-- Cars Panel -->
                <div class="ats-panel" id="<?php echo esc_attr($uid); ?>-cars">
                    <div class="ats-coming-soon">
                        <h3>Car Rentals</h3>
                        <p>Search and book rental cars at the best prices.</p>
                        <span class="ats-coming-soon-badge">Coming Soon</span>
                    </div>
                </div>

                <!-- Cruise Panel -->
                <div class="ats-panel" id="<?php echo esc_attr($uid); ?>-cruise">
                    <div class="ats-coming-soon">
                        <h3>Cruise Booking</h3>
                        <p>Explore and book amazing cruise packages worldwide.</p>
                        <span class="ats-coming-soon-badge">Coming Soon</span>
                    </div>
                </div>

            </div>
        </div>

        <script>
            (function() {
                var UID = <?php echo json_encode($uid); ?>;
                var FN = UID.replace(/-/g, '_');
                var AJAXURL = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var NONCE = <?php echo json_encode(wp_create_nonce('amadex_nonce')); ?>;
                var HOTEL_URL = <?php echo json_encode($atts['hotel_results']); ?>;

                // State
                var checkIn = null;
                var checkOut = null;
                var selecting = 'checkin'; // 'checkin' | 'checkout'
                var calOffset = 0; // months offset from today
                var roomData = [{
                    adults: 1,
                    children: 0
                }];
                var destHotelId = '';

                var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                var DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                // ── Tab switching ───────────────────────────
                window['atsTab_' + FN] = function(tab) {
                    document.querySelectorAll('#' + UID + ' .ats-tab').forEach(function(t) {
                        t.classList.toggle('active', t.dataset.tab === tab);
                    });
                    document.querySelectorAll('#' + UID + ' .ats-panel').forEach(function(p) {
                        p.classList.remove('active');
                    });
                    var panel = document.getElementById(UID + '-' + tab);
                    if (panel) panel.classList.add('active');
                };

                // ── Date picker ─────────────────────────────
                window['atsOpenDates_' + FN] = function() {
                    closeRooms();
                    var dp = document.getElementById(UID + '-datepicker');
                    dp.classList.toggle('open');
                    if (dp.classList.contains('open')) renderCals();
                    document.addEventListener('click', outsideClickDates, {
                        once: false
                    });
                };

                function outsideClickDates(e) {
                    var dp = document.getElementById(UID + '-datepicker');
                    var hf = document.getElementById(UID + '-hform');
                    if (dp && !dp.contains(e.target) && !hf.contains(e.target)) {
                        if (!checkIn || checkOut) {
                            dp.classList.remove('open');
                            document.removeEventListener('click', outsideClickDates);
                        }
                    }
                }

                function renderCals() {
                    var now = new Date();
                    var left = new Date(now.getFullYear(), now.getMonth() + calOffset, 1);
                    var right = new Date(now.getFullYear(), now.getMonth() + calOffset + 1, 1);
                    document.getElementById(UID + '-cal-left').innerHTML = buildCal(left);
                    document.getElementById(UID + '-cal-right').innerHTML = buildCal(right);
                }

                function buildCal(firstDay) {
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    var month = firstDay.getMonth();
                    var year = firstDay.getFullYear();
                    var daysInMonth = new Date(year, month + 1, 0).getDate();
                    var startDow = firstDay.getDay();

                    var html = '<div class="ats-cal-header">';
                    html += '<button class="ats-cal-nav" onclick="atsCalNav_' + FN + '(-1)">&#8249;</button>';
                    html += '<span class="ats-cal-title">' + MONTHS[month] + ' ' + year + '</span>';
                    html += '<button class="ats-cal-nav" onclick="atsCalNav_' + FN + '(1)">&#8250;</button>';
                    html += '</div><div class="ats-cal-grid">';

                    DAYS.forEach(function(d) {
                        html += '<div class="ats-cal-day-name">' + d + '</div>';
                    });

                    for (var i = 0; i < startDow; i++) html += '<div class="ats-cal-day empty"></div>';

                    for (var d = 1; d <= daysInMonth; d++) {
                        var date = new Date(year, month, d);
                        date.setHours(0, 0, 0, 0);
                        var isPast = date < today;
                        var ts = date.getTime();
                        var cls = 'ats-cal-day';
                        if (isPast) {
                            cls += ' past';
                        } else {
                            if (checkIn && checkOut) {
                                var cin = checkIn.getTime(),
                                    cout = checkOut.getTime();
                                if (ts === cin && ts === cout) cls += ' selected';
                                else if (ts === cin) cls += ' range-start';
                                else if (ts === cout) cls += ' range-end';
                                else if (ts > cin && ts < cout) cls += ' in-range';
                            } else if (checkIn && ts === checkIn.getTime()) {
                                cls += ' selected';
                            }
                        }
                        var onclick = isPast ? '' : 'onclick="atsPickDay_' + FN + '(' + year + ',' + month + ',' + d + ')"';
                        html += '<div class="' + cls + '" ' + onclick + '>' + d + '</div>';
                    }
                    html += '</div>';
                    return html;
                }

                window['atsCalNav_' + FN] = function(dir) {
                    calOffset += dir;
                    if (calOffset < 0) calOffset = 0;
                    renderCals();
                };

                window['atsPickDay_' + FN] = function(y, m, d) {
                    var picked = new Date(y, m, d);
                    picked.setHours(0, 0, 0, 0);

                    if (!checkIn || (checkIn && checkOut)) {
                        // Start fresh selection
                        checkIn = picked;
                        checkOut = null;
                        selecting = 'checkout';
                    } else {
                        // Second pick
                        if (picked <= checkIn) {
                            checkIn = picked;
                            checkOut = null;
                            selecting = 'checkout';
                        } else {
                            checkOut = picked;
                            selecting = 'checkin';
                            setTimeout(function() {
                                document.getElementById(UID + '-datepicker').classList.remove('open');
                            }, 300);
                        }
                    }
                    updateDateDisplay();
                    renderCals();
                };

                function updateDateDisplay() {
                    var cinVal = document.getElementById(UID + '-checkin-val');
                    var cinDay = document.getElementById(UID + '-checkin-day');
                    var coutVal = document.getElementById(UID + '-checkout-val');
                    var coutDay = document.getElementById(UID + '-checkout-day');

                    if (checkIn) {
                        cinVal.textContent = fmtShort(checkIn);
                        cinDay.textContent = fmtDay(checkIn);
                    } else {
                        cinVal.textContent = 'Select date';
                        cinDay.textContent = '';
                    }
                    if (checkOut) {
                        coutVal.textContent = fmtShort(checkOut);
                        coutDay.textContent = fmtDay(checkOut);
                    } else {
                        coutVal.textContent = 'Select date';
                        coutDay.textContent = '';
                    }
                }

                function fmtShort(d) {
                    return d.getDate() + ' ' + MONTHS[d.getMonth()].slice(0, 3) + ', ' + String(d.getFullYear()).slice(2);
                }

                function fmtDay(d) {
                    return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][d.getDay()];
                }

                function fmtISO(d) {
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                // ── Rooms & Guests ──────────────────────────
                window['atsOpenRooms_' + FN] = function() {
                    event.stopPropagation();
                    var dp = document.getElementById(UID + '-datepicker');
                    if (dp) dp.classList.remove('open');
                    var drop = document.getElementById(UID + '-rooms-drop');
                    drop.classList.toggle('open');
                    if (drop.classList.contains('open')) {
                        setTimeout(function() {
                            document.addEventListener('click', outsideClickRooms, {
                                once: false
                            });
                        }, 10);
                    }
                };
                window['atsCloseRooms_' + FN] = function(e) {
                    if (e) e.stopPropagation();
                    closeRooms();
                    document.removeEventListener('click', outsideClickRooms);
                };

                function closeRooms() {
                    var drop = document.getElementById(UID + '-rooms-drop');
                    if (drop) drop.classList.remove('open');
                }

                function outsideClickRooms(e) {
                    var drop = document.getElementById(UID + '-rooms-drop');
                    var field = document.getElementById(UID + '-rooms-field');
                    if (drop && !drop.contains(e.target) && !field.contains(e.target)) {
                        closeRooms();
                        document.removeEventListener('click', outsideClickRooms);
                    }
                }

                function atsRenderRooms() {
                    var list = document.getElementById(UID + '-rooms-list');
                    if (!list) return;
                    list.innerHTML = roomData.map(function(r, i) {
                        return '<div style="border-bottom:1px solid #f1f5f9;padding:10px 0;">' +
                            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">' +
                            '<span style="font-size:13px;font-weight:700;background:#f1f5f9;padding:3px 8px;border-radius:4px;">Room ' + (i + 1) + '</span>' +
                            (roomData.length > 1 ? '<button onclick="atsRemoveRoom_' + FN + '(event,' + i + ')" style="font-size:12px;font-weight:700;color:#dc2626;cursor:pointer;background:none;border:none;font-family:inherit;">REMOVE</button>' : '') +
                            '</div>' +
                            '<div class="ats-counter-row">' +
                            '<div><div class="ats-counter-label">Adults</div><div class="ats-counter-sub">Age 18+</div></div>' +
                            '<div class="ats-counter-ctrl">' +
                            '<button class="ats-counter-btn" onclick="atsRoomCtr_' + FN + '(event,' + i + ',\'adults\',-1)">−</button>' +
                            '<span class="ats-counter-val">' + r.adults + '</span>' +
                            '<button class="ats-counter-btn" onclick="atsRoomCtr_' + FN + '(event,' + i + ',\'adults\',1)">+</button>' +
                            '</div>' +
                            '</div>' +
                            '<div class="ats-counter-row">' +
                            '<div><div class="ats-counter-label">Children</div><div class="ats-counter-sub">Under 18</div></div>' +
                            '<div class="ats-counter-ctrl">' +
                            '<button class="ats-counter-btn" onclick="atsRoomCtr_' + FN + '(event,' + i + ',\'children\',-1)">−</button>' +
                            '<span class="ats-counter-val">' + r.children + '</span>' +
                            '<button class="ats-counter-btn" onclick="atsRoomCtr_' + FN + '(event,' + i + ',\'children\',1)">+</button>' +
                            '</div>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                    // Update label
                    var ta = roomData.reduce(function(s, r) {
                        return s + r.adults;
                    }, 0);
                    var tc = roomData.reduce(function(s, r) {
                        return s + r.children;
                    }, 0);
                    document.getElementById(UID + '-rooms-val').textContent = roomData.length + ' room' + (roomData.length > 1 ? 's' : '') + ', ' + ta + ' adult' + (ta > 1 ? 's' : '');
                    document.getElementById(UID + '-children-val').textContent = tc + ' child' + (tc !== 1 ? 'ren' : '');
                }
                atsRenderRooms();

                window['atsRoomCtr_' + FN] = function(e, idx, type, delta) {
                    if (e && e.stopPropagation) e.stopPropagation();
                    if (type === 'adults') roomData[idx].adults = Math.min(16, Math.max(1, roomData[idx].adults + delta));
                    if (type === 'children') roomData[idx].children = Math.min(10, Math.max(0, roomData[idx].children + delta));
                    atsRenderRooms();
                };
                window['atsAddRoom_' + FN] = function(e) {
                    if (e && e.stopPropagation) e.stopPropagation();
                    if (roomData.length >= 8) return;
                    roomData.push({
                        adults: 1,
                        children: 0
                    });
                    atsRenderRooms();
                };
                window['atsRemoveRoom_' + FN] = function(e, idx) {
                    if (e && e.stopPropagation) e.stopPropagation();
                    if (roomData.length <= 1) return;
                    roomData.splice(idx, 1);
                    atsRenderRooms();
                };

                // ── Autocomplete ────────────────────────────
                // ── Autocomplete (reuses amadex_search_airports) ─
                var acTimer = null;

                // Popular destinations shown on click/focus before typing
                var popularDests = [{
                        code: 'JFK',
                        city: 'New York',
                        name: 'John F. Kennedy International, United States'
                    },
                    {
                        code: 'LHR',
                        city: 'London',
                        name: 'Heathrow Airport, United Kingdom'
                    },
                    {
                        code: 'DXB',
                        city: 'Dubai',
                        name: 'Dubai International, United Arab Emirates'
                    },
                    {
                        code: 'CDG',
                        city: 'Paris',
                        name: 'Charles de Gaulle, France'
                    },
                    {
                        code: 'LAX',
                        city: 'Los Angeles',
                        name: 'Los Angeles International, United States'
                    },
                    {
                        code: 'SIN',
                        city: 'Singapore',
                        name: 'Singapore Changi, Singapore'
                    },
                    {
                        code: 'BKK',
                        city: 'Bangkok',
                        name: 'Suvarnabhumi Airport, Thailand'
                    },
                    {
                        code: 'DEL',
                        city: 'Delhi',
                        name: 'Indira Gandhi International, India'
                    },
                ];

                function showPopularDests() {
                    var ac = document.getElementById(UID + '-autocomplete');
                    ac.innerHTML = '<div style="padding:8px 14px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;">Popular Destinations</div>' +
                        popularDests.map(function(a) {
                            return '<div class="ats-autocomplete-item" onclick="atsPickHotel_' + FN + '(\'' + a.code + '\',\'' + a.city + '\',\'' + a.name + '\')">' +
                                '<div style="width:36px;height:36px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>' +
                                '</div>' +
                                '<div style="min-width:0;">' +
                                '<div class="ats-autocomplete-name">' + a.city + ' <span style="color:#94a3b8;font-weight:500;font-size:12px;">(' + a.code + ')</span></div>' +
                                '<div class="ats-autocomplete-sub">' + a.name + '</div>' +
                                '</div>' +
                                '</div>';
                        }).join('');
                    ac.classList.add('open');
                }
                // Prefill destination from cookie or flight search sessionStorage
                function getCookieAts(name) {
                    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                    return match ? decodeURIComponent(match[2]) : '';
                }
                // Auto-fill Check In with today, Check Out with tomorrow
                // Auto-fill Check In with today, Check Out with today + 7
                (function prefillDates() {
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    var checkout = new Date();
                    checkout.setHours(0, 0, 0, 0);
                    checkout.setDate(today.getDate() + 7);
                    checkIn = today;
                    checkOut = checkout;
                    updateDateDisplay();
                })();
                (function prefillDest() {
                    var input = document.getElementById(UID + '-dest-input');
                    var codeEl = document.getElementById(UID + '-dest-id');
                    if (input && !input.value.trim()) {
                        // 1. Try hotel dest cookie
                        var city = getCookieAts('amadex_hotel_dest_city');
                        var code = getCookieAts('amadex_hotel_dest_code');
                        // 2. Fallback to flight search sessionStorage destination
                        // 2. Fallback to flight search sessionStorage destination
                        if (!city) {
                            try {
                                var fs = sessionStorage.getItem('amadex_search_data');
                                if (fs) {
                                    var fsd = JSON.parse(fs);
                                    if (fsd.destination) {
                                        city = fsd.destination;
                                        code = fsd.destination;
                                        // Try to get full name from booking flight data
                                        var bf = sessionStorage.getItem('amadex_booking_flight');
                                        if (bf) {
                                            var bfd = JSON.parse(bf);
                                            var seg = bfd.itineraries && bfd.itineraries[0] && bfd.itineraries[0].segments;
                                            if (seg && seg.length) {
                                                var arr = seg[seg.length - 1].arrival;
                                                if (arr && arr.iataCode) {
                                                    code = arr.iataCode;
                                                    city = arr.cityName || arr.iataCode;
                                                }
                                            }
                                        }
                                    }
                                }
                            } catch (e) {}
                        }
                        if (city) {
                            // If city is just an IATA code, look up the full name
                            var knownCities = {
                                'LAX': 'Los Angeles',
                                'JFK': 'New York',
                                'LHR': 'London',
                                'DXB': 'Dubai',
                                'CDG': 'Paris',
                                'SIN': 'Singapore',
                                'BKK': 'Bangkok',
                                'DEL': 'Delhi',
                                'BOM': 'Mumbai',
                                'ORD': 'Chicago',
                                'DFW': 'Dallas',
                                'MIA': 'Miami',
                                'ATL': 'Atlanta',
                                'SEA': 'Seattle',
                                'DEN': 'Denver',
                                'LAS': 'Las Vegas',
                                'SFO': 'San Francisco',
                                'NRT': 'Tokyo',
                                'HKG': 'Hong Kong',
                                'KUL': 'Kuala Lumpur',
                                'SYD': 'Sydney',
                                'FCO': 'Rome',
                                'MAD': 'Madrid',
                                'BCN': 'Barcelona',
                                'AMS': 'Amsterdam',
                                'FRA': 'Frankfurt',
                                'NYC': 'New York',
                                'LON': 'London',
                                'PAR': 'Paris',
                                'CHI': 'Chicago',
                            };
                            var subText = getCookieAts('amadex_hotel_dest_sub');
                            var cityName = knownCities[city.toUpperCase()] || city;
                            var fullSub = subText || (cityName !== city ? cityName + ' (' + city + ')' : '');

                            input.value = cityName;
                            if (codeEl) {
                                codeEl.value = code;
                                codeEl.dataset.cityCode = code;
                                codeEl.dataset.cityName = cityName;
                            }
                            destHotelId = code;
                            var subEl = document.getElementById(UID + '-dest-sub');
                            if (subEl && fullSub) subEl.textContent = fullSub;
                        }
                    }
                })();
                document.getElementById(UID + '-dest-input').addEventListener('focus', function() {
                    showPopularDests();
                });

                document.getElementById(UID + '-dest-input').addEventListener('click', function() {
                    showPopularDests();
                });

                // Also open when clicking anywhere on the destination field
                document.getElementById(UID + '-dest-wrap').addEventListener('click', function(e) {
                    document.getElementById(UID + '-dest-input').focus();
                    showPopularDests();
                });

                document.getElementById(UID + '-dest-input').addEventListener('input', function() {
                    var val = this.value.trim();
                    clearTimeout(acTimer);
                    var ac = document.getElementById(UID + '-autocomplete');
                    if (val.length < 1) {
                        showPopularDests();
                        return;
                    }
                    acTimer = setTimeout(function() {
                        fetch(AJAXURL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: new URLSearchParams({
                                    action: 'amadex_search_airports',
                                    nonce: NONCE,
                                    keyword: val
                                })
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(data) {
                                if (!data.success || !data.data || !data.data.length) {
                                    ac.classList.remove('open');
                                    return;
                                }
                                ac.innerHTML = data.data.map(function(a) {
                                    var safeName = (a.name || '').replace(/'/g, '&#39;');
                                    var safeCity = (a.city || '').replace(/'/g, '&#39;');
                                    var safeCountry = (a.country || '').replace(/'/g, '&#39;');
                                    var safeCode = (a.code || '').replace(/'/g, '&#39;');
                                    return '<div class="ats-autocomplete-item" onclick="atsPickHotel_' + FN + '(\'' + safeCode + '\',\'' + safeCity + '\',\'' + safeName + ', ' + safeCity + ', ' + safeCountry + '\')">' +
                                        '<div style="width:36px;height:36px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                                        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>' +
                                        '</div>' +
                                        '<div style="min-width:0;">' +
                                        '<div class="ats-autocomplete-name">' + safeCity + ' <span style="color:#94a3b8;font-weight:500;font-size:12px;">(' + safeCode + ')</span></div>' +
                                        '<div class="ats-autocomplete-sub">' + safeName + ', ' + safeCountry + '</div>' +
                                        '</div>' +
                                        '</div>';
                                }).join('');
                                ac.classList.add('open');
                            })
                            .catch(function() {
                                ac.classList.remove('open');
                            });
                    }, 200);
                });

                window['atsPickHotel_' + FN] = function(code, city, fullName) {
                    destHotelId = code;
                    document.getElementById(UID + '-dest-input').value = city;
                    document.getElementById(UID + '-dest-sub').textContent = fullName;
                    var destIdEl = document.getElementById(UID + '-dest-id');
                    destIdEl.value = code;
                    destIdEl.dataset.cityCode = code;
                    destIdEl.dataset.cityName = city;
                    document.getElementById(UID + '-autocomplete').classList.remove('open');
                    // Save to cookie for 30 days
                    var exp = new Date();
                    exp.setDate(exp.getDate() + 30);
                    document.cookie = 'amadex_hotel_dest_city=' + encodeURIComponent(city) + '; expires=' + exp.toUTCString() + '; path=/';
                    document.cookie = 'amadex_hotel_dest_code=' + encodeURIComponent(code) + '; expires=' + exp.toUTCString() + '; path=/';
                    document.cookie = 'amadex_hotel_dest_sub=' + encodeURIComponent(fullName) + '; expires=' + exp.toUTCString() + '; path=/';
                };
                // ── Hotel Search ────────────────────────────
                window['atsHotelSearch_' + FN] = function() {
                    var dest = document.getElementById(UID + '-dest-input').value.trim();
                    var hotelId = document.getElementById(UID + '-dest-id').value;

                    if (!dest) {
                        document.getElementById(UID + '-dest-input').focus();
                        return;
                    }
                    if (!checkIn || !checkOut) {
                        document.getElementById(UID + '-datepicker').classList.add('open');
                        renderCals();
                        return;
                    }

                    // Store search data in sessionStorage and redirect
                    var destEl = document.getElementById(UID + '-dest-id');
                    var cityCode = (destEl && destEl.dataset.cityCode) ? destEl.dataset.cityCode : hotelId;
                    var cityName = (destEl && destEl.dataset.cityName) ? destEl.dataset.cityName : dest;
                    var totalAdults = roomData.reduce(function(s, r) {
                        return s + r.adults;
                    }, 0);
                    var totalChildren = roomData.reduce(function(s, r) {
                        return s + r.children;
                    }, 0);
                    var searchData = {
                        destination: cityName,
                        hotelId: cityCode,
                        cityCode: cityCode,
                        checkIn: fmtISO(checkIn),
                        checkOut: fmtISO(checkOut),
                        rooms: roomData.length,
                        adults: Math.round(totalAdults / roomData.length),
                        children: totalChildren,
                        roomData: roomData,
                    };

                    try {
                        // Force clear old data first then set fresh
                        sessionStorage.removeItem('amadex_hotel_search');
                        sessionStorage.setItem('amadex_hotel_search', JSON.stringify(searchData));
                    } catch (e) {}

                    window.location.href = <?php echo json_encode($atts['hotel_results']); ?>;
                    return;

                    var resultsEl = document.getElementById(UID + '-hotel-results');
                    resultsEl.innerHTML = '<div style="text-align:center;padding:30px;"><div style="width:32px;height:32px;border:3px solid #e2e8f0;border-top-color:#0e7d3f;border-radius:50%;animation:atsSpin 0.8s linear infinite;margin:0 auto 12px;"></div><p style="color:#64748b;font-size:14px;">Searching hotels...</p></div><style>@keyframes atsSpin{to{transform:rotate(360deg)}}</style>';

                    fetch(AJAXURL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'amadex_hotel_search',
                                nonce: NONCE,
                                keyword: dest,
                                hotel_id: hotelId,
                                check_in: fmtISO(checkIn),
                                check_out: fmtISO(checkOut),
                                adults: adults,
                                rooms: rooms
                            })
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (!data.success || !data.data || !data.data.length) {
                                resultsEl.innerHTML = '<p style="text-align:center;color:#94a3b8;padding:30px;">No hotels found. Try a different destination or dates.</p>';
                                return;
                            }
                            resultsEl.innerHTML = renderHotelResults(data.data);
                        })
                        .catch(function() {
                            resultsEl.innerHTML = '<p style="text-align:center;color:#ef4444;padding:20px;">Something went wrong. Please try again.</p>';
                        });
                };

                function renderHotelResults(hotels) {
                    var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:8px;">';
                    hotels.forEach(function(h) {
                        var rating = h.rating ? '⭐ ' + h.rating : '';
                        var price = h.price ? '<strong style="font-size:18px;color:#0f172a;">' + h.price + '</strong><span style="font-size:12px;color:#94a3b8;"> / night</span>' : '';
                        var cancel = h.cancellable ? '<span style="font-size:11px;color:#15803d;font-weight:600;">✓ Free cancellation</span>' : '';
                        html += '<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;transition:box-shadow .15s;" onmouseover="this.style.boxShadow=\'0 4px 20px rgba(14,125,63,.12)\'" onmouseout="this.style.boxShadow=\'none\'">' +
                            '<div style="font-weight:700;font-size:15px;color:#0f172a;margin-bottom:4px;">' + h.name + '</div>' +
                            '<div style="font-size:12px;color:#64748b;margin-bottom:8px;">' + h.address + '</div>' +
                            (rating ? '<div style="font-size:12px;margin-bottom:8px;">' + rating + '</div>' : '') +
                            (cancel ? '<div style="margin-bottom:8px;">' + cancel + '</div>' : '') +
                            (price ? '<div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;">' + price + '</div>' : '') +
                            '</div>';
                    });
                    html += '</div>';
                    return html;
                }

                // Close dropdowns on outside click
                document.addEventListener('click', function(e) {
                    var ac = document.getElementById(UID + '-autocomplete');
                    var destWrap = document.getElementById(UID + '-dest-wrap');
                    if (ac && destWrap && !destWrap.contains(e.target)) {
                        ac.classList.remove('open');
                    }
                });
                // Show tabbed widget only after flight search form is ready
                function atsWaitForSearchBar() {
                    var searchBar = document.querySelector(
                        '.amadex-search-modern, .amadex-modern-search-wrapper, ' +
                        '.amadex-search-form, #amadex-search-form, ' +
                        '.amadex-search, .vsb-wrap'
                    );
                    if (searchBar && searchBar.offsetHeight > 0) {
                        document.getElementById(UID).classList.add('ats-ready');
                    } else {
                        setTimeout(atsWaitForSearchBar, 100);
                    }
                }
                atsWaitForSearchBar();
            })();
        </script>
<?php
        return ob_get_clean();
    }

    /**
     * Hotel Autocomplete AJAX — reuses amadex_search_airports, no extra code needed
     */
    public function hotel_autocomplete()
    {
        wp_send_json_success(array());
    }

    /**
     * Hotel Search AJAX
     */
    public function hotel_search()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $keyword   = sanitize_text_field($_POST['keyword']   ?? '');
        $check_in  = sanitize_text_field($_POST['check_in']  ?? '');
        $check_out = sanitize_text_field($_POST['check_out'] ?? '');
        $adults    = intval($_POST['adults'] ?? 1);
        $rooms     = intval($_POST['rooms']  ?? 1);

        $token = $this->get_token();
        if (!$token) {
            wp_send_json_error(array('message' => 'API authentication failed'));
            return;
        }

        $base = $this->get_base_url();
        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        );

        $city_code = strtoupper(sanitize_text_field($_POST['hotel_id'] ?? $keyword));

        $airport_to_city = array(
            'JFK' => 'NYC',
            'LGA' => 'NYC',
            'EWR' => 'NYC',
            'LHR' => 'LON',
            'LGW' => 'LON',
            'STN' => 'LON',
            'LCY' => 'LON',
            'CDG' => 'PAR',
            'ORY' => 'PAR',
            'DXB' => 'DXB',
            'AUH' => 'AUH',
            'LAX' => 'LAX',
            'SFO' => 'SFO',
            'ORD' => 'CHI',
            'MDW' => 'CHI',
            'DEL' => 'DEL',
            'BOM' => 'BOM',
            'SIN' => 'SIN',
            'BKK' => 'BKK',
            'DFW' => 'DFW',
            'MIA' => 'MIA',
            'ATL' => 'ATL',
            'SEA' => 'SEA',
            'DEN' => 'DEN',
            'LAS' => 'LAS',
        );
        if (isset($airport_to_city[$city_code])) {
            $city_code = $airport_to_city[$city_code];
        }

        // Try to get city code from airport search
        $loc_url  = $base . '/v1/reference-data/locations?' . http_build_query(array(
            'keyword'  => $city_code,
            'subType'  => 'CITY,AIRPORT',
            'page[limit]' => 5,
        ));
        $loc_resp = wp_remote_get($loc_url, array('headers' => $headers, 'timeout' => 15));
        if (!is_wp_error($loc_resp)) {
            $loc_data = json_decode(wp_remote_retrieve_body($loc_resp));
            if (!empty($loc_data->data)) {
                foreach ($loc_data->data as $loc) {
                    // Prefer CITY type, fallback to AIRPORT
                    if (($loc->subType ?? '') === 'CITY') {
                        $city_code = $loc->iataCode;
                        break;
                    }
                    if (!empty($loc->address->cityCode)) {
                        $city_code = $loc->address->cityCode;
                        break;
                    }
                }
            }
        }

        // Step 1b — Get hotel IDs by resolved city code
        $list_url = $base . '/v1/reference-data/locations/hotels/by-city?' . http_build_query(array(
            'cityCode'    => $city_code,
            'radius'      => 10,
            'radiusUnit'  => 'KM',
            'hotelSource' => 'ALL',
        ));
        $list_resp = wp_remote_get($list_url, array('headers' => $headers, 'timeout' => 15));
        if (is_wp_error($list_resp)) {
            wp_send_json_error(array('message' => $list_resp->get_error_message()));
            return;
        }
        $list_data = json_decode(wp_remote_retrieve_body($list_resp));
        if (empty($list_data->data)) {
            wp_send_json_success(array());
            return;
        }

        // Collect up to 20 hotel IDs
        $hotel_ids = array();
        foreach ($list_data->data as $h) {
            $hotel_ids[] = $h->hotelId;
            if (count($hotel_ids) >= 20) break;
        }

        // Step 2 — Get offers/prices
        $offers_url = $base . '/v3/shopping/hotel-offers?' . http_build_query(array(
            'hotelIds'     => implode(',', $hotel_ids),
            'checkInDate'  => $check_in,
            'checkOutDate' => $check_out,
            'adults'       => $adults,
            'roomQuantity' => $rooms,
            'currency'     => 'USD',
            'bestRateOnly' => 'true',
        ));
        $offers_resp = wp_remote_get($offers_url, array('headers' => $headers, 'timeout' => 20));
        if (is_wp_error($offers_resp)) {
            wp_send_json_error(array('message' => $offers_resp->get_error_message()));
            return;
        }
        $offers_data = json_decode(wp_remote_retrieve_body($offers_resp));

        $hotels = array();
        if (!empty($offers_data->data)) {
            foreach ($offers_data->data as $offer) {
                $hotel      = $offer->hotel  ?? null;
                $offers_arr = $offer->offers ?? array();
                if (!$hotel) continue;
                $first      = !empty($offers_arr) ? $offers_arr[0] : null;
                $price      = null;
                $cancellable = false;
                if ($first) {
                    $total    = $first->price->total    ?? null;
                    $currency = $first->price->currency ?? 'USD';
                    if ($total) $price = $currency . ' ' . number_format((float)$total, 2);
                    $cancellable = empty($first->policies->cancellations ?? array());
                }
                $hotels[] = array(
                    'name'        => $hotel->name    ?? '',
                    'address'     => trim(implode(', ', array_filter(array(
                        $hotel->address->lines[0]  ?? '',
                        $hotel->address->cityName  ?? '',
                    )))),
                    'rating'      => $hotel->rating  ?? null,
                    'price'       => $price,
                    'cancellable' => $cancellable,
                    'hotelId'     => $hotel->hotelId ?? '',
                );
            }
        }

        wp_send_json_success($hotels);
    }

    private function get_token()
    {
        $plugin_path = defined('AMADEX_PATH') ? AMADEX_PATH : plugin_dir_path(dirname(__FILE__)) . '../';
        foreach (
            array(
                $plugin_path . 'includes/api/class-amadex-api.php',
                $plugin_path . 'includes/class-amadex-api.php',
            ) as $f
        ) {
            if (file_exists($f)) {
                require_once $f;
                break;
            }
        }
        if (!class_exists('Amadex_API')) return null;
        $this->_api_instance = new Amadex_API();
        $token = $this->_api_instance->get_access_token();
        if (is_wp_error($token)) return null;
        return $token;
    }

    private function get_base_url()
    {
        $settings = get_option('amadex_api_settings', array());
        $env = $settings['environment'] ?? 'test';
        $is_prod = ($env === 'live' || $env === 'production');
        return $is_prod ? 'https://api.amadeus.com' : 'https://test.api.amadeus.com';
    }
}

new Amadex_Tabbed_Search();
