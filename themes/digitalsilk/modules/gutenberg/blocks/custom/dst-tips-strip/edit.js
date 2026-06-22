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
	RangeControl,
	ColorPicker,
	TextControl,
} from '@wordpress/components';
import { plus, trash, upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, background, tips, spacing } = attributes;

	/**
	 * Build inline styles for the block wrapper.
	 */
	const wrapperStyle = {};
	if ( spacing?.paddingTop ) wrapperStyle.paddingTop = spacing.paddingTop;
	if ( spacing?.paddingRight ) wrapperStyle.paddingRight = spacing.paddingRight;
	if ( spacing?.paddingBottom ) wrapperStyle.paddingBottom = spacing.paddingBottom;
	if ( spacing?.paddingLeft ) wrapperStyle.paddingLeft = spacing.paddingLeft;
	if ( spacing?.marginTop ) wrapperStyle.marginTop = spacing.marginTop;
	if ( spacing?.marginRight ) wrapperStyle.marginRight = spacing.marginRight;
	if ( spacing?.marginBottom ) wrapperStyle.marginBottom = spacing.marginBottom;
	if ( spacing?.marginLeft ) wrapperStyle.marginLeft = spacing.marginLeft;

	const blockProps = useBlockProps( {
		...wrapperProps,
		className: classNames( wrapperProps?.className, 'c-tips-strip' ),
		style: { ...wrapperProps?.style, ...wrapperStyle },
	} );

	/**
	 * Update heading field.
	 *
	 * @param {string} key   Key to update.
	 * @param {*}      value New value.
	 */
	const updateHeading = ( key, value ) => {
		setAttributes( { heading: { ...heading, [ key ]: value } } );
	};

	/**
	 * Update background field.
	 *
	 * @param {string} key   Key to update.
	 * @param {*}      value New value.
	 */
	const updateBackground = ( key, value ) => {
		setAttributes( { background: { ...background, [ key ]: value } } );
	};

	/**
	 * Update background image from media picker.
	 *
	 * @param {Object} media Selected media object.
	 */
	const updateBackgroundImage = ( media ) => {
		updateBackground( 'image', {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );
	};

	/**
	 * Update a single field of a single tip.
	 *
	 * @param {number} index Tip index.
	 * @param {string} key   Field key.
	 * @param {*}      value New value.
	 */
	const updateTip = ( index, key, value ) => {
		const newTips = tips.map( ( tip, i ) =>
			i === index ? { ...tip, [ key ]: value } : tip
		);
		setAttributes( { tips: newTips } );
	};

	/**
	 * Update a tip's icon from media picker.
	 *
	 * @param {number} index Tip index.
	 * @param {Object} media Selected media object.
	 */
	const updateTipIcon = ( index, media ) => {
		updateTip( index, 'icon', {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		} );
	};

	/**
	 * Remove a tip's icon.
	 *
	 * @param {number} index Tip index.
	 */
	const removeTipIcon = ( index ) => {
		updateTip( index, 'icon', { id: '', url: '', alt: '' } );
	};

	/**
	 * Add a new empty tip.
	 */
	const addTip = () => {
		setAttributes( {
			tips: [ ...tips, { icon: { id: '', url: '', alt: '' }, title: __( 'New tip', 'dstheme' ), description: '' } ],
		} );
	};

	/**
	 * Remove a tip.
	 *
	 * @param {number} index Tip index.
	 */
	const removeTip = ( index ) => {
		setAttributes( { tips: tips.filter( ( _, i ) => i !== index ) } );
	};

	/**
	 * Update a spacing field.
	 *
	 * @param {string} key   Spacing key.
	 * @param {*}      value New value.
	 */
	const updateSpacing = ( key, value ) => {
		setAttributes( { spacing: { ...spacing, [ key ]: value } } );
	};

	const bgImageUrl = background?.image?.url || '';
	const overlayColor = background?.overlayColor || '#137c43';
	const overlayOpacity = background?.overlayOpacity ?? 65;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Background', 'dstheme' ) } initialOpen={ true }>
					<PanelRow>
						<div className="c-tips-strip__panel-item">
							<p className="c-tips-strip__panel-label">{ __( 'Background Image', 'dstheme' ) }</p>
							<div className="c-tips-strip__panel-controls">
								{ bgImageUrl && (
									<img className="c-tips-strip__panel-preview" src={ bgImageUrl } alt="" />
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
												{ bgImageUrl ? __( 'Replace Image', 'dstheme' ) : __( 'Add Image', 'dstheme' ) }
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
						<div className="c-tips-strip__panel-item">
							<p className="c-tips-strip__panel-label">{ __( 'Overlay Color', 'dstheme' ) }</p>
							<ColorPicker
								color={ overlayColor }
								onChange={ ( value ) => updateBackground( 'overlayColor', value ) }
								enableAlpha
							/>
						</div>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Tips / Cards', 'dstheme' ) } initialOpen={ false }>
					{ tips.map( ( tip, index ) => (
						<PanelRow key={ index } className="c-tips-strip__panel-row">
							<div className="c-tips-strip__panel-item">
								<p className="c-tips-strip__panel-label">
									{ __( 'Card', 'dstheme' ) } { index + 1 } { tip.title ? `— ${ tip.title }` : '' }
								</p>
								<div className="c-tips-strip__panel-controls">
									{ tip.icon?.url && (
										<img className="c-tips-strip__panel-preview" src={ tip.icon.url } alt="" />
									) }
									<MediaUploadCheck>
										<MediaUpload
											onSelect={ ( media ) => updateTipIcon( index, media ) }
											allowedTypes={ [ 'image' ] }
											value={ tip.icon?.id }
											render={ ( { open } ) => (
												<Button
													variant="secondary"
													icon={ tip.icon?.url ? upload : imageIcon }
													onClick={ open }
												>
													{ tip.icon?.url ? __( 'Replace Icon', 'dstheme' ) : __( 'Add Icon', 'dstheme' ) }
												</Button>
											) }
										/>
									</MediaUploadCheck>
									{ tip.icon?.url && (
										<Button
											variant="tertiary"
											isDestructive
											onClick={ () => removeTipIcon( index ) }
										>
											{ __( 'Remove', 'dstheme' ) }
										</Button>
									) }
								</div>

								{ tips.length > 1 && (
									<Button
										variant="tertiary"
										isDestructive
										icon={ trash }
										onClick={ () => removeTip( index ) }
										style={ { marginTop: '8px' } }
									>
										{ __( 'Remove card', 'dstheme' ) }
									</Button>
								) }
							</div>
						</PanelRow>
					) ) }

					<PanelRow>
						<Button variant="primary" icon={ plus } onClick={ addTip }>
							{ __( 'Add card', 'dstheme' ) }
						</Button>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Spacing', 'dstheme' ) } initialOpen={ false }>
					<p className="c-tips-strip__panel-label">{ __( 'Padding', 'dstheme' ) }</p>
					<div className="c-tips-strip__spacing-grid">
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Top', 'dstheme' ) } value={ spacing?.paddingTop || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'paddingTop', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Right', 'dstheme' ) } value={ spacing?.paddingRight || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'paddingRight', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Bottom', 'dstheme' ) } value={ spacing?.paddingBottom || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'paddingBottom', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Left', 'dstheme' ) } value={ spacing?.paddingLeft || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'paddingLeft', v ) } />
					</div>
					<p className="c-tips-strip__panel-label">{ __( 'Margin', 'dstheme' ) }</p>
					<div className="c-tips-strip__spacing-grid">
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Top', 'dstheme' ) } value={ spacing?.marginTop || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'marginTop', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Right', 'dstheme' ) } value={ spacing?.marginRight || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'marginRight', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Bottom', 'dstheme' ) } value={ spacing?.marginBottom || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'marginBottom', v ) } />
						<TextControl __next40pxDefaultSize __nextHasNoMarginBottom label={ __( 'Left', 'dstheme' ) } value={ spacing?.marginLeft || '' } placeholder="0px" onChange={ ( v ) => updateSpacing( 'marginLeft', v ) } />
					</div>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ bgImageUrl && (
					<img
						className="c-tips-strip__bg"
						src={ bgImageUrl }
						alt=""
						aria-hidden="true"
					/>
				) }
				<span
					className="c-tips-strip__overlay"
					aria-hidden="true"
					style={ {
						backgroundColor: overlayColor,
						opacity: overlayOpacity / 100,
					} }
				/>

				<div className="c-tips-strip__inner">
					<div className="c-tips-strip__content">
						<RichText
							tagName="h2"
							className="c-tips-strip__title"
							value={ heading.title }
							onChange={ ( value ) => updateHeading( 'title', value ) }
							placeholder={ __( 'Enter heading…', 'dstheme' ) }
							allowedFormats={ [] }
						/>
						<RichText
							tagName="p"
							className="c-tips-strip__subtitle"
							value={ heading.subtitle }
							onChange={ ( value ) => updateHeading( 'subtitle', value ) }
							placeholder={ __( 'Enter subtitle…', 'dstheme' ) }
							allowedFormats={ [] }
						/>
					</div>

					<div className="c-tips-strip__cards">
						{ tips.map( ( tip, index ) => (
							<div
								className={ classNames( 'c-tips-strip__card', `-card-${ index + 1 }` ) }
								key={ index }
							>
								{ tip.icon?.url && (
									<img
										className="c-tips-strip__card-icon"
										src={ tip.icon.url }
										alt={ tip.icon.alt || '' }
									/>
								) }
								<RichText
									tagName="h3"
									className="c-tips-strip__card-title"
									value={ tip.title }
									onChange={ ( value ) => updateTip( index, 'title', value ) }
									placeholder={ __( 'Tip title…', 'dstheme' ) }
									allowedFormats={ [] }
								/>
								<RichText
									tagName="p"
									className="c-tips-strip__card-description"
									value={ tip.description }
									onChange={ ( value ) => updateTip( index, 'description', value ) }
									placeholder={ __( 'Short description…', 'dstheme' ) }
									allowedFormats={ [] }
								/>
							</div>
						) ) }
					</div>
				</div>
			</div>
		</>
	);
};
