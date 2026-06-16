import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
    BaseControl,
    ColorPalette,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export const HeadingInspectorStyles = (
    { 
        value,
        onChange,
    }
) => {
    const { pretitle_color, subtitle_color, title_styles } = value;

    // Get theme color settings.
    const themeColors = useSelect('core/block-editor').getSettings().colors;

    // Function to change heading color.
    const changeHeadingColor = (color) => {
        onChange({
            ...value,
            title_styles: {
                ...title_styles,
                color
            }
        });
    };

    // Function to reset all style attributes.
    const resetColorStyles = () => {
        onChange({
            ...value,
            title_styles: {
                ...title_styles,
                color: null
            },
            pretitle_color: undefined,
            subtitle_color: undefined
        });
    };

    return (
        <InspectorControls group="styles">
            <ToolsPanel label={__('Text Color')} resetAll={resetColorStyles}>
                <ToolsPanelItem
                    hasValue={() => !!pretitle_color}
                    label={__('Pretitle')}
                    onDeselect={
                        () => {
                            onChange({
                                ...value,
                                pretitle_color: undefined
                            });
                        }
                    }
                >
                    <BaseControl
                        __nextHasNoMarginBottom
                        id={null}
                        label={__('Pretitle')}
                    >
                        <ColorPalette
                            colors={themeColors}
                            value={pretitle_color}
                            onChange={
                                (color) => onChange({
                                    ...value,
                                    pretitle_color: color
                                })
                            }
                        />
                    </BaseControl>
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => !!title_styles?.color}
                    label={__('Title')}
                    onDeselect={
                        () => {
                            onChange({
                                ...value,
                                title_styles: {
                                    ...title_styles,
                                    color: null
                                }
                            });
                        }
                    }
                >
                    <BaseControl
                        __nextHasNoMarginBottom
                        id={null}
                        label={__('Title')}
                    >
                        <ColorPalette
                            colors={themeColors}
                            value={title_styles?.color || null}
                            onChange={(color) => changeHeadingColor(color)}
                        />
                    </BaseControl>
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => !!subtitle_color}
                    label={__('Subtitle')}
                    onDeselect={
                        () => {
                            onChange({
                                ...value,
                                subtitle_color: undefined
                            });
                        }
                    }
                >
                    <BaseControl id={null} label={__('Subtitle')}>
                        <ColorPalette
                            colors={themeColors}
                            value={subtitle_color}
                            onChange={(color) => onChange({
                                ...value,
                                subtitle_color: color
                            })}
                        />
                    </BaseControl>
                </ToolsPanelItem>
            </ToolsPanel>
        </InspectorControls>
    );
};
