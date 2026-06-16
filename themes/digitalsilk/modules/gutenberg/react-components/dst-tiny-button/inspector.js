import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
} from '@wordpress/block-editor';
import {
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
} from '@wordpress/icons';

export const ButtonInspector = (
    {
        value = {},
        onChange,
    }
) => {
    const {
		iconType,
		iconRevesed,
		iconPosition,
    } = value;


    return (
            <InspectorControls>
                <PanelBody>
                    <ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        label={__('Icon Display')}
                        value={iconType}
                        onChange={
                            (newValue) => {
                                onChange(
                                    {
                                        ...value,
                                        iconType: newValue
                                    }
                                )
                            }
                        }
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
                                    onChange={
                                        (newValue) => {
                                            onChange(
                                                {
                                                    ...value,
                                                    iconRevesed: newValue
                                                }
                                            )
                                        }
                                    }
                                />
                                <ToggleGroupControl
                                    __next40pxDefaultSize
                                    __nextHasNoMarginBottom
                                    label={__('Icon Position')}
                                    value={iconPosition}
                                    onChange={
                                        (newValue) => {
                                            onChange(
                                                {
                                                    ...value,
                                                    iconPosition: newValue
                                                }
                                            )
                                        }
                                    }
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
            </InspectorControls>
    )
}