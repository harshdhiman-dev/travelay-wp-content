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
		tag,
		title,
		subtitle,
		shortcode,
		background,
		titleFont,
		titleColor,
		tagColor,
		subtitleColor,
		minHeight,
	} = attributes;

	const bgImageUrl    = background?.image?.url || '';
	const overlayColor  = background?.overlayColor || '#000000';
	const overlayOpacity = background?.overlayOpacity ?? 40;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-hero-banner' ),
		style: {
			...wrapperProps?.style,
			minHeight: minHeight || '520px',
		},
	} );

	const updateBackground = ( key, value ) =>
		setAttributes( { background: { ...background, [ key ]: value } } );

	const updateBackgroundImage = ( media ) =>
		updateBackground( 'image', {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );

	const updateTitleFont = ( key, value ) =>
		setAttributes( { titleFont: { ...titleFont, [ key ]: value } } );

	// Build the custom font-face style string for the editor preview.
	const fontFaceStyle = titleFont?.url
		? `@font-face { font-family: '${ titleFont.family || 'CustomHeroFont' }'; src: url('${ titleFont.url }'); } .c-hero-banner__title { font-family: '${ titleFont.family || 'CustomHeroFont' }', serif !important; }`
		: '';

	return (
		<>
			{ fontFaceStyle && (
				<style dangerouslySetInnerHTML={ { __html: fontFaceStyle } } />
			) }

			<InspectorControls>
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-hero-banner__panel-controls">
								{ bgImageUrl && (
									<img className="c-hero-banner__panel-preview" src={ bgImageUrl } alt="" />
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
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
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
							placeholder="520px"
							onChange={ ( value ) => setAttributes( { minHeight: value } ) }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Title Font', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Font Family Name', 'dstheme' ) }
							help={ __( 'Must match the font-family name exactly (e.g. Shivaraja)', 'dstheme' ) }
							value={ titleFont?.family || '' }
							onChange={ ( value ) => updateTitleFont( 'family', value ) }
						/>
					</PanelRow>
					<PanelRow>
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Font File', 'dstheme' ) }</p>
							<p style={ { fontSize: '11px', color: '#757575', margin: '0 0 8px' } }>
								{ __( 'Upload a .woff, .woff2, .ttf or .otf file from the Media Library', 'dstheme' ) }
							</p>
							<div className="c-hero-banner__panel-controls">
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

				<PanelBody title={ __( 'Colors', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Tag Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ tagColor }
								onChange={ ( value ) => setAttributes( { tagColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Title Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ titleColor }
								onChange={ ( value ) => setAttributes( { titleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
					<PanelRow>
						<div className="c-hero-banner__panel-item">
							<p className="c-hero-banner__panel-label">{ __( 'Subtitle Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ subtitleColor }
								onChange={ ( value ) => setAttributes( { subtitleColor: value } ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Search Widget', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Shortcode', 'dstheme' ) }
							value={ shortcode }
							onChange={ ( value ) => setAttributes( { shortcode: value } ) }
							help={ __( 'e.g. [amadex_search_modern]', 'dstheme' ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ bgImageUrl && (
					<img
						className="c-hero-banner__bg"
						src={ bgImageUrl }
						alt={ background?.image?.alt || '' }
						aria-hidden="true"
					/>
				) }
				<span
					className="c-hero-banner__overlay"
					aria-hidden="true"
					style={ {
						backgroundColor: overlayColor,
						opacity: overlayOpacity / 100,
					} }
				/>

				<div className="c-hero-banner__inner">
					<div className="c-hero-banner__content">
						<RichText
							tagName="p"
							className="c-hero-banner__tag"
							value={ tag }
							onChange={ ( value ) => setAttributes( { tag: value } ) }
							placeholder={ __( 'Small tag text…', 'dstheme' ) }
							allowedFormats={ [] }
							style={ { color: tagColor } }
						/>

						<RichText
							tagName="h1"
							className="c-hero-banner__title"
							value={ title }
							onChange={ ( value ) => setAttributes( { title: value } ) }
							placeholder={ __( 'Hero title…', 'dstheme' ) }
							allowedFormats={ [] }
							style={ {
								color: titleColor,
								fontFamily: titleFont?.family ? `'${ titleFont.family }', serif` : undefined,
							} }
						/>

						<RichText
							tagName="p"
							className="c-hero-banner__subtitle"
							value={ subtitle }
							onChange={ ( value ) => setAttributes( { subtitle: value } ) }
							placeholder={ __( 'Subtitle text…', 'dstheme' ) }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							style={ { color: subtitleColor } }
						/>
					</div>

					<div className="c-hero-banner__search">
						<div className="c-hero-banner__search-preview">
							<p>{ shortcode || '[amadex_search_modern]' }</p>
							<small>{ __( '↑ Search widget renders on the frontend only', 'dstheme' ) }</small>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
