import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Placeholder,
	PanelBody,
	ToggleControl,
	Icon,
	SelectControl,
	TextControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
    Spinner,
    Button,
} from '@wordpress/components';
import {
	getEmbedHTML,
	createMediaObject,
	buildMediaPayload,
    extractIframeSrc,
} from './utilities';
import {
    Image,
    MediaTypePlaceholder,
    SimplePlaceholder,
    InlineSettings,
    InlineSettingsImage,
    Video,
    SettingsVideo,
    SettingsImage,
    MediaDescription,
} from './components';
import { MediaStyles, getMediaStyles } from './styles';
// eslint-disable-next-line import/no-extraneous-dependencies
import { captureVideo, video, image } from '@wordpress/icons';
import { select } from '@wordpress/data';
import './style.scss';

/**
 * DstMedia Component.
 *
 * @param {DstMediaProps} props Component props.
 * @return {JSX.Element} Rendered DstMedia component.
 */
export const DstMedia = (
    {
        value = {},
        onChange,
        panelOpened = true,
        showToolbars = true,
        showInspectorControls = true,
    }
) => {
	// Primary media states.
	const [ primaryType, setPrimaryType ] = useState( value?.primaryType || 'image' );
    // Primary image states
	const [ primaryImageData, setPrimaryImageData ] = useState( value?.imagePrimary || {} );
	const [ primaryImageSize, setPrimaryImageSize ] = useState( value?.imagePrimary?.size || 'full' );
    // Primary image mobile states.
    const [ primaryImageMobileData, setPrimaryImageMobileData ] = useState( value?.imagePrimaryMobile || {} );
    // Local video states.
    const [ videoLocalData, setVideoLocalData ] = useState( value?.videoLocal || {} );
    const [ videoPoster, setVideoPoster ] = useState( value?.videoLocal?.poster || {} );
    const [ videoAutoplay, setVideoAutoplay ] = useState( value?.videoLocal?.autoplay || false );
    const [ videoControls, setVideoControls ] = useState( value?.videoLocal?.controls || false );
    // External video states.
	const [ externalVideoUrl, setExternalVideoUrl ] = useState( value?.videoExternal?.url || '' );
	const [ embedHtml, setEmbedHtml ] = useState( value?.videoExternal?.html || '' );
    const [ changeExternalVideoUrl, setChangeExternalVideoUrl ] = useState( value?.videoExternal?.url || '' );
    // Media description states.
    const [ showMediaDescription, setShowMediaDescription ] = useState( value?.mediaDescription?.show || false );
    const [ mediaDescription, setMediaDescription ] = useState( value?.mediaDescription?.text || '' );
    // Lazy load states
    const [ isLazyLoading, setIsLazyLoading ] = useState( value?.lazyLoad || false );
    // Video popup states
    const [ isVideoPopup, setIsVideoPopup ] = useState( value?.videoPopup || false );
    const [ VideoTag, setVideoTag ] = useState( value?.videoPopup ? 'a' : 'figure' );

	// Secondary image state.
	const [ secondaryImageData, setSecondaryImageData ] = useState( value?.imageSecondary || {} );
    const [ secondaryImageSize, setSecondaryImageSize ] = useState( value?.imageSecondary?.size || 'full' );
	const [ showSecondary, setShowSecondary ] = useState( value?.showImageSecondary || false );

	// WordPress image sizes.
	const imageSizes = select( 'core/editor' ).getEditorSettings().imageSizes;
    // Store the mapped image sizes in a constant
    const imageSizeOptions =  imageSizes ?
        imageSizes.map(
            (size) => (
                {
                    label: size.name,
                    value: size.slug,
                }
            )
        )
    : [];

    // Define state for focal points (desktop & mobile)
    const [ focalPointDesktop, setFocalPointDesktop ] = useState(value?.style?.desktop?.focalPoint || { x: 0.5, y: 0.5 });
    const [ focalPointMobile, setFocalPointMobile ] = useState(value?.style?.mobile?.focalPoint || { x: 0.5, y: 0.5 });

    // Generate class names based on styles
    // Get current media styles
    const { desktop, mobile } = getMediaStyles(value);
    const mediaClass = `media-${desktop.mediaFit} media-${desktop.mediaFit}-mobile r-${desktop.mediaRatio} r-${mobile.mediaRatio}-mobile`;

    // Generate inline styles for focal point
    const focalPointStyle = {
        '--c-media__position': `${focalPointDesktop.x * 100}% ${focalPointDesktop.y * 100}%`,
        '--c-media__position-mobile': `${focalPointMobile.x * 100}% ${focalPointMobile.y * 100}%`
    };

    /**
     * Updates focal point values in the state and syncs with block attributes.
     *
     * @param {string} device   Either "desktop" or "mobile".
     * @param {Object} newValue The new focal point values.
     */
    const handleFocalPointChange = (device, newValue) => {
        if (device === 'desktop') {
            setFocalPointDesktop(newValue);
        } else {
            setFocalPointMobile(newValue);
        }
    };

    // Sync focal point state changes with block attributes
    useEffect(
        () => {
            if ( focalPointDesktop && focalPointMobile ) {
                // Update both focal points in a single onChange call
                onChange(
                    buildMediaPayload('style', {
                        ...value.style,
                        desktop: {
                            ...value.style?.desktop,
                            focalPoint: focalPointDesktop,
                        },
                        mobile: {
                            ...value.style?.mobile,
                            focalPoint: focalPointMobile,
                        },
                    }, value)
                );
            }
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ focalPointDesktop, focalPointMobile ]
    );

	// Sync states with prop changes.
	useEffect(
        () => {
            setPrimaryType( value?.primaryType || '' );
            setPrimaryImageData( value?.imagePrimary || {} );
            setVideoLocalData( value?.videoLocal || {} );
            setPrimaryImageSize( value?.imagePrimary?.size || 'full' );
            setExternalVideoUrl( value?.videoExternal?.url || '' );
            setEmbedHtml( value?.videoExternal?.html || '' );
            setShowSecondary( value?.showImageSecondary || false );
            setShowMediaDescription( value?.mediaDescription?.show || false );
            setMediaDescription( value?.mediaDescription?.text || '' );
            setIsLazyLoading( value?.lazyLoad || false );
            setIsVideoPopup( value?.videoPopup || false );
            setPrimaryImageMobileData( value?.imagePrimaryMobile || {} );
            setFocalPointDesktop(value?.style?.desktop?.focalPoint || { x: 0.5, y: 0.5 });
            setFocalPointMobile(value?.style?.mobile?.focalPoint || { x: 0.5, y: 0.5 });
            if ( value?.isVideoPopup ) {
                setVideoTag( 'a' );
            }
	    },
        [ value ]
    );

    /**
     * Fetch embed HTML when external video URL changes.
     */
    useEffect( () => {
        if ( primaryType === 'videoExternal' && externalVideoUrl ) {
            getEmbedHTML( externalVideoUrl )
                .then( ( html ) => {
                    setEmbedHtml( html );

                    onChange(
                        buildMediaPayload( 'videoExternal', {
                            ...value.videoExternal,
                            html, // Save embed HTML
                        }, value )
                    );
                })
                .catch( () => setEmbedHtml( '' ));
        } else {
            setEmbedHtml( '' );
            onChange(
                buildMediaPayload( 'videoExternal', {
                    ...value.videoExternal,
                    html: '',
                }, value )
            );
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ primaryType, externalVideoUrl ] );

    /**
     * Styles for the toggle group.
     */
    const toggleGroupStyles = { display: 'flex', alignItems: 'center', gap: '0.25em', fontSize: '12px' };

	/**
	 * Handles updating the primary media type.
	 *
	 * @param {string} newType New media type: "image", "videoLocal", or "videoExternal".
	 */
    const handlePrimaryTypeChange = ( newType ) => {
        // Update states
        setPrimaryType( newType );
        setPrimaryImageData( {} );
        setPrimaryImageMobileData( {} );
        setVideoLocalData( {} );
        setVideoPoster( {} );
        setExternalVideoUrl( '' );
        setEmbedHtml( '' );
        setIsVideoPopup( false );

        // Trigger onChange with reset values
        onChange(
            buildMediaPayload('primaryType', newType, {
                ...value,
                imagePrimary: {},
                imagePrimaryMobile: {},
                videoLocal: {},
                videoExternal: {},
                videoPopup: false,
                style: {},
            })
        );
    };

	/**
	 * Handles selecting primary media when media is uploaded.
	 *
	 * @param {Object} media Selected media object.
	 */
	const handleSelectPrimaryMedia = ( media ) => {
		if ( ! media || ! media.url ) {
			return;
		}

		if ( primaryType === 'image' ) {
			const newImage = createMediaObject( media );

			setPrimaryImageData( newImage );
			onChange(
				buildMediaPayload( 'imagePrimary', newImage, value )
			);
		} else if ( primaryType === 'videoLocal' ) {
			const newVideo = createMediaObject( media );

			setVideoLocalData( newVideo );
			onChange(
				buildMediaPayload( 'videoLocal', newVideo, value )
			);
		}
	};

    /**
     * Handles selecting the primary mobile image when media is uploaded.
     *
     * @param {Object} media The selected media object.
     */
    const handleSelectPrimaryMobileMedia = ( media ) => {
        if ( ! media || ! media.url ) {
            return;
        }

        const newImage = createMediaObject( media );

        setPrimaryImageMobileData( newImage );
        onChange(
            buildMediaPayload( 'imagePrimaryMobile', newImage, value )
        );
    }

    /**
     * Handles removal of the primary mobile image.
     */
    const handleRemovePrimaryMobileMedia = () => {
        setPrimaryImageMobileData( {} );
        onChange(
            buildMediaPayload( 'imagePrimaryMobile', {}, value )
        );
    }

	/**
	 * Handles selecting a video poster when media is uploaded.
	 *
	 * @param {Object} media The selected media object.
	 */
	const handleSelectVideoPoster = ( media ) => {
		if ( media && media.url ) {
			const newPoster = {
				id: media.id,
				url: media.url,
			};

			setVideoPoster( newPoster );

			onChange(
				buildMediaPayload( 'videoLocal', {
					...videoLocalData,
					poster: newPoster,
				}, value )
			);
		}
	};

	/**
	 * Handles removal of the video poster.
	 */
	const handleRemoveVideoPoster = () => {
		setVideoPoster( {} );

		onChange(
			buildMediaPayload( 'videoLocal', {
				...videoLocalData,
				poster: {},
			}, value )
		);
	};

	/**
	 * Handles changes to the external video URL.
	 *
	 * @param {string} newUrl The external video URL.
	 */
	const handleExternalVideoChange = ( newUrl ) => {
        setExternalVideoUrl( newUrl );
        setChangeExternalVideoUrl( newUrl );

        onChange(
            buildMediaPayload( 'videoExternal', {
                url: newUrl,
                html: '', // Reset embedHtml when a new URL is entered
            }, value )
        );
	};

    /**
     * Handles removal of the external video.
     */
    const handleRemoveExternalVideo = () => {
        setExternalVideoUrl( '' );
        setEmbedHtml( '' );

        onChange(
            buildMediaPayload( 'videoExternal', {
                url: '',
                html: '',
            }, value )
        );
    }

	/**
	 * Handles removal of the primary media.
	 */
	const handleRemovePrimaryMedia = () => {
		if ( primaryType === 'image' ) {
			setPrimaryImageData( {} );
            setPrimaryImageMobileData( {} );
			onChange(
				buildMediaPayload(
                    'imagePrimary',
                    {},
                    {
                        ...value,
                        imagePrimaryMobile: {},
                        style: {},
                    }
                ),
			);
		} else if ( primaryType === 'videoLocal' ) {
			setVideoLocalData( {} );
			onChange(
				buildMediaPayload(
                    'videoLocal',
                    {},
                    {
                        ...value,
                        style: {},
                    }
                )
			);
		} else if ( primaryType === 'videoExternal' ) {
			setExternalVideoUrl( '' );
			onChange(
				buildMediaPayload(
                    'externalVideoUrl',
                    '',
                    {
                        ...value,
                        style: {},
                    }
                )
			);
		}
	};

    /**
     * Handles toggling the display of the secondary image.
     *
     * @param {boolean} newValue New toggle value.
     */
    const onToggleSecondary = ( newValue ) => {
        setShowSecondary( newValue );

        if ( ! newValue ) {
            // Clear secondary image data when toggled off
            setSecondaryImageData( {} );
            onChange(
                buildMediaPayload( 'imageSecondary', {}, {
                    ...value,
                    showImageSecondary: newValue,
               })
            );
        } else {
            onChange(
                buildMediaPayload( 'showImageSecondary', newValue, value )
            );
        }
    };

    /**
     * Handles selecting a secondary image when media is uploaded.
     *
     * @param {Object} media The selected media object.
     */
    const handleSecondaryImageUpload = ( media ) => {
        if ( media && media.url ) {
			const newImage = createMediaObject( media );

            // Set the new image data
            setSecondaryImageData( newImage );
            if ( onChange ) {
                onChange(
                    buildMediaPayload( 'imageSecondary', newImage, value )
                );
            }
        }
    };

	/**
	 * Handles removal of a secondary image.
	 */
	const handleSecondaryImageRemove = () => {
		setSecondaryImageData( {} );
		onChange(
			buildMediaPayload( 'imageSecondary', {}, value )
		);
	};

    /**
     * Handles toggling the display of the media description.
     *
     * @param {boolean} newValue New toggle value.
     */
    const handleToggleMediaDescription = ( newValue ) => {
        if ( ! newValue ) {
            // Set the media description to an empty string when toggled on
            setMediaDescription( '' );
        }
        setShowMediaDescription( newValue );
        onChange(
            buildMediaPayload( 'mediaDescription', {
                show: newValue,
                text: '',
            }, value )
        );
    }

    /**
     * Handles changes to the media description.
     *
     * @param {string} newValue The new media description.
     */
    const handleMediaDescriptionChange = ( newValue ) => {
        setMediaDescription( newValue );

        onChange(
            buildMediaPayload( 'mediaDescription', {
                show: showMediaDescription,
                text: newValue,
            }, value )
        );
    }

    /**
     * Handles changes to the lazy load toggle.
     *
     * @param {boolean} newValue The new lazy load value.
     */
    const handleLazyLoadChange = ( newValue ) => {
        setIsLazyLoading( newValue );
        onChange(
            buildMediaPayload( 'lazyLoad', newValue, value )
        );
    }

    /**
     * Handles changes to the video popup toggle.
     *
     * @param {boolean} newValue The new lazy load value.
     */
    const handleVideoPopupChange = ( newValue ) => {
        setIsVideoPopup( (prev) => {
            if (prev === newValue) {
                return prev; // Prevent unnecessary re-renders
            }
            return newValue;
        });

        setVideoTag(newValue ? 'a' : 'figure');

        onChange(
            buildMediaPayload('videoPopup', newValue, value)
        );
    };


	return (
		<>
            {
                showInspectorControls && (
                    <InspectorControls>
                        <PanelBody title={ __( 'Media Settings' ) } initialOpen={panelOpened}>
                            <ToggleGroupControl
                                __next40pxDefaultSize
                                __nextHasNoMarginBottom
                                isBlock
                                label={ __( 'Primary Media Type' ) }
                                onChange={ handlePrimaryTypeChange }
                                value={ primaryType }
                            >
                                <ToggleGroupControlOption
                                    label={
                                        <span style={toggleGroupStyles}>
                                            <Icon icon={ image } size={20} /> { __( 'Image' ) }
                                        </span>
                                    }
                                    value="image"
                                />
                                <ToggleGroupControlOption
                                    label={
                                        <span style={toggleGroupStyles}>
                                            <Icon icon={ captureVideo } /> { __( 'Local Video' ) }
                                        </span>
                                    }
                                    value="videoLocal"
                                />
                                <ToggleGroupControlOption
                                    label={
                                        <span style={toggleGroupStyles}>
                                            <Icon icon={ video } /> { __( 'External Video' ) }
                                        </span>
                                    }
                                    value="videoExternal"
                                />
                            </ToggleGroupControl>
                            {/* Media styles controls will now live inside the Media Settings panel */}
                            <MediaStyles
                                value={ value }
                                onChange={ onChange }
                                focalPointDesktop={ focalPointDesktop }
                                focalPointMobile={ focalPointMobile }
                                onFocalPointChange={ handleFocalPointChange }
                                panelOpened={ panelOpened }
                            />
	                        <hr />
	                        <ToggleControl
		                        __nextHasNoMarginBottom
		                        label={ __( 'Enable Lazy Load for Media' ) }
		                        checked={ isLazyLoading }
		                        onChange={ handleLazyLoadChange }
	                        />
	                        <ToggleControl
		                        __nextHasNoMarginBottom
		                        label={ __( 'Add Media Caption' ) }
		                        checked={ showMediaDescription }
		                        onChange={ handleToggleMediaDescription }
	                        />
	                        <ToggleControl
		                        __nextHasNoMarginBottom
		                        label={ __( 'Add Extra Image' ) }
		                        checked={ showSecondary }
		                        onChange={ onToggleSecondary }
	                        />
                        </PanelBody>
                    </InspectorControls>
                )
            }
            <div className="dst-media">
                <>
                    {
                        ! primaryType && (
                            <MediaTypePlaceholder onSelectMediaType={ handlePrimaryTypeChange } />
                        )
                    }
                    { primaryType === 'image' && (
                        primaryImageData && primaryImageData.url ? (
                            <figure className={`dst-media__primary is-image ${mediaClass}`} style={focalPointStyle}>
                                <Image
                                    imageData={ primaryImageData }
                                    imageSize={ primaryImageSize }
                                    pictureMarkup
                                    primaryImageMobileData={ primaryImageMobileData }
                                />
                                <MediaDescription
                                    showMediaDescription={ showMediaDescription }
                                    mediaDescription={ mediaDescription }
                                    handleMediaDescriptionChange={ handleMediaDescriptionChange }
                                />
                                {
                                    showToolbars && (
                                        <InlineSettingsImage
                                            onSelectMedia={ ( media ) => handleSelectPrimaryMedia( media ) }
                                            onRemoveMedia={ handleRemovePrimaryMedia }
                                            allowedTypes={ [ 'image' ] }
                                            mediaId={ primaryImageData.id }
                                            handleSelectPrimaryMobileMedia={ handleSelectPrimaryMobileMedia }
                                            handleRemovePrimaryMobileMedia={ handleRemovePrimaryMobileMedia }
                                            primaryImageMobileData={ primaryImageMobileData }
                                        >
                                            <SettingsImage
                                                primaryImageMobileData={ primaryImageMobileData }
                                                handleSelectPrimaryMobileMedia={ handleSelectPrimaryMobileMedia }
                                                handleRemovePrimaryMobileMedia={ handleRemovePrimaryMobileMedia }
                                                imageSizes={ imageSizes }
                                                imageSizeOptions={ imageSizeOptions }
                                                primaryImageData={ primaryImageData }
                                                setPrimaryImageData={ setPrimaryImageData }
                                                primaryImageSize={ primaryImageSize }
                                                setPrimaryImageSize={ setPrimaryImageSize }
                                                onChange={ onChange }
                                                value={ value }
                                            />
                                        </InlineSettingsImage>
                                    )
                                }
                            </figure>
                        ) : (
                            <>
                                <SimplePlaceholder
                                    icon='format-image'
                                    label={ __( 'Primary Image' ) }
                                    instructions={ __( 'Upload a primary image.' ) }
                                    allowedTypes={ [ 'image' ] }
                                    buttonText={ __( 'Upload Primary Image' ) }
                                    onSelectMedia={ handleSelectPrimaryMedia }
                                    mediaData={ primaryImageData }
                                />
                            </>
                        )
                    ) }
                    { primaryType === 'videoLocal' && (
                        videoLocalData && videoLocalData.url ? (
                            <>
                                <VideoTag
                                    className={`dst-media__primary is-video ${mediaClass} ${isVideoPopup ? 'is-popup' : ''}`}
                                    style={focalPointStyle}
                                    onClick={
                                        (e) => {
                                            e.preventDefault();
                                        }
                                    }
                                    {
                                        ...(
                                            isVideoPopup ? {
                                                'data-dimbox': `video${Math.floor(Math.random() * 10000)}`,
                                                'data-dimbox-type': 'video',
                                                href: videoLocalData.url,
                                            } : {

                                            }
                                        )
                                    }
                                >
                                    <div className="c-video">
                                        <div className={`c-video__wrap js-video-wrap ${ videoAutoplay ? 'is-video-autoplay' : '' }`}>
                                            <Video
                                                videoData={ videoLocalData }
                                                videoPoster={ videoPoster }
                                                videoAutoplay={ videoAutoplay }
                                                videoControls={ videoControls }
                                            />
                                        </div>
                                    </div>
                                    <MediaDescription
                                        showMediaDescription={ showMediaDescription }
                                        mediaDescription={ mediaDescription }
                                        handleMediaDescriptionChange={ handleMediaDescriptionChange }
                                    />
                                </VideoTag>
                                {
                                    showToolbars && (
                                        <InlineSettings
                                            onSelectMedia={ ( media ) => handleSelectPrimaryMedia( media ) }
                                            onRemoveMedia={ handleRemovePrimaryMedia }
                                            allowedTypes={ [ 'video' ] }
                                            replaceTxt={ __( 'Video' ) }
                                            mediaId={ videoLocalData.id }
                                        >
                                            <SettingsVideo
                                                videoPoster={ videoPoster }
                                                handleSelectVideoPoster={ handleSelectVideoPoster }
                                                handleRemoveVideoPoster={ handleRemoveVideoPoster }
                                                videoAutoplay={ videoAutoplay }
                                                setVideoAutoplay={ setVideoAutoplay }
                                                videoControls={ videoControls }
                                                setVideoControls={ setVideoControls }
                                                onChange={ onChange }
                                                videoLocalData={ videoLocalData }
                                                isVideoPopup={ isVideoPopup }
                                                handleVideoPopupChange={ handleVideoPopupChange }
                                                value={ value }
                                            />
                                        </InlineSettings>
                                    )
                                }
                            </>
                        ) : (
                            <>
                                <SimplePlaceholder
                                    icon='format-video'
                                    label={ __( 'Local Video' ) }
                                    instructions={ __( 'Upload a local video.' ) }
                                    allowedTypes={ [ 'video' ] }
                                    buttonText={ __( 'Upload Local Video' ) }
                                    onSelectMedia={ handleSelectPrimaryMedia }
                                    mediaData={ videoLocalData }
                                />
                            </>
                        )
                    ) }
                    { primaryType === 'videoExternal' && (
                        <>
                            {
                                externalVideoUrl && embedHtml ? (
                                    <>
                                    {
                                        <>
                                            <VideoTag
                                                className={`dst-media__primary is-video ${mediaClass} ${isVideoPopup ? 'is-popup' : ''}`}
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                }}
                                                {
                                                    ...(
                                                        isVideoPopup ? {
                                                            'data-dimbox': `video${Math.floor(Math.random() * 10000)}`,
                                                            'data-dimbox-type': 'iframe',
                                                            href: extractIframeSrc(embedHtml) || '#',
                                                        } : {}
                                                    )
                                                }
                                            >
                                                <div className="c-video">
                                                    <div
                                                        className='c-video__wrap'
                                                        dangerouslySetInnerHTML={ { __html: embedHtml } }
                                                    />
                                                </div>
                                                <MediaDescription
                                                    showMediaDescription={ showMediaDescription }
                                                    mediaDescription={ mediaDescription }
                                                    handleMediaDescriptionChange={ handleMediaDescriptionChange }
                                                />
                                            </VideoTag>
                                            {
                                                showToolbars && (
                                                    <InlineSettings
                                                        onRemoveMedia={ handleRemoveExternalVideo }
                                                        showReplaceButton={false}
                                                        replaceTxt={ __( 'Video' ) }
                                                    >
                                                        <>
                                                            <TextControl
                                                                __next40pxDefaultSize
                                                                __nextHasNoMarginBottom
                                                                label={ __( 'External Video URL' ) }
                                                                value={ changeExternalVideoUrl }
                                                                onChange={ (newUrl) => { setChangeExternalVideoUrl(newUrl) } }
                                                                placeholder='https://www.youtobe.com/watch?v=xxxxxxxx'
                                                            />
                                                            <Button
                                                                onClick={ () => { handleExternalVideoChange(changeExternalVideoUrl) } }
                                                                disabled={ changeExternalVideoUrl === externalVideoUrl }
                                                                variant='primary'
                                                                style={{ width: '100%', justifyContent: 'center', margin: '1em 0' }}
                                                            >
                                                                { __( 'Update' ) }
                                                            </Button>
                                                            <ToggleControl
                                                                __nextHasNoMarginBottom
                                                                label={ __( 'Open Video in a popup?' ) }
                                                                checked={ isVideoPopup }
                                                                onChange={ handleVideoPopupChange }
                                                            />
                                                        </>
                                                    </InlineSettings>
                                                )
                                            }
                                        </>
                                    }
                                    </>
                                ) : (
                                    <>
                                        {
                                            ! embedHtml && (
                                                <Placeholder
                                                    icon={video}
                                                    label={ __( 'External Video' ) }
                                                >
                                                        <TextControl
                                                            label={ __( 'External Video URL' ) }
                                                            value={ externalVideoUrl }
                                                            onChange={ handleExternalVideoChange }
                                                            placeholder='https://www.youtobe.com/watch?v=xxxxxxxx'
                                                            style={{minWidth: '250px'}}
                                                        />
                                                </Placeholder>
                                            )
                                        }
                                        {
                                            ! embedHtml && externalVideoUrl && (
                                                <small style={{display: 'flex', alignItems: 'center', gap: '0.5em'}}>
                                                    <Spinner
                                                        style={{
                                                            height: '25px',
                                                            width: '25px'
                                                        }}
                                                    />
                                                    { __( 'Loading video embed...' ) }
                                                </small>
                                            )
                                        }
                                    </>
                                )
                            }
                        </>
                    )}
                </>
                { showSecondary && (
                    <>
                        { secondaryImageData && secondaryImageData.url ? (
                            <figure className="dst-media__secondary is-image">
                                <Image
                                    imageData={ secondaryImageData }
                                    imageSize={ secondaryImageSize }
                                    className="dst-media__src"
                                />
                                {
                                    showToolbars && (
                                        <InlineSettings
                                            onSelectMedia={ ( media ) => handleSecondaryImageUpload( media ) }
                                            onRemoveMedia={ handleSecondaryImageRemove }
                                            allowedTypes={ [ 'image' ] }
                                            mediaId={ secondaryImageData.id }
                                            altIcons
                                            replaceTxt={ __( 'Extra Image' ) }
                                        >
                                            { imageSizes && (
                                                <SelectControl
                                                    label={ __( 'Image Size' ) }
                                                    value={ secondaryImageSize }
                                                    options={ imageSizeOptions }
                                                    onChange={ ( newSize ) => {
                                                        setSecondaryImageSize( newSize );
                                                        const updatedSecondaryImageData = {
                                                            ...secondaryImageData,
                                                            size: newSize,
                                                        };
                                                        setSecondaryImageData( updatedSecondaryImageData );
                                                        onChange(
                                                            buildMediaPayload( 'imageSecondary', updatedSecondaryImageData, value )
                                                        );
                                                    } }
                                                />
                                            ) }
                                        </InlineSettings>
                                    )
                                }
                            </figure>
                        ) : (
                            <SimplePlaceholder
                                icon='format-image'
                                label={ __( 'Decorative Image' ) }
                                instructions={ __( 'Upload an extra image.' ) }
                                allowedTypes={ [ 'image' ] }
                                buttonText={ __( 'Upload extra Image' ) }
                                onSelectMedia={ handleSecondaryImageUpload }
                                mediaData={ secondaryImageData }
                            />
                        ) }
                    </>
                ) }
            </div>
		</>
	);
};
