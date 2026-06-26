<?php

/**
 * Amadex Hotel Detail Page
 * Shortcode: [amadex_hotel_detail]
 * Reads amadex_hotel_detail from sessionStorage via JS
 */
if (!defined('ABSPATH')) exit;

class Amadex_Hotel_Detail
{
    public function __construct()
    {
        add_shortcode('amadex_hotel_detail', array($this, 'render'));
        add_action('wp_ajax_amadex_hotel_detail_fetch',        array($this, 'fetch_detail'));
        add_action('wp_ajax_nopriv_amadex_hotel_detail_fetch', array($this, 'fetch_detail'));
        add_action('wp_ajax_amadex_hotel_rooms',               array($this, 'fetch_rooms'));
        add_action('wp_ajax_nopriv_amadex_hotel_rooms',        array($this, 'fetch_rooms'));
        add_action('wp_ajax_amadex_hotel_places',              array($this, 'fetch_places'));
        add_action('wp_ajax_nopriv_amadex_hotel_places',       array($this, 'fetch_places'));
    }

    public function render($atts)
    {
        if (is_admin()) return '';
        $atts = shortcode_atts(array(
            'results_page' => site_url('/hotel-results/'),
        ), $atts, 'amadex_hotel_detail');
        ob_start(); ?>
        <style>
            .ahd-skel-shine {
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.7) 50%, transparent 100%);
                background-size: 200% 100%;
                animation: ahdShine 1.4s infinite;
            }

            @keyframes ahdShine {
                0% {
                    background-position: -200% 0;
                }

                100% {
                    background-position: 200% 0;
                }
            }

            .ahd-room-view-detail {
                width: 100%;
                padding: 10px;
                background: #fff;
                color: #0e7d3f;
                border: 2px solid #0e7d3f;
                border-radius: 10px;
                font-size: 13px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: all .15s;
                margin-top: 10px;
            }

            .ahd-room-view-detail:hover {
                background: #f0fdf4;
            }

            .ahd-room-popup-backdrop {
                position: fixed;
                inset: 0;
                background: rgb(0 0 0 / 83%);
                z-index: 99998;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                animation: ahdFadeIn .18s ease;
            }

            @keyframes ahdFadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            @keyframes ahdSlideUp {
                from {
                    transform: translateY(28px);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .ahd-room-popup {
                background: #fff;
                border-radius: 20px;
                max-width: 760px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 24px 60px rgba(0, 0, 0, .22);
                position: relative;
                animation: ahdSlideUp .22s ease;
                scrollbar-width: none;
            }

            .ahd-room-popup::-webkit-scrollbar {
                display: none;
            }

            .ahd-room-popup-close {
                position: absolute;
                top: 14px;
                right: 14px;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                border: none;
                background: rgba(255, 255, 255, .9);
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                color: #334155;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
                z-index: 10;
                line-height: 1;
            }

            .ahd-room-popup-close:hover {
                background: #f1f5f9;
            }

            .ahd-rp-gallery {
                position: relative;
                height: 355px;
                border-radius: 20px;
                margin: 8px;
                overflow: hidden;
                background: #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .ahd-rp-gallery img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: opacity .2s;
            }

            .ahd-rp-gallery-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 20px;
                height: 20px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .92);
                border: none;
                cursor: pointer;
                font-size: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .16);
                line-height: 1;
            }

            .ahd-rp-gallery-nav.prev {
                left: 12px;
            }

            .ahd-rp-gallery-nav.next {
                right: 12px;
            }

            .ahd-rp-gallery-dots {
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                gap: 5px;
            }

            .ahd-rp-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .55);
                transition: all .2s;
                cursor: pointer;
            }

            .ahd-rp-dot.active {
                background: #fff;
                width: 18px;
                border-radius: 4px;
            }

            .ahd-rp-body {
                padding: 22px 24px 28px;
            }

            .ahd-rp-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 14px;
                margin-bottom: 16px;
            }

            .ahd-rp-name {
                font-size: 20px;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
            }

            .ahd-rp-price-block {
                text-align: right;
                flex-shrink: 0;
            }

            .ahd-rp-orig {
                font-size: 12px;
                color: #94a3b8;
                text-decoration: line-through;
            }

            .ahd-rp-badge {
                display: inline-block;
                background: #EE9C31;
                color: #fff;
                font-size: 10px;
                font-weight: 700;
                padding: 2px 7px;
                border-radius: 20px;
                margin-left: 4px;
            }

            .ahd-rp-price {
                font-size: 26px;
                font-weight: 700;
                color: #0f172a;
                margin: 2px 0 1px;
                line-height: 1.2;
            }

            .ahd-rp-price span {
                font-size: 13px;
                font-weight: 500;
                color: #64748b;
            }

            .ahd-rp-tax {
                font-size: 11px;
                color: #94a3b8;
            }

            .ahd-rp-hotel-name {
                font-size: 24px;
                font-weight: 700;
                color: #0f172a;
                margin: 0 32px 14px 0;
                line-height: 1.3;
            }

            .ahd-rp-specs-row {
                display: flex;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
                margin-bottom: 6px;
            }

            .ahd-rp-spec {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                font-weight: 500;
                color: #334155;
            }

            .ahd-rp-spec-icon {
                width: 18px;
                text-align: center;
                flex-shrink: 0;
            }

            .ahd-rp-divider {
                border: none;
                border-top: 1px solid #e2e8f0;
                margin: 14px 0;
            }

            .ahd-rp-section {
                margin-bottom: 14px;
            }

            .ahd-rp-section-title {
                font-size: 11px;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.7px;
                margin: 0 0 8px;
            }

            .ahd-rp-items-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4px 8px;
            }

            .ahd-rp-item {
                display: flex;
                align-items: center;
                gap: 7px;
                font-size: 14px;
                color: #334155;
                padding: 2px 0;
            }

            .ahd-rp-item.struck {
                text-decoration: line-through;
                color: #94a3b8;
            }

            .ahd-rp-item svg {
                flex-shrink: 0;
            }

            .ahd-rp-policy-text {
                font-size: 13px;
                color: #475569;
                line-height: 1.55;
                margin: 0;
            }

            .ahd-rp-footer {
                display: flex;
                align-items: center;
                gap: 16px;
                margin-top: 20px;
                padding-top: 16px;
                border-top: 1px solid #e2e8f0;
            }

            .ahd-rp-book-btn {
                flex: 1;
                padding: 12px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
            }

            .ahd-rp-book-btn:hover {
                background: #0a6232;
            }

            .ahd-rp-call-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                flex: 1;
                padding: 12px;
                background: #fff;
                color: #0e7d3f;
                border: 2px solid #0e7d3f;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                text-decoration: none;
                transition: background .15s;
            }

            .ahd-rp-call-btn:hover {
                background: #f0fdf4;
                color: #0e7d3f;
            }

            .ahd-rp-amenities {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2px 16px;
            }

            .ahd-rp-amenity-row {
                display: flex;
                align-items: center;
                gap: 9px;
                padding: 7px 0;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
                font-weight: 600;
                color: #334155;
            }

            .ahd-rp-amenity-row span {
                font-size: 17px;
                flex-shrink: 0;
            }

            .ahd-rp-beds-row {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-bottom: 4px;
            }

            .ahd-rp-bed-pill {
                display: flex;
                align-items: center;
                gap: 6px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 600;
                color: #334155;
            }

            .ahd-rp-policies {
                background: #f8fafc;
                border-radius: 12px;
                padding: 14px 16px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .ahd-rp-policy-row {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                font-size: 13px;
                color: #334155;
                font-weight: 600;
            }

            .ahd-rp-policy-icon {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                flex-shrink: 0;
                margin-top: 1px;
            }

            .ahd-rp-cancel-ok {
                background: #f0fdf4;
            }

            .ahd-rp-cancel-no {
                background: #fef2f2;
            }

            .ahd-rp-cancel-sub {
                font-size: 11px;
                font-weight: 400;
                color: #94a3b8;
                display: block;
                margin-top: 2px;
            }

            .ahd-rp-footer {
                display: flex;
                gap: 10px;
                margin-top: 22px;
            }

            .ahd-rp-book-btn {
                flex: 1;
                padding: 13px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
            }

            .ahd-rp-book-btn:hover {
                background: #0a6232;
            }

            .ahd-rp-call-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                flex: 1;
                padding: 13px;
                background: #fff;
                color: #0e7d3f;
                border: 2px solid #0e7d3f;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                text-decoration: none;
                transition: background .15s;
            }

            .ahd-rp-call-btn:hover {
                background: #f0fdf4;
                color: #0e7d3f;
            }

            .ahd-wrap * {
                box-sizing: border-box;
            }

            .ahd-room-amenity-row i {
                color: #000 !important;
                font-size: 16px;
            }

            .ahd-wrap {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 1100px;
                margin: 0 auto;
                padding: 20px 16px 60px;
            }

            .ahd-back {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                font-weight: 600;
                color: #475569;
                text-decoration: none;
                margin-bottom: 20px;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
                font-family: inherit;
            }

            .ahd-back:hover {
                color: #0e7d3f;
            }

            .ahd-hero {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahd-header-row {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 12px;
                flex-wrap: wrap;
            }

            .ahd-hotel-name {
                font-size: 22px;
                color: #0f172a;
                line-height: 1.2;
                margin: 0 0 6px;
            }

            .ahd-hotel-address {
                font-size: 14px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .ahd-select-btn {
                padding: 10px 20px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 15px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                font-family: inherit;
                white-space: nowrap;
                flex-shrink: 0;
                transition: background .15s;
                width: 225px;
            }

            .ahd-select-btn:hover {
                background: #0a6232;
            }

            .ahd-rating-row {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
                flex-wrap: wrap;
            }

            .ahd-rating-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #f0fdf4;
                border: 2px dotted #15803d;
                color: #15803d;
                font-size: 13px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 20px;
            }

            .ahd-reviews-link {
                font-size: 13px;
                color: #0e7d3f;
                text-decoration: underline;
                cursor: pointer;
                font-weight: 600;
            }

            /* Reviews popup */
            .ahd-reviews-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .7);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                animation: ahdFadeIn .18s ease;
            }

            .ahd-reviews-modal {
                background: #fff;
                border-radius: 20px;
                max-width: 600px;
                width: 100%;
                max-height: 85vh;
                overflow-y: auto;
                box-shadow: 0 24px 60px rgba(0, 0, 0, .25);
                position: relative;
                animation: ahdSlideUp .22s ease;
                scrollbar-width: none;
            }

            .ahd-reviews-modal::-webkit-scrollbar {
                display: none;
            }

            .ahd-reviews-modal-header {
                padding: 20px 24px 16px;
                border-bottom: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: sticky;
                top: 0;
                background: #fff;
                z-index: 2;
                border-radius: 20px 20px 0 0;
            }

            .ahd-reviews-modal-title {
                font-size: 18px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahd-reviews-modal-close {
                width: 34px;
                height: 34px;
                border-radius: 50%;
                border: none;
                background: #f1f5f9;
                cursor: pointer;
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #334155;
            }

            @keyframes ahdSpin {
                to {
                    transform: rotate(360deg);
                }
            }

            .ahd-reviews-modal-body {
                padding: 20px 24px 28px;
            }

            .ahd-review-card {
                padding: 16px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahd-review-card:last-child {
                border-bottom: none;
            }

            .ahd-review-top {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
            }

            .ahd-review-avatar {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                object-fit: cover;
                background: #e2e8f0;
                flex-shrink: 0;
            }

            .ahd-review-author {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahd-review-time {
                font-size: 11px;
                color: #94a3b8;
            }

            .ahd-review-stars {
                color: #f59e0b;
                font-size: 13px;
                margin-bottom: 6px;
            }

            .ahd-review-text {
                font-size: 13px;
                color: #475569;
                line-height: 1.6;
            }

            .ahd-reviews-photos {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 8px;
                margin-bottom: 20px;
            }

            .ahd-reviews-photo {
                width: 100%;
                aspect-ratio: 1;
                object-fit: cover;
                border-radius: 8px;
                cursor: pointer;
            }

            .ahd-photos-container {
                display: flex;
                flex-direction: column;
                gap: 8px;
                border-radius: 12px;
                overflow: hidden;
                margin-bottom: 20px;
            }

            /* Top row: big left + smaller right */
            .ahd-photos-top {
                display: grid;
                grid-template-columns: 1.6fr 1fr;
                gap: 8px;
                height: 340px;
            }

            .ahd-photos-top div {
                position: relative;
                overflow: hidden;
                border-radius: 10px;
            }

            .ahd-photos-top img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            /* Bottom row: 3 equal images */
            .ahd-photos-bottom {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                height: 200px;
            }

            .ahd-photos-bottom div {
                overflow: hidden;
                border-radius: 10px;
            }

            .ahd-photos-bottom img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .ahd-photos-see-all {
                position: absolute;
                bottom: 10px;
                right: 8px;
                background: rgb(255 255 255);
                color: #000000;
                font-size: 12px;
                font-weight: 700;
                padding: 4px 12px;
                backdrop-filter: blur(4px);
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            /* .ahd-tabs {
                display: flex;
                border-bottom: 2px solid #e2e8f0;
                margin-bottom: 0;
                position: sticky;
                top: 0;
                background: #fff;
                z-index: 99;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
                margin-bottom: 20px;
            } */
            .ahd-tabs {
                display: flex;
                border-bottom: 2px solid #e2e8f0;
                margin-bottom: 0;
                background: #fff;
                z-index: 9999;
                transition: box-shadow .2s;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                flex-wrap: nowrap;
            }

            .ahd-tabs::-webkit-scrollbar {
                display: none;
            }

            .ahd-tab {
                flex-shrink: 0;
            }

            .ahd-tabs.is-sticky {
                position: fixed;
                top: 94px;
                left: 0;
                right: 0;
                box-shadow: 0 2px 12px rgba(0, 0, 0, .10);
                border-bottom: 2px solid #e2e8f0;
            }

            .ahd-tabs-spacer {
                display: none;
                height: 0;
            }

            .ahd-tabs-spacer.is-active {
                display: block;
            }

            .ahd-tab {
                padding: 12px 20px;
                font-size: 14px;
                font-weight: 600;
                color: #64748b;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                margin-bottom: -2px;
                transition: all .15s;
                background: none;
                border-top: none;
                border-left: none;
                border-right: none;
                font-family: inherit;
            }

            .ahd-tab:hover {
                color: #0e7d3f;
            }

            .ahd-tab.active {
                color: #0e7d3f;
                border-bottom-color: #0e7d3f;
            }

            /* ── Choose Your Room ── */
            .ahd-rooms-section {
                background: #d8ebdf;
                border: 1px solid #d8ebdf;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahd-rooms-title {
                font-size: 22px;
                color: #0f172a;
                margin: 0 0 16px;
            }

            .ahd-rooms-filter-bar {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 20px;
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .ahd-rooms-filter-bar::-webkit-scrollbar {
                display: none;
            }

            .ahd-rooms-filter-bar>* {
                flex-shrink: 0;
            }

            .ahd-rfbtn {
                padding: 7px 16px;
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

            .ahd-rfbtn:hover {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .ahd-rfbtn.active {
                background: #0e7d3f;
                border-color: #0e7d3f;
                color: #fff;
            }

            .ahd-rfbtn.all {
                background: #0e7d3f;
                border-color: #0e7d3f;
                color: #fff;
            }

            .ahd-rooms-clear {
                font-size: 13px;
                font-weight: 600;
                color: #0e7d3f;
                cursor: pointer;
                background: none;
                border: none;
                font-family: inherit;
                padding: 0;
            }

            .ahd-rooms-clear:hover {
                text-decoration: underline;
            }

            .ahd-rooms-count {
                margin-left: auto;
                font-size: 13px;
                color: #64748b;
                font-weight: 500;
                white-space: nowrap;
            }

            .ahd-rooms-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            }

            @media (max-width:900px) {
                div.ahd-tabs {
                    margin-bottom: 14px;
                    border-bottom: 0;
                }

                .ahd-location-section {
                    padding: 10px 15px !important;
                }

                .ahd-about-section {
                    padding: 10px 15px !important;
                }

                .ahd-amenities-section {
                    padding: 10px 15px !important;
                }

                .ahd-photos-top {
                    height: 150px !important;
                }

                .ahd-photos-bottom {
                    height: 135px !important;
                }

                button.ahd-select-btn {
                    display: none !important;
                }

                .ahd-rating-row {
                    justify-content: space-between;
                }

                div#ahr-sort-bar {
                    display: none !important;
                }

                .ahd-rooms-section {
                    padding: 10px;
                }

                .ahd-rp-footer {
                    flex-direction: row-reverse !important;
                    justify-content: space-between !important;
                }

                .ahd-rooms-section {
                    padding: 10px;
                }

                div#ahd-section-overview {
                    padding: 0 !important;
                    border: 0 !important;
                    box-shadow: none !important;
                }

                .ahd-photos-see-all {
                    display: none !important;
                }

                .ahd-rooms-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width:600px) {
                .ahd-rooms-grid {
                    grid-template-columns: 1fr;
                }
            }

            .ahd-room-card {
                border: 1.5px solid #e2e8f0;
                border-radius: 14px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                gap: 0;
                background: #fff;
                transition: box-shadow .15s;
                padding: 8px;
            }

            .ahd-room-img {
                position: relative;
                height: 180px;
                overflow: hidden;
                background: #f1f5f9;
                border-radius: 15px;
                margin-bottom: 18px;
            }

            .ahd-room-img img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .ahd-room-img-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(#00000014, rgb(0 0 0));
                padding: 30px 14px 12px;
            }

            .ahd-room-img-name {
                color: #fff;
                font-size: 15px;
                font-weight: 700;
                line-height: 1.3;
            }

            .ahd-room-img-count {
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(0, 0, 0, 0.55);
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                padding: 3px 8px;
                border-radius: 20px;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .ahd-room-body {
                padding: 0 4px;
                display: flex;
                flex-direction: column;
                flex: 1;
            }


            .ahd-room-card:hover {
                box-shadow: 0 4px 20px rgba(14, 125, 63, .10);
                border-color: #0e7d3f;
            }

            .ahd-room-name {
                font-size: 16px;
                font-weight: 600;
                color: #0f172a;
                line-height: 1.3;
                margin-bottom: 8px;
            }

            .ahd-room-parking {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                font-weight: 700;
                color: #0e7d3f;
                margin-bottom: 12px;
            }

            .ahd-room-amenity-row {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
                font-size: 13px;
                color: #334155;
                padding: 6px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .ahd-room-amenity-row:last-of-type {
                border-bottom: none;
            }

            .ahd-room-amenity-row span {
                font-size: 18px;
                flex-shrink: 0;
            }

            .ahd-room-cancel-ok {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                padding: 10px 0;
                font-size: 13px;
            }

            .ahd-room-cancel-ok-title {
                font-weight: 700;
                color: #15803d;
            }

            .ahd-room-cancel-ok-sub {
                font-size: 11px;
                color: #64748b;
                font-weight: 600;
                margin-top: 2px;
            }

            .ahd-room-cancel-no {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 10px 0;
                font-size: 13px;
                font-weight: 700;
                color: #dc2626;
            }

            .ahd-room-price-block {
                margin-top: auto;
                padding-top: 10px;
            }

            .ahd-room-orig {
                font-size: 13px;
                color: #94a3b8;
                text-decoration: line-through;
                margin-right: 6px;
            }

            .ahd-room-off {
                background: #EE9C31;
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                padding: 2px 8px;
                border-radius: 20px;
            }

            .ahd-room-price {
                font-size: 22px;
                color: #0f172a;
                font-weight: 600;
                margin: 4px 0 2px;
            }

            .ahd-room-price span {
                font-size: 13px;
                font-weight: 500;
                color: #64748b;
            }

            .ahd-room-tax {
                font-size: 11px;
                color: #94a3b8;
            }

            .ahd-room-book {
                width: 100%;
                padding: 12px;
                background: #0e7d3f;
                color: #fff;
                border: none;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                transition: background .15s;
                margin-top: 6px;
            }

            .ahd-room-book:hover {
                background: #0a6232;
            }

            .ahd-rooms-show-more {
                display: block;
                margin: 20px auto 0;
                padding: 10px 32px;
                background: #fff;
                border: 1.5px solid #e2e8f0;
                border-radius: 100px;
                font-size: 14px;
                font-weight: 600;
                color: #475569;
                cursor: pointer;
                font-family: inherit;
                transition: all .15s;
            }

            .ahd-rooms-show-more:hover {
                border-color: #0e7d3f;
                color: #0e7d3f;
            }

            .ahd-loading {
                text-align: center;
                padding: 80px 20px;
            }

            .ahd-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #e2e8f0;
                border-top-color: #0e7d3f;
                border-radius: 50%;
                animation: ahdSpin .8s linear infinite;
                margin: 0 auto 16px;
            }

            @keyframes ahdSpin {
                to {
                    transform: rotate(360deg);
                }
            }

            .ahd-loading p {
                color: #64748b;
                font-size: 14px;
            }

            .ahd-empty {
                text-align: center;
                padding: 80px 20px;
            }

            .ahd-empty h3 {
                font-size: 18px;
                font-weight: 700;
                color: #334155;
                margin-bottom: 8px;
            }

            /* ── Location Section ── */
            .ahd-location-section {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahd-location-title {
                font-size: 20px;
                color: #0f172a;
                margin: 0 0 4px;
            }

            .ahd-location-address {
                font-size: 13px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 4px;
                margin-bottom: 16px;
            }

            .ahd-map-wrap {
                border-radius: 12px;
                overflow: hidden;
                height: 300px;
                margin-bottom: 20px;
                border: 1px solid #e2e8f0;
            }

            .ahd-map-wrap iframe {
                width: 100%;
                height: 100%;
                border: none;
                display: block;
            }

            .ahd-landmarks {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            @media (max-width:600px) {
                .ahd-landmarks {
                    grid-template-columns: 1fr;
                }
            }

            .ahd-landmark-card {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .ahd-landmark-label {
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 4px;
            }

            .ahd-landmark-name {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
            }

            .ahd-landmark-dist-label {
                font-size: 11px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 4px;
                text-align: right;
            }

            .ahd-landmark-dist {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
                text-align: right;
            }

            /* ── Amenities & Facilities ── */
            .ahd-amenities-section {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahd-amenities-title {
                font-size: 20px;
                color: #0f172a;
                margin: 0 0 16px;
            }

            .ahd-amenities-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
                gap: 10px 16px;
            }

            .ahd-amenity-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                color: #334155;
                font-weight: 500;
            }

            .ahd-amenity-item span {
                font-size: 16px;
            }

            /* ── About ── */
            .ahd-about-section {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 20px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            }

            .ahd-about-title {
                font-size: 20px;
                color: #0f172a;
                margin: 0 0 16px;
            }

            .ahd-checkinout {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                background: #f8fafc;
                border-radius: 10px;
                padding: 16px;
                margin-bottom: 16px;
            }

            @media (max-width:600px) {
                .ahd-checkinout {
                    grid-template-columns: 1fr;
                }
            }

            .ahd-checkinout-label {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 4px;
            }

            .ahd-checkinout-val {
                font-size: 13px;
                color: #64748b;
            }

            .ahd-about-desc {
                font-size: 14px;
                color: #475569;
                line-height: 1.7;
                margin-bottom: 16px;
            }

            .ahd-policies-title {
                font-size: 16px;
                color: #0f172a;
                margin: 20px 0 12px;
            }

            .ahd-policy-block {
                margin-bottom: 14px;
            }

            .ahd-policy-block-title {
                font-size: 13px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 4px;
            }

            .ahd-policy-block-text {
                font-size: 13px;
                color: #64748b;
                line-height: 1.6;
            }

            /* ── Lightbox ── */
            .ahd-lightbox {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .95);
                z-index: 99999;
                display: none;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .ahd-lightbox.open {
                display: flex;
            }

            .ahd-lightbox-close {
                position: absolute;
                top: 16px;
                right: 20px;
                color: #fff;
                font-size: 32px;
                cursor: pointer;
                background: none;
                border: none;
                line-height: 1;
                z-index: 2;
                opacity: .8;
            }

            .ahd-lightbox-close:hover {
                opacity: 1;
            }

            .ahd-lightbox-counter {
                position: absolute;
                top: 20px;
                left: 20px;
                color: #fff;
                font-size: 14px;
                font-weight: 600;
                opacity: .7;
            }

            .ahd-lightbox-main {
                width: 100%;
                max-width: 900px;
                max-height: 70vh;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                padding: 0 60px;
            }

            .ahd-lightbox-main img {
                max-width: 100%;
                max-height: 70vh;
                object-fit: contain;
                border-radius: 8px;
                display: block;
                transition: opacity .2s;
            }

            .ahd-lightbox-prev,
            .ahd-lightbox-next {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(255, 255, 255, .15);
                border: none;
                color: #fff;
                font-size: 28px;
                width: 46px;
                height: 46px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background .15s;
                backdrop-filter: blur(4px);
            }

            .ahd-lightbox-prev {
                left: 8px;
            }

            .ahd-lightbox-next {
                right: 8px;
            }

            .ahd-lightbox-prev:hover,
            .ahd-lightbox-next:hover {
                background: rgba(255, 255, 255, .3);
            }

            .ahd-lightbox-thumbs {
                display: flex;
                gap: 8px;
                margin-top: 16px;
                overflow-x: auto;
                max-width: 900px;
                padding: 4px 0 8px;
                scrollbar-width: none;
            }

            .ahd-lightbox-thumbs::-webkit-scrollbar {
                display: none;
            }

            .ahd-lightbox-thumb {
                width: 70px;
                height: 50px;
                flex-shrink: 0;
                object-fit: cover;
                border-radius: 6px;
                cursor: pointer;
                opacity: .5;
                transition: opacity .15s;
                border: 2px solid transparent;
            }

            .ahd-lightbox-thumb.active {
                opacity: 1;
                border-color: #0e7d3f;
            }

            .ahd-lightbox-title {
                color: #fff;
                font-size: 15px;
                font-weight: 600;
                margin-top: 10px;
                opacity: .8;
            }

            .ahd-empty p {
                font-size: 14px;
                color: #94a3b8;
            }
        </style>

        <div class="ahd-wrap">
            <button class="ahd-back" onclick="history.back()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M19 12H5M12 5l-7 7 7 7" />
                </svg>
                Back to Hotels
            </button>
            <div id="ahd-main">
                <div class="ahd-loading">
                    <div class="ahd-spinner"></div>
                    <p>Loading hotel details...</p>
                </div>
            </div>
            <div id="ahd-rooms-wrap"></div>
        </div>

        <!-- Lightbox -->
        <div class="ahd-lightbox" id="ahd-lightbox">
            <button class="ahd-lightbox-close" onclick="ahdLightboxClose()">×</button>
            <span class="ahd-lightbox-counter" id="ahd-lb-counter"></span>
            <div class="ahd-lightbox-main">
                <button class="ahd-lightbox-prev" onclick="ahdLightboxNav(-1)">&#8249;</button>
                <img id="ahd-lb-img" src="" alt="Hotel">
                <button class="ahd-lightbox-next" onclick="ahdLightboxNav(1)">&#8250;</button>
            </div>
            <div class="ahd-lightbox-thumbs" id="ahd-lb-thumbs"></div>
            <div class="ahd-lightbox-title" id="ahd-lb-title"></div>
        </div>

        <script>
            (function() {
                var AJAXURL = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
                var NONCE = <?php echo json_encode(wp_create_nonce('amadex_nonce')); ?>;

                var hotelData = null;
                try {
                    var raw = sessionStorage.getItem('amadex_hotel_detail');
                    if (raw) hotelData = JSON.parse(raw);
                } catch (e) {}
                if (!hotelData) {
                    document.getElementById('ahd-main').innerHTML = '<div class="ahd-empty"><h3>No hotel selected</h3><p>Please go back and select a hotel.</p></div>';
                    return;
                }

                var placeholderImgs = [];

                var overallRating = hotelData.overall_rating || null;
                var numRatings = hotelData.number_of_ratings || 0;
                var displayRating = overallRating ? (overallRating / 20).toFixed(1) : (hotelData.rating || '');
                var ratingLabel = displayRating >= 4.5 ? 'Exceptional' : displayRating >= 4 ? 'Excellent' : displayRating >= 3.5 ? 'Very Good' : displayRating >= 3 ? 'Good' : 'Pleasant';
                var ratingHtml = displayRating ?
                    '<div class="ahd-rating-badge">★ ' + displayRating + ' ' + ratingLabel + '</div>' +
                    (numRatings ? '<span class="ahd-reviews-link">See all ' + numRatings.toLocaleString() + ' reviews</span>' : '') :
                    '';

                // Fetch real photos + rating from Google Places
                fetch(AJAXURL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'amadex_hotel_places',
                            nonce: NONCE,
                            hotel_name: hotelData.name || '',
                            address: hotelData.address || '',
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (!data.success || !data.data) return;
                        var gp = data.data;

                        // Update rating badge with Google rating
                        if (gp.rating) {
                            var gr = parseFloat(gp.rating).toFixed(1);
                            var grLabel = gr >= 4.5 ? 'Exceptional' : gr >= 4 ? 'Excellent' : gr >= 3.5 ? 'Very Good' : 'Good';
                            var grCount = gp.total_ratings ? gp.total_ratings.toLocaleString() + ' reviews' : '';
                            document.querySelector('.ahd-rating-row').innerHTML =
                                '<div class="ahd-rating-badge">★ ' + gr + ' ' + grLabel + '</div>' +
                                (grCount ? '<span class="ahd-reviews-link">See all ' + grCount + '</span>' : '');
                        }

                        // Update photos
                        if (gp.photos && gp.photos.length) {
                            var imgs = gp.photos.slice();
                            // Pad with grey boxes if less than 5 photos returned
                            while (imgs.length < 5) imgs.push(null);
                            var pc = document.querySelector('.ahd-photos-container');
                            var allPhotos = gp.photos;
                            var photoCount = allPhotos.length;
                            // Store photos globally so onclick can reference safely
                            window._ahdCurrentPhotos = allPhotos;

                            function imgDiv(idx) {
                                return imgs[idx] ?
                                    '<div style="cursor:pointer;" onclick="ahdLightboxOpen(window._ahdCurrentPhotos,' + idx + ')"><img src="' + imgs[idx] + '" alt="Hotel" style="width:100%;height:100%;object-fit:cover;display:block;"></div>' :
                                    '<div style="background:#f1f5f9;border-radius:10px;"></div>';
                            }
                            if (pc) pc.innerHTML =
                                '<div class="ahd-photos-top">' +
                                imgDiv(0) + imgDiv(1) +
                                '</div>' +
                                '<div class="ahd-photos-bottom">' +
                                imgDiv(2) + imgDiv(3) +
                                (imgs[4] ?
                                    '<div style="position:relative;cursor:pointer;" onclick="ahdLightboxOpen(window._ahdCurrentPhotos,4)">' +
                                    '<img src="' + imgs[4] + '" alt="Hotel" style="width:100%;height:100%;object-fit:cover;display:block;">' +
                                    '<div class="ahd-photos-see-all" onclick="event.stopPropagation();ahdLightboxOpen(window._ahdCurrentPhotos,0)">📷 See all Photos ' + photoCount + '</div>' +
                                    '</div>' :
                                    '<div style="background:#f1f5f9;border-radius:10px;"></div>') +
                                '</div>';
                        }

                        // Store reviews for later use
                        if (gp.reviews && gp.reviews.length) {
                            hotelData.google_reviews = gp.reviews;
                        }

                        // Render location section
                        if (gp.lat && gp.lng) {
                            renderLocationSection(gp);
                        }
                        // Render amenities & about sections
                        renderAmenitiesSection(gp);
                        renderAboutSection(gp);
                    })
                    .catch(function() {});

                // Skeleton shown until Google Places photos load
                var photosHtml =
                    '<div class="ahd-photos-container">' +
                    '<div class="ahd-photos-top">' +
                    '<div style="background:#f1f5f9;border-radius:10px;height:100%;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div>' +
                    '<div style="background:#f1f5f9;border-radius:10px;height:100%;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div>' +
                    '</div>' +
                    '<div class="ahd-photos-bottom">' +
                    '<div style="background:#f1f5f9;border-radius:10px;height:100%;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div>' +
                    '<div style="background:#f1f5f9;border-radius:10px;height:100%;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div>' +
                    '<div style="background:#f1f5f9;border-radius:10px;height:100%;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div>' +
                    '</div>' +
                    '</div>';

                document.getElementById('ahd-main').innerHTML =
                    '<div class="ahd-hero" id="ahd-section-overview">' +
                    '<div class="ahd-header-row">' +
                    '<div>' +
                    '<h1 class="ahd-hotel-name">' + (hotelData.name || 'Hotel') + '</h1>' +
                    '<div class="ahd-hotel-address">' +
                    '<svg width="14" height="14" viewBox="0 0 640 640" style="flex-shrink:0;fill:#64748b;"><path d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>' +
                    (hotelData.address || '') +
                    '</div>' +
                    '</div>' +
                    '<button class="ahd-select-btn" onclick="ahdScrollToRooms()">Select Room</button>' +
                    '</div>' +
                    '<div class="ahd-rating-row">' + ratingHtml + '</div>' +
                    photosHtml +
                    '</div>' +
                    '<div class="ahd-tabs-spacer" id="ahd-tabs-spacer"></div>' +
                    '<div class="ahd-tabs" id="ahd-tabs">' +
                    '<button class="ahd-tab active" onclick="ahdTab(this,\'overview\')">Overview</button>' +
                    '<button class="ahd-tab" onclick="ahdTab(this,\'rooms\')">Rooms</button>' +
                    '<button class="ahd-tab" onclick="ahdTab(this,\'location\')">Location</button>' +
                    '<button class="ahd-tab" onclick="ahdTab(this,\'amenities\')">Amenities & Facilities</button>' +
                    '<button class="ahd-tab" onclick="ahdTab(this,\'about\')">About</button>' +
                    '</div>';

                // Sticky tabs scroll logic
                (function() {
                    var tabs = document.getElementById('ahd-tabs');
                    var spacer = document.getElementById('ahd-tabs-spacer');
                    if (!tabs) return;

                    var tabsTop = 0;
                    var tabsHeight = 0;

                    function recalc() {
                        if (!tabs.classList.contains('is-sticky')) {
                            tabsTop = tabs.getBoundingClientRect().top + window.scrollY;
                            tabsHeight = tabs.offsetHeight;
                        }
                    }

                    function getHeaderHeight() {
                        var header = document.querySelector('.site-header');
                        if (!header) return 94;
                        return header.offsetHeight;
                    }

                    function onScroll() {
                        if (!tabsTop) recalc();
                        var headerHeight = getHeaderHeight();
                        if (window.scrollY >= tabsTop - headerHeight) {
                            tabs.classList.add('is-sticky');
                            tabs.style.top = headerHeight + 'px';
                            spacer.style.height = tabsHeight + 'px';
                            spacer.classList.add('is-active');
                        } else {
                            tabs.classList.remove('is-sticky');
                            tabs.style.top = '';
                            spacer.classList.remove('is-active');
                            spacer.style.height = '0';
                        }
                    }

                    // Watch for sticky header class changes and adjust ahr-wrap margin
                    var ahrWrap = document.querySelector('.ahr-wrap');
                    var siteHeader = document.querySelector('.site-header');

                    function updateAhrMargin() {
                        if (!ahrWrap || !siteHeader) return;
                        if (
                            siteHeader.classList.contains('is-sticky') &&
                            siteHeader.classList.contains('scrolling-up')
                        ) {
                            ahrWrap.style.marginTop = '16rem';
                        } else {
                            ahrWrap.style.marginTop = '';
                        }
                    }

                    if (siteHeader) {
                        var headerObserver = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'class') {
                                    updateAhrMargin();
                                    recalc();
                                    onScroll();
                                }
                            });
                        });
                        headerObserver.observe(siteHeader, {
                            attributes: true
                        });
                    }

                    setTimeout(recalc, 300);
                    window.addEventListener('scroll', onScroll, {
                        passive: true
                    });
                    window.addEventListener('resize', function() {
                        recalc();
                        updateAhrMargin();
                    });
                    updateAhrMargin();
                })();

                window.ahdTab = function(btn, section) {
                    document.querySelectorAll('.ahd-tab').forEach(function(t) {
                        t.classList.remove('active');
                    });
                    btn.classList.add('active');
                    if (section) {
                        var el = document.getElementById('ahd-section-' + section);
                        if (el) el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                };
                window.ahdScrollToRooms = function() {
                    var el = document.getElementById('ahd-rooms-wrap');
                    if (el) el.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                };

                // ── Fetch real rooms from Amadeus ──────────────
                var searchData = hotelData.searchData || {};
                var allRooms = [];
                var shownCount = 6;

                fetchRooms();

                function fetchRooms() {
                    document.getElementById('ahd-rooms-wrap').innerHTML =
                        '<div class="ahd-rooms-section"><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding:8px 0;">' +
                        '<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;"><div style="height:180px;background:#f1f5f9;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="padding:12px;display:flex;flex-direction:column;gap:8px;"><div style="height:14px;width:70%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:50%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:60%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:22px;width:40%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div><div style="height:38px;background:#f1f5f9;border-radius:10px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div></div></div>' +
                        '<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;"><div style="height:180px;background:#f1f5f9;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="padding:12px;display:flex;flex-direction:column;gap:8px;"><div style="height:14px;width:70%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:50%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:60%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:22px;width:40%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div><div style="height:38px;background:#f1f5f9;border-radius:10px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div></div></div>' +
                        '<div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;overflow:hidden;"><div style="height:180px;background:#f1f5f9;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="padding:12px;display:flex;flex-direction:column;gap:8px;"><div style="height:14px;width:70%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:50%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:12px;width:60%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;"><div class="ahd-skel-shine"></div></div><div style="height:22px;width:40%;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div><div style="height:38px;background:#f1f5f9;border-radius:10px;position:relative;overflow:hidden;margin-top:4px;"><div class="ahd-skel-shine"></div></div></div></div>' +
                        '</div></div>';

                    fetch(AJAXURL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'amadex_hotel_rooms',
                                nonce: NONCE,
                                hotel_id: hotelData.hotelId || '',
                                check_in: searchData.checkIn || '',
                                check_out: searchData.checkOut || '',
                                adults: searchData.adults || 1,
                                rooms: searchData.rooms || 1,
                            })
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(data) {
                            if (!data.success || !data.data || !data.data.length) {
                                renderFallbackRooms();
                                return;
                            }
                            allRooms = data.data;
                            renderRooms(allRooms.slice(0, shownCount));
                        })
                        .catch(function() {
                            renderFallbackRooms();
                        });
                }

                function renderFallbackRooms() {
                    // Fallback using data from results page
                    var price = hotelData.price_raw || 99;
                    allRooms = [{
                            room_name: 'Standard Room',
                            price_raw: price,
                            cancellable: true,
                            cancel_date: 'Wed, 3 Jun',
                            bed: '1 King Bed'
                        },
                        {
                            room_name: 'Deluxe Room',
                            price_raw: price + 30,
                            cancellable: true,
                            cancel_date: 'Wed, 3 Jun',
                            bed: '1 King Bed'
                        },
                        {
                            room_name: 'Suite',
                            price_raw: price + 80,
                            cancellable: false,
                            cancel_date: '',
                            bed: '2 Queen Beds'
                        },
                    ];
                    renderRooms(allRooms.slice(0, shownCount));
                }

                function renderRooms(rooms) {
                    var total = allRooms.length;
                    var amenities = [{
                            icon: '🧖',
                            label: 'Spa'
                        },
                        {
                            icon: '🏊',
                            label: 'Pool'
                        },
                        {
                            icon: '🏋',
                            label: 'Fitness centre'
                        },
                        {
                            icon: '🛏',
                            label: '1 King Bed'
                        },
                        {
                            icon: '🚌',
                            label: 'Airport transport (surcharge)'
                        },
                    ];

                    var cardsHtml = rooms.map(function(r, idx) {
                        var pr = parseFloat(r.price_raw || 0);
                        var orig = (pr * 1.25).toFixed(2);
                        var bed = r.bed || r.bed_type || '1 King Bed';
                        var board = r.board || '';

                        var amenList = [{
                                icon: '<i class="fa-solid fa-spa" style="color: rgb(255, 255, 255);"></i>',
                                label: 'Spa'
                            },
                            {
                                icon: '<i class="fa-solid fa-person-swimming" style="color: rgb(255, 255, 255);"></i>',
                                label: 'Pool'
                            },
                            {
                                icon: '<i class="fa-solid fa-dumbbell" style="color: rgb(255, 255, 255);"></i>',
                                label: 'Fitness centre'
                            },
                            {
                                icon: '<i class="fa-solid fa-bed" style="color: rgb(255, 255, 255);"></i>',
                                label: bed
                            },
                            {
                                icon: '<i class="fa-solid fa-van-shuttle" style="color: rgb(255, 255, 255);"></i>',
                                label: 'Airport transport (surcharge)'
                            },
                        ];
                        if (board && board !== 'Room Only') amenList.push({
                            icon: '<i class="fa-solid fa-bowl-food" style="color: rgb(255, 255, 255);"></i>',
                            label: board
                        });

                        var amenRows = amenList.map(function(a) {
                            return '<div class="ahd-room-amenity-row"><span>' + a.icon + '</span>' + a.label + '</div>';
                        }).join('');

                        var cancelHtml = r.cancellable ?
                            '<div class="ahd-room-cancel-ok">' +
                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10" fill="#0e7d3f"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>' +
                            '<div><div class="ahd-room-cancel-ok-title">Fully refundable</div><div class="ahd-room-cancel-ok-sub">Before ' + (r.cancel_date || 'Wed, 3 Jun') + '</div></div>' +
                            '</div>' :
                            '<div class="ahd-room-cancel-no">' +
                            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="flex-shrink:0;"><circle cx="12" cy="12" r="10" fill="#dc2626"/><path d="M8 8l8 8M16 8l-8 8" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>' +
                            'Non Refundable</div>';

                        // Get photo — use Google Places photos stored globally
                        var photos = window._ahdCurrentPhotos || [];
                        var roomPhoto = photos[idx % photos.length] || null;
                        var photoCount = photos.length;

                        var imgHtml = roomPhoto ?
                            '<div class="ahd-room-img">' +
                            '<img src="' + roomPhoto + '" alt="' + (r.room_name || 'Room') + '">' +
                            '<div class="ahd-room-img-overlay">' +
                            '<div class="ahd-room-img-name">' + (r.room_name || 'Room') + '</div>' +
                            '</div>' +
                            (photoCount > 1 ? '<div class="ahd-room-img-count">📷 ' + photoCount + '+</div>' : '') +
                            '</div>' :
                            '<div class="ahd-room-img" style="display:flex;align-items:center;justify-content:center;">' +
                            '<span style="font-size:40px;opacity:.2;">🏨</span>' +
                            '<div class="ahd-room-img-overlay">' +
                            '<div class="ahd-room-img-name">' + (r.room_name || 'Room') + '</div>' +
                            '</div>' +
                            '</div>';

                        return '<div class="ahd-room-card">' +
                            imgHtml +
                            '<div class="ahd-room-body">' +
                            (roomPhoto ? '' : '<div class="ahd-room-name">' + (r.room_name || 'Room') + '</div>') +
                            '<div class="ahd-room-parking">' +
                            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#0e7d3f"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="11" font-weight="bold">P</text></svg>' +
                            'Parking Included' +
                            '</div>' +
                            '<div>' + amenRows + '</div>' +
                            cancelHtml +
                            '<div class="ahd-room-price-block">' +
                            '<span class="ahd-room-orig">$' + orig + '</span><span class="ahd-room-off">20% OFF</span>' +
                            '<div class="ahd-room-price">$' + pr.toFixed(2) + '<span>/ Per Night</span></div>' +
                            (r.taxes ? '<div class="ahd-room-tax">+$' + r.taxes + ' Taxes & Fees Per Night, Per Room</div>' : '<div class="ahd-room-tax">+$54 Taxes & Fees Per Night, Per Room</div>') +
                            '</div>' +
                            '<button class="ahd-room-view-detail" onclick="ahdOpenRoomPopup(\'' + idx + '\')">View Detail</button>' +
                            '<button class="ahd-room-book" onclick="ahdBookRoom(\'' + idx + '\')">Book Now</button>' +
                            '</div>' +
                            '</div>';
                    }).join('');

                    var showMoreBtn = allRooms.length > shownCount ?
                        '<button class="ahd-rooms-show-more" onclick="ahdShowMore()">Show more</button>' :
                        '';

                    document.getElementById('ahd-rooms-wrap').innerHTML =
                        '<div class="ahd-rooms-section" id="ahd-section-rooms">' +
                        '<h2 class="ahd-rooms-title">Choose Your Room</h2>' +
                        '<div class="ahd-rooms-filter-bar">' +
                        '<button class="ahd-rfbtn all" onclick="ahdRoomFilter(this,\'all\')">All</button>' +
                        '<button class="ahd-rfbtn" onclick="ahdRoomFilter(this,\'breakfast\')">Breakfast Included</button>' +
                        '<button class="ahd-rfbtn" onclick="ahdRoomFilter(this,\'refundable\')">Refundable</button>' +
                        '<button class="ahd-rfbtn" onclick="ahdRoomFilter(this,\'nonsmoking\')">Non-smoking</button>' +
                        '<button class="ahd-rfbtn" onclick="ahdRoomFilter(this,\'nearest\')">Nearest Hotel</button>' +
                        '<button class="ahd-rfbtn" onclick="ahdRoomFilter(this,\'reviewed\')">Top reviewed</button>' +
                        '<button class="ahd-rooms-clear" onclick="ahdClearRoomFilters()">Clear all</button>' +
                        '<span class="ahd-rooms-count">Showing ' + rooms.length + ' of ' + total + ' Rooms</span>' +
                        '</div>' +
                        '<div class="ahd-rooms-grid">' + cardsHtml + '</div>' +
                        showMoreBtn +
                        '</div>';
                }

                window.ahdShowMore = function() {
                    shownCount += 6;
                    renderRooms(allRooms.slice(0, shownCount));
                };

                window.ahdRoomFilter = function(btn, type) {
                    document.querySelectorAll('.ahd-rfbtn').forEach(function(b) {
                        b.classList.remove('active', 'all');
                    });
                    btn.classList.add('active');
                    var filtered = type === 'all' ? allRooms :
                        type === 'refundable' ? allRooms.filter(function(r) {
                            return r.cancellable;
                        }) :
                        allRooms;
                    shownCount = 6;
                    renderRooms(filtered.slice(0, shownCount));
                };

                window.ahdClearRoomFilters = function() {
                    document.querySelectorAll('.ahd-rfbtn').forEach(function(b) {
                        b.classList.remove('active', 'all');
                    });
                    document.querySelector('.ahd-rfbtn').classList.add('all');
                    shownCount = 6;
                    renderRooms(allRooms.slice(0, shownCount));
                };

                window.ahdBookRoom = function(idx) {
                    var room = allRooms[idx] || allRooms[0];
                    try {
                        sessionStorage.setItem('amadex_booking_room', JSON.stringify(room));
                    } catch (e) {}
                    window.location.href = '/hotel-booking/';
                };

                var rpCurrentPhoto = 0;
                var rpPhotos = [];

                var rpCurrentPhoto = 0;
                var rpPhotos = [];

                window.ahdOpenRoomPopup = function(idx) {
                    var room = allRooms[idx] || allRooms[0];
                    rpPhotos = window._ahdCurrentPhotos || [];
                    rpCurrentPhoto = idx % (rpPhotos.length || 1);
                    var hotelName = room.room_name || (hotelData && hotelData.name) ? room.room_name || 'Standard Room' : 'Hotel Room';
                    var bed = room.bed || room.bed_type || '1 King Bed';
                    var pr = parseFloat(room.price_raw || 0);
                    var cancel = room.cancellable;

                    function checkIcon(struck) {
                        var color = struck ? '#94a3b8' : '#0e7d3f';
                        return '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="' + color + '"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>';
                    }

                    function section(title, items) {
                        // items: array of {label, struck}
                        var rows = items.map(function(it) {
                            return '<div class="ahd-rp-item' + (it.struck ? ' struck' : '') + '">' + checkIcon(it.struck) + it.label + '</div>';
                        }).join('');
                        return '<div class="ahd-rp-section"><div class="ahd-rp-section-title">' + title + '</div><div class="ahd-rp-items-grid">' + rows + '</div></div>';
                    }

                    var boardLabel = room.board && room.board !== 'Room Only' ? room.board : null;

                    var sectionsHtml =
                        section('Toiletries', [{
                                label: 'Toothbrushes',
                                struck: true
                            },
                            {
                                label: 'Toothpaste',
                                struck: true
                            },
                            {
                                label: 'Body Wash',
                                struck: false
                            },
                            {
                                label: 'Shampoo',
                                struck: false
                            },
                            {
                                label: 'Conditioner',
                                struck: false
                            },
                            {
                                label: 'Soap',
                                struck: false
                            },
                        ]) +
                        section('Room Layout And Furnishings', [{
                                label: 'Wardrobe',
                                struck: false
                            },
                            {
                                label: 'Desk',
                                struck: false
                            },
                        ]) +
                        section('Internet And Communications', [{
                                label: 'Wi-Fi In Room',
                                struck: false
                            },
                            {
                                label: 'Telephone',
                                struck: false
                            },
                        ]) +
                        section('Food And Drink', [{
                                label: 'Coffee Maker/Teapot',
                                struck: false
                            },
                            {
                                label: 'Tea Bags',
                                struck: false
                            },
                            {
                                label: 'Bottled Water Free',
                                struck: false
                            },
                            {
                                label: 'Electric Kettle',
                                struck: false
                            },
                            {
                                label: 'Minibar',
                                struck: false
                            },
                            (boardLabel ? {
                                label: boardLabel,
                                struck: false
                            } : null),
                        ].filter(Boolean)) +
                        section('Bathroom', [{
                                label: 'Private Bathroom',
                                struck: false
                            },
                            {
                                label: 'Private Toilet',
                                struck: false
                            },
                            {
                                label: 'Bathrobes',
                                struck: false
                            },
                            {
                                label: 'Towels',
                                struck: false
                            },
                            {
                                label: 'Hot Water (24 Hours)',
                                struck: false
                            },
                        ]) +
                        section('Room Amenities', [{
                                label: 'Air Conditioning',
                                struck: false
                            },
                            {
                                label: 'Curtains',
                                struck: false
                            },
                            {
                                label: 'Bedding: Blanket Or Quilt',
                                struck: false
                            },
                        ]) +
                        section('Media And Technology', [{
                                label: 'TV',
                                struck: false
                            },
                            {
                                label: 'Cable Channels',
                                struck: false
                            },
                            {
                                label: 'Satellite Channels',
                                struck: false
                            },
                        ]) +
                        section('Kitchen Facilities', [{
                            label: 'Refrigerator',
                            struck: false
                        }, ]) +
                        section('Cleaning Services', [{
                            label: 'Iron And Ironing Board',
                            struck: false
                        }, ]) +
                        section('General Amenities', [{
                            label: 'Safe In Room',
                            struck: false
                        }, ]);

                    var cancelPolicy = cancel ?
                        'Free cancellation available. Cancel before check-in for a full refund.' :
                        'This room is non-refundable. Cancellation or changes are not permitted.' + (room.cancel_date ? ' Deadline was ' + room.cancel_date + '.' : '');

                    var popup = document.createElement('div');
                    popup.className = 'ahd-room-popup-backdrop';
                    popup.id = 'ahd-room-popup-backdrop';
                    var imgSrc = rpPhotos[rpCurrentPhoto] || '';
                    var galleryHtml = imgSrc ?
                        '<div class="ahd-rp-gallery">' +
                        '<img id="ahd-rp-img" src="' + imgSrc + '" alt="' + hotelName + '" style="width:100%;height:100%;object-fit:cover;display:block;transition:opacity .2s;">' +
                        (rpPhotos.length > 1 ?
                            '<button class="ahd-rp-gallery-nav prev" onclick="ahdRpSlide(-1)">&#8249;</button>' +
                            '<button class="ahd-rp-gallery-nav next" onclick="ahdRpSlide(1)">&#8250;</button>' +
                            '<div class="ahd-rp-gallery-dots">' +
                            rpPhotos.map(function(_, i) {
                                return '<div class="ahd-rp-dot' + (i === rpCurrentPhoto ? ' active' : '') + '" onclick="ahdRpDot(' + i + ')"></div>';
                            }).join('') +
                            '</div>' :
                            '') +
                        '</div>' :
                        '';

                    popup.innerHTML =
                        '<div class="ahd-room-popup">' +
                        '<button class="ahd-room-popup-close" onclick="ahdCloseRoomPopup()">&times;</button>' +
                        galleryHtml +
                        '<div class="ahd-rp-body">' +

                        '<div class="ahd-rp-hotel-name">' + hotelName + '</div>' +

                        '<div class="ahd-rp-specs-row">' +
                        '<div class="ahd-rp-spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/></svg> 474 Sq Ft</div>' +
                        '<div class="ahd-rp-spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.8"><path d="M2 9h20M2 15h20M9 3v18M15 3v18"/></svg> 1 Bedroom</div>' +
                        '<div class="ahd-rp-spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.8"><circle cx="12" cy="7" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg> Sleeps ' + (room.adults || 2) + '</div>' +
                        '</div>' +
                        '<div class="ahd-rp-specs-row">' +
                        '<div class="ahd-rp-spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.8"><path d="M2 14h20v6H2zM2 14V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v5"/><path d="M12 14V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v5"/></svg> ' + bed + '</div>' +
                        '<div class="ahd-rp-spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="1.8"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg> Free Wi-Fi</div>' +
                        '</div>' +

                        '<hr class="ahd-rp-divider">' +

                        sectionsHtml +

                        '<div class="ahd-rp-section"><div class="ahd-rp-section-title">Child Policies</div>' +
                        '<p class="ahd-rp-policy-text">Children Of All Ages Can Stay In This Room. Additional Fees May Be Charged For Children Using Existing Beds. Add The Number Of Children To Get A More Accurate Price.</p></div>' +

                        '<div class="ahd-rp-section"><div class="ahd-rp-section-title">Cribs And Extra Beds</div>' +
                        '<p class="ahd-rp-policy-text">Extra Beds And Cribs Are Unavailable For This Room Type.</p></div>' +

                        '<div class="ahd-rp-section"><div class="ahd-rp-section-title">Cancellation Policy</div>' +
                        '<p class="ahd-rp-policy-text">' + cancelPolicy + '</p></div>' +

                        '<div class="ahd-rp-footer">' +
                        '<div style="display:flex;flex-direction:row;gap:8px;flex-shrink:0;width:50%;">' +
                        '<button class="ahd-rp-book-btn" style="margin:0;" onclick="ahdCloseRoomPopup(); ahdBookRoom(' + idx + ');">Book This Room</button>' +
                        '<a class="ahd-rp-call-btn" style="margin:0;" href="tel:+18777214100"><i class="fa-solid fa-phone"></i> Call Us</a>' +
                        '</div>' +
                        '<div style="width:50%;text-align:end;">' +
                        '<div style="font-size:12px;color:#94a3b8;text-decoration:line-through;">$' + (pr * 1.25).toFixed(2) + ' <span style="text-decoration:none;background:#EE9C31;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;">20% OFF</span></div>' +
                        '<div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.2;">$' + pr.toFixed(2) + '<span style="font-size:12px;font-weight:500;color:#64748b;"> / night</span></div>' +
                        '<div style="font-size:11px;color:#94a3b8;">+$' + (room.taxes || 54) + ' taxes & fees</div>' +
                        '</div>' +
                        '</div>' +

                        '</div></div>';

                    document.body.appendChild(popup);
                    document.body.style.overflow = 'hidden';
                    popup.addEventListener('click', function(e) {
                        if (e.target === popup) ahdCloseRoomPopup();
                    });
                    document.addEventListener('keydown', ahdRpKeyHandler);
                };

                function ahdRpKeyHandler(e) {
                    if (e.key === 'Escape') ahdCloseRoomPopup();
                }

                window.ahdCloseRoomPopup = function() {
                    var el = document.getElementById('ahd-room-popup-backdrop');
                    if (el) el.remove();
                    document.body.style.overflow = '';
                    document.removeEventListener('keydown', ahdRpKeyHandler);
                };

                window.ahdRpSlide = function(dir) {
                    if (!rpPhotos.length) return;
                    rpCurrentPhoto = (rpCurrentPhoto + dir + rpPhotos.length) % rpPhotos.length;
                    var img = document.getElementById('ahd-rp-img');
                    if (img) {
                        img.style.opacity = '0';
                        setTimeout(function() {
                            img.src = rpPhotos[rpCurrentPhoto];
                            img.style.opacity = '1';
                        }, 160);
                    }
                    document.querySelectorAll('.ahd-rp-dot').forEach(function(d, i) {
                        d.classList.toggle('active', i === rpCurrentPhoto);
                    });
                };

                window.ahdRpDot = function(i) {
                    rpCurrentPhoto = i;
                    var img = document.getElementById('ahd-rp-img');
                    if (img) {
                        img.style.opacity = '0';
                        setTimeout(function() {
                            img.src = rpPhotos[i];
                            img.style.opacity = '1';
                        }, 160);
                    }
                    document.querySelectorAll('.ahd-rp-dot').forEach(function(d, i2) {
                        d.classList.toggle('active', i2 === i);
                    });
                };

                window.ahdRpSlide = function(dir) {
                    if (!rpPhotos.length) return;
                    rpCurrentPhoto = (rpCurrentPhoto + dir + rpPhotos.length) % rpPhotos.length;
                    var img = document.getElementById('ahd-rp-img');
                    if (img) {
                        img.style.opacity = '0';
                        setTimeout(function() {
                            img.src = rpPhotos[rpCurrentPhoto];
                            img.style.opacity = '1';
                        }, 160);
                    }
                    ahdUpdateRpDots();
                };

                window.ahdRpDot = function(i) {
                    rpCurrentPhoto = i;
                    var img = document.getElementById('ahd-rp-img');
                    if (img) {
                        img.style.opacity = '0';
                        setTimeout(function() {
                            img.src = rpPhotos[i];
                            img.style.opacity = '1';
                        }, 160);
                    }
                    ahdUpdateRpDots();
                };

                function ahdUpdateRpDots() {
                    document.querySelectorAll('.ahd-rp-dot').forEach(function(d, i) {
                        d.classList.toggle('active', i === rpCurrentPhoto);
                    });
                }

                function renderAmenitiesSection(gp) {
                    var amenities = gp.amenities || [{
                            icon: '📶',
                            label: 'High-speed Wi-Fi'
                        },
                        {
                            icon: '🅿',
                            label: 'Parking available'
                        },
                        {
                            icon: '🧖',
                            label: 'Spa'
                        },
                        {
                            icon: '🏊',
                            label: 'Pool'
                        },
                        {
                            icon: '🛏',
                            label: '1 King Bed'
                        },
                        {
                            icon: '🚭',
                            label: 'Smoking areas'
                        },
                        {
                            icon: '🎭',
                            label: 'Entertainment'
                        },
                        {
                            icon: '🍽',
                            label: 'Dining services'
                        },
                        {
                            icon: '♿',
                            label: 'Accessible facilities'
                        },
                        {
                            icon: '🏋',
                            label: 'Fitness centre'
                        },
                        {
                            icon: '🚌',
                            label: 'Airport transport (surcharge)'
                        },
                        {
                            icon: '✈',
                            label: 'Airport transfers'
                        },
                    ];

                    var html = '<div class="ahd-amenities-section" id="ahd-section-amenities">' +
                        '<h2 class="ahd-amenities-title">Amenities & Facilities</h2>' +
                        '<div class="ahd-amenities-grid">' +
                        amenities.map(function(a) {
                            return '<div class="ahd-amenity-item"><span>' + a.icon + '</span>' + a.label + '</div>';
                        }).join('') +
                        '</div></div>';

                    var locationEl = document.querySelector('.ahd-location-section');
                    var roomsWrap = document.getElementById('ahd-rooms-wrap');
                    var insertAfter = locationEl || roomsWrap;
                    if (insertAfter) insertAfter.insertAdjacentHTML('afterend', html);
                }

                function renderAboutSection(gp) {
                    var searchData = hotelData.searchData || {};

                    // Format check-in/out from search dates
                    var checkInDate = searchData.checkIn ? new Date(searchData.checkIn).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    }) : '';
                    var checkOutDate = searchData.checkOut ? new Date(searchData.checkOut).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    }) : '';

                    var checkInHtml = checkInDate ? '<b>' + checkInDate + '</b> 2:00 PM – 12:00 AM &nbsp; Early Check-in rules' : 'See hotel for details';
                    var checkOutHtml = checkOutDate ? '<b>' + checkOutDate + '</b> 11:00 AM &nbsp; Late Check-out rules' : 'See hotel for details';

                    var html = '<div class="ahd-about-section" id="ahd-section-about">' +
                        '<h2 class="ahd-about-title">About</h2>' +
                        '<div class="ahd-checkinout">' +
                        '<div>' +
                        '<div class="ahd-checkinout-label">Check-in</div>' +
                        '<div class="ahd-checkinout-val">' + checkInHtml + '</div>' +
                        '</div>' +
                        '<div>' +
                        '<div class="ahd-checkinout-label">Check-Out</div>' +
                        '<div class="ahd-checkinout-val">' + checkOutHtml + '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="ahd-about-desc">Minimum check-in age: 18</div>' +
                        '<div class="ahd-about-desc">Enjoy the comfort you deserve and the convenience you crave as your adventure starts from this hotel. Surrounded by the traditional souks, the hotel and apartments are located in the heart of the city. Whether you are travelling for a business meeting or on a family vacation, find yourself in the ideal place to relax, meet and explore.</div>' +
                        '<div class="ahd-policies-title">Hotel Policy</div>' +

                        '<div class="ahd-policy-block">' +
                        '<div class="ahd-policy-block-title">Check-in & Check-out</div>' +
                        '<div class="ahd-policy-block-text">Standard check-in and check-out times are determined by the hotel. Early check-in and late check-out may be available upon request and are subject to availability. Additional fees may apply.</div>' +
                        '</div>' +

                        '<div class="ahd-policy-block">' +
                        '<div class="ahd-policy-block-title">Reservation Requirements</div>' +
                        '<div class="ahd-policy-block-text">Guests are required to present a valid government-issued photo ID and a valid payment method at check-in. The primary guest must meet the hotel'
                    s minimum age requirement. < /div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Payment & Incidental Charges</div>' +
                    '<div class="ahd-policy-block-text">Some hotels may require a security deposit or authorization hold during check-in to cover incidental expenses. Any applicable charges or deposits are determined by the hotel and communicated at the property.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Additional Fees</div>' +
                    '<div class="ahd-policy-block-text">Optional services such as parking, breakfast, resort amenities, pets, and other extras may incur additional charges. These fees vary by property and are determined by the hotel.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Pet Policy</div>' +
                    '<div class="ahd-policy-block-text">Pet-friendly accommodations are available only at select properties. Guests traveling with pets should review the hotels pet policy before arrival, as restrictions and additional charges may apply.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Hotel Amenities</div>' +
                    '<div class="ahd-policy-block-text">Amenities and services vary by property and may include internet access, fitness facilities, parking, business services, and other guest conveniences. Availability may change due to seasonal or operational conditions.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Special Requests</div>' +
                    '<div class="ahd-policy-block-text">Special requests such as early check-in, late check-out, room preferences, accessible rooms, or extra bedding are subject to availability and cannot be guaranteed in advance.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Guest Responsibilities</div>' +
                    '<div class="ahd-policy-block-text">Guests are expected to follow hotel policies, respect the property, and ensure a comfortable environment for other guests. Failure to comply may result in additional charges or refusal of service.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Policy Changes</div>' +
                    '<div class="ahd-policy-block-text">Hotel policies, fees, and services may change without prior notice. Property-specific terms and conditions always take precedence and should be reviewed before confirming your reservation.</div>' +
                    '</div>' +

                    '<div class="ahd-policy-block">' +
                    '<div class="ahd-policy-block-title">Need Assistance?</div>' +
                    '<div class="ahd-policy-block-text">If you have any questions regarding your reservation or a hotel\'s policies, the Travelay&trade; support team is always available to assist you.</div>' +
                    '</div>';

                    var amenEl = document.querySelector('.ahd-amenities-section');
                    var locationEl = document.querySelector('.ahd-location-section');
                    var roomsWrap = document.getElementById('ahd-rooms-wrap');
                    var insertAfter = amenEl || locationEl || roomsWrap;
                    if (insertAfter) insertAfter.insertAdjacentHTML('afterend', html);
                }
                // ── Lightbox ────────────────────────────
                var lbPhotos = [];
                var lbIndex = 0;

                window.ahdLightboxOpen = function(photos, index) {
                    lbPhotos = photos;
                    lbIndex = index || 0;
                    document.getElementById('ahd-lightbox').classList.add('open');
                    document.body.style.overflow = 'hidden';
                    renderLightbox();
                };
                window.ahdLightboxClose = function() {
                    document.getElementById('ahd-lightbox').classList.remove('open');
                    document.body.style.overflow = '';
                };
                window.ahdLightboxNav = function(dir) {
                    lbIndex = (lbIndex + dir + lbPhotos.length) % lbPhotos.length;
                    renderLightbox();
                };

                function renderLightbox() {
                    var img = document.getElementById('ahd-lb-img');
                    var counter = document.getElementById('ahd-lb-counter');
                    var title = document.getElementById('ahd-lb-title');
                    var thumbs = document.getElementById('ahd-lb-thumbs');
                    img.style.opacity = '0';
                    setTimeout(function() {
                        img.src = lbPhotos[lbIndex];
                        img.style.opacity = '1';
                    }, 100);
                    counter.textContent = (lbIndex + 1) + ' / ' + lbPhotos.length;
                    title.textContent = hotelData.name || '';
                    thumbs.innerHTML = lbPhotos.map(function(p, i) {
                        return '<img class="ahd-lightbox-thumb' + (i === lbIndex ? ' active' : '') +
                            '" src="' + p + '" onclick="ahdLightboxGoto(' + i + ')" alt="photo ' + (i + 1) + '">';
                    }).join('');
                    // Scroll active thumb into view
                    var activeThumb = thumbs.querySelector('.active');
                    if (activeThumb) activeThumb.scrollIntoView({
                        inline: 'center',
                        behavior: 'smooth'
                    });
                }
                window.ahdLightboxGoto = function(i) {
                    lbIndex = i;
                    renderLightbox();
                };
                // Keyboard navigation
                document.addEventListener('keydown', function(e) {
                    if (!document.getElementById('ahd-lightbox').classList.contains('open')) return;
                    if (e.key === 'ArrowRight') ahdLightboxNav(1);
                    if (e.key === 'ArrowLeft') ahdLightboxNav(-1);
                    if (e.key === 'Escape') ahdLightboxClose();
                });
                // Click backdrop to close
                document.getElementById('ahd-lightbox').addEventListener('click', function(e) {
                    if (e.target === this) ahdLightboxClose();
                });

                window.ahdSelectRoom = window.ahdScrollToRooms;

                // ── Reviews popup ────────────────────────
                window.ahdOpenReviews = function() {
                    var reviews = hotelData.google_reviews || [];
                    var photos = window._ahdCurrentPhotos || [];
                    var rating = document.querySelector('.ahd-rating-badge') ? document.querySelector('.ahd-rating-badge').textContent : '';

                    var photosHtml = photos.length ?
                        '<div class="ahd-reviews-photos">' +
                        photos.map(function(p) {
                            return '<img class="ahd-reviews-photo" src="' + p + '" onclick="ahdLightboxOpen(window._ahdCurrentPhotos,' + photos.indexOf(p) + ')">';
                        }).join('') +
                        '</div>' : '';

                    var reviewsHtml = reviews.length ?
                        reviews.map(function(r) {
                            var stars = '';
                            for (var i = 0; i < 5; i++) stars += i < r.rating ? '★' : '☆';
                            return '<div class="ahd-review-card">' +
                                '<div class="ahd-review-top">' +
                                (r.avatar ? '<img class="ahd-review-avatar" src="' + r.avatar + '" alt="' + r.author + '">' :
                                    '<div class="ahd-review-avatar" style="display:flex;align-items:center;justify-content:center;font-size:16px;background:#f0fdf4;color:#0e7d3f;">' + (r.author ? r.author[0] : '?') + '</div>') +
                                '<div>' +
                                '<div class="ahd-review-author">' + (r.author || 'Guest') + '</div>' +
                                '<div class="ahd-review-time">' + (r.time || '') + '</div>' +
                                '</div>' +
                                '</div>' +
                                '<div class="ahd-review-stars">' + stars + '</div>' +
                                '<div class="ahd-review-text">' + (r.text || '') + '</div>' +
                                '</div>';
                        }).join('') :
                        '<div id="ahd-reviews-loading" style="text-align:center;padding:40px 20px;">' +
                        '<div style="width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#0e7d3f;border-radius:50%;animation:ahdSpin 0.8s linear infinite;margin:0 auto 12px;"></div>' +
                        '<p style="color:#94a3b8;font-size:14px;margin:0;">Loading reviews…</p>' +
                        '</div>';

                    var backdrop = document.createElement('div');
                    backdrop.className = 'ahd-reviews-backdrop';
                    backdrop.id = 'ahd-reviews-backdrop';
                    backdrop.innerHTML =
                        '<div class="ahd-reviews-modal">' +
                        '<div class="ahd-reviews-modal-header">' +
                        '<div class="ahd-reviews-modal-title">Guest Reviews ' + (rating ? '· ' + rating : '') + '</div>' +
                        '<button class="ahd-reviews-modal-close" onclick="ahdCloseReviews()">&times;</button>' +
                        '</div>' +
                        '<div class="ahd-reviews-modal-body">' +
                        photosHtml +
                        reviewsHtml +
                        '</div>' +
                        '</div>';
                    document.body.appendChild(backdrop);
                    document.body.style.overflow = 'hidden';
                    backdrop.addEventListener('click', function(e) {
                        if (e.target === backdrop) ahdCloseReviews();
                    });
                };

                window.ahdCloseReviews = function() {
                    var el = document.getElementById('ahd-reviews-backdrop');
                    if (el) el.remove();
                    document.body.style.overflow = '';
                };

                // Attach click to reviews link (delegated since it renders dynamically)
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('ahd-reviews-link')) {
                        ahdOpenReviews();
                    }
                });

                function renderLocationSection(gp) {
                    var lat = gp.lat;
                    var lng = gp.lng;
                    var address = gp.address || hotelData.address || '';
                    var name = hotelData.name || '';
                    var googleKey = 'AIzaSyDf1tj8wdsAL1oPK8O9M0YFnfVPgSTMfYY';

                    var landmarks = gp.nearby || [];
                    var landmarksHtml = '';
                    if (landmarks.length) {
                        landmarksHtml = '<div class="ahd-landmarks">' +
                            landmarks.map(function(l) {
                                return '<div class="ahd-landmark-card">' +
                                    '<div>' +
                                    '<div class="ahd-landmark-label">Nearby Landmark</div>' +
                                    '<div class="ahd-landmark-name">' + l.name + '</div>' +
                                    '</div>' +
                                    '<div>' +
                                    '<div class="ahd-landmark-dist-label">Distance</div>' +
                                    '<div class="ahd-landmark-dist">' + l.distance + '</div>' +
                                    '</div>' +
                                    '</div>';
                            }).join('') +
                            '</div>';
                    }

                    var mapSrc = 'https://www.google.com/maps/embed/v1/place?key=' + googleKey +
                        '&q=' + encodeURIComponent(name + ' ' + address) +
                        '&zoom=15';

                    var locationHtml =
                        '<div class="ahd-location-section" id="ahd-section-location">' +
                        '<h2 class="ahd-location-title">Location</h2>' +
                        '<div class="ahd-location-address">' +
                        '<svg width="13" height="13" viewBox="0 0 640 640" style="flex-shrink:0;fill:#64748b;"><path d="M128 252.6C128 148.4 214 64 320 64C426 64 512 148.4 512 252.6C512 371.9 391.8 514.9 341.6 569.4C329.8 582.2 310.1 582.2 298.3 569.4C248.1 514.9 127.9 371.9 127.9 252.6zM320 320C355.3 320 384 291.3 384 256C384 220.7 355.3 192 320 192C284.7 192 256 220.7 256 256C256 291.3 284.7 320 320 320z"/></svg>' +
                        address +
                        '</div>' +
                        '<div class="ahd-map-wrap">' +
                        '<iframe src="' + mapSrc + '" allowfullscreen loading="lazy"></iframe>' +
                        '</div>' +
                        landmarksHtml +
                        '</div>';

                    var roomsWrap = document.getElementById('ahd-rooms-wrap');
                    if (roomsWrap) {
                        roomsWrap.insertAdjacentHTML('afterend', locationHtml);
                    }
                }

            })();
        </script>
<?php
        return ob_get_clean();
    }

    public function fetch_rooms()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $hotel_id  = sanitize_text_field($_POST['hotel_id']  ?? '');
        $check_in  = sanitize_text_field($_POST['check_in']  ?? '');
        $check_out = sanitize_text_field($_POST['check_out'] ?? '');
        $adults    = max(1, intval($_POST['adults'] ?? 1));
        $rooms_qty = max(1, intval($_POST['rooms']  ?? 1));

        if (!$hotel_id || !$check_in || !$check_out) {
            wp_send_json_success(array());
            return;
        }

        $token = $this->get_token();
        if (!$token) {
            wp_send_json_success(array());
            return;
        }

        $base    = $this->get_base_url();
        $headers = array('Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json');

        $url  = $base . '/v3/shopping/hotel-offers?' . http_build_query(array(
            'hotelIds'     => $hotel_id,
            'checkInDate'  => $check_in,
            'checkOutDate' => $check_out,
            'adults'       => $adults,
            'roomQuantity' => $rooms_qty,
            'currency'     => 'USD',
            'bestRateOnly' => 'false',
        ));
        $resp = wp_remote_get($url, array('headers' => $headers, 'timeout' => 20));

        if (is_wp_error($resp)) {
            amadex_log('HotelRooms: wp_error=' . $resp->get_error_message());
            wp_send_json_success(array());
            return;
        }
        amadex_log('HotelRooms: url=' . $url . ' status=' . wp_remote_retrieve_response_code($resp) . ' body=' . substr(wp_remote_retrieve_body($resp), 0, 500));

        $data = json_decode(wp_remote_retrieve_body($resp));
        if (empty($data->data)) {
            amadex_log('HotelRooms: empty data, body=' . substr(wp_remote_retrieve_body($resp), 0, 500));
            wp_send_json_success(array());
            return;
        }

        $rooms = array();
        foreach ($data->data as $offer_group) {
            $h    = $offer_group->hotel  ?? null;
            $ofrs = $offer_group->offers ?? array();
            foreach ($ofrs as $offer) {
                $price_total = floatval($offer->price->total ?? 0);
                $currency    = $offer->price->currency ?? 'USD';
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
                // Convert to USD if needed
                $price_raw = $this->convert_to_usd($price_raw, $currency);
                $cancellable = empty($offer->policies->cancellations ?? array());
                $cancel_date = '';
                if (!$cancellable && !empty($offer->policies->cancellations)) {
                    $cancel_date = $offer->policies->cancellations[0]->deadline ?? '';
                    if ($cancel_date) {
                        try {
                            $dt = new DateTime($cancel_date);
                            $cancel_date = $dt->format('D, j M');
                        } catch (Exception $e) {
                        }
                    }
                }
                $bed_type = $offer->room->typeEstimated->bedType ?? '';
                $bed_count = $offer->room->typeEstimated->beds    ?? 1;
                $bed_label = ($bed_count > 1 ? $bed_count . ' ' : '') . ucfirst(strtolower($bed_type ?: 'King')) . ' Bed';
                $room_name = $offer->room->description->text ?? ($offer->room->type ?? 'Standard Room');
                // Clean up room name
                if (strlen($room_name) > 60) $room_name = substr($room_name, 0, 60) . '...';

                // Real taxes — convert to USD and per night
                $taxes = 0;
                if (!empty($offer->price->taxes)) {
                    foreach ($offer->price->taxes as $tax) {
                        $taxes += floatval($tax->amount ?? 0);
                    }
                }
                // Divide by nights
                $taxes = round($taxes / $nights, 2);
                // Convert to USD if needed
                $taxes = $this->convert_to_usd($taxes, $currency);
                // Board type
                $board_map = array(
                    'BREAKFAST'     => 'Breakfast Included',
                    'HALF_BOARD'    => 'Half Board',
                    'FULL_BOARD'    => 'Full Board',
                    'ALL_INCLUSIVE' => 'All Inclusive',
                );
                $board_raw   = strtoupper($offer->boardType ?? '');
                $board_label = $board_map[$board_raw] ?? 'Room Only';

                $rooms[] = array(
                    'offerId'     => $offer->id ?? '',
                    'room_name'   => $room_name,
                    'bed'         => $bed_label,
                    'board'       => $board_label,
                    'price_raw'   => $price_raw,
                    'currency'    => $currency,
                    'cancellable' => $cancellable,
                    'cancel_date' => $cancel_date ?: '',
                    'taxes'       => $taxes ? number_format($taxes, 2) : null,
                );
            }
        }

        // Sort by price
        usort($rooms, function ($a, $b) {
            return $a['price_raw'] - $b['price_raw'];
        });

        header('Cache-Control: no-cache, no-store, must-revalidate');
        wp_send_json_success($rooms);
    }

    public function fetch_detail()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        wp_send_json_success(array());
    }
    public function fetch_places()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $hotel_name = sanitize_text_field($_POST['hotel_name'] ?? '');
        $address    = sanitize_text_field($_POST['address']    ?? '');
        if (!$hotel_name) {
            wp_send_json_error(array('message' => 'No hotel name'));
            return;
        }

        $google_key = 'AIzaSyDf1tj8wdsAL1oPK8O9M0YFnfVPgSTMfYY';
        $query      = $hotel_name . ' ' . $address;

        // Step 1 — Find Place to get place_id
        $find_url  = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?' . http_build_query(array(
            'input'     => $query,
            'inputtype' => 'textquery',
            'fields'    => 'place_id,name,rating,user_ratings_total,photos,formatted_address,geometry',
            'key'       => $google_key,
        ));
        $find_resp = wp_remote_get($find_url, array('timeout' => 15));
        if (is_wp_error($find_resp)) {
            wp_send_json_error(array('message' => 'Places API failed'));
            return;
        }

        $find_data = json_decode(wp_remote_retrieve_body($find_resp));
        if (empty($find_data->candidates)) {
            wp_send_json_error(array('message' => 'Not found'));
            return;
        }

        $place     = $find_data->candidates[0];
        $place_id  = $place->place_id ?? '';
        if (!$place_id) {
            wp_send_json_error(array('message' => 'No place ID'));
            return;
        }

        // Step 2 — Get Place Details
        $details_url  = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query(array(
            'place_id' => $place_id,
            'fields'   => 'name,rating,user_ratings_total,reviews,photos,formatted_address,formatted_phone_number,website,opening_hours,geometry',
            'key'      => $google_key,
        ));
        $details_resp = wp_remote_get($details_url, array('timeout' => 15));
        if (is_wp_error($details_resp)) {
            wp_send_json_error(array('message' => 'Details API failed'));
            return;
        }

        $details_data = json_decode(wp_remote_retrieve_body($details_resp));
        $result       = $details_data->result ?? null;
        if (!$result) {
            wp_send_json_error(array('message' => 'No details'));
            return;
        }

        // Build photo URLs
        $photos = array();
        if (!empty($result->photos)) {
            foreach (array_slice((array)$result->photos, 0, 8) as $photo) {
                $ref = $photo->photo_reference ?? '';
                if ($ref) {
                    $photos[] = 'https://maps.googleapis.com/maps/api/place/photo?' . http_build_query(array(
                        'maxwidth'         => 800,
                        'photo_reference'  => $ref,
                        'key'              => $google_key,
                    ));
                }
            }
        }

        // Build reviews
        $reviews = array();
        if (!empty($result->reviews)) {
            foreach (array_slice((array)$result->reviews, 0, 20) as $r) {
                $reviews[] = array(
                    'author'  => $r->author_name   ?? '',
                    'rating'  => $r->rating        ?? 0,
                    'text'    => $r->text           ?? '',
                    'time'    => $r->relative_time_description ?? '',
                    'avatar'  => $r->profile_photo_url ?? '',
                );
            }
        }

        $lat = $result->geometry->location->lat ?? null;
        $lng = $result->geometry->location->lng ?? null;

        // Step 3 — Get nearby landmarks
        $nearby = array();
        if ($lat && $lng) {
            $nearby_url  = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?' . http_build_query(array(
                'location' => $lat . ',' . $lng,
                'rankby'   => 'distance',
                'type'     => 'point_of_interest',
                'key'      => $google_key,
            ));
            $nearby_resp = wp_remote_get($nearby_url, array('timeout' => 10));
            if (!is_wp_error($nearby_resp)) {
                $nearby_data = json_decode(wp_remote_retrieve_body($nearby_resp));
                if (!empty($nearby_data->results)) {
                    $count = 0;
                    foreach ($nearby_data->results as $place) {
                        if ($count >= 4) break;
                        $nearby_lat  = $place->geometry->location->lat ?? 0;
                        $nearby_lng  = $place->geometry->location->lng ?? 0;
                        // Calculate distance in KM
                        $dist = round(sqrt(
                            pow(($nearby_lat - $lat) * 111, 2) +
                                pow(($nearby_lng - $lng) * 111 * cos(deg2rad($lat)), 2)
                        ), 1);
                        if ($dist < 0.1) continue; // skip if too close (same building)
                        $nearby[] = array(
                            'name'     => $place->name ?? '',
                            'distance' => $dist . 'KM',
                        );
                        $count++;
                    }
                }
            }
        }

        // Build amenities from types
        $amenities = array();
        $type_map = array(
            'lodging'     => array('🏨', 'Hotel'),
            'spa'         => array('🧖', 'Spa'),
            'restaurant'  => array('🍽', 'Dining services'),
            'gym'         => array('🏋', 'Fitness centre'),
            'parking'     => array('🅿', 'Parking available'),
            'bar'         => array('🍸', 'Bar'),
            'pool'        => array('🏊', 'Pool'),
        );
        if (!empty($result->types)) {
            foreach ($result->types as $type) {
                if (isset($type_map[$type])) {
                    $amenities[] = array('icon' => $type_map[$type][0], 'label' => $type_map[$type][1]);
                }
            }
        }
        // Always add common hotel amenities
        $amenities = array_merge(array(
            array('icon' => '📶', 'label' => 'High-speed Wi-Fi'),
            array('icon' => '🅿', 'label' => 'Parking available'),
            array('icon' => '🧖', 'label' => 'Spa'),
            array('icon' => '🏊', 'label' => 'Pool'),
            array('icon' => '🛏', 'label' => '1 King Bed'),
            array('icon' => '🚭', 'label' => 'Smoking areas'),
            array('icon' => '🎭', 'label' => 'Entertainment'),
            array('icon' => '🍽', 'label' => 'Dining services'),
            array('icon' => '♿', 'label' => 'Accessible facilities'),
            array('icon' => '🏋', 'label' => 'Fitness centre'),
            array('icon' => '🚌', 'label' => 'Airport transport (surcharge)'),
            array('icon' => '✈', 'label' => 'Airport transfers'),
        ), array());

        $checkin  = 'Check details with hotel';
        $checkout = 'Check details with hotel';

        wp_send_json_success(array(
            'place_id'      => $place_id,
            'name'          => $result->name ?? '',
            'rating'        => $result->rating ?? null,
            'total_ratings' => $result->user_ratings_total ?? 0,
            'address'       => $result->formatted_address ?? '',
            'phone'         => $result->formatted_phone_number ?? '',
            'website'       => $result->website ?? '',
            'photos'        => $photos,
            'reviews'       => $reviews,
            'lat'           => $lat,
            'lng'           => $lng,
            'nearby'        => $nearby,
            'amenities'     => $amenities,
            'checkin'       => $checkin,
            'checkout'      => $checkout,
        ));
    }

    private function convert_to_usd($amount, $currency)
    {
        if ($currency === 'USD' || $amount <= 0) return $amount;

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
                    set_transient($cache_key, $rate, 24 * HOUR_IN_SECONDS);
                }
            }
        }

        if ($rate) return round($amount * $rate, 2);
        return $amount;
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
        $t   = $api->get_access_token();
        return is_wp_error($t) ? null : $t;
    }

    private function get_base_url()
    {
        $s   = get_option('amadex_api_settings', array());
        $env = $s['environment'] ?? 'test';
        return ($env === 'live' || $env === 'production') ? 'https://api.amadeus.com' : 'https://test.api.amadeus.com';
    }
}
new Amadex_Hotel_Detail();
