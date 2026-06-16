import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
    ColorPalette,
    Button,
    Popover,
    Flex,
    ColorIndicator,
    GradientPicker,
    PanelBody,
	TabPanel,
    TextControl
} from '@wordpress/components';
import { ClientLockControl } from '../client-lock';

// Digitalsilk Color Picker
export const DstBackgroundColorPicker = (
    {
        label,
        value,
        onChange,
    }
) => {
    const [ isOpen, setIsOpen ] = useState(false);
    const buttonRef = useRef(null);
    const popoverRef = useRef();

    // Get theme color settings.
    const themeColors = useSelect('core/block-editor').getSettings().colors;

    // Check if the value is a gradient
    const isGradient = (valueToCheck) => {
        if ( typeof valueToCheck !== 'string' ) {
            return false;
        }
        return /^(repeating-)?(linear|radial|conic)-gradient/.test(valueToCheck.trim());
    };
    
    // Get the initial tab name based on the value
    const getInitialTabName = (currentTabValue) => {
        return isGradient(currentTabValue) ? 'gradient' : 'color';
    };

    return (
        <>
            <Button
                __next40pxDefaultSize
                ref={buttonRef}
                onClick={
                    () => {setIsOpen(!isOpen)}
                }
                isPressed={isOpen}
                variant='secondary'
                onMouseDown={ (e) => e.preventDefault() }
                style={{
                    '--wp-admin-theme-color': '#d8d8d8',
                    color: '#1e1e1e',
                    '--wp-components-color-foreground': isOpen ? '#f0f0f0' : '#1e1e1e',
                    marginBottom: '2rem',
                    width: '100%',
                    textAlign: 'center',
                }}
            >
                <Flex align='center' justify='start' gap={2}>
                    <ColorIndicator colorValue={value} />
                    {label}
                </Flex>
            </Button>
            {
                isOpen && (
                    <Popover
                        placement="left"
                        onClickOutside={() => setIsOpen(false)}
                        anchor={buttonRef.current}
                        offset={35}
                        focusOnMount={false}
                        ref={popoverRef}
                    >
                        <div style={{ width: '260px' }}>
                            <TabPanel
                                initialTabName={ getInitialTabName(value) }
                                tabs={
                                    [
                                        { name: 'color', title: 'Color' },
                                        { name: 'gradient', title: 'Gradient' },
                                    ]
                                }
                            >
                                {
                                    (tab) => (
                                        <PanelBody>
                                            {tab.name === 'color' && (
                                                <ColorPalette
                                                    colors={themeColors}
                                                    value={value && !isGradient(value) ? value : ''}
                                                    onChange={(color) => {
                                                        onChange(color);
                                                    }}
                                                />
                                            )}

                                            {tab.name === 'gradient' && (
                                                <>
                                                    <GradientPicker
                                                        {...(isGradient(value) ? { value } : {})}
                                                        onChange={(gradient) => {
                                                            onChange(gradient);
                                                        }}
                                                    />
                                                    <ClientLockControl>
                                                        <TextControl
                                                            __next40pxDefaultSize
                                                            __nextHasNoMarginBottom
                                                            onChange={(gradient) => {
                                                                onChange(gradient);
                                                            }}
                                                            placeholder={ __( 'Linear and radial gradients only' ) }
                                                            value={ value || '' }
                                                        />
                                                    </ClientLockControl>
                                                </>
                                            )}
                                        </PanelBody>
                                    )
                                }
                            </TabPanel>
                        </div>
                    </Popover>
                )
            }
        </>
    )
}