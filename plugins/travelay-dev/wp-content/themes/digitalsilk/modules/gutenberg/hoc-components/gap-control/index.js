/**
 * WordPress dependencies
 */
import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls } from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
	Popover,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	TextControl,
    Flex,
    FlexItem,
    __experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { getBlockType } from '@wordpress/blocks';
import { sidesVertical } from '@wordpress/icons';
import classnames from 'classnames';
import { v4 as uuidv4 } from 'uuid';
import { isClientLocked } from "../../react-components";

// Register dsPadding attribute for supported blocks
const registerGapAttributes = ( settings ) => {
	if ( settings.supports?.dsGapControl ) {
		settings.attributes = {
			dsPadding: {
				type: 'object',
				default: {
					top: {
						type: 'none',
						desktop: '',
						mobile: ''
					},
					bottom: {
						type: 'none',
						desktop: '',
						mobile: ''
					}
				}
			},
			...settings.attributes,
		};
	}
	return settings;
};

addFilter(
	'blocks.registerBlockType',
	'ds-block-filters/register-gap-attributes',
	registerGapAttributes
);

/**
 * Higher-order component to add gap controls
 */
const withGapControl = createHigherOrderComponent(
	( BlockEdit ) => {
        // Define the gap directions
        const directions = [
            { side: 'top', id: uuidv4() },
            { side: 'bottom', id: uuidv4() },
        ];

        // Check if the component is used by admin.
        const isSuperAdmin = ! isClientLocked();

		return ( props ) => {
			const { attributes, setAttributes, name, wrapperProps } = props;
			const blockType = getBlockType( name );

			if ( ! blockType?.supports?.dsGapControl ) {
				return <BlockEdit { ...props } />;
			}

			const { dsPadding = {} } = attributes;

			const [ isOpen, setIsOpen ] = useState( false );
			const buttonRef = useRef();

			const updatePadding = ( direction, key, value ) => {
				setAttributes(
                    {
                        dsPadding: {
                            ...dsPadding,
                            [ direction ]: {
                                ...dsPadding[ direction ],
                                [ key ]: value
                            }
                        }
				    }
                );
			};

			// Compute classnames and inline styles
			const classes = [];
			const styles = { ...wrapperProps?.style };

			if ( dsPadding.top ) {
				switch ( dsPadding.top.type ) {
					case 'small':
						classes.push( 'gt-s' );
						break;
					case 'default':
						classes.push( 'gt' );
						break;
					case 'large':
						classes.push( 'gt-l' );
						break;
					case 'custom':
						classes.push( 'gt-custom' );
						if ( dsPadding.top.desktop ) {
							styles['--gt-custom'] = dsPadding.top.desktop;
						}
						if ( dsPadding.top.mobile ) {
							styles['--gt-custom-mobile'] = dsPadding.top.mobile;
						}
						break;
					default:
						break;
				}
			}

			if ( dsPadding.bottom ) {
				switch ( dsPadding.bottom.type ) {
					case 'small':
						classes.push( 'gb-s' );
						break;
					case 'default':
						classes.push( 'gb' );
						break;
					case 'large':
						classes.push( 'gb-l' );
						break;
					case 'custom':
						classes.push( 'gb-custom' );
						if ( dsPadding.bottom.desktop ) {
							styles['--gb-custom'] = dsPadding.bottom.desktop;
						}
						if ( dsPadding.bottom.mobile ) {
							styles['--gb-custom-mobile'] = dsPadding.bottom.mobile;
						}
						break;
					default:
						break;
				}
			}

			return (
				<>
					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton
								icon={ sidesVertical }
								label={__('Gaps')}
								isPressed={ isOpen }
								onClick={ () => setIsOpen( ( prev ) => ! prev ) }
								ref={ buttonRef }
                                onMouseDown={ (e) => e.preventDefault() }
							/>
						</ToolbarGroup>
					</BlockControls>

					{ isOpen && (
						<Popover
							onClose={ () => setIsOpen( false ) }
							focusOnMount={ true }
							position="bottom"
							anchor={ buttonRef.current }
							offset={ 12 }
						>
							<div style={ { width: 290, padding: '1.6rem' } }>
                                { directions.map( ( { side, id } ) => {
	                                const value = dsPadding[ side ] || {};
									return (
										<div key={ id } style={ { marginBottom: 16 } }>
                                            <ToggleGroupControl
                                                __nextHasNoMarginBottom
                                                __next40pxDefaultSize
                                                value={ value.type }
                                                label={`${__( 'Gap' )} ${ side.charAt( 0 ).toUpperCase() + side.slice( 1 ) }`}
                                                isBlock
                                                isAdaptiveWidth
                                                onChange={
                                                    ( newType ) => {
                                                        setAttributes( {
                                                            dsPadding: {
                                                                ...dsPadding,
                                                                [ side ]: {
                                                                    ...dsPadding[ side ],
                                                                    type: newType,
                                                                    ...( newType !== 'custom' && {
                                                                        desktop: '',
                                                                        mobile: ''
                                                                    } )
                                                                }
                                                            }
                                                        } );
                                                    }
                                                }
                                            >
                                                <ToggleGroupControlOption value="none" label={__('None')} />
                                                <ToggleGroupControlOption value="small" label={__('Small')} />
                                                <ToggleGroupControlOption value="default" label={__('Default')} />
                                                <ToggleGroupControlOption value="large" label={__('Large')} />
                                                <ToggleGroupControlOption value="custom" label={__('Custom')} />
                                            </ToggleGroupControl>

											{ value.type === 'custom' && (
												<Flex style={ { marginTop: 8 } }>
                                                    <FlexItem style={ { width: '50%' } }>
                                                        {
                                                            isSuperAdmin ? (
                                                                <TextControl
                                                                    __next40pxDefaultSize
                                                                    __nextHasNoMarginBottom
                                                                    label={__( 'Desktop' ) }
                                                                    placeholder={__('30vh, clamp(..)')}
                                                                    value={ value.desktop || '' }
                                                                    onChange={ ( val ) => updatePadding( side, 'desktop', val ) }
                                                                />
                                                            ) : (
                                                                <UnitControl
                                                                    __next40pxDefaultSize
                                                                    __nextHasNoMarginBottom
                                                                    label={__('Desktop')}
                                                                    placeholder={__('50')}
                                                                    value={ value.desktop || '' }
                                                                    onChange={ ( val ) => updatePadding( side, 'desktop', val ) }
                                                                />
                                                            )
                                                        }
                                                    </FlexItem>
                                                    <FlexItem style={ { width: '50%' } }>
                                                        {
                                                            isSuperAdmin ? (
                                                                <TextControl
                                                                    __next40pxDefaultSize
                                                                    __nextHasNoMarginBottom
                                                                    label={__('Mobile')}
                                                                    placeholder={__('10vmin, 20%')}
                                                                    value={ value.mobile || '' }
                                                                    onChange={ ( val ) => updatePadding( side, 'mobile', val ) }
                                                                />
                                                            ) : (
                                                                <UnitControl
                                                                    __next40pxDefaultSize
                                                                    __nextHasNoMarginBottom
                                                                    label={__('Mobile')}
                                                                    placeholder={__('30')}
                                                                    value={ value.mobile || '' }
                                                                    onChange={ ( val ) => updatePadding( side, 'mobile', val ) }
                                                                />
                                                            )
                                                        }
                                                    </FlexItem>
												</Flex>
											) }
										</div>
									);
								} ) }
							</div>
						</Popover>
					) }

					<BlockEdit
						{ ...props }
						wrapperProps={
                            {
                                ...wrapperProps,
                                className: classnames( wrapperProps?.className, classes ),
                                style: styles
						    }
                        }
					/>
				</>
			);
		};
	},
	'withGapControl'
);

addFilter(
	'editor.BlockEdit',
	'ds-block-filters/gap-control',
	withGapControl
);
