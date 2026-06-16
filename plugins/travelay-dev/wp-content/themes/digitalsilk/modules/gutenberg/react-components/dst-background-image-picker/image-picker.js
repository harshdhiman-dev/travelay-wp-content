import { useRef, useState } from '@wordpress/element';
import {
    Button,
    Popover,
    Flex
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { useClickOutside, createBackgroundImageObject } from './utilities';
import { renderBackgroundIndicators, BackgroundImagePopover } from './components';
import './editor.scss';

// Digitalsilk Color Picker
export const DstBackgroundImagePicker = (
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

    // Create states for media uploads from the popover.
    const [ mediaUploadMode, setMediaUploadMode ] = useState('add');
    const [ targetItemId, setTargetItemId ] = useState(null);

    // Close the popover when clicking outside of it
    useClickOutside([popoverRef, buttonRef], () => {
        setIsOpen(false);
    });

    // Check if we have a media on our first array item.
    const hasMedia = !!value?.[0]?.desktop?.media?.id;

    return (
        <>
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={(media) => {
                        const newItem = createBackgroundImageObject( media, media );

                        onChange([ newItem ]);
                        setIsOpen(true);
                    }}
                    onClose={() => {
                        setIsOpen(true);
                    }}
                    allowedTypes={ [ 'image', 'video' ] }
                    // value={ mediaId }
                    render={ ( { open } ) => (
                        <>
                            <Button
                                __next40pxDefaultSize
                                ref={buttonRef}
                                onClick={
                                    () => {
                                        if ( hasMedia ) {
                                            setIsOpen( ! isOpen );
                                        } else {
                                            open();
                                        }
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
                        </>
                    ) }
                />
            </MediaUploadCheck>
            <MediaUploadCheck>
                <MediaUpload
                    allowedTypes={ [ 'image', 'video' ] }
                    value={ null }
                    onSelect={(media) => {
                        const existingValue = value || [];
                    
                        if (mediaUploadMode === 'replace_mobile' && targetItemId) {
                            const newValue = existingValue.map((item) => {
                                if (item.id === targetItemId) {
                                    return {
                                        ...item,
                                        mobile: {
                                            ...item.mobile,
                                            media,
                                        },
                                    };
                                }
                                return item;
                            });
                            onChange(newValue);
                            setMediaUploadMode('add');
                            setTargetItemId(null);
                            setIsOpen(true);
                            return;
                        }
                    
                        // Default "add" behavior
                        const newItem = createBackgroundImageObject(media, media);
                        const newValue = [...existingValue, newItem];
                        onChange(newValue);
                        setIsOpen(true);
                    }}
                    onClose={() => {
                        setIsOpen(true);
                    }}
                    render={ ( { open } ) => {
                        mediaUploadRef.current = open;
                        return null;
                    } }
                />
            </MediaUploadCheck>
            {
                isOpen && Array.isArray( value ) && value.length > 0 && (
                    <Popover
                        placement="left"
                        onFocusOutside={() => setIsOpen(false)}
                        anchor={buttonRef.current}
                        offset={35}
                        focusOnMount={false}
                        ref={popoverRef}
                    >
                        <BackgroundImagePopover
                            value={ value }
                            onChange={ onChange }
                            openMediaUploader={ () => {
                                setIsOpen( false );
                                mediaUploadRef.current?.();
                            } }
                            setMediaUploadMode={ setMediaUploadMode }
                            setTargetItemId={ setTargetItemId }
                        />
                    </Popover>
                )
            }
        </>
    )
}
