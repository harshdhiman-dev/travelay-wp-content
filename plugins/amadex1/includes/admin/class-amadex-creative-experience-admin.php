<?php
/**
 * Amadex Creative Experience Admin
 * Handles settings, defaults, sanitization, and UI for Creative Experience customization.
 *
 * @package Amadex
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Amadex_Creative_Experience_Admin {

    /**
     * Get default Creative Experience settings (matches current hardcoded behavior).
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            // Text & Copy
            'text' => array(
                'search_hero' => 'Your next trip starts here',
                'search_popular_label' => 'Popular:',
                'search_finding_strip' => 'Finding your flights…',
                'results_hero' => 'We found your flights',
                'results_select_strip' => "Pick a flight to continue — you're one step away from booking.",
                'results_book_now' => 'Book Now',
                'confirm_book' => 'Confirm & Book',
                'step_next' => 'Next',
                'print_booking' => 'Print',
                'pagination_confirm' => 'Confirm & Book',
                'confirmation_badge' => 'Adventure Awaits ✈️',
                'confirmation_ps' => "P.S. You're going to love this trip.",
                'steps' => array(
                    'flights' => array(
                        'hero' => 'Your flight',
                        'badge' => 'Looking good',
                        'teaser' => 'Next: Passenger details',
                    ),
                    'passengers' => array(
                        'hero' => "Who's flying?",
                        'badge' => '',
                        'teaser' => 'Next: Pick your seats',
                    ),
                    'seats' => array(
                        'hero' => 'Window or aisle?',
                        'badge' => '',
                        'teaser' => 'Next: Add-ons',
                        'skip_ok' => "Skip — I'll take any seat",
                    ),
                    'addons' => array(
                        'hero' => 'Little extras for your trip',
                        'badge' => '',
                        'teaser' => 'Next: Review & pay',
                        'optional_label' => 'Optional',
                    ),
                    'review' => array(
                        'hero' => 'Final check',
                        'badge' => '',
                        'teaser' => '',
                        'protected' => "You're protected",
                    ),
                ),
                'payment_secure_bar' => 'Secure payment',
                'popular_routes' => array(
                    array('from' => 'New York', 'to' => 'Miami', 'origin' => 'JFK', 'dest' => 'MIA'),
                    array('from' => 'Los Angeles', 'to' => 'Las Vegas', 'origin' => 'LAX', 'dest' => 'LAS'),
                    array('from' => 'Chicago', 'to' => 'Orlando', 'origin' => 'ORD', 'dest' => 'MCO'),
                ),
            ),
            // Animations & Behavior
            'animations' => array(
                'ripple_enabled' => true,
                'hover_lift_enabled' => true,
                'lazy_reveal_enabled' => true,
                'confirmation_greeting_animation' => true,
                'processing_modal_animation' => true,
                'step_section_enter' => true,
                'book_now_dots_enabled' => true,
                'duration_fast' => 0.2,
                'duration_normal' => 0.35,
                'duration_slow' => 0.5,
                'easing' => 'ease-out',
            ),
            // Celebration & Confetti
            'celebration' => array(
                'confetti_enabled' => true,
                'confetti_duration' => 4000,
                'confetti_count' => 100,
                'confetti_colors' => array('#0e7d3f', '#1a9d5f', '#fff', '#ffd700', '#87ceeb'),
                'surprise_badge_enabled' => true,
                'surprise_badge_delay' => 800,
                'surprise_ps_enabled' => true,
                'surprise_ps_delay' => 1200,
            ),
            // Step Elements visibility
            'step_elements' => array(
                'flights_hero' => true,
                'flights_badge' => true,
                'flights_teaser' => true,
                'flights_route' => true,
                'passengers_hero' => true,
                'passengers_teaser' => true,
                'seats_hero' => true,
                'seats_teaser' => true,
                'seats_skip_ok' => true,
                'addons_hero' => true,
                'addons_teaser' => true,
                'addons_optional' => true,
                'review_hero' => true,
                'review_protected' => true,
                'popular_chips_enabled' => true,
                'results_hero_enabled' => true,
                'results_select_strip_enabled' => true,
                'payment_secure_bar_enabled' => true,
            ),
            // Sizing & Layout
            'sizing' => array(
                'text_scale' => 1.0,
                'hero_font_size' => 'large',
                'badge_font_size' => 'medium',
                'teaser_font_size' => 'medium',
                'strip_font_size' => 'medium',
                'surprise_font_size' => 'medium',
                'section_max_width' => 1200,
                'section_padding_x' => 20,
                'section_padding_y' => 24,
                'section_gap' => 24,
                'card_padding' => 20,
                'badge_padding_v' => 10,
                'badge_padding_h' => 16,
                'chip_min_height' => 44,
                'chip_padding_v' => 11,
                'chip_padding_h' => 20,
                'strip_padding_v' => 16,
                'strip_padding_h' => 20,
                'icon_scale' => 1.0,
                'mobile_text_scale' => 0.95,
                'mobile_padding_reduce' => 0.9,
            ),
            // Accessibility
            'accessibility' => array(
                'respect_reduced_motion' => true,
                'force_disable_animations' => false,
            ),
        );
    }

    /**
     * Get Creative Experience settings (merged with defaults).
     * Always returns full structure; corrupted or partial saved data is merged safely.
     *
     * @return array
     */
    public static function get_settings() {
        $saved = get_option('amadex_creative_experience_settings', array());
        $defaults = self::get_defaults();
        if (!is_array($saved)) {
            return $defaults;
        }
        return self::array_merge_deep($defaults, $saved);
    }

    /**
     * Recursively merge arrays (saved overrides defaults).
     *
     * @param array $defaults
     * @param array $saved
     * @return array
     */
    private static function array_merge_deep($defaults, $saved) {
        $result = $defaults;
        foreach ($saved as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = self::array_merge_deep($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Sanitize Creative Experience settings.
     *
     * @param array $input Raw POST input.
     * @return array Sanitized settings.
     */
    public static function sanitize($input) {
        if (!is_array($input)) {
            return self::get_defaults();
        }
        $defaults = self::get_defaults();
        $output = array();

        // Text
        if (isset($input['text']) && is_array($input['text'])) {
            $output['text'] = array();
            foreach ($defaults['text'] as $key => $def) {
                if ($key === 'steps' && isset($input['text']['steps']) && is_array($input['text']['steps'])) {
                    $output['text']['steps'] = array();
                    foreach ($defaults['text']['steps'] as $step => $stepDef) {
                        $stepInput = isset($input['text']['steps'][$step]) && is_array($input['text']['steps'][$step]) ? $input['text']['steps'][$step] : array();
                        $sanitizedStep = array();
                        foreach ($stepDef as $k => $def) {
                            $sanitizedStep[$k] = isset($stepInput[$k]) ? sanitize_text_field($stepInput[$k]) : $def;
                        }
                        $output['text']['steps'][$step] = $sanitizedStep;
                    }
                } elseif ($key === 'popular_routes' && isset($input['text']['popular_routes']) && is_array($input['text']['popular_routes'])) {
                    $output['text']['popular_routes'] = array();
                    foreach ($input['text']['popular_routes'] as $i => $r) {
                        if (is_array($r)) {
                            $output['text']['popular_routes'][] = array(
                                'from' => sanitize_text_field($r['from'] ?? ''),
                                'to' => sanitize_text_field($r['to'] ?? ''),
                                'origin' => sanitize_text_field($r['origin'] ?? ''),
                                'dest' => sanitize_text_field($r['dest'] ?? ''),
                            );
                        }
                    }
                    if (empty($output['text']['popular_routes'])) {
                        $output['text']['popular_routes'] = $defaults['text']['popular_routes'];
                    }
                } else {
                    $output['text'][$key] = isset($input['text'][$key]) ? sanitize_text_field($input['text'][$key]) : $def;
                }
            }
        } else {
            $output['text'] = $defaults['text'];
        }

        // Animations
        if (isset($input['animations']) && is_array($input['animations'])) {
            $a = $input['animations'];
            $output['animations'] = array(
                'ripple_enabled' => !empty($a['ripple_enabled']),
                'hover_lift_enabled' => !empty($a['hover_lift_enabled']),
                'lazy_reveal_enabled' => !empty($a['lazy_reveal_enabled']),
                'confirmation_greeting_animation' => !empty($a['confirmation_greeting_animation']),
                'processing_modal_animation' => !empty($a['processing_modal_animation']),
                'step_section_enter' => !empty($a['step_section_enter']),
                'book_now_dots_enabled' => !empty($a['book_now_dots_enabled']),
                'duration_fast' => max(0.05, min(2, floatval($a['duration_fast'] ?? 0.2))),
                'duration_normal' => max(0.1, min(3, floatval($a['duration_normal'] ?? 0.35))),
                'duration_slow' => max(0.15, min(5, floatval($a['duration_slow'] ?? 0.5))),
                'easing' => in_array($a['easing'] ?? '', array('ease-out', 'ease-in', 'ease-in-out', 'ease-bounce', 'linear')) ? $a['easing'] : 'ease-out',
            );
        } else {
            $output['animations'] = $defaults['animations'];
        }

        // Celebration
        if (isset($input['celebration']) && is_array($input['celebration'])) {
            $c = $input['celebration'];
            $colors = array();
            if (!empty($c['confetti_colors']) && is_array($c['confetti_colors'])) {
                foreach ($c['confetti_colors'] as $col) {
                    $col = sanitize_hex_color($col);
                    if ($col) $colors[] = $col;
                }
            }
            if (empty($colors)) $colors = $defaults['celebration']['confetti_colors'];
            $output['celebration'] = array(
                'confetti_enabled' => !empty($c['confetti_enabled']),
                'confetti_duration' => max(1000, min(10000, intval($c['confetti_duration'] ?? 4000))),
                'confetti_count' => max(20, min(500, intval($c['confetti_count'] ?? 100))),
                'confetti_colors' => $colors,
                'surprise_badge_enabled' => !empty($c['surprise_badge_enabled']),
                'surprise_badge_delay' => max(0, min(3000, intval($c['surprise_badge_delay'] ?? 800))),
                'surprise_ps_enabled' => !empty($c['surprise_ps_enabled']),
                'surprise_ps_delay' => max(0, min(5000, intval($c['surprise_ps_delay'] ?? 1200))),
            );
        } else {
            $output['celebration'] = $defaults['celebration'];
        }

        // Step elements
        if (isset($input['step_elements']) && is_array($input['step_elements'])) {
            $output['step_elements'] = array();
            foreach ($defaults['step_elements'] as $key => $def) {
                $output['step_elements'][$key] = !empty($input['step_elements'][$key]);
            }
        } else {
            $output['step_elements'] = $defaults['step_elements'];
        }

        // Colors (text/UI)
        $default_colors = $defaults['colors'];
        if (isset($input['colors']) && is_array($input['colors'])) {
            $col = $input['colors'];
            $output['colors'] = array(
                'primary' => sanitize_hex_color($col['primary'] ?? '') ?: $default_colors['primary'],
                'muted' => sanitize_hex_color($col['muted'] ?? '') ?: $default_colors['muted'],
                'brand' => sanitize_hex_color($col['brand'] ?? '') ?: $default_colors['brand'],
                'confirmation_primary' => sanitize_hex_color($col['confirmation_primary'] ?? '') ?: $default_colors['confirmation_primary'],
                'confirmation_muted' => sanitize_hex_color($col['confirmation_muted'] ?? '') ?: $default_colors['confirmation_muted'],
            );
        } else {
            $output['colors'] = $default_colors;
        }

        // Sizing
        if (isset($input['sizing']) && is_array($input['sizing'])) {
            $s = $input['sizing'];
            $lineHeight = isset($s['line_height']) ? floatval($s['line_height']) : 1.5;
            $letterSpacing = isset($s['letter_spacing']) ? floatval($s['letter_spacing']) : 0;
            $output['sizing'] = array(
                'text_scale' => max(0.7, min(1.5, floatval($s['text_scale'] ?? 1.0))),
                'line_height' => max(1.0, min(2.5, $lineHeight)),
                'letter_spacing' => max(-0.05, min(0.1, $letterSpacing)),
                'hero_font_size' => in_array($s['hero_font_size'] ?? '', array('small', 'medium', 'large', 'xl', 'custom')) ? $s['hero_font_size'] : 'large',
                'badge_font_size' => in_array($s['badge_font_size'] ?? '', array('small', 'medium', 'large', 'xl', 'custom')) ? $s['badge_font_size'] : 'medium',
                'teaser_font_size' => in_array($s['teaser_font_size'] ?? '', array('small', 'medium', 'large', 'xl', 'custom')) ? $s['teaser_font_size'] : 'medium',
                'strip_font_size' => in_array($s['strip_font_size'] ?? '', array('small', 'medium', 'large', 'xl', 'custom')) ? $s['strip_font_size'] : 'medium',
                'surprise_font_size' => in_array($s['surprise_font_size'] ?? '', array('small', 'medium', 'large', 'xl', 'custom')) ? $s['surprise_font_size'] : 'medium',
                'section_max_width' => max(600, min(2000, intval($s['section_max_width'] ?? 1200))),
                'section_padding_x' => max(0, min(100, intval($s['section_padding_x'] ?? 20))),
                'section_padding_y' => max(0, min(100, intval($s['section_padding_y'] ?? 24))),
                'section_gap' => max(0, min(100, intval($s['section_gap'] ?? 24))),
                'card_padding' => max(0, min(80, intval($s['card_padding'] ?? 20))),
                'badge_padding_v' => max(0, min(40, intval($s['badge_padding_v'] ?? 10))),
                'badge_padding_h' => max(0, min(60, intval($s['badge_padding_h'] ?? 16))),
                'chip_min_height' => max(32, min(80, intval($s['chip_min_height'] ?? 44))),
                'chip_padding_v' => max(0, min(30, intval($s['chip_padding_v'] ?? 11))),
                'chip_padding_h' => max(0, min(40, intval($s['chip_padding_h'] ?? 20))),
                'strip_padding_v' => max(0, min(60, intval($s['strip_padding_v'] ?? 16))),
                'strip_padding_h' => max(0, min(60, intval($s['strip_padding_h'] ?? 20))),
                'icon_scale' => max(0.5, min(2, floatval($s['icon_scale'] ?? 1.0))),
                'mobile_text_scale' => max(0.7, min(1.2, floatval($s['mobile_text_scale'] ?? 0.95))),
                'mobile_padding_reduce' => max(0.5, min(1, floatval($s['mobile_padding_reduce'] ?? 0.9))),
            );
        } else {
            $output['sizing'] = $defaults['sizing'];
        }

        // Accessibility
        if (isset($input['accessibility']) && is_array($input['accessibility'])) {
            $acc = $input['accessibility'];
            $output['accessibility'] = array(
                'respect_reduced_motion' => !empty($acc['respect_reduced_motion']),
                'force_disable_animations' => !empty($acc['force_disable_animations']),
            );
        } else {
            $output['accessibility'] = $defaults['accessibility'];
        }

        return $output;
    }
}
