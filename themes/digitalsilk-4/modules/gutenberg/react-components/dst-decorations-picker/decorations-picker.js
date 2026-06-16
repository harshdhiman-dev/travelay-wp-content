import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Popover,
    Flex,
    DropdownMenu,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { createDecorationsObject } from './utilities';
import { renderBackgroundIndicators, DecorationsPopover } from './components';
import { v4 as uuidv4 } from 'uuid';
import {
    image,
    brush
} from '@wordpress/icons';
import './editor.scss';

// Digitalsilk Color Picker
export const DstDecorationsPicker = (
    {
        label,
        value,
        onChange,
    }
) => {
    const [ isOpen, setIsOpen ] = useState(false);
    const buttonRef = useRef(null);
    const popoverRef = useRef();
    const mediaUploadRef = useRef();

    // Check if we have a media on our first array item.
    const hasMedia = !!value?.[0]?.media?.id || value?.[0]?.type === 'custom';

    return (
        <>
            <MediaUploadCheck>
                <MediaUpload
                    allowedTypes={ [ 'image' ] }
                    value={ null }
                    onSelect={ ( media ) => {
                        const newItem = createDecorationsObject(
                            'image',
                            {
                                media,
                                id: uuidv4(),
                            }
                        );
                        onChange([ ...value, newItem ]);
                        setIsOpen( true );
                    } }
                    render={ ( { open } ) => {
                        mediaUploadRef.current = open;
                        return null;
                    } }
                />
            </MediaUploadCheck>
            {
                ! hasMedia && (
                    <DropdownMenu
                        icon={ () => (
                            <Flex align='center' justify='start' gap={2}>
                                { renderBackgroundIndicators( value ) }
                                { label }
                            </Flex>
                        ) }
                        className='dst-decorations-dropdown'
                        toggleProps={
                            {
                                style: {
                                    '--wp-admin-theme-color': '#d8d8d8',
                                    color: '#1e1e1e',
                                    '--wp-components-color-foreground': isOpen ? '#f0f0f0' : '#1e1e1e',
                                    width: '100%',
                                    textAlign: 'center',
                                    padding: '6px 12px',
                                },
                            }
                        }
                        popoverProps={
                            {
                                offset: 0,
                                variant: 'toolbar',
                                className: 'dst-decorations-popover'
                            }
                        }
                        label={__('Add Decoration')}
                        controls={ [
                            {
                                title: __( 'Add Image' ),
                                icon: image,
                                onClick: () => mediaUploadRef.current?.(),
                            },
                            {
                                title: __( 'Add Custom' ),
                                icon: brush,
                                onClick: () => {
                                    const newItem = createDecorationsObject( 'custom', { id: uuidv4() } );
                                    onChange([ newItem ]);
                                    setIsOpen( true );
                                }
                            },
                        ] }
                    />
                )
            }
            {
                hasMedia && (
                    <Button
                        __next40pxDefaultSize
                        ref={buttonRef}
                        onClick={
                            () => {
                                setIsOpen( ! isOpen );
                            }
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
                            { renderBackgroundIndicators( value ) }
                            { label }
                        </Flex>
                    </Button>
                )
            }
            {
                isOpen && Array.isArray( value ) && value.length > 0 && (
                    <Popover
                        placement="left"
                        onFocusOutside={() => setIsOpen(false)}
                        anchor={buttonRef.current}
                        offset={35}
                        focusOnMount={false}
                        ref={popoverRef}
                        shift={true}
                    >
                        <DecorationsPopover
                            value={ value }
                            onChange={ onChange }
                            openMediaUploader={ () => {
                                setIsOpen( false );
                                mediaUploadRef.current?.();
                            } }
                        />
                    </Popover>
                )
            }
        </>
    )
}
