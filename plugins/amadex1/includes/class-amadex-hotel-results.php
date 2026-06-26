<?php

/**
 * Amadex Hotel Results Page
 * Shortcode: [amadex_hotel_results]
 */
if (!defined('ABSPATH')) exit;

class Amadex_Hotel_Results
{
    public function __construct()
    {
        add_shortcode('amadex_hotel_results', array($this, 'render'));
        add_action('wp_ajax_amadex_hotel_results_search',        array($this, 'search'));
        add_action('wp_ajax_nopriv_amadex_hotel_results_search', array($this, 'search'));
        add_action('wp_ajax_amadex_hotel_photo',                 array($this, 'fetch_photo'));
        add_action('wp_ajax_nopriv_amadex_hotel_photo',          array($this, 'fetch_photo'));
        add_action('wp_ajax_amadex_search_locations',            array($this, 'search_locations'));
        add_action('wp_ajax_nopriv_amadex_search_locations',     array($this, 'search_locations'));
    }

    public function render($atts)
    {
        if (is_admin()) return '';
        $atts = shortcode_atts(array('search_page' => site_url('/')), $atts, 'amadex_hotel_results');
        ob_start(); ?>
        <style>
            .ahr-card-address svg {
                width: 1.5rem;
            }

            .ahr-wrap * {
                box-sizing: border-box;
            }

            .ahr-rooms-left i.fa-solid.fa-bed {
                color: #B80000 !important;
            }

            .ahr-wrap {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px 16px 60px;
            }

            .ahr-summary {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                margin-bottom: 24px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
                display: flex;
                align-items: stretch;
                overflow: visible;
                position: sticky;
                top: 0;
                z-index: 999;
            }

            .ahr-sf {
                display: flex;
                align-items: stretch;
                flex: 1;
                flex-wrap: wrap;
            }

            .ahr-sf-field {
                flex: 1;
                min-width: 140px;
                padding: 12px 18px;
                border-right: 1px solid #e2e8f0;
                cursor: pointer;
                position: relative;
                transition: background .12s;
            }

            .ahr-sf-field:hover {
                background: #f8fafc;
            }

            .ahr-sf-label {
                font-size: 11px;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: block;
                margin-bottom: 3px;
            }

            .ahr-sf-val {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .ahr-sf-input {
                border: none;
                outline: none;
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                width: 100%;
                font-family: inherit;
                background: transparent;
                padding: 0;
            }

            .ahr-sf-date {
                border: none !important;
                outline: none;
                font-size: 15px !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                font-family: inherit;
                background: transparent !important;
                padding: 0 !important;
                width: 100% !important;
                cursor: pointer;
                min-height: 10px !important;
            }

            .ahr-guests-drop {
                position: absolute;
                top: calc(100% + 8px);
                left: 0;
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, .12);
                padding: 16px;
                z-index: 9999;
                min-width: 260px;
                display: none;
            }

            .ahr-guests-drop.open {
                display: block;
            }

            .ahr-gctr-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahr-gctr-row:last-of-type {
                border-bottom: none;
            }

            .ahr-gctr-label {
                font-size: 14px;
                font-weight: 600;
                color: #0f172a;
            }

            .ahr-gctr-sub {
                font-size: 11px;
                color: #94a3b8;
            }

            .ahr-gctr-ctrl {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .ahr-gctr-btn {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                font-size: 16px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #475569;
                font-family: inherit;
                transition: all .12s;
                line-height: 1;
            }

            .ahr-gctr-btn:hover {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .ahr-gctr-val {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
                min-width: 18px;
                text-align: center;
            }

            .ahr-guests-done {
                width: 100%;
                margin-top: 12px;
                padding: 9px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
            }

            .ahr-room-block {
                border-bottom: 1px solid #f1f5f9;
                padding: 10px 0;
            }

            .ahr-room-block-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }

            .ahr-room-block-title {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
                background: #f1f5f9;
                padding: 4px 10px;
                border-radius: 4px;
            }

            .ahr-room-remove {
                font-size: 12px;
                font-weight: 700;
                color: #dc2626;
                cursor: pointer;
                background: none;
                border: none;
                font-family: inherit;
                padding: 0;
            }

            .ahr-room-remove:hover {
                text-decoration: underline;
            }

            .ahr-add-room {
                width: 100%;
                margin-top: 10px;
                padding: 8px;
                background: #fff;
                color: #0e7d3f;
                border: 1.5px dashed #0e7d3f;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
            }

            .ahr-add-room:hover {
                background: #f0fdf4;
            }

            .ahr-dest-ac {
                position: absolute;
                top: calc(100% + 6px);
                left: 0;
                min-width: 300px;
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, .12);
                z-index: 99999;
                max-height: 260px;
                overflow-y: auto;
                display: none;
            }

            .ahr-dest-ac.open {
                display: block;
            }

            .ahr-dest-ac-item {
                padding: 10px 14px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                border-bottom: 1px solid #f8fafc;
                transition: background .1s;
            }

            .ahr-dest-ac-item:hover {
                background: #f0fdf4;
            }

            .ahr-dest-ac-item:last-child {
                border-bottom: none;
            }

            .ahr-dest-ac-name {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahr-dest-ac-sub {
                font-size: 11px;
                color: #64748b;
            }

            .ahr-modify-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 0 28px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 0 13px 13px 0;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .ahr-modify-btn:hover {
                background: #0a6232;
            }

            .ahr-layout {
                display: grid;
                grid-template-columns: 240px 1fr;
                gap: 20px;
                align-items: start;
                padding-top: 4px;
            }

            @media (max-width:768px) {
                .ahr-layout {
                    grid-template-columns: 1fr;
                }
            }

            .ahr-filters {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
                position: sticky;
                top: 80px;
                overflow-y: auto;
                height: 90vh;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }

            .ahr-filters::-webkit-scrollbar {
                display: none;
            }

            .ahr-filter-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
                padding-bottom: 12px;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahr-filter-title {
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
                margin: 0;
            }

            .ahr-filter-clear {
                font-size: 12px;
                font-weight: 600;
                color: #0e7d3f;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
                font-family: inherit;
            }

            .ahr-filter-clear:hover {
                text-decoration: underline;
            }

            .ahr-active-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 14px;
            }

            .ahr-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #15803d;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 20px;
            }

            .ahr-chip-remove {
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                color: #15803d;
                background: none;
                border: none;
                padding: 0;
            }

            .ahr-filter-section {
                margin-bottom: 16px;
                padding-bottom: 16px;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahr-filter-section:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }

            .ahr-filter-label {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 10px;
                display: block;
            }

            .ahr-filter-option {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 9px;
                cursor: pointer;
                font-size: 13px !important;
                color: #334155;
            }

            .ahr-filter-option-left {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .ahr-filter-option input[type="checkbox"] {
                accent-color: #0e7d3f;
                width: 16px;
                height: 16px;
                cursor: pointer;
                flex-shrink: 0;
            }

            .ahr-filter-count {
                font-size: 12px;
                color: #94a3b8;
                font-weight: 500;
            }

            .ahr-toggle-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 10px;
            }

            .ahr-toggle-label {
                font-size: 13px;
                font-weight: 600;
                color: #0f172a;
            }

            .ahr-toggle {
                position: relative;
                width: 40px !important;
                height: 22px;
                flex-shrink: 0;
            }

            .ahr-toggle input {
                display: none;
            }

            .ahr-toggle-slider {
                position: absolute;
                inset: 0;
                background: #e2e8f0;
                border-radius: 22px;
                cursor: pointer;
                transition: background .2s;
            }

            .ahr-toggle-slider::before {
                content: '';
                position: absolute;
                width: 16px;
                height: 16px;
                background: #fff;
                border-radius: 50%;
                top: 3px;
                left: 3px;
                transition: transform .2s;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            }

            .ahr-toggle input:checked+.ahr-toggle-slider {
                background: #0e7d3f;
            }

            .ahr-toggle input:checked+.ahr-toggle-slider::before {
                transform: translateX(18px);
            }

            .ahr-budget-vals {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 8px;
            }

            .ahr-price-range {
                width: 100%;
                accent-color: #0e7d3f;
                margin-bottom: 4px;
            }

            .ahr-show-all {
                font-size: 12px;
                font-weight: 600;
                color: #0e7d3f;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
                font-family: inherit;
                margin-top: 6px;
                display: block;
            }

            .ahr-show-all:hover {
                text-decoration: underline;
            }

            .ahr-sort-bar {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 16px;
                flex-wrap: wrap;
            }

            .ahr-sort-btn {
                padding: 8px 16px;
                border-radius: 100px;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                cursor: pointer;
                font-family: inherit;
                transition: all .15s;
                white-space: nowrap;
            }

            .ahr-sort-btn:hover {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .ahr-sort-btn.active {
                background: #0e7d3f;
                border-color: #0e7d3f;
                color: #fff;
            }

            .ahr-sort-spacer {
                flex: 1;
            }

            .ahr-prop-count {
                font-size: 13px;
                color: #64748b;
                font-weight: 500;
                white-space: nowrap;
            }

            .ahr-results {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .ahr-result-count {
                font-size: 14px;
                color: #64748b;
                font-weight: 500;
                margin-bottom: 4px;
                display: none !important;
            }

            .ahr-card {
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: 16px;
                overflow: hidden;
                display: flex;
                align-items: stretch;
                transition: box-shadow .15s;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .06);
                padding: 15px;
                position: relative;
            }

            .ahr-card:hover {
                box-shadow: 0 6px 24px rgba(14, 125, 63, .12);
                border-color: #0e7d3f;
            }

            .ahr-card-img {
                width: 320px;
                flex-shrink: 0;
                position: relative;
                display: flex;
                font-size: 48px;
                border-radius: 10px;
            }

            .ahr-card-img-inner {
                height: 100%;
                display: flex;
            }

            .ahr-card-img-inner img {
                width: 296px;
                border-radius: 10px;
                height: 274px;
            }

            @media (max-width:600px) {
                .ahr-card-img {
                    display: none;
                }
            }

            .ahr-card-badge-top {
                position: absolute;
                top: 18px;
                left: 0;
                background: #fff;
                color: #000;
                font-size: 14px;
                font-weight: 700;
                padding: 5px 10px;
                display: flex;
                justify-content: center;
                width: 12rem;
                border-top-right-radius: 5px;
                border-bottom-right-radius: 5px;
                gap: 5px;
            }

            .ahr-card-body {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .ahr-card-top-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 2px;
            }

            .ahr-rooms-left {
                font-size: 12px;
                font-weight: 600;
                color: #B80000;
                background: #FFEEEE;
                padding: 6px 10px;
                border-radius: 20px;
            }

            .ahr-rating {
                position: absolute;
                top: 12px;
                right: 12px;
                display: flex;
                align-items: center;
                gap: 4px;
                border: 1px dashed #0e7d3f;
                border-radius: 20px;
                padding: 4px 12px;
                font-size: 13px;
                font-weight: 700;
                color: #0e7d3f;
                background: #fff;
                z-index: 2;
                min-height: 28px;
            }

            .ahr-rating .ahr-star {
                font-size: 14px;
                color: #0e7d3f;
            }

            .ahr-star {
                color: #f59e0b;
                font-size: 13px;
            }

            .ahr-card-name {
                font-size: 20px;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.3;
            }

            .ahr-card-address {
                font-size: 14px;
                font-weight: 500;
                color: #000;
                display: flex;
            }

            .ahr-card-amenities {
                display: flex;
                gap: 10px;
                font-size: 18px;
                margin: 4px 0;
            }

            .ahr-card-bottom-row {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                margin-top: auto;
                padding-top: 12px;
                border-top: 1px solid #f1f5f9;
                flex-wrap: wrap;
                gap: 12px;
            }

            .ahr-price-original {
                font-size: 13px;
                color: #94a3b8;
                text-decoration: line-through;
                margin-right: 6px;
            }

            .ahr-discount-badge {
                background: #EE9C31;
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 20px;
            }

            .ahr-card-price {
                font-size: 24px;
                font-weight: 700;
                color: #0f172a;
                letter-spacing: -0.5px;
                line-height: 1.2;
                margin-top: 2px;
            }

            .ahr-per-night {
                font-size: 13px;
                font-weight: 500;
                color: #64748b;
            }

            .ahr-price-note {
                font-size: 11px;
                color: #94a3b8;
                margin-top: 2px;
            }

            .ahr-card-actions {
                display: flex;
                flex-direction: column;
                gap: 8px;
                align-items: flex-end;
            }

            .ahr-view-btn {
                padding: 9px 22px;
                background: #fff;
                color: #0e7d3f;
                border: 2px solid #0e7d3f;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: all .15s;
                white-space: nowrap;
                width: 187px;
                height: 40px;
                border-radius: 15px;
            }

            .ahr-view-btn:hover {
                background: #f0fdf4;
            }

            .ahr-book-btn {
                display: flex;
                padding: 9px 18px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
                white-space: nowrap;
                text-decoration: none;
                width: 187px;
                height: 40px;
                border-radius: 15px;
                text-align: center;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .ahr-book-btn:hover {
                background: #0a6232;
                color: #fff;
            }

            .ahr-tag {
                display: none;
            }

            .ahr-loading {
                text-align: center;
                padding: 80px 20px;
            }

            .ahr-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #e2e8f0;
                border-top-color: #0e7d3f;
                border-radius: 50%;
                animation: ahrSpin .8s linear infinite;
                margin: 0 auto 16px;
            }

            @keyframes ahrSpin {
                to {
                    transform: rotate(360deg);
                }
            }

            .ahr-skel-shine {
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.7) 50%, transparent 100%);
                background-size: 200% 100%;
                animation: ahrShine 1.4s infinite;
            }

            @keyframes ahrShine {
                0% {
                    background-position: -200% 0;
                }

                100% {
                    background-position: 200% 0;
                }
            }

            .ahr-loading p {
                color: #64748b;
                font-size: 14px;
            }

            .ahr-empty {
                text-align: center;
                padding: 80px 20px;
            }

            .ahr-empty-icon {
                font-size: 48px;
                display: block;
                margin-bottom: 14px;
            }

            .ahr-empty h3 {
                font-size: 18px;
                font-weight: 700;
                color: #334155;
                margin-bottom: 8px;
            }

            .ahr-empty p {
                font-size: 14px;
                color: #94a3b8;
            }
        </style>

        <div class="ahr-wrap">
            <!-- Summary bar -->
            <div class="ahr-summary" id="ahr-summary" style="display:none;">
                <div class="ahr-sf">
                    <div class="ahr-sf-field" style="flex:2;min-width:180px;" id="ahr-dest-wrap">
                        <span class="ahr-sf-label">Destination</span>
                        <input class="ahr-sf-input" id="ahr-dest-input" autocomplete="off" placeholder="City or airport">
                        <input type="hidden" id="ahr-dest-code">
                        <div class="ahr-dest-ac" id="ahr-dest-ac"></div>
                    </div>
                    <div class="ahr-sf-field">
                        <span class="ahr-sf-label">Check In</span>
                        <input type="date" class="ahr-sf-date" id="ahr-checkin">
                    </div>
                    <div class="ahr-sf-field">
                        <span class="ahr-sf-label">Check Out</span>
                        <input type="date" class="ahr-sf-date" id="ahr-checkout">
                    </div>
                    <div class="ahr-sf-field" id="ahr-guests-field" onclick="ahrOpenGuests()" style="cursor:pointer;border-right:none;">
                        <span class="ahr-sf-label">Rooms and guests</span>
                        <span class="ahr-sf-val" id="ahr-guests-val">1 room, 1 adult</span>
                        <div class="ahr-guests-drop" id="ahr-guests-drop">
                            <div id="ahr-rooms-list"></div>
                            <button class="ahr-add-room" onclick="ahrAddRoom(event)">+ Add New Room</button>
                            <button class="ahr-guests-done" onclick="ahrCloseGuests(event)">Done</button>
                        </div>
                    </div>
                </div>
                <button class="ahr-modify-btn" onclick="ahrDoSearch()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    Modify
                </button>
            </div>

            <!-- Layout -->
            <div class="ahr-layout">
                <!-- Filters -->
                <div class="ahr-filters" id="ahr-filters" style="display:none;">
                    <div class="ahr-filter-header">
                        <span class="ahr-filter-title">Filters</span>
                        <button class="ahr-filter-clear" onclick="ahrClearFilters()">Clear All</button>
                    </div>
                    <div class="ahr-active-chips" id="ahr-active-chips"></div>
                    <div class="ahr-filter-section">
                        <span class="ahr-filter-label">Popular Filters</span>
                        <div class="ahr-toggle-row">
                            <span class="ahr-toggle-label">No prepayment</span>
                            <label class="ahr-toggle">
                                <input type="checkbox" id="ahr-filter-prepay" onchange="ahrApplyFilters()">
                                <span class="ahr-toggle-slider"></span>
                            </label>
                        </div>
                        <label class="ahr-filter-option">
                            <div class="ahr-filter-option-left">
                                <input type="checkbox" id="ahr-filter-cancel" onchange="ahrApplyFilters()">
                                <span>Free cancellation</span>
                            </div>
                            <span class="ahr-filter-count" id="ahr-count-cancel"></span>
                        </label>
                        <label class="ahr-filter-option">
                            <div class="ahr-filter-option-left">
                                <input type="checkbox" id="ahr-filter-breakfast" onchange="ahrApplyFilters()">
                                <span>Breakfast included</span>
                            </div>
                        </label>
                    </div>
                    <div class="ahr-filter-section">
                        <span class="ahr-filter-label">Your budget (per night)</span>
                        <div class="ahr-budget-vals">
                            <span id="ahr-price-min-label">$0</span>
                            <span id="ahr-price-max-label">$2000+</span>
                        </div>
                        <input type="range" class="ahr-price-range" id="ahr-price-range" min="0" max="2000" step="10" value="2000" oninput="ahrApplyFilters()">
                    </div>
                    <div class="ahr-filter-section">
                        <span class="ahr-filter-label">Star Rating</span>
                        <?php foreach (array(5, 4, 3, 2, 1) as $s): ?>
                            <label class="ahr-filter-option">
                                <div class="ahr-filter-option-left">
                                    <input type="checkbox" class="ahr-filter-star" value="<?php echo $s; ?>" onchange="ahrApplyFilters()">
                                    <span><?php echo $s; ?> Star<?php echo $s > 1 ? 's' : ''; ?></span>
                                </div>
                                <span class="ahr-filter-count ahr-count-star" data-star="<?php echo $s; ?>"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="ahr-filter-section">
                        <span class="ahr-filter-label">User Rating</span>
                        <?php foreach (array('4.5' => '4.5+', '4.0' => '4+', '3.5' => '3.5+', '3.0' => '3+') as $val => $lbl): ?>
                            <label class="ahr-filter-option">
                                <div class="ahr-filter-option-left">
                                    <input type="checkbox" class="ahr-filter-user-rating" value="<?php echo $val; ?>" onchange="ahrApplyFilters()">
                                    <span><?php echo $lbl; ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="ahr-filter-section">
                        <span class="ahr-filter-label">Room Amenities</span>
                        <?php
                        $amenities = array('WIFI' => 'WiFi', 'POOL' => 'Pool', 'SPA' => 'Spa', 'GYM' => 'Fitness Centre', 'PARKING' => 'Parking', 'RESTAURANT' => 'Restaurant');
                        $i = 0;
                        foreach ($amenities as $val => $lbl): $i++; ?>
                            <label class="ahr-filter-option ahr-amenity-item" style="<?php echo $i > 4 ? 'display:none;' : ''; ?>">
                                <div class="ahr-filter-option-left">
                                    <input type="checkbox" class="ahr-filter-amenity" value="<?php echo $val; ?>" onchange="ahrApplyFilters()">
                                    <span><?php echo $lbl; ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <button class="ahr-show-all" id="ahr-show-amenities" onclick="ahrToggleAmenities()">+ Show all</button>
                    </div>
                </div>

                <!-- Results -->
                <div>
                    <div class="ahr-sort-bar" id="ahr-sort-bar" style="display:none;">
                        <button class="ahr-sort-btn active" onclick="ahrSort('recommended',this)">Recommended</button>
                        <button class="ahr-sort-btn" onclick="ahrSort('price_asc',this)">Low Price ↓</button>
                        <button class="ahr-sort-btn" onclick="ahrSort('price_desc',this)">High Price ↑</button>
                        <button class="ahr-sort-btn" onclick="ahrSort('top_reviewed',this)">Top Reviewed</button>
                        <div class="ahr-sort-spacer"></div>
                        <span class="ahr-prop-count" id="ahr-prop-count"></span>
                    </div>
                    <div class="ahr-result-count" id="ahr-result-count" style="display:none;"></div>
                    <div class="ahr-results" id="ahr-results">
                        <div id="ahr-skel-init"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                var AJAXURL = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var NONCE = <?php echo json_encode(wp_create_nonce('amadex_nonce')); ?>;
                var allHotels = [];
                var currentSort = 'recommended';
                var currentFiltered = [];

                // Force clear any browser cache on page load
                // ── Read session ────────────────────────────
                var searchData = null;
                try {
                    var raw = sessionStorage.getItem('amadex_hotel_search');
                    if (raw) searchData = JSON.parse(raw);
                } catch (e) {}
                if (!searchData) {
                    document.getElementById('ahr-results').innerHTML = '<div class="ahr-empty"><span class="ahr-empty-icon">🔍</span><h3>No search data found</h3><p>Please go back and search for hotels.</p></div>';
                    return;
                }

                // ── State ───────────────────────────────────
                var gCityCode = searchData.cityCode || searchData.hotelId || '';
                // Per-room data: [{adults:1, children:0}, ...]
                var gRoomData = [];
                var savedRooms = searchData.roomData;
                if (savedRooms && savedRooms.length) {
                    gRoomData = savedRooms;
                } else {
                    var initRooms = parseInt(searchData.rooms) || 1;
                    for (var i = 0; i < initRooms; i++) {
                        gRoomData.push({
                            adults: parseInt(searchData.adults) || 1,
                            children: 0
                        });
                    }
                }

                document.getElementById('ahr-dest-input').value = searchData.destination || '';
                document.getElementById('ahr-dest-code').value = gCityCode;
                document.getElementById('ahr-checkin').value = searchData.checkIn || '';
                document.getElementById('ahr-checkout').value = searchData.checkOut || '';
                renderRoomsList();
                updateGuestsLabel();
                document.getElementById('ahr-summary').style.display = 'flex';

                function renderRoomsList() {
                    var list = document.getElementById('ahr-rooms-list');
                    if (!list) return;
                    list.innerHTML = gRoomData.map(function(r, i) {
                        return '<div class="ahr-room-block">' +
                            '<div class="ahr-room-block-header">' +
                            '<span class="ahr-room-block-title">Room ' + (i + 1) + '</span>' +
                            (gRoomData.length > 1 ? '<button class="ahr-room-remove" onclick="ahrRemoveRoom(event,' + i + ')">REMOVE</button>' : '') +
                            '</div>' +
                            '<div class="ahr-gctr-row">' +
                            '<div><div class="ahr-gctr-label">Adults</div><div class="ahr-gctr-sub">Age 18+</div></div>' +
                            '<div class="ahr-gctr-ctrl">' +
                            '<button class="ahr-gctr-btn" onclick="ahrRoomCtr(event,' + i + ',\'adults\',-1)">−</button>' +
                            '<span class="ahr-gctr-val">' + r.adults + '</span>' +
                            '<button class="ahr-gctr-btn" onclick="ahrRoomCtr(event,' + i + ',\'adults\',1)">+</button>' +
                            '</div>' +
                            '</div>' +
                            '<div class="ahr-gctr-row">' +
                            '<div><div class="ahr-gctr-label">Children</div><div class="ahr-gctr-sub">Under 18</div></div>' +
                            '<div class="ahr-gctr-ctrl">' +
                            '<button class="ahr-gctr-btn" onclick="ahrRoomCtr(event,' + i + ',\'children\',-1)">−</button>' +
                            '<span class="ahr-gctr-val">' + r.children + '</span>' +
                            '<button class="ahr-gctr-btn" onclick="ahrRoomCtr(event,' + i + ',\'children\',1)">+</button>' +
                            '</div>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                }

                function updateGuestsLabel() {
                    var totalAdults = gRoomData.reduce(function(s, r) {
                        return s + r.adults;
                    }, 0);
                    var totalChildren = gRoomData.reduce(function(s, r) {
                        return s + r.children;
                    }, 0);
                    document.getElementById('ahr-guests-val').textContent =
                        gRoomData.length + ' room' + (gRoomData.length > 1 ? 's' : '') + ', ' +
                        totalAdults + ' adult' + (totalAdults > 1 ? 's' : '') +
                        (totalChildren ? ', ' + totalChildren + ' child' + (totalChildren > 1 ? 'ren' : '') : '');
                }

                window.ahrRoomCtr = function(e, roomIdx, type, delta) {
                    e.stopPropagation();
                    if (type === 'adults') gRoomData[roomIdx].adults = Math.min(16, Math.max(1, gRoomData[roomIdx].adults + delta));
                    if (type === 'children') gRoomData[roomIdx].children = Math.min(10, Math.max(0, gRoomData[roomIdx].children + delta));
                    renderRoomsList();
                    updateGuestsLabel();
                };
                window.ahrAddRoom = function(e) {
                    e.stopPropagation();
                    if (gRoomData.length >= 8) return;
                    gRoomData.push({
                        adults: 1,
                        children: 0
                    });
                    renderRoomsList();
                    updateGuestsLabel();
                };
                window.ahrRemoveRoom = function(e, idx) {
                    e.stopPropagation();
                    if (gRoomData.length <= 1) return;
                    gRoomData.splice(idx, 1);
                    renderRoomsList();
                    updateGuestsLabel();
                };
                window.ahrOpenGuests = function() {
                    event.stopPropagation();
                    document.getElementById('ahr-guests-drop').classList.toggle('open');
                };
                window.ahrCloseGuests = function(e) {
                    if (e) e.stopPropagation();
                    document.getElementById('ahr-guests-drop').classList.remove('open');
                };

                // ── Autocomplete ────────────────────────────
                var acTimer = null;
                document.getElementById('ahr-dest-input').addEventListener('input', function() {
                    var val = this.value.trim();
                    var ac = document.getElementById('ahr-dest-ac');
                    clearTimeout(acTimer);
                    if (val.length < 1) {
                        ac.classList.remove('open');
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
                        }).then(function(r) {
                            return r.json();
                        }).then(function(data) {
                            if (!data.success || !data.data || !data.data.length) {
                                ac.classList.remove('open');
                                return;
                            }
                            ac.innerHTML = data.data.map(function(a) {
                                var city = (a.city || '').replace(/'/g, "&#39;"),
                                    code = (a.code || '').replace(/'/g, "&#39;"),
                                    name = (a.name || '').replace(/'/g, "&#39;"),
                                    country = (a.country || '').replace(/'/g, "&#39;");
                                var icon = a.type === 'CITY' ?
                                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="1"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>' :
                                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0e7d3f" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
                                return '<div class="ahr-dest-ac-item" onclick="ahrPickDest(\'' + code + '\',\'' + city + '\',\'' + name + '\',\'' + country + '\')">' +
                                    '<div style="width:32px;height:32px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' + icon + '</div>' +
                                    '<div><div class="ahr-dest-ac-name">' + city + ' (' + code + ')</div><div class="ahr-dest-ac-sub">' + name + ', ' + country + '</div></div></div>';
                            }).join('');
                            ac.classList.add('open');
                        });
                    }, 200);
                });
                window.ahrPickDest = function(code, city) {
                    gCityCode = code;
                    document.getElementById('ahr-dest-input').value = city;
                    document.getElementById('ahr-dest-code').value = code;
                    document.getElementById('ahr-dest-ac').classList.remove('open');
                    document.getElementById('ahr-dest-code').dataset.cityName = city;
                };
                document.addEventListener('click', function(e) {
                    var wrap = document.getElementById('ahr-dest-wrap');
                    var ac = document.getElementById('ahr-dest-ac');
                    if (ac && wrap && !wrap.contains(e.target)) ac.classList.remove('open');
                    var drop = document.getElementById('ahr-guests-drop');
                    var field = document.getElementById('ahr-guests-field');
                    if (drop && field && !field.contains(e.target)) drop.classList.remove('open');
                });

                // ── Modify / re-search ──────────────────────
                window.ahrDoSearch = function() {
                    var dest = document.getElementById('ahr-dest-input').value.trim();
                    var code = document.getElementById('ahr-dest-code').dataset.cityName ||
                        document.getElementById('ahr-dest-code').value.trim() ||
                        gCityCode;
                    var checkIn = document.getElementById('ahr-checkin').value;
                    var checkOut = document.getElementById('ahr-checkout').value;
                    if (!dest || !checkIn || !checkOut) {
                        alert('Please fill in all fields.');
                        return;
                    }
                    searchData.destination = dest;
                    gCityCode = code;
                    var totalAdults = gRoomData.reduce(function(s, r) {
                        return s + r.adults;
                    }, 0);
                    var totalChildren = gRoomData.reduce(function(s, r) {
                        return s + r.children;
                    }, 0);
                    var ns = {
                        destination: dest,
                        hotelId: code,
                        cityCode: code,
                        checkIn: checkIn,
                        checkOut: checkOut,
                        rooms: gRoomData.length,
                        adults: Math.round(totalAdults / gRoomData.length),
                        children: totalChildren,
                        roomData: gRoomData,
                    };
                    try {
                        sessionStorage.setItem('amadex_hotel_search', JSON.stringify(ns));
                    } catch (e) {}
                    document.getElementById('ahr-result-count').style.display = 'none';
                    document.getElementById('ahr-sort-bar').style.display = 'none';
                    var skelCard2 = '<div style=\"background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;padding:15px;display:flex;gap:16px;margin-bottom:14px;\"><div style=\"width:296px;height:274px;background:#f1f5f9;border-radius:10px;flex-shrink:0;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"flex:1;display:flex;flex-direction:column;gap:10px;padding-top:8px;\"><div style=\"width:60%;height:20px;background:#f1f5f9;border-radius:6px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:35%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"display:flex;gap:8px;\"><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div><div style=\"width:90%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:65%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-end;\"><div style=\"display:flex;flex-direction:column;gap:6px;\"><div style=\"width:80px;height:12px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:130px;height:28px;background:#f1f5f9;border-radius:6px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:110px;height:11px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div><div style=\"display:flex;flex-direction:column;gap:8px;\"><div style=\"width:187px;height:40px;background:#f1f5f9;border-radius:15px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:187px;height:40px;background:#f1f5f9;border-radius:15px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div></div></div></div>';
                    document.getElementById('ahr-results').innerHTML = skelCard2 + skelCard2 + skelCard2;
                    var skelRow2 = '<div style=\"display:flex;align-items:center;gap:8px;margin-bottom:9px;\"><div style=\"width:16px;height:16px;background:#f1f5f9;border-radius:3px;flex-shrink:0;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"flex:1;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div>';
                    var skelSection2 = '<div style=\"margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #f1f5f9;\"><div style=\"width:80px;height:14px;background:#f1f5f9;border-radius:4px;margin-bottom:12px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div>' + skelRow2 + skelRow2 + skelRow2 + '</div>';
                    var filtersEl2 = document.getElementById('ahr-filters');
                    filtersEl2.innerHTML = '<div style=\"display:flex;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;\"><div style=\"width:60px;height:16px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:50px;height:16px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div>' + skelSection2 + skelSection2 + skelSection2;
                    filtersEl2.style.display = 'block';
                    var totalAdults = gRoomData.reduce(function(s, r) {
                        return s + r.adults;
                    }, 0);
                    fetchHotels(dest, code, checkIn, checkOut, Math.round(totalAdults / gRoomData.length), gRoomData.length);
                };

                // ── Sort ────────────────────────────────────
                window.ahrSort = function(sort, btn) {
                    currentSort = sort;
                    document.querySelectorAll('.ahr-sort-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    renderResults(currentFiltered.length ? currentFiltered : allHotels);
                };

                function sortHotels(hotels) {
                    var arr = hotels.slice();
                    if (currentSort === 'price_asc') arr.sort(function(a, b) {
                        return (a.price_raw || 0) - (b.price_raw || 0);
                    });
                    if (currentSort === 'price_desc') arr.sort(function(a, b) {
                        return (b.price_raw || 0) - (a.price_raw || 0);
                    });
                    if (currentSort === 'top_reviewed') arr.sort(function(a, b) {
                        return (b.overall_rating || 0) - (a.overall_rating || 0);
                    });
                    return arr;
                }

                // ── Filter counts ───────────────────────────
                function updateFilterCounts(hotels) {
                    var cancelCount = hotels.filter(function(h) {
                        return h.cancellable;
                    }).length;
                    var el = document.getElementById('ahr-count-cancel');
                    if (el) el.textContent = '(' + cancelCount + ')';
                    document.querySelectorAll('.ahr-count-star').forEach(function(el) {
                        var s = parseInt(el.dataset.star);
                        el.textContent = '(' + hotels.filter(function(h) {
                            return parseInt(h.rating || 0) === s;
                        }).length + ')';
                    });
                }

                function updateUserRatingCounts() {
                    var total = allHotels.filter(function(h) {
                        return h.google_rating >= 3;
                    }).length;
                }

                // ── Fetch ───────────────────────────────────
                function fetchHotels(dest, code, checkIn, checkOut, adults, rooms) {
                    fetch(AJAXURL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Cache-Control': 'no-cache, no-store',
                                'Pragma': 'no-cache'
                            },
                            body: new URLSearchParams({
                                action: 'amadex_hotel_results_search',
                                nonce: NONCE,
                                keyword: dest || '',
                                hotel_id: code || '',
                                cityCode: code || '',
                                check_in: checkIn || '',
                                check_out: checkOut || '',
                                adults: adults || 1,
                                rooms: rooms || 1,
                                _t: Date.now(),
                            })
                        }).then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (!data.success || !data.data || !data.data.length) {
                                document.getElementById('ahr-results').innerHTML = '<div class="ahr-empty"><span class="ahr-empty-icon">🏨</span><h3>No hotels found</h3><p>Try different dates or a nearby city.</p></div>';
                                return;
                            }
                            allHotels = data.data;
                            var maxP = Math.max.apply(null, allHotels.map(function(h) {
                                return h.price_raw || 0;
                            }));
                            // Restore real filter panel HTML (skeleton may have replaced it)
                            var filtersEl = document.getElementById('ahr-filters');
                            filtersEl.innerHTML = ahrRealFiltersHTML;
                            var range = document.getElementById('ahr-price-range');
                            range.max = Math.ceil(maxP / 100) * 100 || 2000;
                            range.value = range.max;
                            document.getElementById('ahr-price-max-label').textContent = '$' + range.max + '+';
                            filtersEl.style.display = 'block';
                            renderResults(allHotels);
                            updateFilterCounts(allHotels);
                            // Load real Google photos in background
                            loadGooglePhotos(allHotels);
                        }).catch(function() {
                            document.getElementById('ahr-results').innerHTML = '<div class="ahr-empty"><span class="ahr-empty-icon">⚠️</span><h3>Something went wrong</h3><p>Please try again.</p></div>';
                        });
                }

                // Capture the real filter HTML before any skeleton overwrites it
                var ahrRealFiltersHTML = document.getElementById('ahr-filters').innerHTML;

                (function() {
                    var skelCard = '<div style=\"background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;padding:15px;display:flex;gap:16px;margin-bottom:14px;\"><div style=\"width:296px;height:274px;background:#f1f5f9;border-radius:10px;flex-shrink:0;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"flex:1;display:flex;flex-direction:column;gap:10px;padding-top:8px;\"><div style=\"width:60%;height:20px;background:#f1f5f9;border-radius:6px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:35%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"display:flex;gap:8px;\"><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:70px;height:28px;background:#f1f5f9;border-radius:20px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div><div style=\"width:90%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:65%;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-end;\"><div style=\"display:flex;flex-direction:column;gap:6px;\"><div style=\"width:80px;height:12px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:130px;height:28px;background:#f1f5f9;border-radius:6px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:110px;height:11px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div><div style=\"display:flex;flex-direction:column;gap:8px;\"><div style=\"width:187px;height:40px;background:#f1f5f9;border-radius:15px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:187px;height:40px;background:#f1f5f9;border-radius:15px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div></div></div></div>';
                    document.getElementById('ahr-results').innerHTML = skelCard + skelCard + skelCard;
                    var skelRow = '<div style=\"display:flex;align-items:center;gap:8px;margin-bottom:9px;\"><div style=\"width:16px;height:16px;background:#f1f5f9;border-radius:3px;flex-shrink:0;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"flex:1;height:13px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div>';
                    var skelSection = '<div style=\"margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #f1f5f9;\"><div style=\"width:80px;height:14px;background:#f1f5f9;border-radius:4px;margin-bottom:12px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div>' + skelRow + skelRow + skelRow + '</div>';
                    var filtersEl = document.getElementById('ahr-filters');
                    filtersEl.innerHTML = '<div style=\"display:flex;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;\"><div style=\"width:60px;height:16px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div><div style=\"width:50px;height:16px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;\"><div class=\"ahr-skel-shine\"></div></div></div>' + skelSection + skelSection + skelSection;
                    filtersEl.style.display = 'block';
                })();
                fetchHotels(
                    searchData.destination || '',
                    searchData.cityCode || searchData.hotelId || '',
                    searchData.checkIn || '', searchData.checkOut || '',
                    searchData.adults || 1, searchData.rooms || 1
                );

                // ── Render ──────────────────────────────────
                function renderResults(hotels) {
                    currentFiltered = hotels;
                    hotels = sortHotels(hotels);
                    var countEl = document.getElementById('ahr-result-count');
                    var sortBar = document.getElementById('ahr-sort-bar');
                    var propCount = document.getElementById('ahr-prop-count');
                    countEl.textContent = hotels.length + ' hotel' + (hotels.length !== 1 ? 's' : '') + ' found';
                    countEl.style.display = 'block';
                    if (sortBar) sortBar.style.display = 'flex';
                    if (propCount) propCount.textContent = hotels.length + ' properties in ' + (searchData.destination || '');

                    if (!hotels.length) {
                        document.getElementById('ahr-results').innerHTML = '<div class="ahr-empty"><span class="ahr-empty-icon">🏨</span><h3>No hotels match your filters</h3><p>Try adjusting your filters.</p></div>';
                        return;
                    }

                    var sentimentIcons = {
                        staff: '🛎',
                        location: '📍',
                        sleepQuality: '😴',
                        internet: '📶',
                        food: '🍽',
                        comfort: '🛋',
                        valueForMoney: '💰',
                        facilities: '🏋',
                        pools: '🏊',
                        wellness: '💆'
                    };

                    document.getElementById('ahr-results').innerHTML = hotels.map(function(h) {
                        var overallRating = h.overall_rating || null;
                        var numRatings = h.number_of_ratings || 0;
                        var sentiments = h.sentiments || {};

                        var ratingNum = parseFloat(h.rating) || 0;
                        h.rating = ratingNum;

                        var ratingBadge = '';
                        var displayRating = 0;
                        if (overallRating) {
                            displayRating = (overallRating / 20).toFixed(1);
                        } else if (ratingNum) {
                            displayRating = parseFloat(ratingNum).toFixed(1);
                        }
                        // Always show badge — Google will update it when photos load
                        var sc = displayRating >= 4 ? '#15803d' : displayRating >= 3 ? '#d97706' : '#64748b';
                        var ratingText = displayRating > 0 ? '★ ' + displayRating : '★ --';
                        ratingBadge = '<div class="ahr-rating" style="border-color:' + sc + ';color:' + sc + ';">' + ratingText + '</div>';

                        var sentimentTags = '';
                        if (Object.keys(sentiments).length) {
                            sentimentTags = Object.keys(sentiments)
                                .filter(function(k) {
                                    return sentiments[k] !== null && sentiments[k] >= 0;
                                })
                                .sort(function(a, b) {
                                    return sentiments[b] - sentiments[a];
                                })
                                .slice(0, 3)
                                .map(function(k) {
                                    return '<span style="font-size:12px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:2px 8px;">' + (sentimentIcons[k] || '·') + ' ' + k.replace(/([A-Z])/g, ' $1').replace(/^./, function(s) {
                                        return s.toUpperCase();
                                    }) + ' ' + sentiments[k] + '</span>';
                                }).join('');
                        }

                        var reviewsLabel = numRatings ? '<div style="position:absolute;top:44px;right:12px;font-size:11px;color:#94a3b8;">' + numRatings.toLocaleString() + ' reviews</div>' : '';
                        var originalPrice = h.price_raw ? (h.price_raw * 1.25).toFixed(2) : null;
                        var discount = originalPrice ? '20% OFF' : '';
                        var roomsLeft = h.rooms_available || 1;
                        if (roomsLeft > 9) roomsLeft = 9;

                        return '<div class="ahr-card">' +
                            '<div class="ahr-card-img">' +
                            '<div class="ahr-card-badge-top"><img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-465.png" width="15" height="20"> Top Booked</div>' +
                            '<div class="ahr-card-img-inner" id="ahr-img-wrap-' + h.hotelId + '" style="background:#f1f5f9;border-radius:10px;display:flex;align-items:center;justify-content:center;width:296px;height:274px;">' +
                            '<div class="ahr-img-spinner" style="width:32px;height:32px;border:3px solid #e2e8f0;border-top-color:#0e7d3f;border-radius:50%;animation:ahrSpin .8s linear infinite;"></div>' +
                            '</div>' +
                            '</div>' +
                            '<div class="ahr-card-body">' +
                            ratingBadge +
                            reviewsLabel +
                            '<div class="ahr-card-top-row">' +
                            '<span class="ahr-rooms-left"><i class="fa-solid fa-bed" style="color: rgb(255, 255, 255);"></i> ' + roomsLeft + ' Room' + (roomsLeft > 1 ? 's' : '') + ' Left</span>' +
                            '</div>' +
                            '<div class="ahr-card-name">' + h.name + '</div>' +
                            '<div class="ahr-review-count" style="font-size:11px;color:#94a3b8;display:none;margin-bottom:2px;"></div>' +
                            '<div class="ahr-card-address"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:1rem;margin-right:4px;"><path d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>' + h.address + '</div>' +
                            (sentimentTags ? '<div style="display:flex;flex-wrap:wrap;gap:5px;margin:4px 0;">' + sentimentTags + '</div>' : '') +
                            '<div class="ahr-card-amenities">' +
                            '<span title="SPA"><img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-31093.png" width="25" height="15"></span>' +
                            '<span title="Restaurant"><img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-31092.png" width="20" height="20"></span>' +
                            '<span title="Pool"><img src="https://honeydew-kingfisher-775674.hostingersite.com/wp-content/uploads/2026/06/Group-31131.png" width="20" height="20"></span>' +
                            '</div>' +
                            '<div class="ahr-card-bottom-row">' +
                            '<div class="ahr-price-block">' +
                            (originalPrice ? '<span class="ahr-price-original">$' + originalPrice + '</span>' : '') +
                            (discount ? '<span class="ahr-discount-badge">' + discount + '</span>' : '') +
                            '<div class="ahr-card-price">' + (h.price || 'N/A') + '<span class="ahr-per-night"> / Per Night</span></div>' +
                            '<div class="ahr-price-note">+ Taxes & Fees Per Night, Per Room</div>' +
                            '</div>' +
                            '<div class="ahr-card-actions">' +
                            '<button class="ahr-view-btn" onclick="ahrBook(\'' + h.hotelId + '\')">View Detail</button>' +
                            '<a class="ahr-book-btn" href="tel:+18777214100"><i class="fa-solid fa-phone" style="color: rgb(255, 255, 255);"></i> +1-877-721-0410</a>' +
                            '</div>' +
                            '</div>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                }

                // ── Filters ─────────────────────────────────
                window.ahrApplyFilters = function() {
                    var selectedStars = Array.from(document.querySelectorAll('.ahr-filter-star:checked')).map(function(el) {
                        return parseInt(el.value);
                    });
                    var cancelOnly = document.getElementById('ahr-filter-cancel').checked;
                    var maxPrice = parseInt(document.getElementById('ahr-price-range').value);
                    var minRatings = Array.from(document.querySelectorAll('.ahr-filter-user-rating:checked')).map(function(el) {
                        return parseFloat(el.value);
                    });
                    var minRating = minRatings.length ? Math.min.apply(null, minRatings) : 0;
                    document.getElementById('ahr-price-max-label').textContent = '$' + maxPrice + (maxPrice >= parseInt(document.getElementById('ahr-price-range').max) ? '+' : '');
                    var filtered = allHotels.filter(function(h) {
                        if (selectedStars.length) {
                            var star = parseInt(h.rating || 0);
                            if (!selectedStars.includes(star)) return false;
                        }
                        if (cancelOnly && !h.cancellable) return false;
                        if (h.price_raw && h.price_raw > maxPrice) return false;
                        if (minRating) {
                            var gr = parseFloat(h.google_rating || 0);
                            if (gr > 0 && gr < minRating) return false;
                        }
                        return true;
                    });
                    var chips = [];
                    if (cancelOnly) chips.push({
                        label: 'Free cancellation',
                        key: 'cancel'
                    });
                    selectedStars.forEach(function(s) {
                        chips.push({
                            label: s + ' Stars',
                            key: 'star-' + s
                        });
                    });
                    var chipsEl = document.getElementById('ahr-active-chips');
                    if (chipsEl) chipsEl.innerHTML = chips.map(function(c) {
                        return '<span class="ahr-chip">' + c.label + '<button class="ahr-chip-remove" onclick="ahrRemoveChip(\'' + c.key + '\')">×</button></span>';
                    }).join('');
                    renderResults(filtered);
                };
                window.ahrRemoveChip = function(key) {
                    if (key === 'cancel') document.getElementById('ahr-filter-cancel').checked = false;
                    else if (key.startsWith('star-')) {
                        var cb = document.querySelector('.ahr-filter-star[value="' + key.split('-')[1] + '"]:checked');
                        if (cb) cb.checked = false;
                    }
                    ahrApplyFilters();
                };
                window.ahrClearFilters = function() {
                    document.querySelectorAll('.ahr-filter-star,.ahr-filter-user-rating,.ahr-filter-amenity').forEach(function(el) {
                        el.checked = false;
                    });
                    ['ahr-filter-cancel', 'ahr-filter-prepay', 'ahr-filter-breakfast'].forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) el.checked = false;
                    });
                    var range = document.getElementById('ahr-price-range');
                    if (range) {
                        range.value = range.max;
                        document.getElementById('ahr-price-max-label').textContent = '$' + range.max + '+';
                    }
                    var chips = document.getElementById('ahr-active-chips');
                    if (chips) chips.innerHTML = '';
                    renderResults(allHotels);
                };
                var amenitiesExpanded = false;
                window.ahrToggleAmenities = function() {
                    amenitiesExpanded = !amenitiesExpanded;
                    document.querySelectorAll('.ahr-amenity-item').forEach(function(el, i) {
                        if (i >= 4) el.style.display = amenitiesExpanded ? 'flex' : 'none';
                    });
                    var btn = document.getElementById('ahr-show-amenities');
                    if (btn) btn.textContent = amenitiesExpanded ? '- Show less' : '+ Show all';
                };
                // ── Load real Google photos after render ────────
                function loadGooglePhotos(hotels) {
                    var queue = hotels.slice();
                    var delay = 0;
                    queue.forEach(function(h, idx) {
                        setTimeout(function() {
                            fetch(AJAXURL, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: new URLSearchParams({
                                        action: 'amadex_hotel_photo',
                                        nonce: NONCE,
                                        hotel_name: h.name || '',
                                        address: h.address || '',
                                    })
                                })
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (!data.success || !data.data) return;
                                    var gp = data.data;

                                    // Find image by hotel ID
                                    var wrap = document.getElementById('ahr-img-wrap-' + h.hotelId);
                                    if (wrap && gp.photos && gp.photos.length) {
                                        wrap.innerHTML = '<img id="ahr-img-' + h.hotelId + '" src="' + gp.photos[0] + '" alt="Hotel" data-hotel-id="' + h.hotelId + '" style="width:296px;height:274px;border-radius:10px;object-fit:cover;">';
                                        h.images = gp.photos;
                                    }

                                    if (gp.rating) {
                                        var wrap2 = document.getElementById('ahr-img-wrap-' + h.hotelId);
                                        var card = wrap2 ? wrap2.closest('.ahr-card') : null;
                                        if (card) {
                                            // Update rating badge
                                            var gr = parseFloat(gp.rating).toFixed(1);
                                            var sc = gr >= 4 ? '#15803d' : gr >= 3 ? '#d97706' : '#dc2626';
                                            var badge = card.querySelector('.ahr-rating');
                                            if (badge) {
                                                badge.style.borderColor = sc;
                                                badge.style.color = sc;
                                                badge.style.borderStyle = 'dashed';
                                                badge.innerHTML = '★ ' + gr;
                                            }

                                            // Update review count
                                            var reviewEl = card.querySelector('.ahr-review-count');
                                            if (reviewEl && gp.reviews) {
                                                reviewEl.textContent = gp.reviews.toLocaleString() + ' reviews';
                                                reviewEl.style.display = 'block';
                                            }

                                            // Update address with Google formatted address
                                            if (gp.address) {
                                                var addrEl = card.querySelector('.ahr-card-address');
                                                if (addrEl) {
                                                    addrEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:1rem;margin-right:4px;flex-shrink:0;"><path d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>' + gp.address;
                                                }
                                            }
                                        }
                                        h.google_rating = parseFloat(gp.rating);
                                        h.google_reviews = gp.reviews;
                                        h.google_address = gp.address;
                                        var hotelInList = allHotels.find(function(x) {
                                            return x.hotelId === h.hotelId;
                                        });
                                        if (hotelInList) hotelInList.google_rating = parseFloat(gp.rating);
                                    }
                                })
                                .catch(function() {});
                        }, delay);
                        delay += 300; // 300ms between each request to avoid rate limiting
                    });
                }


                window.ahrBook = function(hotelId) {
                    var hotel = allHotels.find(function(h) {
                        return h.hotelId === hotelId;
                    });
                    if (!hotel) return;
                    // Build offers array from raw data for rooms section
                    var detailData = Object.assign({}, hotel, {
                        offers: [], // don't pass fake offers — detail page fetches real ones
                        searchData: JSON.parse(sessionStorage.getItem('amadex_hotel_search') || '{}'),
                    });
                    try {
                        sessionStorage.setItem('amadex_hotel_detail', JSON.stringify(detailData));
                    } catch (e) {}
                    window.location.href = '/hotel-detail/';
                };
            })();

            // Adjust ahr-wrap margin when header becomes sticky scrolling-up
            (function() {
                var ahrWrap = document.querySelector('.ahr-wrap');
                var siteHeader = document.querySelector('.site-header');
                if (!ahrWrap || !siteHeader) return;

                function updateMargin() {
                    if (
                        siteHeader.classList.contains('is-sticky')
                    ) {
                        ahrWrap.style.marginTop = '16rem';
                    } else {
                        ahrWrap.style.marginTop = '';
                    }
                }

                new MutationObserver(function(mutations) {
                    mutations.forEach(function(m) {
                        if (m.attributeName === 'class') updateMargin();
                    });
                }).observe(siteHeader, { attributes: true });

                updateMargin();
            })();
        </script>
<?php
        return ob_get_clean();
    }

    public function search()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $keyword   = sanitize_text_field($_POST['keyword']   ?? '');
        $hotel_id  = sanitize_text_field($_POST['hotel_id']  ?? '');
        $city_post = sanitize_text_field($_POST['cityCode']  ?? '');
        $check_in  = sanitize_text_field($_POST['check_in']  ?? '');
        $check_out = sanitize_text_field($_POST['check_out'] ?? '');
        $adults    = max(1, intval($_POST['adults'] ?? 1));
        $rooms     = max(1, intval($_POST['rooms']  ?? 1));

        amadex_log('HotelResults: keyword=' . $keyword . ' hotel_id=' . $hotel_id . ' cityCode=' . $city_post . ' checkin=' . $check_in . ' checkout=' . $check_out);

        $token = $this->get_token();
        if (!$token) {
            wp_send_json_error(array('message' => 'Auth failed'));
            return;
        }

        $base    = $this->get_base_url();
        $headers = array('Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json');

        $a2c = array(
            'JFK' => 'NYC',
            'LGA' => 'NYC',
            'EWR' => 'NYC',
            'SMF' => 'SAC',  // Sacramento
            'SJC' => 'SJC',  // San Jose
            'OAK' => 'OAK',  // Oakland
            'PDX' => 'PDX',  // Portland
            'SLC' => 'SLC',  // Salt Lake City
            'PHX' => 'PHX',  // Phoenix
            'TUS' => 'TUS',  // Tucson
            'ABQ' => 'ABQ',  // Albuquerque
            'MSP' => 'MSP',  // Minneapolis
            'DTW' => 'DTW',  // Detroit
            'CLE' => 'CLE',  // Cleveland
            'PIT' => 'PIT',  // Pittsburgh
            'BNA' => 'BNA',  // Nashville
            'MCI' => 'MCI',  // Kansas City
            'STL' => 'STL',  // St Louis
            'MSY' => 'MSY',  // New Orleans
            'RDU' => 'RDU',  // Raleigh
            'CLT' => 'CLT',  // Charlotte
            'IAD' => 'WAS',
            'DCA' => 'WAS',
            'BWI' => 'WAS', // Washington DC
            'BOS' => 'BOS',  // Boston
            'PHL' => 'PHL',  // Philadelphia
            'TPA' => 'TPA',  // Tampa
            'MCO' => 'MCO',  // Orlando
            'FLL' => 'FLL',  // Fort Lauderdale
            'RSW' => 'RSW',  // Fort Myers
            'JAX' => 'JAX',  // Jacksonville
            'CMH' => 'CMH',  // Columbus
            'IND' => 'IND',  // Indianapolis
            'MKE' => 'MKE',  // Milwaukee
            'OMA' => 'OMA',  // Omaha
            'BUF' => 'BUF',  // Buffalo
            'CVG' => 'CVG',  // Cincinnati
            'MEM' => 'MEM',  // Memphis
            'BHM' => 'BHM',  // Birmingham
            'LIT' => 'LIT',  // Little Rock
            'OKC' => 'OKC',  // Oklahoma City
            'TUL' => 'TUL',  // Tulsa
            'ELP' => 'ELP',  // El Paso
            'SAT' => 'SAT',  // San Antonio
            'AUS' => 'AUS',  // Austin
            'HOU' => 'HOU',
            'IAH' => 'HOU', // Houston
            'HNL' => 'HNL',  // Honolulu
            'ANC' => 'ANC',  // Anchorage
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
            'YYZ' => 'YTO',
            'YVR' => 'YVR',
            'SYD' => 'SYD',
            'MEL' => 'MEL',
            'FCO' => 'ROM',
            'MXP' => 'MIL',
            'MAD' => 'MAD',
            'BCN' => 'BCN',
            'AMS' => 'AMS',
            'FRA' => 'FRA',
            'NRT' => 'TYO',
            'HND' => 'TYO',
            'ICN' => 'SEL',
            'PEK' => 'BJS',
            'HKG' => 'HKG',
            'KUL' => 'KUL',
        );

        $raw  = strtoupper(trim($city_post ?: $hotel_id ?: $keyword));
        $city = isset($a2c[$raw]) ? $a2c[$raw] : $raw;

        amadex_log('HotelResults: raw=' . $raw . ' resolved_city=' . $city);

        if (strlen($city) > 3) {
            $loc_resp = wp_remote_get($base . '/v1/reference-data/locations?' . http_build_query(array(
                'keyword' => $city,
                'subType' => 'CITY,AIRPORT',
                'page[limit]' => 5,
            )), array('headers' => $headers, 'timeout' => 15));

            if (!is_wp_error($loc_resp)) {
                $loc_body = wp_remote_retrieve_body($loc_resp);
                $loc      = json_decode($loc_body);
                amadex_log('HotelResults: locations body=' . substr($loc_body, 0, 300));
                if (!empty($loc->data)) {
                    foreach ($loc->data as $l) {
                        if (($l->subType ?? '') === 'CITY') {
                            $city = $l->iataCode;
                            break;
                        }
                        if (!empty($l->address->cityCode)) {
                            $city = $l->address->cityCode;
                            break;
                        }
                    }
                }
            }
        }

        amadex_log('HotelResults: final city_code=' . $city);

        $list_resp = wp_remote_get($base . '/v1/reference-data/locations/hotels/by-city?' . http_build_query(array(
            'cityCode' => $city,
            'radius' => 50,
            'radiusUnit' => 'KM',
            'hotelSource' => 'ALL',
        )), array('headers' => $headers, 'timeout' => 20));
        if (is_wp_error($list_resp)) {
            wp_send_json_error(array('message' => 'Hotel list failed'));
            return;
        }
        $list_body = wp_remote_retrieve_body($list_resp);
        $list      = json_decode($list_body);
        amadex_log('HotelResults: list count=' . count($list->data ?? array()) . ' body=' . substr($list_body, 0, 200));
        if (empty($list->data)) {
            wp_send_json_success(array());
            return;
        }

        // Store address data from list API (offers API doesn't return full address)
        $hotel_list_data = array();
        $all_ids = array();
        foreach ($list->data as $h) {
            $all_ids[] = $h->hotelId;
            $hotel_list_data[$h->hotelId] = array(
                'name'    => $h->name ?? '',
                'address' => trim(implode(', ', array_filter(array(
                    $h->address->lines[0]   ?? '',
                    $h->address->cityName   ?? '',
                    $h->address->stateCode  ?? '',
                    $h->address->countryCode ?? '',
                )))),
                'lat'  => $h->geoCode->latitude  ?? null,
                'lng'  => $h->geoCode->longitude ?? null,
            );
            if (count($all_ids) >= 20) break;
        }

        $all_offers = array();
        foreach (array_chunk($all_ids, 20) as $chunk) {
            $or = wp_remote_get($base . '/v3/shopping/hotel-offers?' . http_build_query(array(
                'hotelIds' => implode(',', $chunk),
                'checkInDate' => $check_in,
                'checkOutDate' => $check_out,
                'adults' => $adults,
                'roomQuantity' => $rooms,
                'currency' => 'USD',
                'bestRateOnly' => 'false',
            )), array('headers' => $headers, 'timeout' => 25));
            if (!is_wp_error($or)) {
                $ob = wp_remote_retrieve_body($or);
                $od = json_decode($ob);
                amadex_log('HotelResults: offers chunk=' . count($od->data ?? array()) . ' body=' . substr($ob, 0, 150));
                if (!empty($od->data)) $all_offers = array_merge($all_offers, $od->data);
            }
        }

        amadex_log('HotelResults: total offers=' . count($all_offers));

        $hotels = array();
        foreach ($all_offers as $offer) {
            $h    = $offer->hotel  ?? null;
            $ofrs = $offer->offers ?? array();
            if (!$h) continue;
            // Find lowest price offer
            $first = null;
            $lowest_price = PHP_FLOAT_MAX;
            foreach ($ofrs as $ofr) {
                $p = floatval($ofr->price->total ?? 0);
                if ($p > 0 && $p < $lowest_price) {
                    $lowest_price = $p;
                    $first = $ofr;
                }
            }
            if (!$first) $first = $ofrs[0] ?? null;

            $price_raw = 0;
            $price = null;
            $cancel = false;
            if ($first) {
                $price_total = floatval($first->price->total ?? 0);
                $currency    = $first->price->currency ?? 'USD';
                $nights = 1;
                if (!empty($check_in) && !empty($check_out)) {
                    try {
                        $ci = new DateTime($check_in);
                        $co = new DateTime($check_out);
                        $nights = max(1, $ci->diff($co)->days);
                    } catch (Exception $e) {
                    }
                }
                $price_raw = round($price_total / $nights, 2);
                $price_raw = $this->convert_to_usd($price_raw, $currency);
                if ($price_raw) $price = '$' . number_format($price_raw, 2);
                $cancel = empty($first->policies->cancellations ?? array());
            }
            $ap = array();
            if (!empty($h->address->lines)) foreach ($h->address->lines as $l) {
                if (trim($l)) $ap[] = trim($l);
            }
            if (!empty($h->address->cityName))    $ap[] = $h->address->cityName;
            if (!empty($h->address->countryCode)) $ap[] = $h->address->countryCode;
            $hid = $h->hotelId ?? '';
            // Use address from list API if offers API didn't return it
            $list_addr = $hotel_list_data[$hid]['address'] ?? '';
            $offers_addr = implode(', ', array_filter($ap));
            $addr = $offers_addr ?: $list_addr ?: ($h->cityCode ?? 'Address not available');
            $name = ($h->name ?? '') ?: ($hotel_list_data[$hid]['name'] ?? '');

            // Get number of available rooms from offers count
            $rooms_available = count($ofrs);

            $hotels[] = array(
                'name'          => $name,
                'address'       => $addr,
                'rating'        => intval($h->rating ?? $h->starRating ?? 0),
                'price'         => $price,
                'price_raw'     => $price_raw,
                'cancellable'   => $cancel,
                'hotelId'       => $h->hotelId ?? '',
                'rooms_available' => $rooms_available,
            );
        }
        usort($hotels, function ($a, $b) {
            return $a['price_raw'] - $b['price_raw'];
        });

        // Step 4 — Sentiment ratings in batches of 3
        if (!empty($hotels)) {
            $hmap = array();
            foreach ($hotels as $idx => $h) $hmap[$h['hotelId']] = $idx;
            foreach (array_chunk(array_keys($hmap), 3) as $chunk) {
                $sr = wp_remote_get(
                    $base . '/v2/e-reputation/hotel-sentiments?' . http_build_query(array('hotelIds' => implode(',', $chunk))),
                    array('headers' => $headers, 'timeout' => 15)
                );
                if (!is_wp_error($sr)) {
                    $sd = json_decode(wp_remote_retrieve_body($sr));
                    if (!empty($sd->data)) {
                        foreach ($sd->data as $s) {
                            $hid = $s->hotelId ?? '';
                            if (isset($hmap[$hid])) {
                                $i = $hmap[$hid];
                                $hotels[$i]['overall_rating']    = $s->overallRating ?? null;
                                $hotels[$i]['number_of_ratings'] = $s->numberOfRatings ?? 0;
                                $hotels[$i]['sentiments']        = array(
                                    'staff' => $s->sentiments->staff ?? null,
                                    'location' => $s->sentiments->location ?? null,
                                    'sleepQuality' => $s->sentiments->sleepQuality ?? null,
                                    'internet' => $s->sentiments->internet ?? null,
                                    'food' => $s->sentiments->food ?? null,
                                    'comfort' => $s->sentiments->comfort ?? null,
                                    'valueForMoney' => $s->sentiments->valueForMoney ?? null,
                                    'facilities' => $s->sentiments->facilities ?? null,
                                    'pools' => $s->sentiments->pools ?? null,
                                    'wellness' => $s->sentiments->wellness ?? null,
                                );
                            }
                        }
                    }
                }
            }
        }

        amadex_log('HotelResults: final count=' . count($hotels));
        // Prevent caching of hotel results
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        wp_send_json_success($hotels);
    }
    public function fetch_photo()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $hotel_name = sanitize_text_field($_POST['hotel_name'] ?? '');
        $address    = sanitize_text_field($_POST['address']    ?? '');
        $google_key = 'AIzaSyDf1tj8wdsAL1oPK8O9M0YFnfVPgSTMfYY';

        $find_resp = wp_remote_get(
            'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?' . http_build_query(array(
                'input'     => $hotel_name . ' ' . $address,
                'inputtype' => 'textquery',
                'fields'    => 'place_id,photos,rating,user_ratings_total,formatted_address',
                'key'       => $google_key,
            )),
            array('timeout' => 10)
        );
        if (is_wp_error($find_resp)) {
            wp_send_json_error();
            return;
        }

        $find = json_decode(wp_remote_retrieve_body($find_resp));
        if (empty($find->candidates[0])) {
            wp_send_json_error();
            return;
        }

        $place     = $find->candidates[0];
        $photos    = array();
        $rating    = $place->rating ?? null;
        $reviews   = $place->user_ratings_total ?? 0;

        if (!empty($place->photos)) {
            foreach (array_slice((array)$place->photos, 0, 4) as $p) {
                if (!empty($p->photo_reference)) {
                    $photos[] = 'https://maps.googleapis.com/maps/api/place/photo?' . http_build_query(array(
                        'maxwidth'        => 600,
                        'photo_reference' => $p->photo_reference,
                        'key'             => $google_key,
                    ));
                }
            }
        }

        wp_send_json_success(array(
            'photos'  => $photos,
            'rating'  => $rating,
            'reviews' => $reviews,
            'address' => $place->formatted_address ?? '',
        ));
    }
    private function convert_to_usd($amount, $currency)
    {
        if ($currency === 'USD' || $amount <= 0) return $amount;

        // Check cached rate
        $cache_key = 'amadex_fx_' . $currency;
        $rate = get_transient($cache_key);

        if ($rate === false) {
            $resp = wp_remote_get(
                'https://api.frankfurter.app/latest?from=' . $currency . '&to=USD',
                array('timeout' => 5)
            );
            if (!is_wp_error($resp)) {
                $data = json_decode(wp_remote_retrieve_body($resp));
                $rate = $data->rates->USD ?? null;
                if ($rate) {
                    // Cache for 24 hours
                    set_transient($cache_key, $rate, 24 * HOUR_IN_SECONDS);
                }
            }
        }

        if ($rate) return round($amount * $rate, 2);
        return $amount; // fallback — return original if API fails
    }

    public function search_locations()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        if (strlen($keyword) < 1) {
            wp_send_json_success(array());
            return;
        }

        $token = $this->get_token();
        if (!$token) {
            wp_send_json_success(array());
            return;
        }

        $base = $this->get_base_url();
        $resp = wp_remote_get($base . '/v1/reference-data/locations?' . http_build_query(array(
            'keyword'      => $keyword,
            'subType'      => 'CITY,AIRPORT',
            'page[limit]'  => 10,
            'view'         => 'LIGHT',
        )), array(
            'headers' => array('Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'),
            'timeout' => 10,
        ));

        if (is_wp_error($resp)) {
            wp_send_json_success(array());
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($resp));
        if (empty($data->data)) {
            wp_send_json_success(array());
            return;
        }

        $results = array();
        foreach ($data->data as $loc) {
            $subtype = $loc->subType ?? '';
            $city    = $loc->address->cityName    ?? ($loc->name ?? '');
            $country = $loc->address->countryName ?? '';
            $code    = $loc->iataCode             ?? '';
            $name    = $loc->name                 ?? '';

            if (!$code) continue;

            $results[] = array(
                'code'    => $code,
                'city'    => $city,
                'name'    => $subtype === 'CITY' ? ('All airports in ' . $city) : $name,
                'country' => $country,
                'type'    => $subtype,
            );
        }

        wp_send_json_success($results);
    }
    private function get_token()
    {
        $path = defined('AMADEX_PATH') ? AMADEX_PATH : plugin_dir_path(dirname(__FILE__)) . '../';
        foreach (array($path . 'includes/api/class-amadex-api.php', $path . 'includes/class-amadex-api.php') as $f) {
            if (file_exists($f)) {
                require_once $f;
                break;
            }
        }
        if (!class_exists('Amadex_API')) return null;
        $api = new Amadex_API();
        $t = $api->get_access_token();
        return is_wp_error($t) ? null : $t;
    }

    private function get_base_url()
    {
        $s = get_option('amadex_api_settings', array());
        $env = $s['environment'] ?? 'test';
        return ($env === 'live' || $env === 'production') ? 'https://api.amadeus.com' : 'https://test.api.amadeus.com';
    }
}
new Amadex_Hotel_Results();
