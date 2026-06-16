/**
 * Amadex Promotional Container Templates
 * 
 * Template engine for promotional containers with predefined layouts,
 * content schemas, and responsive rules.
 * 
 * @package Amadex
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    /**
     * Template Definitions
     * Each template defines:
     * - id: Unique template identifier
     * - name: Display name
     * - description: Template description
     * - contentSchema: Required/optional fields
     * - gridConfig: Grid layout configuration
     * - desktopLayout: Desktop HTML structure
     * - mobileLayout: Mobile HTML structure (optional, falls back to desktop)
     * - tabletLayout: Tablet HTML structure (optional, falls back to desktop)
     * - allowedAnimations: Array of allowed animation types
     */
    const TEMPLATE_DEFINITIONS = {
        'native_inline_card': {
            id: 'native_inline_card',
            name: 'Native Inline Card',
            description: 'A simple, native-looking card that blends seamlessly with content. Perfect for inline promotions.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'image_url', 'button_text', 'link_url']
            },
            gridConfig: {
                desktop: { columns: 'auto 1fr auto', gap: '20px', alignItems: 'center' },
                tablet: { columns: 'auto 1fr auto', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const imageUrl = container.image_url || '';
                const buttonText = container.button_text || 'Learn More';
                const linkUrl = container.link_url || '';
                
                let html = '<div class="amadex-template-native-inline-card amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (imageUrl) {
                    html += '<div class="amadex-template-image">';
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '">';
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
                
                if (linkUrl || buttonText) {
                    html += '<div class="amadex-template-action">';
                    if (linkUrl) {
                        html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                    } else {
                        html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                    }
                    html += '</div>';
                }
                
                html += '</div>';
                return html;
            }
        },
        
        'itinerary_promo': {
            id: 'itinerary_promo',
            name: 'Itinerary Promo',
            description: 'Designed for travel itineraries with date ranges, destinations, and booking actions.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'departure_date', 'return_date', 'destination', 'price', 'button_text', 'link_url', 'image_url']
            },
            gridConfig: {
                desktop: { columns: '1fr 2fr 1fr', gap: '20px', alignItems: 'center' },
                tablet: { columns: '1fr 1fr', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'pulse', 'glow', 'float'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const departureDate = container.additional_data?.departure_date || '';
                const returnDate = container.additional_data?.return_date || '';
                const destination = container.additional_data?.destination || '';
                const price = container.additional_data?.price || '';
                const buttonText = container.button_text || 'Book Now';
                const linkUrl = container.link_url || '';
                const imageUrl = container.image_url || '';
                
                let html = '<div class="amadex-template-itinerary-promo amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (imageUrl) {
                    html += '<div class="amadex-template-image">';
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '">';
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                if (destination) {
                    html += '<div class="amadex-template-destination" style="' + bodyColorStyle + '">';
                    html += '<strong>Destination:</strong> ' + escapeHtml(destination);
                    html += '</div>';
                }
                if (departureDate || returnDate) {
                    html += '<div class="amadex-template-dates" style="' + bodyColorStyle + '">';
                    if (departureDate) html += '<span>Depart: ' + escapeHtml(departureDate) + '</span>';
                    if (returnDate) html += '<span>Return: ' + escapeHtml(returnDate) + '</span>';
                    html += '</div>';
                }
                html += '</div>';
                
                html += '<div class="amadex-template-action">';
                if (price) {
                    html += '<div class="amadex-template-price" style="' + headingColorStyle + '">' + escapeHtml(price) + '</div>';
                }
                if (linkUrl) {
                    html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                } else {
                    html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                }
                html += '</div>';
                
                html += '</div>';
                return html;
            }
        },
        
        'three_agent_cards': {
            id: 'three_agent_cards',
            name: '3 Agent Cards',
            description: 'Three-column grid showcasing agent profiles, services, or features side-by-side.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'cards'] // cards is array of {title, description, image_url, link_url}
            },
            gridConfig: {
                desktop: { columns: 'repeat(3, 1fr)', gap: '20px', alignItems: 'stretch' },
                tablet: { columns: 'repeat(2, 1fr)', gap: '15px', alignItems: 'stretch' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow', 'zoom_in'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const cards = container.additional_data?.cards || [];
                
                // If no cards provided, create default structure
                if (cards.length === 0) {
                    cards.push(
                        { title: 'Agent 1', description: 'Description for agent 1', image_url: '', link_url: '' },
                        { title: 'Agent 2', description: 'Description for agent 2', image_url: '', link_url: '' },
                        { title: 'Agent 3', description: 'Description for agent 3', image_url: '', link_url: '' }
                    );
                }
                
                let html = '<div class="amadex-template-three-agent-cards amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (title || description) {
                    html += '<div class="amadex-template-header">';
                    if (title) {
                        html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                    }
                    if (description) {
                        html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                    }
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-cards-grid">';
                cards.forEach(function(card, index) {
                    html += '<div class="amadex-template-card">';
                    if (card.image_url) {
                        html += '<div class="amadex-template-card-image">';
                        html += '<img src="' + escapeHtml(card.image_url) + '" alt="' + escapeHtml(card.title || 'Card ' + (index + 1)) + '">';
                        html += '</div>';
                    }
                    html += '<div class="amadex-template-card-content">';
                    if (card.title) {
                        html += '<h4 class="amadex-template-card-title" style="' + headingColorStyle + '">' + escapeHtml(card.title) + '</h4>';
                    }
                    if (card.description) {
                        html += '<p class="amadex-template-card-description" style="' + bodyColorStyle + '">' + escapeHtml(card.description) + '</p>';
                    }
                    if (card.link_url) {
                        html += '<a href="' + escapeHtml(card.link_url) + '" class="amadex-promo-button amadex-promo-link" target="_blank">Learn More</a>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                
                html += '</div>';
                return html;
            }
        },
        
        'two_column_feature': {
            id: 'two_column_feature',
            name: '2 Column Feature',
            description: 'Two-column layout perfect for feature highlights, comparisons, or side-by-side content.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'left_content', 'right_content', 'left_image', 'right_image']
            },
            gridConfig: {
                desktop: { columns: '1fr 1fr', gap: '30px', alignItems: 'center' },
                tablet: { columns: '1fr 1fr', gap: '20px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '20px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow', 'zoom_in'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const leftContent = container.additional_data?.left_content || '';
                const rightContent = container.additional_data?.right_content || '';
                const leftImage = container.additional_data?.left_image || '';
                const rightImage = container.additional_data?.right_image || '';
                
                let html = '<div class="amadex-template-two-column-feature amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (title || description) {
                    html += '<div class="amadex-template-header">';
                    if (title) {
                        html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                    }
                    if (description) {
                        html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                    }
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-columns">';
                
                // Left Column
                html += '<div class="amadex-template-column amadex-template-column-left">';
                if (leftImage) {
                    html += '<div class="amadex-template-column-image">';
                    html += '<img src="' + escapeHtml(leftImage) + '" alt="' + escapeHtml(title) + ' - Left">';
                    html += '</div>';
                }
                if (leftContent) {
                    html += '<div class="amadex-template-column-content" style="' + bodyColorStyle + '">' + escapeHtml(leftContent) + '</div>';
                }
                html += '</div>';
                
                // Right Column
                html += '<div class="amadex-template-column amadex-template-column-right">';
                if (rightImage) {
                    html += '<div class="amadex-template-column-image">';
                    html += '<img src="' + escapeHtml(rightImage) + '" alt="' + escapeHtml(title) + ' - Right">';
                    html += '</div>';
                }
                if (rightContent) {
                    html += '<div class="amadex-template-column-content" style="' + bodyColorStyle + '">' + escapeHtml(rightContent) + '</div>';
                }
                html += '</div>';
                
                html += '</div>';
                html += '</div>';
                return html;
            }
        },
        
        'hero_spotlight': {
            id: 'hero_spotlight',
            name: 'Hero Spotlight',
            description: 'Large, attention-grabbing hero banner with prominent CTA. Perfect for major promotions.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'image_url', 'button_text', 'link_url', 'subtitle']
            },
            gridConfig: {
                desktop: { columns: '1fr', gap: '30px', alignItems: 'center', minHeight: '400px' },
                tablet: { columns: '1fr', gap: '25px', alignItems: 'center', minHeight: '350px' },
                mobile: { columns: '1fr', gap: '20px', alignItems: 'center', minHeight: '300px' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'zoom_in', 'pulse', 'glow', 'neon_glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const subtitle = container.additional_data?.subtitle || '';
                const description = container.description || '';
                const imageUrl = container.image_url || '';
                const buttonText = container.button_text || 'Get Started';
                const linkUrl = container.link_url || '';
                
                let html = '<div class="amadex-template-hero-spotlight amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (imageUrl) {
                    html += '<div class="amadex-template-hero-image">';
                    html += '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(title) + '">';
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-hero-content">';
                if (subtitle) {
                    html += '<div class="amadex-template-hero-subtitle" style="' + bodyColorStyle + '">' + escapeHtml(subtitle) + '</div>';
                }
                html += '<h2 class="amadex-template-hero-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h2>';
                if (description) {
                    html += '<p class="amadex-promo-description amadex-template-hero-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                if (linkUrl || buttonText) {
                    html += '<div class="amadex-template-hero-action">';
                    if (linkUrl) {
                        html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link amadex-template-hero-button" target="_blank">' + escapeHtml(buttonText) + '</a>';
                    } else {
                        html += '<button type="button" class="amadex-promo-button amadex-template-hero-button">' + escapeHtml(buttonText) + '</button>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                
                html += '</div>';
                return html;
            }
        },
        
        'promo_carousel': {
            id: 'promo_carousel',
            name: 'Promo Carousel',
            description: 'Carousel/slider showcasing multiple promotions or features. Auto-rotates through items.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'slides'] // slides is array of {title, description, image_url, link_url, button_text}
            },
            gridConfig: {
                desktop: { columns: '1fr', gap: '0', alignItems: 'stretch' },
                tablet: { columns: '1fr', gap: '0', alignItems: 'stretch' },
                mobile: { columns: '1fr', gap: '0', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const slides = container.additional_data?.slides || [];
                
                // If no slides provided, create default structure
                if (slides.length === 0) {
                    slides.push(
                        { title: 'Slide 1', description: 'First slide content', image_url: '', link_url: '', button_text: 'Learn More' },
                        { title: 'Slide 2', description: 'Second slide content', image_url: '', link_url: '', button_text: 'Learn More' },
                        { title: 'Slide 3', description: 'Third slide content', image_url: '', link_url: '', button_text: 'Learn More' }
                    );
                }
                
                let html = '<div class="amadex-template-promo-carousel amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (title || description) {
                    html += '<div class="amadex-template-header">';
                    if (title) {
                        html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                    }
                    if (description) {
                        html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                    }
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-carousel-wrapper">';
                html += '<div class="amadex-template-carousel-slides">';
                slides.forEach(function(slide, index) {
                    html += '<div class="amadex-template-carousel-slide" data-slide-index="' + index + '">';
                    if (slide.image_url) {
                        html += '<div class="amadex-template-carousel-image">';
                        html += '<img src="' + escapeHtml(slide.image_url) + '" alt="' + escapeHtml(slide.title || 'Slide ' + (index + 1)) + '">';
                        html += '</div>';
                    }
                    html += '<div class="amadex-template-carousel-content">';
                    if (slide.title) {
                        html += '<h4 class="amadex-template-carousel-title" style="' + headingColorStyle + '">' + escapeHtml(slide.title) + '</h4>';
                    }
                    if (slide.description) {
                        html += '<p class="amadex-template-carousel-description" style="' + bodyColorStyle + '">' + escapeHtml(slide.description) + '</p>';
                    }
                    if (slide.link_url || slide.button_text) {
                        const btnText = slide.button_text || 'Learn More';
                        if (slide.link_url) {
                            html += '<a href="' + escapeHtml(slide.link_url) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(btnText) + '</a>';
                        } else {
                            html += '<button type="button" class="amadex-promo-button">' + escapeHtml(btnText) + '</button>';
                        }
                    }
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                html += '<div class="amadex-template-carousel-controls">';
                html += '<button class="amadex-template-carousel-prev" aria-label="Previous slide">‹</button>';
                html += '<div class="amadex-template-carousel-dots"></div>';
                html += '<button class="amadex-template-carousel-next" aria-label="Next slide">›</button>';
                html += '</div>';
                html += '</div>';
                
                html += '</div>';
                return html;
            }
        },
        
        'video_promo_tile': {
            id: 'video_promo_tile',
            name: 'Video Promo Tile',
            description: 'Video-focused promotion with thumbnail, play button, and video metadata.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'video_url', 'video_thumbnail', 'video_duration', 'button_text', 'link_url']
            },
            gridConfig: {
                desktop: { columns: 'auto 1fr auto', gap: '20px', alignItems: 'center' },
                tablet: { columns: 'auto 1fr auto', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow', 'zoom_in'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const videoUrl = container.additional_data?.video_url || '';
                const videoThumbnail = container.additional_data?.video_thumbnail || container.image_url || '';
                const videoDuration = container.additional_data?.video_duration || '';
                const buttonText = container.button_text || 'Watch Video';
                const linkUrl = container.link_url || videoUrl || '';
                
                let html = '<div class="amadex-template-video-promo-tile amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (videoThumbnail || videoUrl) {
                    html += '<div class="amadex-template-video-thumbnail">';
                    if (videoThumbnail) {
                        html += '<img src="' + escapeHtml(videoThumbnail) + '" alt="' + escapeHtml(title) + '">';
                    }
                    html += '<div class="amadex-template-video-play-button">';
                    html += '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">';
                    html += '<circle cx="32" cy="32" r="32" fill="rgba(255, 255, 255, 0.9)"/>';
                    html += '<path d="M24 20L44 32L24 44V20Z" fill="#0e7d3f"/>';
                    html += '</svg>';
                    html += '</div>';
                    if (videoDuration) {
                        html += '<div class="amadex-template-video-duration">' + escapeHtml(videoDuration) + '</div>';
                    }
                    if (linkUrl) {
                        html = '<a href="' + escapeHtml(linkUrl) + '" class="amadex-template-video-link" target="_blank">' + html + '</a>';
                    }
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-text">';
                html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                html += '</div>';
                
                if (linkUrl && !videoThumbnail) {
                    html += '<div class="amadex-template-action">';
                    html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                    html += '</div>';
                }
                
                html += '</div>';
                return html;
            }
        },
        
        // Phase 4: New Travelay-Specific Templates
        'travelaygent_profile_card': {
            id: 'travelaygent_profile_card',
            name: 'TravelayGent Profile Card',
            description: 'Single agent spotlight card with photo, name, specialties, rating, and CTA. Perfect for showcasing individual TravelayGents.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: 'auto 1fr auto', gap: '20px', alignItems: 'center' },
                tablet: { columns: 'auto 1fr', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'glow', 'zoom_in'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const templateData = container.template_data || {};
                const agentName = templateData.agent_name || '';
                const agentPhoto = templateData.agent_photo || '';
                const agentSpecialties = templateData.agent_specialties || '';
                const agentRating = templateData.agent_rating || '5.0';
                const agentLink = templateData.agent_profile_link || container.link_url || '';
                const buttonText = container.button_text || 'Call & Ask for ' + (agentName || 'Agent');
                
                let html = '<div class="amadex-template-travelaygent-profile amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (agentPhoto) {
                    html += '<div class="amadex-template-agent-photo">';
                    html += '<img src="' + escapeHtml(agentPhoto) + '" alt="' + escapeHtml(agentName || title) + '">';
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-agent-content">';
                if (title) {
                    html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                }
                if (agentName) {
                    html += '<h4 class="amadex-template-agent-name" style="' + headingColorStyle + '">' + escapeHtml(agentName) + '</h4>';
                }
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                if (agentSpecialties) {
                    html += '<div class="amadex-template-agent-specialties" style="' + bodyColorStyle + '">' + escapeHtml(agentSpecialties) + '</div>';
                }
                if (agentRating) {
                    html += '<div class="amadex-template-agent-rating">';
                    html += '<span style="' + headingColorStyle + '">' + escapeHtml(agentRating) + ' ⭐</span>';
                    html += '</div>';
                }
                html += '</div>';
                
                if (agentLink || buttonText) {
                    html += '<div class="amadex-template-agent-action">';
                    if (agentLink) {
                        html += '<a href="' + escapeHtml(agentLink) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                    } else {
                        html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                    }
                    html += '</div>';
                }
                
                html += '</div>';
                return html;
            }
        },
        
        'travel_pass_verification': {
            id: 'travel_pass_verification',
            name: 'Travel Pass Verification Badge',
            description: 'Travelay Pass verification badge with status indicator and special offers. Displays verification status prominently.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: 'auto 1fr auto', gap: '15px', alignItems: 'center' },
                tablet: { columns: 'auto 1fr auto', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '10px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'pulse', 'glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || 'TRAVELAY PASS';
                const templateData = container.template_data || {};
                const verificationStatus = templateData.verification_status || 'verified';
                const offerText = templateData.offer_text || '(Inventory Suggest Better Price on Call)';
                
                let html = '<div class="amadex-template-travel-pass amadex-promo-content" style="' + backgroundColorStyle + '">';
                html += '<div class="amadex-template-pass-header">';
                html += '<span class="amadex-template-pass-icon">✈️</span>';
                html += '<span class="amadex-template-pass-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</span>';
                html += '</div>';
                html += '<div class="amadex-template-pass-offer" style="' + bodyColorStyle + '">' + escapeHtml(offerText) + '</div>';
                html += '<div class="amadex-template-pass-status" style="' + headingColorStyle + '">';
                html += verificationStatus === 'verified' ? 'VERIFIED FLIGHT' : 'PENDING VERIFICATION';
                html += '</div>';
                html += '</div>';
                return html;
            }
        },
        
        'agent_comparison_grid': {
            id: 'agent_comparison_grid',
            name: 'Agent Comparison Grid',
            description: 'Side-by-side comparison of 2-4 TravelayGents with photos, ratings, and specialties. Helps users choose the right agent.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: 'repeat(4, 1fr)', gap: '20px', alignItems: 'stretch' },
                tablet: { columns: 'repeat(2, 1fr)', gap: '15px', alignItems: 'stretch' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse', 'zoom_in'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const templateData = container.template_data || {};
                const agents = templateData.comparison_agents || [];
                
                let html = '<div class="amadex-template-agent-comparison amadex-promo-content" style="' + backgroundColorStyle + '">';
                
                if (title || description) {
                    html += '<div class="amadex-template-header">';
                    if (title) html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                    if (description) html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                    html += '</div>';
                }
                
                html += '<div class="amadex-template-comparison-grid">';
                agents.forEach(function(agent, index) {
                    html += '<div class="amadex-template-comparison-card">';
                    if (agent.photo) {
                        html += '<div class="amadex-template-comparison-photo">';
                        html += '<img src="' + escapeHtml(agent.photo) + '" alt="' + escapeHtml(agent.name || 'Agent ' + (index + 1)) + '">';
                        html += '</div>';
                    }
                    html += '<div class="amadex-template-comparison-content">';
                    if (agent.name) {
                        html += '<h4 style="' + headingColorStyle + '">' + escapeHtml(agent.name) + '</h4>';
                    }
                    if (agent.rating) {
                        html += '<div class="amadex-template-comparison-rating">' + escapeHtml(agent.rating) + ' ⭐</div>';
                    }
                    if (agent.specialties) {
                        html += '<p style="' + bodyColorStyle + '">' + escapeHtml(agent.specialties) + '</p>';
                    }
                    if (agent.link) {
                        html += '<a href="' + escapeHtml(agent.link) + '" class="amadex-promo-button amadex-promo-link" target="_blank">Choose Agent</a>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                html += '</div>';
                return html;
            }
        },
        
        'deal_countdown_timer': {
            id: 'deal_countdown_timer',
            name: 'Deal Countdown Timer',
            description: 'Deal highlight with countdown timer, discount percentage, and urgency messaging. Creates time-sensitive urgency.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: '1fr', gap: '20px', alignItems: 'center' },
                tablet: { columns: '1fr', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'center' }
            },
            allowedAnimations: ['fade_in', 'pulse', 'glow', 'neon_glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const templateData = container.template_data || {};
                const dealHeadline = templateData.deal_headline || '';
                const discountPercent = templateData.discount_percent || '';
                const countdownEnd = templateData.countdown_end || '';
                const buttonText = container.button_text || 'Book Now';
                const linkUrl = container.link_url || '';
                
                let html = '<div class="amadex-template-deal-countdown amadex-promo-content" style="' + backgroundColorStyle + '">';
                if (dealHeadline) {
                    html += '<div class="amadex-template-deal-headline" style="' + headingColorStyle + '">' + escapeHtml(dealHeadline) + '</div>';
                }
                if (title) {
                    html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                }
                if (discountPercent) {
                    html += '<div class="amadex-template-discount" style="' + headingColorStyle + '">' + escapeHtml(discountPercent) + ' OFF</div>';
                }
                if (countdownEnd) {
                    html += '<div class="amadex-template-countdown" data-end="' + escapeHtml(countdownEnd) + '">';
                    html += '<span class="countdown-timer">00:00:00</span>';
                    html += '</div>';
                }
                if (description) {
                    html += '<p class="amadex-promo-description" style="' + bodyColorStyle + '">' + escapeHtml(description) + '</p>';
                }
                if (linkUrl || buttonText) {
                    html += '<div class="amadex-template-deal-action">';
                    if (linkUrl) {
                        html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(buttonText) + '</a>';
                    } else {
                        html += '<button type="button" class="amadex-promo-button">' + escapeHtml(buttonText) + '</button>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                return html;
            }
        },
        
        'trust_metrics_banner': {
            id: 'trust_metrics_banner',
            name: 'Trust Metrics Banner',
            description: 'Trust-building banner with customer count, ratings, and certifications. Builds credibility and social proof.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: 'repeat(3, 1fr)', gap: '20px', alignItems: 'center' },
                tablet: { columns: 'repeat(3, 1fr)', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'pulse', 'glow'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const templateData = container.template_data || {};
                const customerCount = templateData.customer_count || 0;
                const trustRating = templateData.trust_rating || '4.5';
                const certification = templateData.certification || '';
                
                let html = '<div class="amadex-template-trust-metrics amadex-promo-content" style="' + backgroundColorStyle + '">';
                if (title) {
                    html += '<h3 class="amadex-promo-title" style="' + headingColorStyle + '">' + escapeHtml(title) + '</h3>';
                }
                html += '<div class="amadex-template-trust-grid">';
                if (customerCount > 0) {
                    html += '<div class="amadex-template-trust-metric">';
                    html += '<div class="amadex-template-trust-value" style="' + headingColorStyle + '">' + escapeHtml(customerCount.toLocaleString()) + '+</div>';
                    html += '<div class="amadex-template-trust-label" style="' + bodyColorStyle + '">Happy Customers</div>';
                    html += '</div>';
                }
                if (trustRating) {
                    html += '<div class="amadex-template-trust-metric">';
                    html += '<div class="amadex-template-trust-value" style="' + headingColorStyle + '">' + escapeHtml(trustRating) + ' ⭐</div>';
                    html += '<div class="amadex-template-trust-label" style="' + bodyColorStyle + '">Average Rating</div>';
                    html += '</div>';
                }
                if (certification) {
                    html += '<div class="amadex-template-trust-metric">';
                    html += '<div class="amadex-template-trust-value" style="' + headingColorStyle + '">✓</div>';
                    html += '<div class="amadex-template-trust-label" style="' + bodyColorStyle + '">' + escapeHtml(certification) + '</div>';
                    html += '</div>';
                }
                html += '</div>';
                html += '</div>';
                return html;
            }
        },
        
        'flight_status_alert': {
            id: 'flight_status_alert',
            name: 'Flight Status Alert',
            description: 'Flight status indicator with flight number, route, status, and action buttons. Perfect for flight tracking and alerts.',
            contentSchema: {
                required: ['title'],
                optional: ['description', 'template_data']
            },
            gridConfig: {
                desktop: { columns: 'auto 1fr auto', gap: '20px', alignItems: 'center' },
                tablet: { columns: 'auto 1fr auto', gap: '15px', alignItems: 'center' },
                mobile: { columns: '1fr', gap: '15px', alignItems: 'stretch' }
            },
            allowedAnimations: ['fade_in', 'slide_in_left', 'slide_in_right', 'pulse'],
            render: function(container, helpers) {
                const { escapeHtml, headingColorStyle, bodyColorStyle, backgroundColorStyle } = helpers;
                const title = container.title || '';
                const description = container.description || '';
                const templateData = container.template_data || {};
                const flightNumber = templateData.flight_number || '';
                const flightStatus = templateData.flight_status || 'on_time';
                const flightRoute = templateData.flight_route || '';
                const actionButton = templateData.action_button || 'View Details';
                const linkUrl = container.link_url || '';
                
                let statusClass = 'status-' + flightStatus;
                let statusText = flightStatus === 'on_time' ? 'On Time' : (flightStatus === 'delayed' ? 'Delayed' : 'Cancelled');
                
                let html = '<div class="amadex-template-flight-status amadex-promo-content ' + statusClass + '" style="' + backgroundColorStyle + '">';
                html += '<div class="amadex-template-flight-info">';
                if (flightNumber) {
                    html += '<div class="amadex-template-flight-number" style="' + headingColorStyle + '">' + escapeHtml(flightNumber) + '</div>';
                }
                if (flightRoute) {
                    html += '<div class="amadex-template-flight-route" style="' + bodyColorStyle + '">' + escapeHtml(flightRoute) + '</div>';
                }
                html += '</div>';
                html += '<div class="amadex-template-flight-status-indicator">';
                html += '<span class="amadex-template-status-badge ' + statusClass + '" style="' + headingColorStyle + '">' + escapeHtml(statusText) + '</span>';
                html += '</div>';
                if (linkUrl || actionButton) {
                    html += '<div class="amadex-template-flight-action">';
                    if (linkUrl) {
                        html += '<a href="' + escapeHtml(linkUrl) + '" class="amadex-promo-button amadex-promo-link" target="_blank">' + escapeHtml(actionButton) + '</a>';
                    } else {
                        html += '<button type="button" class="amadex-promo-button">' + escapeHtml(actionButton) + '</button>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                return html;
            }
        }
    };

    /**
     * Get template definition by ID
     * @param {string} templateId - Template identifier
     * @returns {Object|null} Template definition or null if not found
     */
    function getTemplate(templateId) {
        return TEMPLATE_DEFINITIONS[templateId] || null;
    }

    /**
     * Get all available templates
     * @returns {Object} All template definitions
     */
    function getAllTemplates() {
        return TEMPLATE_DEFINITIONS;
    }

    /**
     * Get template list for admin UI
     * @returns {Array} Array of {id, name, description} objects
     */
    function getTemplateList() {
        return Object.keys(TEMPLATE_DEFINITIONS).map(function(templateId) {
            const template = TEMPLATE_DEFINITIONS[templateId];
            return {
                id: template.id,
                name: template.name,
                description: template.description
            };
        });
    }

    /**
     * Validate container data against template schema
     * @param {Object} container - Container configuration
     * @param {string} templateId - Template identifier
     * @returns {Object} {valid: boolean, errors: Array}
     */
    function validateTemplateSchema(container, templateId) {
        const template = getTemplate(templateId);
        if (!template) {
            return { valid: false, errors: ['Template not found: ' + templateId] };
        }

        const schema = template.contentSchema;
        const errors = [];
        
        // Check required fields
        if (schema.required) {
            schema.required.forEach(function(field) {
                if (!container[field] && !container.additional_data?.[field]) {
                    errors.push('Required field missing: ' + field);
                }
            });
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    // Export for use in different contexts
    if (typeof module !== 'undefined' && module.exports) {
        // Node.js/CommonJS
        module.exports = {
            getTemplate: getTemplate,
            getAllTemplates: getAllTemplates,
            getTemplateList: getTemplateList,
            validateTemplateSchema: validateTemplateSchema,
            TEMPLATE_DEFINITIONS: TEMPLATE_DEFINITIONS
        };
    } else if (typeof define === 'function' && define.amd) {
        // AMD
        define([], function() {
            return {
                getTemplate: getTemplate,
                getAllTemplates: getAllTemplates,
                getTemplateList: getTemplateList,
                validateTemplateSchema: validateTemplateSchema,
                TEMPLATE_DEFINITIONS: TEMPLATE_DEFINITIONS
            };
        });
    } else {
        // Browser global
        global.AmadexPromoTemplates = {
            getTemplate: getTemplate,
            getAllTemplates: getAllTemplates,
            getTemplateList: getTemplateList,
            validateTemplateSchema: validateTemplateSchema,
            TEMPLATE_DEFINITIONS: TEMPLATE_DEFINITIONS
        };
    }

})(typeof window !== 'undefined' ? window : this);
