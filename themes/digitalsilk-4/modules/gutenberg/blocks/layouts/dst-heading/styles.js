import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
    BaseControl,
    ColorPalette,
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    CheckboxControl
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export const HeadingInspectorStyles = ({ blockProps }) => {
    const { attributes, setAttributes } = blockProps;
    const { pretitle_color, subtitle_color, title_styles, disableModuleMargins } = attributes;

    // Get theme color settings.
    const themeColors = useSelect('core/block-editor').getSettings().colors;

    // Function to change heading color.
    const changeHeadingColor = (color) => {
        setAttributes({
            title_styles: {
                ...title_styles,
                color
            }
        });
    };

    // Function to reset all style attributes.
    const resetColorStyles = () => {
        setAttributes({
            title_styles: {
                ...title_styles,
                color: null
            },
            pretitle_color: undefined,
            subtitle_color: undefined
        });
    };

    // Function to reset custom style attributes
    const resetCustomStyles = () => {
        setAttributes({
            disableModuleMargins: false
        });
    };

    return (
        <InspectorControls group="styles">
            <ToolsPanel label={__('Text Color')} resetAll={resetColorStyles}>
                <ToolsPanelItem
                    hasValue={() => !!pretitle_color}
                    label={__('Pretitle')}
                    onDeselect={() => setAttributes({ pretitle_color: undefined })}
                >
                    <BaseControl
                        __nextHasNoMarginBottom
                        id={null}
                        label={__('Pretitle')}
                    >
                        <ColorPalette
                            colors={themeColors}
                            value={pretitle_color}
                            onChange={(color) => setAttributes({ pretitle_color: color })}
                        />
                    </BaseControl>
                </ToolsPanelItem>

                <ToolsPanelItem
                    hasValue={() => !!title_styles?.color}
                    label={__('Title')}
                    onDeselect={() =>
                        setAttributes({
                            title_styles: {
                                ...title_styles,
                                color: null
                            }
                        })
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
                    onDeselect={() => setAttributes({ subtitle_color: undefined })}
                >
                    <BaseControl id={null} label={__('Subtitle')}>
                        <ColorPalette
                            colors={themeColors}
                            value={subtitle_color}
                            onChange={(color) => setAttributes({ subtitle_color: color })}
                        />
                    </BaseControl>
                </ToolsPanelItem>
            </ToolsPanel>

            <ToolsPanel label={__('Custom Styles')} resetAll={resetCustomStyles}>
                <ToolsPanelItem
                    hasValue={() => disableModuleMargins === true}
                    label={__('Margin Settings')}
                    onDeselect={() => setAttributes({ disableModuleMargins: false })}
                >
                    <CheckboxControl
                        label={__('Disable module margins')}
                        checked={disableModuleMargins}
                        onChange={(isChecked) => setAttributes({ disableModuleMargins: isChecked })}
                    />
                </ToolsPanelItem>
            </ToolsPanel>
        </InspectorControls>
    );
};
