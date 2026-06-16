/**
 * Amadex Promotional Topics
 * 
 * Pre-designed banner concepts for Travelay promotional campaigns.
 * Each topic includes multiple banner angles, recommended container types,
 * psychology explanations, and example configurations.
 * 
 * @package Amadex
 * @version 1.0.0
 */

(function(global) {
    'use strict';

    /**
     * Promo Topic Definitions
     * Each topic defines:
     * - id: Unique identifier
     * - name: Display name
     * - description: Topic overview
     * - angles: Array of banner angles (3-5 per topic)
     *   Each angle includes:
     *   - id: Unique angle identifier
     *   - name: Angle name
     *   - headline: Example headline
     *   - description: Angle description
     *   - psychology: Psychology explanation
     *   - recommendedContainerType: Best container type ID
     *   - recommendedTemplate: Best template ID (optional)
     *   - exampleConfig: Example banner configuration
     */
    const PROMO_TOPICS = {
        'live_video_calling': {
            id: 'live_video_calling',
            name: 'Live Video Calling Feature',
            description: 'Promote Travelay\'s live video calling feature that connects travelers with travel agents in real-time.',
            angles: [
                {
                    id: 'personal_connection',
                    name: 'Personal Connection Angle',
                    headline: 'Talk to a Real Travel Expert - Live Video Call',
                    subheadline: 'Get instant answers and personalized recommendations',
                    description: 'Emphasizes the human connection and real-time interaction. Appeals to travelers who want personalized service.',
                    psychology: 'Taps into the need for human connection and trust. Video calls create intimacy and reduce anxiety about booking. The "real expert" messaging builds credibility.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Talk to a Real Travel Expert - Live Video Call',
                        description: 'Get instant answers and personalized recommendations from our certified travel agents. No waiting, no emails - just real-time help.',
                        button_text: 'Start Video Call',
                        link_url: '#video-call',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0e7d3f',
                        container_color_secondary: '#22af5c',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'glow'],
                        animation_intensity: 60,
                        animation_duration: 2.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'instant_support',
                    name: 'Instant Support Angle',
                    headline: 'Need Help? Video Call an Agent in 30 Seconds',
                    subheadline: 'No waiting, no emails - instant expert advice',
                    description: 'Focuses on speed and convenience. Appeals to travelers who need quick answers or are in a hurry.',
                    psychology: 'Addresses urgency and impatience. The "30 seconds" promise creates a sense of immediacy. Reduces friction in the decision-making process.',
                    recommendedContainerType: '300x250',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Need Help? Video Call an Agent in 30 Seconds',
                        description: 'No waiting, no emails - instant expert advice when you need it most.',
                        button_text: 'Call Now',
                        link_url: '#video-call',
                        image_url: '',
                        container_type_id: '300x250',
                        template_id: 'native_inline_card',
                        container_color_type: 'solid',
                        container_color_primary: '#f6851f',
                        container_color_opacity: 100,
                        animations: ['cta_pulse', 'fade_in'],
                        animation_intensity: 70,
                        animation_duration: 2.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'trust_credibility',
                    name: 'Trust & Credibility Angle',
                    headline: 'See Who You\'re Talking To - Verified Travel Agents',
                    subheadline: 'Face-to-face consultation builds confidence',
                    description: 'Emphasizes transparency and verification. Appeals to cautious travelers who need reassurance.',
                    psychology: 'Builds trust through transparency. Seeing the agent\'s face reduces uncertainty. "Verified" messaging adds authority and security.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'three_agent_cards',
                    exampleConfig: {
                        type: 'feature',
                        title: 'See Who You\'re Talking To - Verified Travel Agents',
                        description: 'Face-to-face consultation builds confidence. All our agents are certified and verified.',
                        button_text: 'Meet Our Agents',
                        link_url: '#agents',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'three_agent_cards',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0066cc',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'glow'],
                        animation_intensity: 50,
                        animation_duration: 2.5,
                        animation_max_loops: 2
                    }
                },
                {
                    id: 'premium_experience',
                    name: 'Premium Experience Angle',
                    headline: 'Premium Service: One-on-One Video Consultation',
                    subheadline: 'Exclusive access to expert travel planning',
                    description: 'Positions video calling as a premium, exclusive service. Appeals to travelers seeking luxury experiences.',
                    psychology: 'Creates exclusivity and premium positioning. "One-on-one" suggests personalized attention. Appeals to status-conscious travelers.',
                    recommendedContainerType: 'hero_takeover',
                    recommendedTemplate: 'hero_spotlight',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Premium Service: One-on-One Video Consultation',
                        description: 'Exclusive access to expert travel planning. Experience the difference of personalized service.',
                        button_text: 'Book Consultation',
                        link_url: '#consultation',
                        image_url: '',
                        container_type_id: 'hero_takeover',
                        template_id: 'hero_spotlight',
                        container_color_type: 'gradient_3',
                        container_color_primary: '#1a1a2e',
                        container_color_secondary: '#16213e',
                        container_color_tertiary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['shimmer_sweep', 'glow'],
                        animation_intensity: 40,
                        animation_duration: 3.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'problem_solver',
                    name: 'Problem Solver Angle',
                    headline: 'Stuck? Our Agents Solve It Live on Video',
                    subheadline: 'Complex itineraries, last-minute changes, special requests',
                    description: 'Positions video calling as a problem-solving tool. Appeals to travelers with complex needs or issues.',
                    psychology: 'Addresses pain points directly. "Stuck" creates relatability. Positions the service as a solution, not just a feature.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Stuck? Our Agents Solve It Live on Video',
                        description: 'Complex itineraries, last-minute changes, special requests - we handle it all in real-time.',
                        button_text: 'Get Help Now',
                        link_url: '#help',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#dc2626',
                        container_color_secondary: '#f6851f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'shake'],
                        animation_intensity: 65,
                        animation_duration: 2.0,
                        animation_max_loops: 2
                    }
                }
            ]
        },
        
        'deposit_hold_flight': {
            id: 'deposit_hold_flight',
            name: 'Deposit / Hold Flight',
            description: 'Promote the ability to hold flights with a small deposit, reducing booking anxiety and increasing conversions.',
            angles: [
                {
                    id: 'risk_reduction',
                    name: 'Risk Reduction Angle',
                    headline: 'Hold Your Flight for Just $50 - No Risk',
                    subheadline: 'Secure your price, decide later',
                    description: 'Emphasizes low financial commitment and risk-free decision making. Appeals to price-conscious and cautious travelers.',
                    psychology: 'Reduces financial anxiety and decision paralysis. Small deposit ($50) feels manageable. "No risk" messaging removes fear of commitment.',
                    recommendedContainerType: '300x250',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Hold Your Flight for Just $50 - No Risk',
                        description: 'Secure your price, decide later. Full refund if you change your mind.',
                        button_text: 'Hold Now',
                        link_url: '#hold',
                        image_url: '',
                        container_type_id: '300x250',
                        template_id: 'native_inline_card',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#10b981',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'glow'],
                        animation_intensity: 60,
                        animation_duration: 2.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'price_protection',
                    name: 'Price Protection Angle',
                    headline: 'Lock in Today\'s Price - Prices Won\'t Go Up',
                    subheadline: 'Hold for 24-48 hours while you decide',
                    description: 'Focuses on price security and protection from increases. Appeals to travelers worried about price volatility.',
                    psychology: 'Addresses fear of missing out (FOMO) on good prices. Creates urgency ("today\'s price"). Price protection reduces anxiety about timing.',
                    recommendedContainerType: '728x90',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Lock in Today\'s Price - Prices Won\'t Go Up',
                        description: 'Hold for 24-48 hours while you decide. Your price is protected.',
                        button_text: 'Lock Price',
                        link_url: '#hold',
                        image_url: '',
                        container_type_id: '728x90',
                        template_id: 'native_inline_card',
                        container_color_type: 'solid',
                        container_color_primary: '#0e7d3f',
                        container_color_opacity: 100,
                        animations: ['cta_pulse', 'fade_in'],
                        animation_intensity: 70,
                        animation_duration: 2.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'flexibility_freedom',
                    name: 'Flexibility & Freedom Angle',
                    headline: 'Book with Confidence - Change Your Mind Anytime',
                    subheadline: 'Small deposit, maximum flexibility',
                    description: 'Emphasizes flexibility and freedom to change plans. Appeals to travelers who value options.',
                    psychology: 'Reduces commitment anxiety. "Change your mind anytime" removes fear of being locked in. Appeals to freedom-loving travelers.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Book with Confidence - Change Your Mind Anytime',
                        description: 'Small deposit, maximum flexibility. Full refund if plans change.',
                        button_text: 'Reserve Now',
                        link_url: '#hold',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0066cc',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'float'],
                        animation_intensity: 50,
                        animation_duration: 2.5,
                        animation_max_loops: 2
                    }
                },
                {
                    id: 'urgency_scarcity',
                    name: 'Urgency & Scarcity Angle',
                    headline: 'Only 3 Seats Left - Hold Yours Now for $50',
                    subheadline: 'Don\'t lose your spot while deciding',
                    description: 'Creates urgency through scarcity messaging. Appeals to travelers who fear missing out.',
                    psychology: 'Scarcity principle - limited availability increases perceived value. Creates FOMO. Small deposit makes action feel low-risk.',
                    recommendedContainerType: '300x600',
                    recommendedTemplate: 'itinerary_promo',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Only 3 Seats Left - Hold Yours Now for $50',
                        description: 'Don\'t lose your spot while deciding. Secure your seat with a small deposit.',
                        button_text: 'Hold Seat',
                        link_url: '#hold',
                        image_url: '',
                        container_type_id: '300x600',
                        template_id: 'itinerary_promo',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#dc2626',
                        container_color_secondary: '#f6851f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'shake', 'blink'],
                        animation_intensity: 75,
                        animation_duration: 1.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'peace_of_mind',
                    name: 'Peace of Mind Angle',
                    headline: 'Take Your Time - We\'ll Hold It for You',
                    subheadline: 'No pressure, no rush, just $50 to secure',
                    description: 'Emphasizes low-pressure booking and peace of mind. Appeals to travelers who feel rushed or pressured.',
                    psychology: 'Reduces pressure and anxiety. "Take your time" is reassuring. Appeals to travelers who dislike high-pressure sales tactics.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Take Your Time - We\'ll Hold It for You',
                        description: 'No pressure, no rush, just $50 to secure your flight while you decide.',
                        button_text: 'Hold Flight',
                        link_url: '#hold',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'native_inline_card',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0e7d3f',
                        container_color_secondary: '#22af5c',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'float'],
                        animation_intensity: 40,
                        animation_duration: 3.0,
                        animation_max_loops: 2
                    }
                }
            ]
        },
        
        'dedicated_agent_subscription': {
            id: 'dedicated_agent_subscription',
            name: 'Dedicated Agent Subscription',
            description: 'Promote subscription service that provides travelers with a dedicated personal travel agent.',
            angles: [
                {
                    id: 'personal_concierge',
                    name: 'Personal Concierge Angle',
                    headline: 'Your Personal Travel Agent - Always Available',
                    subheadline: 'One agent, all your trips, unlimited support',
                    description: 'Positions subscription as a personal concierge service. Appeals to frequent travelers and those who value personalized service.',
                    psychology: 'Creates exclusivity and personalization. "Your personal" implies ownership and dedicated attention. Appeals to status and convenience.',
                    recommendedContainerType: 'hero_takeover',
                    recommendedTemplate: 'hero_spotlight',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Your Personal Travel Agent - Always Available',
                        description: 'One agent, all your trips, unlimited support. Your dedicated expert for every journey.',
                        button_text: 'Get Started',
                        link_url: '#subscription',
                        image_url: '',
                        container_type_id: 'hero_takeover',
                        template_id: 'hero_spotlight',
                        container_color_type: 'gradient_3',
                        container_color_primary: '#1a1a2e',
                        container_color_secondary: '#16213e',
                        container_color_tertiary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['shimmer_sweep', 'glow'],
                        animation_intensity: 45,
                        animation_duration: 3.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'value_proposition',
                    name: 'Value Proposition Angle',
                    headline: 'Unlimited Travel Support for $29/month',
                    subheadline: 'Save time, money, and stress on every trip',
                    description: 'Focuses on value and cost savings. Appeals to budget-conscious travelers who travel frequently.',
                    psychology: 'Price anchoring ($29/month) feels affordable. "Unlimited" suggests great value. Appeals to rational decision-makers who calculate ROI.',
                    recommendedContainerType: '300x250',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Unlimited Travel Support for $29/month',
                        description: 'Save time, money, and stress on every trip. One low monthly fee.',
                        button_text: 'Subscribe Now',
                        link_url: '#subscription',
                        image_url: '',
                        container_type_id: '300x250',
                        template_id: 'native_inline_card',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0e7d3f',
                        container_color_secondary: '#22af5c',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'glow'],
                        animation_intensity: 55,
                        animation_duration: 2.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'expertise_authority',
                    name: 'Expertise & Authority Angle',
                    headline: 'Access to Certified Travel Experts 24/7',
                    subheadline: 'Professional planning for every journey',
                    description: 'Emphasizes expertise and professional service. Appeals to travelers who value quality and expertise.',
                    psychology: 'Builds trust through authority. "Certified" adds credibility. "24/7" suggests reliability and availability. Appeals to quality-conscious travelers.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'three_agent_cards',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Access to Certified Travel Experts 24/7',
                        description: 'Professional planning for every journey. Expert advice whenever you need it.',
                        button_text: 'Meet Experts',
                        link_url: '#experts',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'three_agent_cards',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0066cc',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'glow'],
                        animation_intensity: 50,
                        animation_duration: 2.5,
                        animation_max_loops: 2
                    }
                },
                {
                    id: 'time_savings',
                    name: 'Time Savings Angle',
                    headline: 'Stop Searching - Let Your Agent Do the Work',
                    subheadline: 'Save hours of research, get better deals',
                    description: 'Focuses on time savings and convenience. Appeals to busy professionals and time-poor travelers.',
                    psychology: 'Addresses time poverty. "Stop searching" acknowledges the pain of research. Appeals to efficiency and convenience seekers.',
                    recommendedContainerType: '728x90',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Stop Searching - Let Your Agent Do the Work',
                        description: 'Save hours of research, get better deals. Your agent handles everything.',
                        button_text: 'Save Time',
                        link_url: '#subscription',
                        image_url: '',
                        container_type_id: '728x90',
                        template_id: 'native_inline_card',
                        container_color_type: 'solid',
                        container_color_primary: '#f6851f',
                        container_color_opacity: 100,
                        animations: ['cta_pulse', 'fade_in'],
                        animation_intensity: 65,
                        animation_duration: 2.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'relationship_building',
                    name: 'Relationship Building Angle',
                    headline: 'Build a Relationship with Your Travel Expert',
                    subheadline: 'They learn your preferences, you get better trips',
                    description: 'Emphasizes long-term relationship and personalization. Appeals to travelers who value consistency and personalization.',
                    psychology: 'Appeals to relationship-building and personalization. "Learn your preferences" suggests tailored service. Creates emotional connection.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'feature',
                        title: 'Build a Relationship with Your Travel Expert',
                        description: 'They learn your preferences, you get better trips. A partnership that grows with you.',
                        button_text: 'Start Relationship',
                        link_url: '#subscription',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0e7d3f',
                        container_color_secondary: '#22af5c',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'float'],
                        animation_intensity: 45,
                        animation_duration: 3.0,
                        animation_max_loops: 2
                    }
                }
            ]
        },
        
        'transparent_pricing': {
            id: 'transparent_pricing',
            name: 'Transparent Pricing',
            description: 'Promote Travelay\'s transparent pricing model that shows all fees upfront with no hidden costs.',
            angles: [
                {
                    id: 'no_surprises',
                    name: 'No Surprises Angle',
                    headline: 'See the Full Price - No Hidden Fees Ever',
                    subheadline: 'What you see is what you pay, guaranteed',
                    description: 'Emphasizes transparency and honesty. Appeals to travelers who have been burned by hidden fees.',
                    psychology: 'Addresses trust issues and fear of hidden costs. "No hidden fees ever" is a strong promise. Builds trust through transparency.',
                    recommendedContainerType: '300x250',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'See the Full Price - No Hidden Fees Ever',
                        description: 'What you see is what you pay, guaranteed. No surprises at checkout.',
                        button_text: 'See Pricing',
                        link_url: '#pricing',
                        image_url: '',
                        container_type_id: '300x250',
                        template_id: 'native_inline_card',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#10b981',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'glow'],
                        animation_intensity: 60,
                        animation_duration: 2.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'trust_transparency',
                    name: 'Trust & Transparency Angle',
                    headline: 'Honest Pricing - We Show Everything Upfront',
                    subheadline: 'No tricks, no hidden costs, just honest prices',
                    description: 'Positions transparency as a core value. Appeals to travelers who value honesty and integrity.',
                    psychology: 'Builds trust through honesty. "We show everything" is transparent. Appeals to travelers who have lost trust in other booking sites.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Honest Pricing - We Show Everything Upfront',
                        description: 'No tricks, no hidden costs, just honest prices you can trust.',
                        button_text: 'View Prices',
                        link_url: '#pricing',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0066cc',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'glow'],
                        animation_intensity: 50,
                        animation_duration: 2.5,
                        animation_max_loops: 2
                    }
                },
                {
                    id: 'comparison_advantage',
                    name: 'Comparison Advantage Angle',
                    headline: 'Compare True Costs - All Fees Included',
                    subheadline: 'Make informed decisions with complete pricing',
                    description: 'Positions transparent pricing as a comparison tool. Appeals to savvy travelers who compare options.',
                    psychology: 'Empowers decision-making. "True costs" suggests accuracy. Appeals to rational buyers who want complete information.',
                    recommendedContainerType: '728x90',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Compare True Costs - All Fees Included',
                        description: 'Make informed decisions with complete pricing. No surprises, no guesswork.',
                        button_text: 'Compare Now',
                        link_url: '#compare',
                        image_url: '',
                        container_type_id: '728x90',
                        template_id: 'native_inline_card',
                        container_color_type: 'solid',
                        container_color_primary: '#0e7d3f',
                        container_color_opacity: 100,
                        animations: ['cta_pulse', 'fade_in'],
                        animation_intensity: 65,
                        animation_duration: 2.0,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'peace_of_mind_pricing',
                    name: 'Peace of Mind Pricing Angle',
                    headline: 'Book with Confidence - Price Won\'t Change',
                    subheadline: 'The price you see is locked in, guaranteed',
                    description: 'Emphasizes price stability and guarantees. Appeals to travelers worried about price changes.',
                    psychology: 'Reduces anxiety about price volatility. "Won\'t change" and "guaranteed" create security. Appeals to risk-averse travelers.',
                    recommendedContainerType: '300x250',
                    recommendedTemplate: 'native_inline_card',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Book with Confidence - Price Won\'t Change',
                        description: 'The price you see is locked in, guaranteed. No last-minute surprises.',
                        button_text: 'Book Now',
                        link_url: '#book',
                        image_url: '',
                        container_type_id: '300x250',
                        template_id: 'native_inline_card',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#10b981',
                        container_color_secondary: '#0e7d3f',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['pulse', 'glow'],
                        animation_intensity: 55,
                        animation_duration: 2.5,
                        animation_max_loops: 3
                    }
                },
                {
                    id: 'industry_leader',
                    name: 'Industry Leader Angle',
                    headline: 'Setting the Standard for Transparent Pricing',
                    subheadline: 'Join thousands who trust our honest approach',
                    socialProof: 'Join thousands who trust our honest approach',
                    description: 'Positions Travelay as an industry leader in transparency. Appeals to travelers who want to support ethical businesses.',
                    psychology: 'Social proof ("thousands") builds credibility. "Setting the standard" positions as leader. Appeals to values-driven travelers.',
                    recommendedContainerType: 'native_inline_card',
                    recommendedTemplate: 'two_column_feature',
                    exampleConfig: {
                        type: 'price_alert',
                        title: 'Setting the Standard for Transparent Pricing',
                        description: 'Join thousands who trust our honest approach. See why we\'re different.',
                        button_text: 'Learn More',
                        link_url: '#transparency',
                        image_url: '',
                        container_type_id: 'native_inline_card',
                        template_id: 'two_column_feature',
                        container_color_type: 'gradient_2',
                        container_color_primary: '#0e7d3f',
                        container_color_secondary: '#22af5c',
                        container_color_opacity: 100,
                        container_gradient_direction: 'to right',
                        animations: ['fade_in', 'float'],
                        animation_intensity: 45,
                        animation_duration: 3.0,
                        animation_max_loops: 2
                    }
                }
            ]
        }
    };

    /**
     * Get a promo topic by ID
     * @param {string} topicId - Topic identifier
     * @returns {Object|null} Topic definition or null if not found
     */
    function getTopic(topicId) {
        return PROMO_TOPICS[topicId] || null;
    }

    /**
     * Get all promo topics
     * @returns {Object} All topic definitions
     */
    function getAllTopics() {
        return PROMO_TOPICS;
    }

    /**
     * Get a specific angle from a topic
     * @param {string} topicId - Topic identifier
     * @param {string} angleId - Angle identifier
     * @returns {Object|null} Angle definition or null if not found
     */
    function getAngle(topicId, angleId) {
        const topic = getTopic(topicId);
        if (!topic || !topic.angles) {
            return null;
        }
        return topic.angles.find(function(angle) {
            return angle.id === angleId;
        }) || null;
    }

    /**
     * Get example configuration for an angle
     * @param {string} topicId - Topic identifier
     * @param {string} angleId - Angle identifier
     * @returns {Object|null} Example configuration or null if not found
     */
    function getExampleConfig(topicId, angleId) {
        const angle = getAngle(topicId, angleId);
        return angle ? angle.exampleConfig : null;
    }

    /**
     * Get all angles for a topic
     * @param {string} topicId - Topic identifier
     * @returns {Array} Array of angle definitions
     */
    function getTopicAngles(topicId) {
        const topic = getTopic(topicId);
        return topic && topic.angles ? topic.angles : [];
    }

    // Export for use in different contexts
    if (typeof module !== 'undefined' && module.exports) {
        // Node.js/CommonJS
        module.exports = {
            getTopic: getTopic,
            getAllTopics: getAllTopics,
            getAngle: getAngle,
            getExampleConfig: getExampleConfig,
            getTopicAngles: getTopicAngles,
            PROMO_TOPICS: PROMO_TOPICS
        };
    } else if (typeof define === 'function' && define.amd) {
        // AMD
        define([], function() {
            return {
                getTopic: getTopic,
                getAllTopics: getAllTopics,
                getAngle: getAngle,
                getExampleConfig: getExampleConfig,
                getTopicAngles: getTopicAngles,
                PROMO_TOPICS: PROMO_TOPICS
            };
        });
    } else {
        // Browser global
        global.AmadexPromoTopics = {
            getTopic: getTopic,
            getAllTopics: getAllTopics,
            getAngle: getAngle,
            getExampleConfig: getExampleConfig,
            getTopicAngles: getTopicAngles,
            PROMO_TOPICS: PROMO_TOPICS
        };
    }

})(typeof window !== 'undefined' ? window : this);
