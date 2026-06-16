/* eslint-disable jsx-a11y/click-events-have-key-events */
/* eslint-disable jsx-a11y/no-noninteractive-element-interactions */
import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
    Button,
    Popover,
    PanelBody,
    Flex,
    Modal,
} from '@wordpress/components';
import {
    RenderIconPill,
    RenderIconControls,
    SelectedIconDisplay,
} from './components';
import { useClickOutside } from './utilities';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';

// Let's cache the theme icons to avoid fetching them multiple times.
let cachedThemeIcons = null;

/**
 * DstIconPicker Component
 *
 * This component allows users to select an SVG icon from a list of icons
 * loaded from a REST API. The component supports icon selection, removal,
 * and passes the selected icon to an external onChange handler for use in
 * other components or blocks.
 *
 * Required block attribute is a string representing the icon slug ( or an icon id, if it's an uploaded icon ).
 *
 * Example usage:
 * import { DstIconPicker } from '../../react-components';
 *
 * <DstIconPicker
 *  icon={icon}
 *  onChange={(newIcon) => setAttributes({ icon: newIcon })}
 *  size={25}
 *  iconSet = { ['theme', 'social', 'buttons', 'general'] }
 *  placeholder={__('This is a text placeholder')}
 *  showInline={true}
 *  displayAs="inline"
  />
 *
 * @param {Object}   props               - Component props
 * @param {string}   [props.icon]        - The current icon slug (optional)
 * @param {number}   [props.size]        - Size of the displayed icon (default is 28) (optional)
 * @param {Function} [props.onChange]    - Callback function triggered when an icon is selected/removed
 * @param {string}   [props.placeholder] - Text to display on the button when no icon is selected (optional)
 * @param {string}   [props.className]   - Additional CSS class names (optional)
 * @param {Array}    [props.iconSet]     - Array of icon categories to include in the picker (optional)
 * @param {boolean}  [props.disabled]    - Whether the icon picker is disabled (default is false)
 * @param {boolean}  [props.showInline]  - Whether to show the icon picker inline ( in a popover ) or in an inspectorControls Modal. (default is true)
 * @param {string}   [props.displayAs]   - How to display the SVG: 'inline' (default) or 'img'
 * @param {Object}   [props.style]       - Inline styles (optional)
 *
 * @return {JSX.Element} The rendered component
 */
export const DstIconPicker = ({
    icon,
    size,
    onChange,
    placeholder,
    className: cssClass,
    iconSet = ['theme', 'social', 'buttons', 'general'],
    disabled = false,
    showInline = true,
    displayAs = 'inline',
    ...props
}) => {
    // States for the current selected icon and available icons fetched from the API
    const [selectedIcon, setSelectedIcon] = useState(icon || '');
    const [availableIcons, setAvailableIcons] = useState([]);
    const [isPopoverVisible, setIsPopoverVisible] = useState(false);

    // State to store the fetched attachment data.
    const [attachment, setAttachment] = useState(null);
    const [loading, setLoading] = useState(true);

    // Button refferences, used for popover.
    const buttonRefInline = useRef();
    const popoverRef = useRef();

    // Extract constants from our local data store.
    const { iconLibraryDataStore } = window;
    let { iconsList } = iconLibraryDataStore;

    // Ensure iconsList is always an object.
    if ( ! iconsList || Array.isArray( iconsList ) ) {
        iconsList = {};
    }

    const memoizedIconsList = useMemo(() => iconsList, [ iconsList ]);

    // Check if the selectedIcon is a number or a string representing a number.
    const isNumber = !isNaN(selectedIcon) && selectedIcon !== '';

    /**
     * Handles opening the popover, but only if not disabled.
     */
    const handleTogglePopover = () => {
        if (!disabled) {
            setIsPopoverVisible(!isPopoverVisible);
        }
    };

    // Fetch theme icons.
    const fetchThemeIcons = () => {
        if (cachedThemeIcons) {
            setAvailableIcons( cachedThemeIcons );
            return;
        }

        apiFetch(
            { path: '/ds/v1/icons-svg' }
        ).then(
            (response) => {
                cachedThemeIcons = response;
                setAvailableIcons(response);
            }
        ).catch(
            (error) => {
                // eslint-disable-next-line no-console
                console.warn('Error fetching icons:', error);
            }
        );
    };

    // On component mount, fetch available icons via API
    useEffect(
        () => {
            if ( iconSet.includes('theme') ) {
                fetchThemeIcons();
            } else {
                setAvailableIcons([]);
            }
        },
        [ iconSet ]
    );

    // On component mount, fetch available icons via API
    useEffect(
        () => {
            // If our icon is an attachment.
            if ( selectedIcon && isNumber ) {

                // Set attachment to null
                setAttachment(null);

                // Set loading state to true before fetching data.
                setLoading(true);

                // Fetch the attachment data from the WordPress REST API.
                apiFetch(
                    { path: `/wp/v2/media/${selectedIcon}` }
                ).then(
                    (data) => {
                        // On success, set the fetched attachment data and mark loading as false.
                        setAttachment(data);
                        setLoading(false);
                    }
                ).catch(
                    () => {
                        // On error, clear the attachment data and mark loading as false.
                        setAttachment(null);
                        setLoading(false);
                    }
                );
            }
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ selectedIcon ]
    );

    // Sync selectedIcon with props.icon when it changes
    useEffect(
        () => {
            setSelectedIcon(icon || '');
        },
        [icon]
    );

    /**
     * Handles icon selection.
     *
     * Updates the selected icon and triggers the external onChange handler.
     *
     * @param {string} newIcon - The selected icon's slug.
     */
    const selectCurrentIcon = (newIcon) => {
        setSelectedIcon(newIcon);
        setIsPopoverVisible(false);

        // Trigger onChange callback if provided
        if (onChange) {
            onChange(newIcon);
        }
    };

    /**
     * Removes the current selected icon.
     *
     * Clears the selected icon and triggers the external onChange handler with an empty string.
     */
    const removeCurrentIcon = () => {
        setSelectedIcon('');

        // Trigger onChange callback if provided
        if (onChange) {
            onChange('');
        }
    };

    // Render the icon controls wrapper in the popover or inspector.
    const RenderIconControlsWrapper = () => {
        return (
            <div className="iconPopover__wrapper">
                <RenderIconPill
                    selectedIcon={selectedIcon}
                    showInline={showInline}
                    onRemove={removeCurrentIcon}
                />
                <RenderIconControls
                    availableIcons={availableIcons}
                    iconsList={memoizedIconsList || {}}
                    iconSet={iconSet}
                    selectedIcon={selectedIcon}
                    isNumber={isNumber}
                    onSelect={selectCurrentIcon}
                    onRemove={removeCurrentIcon}
                />
            </div>
        );
    }

    // Close the popover when clicking outside of it
    useClickOutside(
        popoverRef,
        () => {
            setIsPopoverVisible(false)
        }
    );

    return (
        <>
            <span
                className={`dst-icon-picker dst-icon ${cssClass || ''} ${!selectedIcon && !showInline ? '-no-icon' : ''}`}
                ref={buttonRefInline}
                {...props}
            >
                {/* Popover for the icons */}
                {showInline && isPopoverVisible && (
                    <Popover
                        ref={popoverRef}
                        position="bottom right"
                        onClose={() => setIsPopoverVisible(false)}
                        onFocusOutside={() => setIsPopoverVisible(false)}
                        anchor={buttonRefInline.current}
                        variant="toolbar"
                        className="iconPopover"
                        noArrow={false}
                        offset={5}
                        __unstableSlotName="dst-icon-picker-popover-slot"
                    >
                        <RenderIconControlsWrapper />
                    </Popover>
                )}

                {/* Button or icon preview */}
                {selectedIcon ? (
                    <SelectedIconDisplay
                        iconData={selectedIcon}
                        isNumber={isNumber}
                        attachment={attachment}
                        size={size}
                        loading={loading}
                        onClick={handleTogglePopover}
                        displayAs={displayAs}
                    />
                ) : (
                    <>
                        {
                            showInline && (
                                <Button
                                    variant="tertiary"
                                    onClick={handleTogglePopover}
                                    size="small"
                                    className="dsIconPicker__button"
                                >
                                    {placeholder || __('Select Icon')}
                                </Button>
                            )
                        }
                    </>
                )}
            </span>
            {
                ! showInline && (
                    <>
                        <InspectorControls>
                            <PanelBody title={__('Icon')}>
                                <Flex gap={2} justify='flex-start'>
                                    <Button
                                        variant="secondary"
                                        onClick={handleTogglePopover}
                                    >
                                        {
                                            selectedIcon ? __('Change Icon') : __('Select Icon')
                                        }
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        isDestructive
                                        onClick={removeCurrentIcon}
                                        disabled={!selectedIcon}
                                    >
                                        {__('Remove Icon')}
                                    </Button>
                                </Flex>
                            </PanelBody>
                        </InspectorControls>
                        {
                            isPopoverVisible && (
                                <Modal
                                    title={__('Select an Icon')}
                                    onRequestClose={() => setIsPopoverVisible(false)}
                                    className="iconPickerModal"
                                >
                                    <RenderIconControlsWrapper />
                                </Modal>
                            )
                        }
                    </>
                )
            }
        </>
    );
};
