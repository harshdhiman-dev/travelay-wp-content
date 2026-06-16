import {useRef} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BlockControls, HeadingLevelDropdown, AlignmentToolbar } from '@wordpress/block-editor';
import {
    TextControl,
	ToolbarGroup,
	ToolbarButton,
	Popover,
	Flex,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
	overlayText,
	heading,
	headingLevel1,
	headingLevel2,
	headingLevel3,
	headingLevel4,
	headingLevel5,
	headingLevel6,
	alignLeft,
	alignCenter,
	alignRight,
	alignNone,
	styles,
} from '@wordpress/icons';

export const HeadingToolbar = (
    {
        value,
        onChange,
        backtitlePopoverVisible,
        setBacktitlePopoverVisible,
        headingPopoverVisible,
        setHeadingPopoverVisible,
        alignmentPopoverVisible,
        setAlignmentPopoverVisible,
    }
) => {
    const { backtitle, alignment, alignmentMobile, title_styles, headingTheme } = value;
    const { tag, tag_style } = title_styles || {};

    // Refs for the toolbar buttons
    const buttonRef = useRef();
    const headingButtonRef = useRef();
    const alignmentButtonRef = useRef();
    const themeButtonRef = useRef();


	// Define the icon mapping for heading levels
	const headingIcons = {
		h1: headingLevel1,
		h2: headingLevel2,
		h3: headingLevel3,
		h4: headingLevel4,
		h5: headingLevel5,
		h6: headingLevel6,
	};

	// Get the appropriate icon based on the current tag, default to `heading` if undefined
	const currentHeadingIcon = headingIcons[tag] || heading;

	// Define the icon mapping for heading levels
	const headingAlignment = {
		left: alignLeft,
		center: alignCenter,
		right: alignRight,
	};

	// Get the appropriate icon based on the current tag, default to `heading` if undefined
	const currentAlignmentIcon = headingAlignment[alignment] || alignNone;

    // Function to convert heading value ( h1 ) to number ( 1 )
    const HeadingValueToNumber = (hValue) => {
        return parseInt( hValue.replace( /\D/g, '' ), 10 )
    }

    return (
        <>
            { /* Inline block controls */}
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={currentHeadingIcon}
                        label={__('Change Level')}
                        isActive={headingPopoverVisible}
                        onClick={
                            () => {
                                setHeadingPopoverVisible(!headingPopoverVisible);
                                setAlignmentPopoverVisible(false);
                                setBacktitlePopoverVisible(false);
                            }
                        }
                        ref={headingButtonRef}
                    />
                    <ToolbarButton
                        icon={currentAlignmentIcon}
                        label={__('Change Alignment')}
                        isActive={alignmentPopoverVisible}
                        onClick={
                            () => {
                                setAlignmentPopoverVisible(!alignmentPopoverVisible);
                                setHeadingPopoverVisible(false);
                                setBacktitlePopoverVisible(false);
                            }
                        }
                        ref={alignmentButtonRef}
                    />
                    <ToolbarButton
                        icon={overlayText}
                        isActive={backtitlePopoverVisible || backtitle}
                        label={__('Change Backtitle')}
                        onClick={
                            () => {
                                setBacktitlePopoverVisible(!backtitlePopoverVisible);
                                setHeadingPopoverVisible(false);
                                setAlignmentPopoverVisible(false);
                            }
                        }
                        ref={buttonRef}
                    />
                    {/* Theme selector button */}
                    <ToolbarButton
                        icon={styles}
                        isActive={headingTheme === 'inverted'}
                        label={__('Toggle Theme')}
                        onClick={() => {
                            const newTheme = headingTheme === 'default' ? 'inverted' : 'default';
                            onChange(
                                {
                                    ...value,
                                    headingTheme: newTheme
                                }
                            );
                        }}
                        ref={themeButtonRef}
                    />
                    {/* Popover for the heading level and styles */}
                    {
                        headingPopoverVisible && headingButtonRef.current && (
                            <Popover
                                position="bottom center"
                                onClose={() => setHeadingPopoverVisible(false)}
                                onFocusOutside={() => setHeadingPopoverVisible(false)}
                                anchor={headingButtonRef.current}
                                focusOnMount={false}
                                variant="toolbar"
                            >
                                <div style={
                                    {
                                        width: '165px',
                                        padding: '8px',
                                    }
                                }>
                                    <Flex gap={0} justify='flex-start' className="sub-level-dropdown -is-level">
                                        <HeadingLevelDropdown
                                            value={ tag ? HeadingValueToNumber(tag) : 1 }
                                            onChange={
                                                (newVal) => onChange(
                                                    {
                                                        ...value,
                                                        title_styles: {
                                                            ...title_styles,
                                                            tag: `h${newVal}`,
                                                            tag_style: `h${newVal}`
                                                        }
                                                    }
                                                )
                                            }
                                        />
                                    </Flex>
                                    <Flex gap={0} justify='flex-start' className="sub-level-dropdown -is-style">
                                        <HeadingLevelDropdown
                                            value={ tag_style ? HeadingValueToNumber(tag_style) : 1 }
                                            onChange={
                                                (newVal) => onChange(
                                                    {
                                                        ...value,
                                                        title_styles: {
                                                            ...title_styles,
                                                            tag_style: `h${newVal}`
                                                        }
                                                    }
                                                )
                                            }
                                        />
                                    </Flex>
                                </div>
                            </Popover>
                        )
                    }
                    {/* Popover for the Alignment toolbar */}
                    {
                        alignmentPopoverVisible && (
                            <Popover
                                position="bottom center"
                                onClose={() => setAlignmentPopoverVisible(false)}
                                onFocusOutside={() => setAlignmentPopoverVisible(false)}
                                anchor={alignmentButtonRef.current}
                                focusOnMount={false}
                                variant="toolbar"
                            >
                                <div style={
                                    {
                                        width: '165px',
                                        padding: '8px',
                                    }
                                }>
                                    <Flex gap={0} justify='flex-start' className="sub-level-dropdown -is-alignment-desktop">
                                        <AlignmentToolbar
                                            value={alignment || 'left'}
                                            onChange={
                                                (newVal) => {
                                                    onChange(
                                                        {
                                                            ...value,
                                                            alignment: newVal,
                                                            alignmentMobile: newVal
                                                        }
                                                    );
                                                }
                                            }
                                        />
                                    </Flex>
                                    <Flex gap={0} justify='flex-start' className="sub-level-dropdown -is-alignment-mobile">
                                        <AlignmentToolbar
                                            value={alignmentMobile || 'left'}
                                            onChange={
                                                (newVal) => {
                                                    onChange(
                                                        {
                                                            ...value,
                                                            alignmentMobile: newVal
                                                        }
                                                    );
                                                }
                                            }
                                        />
                                    </Flex>
                                </div>
                            </Popover>
                        )
                    }
                    {/* Popover for the backtitle */}
                    {
                        backtitlePopoverVisible && (
                            <Popover
                                position="bottom center"
                                onClose={() => setBacktitlePopoverVisible(false)}
                                onFocusOutside={() => setBacktitlePopoverVisible(false)}
                                anchor={buttonRef.current}
                                focusOnMount={false}
                                variant="toolbar"
                            >
                                <div style={
                                    {
                                        width: '300px',
                                        padding: '1em',
                                    }
                                }>
                                    <TextControl
                                        __next40pxDefaultSize
                                        __nextHasNoMarginBottom
                                        label={__('Backtitle')}
                                        value={backtitle}
                                        onChange={
                                            (newValue) => onChange(
                                                {
                                                    ...value,
                                                    backtitle: newValue
                                                }
                                            )
                                        }
                                        placeholder={__('Enter backtitle...')}
                                    />
                                </div>
                            </Popover>
                        )
                    }
                </ToolbarGroup>
            </BlockControls>
        </>
    );
};
