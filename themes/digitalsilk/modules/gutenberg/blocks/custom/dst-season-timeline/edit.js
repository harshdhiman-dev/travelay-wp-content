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
} from '@wordpress/components';
import { upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, seasons, decorationUrl, spacing } = attributes;

	const spacingStyle = {};
	if ( spacing?.paddingTop ) spacingStyle.paddingTop = spacing.paddingTop;
	if ( spacing?.paddingRight ) spacingStyle.paddingRight = spacing.paddingRight;
	if ( spacing?.paddingBottom ) spacingStyle.paddingBottom = spacing.paddingBottom;
	if ( spacing?.paddingLeft ) spacingStyle.paddingLeft = spacing.paddingLeft;
	if ( spacing?.marginTop ) spacingStyle.marginTop = spacing.marginTop;
	if ( spacing?.marginRight ) spacingStyle.marginRight = spacing.marginRight;
	if ( spacing?.marginBottom ) spacingStyle.marginBottom = spacing.marginBottom;
	if ( spacing?.marginLeft ) spacingStyle.marginLeft = spacing.marginLeft;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-season-timeline' ),
		style: { ...wrapperProps?.style, ...spacingStyle },
	} );

	const updateHeading = ( key, value ) =>
		setAttributes( { heading: { ...heading, [ key ]: value } } );

	const updateSeason = ( index, key, value ) => {
		const newSeasons = seasons.map( ( s, i ) =>
			i === index ? { ...s, [ key ]: value } : s
		);
		setAttributes( { seasons: newSeasons } );
	};

	const updateSeasonMedia = ( index, media ) => {
		updateSeason( index, 'media', {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );
	};

	const removeSeasonMedia = ( index ) => {
		updateSeason( index, 'media', { id: '', url: '', alt: '' } );
	};

	const updateSpacing = ( key, value ) =>
		setAttributes( { spacing: { ...spacing, [ key ]: value } } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Season Images', 'dstheme' ) } initialOpen={ true }>
					{ seasons.map( ( season, index ) => (
						<PanelRow key={ index } className="c-season-timeline__panel-row">
							<div className="c-season-timeline__panel-item">
								<p className="c-season-timeline__panel-label">
									{ season.name || `${ __( 'Season', 'dstheme' ) } ${ index + 1 }` }
								</p>
								<div className="c-season-timeline__panel-controls">
									{ season.media?.url && (
										<img
											className="c-season-timeline__panel-preview"
											src={ season.media.url }
											alt=""
										/>
									) }
									<MediaUploadCheck>
										<MediaUpload
											onSelect={ ( media ) => updateSeasonMedia( index, media ) }
											allowedTypes={ [ 'image' ] }
											value={ season.media?.id }
											render={ ( { open } ) => (
												<Button
													variant="secondary"
													icon={ season.media?.url ? upload : imageIcon }
													onClick={ open }
												>
													{ season.media?.url
														? __( 'Replace', 'dstheme' )
														: __( 'Add Image', 'dstheme' ) }
												</Button>
											) }
										/>
									</MediaUploadCheck>
									{ season.media?.url && (
										<Button
											variant="tertiary"
											isDestructive
											onClick={ () => removeSeasonMedia( index ) }
										>
											{ __( 'Remove', 'dstheme' ) }
										</Button>
									) }
								</div>
							</div>
						</PanelRow>
					) ) }
				</PanelBody>

				<PanelBody title={ __( 'Decoration', 'dstheme' ) } initialOpen={ false }>
					<PanelRow>
						<div className="c-season-timeline__panel-item">
							<p className="c-season-timeline__panel-label">
								{ __( 'Silhouette / Background Image', 'dstheme' ) }
							</p>
							<div className="c-season-timeline__panel-controls">
								{ decorationUrl && (
									<img
										className="c-season-timeline__panel-preview"
										src={ decorationUrl }
										alt=""
									/>
								) }
								<MediaUploadCheck>
									<MediaUpload
										onSelect={ ( media ) =>
											setAttributes( { decorationUrl: media?.url || '' } )
										}
										allowedTypes={ [ 'image', 'image/svg+xml' ] }
										value={ null }
										render={ ( { open } ) => (
											<Button
												variant="secondary"
												icon={ decorationUrl ? upload : imageIcon }
												onClick={ open }
											>
												{ decorationUrl
													? __( 'Replace', 'dstheme' )
													: __( 'Add Decoration', 'dstheme' ) }
											</Button>
										) }
									/>
								</MediaUploadCheck>
								{ decorationUrl && (
									<Button
										variant="tertiary"
										isDestructive
										onClick={ () => setAttributes( { decorationUrl: '' } ) }
									>
										{ __( 'Remove', 'dstheme' ) }
									</Button>
								) }
							</div>
						</div>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Spacing', 'dstheme' ) } initialOpen={ false }>
					<p className="c-season-timeline__panel-label">{ __( 'Padding', 'dstheme' ) }</p>
					<div className="c-season-timeline__spacing-grid">
						{ [ 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft' ].map( ( key ) => (
							<TextControl
								key={ key }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ key.replace( 'padding', '' ) }
								value={ spacing?.[ key ] || '' }
								placeholder="0px"
								onChange={ ( v ) => updateSpacing( key, v ) }
							/>
						) ) }
					</div>
					<p className="c-season-timeline__panel-label">{ __( 'Margin', 'dstheme' ) }</p>
					<div className="c-season-timeline__spacing-grid">
						{ [ 'marginTop', 'marginRight', 'marginBottom', 'marginLeft' ].map( ( key ) => (
							<TextControl
								key={ key }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ key.replace( 'margin', '' ) }
								value={ spacing?.[ key ] || '' }
								placeholder="0px"
								onChange={ ( v ) => updateSpacing( key, v ) }
							/>
						) ) }
					</div>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ decorationUrl && (
					<img
						className="c-season-timeline__decoration"
						src={ decorationUrl }
						alt=""
						aria-hidden="true"
					/>
				) }

				<div className="c-season-timeline__heading">
					{ heading.showDecoration && (
						<span className="c-season-timeline__ornament" aria-hidden="true">
							<img
								src="https://www.flytravelay.com/wp-content/uploads/2026/06/Group-219.png"
								alt=""
							/>
						</span>
					) }
					<Button
						variant="secondary"
						className="c-season-timeline__decor-toggle"
						isPressed={ !!heading.showDecoration }
						onClick={ () => updateHeading( 'showDecoration', !heading.showDecoration ) }
					>
						{ heading.showDecoration ? __( 'Hide ornament', 'dstheme' ) : __( 'Show ornament', 'dstheme' ) }
					</Button>
					<RichText
						tagName="h2"
						className="c-season-timeline__title"
						value={ heading.title }
						onChange={ ( value ) => updateHeading( 'title', value ) }
						placeholder={ __( 'Enter heading…', 'dstheme' ) }
						allowedFormats={ [] }
					/>
				</div>

				<div className="c-season-timeline__track">
					<div className="c-season-timeline__line" aria-hidden="true" />

					{ seasons.map( ( season, index ) => (
						<div className="c-season-timeline__season" key={ index }>
							<div className="c-season-timeline__season-top">
								<RichText
									tagName="strong"
									className="c-season-timeline__season-name"
									value={ season.name }
									onChange={ ( value ) => updateSeason( index, 'name', value ) }
									placeholder={ __( 'Season name…', 'dstheme' ) }
									allowedFormats={ [] }
								/>
								<RichText
									tagName="span"
									className="c-season-timeline__season-subtitle"
									value={ season.subtitle }
									onChange={ ( value ) => updateSeason( index, 'subtitle', value ) }
									placeholder={ __( 'Short description…', 'dstheme' ) }
									allowedFormats={ [] }
								/>
							</div>

							<div className="c-season-timeline__dot" aria-hidden="true" />

							<div className="c-season-timeline__season-image">
								{ season.media?.url ? (
									<img
										src={ season.media.url }
										alt={ season.media.alt || season.name }
									/>
								) : (
									<div className="c-season-timeline__image-placeholder">
										{ __( 'Add image via sidebar', 'dstheme' ) }
									</div>
								) }
							</div>
						</div>
					) ) }
				</div>
			</div>
		</>
	);
};
