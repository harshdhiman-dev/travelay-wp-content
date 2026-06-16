import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
} from '@wordpress/block-editor';
import {
    Button,
    Icon,
    Flex,
    PanelBody,
    ToggleControl,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
    __experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
    arrowLeft,
    arrowRight,
    seen,
    unseen
} from '@wordpress/icons';

export const ButtonInspector = (
    {
        blockProps,
        popupOpen,
        setPopupOpen,
    }
) => {
    const { attributes, setAttributes } = blockProps;
    const {
		iconType,
		iconRevesed,
		iconPosition,
        hasPopup,
    } = attributes;


    return (
            <InspectorControls>
                <PanelBody>
                    <ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Icon Display')}
                        value={iconType}
                        onChange={(value) => setAttributes({ iconType: value })}
                        help={__("Modifies the button icon: Default follows theme settings, Custom lets you choose an icon and position, and Hidden removes it.")}
                        isAdaptiveWidth
                    >
                        <ToggleGroupControlOption
                            label={__('Default')}
                            value="default"
                        />
                        <ToggleGroupControlOption
                            label={__('Custom')}
                            value="custom"
                        />
                        <ToggleGroupControlOption
                            label={__('Hidden')}
                            value="none"
                        />
                    </ToggleGroupControl>
                    {
                        'custom' === iconType && (
                            <>
                                <ToggleControl
                                    __nextHasNoMarginBottom
                                    checked={iconRevesed}
                                    label={__('Reverse Icon Direction')}
                                    onChange={(value) => setAttributes({ iconRevesed: value })}
                                />
                                <ToggleGroupControl
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    label={__('Icon Position')}
                                    value={iconPosition}
                                    onChange={(value) => setAttributes({ iconPosition: value })}
                                    isAdaptiveWidth
                                >
                                    <ToggleGroupControlOptionIcon
                                        label={__('Left')}
                                        value='row-reverse'
                                        icon={arrowLeft}
                                    />
                                    <ToggleGroupControlOptionIcon
                                        label={__('Right')}
                                        value='row'
                                        icon={arrowRight}
                                    />
                                </ToggleGroupControl>
                            </>
                        )
                    }
                </PanelBody>
                <PanelBody>
                    <ToggleControl
                        __nextHasNoMarginBottom
                        checked={hasPopup}
                        label={ hasPopup ?  __('Remove Popup') : __('Add Popup') }
                        onChange={(value) => {
                            setAttributes({ hasPopup: value })
                            setPopupOpen( value );
                        }}
                    />
                    {
                        hasPopup && (
                            <Button
                                variant='secondary'
                                onClick={() => setPopupOpen( ! popupOpen )}
                            >
                                {
                                    popupOpen ? (
                                        <Flex gap={2}>
                                            {__('Hide Popup')}
                                            <Icon icon={unseen} />
                                        </Flex>
                                    ) : (
                                        <Flex gap={2}>
                                            {__('Show Popup')}
                                            <Icon icon={seen} />
                                        </Flex>
                                    )
                                }
                            </Button>
                        )
                    }
                </PanelBody>
            </InspectorControls>
    )
}