import { useRef, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck, RichText, BlockControls } from '@wordpress/block-editor';
import {
    Placeholder,
    Button,
    ToolbarGroup,
    ToolbarButton,
    Icon,
    Modal,
    ToggleControl,
    SelectControl,
    Flex
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import { image, mobile, media, captureVideo, video, reusableBlock, trash, cog } from '@wordpress/icons';
import { buildMediaPayload } from './utilities';

/**
 * Image Component.
 *
 * Renders an image with responsive `<picture>` markup, fallback logic, and accessibility attributes.
 *
 * @param {Object}  props                        Component props.
 * @param {Object}  props.imageData              Image data object (should include sizes, url, and alt).
 * @param {Object}  props.primaryImageMobileData Mobile image data object (should include sizes, url, and alt).
 * @param {string}  props.imageSize              Selected image size (e.g., 'thumbnail', 'medium', 'large', 'full').
 * @param {string}  [props.className]            Optional className for styling.
 * @param {boolean} [props.pictureMarkup]        Whether to use picture element markup.
 * @return {JSX.Element|null} Rendered image component or null if no image available.
 */
export const Image = (
    {
        imageData,
        primaryImageMobileData,
        imageSize,
        className = '',
        pictureMarkup = false,
    }
) => {
    if (!imageData || !imageData.url) {
        return null; // Avoid rendering if no image data exists
    }

    const selectedSize = imageData.sizes?.[imageSize] || {};
    const imageUrl = selectedSize.url || imageData.url; // Use selected size URL or fallback to full size
    const imageWidth = selectedSize.width || undefined;
    const imageHeight = selectedSize.height || undefined;
    const imageAlt = imageData.alt || '';

    // Ensure sizes exist, otherwise use full-size URL
    const fullSize = imageData.sizes?.full || { url: imageData.url };
    const largeSize = imageData.sizes?.large || fullSize;
    const mediumSize = imageData.sizes?.medium || largeSize;

    // Default srcset (order: 1500px → 768px → 300px)
    let srcset1500 = fullSize.url;
    let srcset768 = largeSize.url;
    let srcset300 = mediumSize.url;

    // If selected image size is < 300px, use the same srcset for all
    if (selectedSize.width && selectedSize.width < 300) {
        srcset1500 = srcset768 = srcset300 = selectedSize.url;
    }

    // If primaryImageMobileData exists, replace the 300px breakpoint
    if (primaryImageMobileData && primaryImageMobileData.url) {
        srcset300 = primaryImageMobileData.url;
    }

    return (
        <>
            {pictureMarkup ? (
                <picture className="c-picture">
                    <source media="(min-width: 1500px)" srcSet={srcset1500} />
                    <source media="(min-width: 768px)" srcSet={srcset768} />
                    <source media="(min-width: 300px)" srcSet={srcset300} />
                    <img
                        src={imageUrl}
                        alt={imageAlt}
                        className="dst-media__src"
                        loading="lazy"
                    />
                </picture>
            ) : (
                <img
                    className={className}
                    src={imageUrl}
                    alt={imageAlt}
                    width={imageWidth}
                    height={imageHeight}
                />
            )}
        </>
    );
};

/**
 * MediaTypePlaceholder Component.
 *
 * @param {Object}   props                   Component props.
 * @param {Function} props.onSelectMediaType Function to handle media type selection.
 * @return {JSX.Element} Rendered MediaTypePlaceholder component.
 */
export const MediaTypePlaceholder = ( { onSelectMediaType } ) => {
    return (
        <Placeholder
            icon={ media }
            label={ __( 'Media type' ) }
            instructions={ __( 'What media type would you like to insert?' ) }
        >
            <Flex gap={4} style={{width: 'auto'}}>
                <Button
                    variant="tertiary"
                    onClick={ () => onSelectMediaType( 'image' ) }
                    icon={ image }
                >
                    { __( 'Image' ) }
                </Button>
                <Button
                    variant="tertiary"
                    onClick={ () => onSelectMediaType( 'videoLocal' ) }
                    icon={ captureVideo }
                >
                    { __( 'Local Video' ) }
                </Button>
                <Button
                    variant="tertiary"
                    onClick={ () => onSelectMediaType( 'videoExternal' ) }
                    icon={ video }
                >
                    { __( 'External Video' ) }
                </Button>
            </Flex>
        </Placeholder>
    );
};

/**
 * InlineSettings Component.
 *
 * @param {Object}    props                          Component props.
 * @param {Function}  props.onSelectMedia            Function to handle media selection.
 * @param {Function}  props.onRemoveMedia            Function to handle media removal.
 * @param {Array}     props.allowedTypes             Array of allowed media types.
 * @param {number}    props.mediaId                  ID of the selected media.
 * @param {boolean}   [props.showReplaceButton=true] Whether to show replace button.
 * @param {string}    [props.replaceTxt='Primary']   Text for the replace button.
 * @param {boolean}   [props.altIcons=false]         Whether to use alternate icons.
 * @param {ReactNode} props.children                 Content to display inside the modal.
 * @return {JSX.Element} Rendered InlineSettings component.
 */
export const InlineSettings = ( {
    onSelectMedia,
    onRemoveMedia,
    allowedTypes = [ 'image' ],
    mediaId,
    showReplaceButton = true,
    replaceTxt = __( 'Primary' ),
    altIcons = false,
    children
} ) => {
    const [ isModalOpened, setIsModalOpened ] = useState( false );

    const mediaIcon = altIcons ? 'paperclip' : video;
    const trashIcon = trash;
    const cogIcon = cog;

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    {
                        showReplaceButton ? (
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={ onSelectMedia }
                                    allowedTypes={ allowedTypes }
                                    value={ mediaId }
                                    render={ ( { open } ) => (
                                        <ToolbarButton
                                            label={ __( 'Replace' ) +  ' ' + replaceTxt }
                                            onClick={ open }
                                        >
                                            <Flex gap={2}>
                                                { replaceTxt } <Icon icon={mediaIcon} />
                                            </Flex>
                                        </ToolbarButton>
                                    ) }
                                />
                            </MediaUploadCheck>
                        ) : (
                            <ToolbarButton
                                label={ __( 'Replace' ) +  ' ' + replaceTxt }
                                onClick={ () => setIsModalOpened( true ) }
                            >
                                <Flex gap={2}>
                                    { replaceTxt } <Icon icon={mediaIcon} />
                                </Flex>
                            </ToolbarButton>
                        )
                    }
                    <ToolbarButton
                        icon={ cogIcon }
                        label={ replaceTxt + ' ' + __( 'Settings' ) }
                        onClick={ () => setIsModalOpened( true ) }
                    />
                    <ToolbarButton
                        icon={ trashIcon }
                        label={ __( 'Remove' ) +  ' ' + replaceTxt }
                        onClick={ onRemoveMedia }
                        isDestructive
                    />
                </ToolbarGroup>
            </BlockControls>

            { isModalOpened && (
                <Modal
                    title={ __( 'Settings' ) }
                    onRequestClose={ () => setIsModalOpened( false ) }
                    size='medium'
                >
                    { children }
                </Modal>
            ) }
        </>
    );
};

/**
 * InlineSettingsImage Component.
 *
 * @param {Object}    props                                Component props.
 * @param {Function}  props.onSelectMedia                  Function to handle media selection.
 * @param {Function}  props.onRemoveMedia                  Function to handle media removal.
 * @param {number}    props.mediaId                        ID of the selected media.
 * @param {Function}  props.handleSelectPrimaryMobileMedia Function to handle primary mobile media selection.
 * @param {Function}  props.handleRemovePrimaryMobileMedia Function to handle primary mobile media removal.
 * @param {Object}    props.primaryImageMobileData         Primary mobile image data object.
 * @param {ReactNode} props.children                       Content to display inside the modal.
 *
 * @return {JSX.Element} Rendered InlineSettings component.
 */
export const InlineSettingsImage = ( {
    onSelectMedia,
    onRemoveMedia,
    mediaId,
    handleSelectPrimaryMobileMedia,
    handleRemovePrimaryMobileMedia,
    primaryImageMobileData,
    children
} ) => {
    const [ isModalOpened, setIsModalOpened ] = useState( false );
    const [ isMobileModalOpened, setIsMobileModalOpened ] = useState( false );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={ onSelectMedia }
                            allowedTypes={ [ 'image' ] }
                            value={ mediaId }
                            render={ ( { open } ) => (
                                <ToolbarButton
                                    label={ __( 'Replace Image' ) }
                                    onClick={ open }
                                >
                                    <Flex gap={2}>
                                        { __( 'Main Image' ) } <Icon icon={media} />
                                    </Flex>
                                </ToolbarButton>
                            ) }
                        />
                    </MediaUploadCheck>
                    {
                        primaryImageMobileData?.id && (
                            <ToolbarButton
                                label={ __( 'Replace Mobile Media' ) }
                                onClick={ () => setIsMobileModalOpened( true ) }
                            >
                                <Flex gap={2}>
                                    { __( 'Mobile' ) } <Icon icon={mobile} />
                                </Flex>
                            </ToolbarButton>
                        )
                    }
                    <ToolbarButton
                        icon={ cog }
                        label={ __( 'Image Settings' ) }
                        onClick={ () => setIsModalOpened( true ) }
                    />
                    <ToolbarButton
                        icon={ trash }
                        label={ __( 'Remove Image' ) }
                        onClick={ onRemoveMedia }
                        isDestructive
                    />
                </ToolbarGroup>
            </BlockControls>

            { isModalOpened && (
                <Modal
                    title={ __( 'Settings' ) }
                    onRequestClose={ () => setIsModalOpened( false ) }
                    size='medium'
                >
                    { children }
                </Modal>
            ) }

            { isMobileModalOpened && (
                <Modal
                    title={ __( 'Mobile Image Settings' ) }
                    onRequestClose={ () => setIsMobileModalOpened( false ) }
                    size='medium'
                >
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={
                                (mobileMedia) => {
                                    handleSelectPrimaryMobileMedia(mobileMedia)
                                    setIsMobileModalOpened( false )
                                }
                            }
                            allowedTypes={ [ 'image' ] }
                            value={ primaryImageMobileData.id }
                            render={ ( { open } ) => (
                                <Placeholder
                                    style={{ border: 'none', boxShadow: 'none', padding: '0' }}
                                >
                                    { primaryImageMobileData.url ? (
                                        <>
                                            <button className='dst-image-preview-button' onClick={ open }>
                                                <img src={ primaryImageMobileData.url } alt={ __( 'Mobile Image' ) } style={{ maxWidth: '100%' }} />
                                            </button>
                                            <Flex gap={ 4 }>
                                                <Button onClick={ open } variant="secondary"><Icon icon={ reusableBlock } /> { __( 'Replace' ) }</Button>
                                                <Button
                                                    onClick={
                                                        () => {
                                                            handleRemovePrimaryMobileMedia()
                                                            setIsMobileModalOpened( false )
                                                        }
                                                    }
                                                    variant="secondary"
                                                    isDestructive
                                                >
                                                    <Icon icon={ trash } /> { __( 'Remove' ) }
                                                </Button>
                                            </Flex>
                                        </>
                                    ) : (
                                        <Button onClick={ open } variant="primary">{ __( 'Add Mobile Image' ) }</Button>
                                    ) }
                                </Placeholder>
                            ) }
                        />
                    </MediaUploadCheck>
                </Modal>
            ) }
        </>
    );
};

/**
 * Video Component.
 *
 * @param {Object}  props               Component props.
 * @param {Object}  props.videoData     Video data containing the video URL.
 * @param {Object}  props.videoPoster   Video poster object containing `url`.
 * @param {boolean} props.videoAutoplay Determines if the video should autoplay.
 * @param {boolean} props.videoControls Determines if video controls should be shown.
 * @return {JSX.Element} Rendered Video component.
 */
export const Video = ({ videoData, videoPoster, videoAutoplay, videoControls }) => {
    const videoRef = useRef(null);
    const [ isVideoPlaying, setIsVideoPlaying ] = useState( videoAutoplay );
    const [ isVideoMuted, setIsVideoMuted ] = useState( true );

    // Stop video when autoplay is turned off
    useEffect(() => {
        if (videoRef.current) {
            if (!videoAutoplay) {
                videoRef.current.pause();
                setIsVideoPlaying(false);
            } else {
                videoRef.current.play();
                setIsVideoPlaying(true);
            }
        }
    }, [videoAutoplay]);

    return (
        <>
            <video
                muted={ isVideoMuted }
                playsInline
                disablePictureInPicture
                data-poster={ videoPoster?.url }
                poster={ videoPoster?.url }
                autoPlay={ videoAutoplay }
                loop
                className="c-media__src c-video__src js-video-init lazy"
                ref={ videoRef }
            >
                <source data-src={ videoData?.url } src={ videoData?.url } type="video/mp4" />
            </video>
            {
                videoControls && (
                    <div className="c-video__controls">
                        <button
                            className={`c-video__btn btn-play ${isVideoPlaying ? 'is-playing' : ''}`}
                            title="Play/Pause"
                            onClick={() => {
                                if ( videoRef.current ) {
                                    if ( isVideoPlaying ) {
                                        videoRef.current.pause();
                                    } else {
                                        videoRef.current.play();
                                    }
                                    setIsVideoPlaying( ! isVideoPlaying );
                                }
                            }}
                        />
                        <button
                            className={`c-video__btn btn-mute ${isVideoMuted ? 'is-muted' : ''}`}
                            title="Mute"
                            onClick={() => setIsVideoMuted( ! isVideoMuted )}
                        />
                    </div>
                )
            }
        </>
    );
};

/**
 * SettingsVideo Component.
 *
 * @param {Object}   props                         Component props.
 * @param {Object}   props.videoPoster             Video poster object.
 * @param {Function} props.handleSelectVideoPoster Function to handle poster selection.
 * @param {Function} props.handleRemoveVideoPoster Function to handle poster removal.
 * @param {boolean}  props.videoAutoplay           Whether video autoplay is enabled.
 * @param {Function} props.setVideoAutoplay        Function to set video autoplay.
 * @param {boolean}  props.videoControls           Whether video controls are enabled.
 * @param {Function} props.setVideoControls        Function to set video controls.
 * @param {Function} props.onChange                Function to update block attributes.
 * @param {Object}   props.videoLocalData          Video local data.
 * @param {boolean}  props.isVideoPopup            Whether video should open in a popup.
 * @param {Function} props.handleVideoPopupChange  Function to handle video popup change.
 * @param {Object}   props.value                   Block values.
 * @return {JSX.Element} Rendered SettingsVideo component.
 */
export const SettingsVideo = ( {
    videoPoster,
    handleSelectVideoPoster,
    handleRemoveVideoPoster,
    videoAutoplay,
    setVideoAutoplay,
    videoControls,
    setVideoControls,
    onChange,
    videoLocalData,
    isVideoPopup,
    handleVideoPopupChange,
    value
} ) => {
    return (
        <>
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={ handleSelectVideoPoster }
                    allowedTypes={ [ 'image' ] }
                    value={ videoPoster.id }
                    render={ ( { open } ) => (
                        <Placeholder
                            icon="format-image"
                            label={ __( 'Poster Image' ) }
                            instructions={ __( 'Upload a poster image for the video.' ) }
                            style={{ border: 'none', boxShadow: 'none', padding: '0' }}
                        >
                            { videoPoster.url ? (
                                <>
                                    <button className='dst-image-preview-button' onClick={ open }>
                                        <img src={ videoPoster.url } alt={ __( 'Video Poster' ) } style={{ maxWidth: '100%' }} />
                                    </button>
                                    <Flex gap={ 4 }>
                                        <Button onClick={ open } variant="secondary"><Icon icon={ reusableBlock } /> { __( 'Replace' ) }</Button>
                                        <Button onClick={ handleRemoveVideoPoster } variant="secondary" isDestructive><Icon icon={ trash } /> { __( 'Remove' ) }</Button>
                                    </Flex>
                                </>
                            ) : (
                                <Button onClick={ open } variant="primary">{ __( 'Add Poster' ) }</Button>
                            ) }
                        </Placeholder>
                    ) }
                />
            </MediaUploadCheck>
            <Flex gap={ 4 } direction='column' style={{ marginTop: '1.23em' }}>
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={ __( 'Autoplay Video' ) }
                    checked={ videoAutoplay }
                    onChange={ ( newValue ) => {
                        setVideoAutoplay( newValue );
                        onChange(
                            buildMediaPayload( 'videoLocal', {
                                ...videoLocalData,
                                autoplay: newValue,
                            }, value )
                        );
                    } }
                />
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={ __( 'Show Video Controls' ) }
                    checked={ videoControls }
                    onChange={ ( newValue ) => {
                        setVideoControls( newValue );
                        onChange(
                            buildMediaPayload( 'videoLocal', {
                                ...videoLocalData,
                                controls: newValue,
                            }, value )
                        );
                    } }
                />
                <ToggleControl
                    __nextHasNoMarginBottom
                    label={ __( 'Open Video in a popup?' ) }
                    checked={ isVideoPopup }
                    onChange={ handleVideoPopupChange }
                />
            </Flex>
        </>
    );
};

/**
 * SettingsImage Component.
 *
 * @param {Object}   props                                Component props.
 * @param {Object}   props.imageSizes                     Image sizes object.
 * @param {string}   props.primaryImageSize               Selected image size.
 * @param {Array}    props.imageSizeOptions               Image size options.
 * @param {Function} props.setPrimaryImageSize            Function to set the primary image size.
 * @param {Object}   props.primaryImageData               Primary image data object.
 * @param {Object}   props.primaryImageMobileData         Primary mobile image data object.
 * @param {Function} props.handleSelectPrimaryMobileMedia Function to handle primary mobile media selection.
 * @param {Function} props.handleRemovePrimaryMobileMedia Function to handle primary mobile media removal.
 * @param {Object}   props.value                          Block values.
 * @param {Function} props.onChange                       Function to update block attributes.
 *
 * @return {JSX.Element} Rendered SettingsImage component.
 */
export const SettingsImage = (
    {
        imageSizes,
        primaryImageSize,
        imageSizeOptions,
        setPrimaryImageSize,
        primaryImageData,
        primaryImageMobileData,
        handleSelectPrimaryMobileMedia,
        handleRemovePrimaryMobileMedia,
        value,
        onChange,
    }
) => {
    return (
        <>
            {
                imageSizes && (
                    <>
                        <SelectControl
                            label={ __( 'Image Size' ) }
                            value={ primaryImageSize }
                            options={ imageSizeOptions }
                            onChange={ ( newSize ) => {
                                setPrimaryImageSize( newSize );
                                onChange(
                                    buildMediaPayload( 'imagePrimary', { ...primaryImageData, size: newSize }, value )
                                );
                            } }
                        />
                        <br />
                    </>
                )
            }
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={ handleSelectPrimaryMobileMedia }
                    allowedTypes={ [ 'image' ] }
                    value={ primaryImageMobileData.id }
                    render={ ( { open } ) => (
                        <Placeholder
                            icon={mobile}
                            label={ __( 'Mobile Image' ) }
                            instructions={ __( 'Upload an optional mobile image.' ) }
                            style={{ border: 'none', boxShadow: 'none', padding: '0' }}
                        >
                            { primaryImageMobileData.url ? (
                                <>
                                    <button className='dst-image-preview-button' onClick={ open }>
                                        <img src={ primaryImageMobileData.url } alt={ __( 'Mobile Image' ) } style={{ maxWidth: '100%' }} />
                                    </button>
                                    <Flex gap={ 4 }>
                                        <Button onClick={ open } variant="secondary"><Icon icon={ reusableBlock } /> { __( 'Replace' ) }</Button>
                                        <Button onClick={ handleRemovePrimaryMobileMedia } variant="secondary" isDestructive><Icon icon={ trash } /> { __( 'Remove' ) }</Button>
                                    </Flex>
                                </>
                            ) : (
                                <Button onClick={ open } variant="primary">{ __( 'Add Mobile Image' ) }</Button>
                            ) }
                        </Placeholder>
                    ) }
                />
            </MediaUploadCheck>
        </>
    )
}

/**
 * MediaDescription Component.
 *
 * @param {Object}   props                              Component props.
 * @param {boolean}  props.showMediaDescription         Should the media description be shown.
 * @param {string}   props.mediaDescription             The media description value.
 * @param {Function} props.handleMediaDescriptionChange Function to handle media description change.
 * @return {JSX.Element} Rendered MediaDescription component.
 */
export const MediaDescription = (
    {
        showMediaDescription,
        mediaDescription,
        handleMediaDescriptionChange,
    }
) => {
    return (
        <>
            {
                showMediaDescription && (
                    <figcaption className="dst-media__caption">
                        <RichText
                            tagName="p"
                            value={ mediaDescription }
                            onChange={ handleMediaDescriptionChange }
                            placeholder={ __( 'Enter media description...' ) }
                        />
                    </figcaption>
                )
            }
        </>
    );
}

/**
 * SimplePlaceholder Component.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.icon          Icon to display in the placeholder.
 * @param {string}   props.label         Label to display in the placeholder.
 * @param {string}   props.instructions  Instructions to display in the placeholder.
 * @param {Array}    props.allowedTypes  Array of allowed media types.
 * @param {string}   props.buttonText    Text to display on the button.
 * @param {Function} props.onSelectMedia Function to handle media selection.
 * @param {Object}   props.mediaData     Media data object.
 *
 * @return {JSX.Element} Rendered SimplePlaceholder component.
 */
export const SimplePlaceholder = (
    {
        icon,
        label,
        instructions,
        allowedTypes,
        buttonText,
        onSelectMedia,
        mediaData,
    }
) => {
    return (
        <Placeholder
            icon={icon}
            label={label}
            instructions={instructions}
        >
        <div>
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={ ( newMedia ) => onSelectMedia( newMedia ) }
                    allowedTypes={allowedTypes}
                    value={ mediaData.id }
                    render={ ( { open } ) => (
                        <Button onClick={ open } variant="primary">
                            { buttonText }
                        </Button>
                    ) }
                />
            </MediaUploadCheck>
        </div>
    </Placeholder>
    );
}
