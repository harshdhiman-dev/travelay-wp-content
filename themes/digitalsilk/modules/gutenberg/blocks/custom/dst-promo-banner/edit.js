/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	PanelRow,
	TextControl,
	RangeControl,
	SelectControl,
	ToggleControl,
	ColorPicker,
} from '@wordpress/components';
import { upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		title,
		subtitle,
		titleColor,
		subtitleColor,
		titleFontSize,
		titleAlign,
		subtitleFontSize,
		subtitleAlign,
		titleFont,
		background,
		minHeight,
		contentWidth,
		button,
		subtitleBorder,
	} = attributes;

	const bgImageUrl     = background?.image?.url || '';
	const bgColor        = background?.bgColor    || '#d4edda';
	const overlayColor   = background?.overlayColor   || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 0;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-promo-banner' ),
		style: {
			...wrapperProps?.style,
			minHeight:       minHeight || '220px',
			backgroundColor: bgColor,
		},
	} );

	const updateBackground = ( key, value ) =>
		setAttributes( { background: { ...background, [ key ]: value } } );

	const updateBackgroundImage = ( media ) =>
		updateBackground( 'image', {
			id:  media?.id  || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );

	const updateTitleFont = ( key, value ) =>
		setAttributes( { titleFont: { ...titleFont, [ key ]: value } } );

	const updateButton = ( key, value ) =>
		setAttributes( { button: { ...button, [ key ]: value } } );

	const fontFaceStyle = titleFont?.url && titleFont?.family
		? `@font-face { font-family: '${ titleFont.family }'; src: url('${ titleFont.url }'); } .c-promo-banner__title { font-family: '${ titleFont.family }', serif !important; }`
		: '';

	// Build live preview title style
	const titleStyle = {
		color:     titleColor,
		textAlign: titleAlign,
		fontFamily: titleFont?.family ? `'${ titleFont.family }', serif` : undefined,
	};
	if ( titleFontSize ) titleStyle.fontSize = titleFontSize;

	const subtitleStyle = {
		color:     subtitleColor,
		textAlign: subtitleAlign,
	};
	if ( subtitleFontSize ) subtitleStyle.fontSize = subtitleFontSize;

	return (
		<>
			{ fontFaceStyle && (
				<style dangerouslySetInnerHTML={ { __html: fontFaceStyle } } />
			) }

			<InspectorControls>

				{ /* ── Background ──────────────────────────────── */ }
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-promo-banner__panel-controls">
								{ bgImageUrl && (
									<img className="c-promo-banner__panel-preview" src={ bgImageUrl } alt="" />
								) }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( media ) => updateBackgroundImage( media ) }
										allowedTypes={ [ 'image' ] }
										value={ background?.image?.id }
										render={ ( { open } ) => (
											<Button
												variant="secondary"
												icon={ bgImageUrl ? upload : imageIcon }
												onClick={ open }
											>
												{ bgImageUrl ? __( 'Replace', 'dstheme' ) : __( 'Add Image', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ bgImageUrl && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => updateBackground( 'image', { id: '', url: '', alt: '' } ) }
									>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>

					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Background Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ bgColor }
								onChange={ ( value ) => updateBackground( 'bgColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>

					<PanelRow>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Overlay Opacity', 'dstheme' ) }
							value={ overlayOpacity }
							onChange={ ( value ) => updateBackground( 'overlayOpacity', value ) }
							min={ 0 }
							max={ 100 }
						/>
					</PanelRow>

					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ overlayColor }
								onChange={ ( value ) => updateBackground( 'overlayColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>

					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Min Height', 'dstheme' ) }
							value={ minHeight }
							placeholder="220px"
							onChange={ ( value ) => setAttributes( { minHeight: value } ) }
						/>
					</PanelRow>

					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Content Max Width', 'dstheme' ) }
							value={ contentWidth }
							placeholder="900px"
							help={ __( 'Max width of the content area', 'dstheme' ) }
							onChange={ ( value ) => setAttributes( { contentWidth: value } ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Title ───────────────────────────────────── */ }
				<PanelBody title={ __( 'Title', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Font Size', 'dstheme' ) }
							value={ titleFontSize }
							placeholder="e.g. 36px or 3vw"
							help={ __( 'Leave empty to use theme default', 'dstheme' ) }
							onChange={ ( value ) => setAttributes( { titleFontSize: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Alignment', 'dstheme' ) }
							value={ titleAlign }
							options={ [
								{ label: __( 'Left',   'dstheme' ), value: 'left'   },
								{ label: __( 'Center', 'dstheme' ), value: 'center' },
								{ label: __( 'Right',  'dstheme' ), value: 'right'  },
							] }
							onChange={ ( value ) => setAttributes( { titleAlign: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ titleColor }
								onChange={ ( value ) => setAttributes( { titleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Title Font ───────────────────────────────── */ }
				<PanelBody title={ __( 'Title Font', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Font Family Name', 'dstheme' ) }
							help={ __( 'Must match the font-family name exactly', 'dstheme' ) }
							value={ titleFont?.family || '' }
							onChange={ ( value ) => updateTitleFont( 'family', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Font File', 'dstheme' ) }</p>
							<p style={ { fontSize: '11px', color: '#757575', margin: '0 0 8px' } }>
								{ __( 'Upload a .woff, .woff2, .ttf or .otf file', 'dstheme' ) }
							</p>
							<div className="c-promo-banner__panel-controls">
								{ titleFont?.url && (
									<code style={ { fontSize: '11px', wordBreak: 'break-all' } }>
										{ titleFont.url.split( '/' ).pop() }
									</code>
								) }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( media ) => updateTitleFont( 'url', media?.url || '' ) }
										allowedTypes={ [ 'application/font-woff', 'application/font-woff2', 'font/woff', 'font/woff2', 'application/x-font-ttf', 'font/ttf', 'font/otf', 'application/octet-stream' ] }
										value={ null }
										render={ ( { open } ) => (
											<Button
												variant="secondary"
												icon={ titleFont?.url ? upload : imageIcon }
												onClick={ open }
											>
												{ titleFont?.url ? __( 'Replace Font', 'dstheme' ) : __( 'Upload Font', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ titleFont?.url && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => updateTitleFont( 'url', '' ) }
									>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Subtitle ─────────────────────────────────── */ }
				<PanelBody title={ __( 'Subtitle', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Font Size', 'dstheme' ) }
							value={ subtitleFontSize }
							placeholder="e.g. 16px or 1.4rem"
							help={ __( 'Leave empty to use theme default', 'dstheme' ) }
							onChange={ ( value ) => setAttributes( { subtitleFontSize: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Alignment', 'dstheme' ) }
							value={ subtitleAlign }
							options={ [
								{ label: __( 'Left',   'dstheme' ), value: 'left'   },
								{ label: __( 'Center', 'dstheme' ), value: 'center' },
								{ label: __( 'Right',  'dstheme' ), value: 'right'  },
							] }
							onChange={ ( value ) => setAttributes( { subtitleAlign: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show border around subtitle', 'dstheme' ) }
							checked={ subtitleBorder }
							onChange={ ( value ) => setAttributes( { subtitleBorder: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ subtitleColor }
								onChange={ ( value ) => setAttributes( { subtitleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── CTA Button ───────────────────────────────── */ }
				<PanelBody title={ __( 'CTA Button', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Button Text', 'dstheme' ) }
							value={ button?.text || '' }
							placeholder={ __( 'e.g. Book Now', 'dstheme' ) }
							onChange={ ( value ) => updateButton( 'text', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Button URL', 'dstheme' ) }
							value={ button?.url || '' }
							placeholder="https://"
							onChange={ ( value ) => updateButton( 'url', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Open in', 'dstheme' ) }
							value={ button?.target || '_self' }
							options={ [
								{ label: __( 'Same tab', 'dstheme' ),  value: '_self'  },
								{ label: __( 'New tab',  'dstheme' ),  value: '_blank' },
							] }
							onChange={ ( value ) => updateButton( 'target', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Border Radius', 'dstheme' ) }
							value={ button?.borderRadius || '40px' }
							placeholder="40px"
							onChange={ ( value ) => updateButton( 'borderRadius', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Button Background', 'dstheme' ) }</p>
							<ColorPicker
								color={ button?.bgColor || '#1a5c2a' }
								onChange={ ( value ) => updateButton( 'bgColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-promo-banner__panel-item">
							<p className="c-promo-banner__panel-label">{ __( 'Button Text Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ button?.textColor || '#ffffff' }
								onChange={ ( value ) => updateButton( 'textColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

			</InspectorControls>

			<div className="c-promo-banner__outer">
			<div { ...blockProps }>
				{ bgImageUrl && (
					<img
						className="c-promo-banner__bg"
						src={ bgImageUrl }
						alt={ background?.image?.alt || '' }
						aria-hidden="true"
					/>
				) }
				{ overlayOpacity > 0 && (
					<span
						className="c-promo-banner__overlay"
						aria-hidden="true"
						style={ {
							backgroundColor: overlayColor,
							opacity: overlayOpacity / 100,
						} }
					/>
				) }

				<div className="c-promo-banner__inner" style={ { maxWidth: contentWidth || '900px' } }>
					<div className="c-promo-banner__content">

						<RichText
							tagName="h2"
							className="c-promo-banner__title"
							value={ title }
							onChange={ ( value ) => setAttributes( { title: value } ) }
							placeholder={ __( 'Promo title…', 'dstheme' ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							style={ titleStyle }
						/>

						<RichText
							tagName="p"
							className={ `c-promo-banner__subtitle${ subtitleBorder ? ' c-promo-banner__subtitle--border' : '' }` }
							value={ subtitle }
							onChange={ ( value ) => setAttributes( { subtitle: value } ) }
							placeholder={ __( 'Subtitle text…', 'dstheme' ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							style={ subtitleStyle }
						/>

						{ button?.text && (
							<div className="c-promo-banner__cta-wrap">
								<span
									className="c-promo-banner__cta"
									style={ {
										backgroundColor: button.bgColor || '#1a5c2a',
										color:           button.textColor || '#ffffff',
										borderRadius:    button.borderRadius || '40px',
									} }
								>
									{ button.text }
								</span>
							</div>
						) }

					</div>
				</div>
			</div>
			</div>
		</>
	);
};
