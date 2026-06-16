/**
 * Amadex Animation Library
 * 
 * Enhanced animation library with metadata, performance considerations,
 * and modern animation patterns.
 * 
 * @package Amadex
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    /**
     * Animation Definitions
     * Each animation defines:
     * - id: Unique identifier
     * - name: Display name
     * - description: What the animation does
     * - useCase: Best use case
     * - performanceRisk: 'low', 'medium', 'high'
     * - recommendedLimit: Maximum number of loops (0 = infinite, but should respect max 3)
     * - mobileSafe: Boolean indicating if safe for mobile
     * - requiresJS: Boolean indicating if requires JavaScript
     * - respectsReducedMotion: Boolean indicating if respects prefers-reduced-motion
     */
    const ANIMATION_DEFINITIONS = {
        'shine': {
            id: 'shine',
            name: 'Shine/Shimmer',
            description: 'Premium light sweep effect that travels across the container. Creates a luxurious, polished appearance.',
            useCase: 'Premium promotions, luxury products, high-value offers. Best for hero banners and featured content.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'pulse': {
            id: 'pulse',
            name: 'Pulse',
            description: 'Smooth breathing effect that gently scales the container. Draws attention without being aggressive.',
            useCase: 'Important announcements, limited-time offers, call-to-action buttons. Subtle attention-grabbing.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'fade_in': {
            id: 'fade_in',
            name: 'Fade In',
            description: 'Elegant entrance animation that fades in from transparent. Smooth and professional.',
            useCase: 'Initial page load, content reveals, subtle entrances. Universal entrance animation.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'slide_in_left': {
            id: 'slide_in_left',
            name: 'Slide In Left',
            description: 'Dynamic entrance from the left side. Creates sense of movement and direction.',
            useCase: 'Content reveals, sequential item displays, directional storytelling.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'slide_in_right': {
            id: 'slide_in_right',
            name: 'Slide In Right',
            description: 'Dynamic entrance from the right side. Creates sense of movement and direction.',
            useCase: 'Content reveals, sequential item displays, directional storytelling.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'slide_in_settle': {
            id: 'slide_in_settle',
            name: 'Slide In Settle',
            description: 'Entrance animation that slides in and settles with a gentle bounce. More dynamic than simple slide.',
            useCase: 'Important announcements, featured content, attention-grabbing reveals.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'bounce': {
            id: 'bounce',
            name: 'Bounce',
            description: 'Playful entrance with bouncing motion. Adds energy and fun to the container.',
            useCase: 'Playful promotions, casual content, youth-oriented campaigns.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'glow': {
            id: 'glow',
            name: 'Glow',
            description: 'Pulsing glow effect around the container. Creates premium, attention-grabbing appearance.',
            useCase: 'Premium offers, special promotions, highlighted content.',
            performanceRisk: 'medium',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'cta_pulse': {
            id: 'cta_pulse',
            name: 'CTA Pulse',
            description: 'Specialized pulse animation for call-to-action buttons. More pronounced than regular pulse.',
            useCase: 'Call-to-action buttons, conversion-focused elements, primary CTAs.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'rotate': {
            id: 'rotate',
            name: 'Rotate',
            description: 'Continuous rotation animation. Creates dynamic, eye-catching movement.',
            useCase: 'Loading states, decorative elements, attention-grabbing backgrounds.',
            performanceRisk: 'medium',
            recommendedLimit: 0, // Infinite, but should be used sparingly
            mobileSafe: false, // Can cause motion sickness
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'blink': {
            id: 'blink',
            name: 'Blink',
            description: 'Attention-grabbing blink effect. High visibility but can be distracting.',
            useCase: 'Urgent announcements, critical alerts, high-priority messages.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'gradient_shift': {
            id: 'gradient_shift',
            name: 'Gradient Shift',
            description: 'Animated gradient background that shifts colors smoothly. Creates depth and movement.',
            useCase: 'Background effects, premium promotions, dynamic visual interest.',
            performanceRisk: 'medium',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'float': {
            id: 'float',
            name: 'Float',
            description: 'Gentle up and down floating motion. Creates sense of lightness and elegance.',
            useCase: 'Decorative elements, subtle background animations, elegant presentations.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'shake': {
            id: 'shake',
            name: 'Shake',
            description: 'Vibrating shake effect. High attention but can be jarring.',
            useCase: 'Error states, urgent alerts, attention-demanding messages.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: false, // Can cause motion sickness
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'zoom_in': {
            id: 'zoom_in',
            name: 'Zoom In',
            description: 'Scale entrance animation that zooms in from smaller size. Creates impact.',
            useCase: 'Important reveals, featured content, dramatic entrances.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'glazing': {
            id: 'glazing',
            name: 'Glazing',
            description: 'Premium frosted glass shine effect. Creates luxury and sophistication.',
            useCase: 'Premium products, high-end promotions, luxury brand content.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'wave': {
            id: 'wave',
            name: 'Wave',
            description: 'Gentle wave motion across the container. Creates organic, flowing movement.',
            useCase: 'Decorative backgrounds, nature-themed content, flowing designs.',
            performanceRisk: 'medium',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'neon_glow': {
            id: 'neon_glow',
            name: 'Neon Glow',
            description: 'Intense neon-style glow effect. High visibility and modern aesthetic.',
            useCase: 'Tech products, modern brands, high-energy promotions.',
            performanceRisk: 'medium',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'shimmer_sweep': {
            id: 'shimmer_sweep',
            name: 'Shimmer Sweep',
            description: 'Enhanced shimmer effect with multiple light sweeps. More pronounced than standard shine.',
            useCase: 'Premium promotions, luxury products, high-value offers.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'number_counter': {
            id: 'number_counter',
            name: 'Number Counter',
            description: 'Animated number counting effect. Counts up from 0 to target value.',
            useCase: 'Statistics, metrics, achievements, countdown timers.',
            performanceRisk: 'low',
            recommendedLimit: 1,
            mobileSafe: true,
            requiresJS: true,
            respectsReducedMotion: true
        },
        
        'hover_microinteraction': {
            id: 'hover_microinteraction',
            name: 'Hover Microinteraction',
            description: 'Subtle interactive effects on hover. Enhances user engagement and feedback.',
            useCase: 'Interactive elements, buttons, clickable content, user feedback.',
            performanceRisk: 'low',
            recommendedLimit: 0, // Triggered by user, not automatic
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        },
        
        'seasonal_ornament_edges': {
            id: 'seasonal_ornament_edges',
            name: 'Seasonal Ornament Edges',
            description: 'Decorative animated borders with seasonal themes. Adds festive touch.',
            useCase: 'Seasonal campaigns, holiday promotions, festive content.',
            performanceRisk: 'low',
            recommendedLimit: 3,
            mobileSafe: true,
            requiresJS: false,
            respectsReducedMotion: true
        }
    };

    /**
     * Animation Rules and Constraints
     */
    const ANIMATION_RULES = {
        MAX_LOOPS: 3,
        RESPECT_REDUCED_MOTION: true,
        NO_HEAVY_JS: true,
        MOBILE_SAFE_DEFAULT: true,
        PERFORMANCE_THRESHOLD: 'medium' // Warn if performance risk is medium or high
    };

    /**
     * Get animation definition by ID
     * @param {string} animationId - Animation identifier
     * @returns {Object|null} Animation definition or null if not found
     */
    function getAnimation(animationId) {
        return ANIMATION_DEFINITIONS[animationId] || null;
    }

    /**
     * Get all available animations
     * @returns {Object} All animation definitions
     */
    function getAllAnimations() {
        return ANIMATION_DEFINITIONS;
    }

    /**
     * Get animation list for admin UI
     * @returns {Array} Array of animation objects with metadata
     */
    function getAnimationList() {
        return Object.keys(ANIMATION_DEFINITIONS).map(function(animationId) {
            const animation = ANIMATION_DEFINITIONS[animationId];
            return {
                id: animation.id,
                name: animation.name,
                description: animation.description,
                useCase: animation.useCase,
                performanceRisk: animation.performanceRisk,
                recommendedLimit: animation.recommendedLimit,
                mobileSafe: animation.mobileSafe,
                requiresJS: animation.requiresJS,
                respectsReducedMotion: animation.respectsReducedMotion
            };
        });
    }

    /**
     * Validate animation selection against rules
     * @param {Array} selectedAnimations - Array of animation IDs
     * @returns {Object} {valid: boolean, warnings: Array, errors: Array}
     */
    function validateAnimationSelection(selectedAnimations) {
        const warnings = [];
        const errors = [];
        
        if (!Array.isArray(selectedAnimations) || selectedAnimations.length === 0) {
            return { valid: true, warnings: [], errors: [] };
        }
        
        // Check for too many animations
        if (selectedAnimations.length > 3) {
            warnings.push('Using more than 3 animations may impact performance and user experience.');
        }
        
        // Check for high-performance-risk animations
        const highRiskAnimations = selectedAnimations.filter(function(animId) {
            const anim = getAnimation(animId);
            return anim && anim.performanceRisk === 'high';
        });
        
        if (highRiskAnimations.length > 0) {
            warnings.push('High-performance-risk animations detected. Use sparingly.');
        }
        
        // Check for non-mobile-safe animations
        const nonMobileSafe = selectedAnimations.filter(function(animId) {
            const anim = getAnimation(animId);
            return anim && !anim.mobileSafe;
        });
        
        if (nonMobileSafe.length > 0) {
            warnings.push('Some animations may cause motion sickness on mobile devices.');
        }
        
        // Check for JS-required animations (if JS is not available)
        const jsRequired = selectedAnimations.filter(function(animId) {
            const anim = getAnimation(animId);
            return anim && anim.requiresJS;
        });
        
        if (jsRequired.length > 0 && typeof window === 'undefined') {
            errors.push('Some animations require JavaScript but JS is not available.');
        }
        
        return {
            valid: errors.length === 0,
            warnings: warnings,
            errors: errors
        };
    }

    /**
     * Get animation CSS classes with reduced motion support
     * @param {Array} animationIds - Array of animation IDs
     * @param {Object} options - Options {maxLoops: number, respectReducedMotion: boolean}
     * @returns {string} Space-separated CSS classes
     */
    function getAnimationClasses(animationIds, options) {
        if (!Array.isArray(animationIds) || animationIds.length === 0) {
            console.log('Amadex Animations: No animation IDs provided');
            return '';
        }
        
        console.log('Amadex Animations: getAnimationClasses called', {
            animationIds: animationIds,
            options: options
        });
        
        const respectReducedMotion = options && options.respectReducedMotion !== undefined ? options.respectReducedMotion : ANIMATION_RULES.RESPECT_REDUCED_MOTION;
        
        // Check if user prefers reduced motion - if so, return empty string (no animations)
        if (respectReducedMotion && prefersReducedMotion()) {
            console.log('Amadex Animations: User prefers reduced motion, skipping animations');
            return '';
        }
        
        const maxLoops = options && options.maxLoops !== undefined ? options.maxLoops : ANIMATION_RULES.MAX_LOOPS;
        
        let classes = '';
        
        animationIds.forEach(function(animId) {
            const anim = getAnimation(animId);
            if (anim) {
                classes += ' amadex-animation-' + anim.id;
                console.log('Amadex Animations: Added animation class', {
                    animId: animId,
                    animName: anim.name,
                    class: 'amadex-animation-' + anim.id
                });
                
                // Add loop limit class if needed
                if (maxLoops > 0 && anim.recommendedLimit > 0) {
                    const loopLimit = Math.min(maxLoops, anim.recommendedLimit);
                    classes += ' amadex-animation-loops-' + loopLimit;
                    console.log('Amadex Animations: Added loop limit class', {
                        maxLoops: maxLoops,
                        recommendedLimit: anim.recommendedLimit,
                        loopLimit: loopLimit,
                        class: 'amadex-animation-loops-' + loopLimit
                    });
                }
            } else {
                console.warn('Amadex Animations: Animation not found', {
                    animId: animId
                });
            }
        });
        
        const finalClasses = classes.trim();
        console.log('Amadex Animations: Final animation classes', {
            classes: finalClasses,
            classCount: finalClasses.split(' ').length
        });
        
        return finalClasses;
    }

    /**
     * Check if user prefers reduced motion
     * @returns {boolean} True if user prefers reduced motion
     */
    function prefersReducedMotion() {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return false;
        }
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    // Export for use in different contexts
    if (typeof module !== 'undefined' && module.exports) {
        // Node.js/CommonJS
        module.exports = {
            getAnimation: getAnimation,
            getAllAnimations: getAllAnimations,
            getAnimationList: getAnimationList,
            validateAnimationSelection: validateAnimationSelection,
            getAnimationClasses: getAnimationClasses,
            prefersReducedMotion: prefersReducedMotion,
            ANIMATION_DEFINITIONS: ANIMATION_DEFINITIONS,
            ANIMATION_RULES: ANIMATION_RULES
        };
    } else if (typeof define === 'function' && define.amd) {
        // AMD
        define([], function() {
            return {
                getAnimation: getAnimation,
                getAllAnimations: getAllAnimations,
                getAnimationList: getAnimationList,
                validateAnimationSelection: validateAnimationSelection,
                getAnimationClasses: getAnimationClasses,
                prefersReducedMotion: prefersReducedMotion,
                ANIMATION_DEFINITIONS: ANIMATION_DEFINITIONS,
                ANIMATION_RULES: ANIMATION_RULES
            };
        });
    } else {
        // Browser global
        global.AmadexAnimations = {
            getAnimation: getAnimation,
            getAllAnimations: getAllAnimations,
            getAnimationList: getAnimationList,
            validateAnimationSelection: validateAnimationSelection,
            getAnimationClasses: getAnimationClasses,
            prefersReducedMotion: prefersReducedMotion,
            ANIMATION_DEFINITIONS: ANIMATION_DEFINITIONS,
            ANIMATION_RULES: ANIMATION_RULES
        };
    }

})(typeof window !== 'undefined' ? window : this);
