import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    TabPanel,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    FocalPointPicker,
    Icon,
    Flex,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { desktop as desktopIcon, mobile as mobileIcon } from '@wordpress/icons';
import { buildMediaPayload } from './utilities';

/**
 * Extracts media styles from the block value.
 *
 * @param {Object} value The block attributes.
 * @return {Object} The extracted media styles.
 */
export const getMediaStyles = ( value ) => {
    return {
        desktop: {
            mediaRatio: value?.style?.desktop?.mediaRatio || '16x9',
            mediaFit: value?.style?.desktop?.mediaFit || 'cover',
            focalPoint: value?.style?.desktop?.focalPoint || { x: 0.5, y: 0.5 },
        },
        mobile: {
            mediaRatio: value?.style?.mobile?.mediaRatio || '16x9',
            mediaFit: value?.style?.mobile?.mediaFit || 'cover',
            focalPoint: value?.style?.mobile?.focalPoint || { x: 0.5, y: 0.5 },
        }
    };
};

/**
 * MediaStyles Component.
 *
 * This component provides style settings for the media block, allowing users to configure
 * media ratio, media fit, and focal points for both desktop and mobile views.
 *
 * @param {Object}   props                    Component properties.
 * @param {Object}   props.value              The block attributes containing style settings.
 * @param {Function} props.onChange           Callback function to update block attributes.
 * @param {Object}   props.focalPointDesktop  The current focal point settings for desktop.
 * @param {Object}   props.focalPointMobile   The current focal point settings for mobile.
 * @param {Function} props.onFocalPointChange Function to update the focal point state in the parent.
 * @param {boolean}  props.panelOpened        Indicates if the panel is currently opened.
 *
 * @return {JSX.Element} The rendered MediaStyles component.
 */
export const MediaStyles = ( { value, onChange, focalPointDesktop, focalPointMobile, onFocalPointChange, panelOpened } ) => {

    // Local state for styles
    const [ styles, setStyles ] = useState( getMediaStyles(value) );

    // Local state to check for media type ( does not need to be exported ).
    const [ mediaType, setMediaType ] = useState( value?.primaryType || 'image' );

    /**
     * Updates media URLs when the block value changes.
     */
    useEffect(() => {
        // Update media type
        if ( value?.primaryType ) {
            setMediaType( value.primaryType );
        }
    }, [ value ]);

    /**
     * Sync styles with external value updates.
     * Ensures that if `value.style` is updated from another component, the local state is updated.
     */
    useEffect(
        () => {
            setStyles(getMediaStyles(value));
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ value?.style ]
    );

    /**
     * Updates the block's media styles.
     *
     * @param {string} key      The style property to update.
     * @param {string} device   Either "desktop" or "mobile".
     * @param {any}    newValue The new value for the style.
     */
    const updateStyles = ( key, device, newValue ) => {
        const isMediaFitChange = key === 'mediaFit';

        const updatedStyles = {
            ...styles,
            [device]: {
                ...styles[device],
                [key]: newValue,
                // Reset focal point when mediaFit changes
                focalPoint: isMediaFitChange ? { x: 0.5, y: 0.5 } : styles[device].focalPoint,
            },
        };

        // Update local state immediately
        setStyles(updatedStyles);

        // Notify parent (`index.js`) about changes, including resetting focal point
        onChange(
            buildMediaPayload('style', updatedStyles, value)
        );

        // Also trigger focal point reset in parent state (index.js)
        if (isMediaFitChange) {
            onFocalPointChange(device, { x: 0.5, y: 0.5 });
        }
    };

    return (
        <TabPanel
            className="ds-responsive-styles-tabs"
            tabs={
                [
                    {
                        name: 'desktop',
                        title: (
                            <Flex align='center' gap={2}>
                                <Icon icon={ desktopIcon } />
                                <span>{__( 'Desktop' )}</span>
                            </Flex>
                        ),
                    },
                    {
                        name: 'mobile',
                        title: (
                            <Flex align='center' gap={2}>
                                <Icon icon={ mobileIcon } />
                                <span>{__( 'Mobile' )}</span>
                            </Flex>
                        ),
                    }
                ]
            }
        >
            {
                ( tab ) => (
                    'desktop' === tab.name ? (
                        <>
                            <ToggleGroupControl
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                                isBlock
                                label={ __( 'Media Ratio' ) }
                                value={ styles.desktop.mediaRatio }
                                onChange={ ( newValue ) => updateStyles('mediaRatio', 'desktop', newValue) }
                            >
                                <ToggleGroupControlOption label={ __( '16x9' ) } value="16x9" />
                                <ToggleGroupControlOption label={ __( '7x5' ) } value="7x5" />
                                <ToggleGroupControlOption label={ __( '4x3' ) } value="4x3" />
                                <ToggleGroupControlOption label={ __( '3x4' ) } value="3x4" />
                                <ToggleGroupControlOption label={ __( '1x1' ) } value="1x1" />
                                <ToggleGroupControlOption label={ __( 'none' ) } value="none" />
                            </ToggleGroupControl>
                            <ToggleGroupControl
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                                label={ __( 'Media Fit' ) }
                                value={ styles.desktop.mediaFit }
                                onChange={ ( newValue ) => updateStyles('mediaFit', 'desktop', newValue) }
                            >
                                <ToggleGroupControlOption label={ __( 'Cover' ) } value="cover" />
                                <ToggleGroupControlOption label={ __( 'Contain' ) } value="contain" />
                            </ToggleGroupControl>
                            {
                                styles.desktop.mediaFit === 'cover' && mediaType !== 'videoExternal' && (
                                    <FocalPointPicker
                                        __nextHasNoMarginBottom
                                        label={ __( 'Focal Point' ) }
                                        value={ focalPointDesktop }
                                        url={ value?.imagePrimary?.url || value?.videoLocal?.url || '' }
                                        onChange={ ( newValue ) => onFocalPointChange('desktop', newValue) }
                                        onDrag={ ( newValue ) => onFocalPointChange('desktop', newValue) }
                                    />
                                )
                            }
                        </>
                    ) : (
                        <>
                            <ToggleGroupControl
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                                isBlock
                                label={ __( 'Media Ratio' ) }
                                value={ styles.mobile.mediaRatio }
                                onChange={ ( newValue ) => updateStyles('mediaRatio', 'mobile', newValue) }
                            >
                                <ToggleGroupControlOption label={ __( '16x9' ) } value="16x9" />
								<ToggleGroupControlOption label={ __( '7x5' ) } value="7x5" />
                                <ToggleGroupControlOption label={ __( '4x3' ) } value="4x3" />
                                <ToggleGroupControlOption label={ __( '3x4' ) } value="4x3" />
                                <ToggleGroupControlOption label={ __( '1x1' ) } value="1x1" />
                                <ToggleGroupControlOption label={ __( 'none' ) } value="none" />
                            </ToggleGroupControl>
                            <ToggleGroupControl
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                                label={ __( 'Media Fit' ) }
                                value={ styles.mobile.mediaFit }
                                onChange={ ( newValue ) => updateStyles('mediaFit', 'mobile', newValue) }
                            >
                                <ToggleGroupControlOption label={ __( 'Cover' ) } value="cover" />
                                <ToggleGroupControlOption label={ __( 'Contain' ) } value="contain" />
                            </ToggleGroupControl>
                            {
                                styles.mobile.mediaFit === 'cover' && mediaType !== 'videoExternal' && (
                                    <FocalPointPicker
                                        __nextHasNoMarginBottom
                                        label={ __( 'Focal Point' ) }
                                        value={ focalPointMobile }
                                        url={ value?.imagePrimaryMobile?.url || value?.imagePrimary?.url || value?.videoLocal?.url || '' }
                                        onChange={ ( newValue ) => onFocalPointChange('mobile', newValue) }
                                        onDrag={ ( newValue ) => onFocalPointChange('mobile', newValue) }
                                    />
                                )
                            }
                        </>
                    )
                )
            }
        </TabPanel>
    );
};
