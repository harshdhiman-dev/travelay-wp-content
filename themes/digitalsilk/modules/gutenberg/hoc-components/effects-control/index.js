/**
 * WordPress dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, RangeControl, TextControl, ExternalLink } from '@wordpress/components';
import { getBlockType } from '@wordpress/blocks';

/**
 * Register dsEffects attribute for blocks that support dsEffects
 *
 * @param {Object} settings - The block settings.
 */
const registerEffectsAttributes = ( settings ) => {
    if ( settings.supports?.dsEffects ) {
        settings.attributes = {
            dsEffects: {
                type: 'object',
                default: {
                    type: '',
                    repeat: false,
                    threashold: 0.0,
                    margin: '',
                    custom: '',
                },
            },
            ...settings.attributes,
        };
    }
    return settings;
};

addFilter(
    'blocks.registerBlockType',
    'ds-block-filters/register-effects-attributes',
    registerEffectsAttributes
);

/**
 * Higher-order component to inject Effects controls into the editor
 */
const withEffectsControls = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {
        const { name, attributes, setAttributes, wrapperProps } = props;
        const blockType = getBlockType( name );

        if ( ! blockType?.supports?.dsEffects ) {
            return <BlockEdit { ...props } />;
        }

        const { dsEffects } = attributes;
        const { type, repeat, threashold, margin, custom } = dsEffects;

        // DEBOUNCE dispatch of a custom event whenever dsEffects changes
        const debounceRef = useRef();
        useEffect(
            () => {
                // Clear previous timer
                clearTimeout( debounceRef.current );
                // Set new one
                debounceRef.current = setTimeout(
                    () => {
                        const e = new CustomEvent( 'animationChanged', { detail: dsEffects } );
                        window.dispatchEvent( e );
                    },
                    300 // 300ms debounce
                );

                // Cleanup on unmount
                return () => clearTimeout( debounceRef.current );
            },
            [ dsEffects ]
        );

		// Build data-viewport* attributes only when an effect is chosen.
		const viewportAttrs = type ? {
					'data-viewport': 'true',
					'data-viewport-repeat': repeat ? 'true' : 'false',
					'data-viewport-effect': custom ? custom : type,
					'data-viewport-threshold': String( threashold ),
					'data-viewport-margin': margin,
			  } : {};

        const updatedWrapperProps = {
            ...wrapperProps,
            ...viewportAttrs,
        };

        const effectOptions = [
            { label: __( 'Choose Effect' ), value: '' },
            { label: __( 'Fade' ), value: 'fade' },
            { label: __( 'Fade Up' ), value: 'fade-up' },
            { label: __( 'Fade Down' ), value: 'fade-down' },
            { label: __( 'Fade Right' ), value: 'fade-right' },
            { label: __( 'Fade Left' ), value: 'fade-left' },
            { label: __( 'Zoom In' ), value: 'zoom-in' },
            { label: __( 'Slide Up' ), value: 'slide-up' },
            { label: __( 'Slide Down' ), value: 'slide-down' },
            { label: __( 'Slide Right' ), value: 'slide-right' },
            { label: __( 'Slide Left' ), value: 'slide-left' },
            { label: __( 'Fade In Sequence' ), value: 'fade-in-seq' },
            { label: __( 'Fade In Sliding' ), value: 'fade-in-slides' },
            { label: __( 'Animate Headings' ), value: 'animate-headings' },
            { label: __( 'Custom Class Name' ), value: 'custom' },
        ];

        // Effect change handler
        const handleEffectChange = ( newType ) => {
            if ( newType === '' ) {
                // Reset everything when user clears the effect
                setAttributes({
                    dsEffects: {
                        type:       '',
                        repeat:     false,
                        threashold: 0.0,
                        margin:     '',
                        custom:     '',
                    },
                });
                return;
            }

            // Otherwise update type and drop custom unless they chose 'custom'
            setAttributes({
                dsEffects: {
                    ...dsEffects,
                    type:   newType,
                    custom: newType === 'custom' ? dsEffects.custom : '',
                },
            });
        };

        return (
            <>
                <InspectorControls group="styles">
                    <PanelBody
	                    title={ __( 'Effects' ) }
	                    initialOpen={ false }
	                    className="dst-effects"
                    >
                        <SelectControl
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
                            help={__('Choose predefined effect or you can create custom CSS effect by adding custom class.')}
                            label={ __( 'Chose Effect' ) }
                            value={ type }
                            options={ effectOptions }
                            onChange={ handleEffectChange }
                        />
                        {
                            type === 'custom' && (
                                <TextControl
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    label={ __( 'Custom Class' ) }
                                    value={ custom }
                                    placeholder="my-custom-class-name"
                                    onChange={ ( newCustomClass ) => {
                                        setAttributes( {
                                            dsEffects: {
                                                ...dsEffects,
                                                custom: newCustomClass,
                                            },
                                        } );
                                    } }
                                />
                            )
                        }
                        {
                            type && (
                                <>
                                    <ToggleControl
                                        __nextHasNoMarginBottom
                                        label={ __( 'Repeatable' ) }
                                        help={__('Check if animation is repeatable.')}
                                        checked={ repeat }
                                        onChange={ ( newRepeat ) => {
                                            setAttributes( {
                                                dsEffects: {
                                                    ...dsEffects,
                                                    repeat: newRepeat,
                                                },
                                            } );
                                        } }
                                    />
                                    <RangeControl
                                        __next40pxDefaultSize
                                        __nextHasNoMarginBottom
                                        label={ __( 'Threshold' ) }
                                        value={ threashold }
                                        onChange={ ( newValue ) => {
                                            setAttributes( {
                                                dsEffects: {
                                                    ...dsEffects,
                                                    threashold: newValue,
                                                },
                                            } );
                                        } }
                                        min={ 0 }
                                        max={ 1 }
                                        step={ 0.1 }
                                    />
                                    <p
                                        style={
                                            {
                                                fontSize: '12px',
                                                marginTop: '-20px',
                                            }
                                        }
                                    >
                                        <ExternalLink href='https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/IntersectionObserver'>
                                            {__('Read More')}
                                        </ExternalLink>
                                    </p>
                                    <TextControl
                                        __next40pxDefaultSize
                                        __nextHasNoMarginBottom
                                        label={ __( 'Root Margin' ) }
                                        value={ margin }
                                        placeholder="0px 0px 0px 0px"
                                        onChange={ ( newMargin ) => {
                                            setAttributes( {
                                                dsEffects: {
                                                    ...dsEffects,
                                                    margin: newMargin,
                                                },
                                            } );
                                        } }
                                    />
                                    <p
                                        style={
                                            {
                                                fontSize: '12px',
                                                marginTop: '-10px',
                                            }
                                        }
                                    >
                                        <ExternalLink href="https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/rootMargin">
                                            {__('Read More')}
                                        </ExternalLink>
                                    </p>
                                </>
                            )
                        }
                    </PanelBody>
                </InspectorControls>
                <BlockEdit { ...props } wrapperProps={ updatedWrapperProps } />
            </>
        );
    };
}, 'withEffectsControls' );

addFilter(
    'editor.BlockEdit',
    'ds-block-filters/effects-controls',
    withEffectsControls
);
