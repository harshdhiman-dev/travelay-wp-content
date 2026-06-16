import { __ } from '@wordpress/i18n';
import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem,
    BaseControl,
    ColorPalette,
    FontSizePicker,
    SelectControl,
    RangeControl,
    __experimentalUnitControl as UnitControl,
    __experimentalBorderBoxControl as BorderBoxControl,
} from '@wordpress/components';
import { useSettings } from '@wordpress/block-editor';

/**
 * Updates the tab styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 * @param {string}   key           - The key of the style to update.
 * @param {string}   value         - The value to set for the style.
 */
const updateTabStyles = ( attributes, setAttributes, key, value ) => {
    const { tabStyles } = attributes;
    const newTabStyles = { ...tabStyles, [ key ]: value };
    setAttributes( { tabStyles: newTabStyles } );
};

/**
 * Removes a single tab style.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 * @param {string}   key           - The key of the style to remove.
 */
const removeTabStyle = ( attributes, setAttributes, key ) => {
    const { tabStyles } = attributes;
    const newTabStyles = { ...tabStyles };
    if ( newTabStyles[ key ] !== undefined ) {
        delete newTabStyles[ key ];
        setAttributes( { tabStyles: newTabStyles } );
    }
};

/**
 * Bulk removes tab styles.
 *
 * @param {Object}        attributes     - Block attributes.
 * @param {Function}      setAttributes  - Function to set attributes.
 * @param {Array<string>} stylesToRemove - Array of style keys to remove.
 */
const removeTabStyles = ( attributes, setAttributes, stylesToRemove ) => {
    const { tabStyles } = attributes;
    const newTabStyles = { ...tabStyles };

    stylesToRemove.forEach( ( property ) => {
        if ( newTabStyles[ property ] !== undefined ) {
            delete newTabStyles[ property ];
        }
    } );

    setAttributes( { tabStyles: newTabStyles } );
};

/**
 * Resets all tab styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllTabsStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'tabsGap',
        'tabsMinHeight',
        'tabsBorder',
        'tabsColor',
        'tabsBgColor',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

/**
 * Resets all label styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllLabelsStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'labelsPadding',
        'labelsGap',
        'labelsMaxWidth',
        'labelsBorder',
        'labelsFontSize',
        'labelsColor',
        'labelsBgColor',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

/**
 * Resets all arrow styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllArrowStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'arrowsSize',
        'arrowsPadding',
        'arrowsBorderRadius',
        'arrowsColor',
        'arrowsBgColor',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

/**
 * Resets all arrow Hover styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllArrowHoverStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'arrowsHoverColor',
        'arrowsHoverBgColor',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

/**
 * Resets all label active styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllActiveLabelsStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'labelsBorderActive',
        'labelsColorActive',
        'labelsBgColorActive',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

/**
 * Resets all Animation styles.
 *
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Function to set attributes.
 */
const resetAllAnimationStyles = ( attributes, setAttributes ) => {
    const stylesToRemove = [
        'tabsAnimationDuration',
        'tabsAnimationPositionStart',
        'tabsAnimationTimingFunction',
    ];
    removeTabStyles( attributes, setAttributes, stylesToRemove );
};

export const TabStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles } = attributes;

    // Extract colors and font sizes from theme settings
    const [ colors ] = useSettings( 'color.palette' );

    return (
        <ToolsPanel
            label={ __( 'Tab Styles' ) }
            resetAll={ () => resetAllTabsStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsGap }
                label={ __( 'Gap' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsGap' ) }
            >
                <UnitControl
                    label={ __( 'Gap' ) }
                    value={ tabStyles?.tabsGap || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsGap', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsMinHeight }
                label={ __( 'Min Height' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsMinHeight' ) }
            >
                <UnitControl
                    label={ __( 'Min Height' ) }
                    value={ tabStyles?.tabsMinHeight || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsMinHeight', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsBorder }
                label={ __( 'Border' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsBorder' ) }
            >
                <BorderBoxControl
                    colors={ colors }
                    label={ __( 'Border' ) }
                    value={ tabStyles?.tabsBorder || {} }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsBorder', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsColor }
                label={ __( 'Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.tabsColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsBgColor }
                label={ __( 'Background Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsBgColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Background Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.tabsBgColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsBgColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export const LabelStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles, className } = attributes;

    // Extract colors and font sizes from theme settings
    const [ colors, fontSizes ] = useSettings( 'color.palette', 'typography.fontSizes' );

    return (
        <ToolsPanel
            label={ __( 'Label Styles' ) }
            resetAll={ () => resetAllLabelsStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsFontSize }
                label={ __( 'Font Size' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsFontSize' ) }
            >
                <FontSizePicker
                    fontSizes={ fontSizes }
                    label={ __( 'Font Size' ) }
                    value={ tabStyles?.labelsFontSize || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsFontSize', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsPadding }
                label={ __( 'Padding' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsPadding' ) }
            >
                <UnitControl
                    label={ __( 'Padding' ) }
                    value={ tabStyles?.labelsPadding || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsPadding', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsGap }
                label={ __( 'Gap' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsGap' ) }
            >
                <UnitControl
                    label={ __( 'Gap' ) }
                    value={ tabStyles?.labelsGap || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsGap', value ) }
                />
            </ToolsPanelItem>
            {
                className && className === 'is-style-vertical' && (
                    <ToolsPanelItem
                        hasValue={ () => !!tabStyles?.labelsMaxWidth }
                        label={ __( 'Max Width' ) }
                        onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsMaxWidth' ) }
                    >
                        <UnitControl
                            label={ __( 'Max Width' ) }
                            value={ tabStyles?.labelsMaxWidth || '' }
                            onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsMaxWidth', value ) }
                        />
                    </ToolsPanelItem>
                )
            }
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsBorder }
                label={ __( 'Border' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsBorder' ) }
            >
                <BorderBoxControl
                    colors={ colors }
                    label={ __( 'Border' ) }
                    value={ tabStyles?.labelsBorder || {} }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsBorder', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsColor }
                label={ __( 'Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.labelsColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsColor', value ) }
                    />
                </BaseControl>
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsBgColor }
                label={ __( 'Background' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsBgColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Background' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.labelsBgColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsBgColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export const LabelActiveStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles } = attributes;

    // Extract colors and font sizes from theme settings
    const [ colors ] = useSettings( 'color.palette', 'typography.fontSizes' );

    return (
        <ToolsPanel
            label={ __( 'Label Active Styles' ) }
            resetAll={ () => resetAllActiveLabelsStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsBorderActive }
                label={ __( 'Border' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsBorderActive' ) }
            >
                <BorderBoxControl
                    colors={ colors }
                    label={ __( 'Border Active' ) }
                    value={ tabStyles?.labelsBorderActive || {} }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsBorderActive', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsColorActive }
                label={ __( 'Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsColorActive' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Color Active' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.labelsColorActive || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsColorActive', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.labelsBgColorActive }
                label={ __( 'Background' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'labelsBgColorActive' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Background Active' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.labelsBgColorActive || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'labelsBgColorActive', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export const ArrowsStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles, tabArrows } = attributes;

    // Extract colors from theme settings
    const [ colors ] = useSettings( 'color.palette' );

    if ( ! tabArrows ) {
        return;
    }

    return (
        <ToolsPanel
            label={ __( 'Arrows Styles' ) }
            resetAll={ () => resetAllArrowStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsSize }
                label={ __( 'Size' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsSize' ) }
            >
                <UnitControl
                    label={ __( 'Size' ) }
                    value={ tabStyles?.arrowsSize || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsSize', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsPadding }
                label={ __( 'Padding' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsPadding' ) }
            >
                <UnitControl
                    label={ __( 'Padding' ) }
                    value={ tabStyles?.arrowsPadding || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsPadding', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsBorderRadius }
                label={ __( 'Border Radius' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsBorderRadius' ) }
            >
                <UnitControl
                    label={ __( 'Border Radius' ) }
                    value={ tabStyles?.arrowsBorderRadius || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsBorderRadius', value ) }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsColor }
                label={ __( 'Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Arrows Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.arrowsColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsBgColor }
                label={ __( 'Background Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsBgColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Arrows Background Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.arrowsBgColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsBgColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export const ArrowsHoverStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles, tabArrows } = attributes;

    // Extract colors from theme settings
    const [ colors ] = useSettings( 'color.palette' );

    if ( ! tabArrows ) {
        return;
    }

    return (
        <ToolsPanel
            label={ __( 'Arrows Hover Styles' ) }
            resetAll={ () => resetAllArrowHoverStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsHoverColor }
                label={ __( 'Hover Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsHoverColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Arrows Hover Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.arrowsHoverColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsHoverColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.arrowsHoverBgColor }
                label={ __( 'Hover Background Color' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'arrowsHoverBgColor' ) }
            >
                <BaseControl
                    id={ null }
                    label={ __( 'Arrows Hover Background Color' ) }
                >
                    <ColorPalette
                        colors={ colors }
                        value={ tabStyles?.arrowsHoverBgColor || '' }
                        onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'arrowsHoverBgColor', value ) }
                        disableCustomColors
                    />
                </BaseControl>
            </ToolsPanelItem>
        </ToolsPanel>
    );
};

export const AnimationsStylesPanel = ( { attributes, setAttributes } ) => {
    const { tabStyles } = attributes;

    return (
        <ToolsPanel
            label={ __( 'Animation Styles' ) }
            resetAll={ () => resetAllAnimationStyles( attributes, setAttributes ) }
        >
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsAnimationDuration }
                label={ __( 'Duration' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsAnimationDuration' ) }
            >
                <RangeControl
                    label={ __( 'Animation Duration' ) }
                    help={ __( 'Set duration speed ( in seconds )' ) }
                    value={ tabStyles?.tabsAnimationDuration || 0.3 }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsAnimationDuration', value ) }
                    min={ 0 }
                    max={ 3 }
                    step={ 0.1 }
                    beforeIcon='clock'
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsAnimationPositionStart }
                label={ __( 'Direction' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsAnimationPositionStart' ) }
            >
                <SelectControl
                    label={ __( 'Animation Direction' ) }
                    value={ tabStyles?.tabsAnimationPositionStart || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsAnimationPositionStart', value ) }
                    options={[
                        {
                            label: __( 'Default' ),
                            value: ''
                        },
                        {
                            label: __( 'Left Subtle' ),
                            value: '-25px, 0'
                        },
                        {
                            label: __( 'Left' ),
                            value: '-100px, 0'
                        },
                        {
                            label: __( 'Right Subtle' ),
                            value: '25px, 0'
                        },
                        {
                            label: __( 'Right' ),
                            value: '100px, 0'
                        },
                        {
                            label: __( 'Top Subtle' ),
                            value: '0, -25px'
                        },
                        {
                            label: __( 'Top' ),
                            value: '0, -100px'
                        },
                        {
                            label: __( 'Bottom Subtle' ),
                            value: '0, 25px'
                        },
                        {
                            label: __( 'Bottom' ),
                            value: '0, 100px'
                        },
                        {
                            label: __( 'Top Left Subtle' ),
                            value: '-25px, -25px'
                        },
                        {
                            label: __( 'Top Left' ),
                            value: '-100px, -100px'
                        },
                        {
                            label: __( 'Top Right Subtle' ),
                            value: '25px, -25px'
                        },
                        {
                            label: __( 'Top Right' ),
                            value: '100px, -100px'
                        },
                        {
                            label: __( 'Bottom Left Subtle' ),
                            value: '-25px, 25px'
                        },
                        {
                            label: __( 'Bottom Left' ),
                            value: '-100px, 100px'
                        },
                        {
                            label: __( 'Bottom Right Subtle' ),
                            value: '25px, 25px'
                        },
                        {
                            label: __( 'Bottom Right' ),
                            value: '100px, 100px'
                        },
                        {
                            label: __( 'None' ),
                            value: '0, 0'
                        }
                    ]}
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                hasValue={ () => !!tabStyles?.tabsAnimationTimingFunction }
                label={ __( 'Type' ) }
                onDeselect={ () => removeTabStyle( attributes, setAttributes, 'tabsAnimationTimingFunction' ) }
            >
                <SelectControl
                    label={ __( 'Animation Type' ) }
                    value={ tabStyles?.tabsAnimationTimingFunction || '' }
                    onChange={ ( value ) => updateTabStyles( attributes, setAttributes, 'tabsAnimationTimingFunction', value ) }
                    options={[
                        {
                            label: __( 'Default' ),
                            value: ''
                        },
                        {
                            label: __( 'Elastic' ),
                            value: 'linear(0 0%, 0.22 2.1%, 0.86 6.5%, 1.11 8.6%, 1.3 10.7%, 1.35 11.8%, 1.37 12.9%, 1.37 13.7%, 1.36 14.5%, 1.32 16.2%, 1.03 21.8%, 0.94 24%, 0.89 25.9%, 0.88 26.85%, 0.87 27.8%, 0.87 29.25%, 0.88 30.7%, 0.91 32.4%, 0.98 36.4%, 1.01 38.3%, 1.04 40.5%, 1.05 42.7%, 1.05 44.1%, 1.04 45.7%, 1 53.3%, 0.99 55.4%, 0.98 57.5%, 0.99 60.7%, 1 68.1%, 1.01 72.2%, 1 86.7%, 1 100%)'
                        },
                        {
                            label: __( 'Bounce' ),
                            value: 'linear(0 0%, 0 2.27%, 0.02 4.53%, 0.04 6.8%, 0.06 9.07%, 0.1 11.33%, 0.14 13.6%, 0.25 18.15%, 0.39 22.7%, 0.56 27.25%, 0.77 31.8%, 1 36.35%, 0.89 40.9%, 0.85 43.18%, 0.81 45.45%, 0.79 47.72%, 0.77 50%, 0.75 52.27%, 0.75 54.55%, 0.75 56.82%, 0.77 59.1%, 0.79 61.38%, 0.81 63.65%, 0.85 65.93%, 0.89 68.2%, 1 72.7%, 0.97 74.98%, 0.95 77.25%, 0.94 79.53%, 0.94 81.8%, 0.94 84.08%, 0.95 86.35%, 0.97 88.63%, 1 90.9%, 0.99 93.18%, 0.98 95.45%, 0.99 97.73%, 1 100%)'
                        },
                        {
                            label: __( 'Out Back' ),
                            value: 'cubic-bezier(0.18, 0.89, 0.32, 1.28)'
                        },
                        {
                            label: __( 'In Cubic' ),
                            value: 'cubic-bezier(0.55, 0.06, 0.68, 0.19)'
                        },
                    ]}
                />
            </ToolsPanelItem>
        </ToolsPanel>
    );
};
