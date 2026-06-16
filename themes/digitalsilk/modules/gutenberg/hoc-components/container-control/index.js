/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useState, useEffect } from '@wordpress/element';
import { getBlockType } from '@wordpress/blocks';
import { BlockControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    ToolbarDropdownMenu,
    Modal,
    Icon,
    __experimentalUnitControl as UnitControl,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
    Popover,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { stretchFullWidth, alignNone, stretchWide, resizeCornerNE, sidesHorizontal, formatOutdent, alignCenter, formatOutdentRTL } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * Registers container attributes for blocks that support dsContainers.
 *
 * @param {Object} settings The block settings.
 * @return {Object} The modified block settings.
 */
const registerContainerAttributes = ( settings ) => {
    if ( settings.supports?.dsContainers ) {
        settings.attributes = {
            dsContainer: {
                type: 'string',
                default: 'container',
            },
            dsContainerCustom: {
                type: 'string',
                default: '',
            },
            dsContainerSideGap: {
                type: 'boolean',
                default: true,
            },
            dsContainerAlign: {
                type: 'string',
                default: 'center',
            },
            ...settings.attributes,
        };
    }
    return settings;
};

addFilter(
    'blocks.registerBlockType',
    'ds-block-filters/register-container-attributes',
    registerContainerAttributes
);

/**
 * Higher-order component to add container toolbar and apply wrapper class/style.
 *
 * @param {Function} BlockEdit The original block edit component.
 * @return {Function} The enhanced block edit component.
 */
const withContainerClass = createHigherOrderComponent(
    ( BlockEdit ) => {
        // Define container widths.
        const containerWidthDefault = getComputedStyle(document.documentElement).getPropertyValue('--dst--default-container-width').trim();
        const containerWideWidth = getComputedStyle(document.documentElement).getPropertyValue('--dst--wide-container-width').trim();
        const containerFullWidth = '100%';

        return ( props ) => {
            const { attributes, setAttributes, name, wrapperProps = {} } = props;
            const blockType = getBlockType( name );

            if ( ! blockType?.supports?.dsContainers ) {
                return <BlockEdit { ...props } />;
            }

            const { dsContainer, dsContainerCustom, dsContainerSideGap, dsContainerAlign } = attributes;

            // State for modal and popover.
            const [ isModalOpen, setModalOpen ] = useState( false );
            const [ isAlignPopoverVisible, setIsAlignPopoverVisible ] = useState( false );

            // State for container width and label.
            const [ containerWidth, setContainerWidth ] = useState('');
            const [ containerLabel, setContainerLabel ] = useState( __('Full Width') );

            /**
             * Handles the selection of a container type from the dropdown.
             *
             * @param {string} value The selected container type.
             */
            const handleSelect = ( value ) => {
                setAttributes( { dsContainer: value } );

                if ( ! value ) {
                    setAttributes( { dsContainerSideGap: true } );
                }

                if ( value === 'container-custom' ) {
                    setModalOpen( true );
                } else {
                    // Reset alignment if not custom container
                    setAttributes( { dsContainerAlign: 'center' } );
                }
            };

            /**
             * Handles updating the custom width value.
             *
             * @param {string} newValue The new width value.
             */
            const handleCustomWidthChange = ( newValue ) => {
                setAttributes( { dsContainerCustom: newValue || '' } );
            };

            /**
             * Handles updating the container alignment.
             *
             * @param {string} newAlign The new alignment value.
             */
            const handleAlignChange = ( newAlign ) => {
                setAttributes( { dsContainerAlign: newAlign } );
                setIsAlignPopoverVisible( false ); // Re-add closing popover
            };

            useEffect(
                () => {
                    if ( dsContainer !== 'container-custom' && dsContainerCustom ) {
                        setAttributes( { dsContainerCustom: '' } );
                    }
                },
                // eslint-disable-next-line react-hooks/exhaustive-deps
                [ dsContainer ]
            );

            // Fetch container widths from CSS custom properties
            // and set labels based on the selected container type.
            useEffect(
                () => {
                    if ( dsContainer === 'container-custom' ) {
                        setContainerWidth( dsContainerCustom || '' );
                        setContainerLabel( __('Custom') );
                    } else if ( dsContainer === 'container-wide' ) {
                        setContainerWidth( containerWideWidth );
                        setContainerLabel( __('Wide') );
                    }
                    else if ( dsContainer === 'container' ) {
                        setContainerWidth( containerWidthDefault );
                        setContainerLabel( __('Default') );
                    } else {
                        setContainerWidth( containerFullWidth );
                        setContainerLabel( __('Full') );
                    }
                },
                // eslint-disable-next-line react-hooks/exhaustive-deps
                [ dsContainer, dsContainerCustom ]
            );

            // Container icon mapping
            const iconMap = {
                '': stretchFullWidth,
                'container': alignNone,
                'container-wide': stretchWide,
                'container-custom': resizeCornerNE,
            };

            // Alignment icon mapping
            const alignIconMap = {
                left: formatOutdent,
                center: alignCenter,
                right: formatOutdentRTL,
            };

            // Map empty string (full width) to 'container-fluid' to follow same pattern
            const dsContainerClass = dsContainer === '' ? 'container-fluid' : dsContainer;

            const updatedClasses = classnames(
                dsContainerClass,
                {
                    'no-side-padding' : ! dsContainerSideGap,
                    // Add alignment class only for custom container
                    [ `container-${dsContainerAlign}` ]: dsContainer === 'container-custom' && dsContainerAlign,
                }
            );

            const updatedWrapperProps = {
                ...wrapperProps,
                className: classnames( wrapperProps?.className, updatedClasses ),
                style: {
                    ...wrapperProps?.style,
                    ...( dsContainer === 'container-custom' && dsContainerCustom
                        ? { '--l-container-width': dsContainerCustom }
                        : {} ),
                },
            };

            return (
                <>
                    <BlockControls>
                        <ToolbarGroup>
                            <ToolbarDropdownMenu
                                icon={ () => (
                                    <>
                                        <Icon icon={iconMap[ dsContainer ] || alignNone} />
                                        <span>{ `${__('Width')} : ${containerLabel} ` }<span style={{opacity: 0.5}}>{containerWidth}</span></span>
                                    </>
                                ) }
                                label={ __( 'Select container type' ) }
                                controls={ [
                                    {
                                        title: `${__( 'Full Width' )} : ${containerFullWidth}`,
                                        icon: stretchFullWidth,
                                        onClick: () => handleSelect( '' ),
                                        isActive: '' === dsContainer,
                                    },
                                    {
                                        title: `${__( 'Default' )} : ${containerWidthDefault}`,
                                        icon: alignNone,
                                        onClick: () => handleSelect( 'container' ),
                                        isActive: 'container' === dsContainer,
                                    },
                                    {
                                        title: `${__( 'Wide' )} : ${containerWideWidth}`,
                                        icon: stretchWide,
                                        onClick: () => handleSelect( 'container-wide' ),
                                        isActive: 'container-wide' === dsContainer,
                                    },
                                    {
                                        title: dsContainerCustom ? `${__( 'Custom' )} : ${dsContainerCustom}` : __( 'Custom' ),
                                        icon: resizeCornerNE,
                                        onClick: () => handleSelect( 'container-custom' ),
                                        isActive: 'container-custom' === dsContainer,
                                    },
                                ] }
                            />

                            { /* Restore Alignment Control using ToolbarButton and Popover */ }
                            { dsContainer === 'container-custom' && (
                                <>
                                    <ToolbarButton
                                        icon={ alignIconMap[ dsContainerAlign ] || alignCenter }
                                        label={ __( 'Align Custom Container' ) }
                                        onClick={ () => setIsAlignPopoverVisible( ! isAlignPopoverVisible ) }
                                        isActive={ !! dsContainerAlign && dsContainerAlign !== 'center' }
                                        onMouseDown={ (e) => e.preventDefault() }
                                    />
                                    { isAlignPopoverVisible && (
                                        <Popover
                                            placement="bottom-end"
                                            onClose={ () => setIsAlignPopoverVisible( false ) }
                                            offset={ 6 }
                                            focusOnMount='container'
                                        >
                                            <div style={ { padding: '0 6px 7px' } } >
                                                <ToggleGroupControl
                                                    __nextHasNoMarginBottom
                                                    __next40pxDefaultSize
                                                    value={ dsContainerAlign }
                                                    isBlock
                                                    isAdaptiveWidth
                                                    onChange={ (newValue) => handleAlignChange( newValue ) }
                                                    style={ { borderColor: 'transparent' } }
                                                >
                                                    <ToggleGroupControlOptionIcon icon={ formatOutdent } value="left" label={__('Align Left')} />
                                                    <ToggleGroupControlOptionIcon icon={ alignCenter } value="center" label={__('Align Center')} />
                                                    <ToggleGroupControlOptionIcon icon={ formatOutdentRTL } value="right" label={__('Align Right')} />
                                                </ToggleGroupControl>
                                            </div>
                                        </Popover>
                                    ) }
                                </>
                            ) }
                            {
                                dsContainer !== undefined && (
                                    <ToolbarButton
                                        icon={ sidesHorizontal }
                                        label={ dsContainerSideGap ? __( 'Disable Side Gap') : __( 'Enable Side Gap' ) }
                                        onClick={ () => setAttributes({ dsContainerSideGap: !dsContainerSideGap }) }
                                        isActive={ dsContainerSideGap }
                                    />
                                )
                            }
                        </ToolbarGroup>
                    </BlockControls>
                    { isModalOpen && (
                        <Modal
                            title={ __( 'Custom Container Width' ) }
                            onRequestClose={ () => setModalOpen( false ) }
                        >
                            <UnitControl
                                label={ __( 'Set Custom Width' ) }
                                value={ dsContainerCustom }
                                onChange={ handleCustomWidthChange }
                            />
                        </Modal>
                    ) }
                    <BlockEdit { ...props } wrapperProps={ updatedWrapperProps } />
                </>
            );
        };
    },
    'withContainerClass'
);

addFilter(
    'editor.BlockEdit',
    'ds-block-filters/with-container-class',
    withContainerClass
);
