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
	ColorPicker,
} from '@wordpress/components';
import { upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		heading,
		headingColor,
		sectionBgColor,
		title,
		titleColor,
		subtitle,
		subtitleColor,
		background,
		minHeight,
		contentWidth,
		button,
	} = attributes;

	const bgImageUrl     = background?.image?.url || '';
	const overlayColor   = background?.overlayColor   || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 35;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-cta-banner' ),
		style: {
			...wrapperProps?.style,
			backgroundColor: sectionBgColor,
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

	const updateButton = ( key, value ) =>
		setAttributes( { button: { ...button, [ key ]: value } } );

	return (
		<>
			<InspectorControls>

				{ /* ── Section ─────────────────────────────────── */ }
				<PanelBody title={ __( 'Section', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Section Background Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ sectionBgColor }
								onChange={ ( value ) => setAttributes( { sectionBgColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Heading Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ headingColor }
								onChange={ ( value ) => setAttributes( { headingColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── Photo Banner ────────────────────────────────── */ }
				<PanelBody title={ __( 'Photo Banner', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Banner Image', 'dstheme' ) }</p>
							<div className="c-cta-banner__panel-controls">
								{ bgImageUrl && (
									<img className="c-cta-banner__panel-preview" src={ bgImageUrl } alt="" />
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
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
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
							placeholder="320px"
							onChange={ ( value ) => setAttributes( { minHeight: value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Content Max Width', 'dstheme' ) }
							value={ contentWidth }
							placeholder="560px"
							onChange={ ( value ) => setAttributes( { contentWidth: value } ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Title & Subtitle Colors ─────────────────────── */ }
				<PanelBody title={ __( 'Text Colors', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Title Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ titleColor }
								onChange={ ( value ) => setAttributes( { titleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Subtitle Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ subtitleColor }
								onChange={ ( value ) => setAttributes( { subtitleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				{ /* ── CTA Button ───────────────────────────────────── */ }
				<PanelBody title={ __( 'Call Button', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Button Text', 'dstheme' ) }
							value={ button?.text || '' }
							placeholder="Call Now: +1 877 721 0410"
							onChange={ ( value ) => updateButton( 'text', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Phone Number', 'dstheme' ) }
							value={ button?.phone || '' }
							placeholder="+18777210410"
							help={ __( 'Used for the tel: link', 'dstheme' ) }
							onChange={ ( value ) => updateButton( 'phone', value ) }
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
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Button Background', 'dstheme' ) }</p>
							<ColorPicker
								color={ button?.bgColor || '#1f7a4d' }
								onChange={ ( value ) => updateButton( 'bgColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-cta-banner__panel-item">
							<p className="c-cta-banner__panel-label">{ __( 'Button Text Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ button?.textColor || '#ffffff' }
								onChange={ ( value ) => updateButton( 'textColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

			</InspectorControls>

			<div { ...blockProps }>
				<div className="c-cta-banner__inner">

					<RichText
						tagName="h2"
						className="c-cta-banner__heading"
						value={ heading }
						onChange={ ( value ) => setAttributes( { heading: value } ) }
						placeholder={ __( 'Section heading…', 'dstheme' ) }
						allowedFormats={ [] }
						style={ { color: headingColor } }
					/>

					<div className="c-cta-banner__photo" style={ { minHeight: minHeight || '320px' } }>
						{ bgImageUrl && (
							<img className="c-cta-banner__bg" src={ bgImageUrl } alt={ background?.image?.alt || '' } />
						) }
						{ overlayOpacity > 0 && (
							<span
								className="c-cta-banner__overlay"
								aria-hidden="true"
								style={ { backgroundColor: overlayColor, opacity: overlayOpacity / 100 } }
							/>
						) }

						<div className="c-cta-banner__content" style={ { maxWidth: contentWidth || '560px' } }>
							<RichText
								tagName="h3"
								className="c-cta-banner__title"
								value={ title }
								onChange={ ( value ) => setAttributes( { title: value } ) }
								placeholder={ __( 'Banner title…', 'dstheme' ) }
								allowedFormats={ [ 'core/bold', 'core/italic' ] }
								style={ { color: titleColor } }
							/>
							<RichText
								tagName="p"
								className="c-cta-banner__subtitle"
								value={ subtitle }
								onChange={ ( value ) => setAttributes( { subtitle: value } ) }
								placeholder={ __( 'Banner subtitle…', 'dstheme' ) }
								allowedFormats={ [ 'core/bold', 'core/italic' ] }
								style={ { color: subtitleColor } }
							/>

							{ button?.text && (
								<span
									className="c-cta-banner__cta"
									style={ {
										backgroundColor: button.bgColor || '#1f7a4d',
										color:           button.textColor || '#ffffff',
										borderRadius:    button.borderRadius || '40px',
									} }
								>
									{ button.text }
								</span>
							) }
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
