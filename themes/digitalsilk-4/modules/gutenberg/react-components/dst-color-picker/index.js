import { useRef, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
    ColorPalette,
    Button,
    Popover,
    Flex,
    ColorIndicator
} from '@wordpress/components';
import { useClickOutside } from './utilities';

// Digitalsilk Color Picker
export const DstColorPicker = (
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

    // Close the popover when clicking outside of it
    useClickOutside([popoverRef, buttonRef], () => {
        setIsOpen(false);
    });

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
            {isOpen && (
                <Popover
                    placement="left"
                    onFocusOutside={() => setIsOpen(false)}
                    anchor={buttonRef.current}
                    offset={35}
                    focusOnMount={false}
                    ref={popoverRef}
                >
                    <Flex style={{ padding: '16px', width: '260px' }}>
                        <ColorPalette
                            colors={themeColors}
                            value={value}
                            onChange={(color) => {
                                onChange(color);
                            }}
                        />
                    </Flex>
                </Popover>
            )}
        </>
    )
}