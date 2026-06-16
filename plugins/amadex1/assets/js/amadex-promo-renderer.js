/**
 * Amadex Promotional Container Renderer
 * 
 * Shared rendering engine for both admin preview and frontend display.
 * Ensures 1:1 parity between preview and live output.
 * 
 * @package Amadex
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Convert hex color to RGBA
     * @param {string} hex - Hex color (#RRGGBB)
     * @param {number} alpha - Alpha value (0-1)
     * @returns {string} RGBA color string
     */
    function hexToRgba(hex, alpha) {
        if (!hex || typeof hex !== 'string' || hex.length < 7) {
            return 'rgba(0, 0, 0, ' + alpha + ')';
        }
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    /**
     * Calculate contrast color (black or white) based on background
     * @param {string} hex - Hex color (#RRGGBB)
     * @returns {string} Contrast color (#111827 for light, #ffffff for dark)
     */
    function getContrastColor(hex) {
        if (!hex || typeof hex !== 'string' || hex.length < 7) {
            return '#111827';
        }
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.5 ? '#111827' : '#ffffff';
    }

    /**
     * Compile container config into CSS variables
     * @param {Object} container - Container configuration object
     * @param {string} device - Device type ('desktop', 'tablet', 'mobile'), defaults to 'desktop'
     * @returns {string} CSS variable string for inline style
     */
    function compileCSSVariables(container, device) {
        device = device || 'desktop';
        const vars = [];
        
        // Get device-specific dimensions if available
        let deviceDimensions = null;
        if (container.dimensions && container.dimensions[device]) {
            deviceDimensions = container.dimensions[device];
        }
        
        // Width controls - use device-specific if available, fallback to global
        let width = '100%';
        if (deviceDimensions && deviceDimensions.width_value !== undefined && deviceDimensions.width_unit) {
            width = deviceDimensions.width_value + deviceDimensions.width_unit;
        } else if (container.container_width_value !== undefined && container.container_width_unit) {
            width = container.container_width_value + container.container_width_unit;
        } else if (container.container_width) {
            // Legacy support
            if (container.container_width === 'full') width = '100%';
            else if (container.container_width === 'compact') width = '65%';
            else if (container.container_width === 'mini') width = '45%';
        }
        vars.push('--pc-width: ' + width);
        
        // Max width - use device-specific if available
        if (deviceDimensions && deviceDimensions.max_width_value !== undefined && deviceDimensions.max_width_value !== '') {
            const maxWidthUnit = deviceDimensions.max_width_unit || 'px';
            vars.push('--pc-max-width: ' + deviceDimensions.max_width_value + maxWidthUnit);
        } else if (container.container_max_width_value !== undefined && container.container_max_width_value !== '') {
            const maxWidthUnit = container.container_max_width_unit || 'px';
            vars.push('--pc-max-width: ' + container.container_max_width_value + maxWidthUnit);
        }
        
        // Min width - use device-specific if available
        if (deviceDimensions && deviceDimensions.min_width_value !== undefined && deviceDimensions.min_width_value !== '') {
            const minWidthUnit = deviceDimensions.min_width_unit || 'px';
            vars.push('--pc-min-width: ' + deviceDimensions.min_width_value + minWidthUnit);
        } else if (container.container_min_width_value !== undefined && container.container_min_width_value !== '') {
            const minWidthUnit = container.container_min_width_unit || 'px';
            vars.push('--pc-min-width: ' + container.container_min_width_value + minWidthUnit);
        }
        
        // Height controls - use device-specific if available
        let height = 'auto';
        if (deviceDimensions && deviceDimensions.height_value !== undefined && deviceDimensions.height_unit && deviceDimensions.height_unit !== 'auto') {
            height = deviceDimensions.height_value + deviceDimensions.height_unit;
        } else if (container.container_height_value !== undefined && container.container_height_unit && container.container_height_unit !== 'auto') {
            height = container.container_height_value + container.container_height_unit;
        }
        vars.push('--pc-height: ' + height);
        
        // Max height - use device-specific if available
        if (deviceDimensions && deviceDimensions.max_height_value !== undefined && deviceDimensions.max_height_value !== '') {
            const maxHeightUnit = deviceDimensions.max_height_unit || 'px';
            vars.push('--pc-max-height: ' + deviceDimensions.max_height_value + maxHeightUnit);
        } else if (container.container_max_height_value !== undefined && container.container_max_height_value !== '') {
            const maxHeightUnit = container.container_max_height_unit || 'px';
            vars.push('--pc-max-height: ' + container.container_max_height_value + maxHeightUnit);
        }
        
        // Min height - use device-specific if available
        if (deviceDimensions && deviceDimensions.min_height_value !== undefined && deviceDimensions.min_height_value !== '') {
            const minHeightUnit = deviceDimensions.min_height_unit || 'px';
            vars.push('--pc-min-height: ' + deviceDimensions.min_height_value + minHeightUnit);
        } else if (container.container_min_height_value !== undefined && container.container_min_height_value !== '') {
            const minHeightUnit = container.container_min_height_unit || 'px';
            vars.push('--pc-min-height: ' + container.container_min_height_value + minHeightUnit);
        }
        
        // Padding controls - handle different modes
        const paddingMode = container.container_padding_mode || 'uniform';
        
        if (paddingMode === 'uniform' && container.container_padding_all !== undefined && container.container_padding_all !== '') {
            const paddingUnit = container.container_padding_all_unit || 'px';
            vars.push('--pc-padding: ' + container.container_padding_all + paddingUnit);
        } else if (paddingMode === 'xy') {
            // X/Y padding mode (horizontal/vertical)
            if (container.container_padding_x !== undefined && container.container_padding_x !== '') {
                const paddingXUnit = container.container_padding_x_unit || 'px';
                vars.push('--pc-padding-left: ' + container.container_padding_x + paddingXUnit);
                vars.push('--pc-padding-right: ' + container.container_padding_x + paddingXUnit);
            }
            if (container.container_padding_y !== undefined && container.container_padding_y !== '') {
                const paddingYUnit = container.container_padding_y_unit || 'px';
                vars.push('--pc-padding-top: ' + container.container_padding_y + paddingYUnit);
                vars.push('--pc-padding-bottom: ' + container.container_padding_y + paddingYUnit);
            }
        } else if (paddingMode === 'individual') {
            // Individual padding sides
            if (container.container_padding_top !== undefined && container.container_padding_top !== '') {
                vars.push('--pc-padding-top: ' + container.container_padding_top + 'px');
            }
            if (container.container_padding_right !== undefined && container.container_padding_right !== '') {
                vars.push('--pc-padding-right: ' + container.container_padding_right + 'px');
            }
            if (container.container_padding_bottom !== undefined && container.container_padding_bottom !== '') {
                vars.push('--pc-padding-bottom: ' + container.container_padding_bottom + 'px');
            }
            if (container.container_padding_left !== undefined && container.container_padding_left !== '') {
                vars.push('--pc-padding-left: ' + container.container_padding_left + 'px');
            }
        } else {
            // Fallback: check for legacy individual padding
            const paddingTop = (container.container_padding_top !== undefined && container.container_padding_top !== '') ? container.container_padding_top + 'px' : '0';
            const paddingRight = (container.container_padding_right !== undefined && container.container_padding_right !== '') ? container.container_padding_right + 'px' : '0';
            const paddingBottom = (container.container_padding_bottom !== undefined && container.container_padding_bottom !== '') ? container.container_padding_bottom + 'px' : '0';
            const paddingLeft = (container.container_padding_left !== undefined && container.container_padding_left !== '') ? container.container_padding_left + 'px' : '0';
            
            if (paddingTop !== '0' || paddingRight !== '0' || paddingBottom !== '0' || paddingLeft !== '0') {
                vars.push('--pc-padding-top: ' + paddingTop);
                vars.push('--pc-padding-right: ' + paddingRight);
                vars.push('--pc-padding-bottom: ' + paddingBottom);
                vars.push('--pc-padding-left: ' + paddingLeft);
            }
        }
        
        // Gap controls (for grid layouts)
        if (container.container_gap_column !== undefined && container.container_gap_column !== '') {
            const gapUnit = container.container_gap_column_unit || 'px';
            vars.push('--pc-gap-column: ' + container.container_gap_column + gapUnit);
        }
        if (container.container_gap_row !== undefined && container.container_gap_row !== '') {
            const gapUnit = container.container_gap_row_unit || 'px';
            vars.push('--pc-gap-row: ' + container.container_gap_row + gapUnit);
        }
        
        // Border radius
        if (container.container_border_radius !== undefined && container.container_border_radius !== '') {
            const radiusUnit = container.container_border_radius_unit || 'px';
            vars.push('--pc-border-radius: ' + container.container_border_radius + radiusUnit);
        }
        
        // Typography scale
        if (container.container_typography_scale !== undefined && container.container_typography_scale !== '') {
            const typographyScale = parseFloat(container.container_typography_scale) || 1.0;
            vars.push('--pc-typography-scale: ' + typographyScale);
        }
        
        // Compactness (affects overall spacing density)
        if (container.container_compactness !== undefined && container.container_compactness !== '') {
            const compactness = parseFloat(container.container_compactness) || 50;
            const compactnessRatio = compactness / 100; // 0-1 scale
            vars.push('--pc-compactness: ' + compactnessRatio);
        }
        
        // Animation controls (intensity, duration, delay, max loops)
        const animations = (container.animations && Array.isArray(container.animations)) ? container.animations : [];
        if (animations.length > 0) {
            const animationIntensity = container.animation_intensity !== undefined ? parseFloat(container.animation_intensity) : 50;
            const intensityRatio = animationIntensity / 100;
            vars.push('--amadex-intensity: ' + intensityRatio);
            
            // Animation duration (in seconds or "infinite")
            if (container.animation_duration !== undefined && container.animation_duration !== '') {
                if (container.animation_duration === 'infinite' || container.animation_duration.toLowerCase() === 'infinite') {
                    vars.push('--amadex-duration: infinite');
                } else {
                    const duration = parseFloat(container.animation_duration) || 2.0;
                    vars.push('--amadex-duration: ' + duration + 's');
                }
            }
            
            // Animation delay (in seconds)
            if (container.animation_delay !== undefined && container.animation_delay !== '') {
                const delay = parseFloat(container.animation_delay) || 0;
                vars.push('--amadex-delay: ' + delay + 's');
            }
            
        }
        
        return vars.length > 0 ? vars.join('; ') + ';' : '';
    }

    /**
     * Build background color style from container config
     * @param {Object} container - Container configuration
     * @returns {Object} Object with backgroundColorStyle and primaryColor
     */
    function buildBackgroundStyle(container) {
        let backgroundColorStyle = '';
        let primaryColor = '#0e7d3f'; // Default for contrast calculation
        const colorType = container.container_color_type || 'default';
        
        if (colorType !== 'default') {
            primaryColor = container.container_color_primary || '#0e7d3f';
            const opacity = container.container_color_opacity !== undefined ? parseFloat(container.container_color_opacity) : 100;
            const opacityDecimal = opacity / 100;
            
            if (colorType === 'solid') {
                backgroundColorStyle = 'background: ' + hexToRgba(primaryColor, opacityDecimal) + ';';
            } else if (colorType === 'gradient_2' || colorType === 'gradient_3') {
                const secondaryColor = container.container_color_secondary || '#22af5c';
                let gradientDirection = container.container_gradient_direction || 'to right';
                const gradientAngle = container.container_gradient_angle !== undefined ? parseInt(container.container_gradient_angle) : 135;
                
                if (gradientDirection === 'custom') {
                    gradientDirection = gradientAngle + 'deg';
                }
                
                if (colorType === 'gradient_2') {
                    backgroundColorStyle = 'background: linear-gradient(' + gradientDirection + ', ' + hexToRgba(primaryColor, opacityDecimal) + ', ' + hexToRgba(secondaryColor, opacityDecimal) + ');';
                } else {
                    const tertiaryColor = container.container_color_tertiary || '#f97316';
                    const stops = container.gradient_stops || [0, 50, 100];
                    backgroundColorStyle = 'background: linear-gradient(' + gradientDirection + ', ' + hexToRgba(primaryColor, opacityDecimal) + ' ' + stops[0] + '%, ' + hexToRgba(secondaryColor, opacityDecimal) + ' ' + stops[1] + '%, ' + hexToRgba(tertiaryColor, opacityDecimal) + ' ' + stops[2] + '%);';
                }
            }
        }
        
        return {
            backgroundColorStyle: backgroundColorStyle,
            primaryColor: primaryColor
        };
    }

    /**
     * Build text color styles from container config
     * @param {Object} container - Container configuration
     * @param {string} primaryColor - Primary background color for contrast calculation
     * @returns {Object} Object with headingColorStyle and bodyColorStyle
     */
    function buildTextColorStyles(container, primaryColor) {
        let headingColorStyle = '';
        let bodyColorStyle = '';
        const colorType = container.container_color_type || 'default';
        const textColorAuto = container.text_color_auto !== undefined ? container.text_color_auto : true;
        
        if (!textColorAuto) {
            // Manual mode: get heading and body colors separately
            let headingColor = container.container_heading_color || '';
            let bodyColor = container.container_body_color || '';
            
            // Legacy support: if old container_text_color exists, use it for both
            if (!headingColor && !bodyColor && container.container_text_color) {
                headingColor = container.container_text_color;
                bodyColor = container.container_text_color;
            }
            
            // If only one is set, auto-calculate the other from background
            if (headingColor && !bodyColor && colorType !== 'default') {
                bodyColor = getContrastColor(primaryColor);
            } else if (!headingColor && bodyColor && colorType !== 'default') {
                headingColor = getContrastColor(primaryColor);
            } else if (!headingColor && !bodyColor && colorType !== 'default') {
                // Both empty, use auto-calculated
                headingColor = getContrastColor(primaryColor);
                bodyColor = getContrastColor(primaryColor);
            } else if (!headingColor && !bodyColor) {
                // Default colors when no background customization
                headingColor = '#111827';
                bodyColor = '#6b7280';
            }
            
            headingColorStyle = 'color: ' + (headingColor || '#111827') + ';';
            bodyColorStyle = 'color: ' + (bodyColor || '#6b7280') + ';';
        } else if (colorType !== 'default') {
            // Auto-calculate both from background
            const autoTextColor = getContrastColor(primaryColor);
            headingColorStyle = 'color: ' + autoTextColor + ';';
            bodyColorStyle = 'color: ' + autoTextColor + ';';
        }
        
        return {
            headingColorStyle: headingColorStyle,
            bodyColorStyle: bodyColorStyle
        };
    }

    /**
     * Build animation classes from container config
     * @param {Object} container - Container configuration
     * @returns {string} Space-separated animation classes
     */
    function buildAnimationClasses(container) {
        const animations = (container.animations && Array.isArray(container.animations)) ? container.animations : [];
        let animationClasses = '';
        
        if (animations.length > 0) {
            animations.forEach(function(anim) {
                animationClasses += ' amadex-animation-' + anim;
            });
            
            if (container.animation_mobile_disabled) {
                animationClasses += ' amadex-animation-mobile-disabled';
            }
        }
        
        return animationClasses;
    }

    /**
     * Render promotional container HTML
     * 
     * This is the SINGLE SOURCE OF TRUTH for container rendering.
     * Used by both admin preview and frontend display.
     * 
     * @param {Object} container - Container configuration object
     * @param {string} containerId - Unique container identifier
     * @returns {string} HTML string for the container
     */
    function renderPromotionalContainer(container, containerId, device) {
        try {
            // Validate inputs
            if (!container || typeof container !== 'object') {
                throw new Error('Invalid container object provided');
            }
            
            // Determine device (default to desktop, or from container.current_device, or from parameter)
            device = device || container.current_device || 'desktop';
            
            const type = container.type || 'price_alert';
            const templateId = container.template_id || null;
            const containerTypeId = container.container_type_id || null;
            const title = container.title || '';
            const description = container.description || '';
            const buttonText = container.button_text || 'Track prices';
            const imageUrl = container.image_url || '';
            const linkUrl = container.link_url || '';
            const additionalData = container.additional_data || {};
            
            // Apply container type constraints if specified
            let processedContainer = container;
            if (containerTypeId && typeof AmadexContainerTypes !== 'undefined') {
                processedContainer = AmadexContainerTypes.applyContainerTypeConstraints(container, containerTypeId);
            }
            
            // Compile CSS variables (use processed container with constraints applied, pass device)
            const cssVars = compileCSSVariables(processedContainer, device);
            
            // Build background style
            const bgStyle = buildBackgroundStyle(container);
            const backgroundColorStyle = bgStyle.backgroundColorStyle;
            const primaryColor = bgStyle.primaryColor;
            
            // Build text color styles
            const textColors = buildTextColorStyles(container, primaryColor);
            const headingColorStyle = textColors.headingColorStyle;
            const bodyColorStyle = textColors.bodyColorStyle;
            
            // Build animation classes
            const animationClasses = buildAnimationClasses(container);
            
            // Build inline styles - combine CSS variables with legacy inline styles for backward compatibility
            let inlineStyles = '';
            if (cssVars) {
                inlineStyles += cssVars + ' ';
            }
            
            // Apply container type dimensions if specified
            let containerWidth = '100%';
            let containerHeight = 'auto';
            
            if (containerTypeId && typeof AmadexContainerTypes !== 'undefined') {
                const containerType = AmadexContainerTypes.getContainerType(containerTypeId);
                if (containerType) {
                    // Use container type default dimensions
                    if (containerType.dimensions.width !== '100%') {
                        containerWidth = containerType.dimensions.width + (containerType.dimensions.unit || 'px');
                    } else {
                        containerWidth = containerType.dimensions.width;
                    }
                    if (containerType.dimensions.height !== 'auto') {
                        containerHeight = containerType.dimensions.height + (containerType.dimensions.unit || 'px');
                    } else {
                        containerHeight = containerType.dimensions.height;
                    }
                }
            }
            
            // Legacy width/height for backward compatibility (will be overridden by CSS variables if present)
            if (processedContainer.container_width_value !== undefined && processedContainer.container_width_unit) {
                containerWidth = processedContainer.container_width_value + processedContainer.container_width_unit;
            } else if (processedContainer.container_width) {
                if (processedContainer.container_width === 'full') containerWidth = '100%';
                else if (processedContainer.container_width === 'compact') containerWidth = '65%';
                else if (processedContainer.container_width === 'mini') containerWidth = '45%';
            }
            inlineStyles += 'width: ' + containerWidth + ';';
            
            if (processedContainer.container_height_value !== undefined && processedContainer.container_height_unit && processedContainer.container_height_unit !== 'auto') {
                containerHeight = processedContainer.container_height_value + processedContainer.container_height_unit;
            }
            if (containerHeight !== 'auto') {
                inlineStyles += ' height: ' + containerHeight + ';';
            }
            
            // Base CSS classes
            let cssClasses = 'amadex-promotional-container';
            cssClasses += ' amadex-promo-type-' + type;
            if (templateId) {
                cssClasses += ' amadex-template-' + templateId;
            }
            if (containerTypeId) {
                const containerType = AmadexContainerTypes ? AmadexContainerTypes.getContainerType(containerTypeId) : null;
                if (containerType && containerType.cssClass) {
                    cssClasses += ' ' + containerType.cssClass;
                }
            }
            // Add animation classes (ensure proper spacing)
            if (animationClasses && animationClasses.trim()) {
                cssClasses += ' ' + animationClasses.trim();
            }
            
            // Debug: Log final classes
            if (animationClasses && animationClasses.trim()) {
            }
            
            let html = '<div class="' + cssClasses + '" style="' + inlineStyles + '" data-container-id="' + escapeHtml(containerId) + '" data-container-type="' + escapeHtml(type) + '"';
            if (templateId) {
                html += ' data-template-id="' + escapeHtml(templateId) + '"';
            }
            if (containerTypeId) {
                html += ' data-container-type-id="' + escapeHtml(containerTypeId) + '"';
            }
            html += '>';
            
            // Check if template-based rendering is requested
            if (templateId && typeof AmadexPromoTemplates !== 'undefined') {
                const template = AmadexPromoTemplates.getTemplate(templateId);
                if (template && typeof template.render === 'function') {
                    // Use template renderer
                    const templateHtml = template.render(container, {
                        escapeHtml: escapeHtml,
                        headingColorStyle: headingColorStyle,
                        bodyColorStyle: bodyColorStyle,
                        backgroundColorStyle: backgroundColorStyle,
                        primaryColor: primaryColor
                    });
                    html += templateHtml;
                    html += '</div>';
                    return html;
                }
            }
            
            // Fallback to legacy type-based rendering
            if (type === 'price_alert') {
                html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
                if (imageUrl) {
                    html += '<div class="amadex-promo-image"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '"></div>';
                }
                html += '<div class="amadex-promo-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
                html += '<form class="amadex-promo-form amadex-price-alert-form">';
                html += '<input type="email" class="amadex-promo-email-input" placeholder="' + escapeHtml(additionalData.email_placeholder || 'Enter your email') + '" required>';
                html += '<button type="submit" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                html += '</form>';
                html += '</div>';
            } else if (type === 'airline_ad') {
                html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
                if (additionalData.airline_logo_url) {
                    html += '<div class="amadex-promo-airline-logo"><img src="' + escapeHtml(additionalData.airline_logo_url) + '" alt="' + escapeHtml(title) + '"></div>';
                }
                html += '<div class="amadex-promo-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                if (additionalData.offer_text) {
                    html += '<p class="amadex-promo-offer" style="' + bodyColorStyle + '">' + escapeHtml(additionalData.offer_text) + '</p>';
                }
                html += '</div>';
                if (linkUrl) {
                    html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                }
                html += '</div>';
            } else if (type === 'product_cross_sell') {
                html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
                if (imageUrl) {
                    html += '<div class="amadex-promo-image"><img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '"></div>';
                }
                html += '<div class="amadex-promo-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
                if (linkUrl) {
                    html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                } else {
                    html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                }
                html += '</div>';
            } else if (type === 'callback') {
                html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
                html += '<div class="amadex-promo-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
                html += '<form class="amadex-promo-form amadex-callback-form">';
                html += '<input type="tel" class="amadex-promo-phone-input" placeholder="' + escapeHtml(additionalData.phone_placeholder || 'Enter your phone number') + '" required>';
                html += '<button type="submit" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                html += '</form>';
                html += '<div class="amadex-promo-message" style="display:none;"></div>';
                html += '</div>';
            } else if (type === 'ad') {
                html += '<div class="amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                // Image section (if provided)
                if (imageUrl) {
                    if (linkUrl) {
                        html += '<a href="' + escapeHtml(linkUrl) + '" target="_blank" class="amadex-promo-ad-link">';
                        html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '" class="amadex-promo-ad-image">';
                        html += '</a>';
                    } else {
                        html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '" class="amadex-promo-ad-image">';
                    }
                }
                
                // Text content section (title and/or description)
                if (title || description) {
                    html += '<div class="amadex-promo-text">';
                    if (title) {
                        html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                    }
                    if (description) {
                        html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                    }
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            html += '</div>';
            return html;
            
        } catch (error) {
            // Enhanced error logging with full context
            console.error('Amadex Promo Renderer: Critical error in renderPromotionalContainer:', error);
            console.error('Error Details:', {
                containerId: containerId || 'unknown',
                containerType: container ? (container.type || 'unknown') : 'null',
                containerTitle: container ? (container.title || 'N/A') : 'N/A',
                errorName: error.name,
                errorMessage: error.message,
                errorStack: error.stack
            });
            
            // Return a user-friendly fallback container (for production)
            const fallbackTitle = container && container.title ? container.title : 'Promotional Content';
            return '<div class="amadex-promotional-container amadex-promo-error" style="padding: 20px; border: 2px solid #ff6b6b; background: #fff5f5; border-radius: 8px; margin: 10px 0;">' +
                   '<div class="amadex-promo-content">' +
                   '<h3 class="amadex-promo-title" style="color: #d63031; margin: 0 0 10px 0;">' + escapeHtml(fallbackTitle) + '</h3>' +
                   '<p style="color: #666; margin: 0; font-size: 14px;">Unable to load promotional content. Please refresh the page.</p>' +
                   '</div>' +
                   '</div>';
        }
    }

    // Export for use in different contexts
    if (typeof module !== 'undefined' && module.exports) {
        // Node.js/CommonJS
        module.exports = {
            renderPromotionalContainer: renderPromotionalContainer,
            compileCSSVariables: compileCSSVariables,
            buildBackgroundStyle: buildBackgroundStyle,
            buildTextColorStyles: buildTextColorStyles,
            buildAnimationClasses: buildAnimationClasses,
            escapeHtml: escapeHtml,
            hexToRgba: hexToRgba,
            getContrastColor: getContrastColor
        };
    } else if (typeof define === 'function' && define.amd) {
        // AMD
        define([], function() {
            return {
                renderPromotionalContainer: renderPromotionalContainer,
                compileCSSVariables: compileCSSVariables,
                buildBackgroundStyle: buildBackgroundStyle,
                buildTextColorStyles: buildTextColorStyles,
                buildAnimationClasses: buildAnimationClasses,
                escapeHtml: escapeHtml,
                hexToRgba: hexToRgba,
                getContrastColor: getContrastColor
            };
        });
    } else {
        // Browser global
        global.AmadexPromoRenderer = {
            renderPromotionalContainer: renderPromotionalContainer,
            compileCSSVariables: compileCSSVariables,
            buildBackgroundStyle: buildBackgroundStyle,
            buildTextColorStyles: buildTextColorStyles,
            buildAnimationClasses: buildAnimationClasses,
            escapeHtml: escapeHtml,
            hexToRgba: hexToRgba,
            getContrastColor: getContrastColor
        };
    }

})(typeof window !== 'undefined' ? window : this);