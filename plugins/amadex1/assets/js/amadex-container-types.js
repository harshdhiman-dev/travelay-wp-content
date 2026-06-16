/**
 * Amadex Container Types
 * 
 * Skyscanner-inspired container type definitions with standard dimensions,
 * use cases, and constraints.
 * 
 * @package Amadex
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    /**
     * Container Type Definitions
     * Each type defines:
     * - id: Unique identifier
     * - name: Display name
     * - description: Detailed description
     * - useCase: When to use this type
     * - uniqueness: What makes it unique
     * - dimensions: Standard dimensions {width, height, unit}
     * - constraints: Min/max dimensions and aspect ratio
     * - responsive: How it behaves on different screens
     * - cssClass: CSS class to apply
     */
    const CONTAINER_TYPE_DEFINITIONS = {
        'standard_320x50': {
            id: 'standard_320x50',
            name: 'Standard Display Banner (320×50)',
            description: 'Mobile Leaderboard - Compact horizontal banner perfect for mobile screens. Standard IAB size for mobile advertising.',
            useCase: 'Mobile-first promotions, price alerts, quick CTAs. Ideal for placement between search results on mobile devices.',
            uniqueness: 'Smallest standard banner size, optimized for mobile viewports. Non-intrusive and lightweight.',
            dimensions: { width: 320, height: 50, unit: 'px' },
            constraints: {
                minWidth: 300,
                maxWidth: 400,
                minHeight: 50,
                maxHeight: 60,
                aspectRatio: '6.4:1',
                lockAspectRatio: true
            },
            responsive: {
                desktop: { width: '320px', height: '50px' },
                tablet: { width: '320px', height: '50px' },
                mobile: { width: '100%', maxWidth: '320px', height: '50px' }
            },
            cssClass: 'amadex-container-type-320x50'
        },
        
        'standard_320x100': {
            id: 'standard_320x100',
            name: 'Standard Display Banner (320×100)',
            description: 'Mobile Large Banner - Taller mobile banner with more space for content. Standard IAB mobile banner size.',
            useCase: 'Mobile promotions with more content, airline ads, product showcases. Better for detailed messaging on mobile.',
            uniqueness: 'Double the height of 320×50, allowing for richer content while remaining mobile-optimized.',
            dimensions: { width: 320, height: 100, unit: 'px' },
            constraints: {
                minWidth: 300,
                maxWidth: 400,
                minHeight: 90,
                maxHeight: 120,
                aspectRatio: '3.2:1',
                lockAspectRatio: true
            },
            responsive: {
                desktop: { width: '320px', height: '100px' },
                tablet: { width: '320px', height: '100px' },
                mobile: { width: '100%', maxWidth: '320px', height: '100px' }
            },
            cssClass: 'amadex-container-type-320x100'
        },
        
        'standard_300x250': {
            id: 'standard_300x250',
            name: 'Standard Display Banner (300×250)',
            description: 'Medium Rectangle - The most common display ad size. Perfect for sidebar placements and inline content.',
            useCase: 'Sidebar promotions, inline content ads, product features. Universal compatibility across all devices.',
            uniqueness: 'Most widely supported ad size. Square-ish format allows for balanced content layout.',
            dimensions: { width: 300, height: 250, unit: 'px' },
            constraints: {
                minWidth: 280,
                maxWidth: 320,
                minHeight: 230,
                maxHeight: 270,
                aspectRatio: '6:5',
                lockAspectRatio: true
            },
            responsive: {
                desktop: { width: '300px', height: '250px' },
                tablet: { width: '300px', height: '250px' },
                mobile: { width: '100%', maxWidth: '300px', height: '250px' }
            },
            cssClass: 'amadex-container-type-300x250'
        },
        
        'standard_728x90': {
            id: 'standard_728x90',
            name: 'Standard Display Banner (728×90)',
            description: 'Leaderboard - Classic desktop banner format. Perfect for top-of-page placements.',
            useCase: 'Desktop header promotions, top-of-page announcements, major campaign banners.',
            uniqueness: 'Wide format optimized for desktop screens. Maximum horizontal visibility.',
            dimensions: { width: 728, height: 90, unit: 'px' },
            constraints: {
                minWidth: 700,
                maxWidth: 750,
                minHeight: 85,
                maxHeight: 95,
                aspectRatio: '8.09:1',
                lockAspectRatio: true
            },
            responsive: {
                desktop: { width: '728px', height: '90px' },
                tablet: { width: '100%', maxWidth: '728px', height: '90px' },
                mobile: { width: '100%', height: '90px' }
            },
            cssClass: 'amadex-container-type-728x90'
        },
        
        'standard_300x600': {
            id: 'standard_300x600',
            name: 'Standard Display Banner (300×600)',
            description: 'Half Page - Tall vertical banner perfect for sidebar placements. High visibility format.',
            useCase: 'Sidebar promotions, vertical content showcases, detailed product features. Great for desktop layouts.',
            uniqueness: 'Tall format maximizes vertical space. Perfect for scrolling content and detailed messaging.',
            dimensions: { width: 300, height: 600, unit: 'px' },
            constraints: {
                minWidth: 280,
                maxWidth: 320,
                minHeight: 580,
                maxHeight: 620,
                aspectRatio: '1:2',
                lockAspectRatio: true
            },
            responsive: {
                desktop: { width: '300px', height: '600px' },
                tablet: { width: '300px', height: '600px' },
                mobile: { width: '100%', maxWidth: '300px', height: '600px' }
            },
            cssClass: 'amadex-container-type-300x600'
        },
        
        'native_inline_card': {
            id: 'native_inline_card',
            name: 'Native Inline Card',
            description: 'Seamlessly blends with content flow. Matches site design and feels like editorial content.',
            useCase: 'Content-integrated promotions, sponsored content, native advertising. Best for maintaining user experience.',
            uniqueness: 'No fixed dimensions - adapts to container width. Feels like part of the page, not an ad.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: 200,
                maxWidth: '100%',
                minHeight: 'auto',
                maxHeight: 'none',
                aspectRatio: 'none',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', maxWidth: '1200px', height: 'auto' },
                tablet: { width: '100%', maxWidth: '768px', height: 'auto' },
                mobile: { width: '100%', height: 'auto' }
            },
            cssClass: 'amadex-container-type-native-inline'
        },
        
        'itinerary_style_native': {
            id: 'itinerary_style_native',
            name: 'Itinerary Style Native',
            description: 'Travel-focused native format that mimics flight result cards. Perfect for travel-related promotions.',
            useCase: 'Flight promotions, travel deals, itinerary-based offers. Blends with flight search results.',
            uniqueness: 'Designed to match flight card aesthetics. Includes date ranges, destinations, and pricing naturally.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: 300,
                maxWidth: '100%',
                minHeight: 120,
                maxHeight: 'none',
                aspectRatio: 'none',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', maxWidth: '800px', height: 'auto' },
                tablet: { width: '100%', maxWidth: '768px', height: 'auto' },
                mobile: { width: '100%', height: 'auto' }
            },
            cssClass: 'amadex-container-type-itinerary-native'
        },
        
        'brand_banner': {
            id: 'brand_banner',
            name: 'Brand Banner',
            description: 'Large format banner for brand awareness campaigns. High-impact visual format.',
            useCase: 'Brand campaigns, major promotions, seasonal announcements. Maximum visibility and impact.',
            uniqueness: 'Flexible dimensions allow for creative freedom. Optimized for brand storytelling.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: 600,
                maxWidth: '100%',
                minHeight: 200,
                maxHeight: 600,
                aspectRatio: '16:9',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', maxWidth: '1200px', height: 'auto', minHeight: '200px' },
                tablet: { width: '100%', maxWidth: '768px', height: 'auto', minHeight: '180px' },
                mobile: { width: '100%', height: 'auto', minHeight: '150px' }
            },
            cssClass: 'amadex-container-type-brand-banner'
        },
        
        'carousel': {
            id: 'carousel',
            name: 'Carousel',
            description: 'Multi-slide carousel for showcasing multiple promotions or features. Auto-rotates through content.',
            useCase: 'Multiple promotions, feature showcases, rotating offers. Maximizes space efficiency.',
            uniqueness: 'Single container displays multiple items. Time-based rotation keeps content fresh.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: 300,
                maxWidth: '100%',
                minHeight: 200,
                maxHeight: 500,
                aspectRatio: 'none',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', maxWidth: '1200px', height: 'auto', minHeight: '250px' },
                tablet: { width: '100%', maxWidth: '768px', height: 'auto', minHeight: '220px' },
                mobile: { width: '100%', height: 'auto', minHeight: '200px' }
            },
            cssClass: 'amadex-container-type-carousel'
        },
        
        'hero_takeover': {
            id: 'hero_takeover',
            name: 'Hero Takeover',
            description: 'Full-width hero section that takes over the viewport. Maximum visual impact.',
            useCase: 'Major campaigns, launch announcements, premium promotions. Above-the-fold impact.',
            uniqueness: 'Full viewport width with flexible height. Dominates the visual hierarchy.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: '100%',
                maxWidth: '100%',
                minHeight: 400,
                maxHeight: 800,
                aspectRatio: 'none',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', height: 'auto', minHeight: '500px' },
                tablet: { width: '100%', height: 'auto', minHeight: '400px' },
                mobile: { width: '100%', height: 'auto', minHeight: '300px' }
            },
            cssClass: 'amadex-container-type-hero-takeover'
        },
        
        'in_banner_video': {
            id: 'in_banner_video',
            name: 'In-Banner Video',
            description: 'Video-enabled banner with thumbnail and play button. Supports video content within banner format.',
            useCase: 'Video promotions, product demos, brand storytelling. Engaging multimedia format.',
            uniqueness: 'Combines static banner benefits with video engagement. Thumbnail preview with play overlay.',
            dimensions: { width: '100%', height: 'auto', unit: 'responsive' },
            constraints: {
                minWidth: 300,
                maxWidth: '100%',
                minHeight: 200,
                maxHeight: 600,
                aspectRatio: '16:9',
                lockAspectRatio: false
            },
            responsive: {
                desktop: { width: '100%', maxWidth: '1200px', height: 'auto', minHeight: '300px' },
                tablet: { width: '100%', maxWidth: '768px', height: 'auto', minHeight: '250px' },
                mobile: { width: '100%', height: 'auto', minHeight: '200px' }
            },
            cssClass: 'amadex-container-type-in-banner-video'
        }
    };

    /**
     * Get container type definition by ID
     * @param {string} typeId - Container type identifier
     * @returns {Object|null} Container type definition or null if not found
     */
    function getContainerType(typeId) {
        return CONTAINER_TYPE_DEFINITIONS[typeId] || null;
    }

    /**
     * Get all available container types
     * @returns {Object} All container type definitions
     */
    function getAllContainerTypes() {
        return CONTAINER_TYPE_DEFINITIONS;
    }

    /**
     * Get container type list for admin UI
     * @returns {Array} Array of {id, name, description, useCase, uniqueness} objects
     */
    function getContainerTypeList() {
        return Object.keys(CONTAINER_TYPE_DEFINITIONS).map(function(typeId) {
            const type = CONTAINER_TYPE_DEFINITIONS[typeId];
            return {
                id: type.id,
                name: type.name,
                description: type.description,
                useCase: type.useCase,
                uniqueness: type.uniqueness,
                dimensions: type.dimensions
            };
        });
    }

    /**
     * Get container type constraints
     * @param {string} typeId - Container type identifier
     * @returns {Object|null} Constraints object or null
     */
    function getContainerTypeConstraints(typeId) {
        const type = getContainerType(typeId);
        return type ? type.constraints : null;
    }

    /**
     * Get responsive dimensions for container type
     * @param {string} typeId - Container type identifier
     * @param {string} breakpoint - 'desktop', 'tablet', or 'mobile'
     * @returns {Object|null} Responsive dimensions or null
     */
    function getResponsiveDimensions(typeId, breakpoint) {
        const type = getContainerType(typeId);
        if (!type || !type.responsive) {
            return null;
        }
        return type.responsive[breakpoint] || type.responsive.desktop || null;
    }

    /**
     * Apply container type constraints to container config
     * @param {Object} container - Container configuration
     * @param {string} typeId - Container type identifier
     * @returns {Object} Updated container config with constraints applied
     */
    function applyContainerTypeConstraints(container, typeId) {
        const type = getContainerType(typeId);
        if (!type) {
            return container;
        }

        const constraints = type.constraints;
        const responsive = type.responsive;
        
        // Create a copy to avoid mutating original
        const updated = Object.assign({}, container);
        
        // Apply dimension constraints if lockAspectRatio is true
        if (constraints.lockAspectRatio && constraints.aspectRatio) {
            // Parse aspect ratio (e.g., "16:9" -> {width: 16, height: 9})
            const ratioParts = constraints.aspectRatio.split(':');
            const ratioWidth = parseFloat(ratioParts[0]);
            const ratioHeight = parseFloat(ratioParts[1]);
            
            // If width is set, calculate height to maintain aspect ratio
            if (updated.container_width_value && updated.container_width_unit === 'px') {
                const calculatedHeight = (updated.container_width_value / ratioWidth) * ratioHeight;
                updated.container_height_value = Math.round(calculatedHeight);
                updated.container_height_unit = 'px';
            }
            // If height is set, calculate width to maintain aspect ratio
            else if (updated.container_height_value && updated.container_height_unit === 'px') {
                const calculatedWidth = (updated.container_height_value / ratioHeight) * ratioWidth;
                updated.container_width_value = Math.round(calculatedWidth);
                updated.container_width_unit = 'px';
            }
        }
        
        // Apply min/max constraints
        if (constraints.minWidth && updated.container_width_value) {
            if (updated.container_width_value < constraints.minWidth) {
                updated.container_width_value = constraints.minWidth;
            }
        }
        if (constraints.maxWidth && constraints.maxWidth !== '100%' && updated.container_width_value) {
            if (updated.container_width_value > constraints.maxWidth) {
                updated.container_width_value = constraints.maxWidth;
            }
        }
        if (constraints.minHeight && updated.container_height_value) {
            if (updated.container_height_value < constraints.minHeight) {
                updated.container_height_value = constraints.minHeight;
            }
        }
        if (constraints.maxHeight && constraints.maxHeight !== 'none' && updated.container_height_value) {
            if (updated.container_height_value > constraints.maxHeight) {
                updated.container_height_value = constraints.maxHeight;
            }
        }
        
        return updated;
    }

    // Export for use in different contexts
    if (typeof module !== 'undefined' && module.exports) {
        // Node.js/CommonJS
        module.exports = {
            getContainerType: getContainerType,
            getAllContainerTypes: getAllContainerTypes,
            getContainerTypeList: getContainerTypeList,
            getContainerTypeConstraints: getContainerTypeConstraints,
            getResponsiveDimensions: getResponsiveDimensions,
            applyContainerTypeConstraints: applyContainerTypeConstraints,
            CONTAINER_TYPE_DEFINITIONS: CONTAINER_TYPE_DEFINITIONS
        };
    } else if (typeof define === 'function' && define.amd) {
        // AMD
        define([], function() {
            return {
                getContainerType: getContainerType,
                getAllContainerTypes: getAllContainerTypes,
                getContainerTypeList: getContainerTypeList,
                getContainerTypeConstraints: getContainerTypeConstraints,
                getResponsiveDimensions: getResponsiveDimensions,
                applyContainerTypeConstraints: applyContainerTypeConstraints,
                CONTAINER_TYPE_DEFINITIONS: CONTAINER_TYPE_DEFINITIONS
            };
        });
    } else {
        // Browser global
        global.AmadexContainerTypes = {
            getContainerType: getContainerType,
            getAllContainerTypes: getAllContainerTypes,
            getContainerTypeList: getContainerTypeList,
            getContainerTypeConstraints: getContainerTypeConstraints,
            getResponsiveDimensions: getResponsiveDimensions,
            applyContainerTypeConstraints: applyContainerTypeConstraints,
            CONTAINER_TYPE_DEFINITIONS: CONTAINER_TYPE_DEFINITIONS
        };
    }

})(typeof window !== 'undefined' ? window : this);
