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
	SelectControl,
	ToggleControl,
	RangeControl,
	ColorPicker,
	TextControl,
} from '@wordpress/components';
import { plus, trash, upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

const MEDIA_FIT_OPTIONS = [
	{ label: __('Media Cover', 'dstheme'), value: 'cover' },
	{ label: __('Media Contain', 'dstheme'), value: 'contain' },
];

const MEDIA_POSITION_OPTIONS = [
	{ label: __('Top Left', 'dstheme'), value: 'top left' },
	{ label: __('Top Center', 'dstheme'), value: 'top center' },
	{ label: __('Top Right', 'dstheme'), value: 'top right' },
	{ label: __('Center Left', 'dstheme'), value: 'center left' },
	{ label: __('Center Center', 'dstheme'), value: 'center center' },
	{ label: __('Center Right', 'dstheme'), value: 'center right' },
	{ label: __('Bottom Left', 'dstheme'), value: 'bottom left' },
	{ label: __('Bottom Center', 'dstheme'), value: 'bottom center' },
	{ label: __('Bottom Right', 'dstheme'), value: 'bottom right' },
];

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, features, images, showDecoration, background, spacing } = attributes;

	/**
	 * Build the live preview inline style for background + spacing.
	 */
	const backgroundStyle = {};
	if (background?.type === 'color' && background?.color) {
		backgroundStyle.backgroundColor = background.color;
	} else if (background?.type === 'image' && background?.image?.url) {
		backgroundStyle.backgroundImage = `url(${background.image.url})`;
		backgroundStyle.backgroundSize = background.mediaFit === 'contain' ? 'contain' : 'cover';
		backgroundStyle.backgroundPosition = background.mediaPosition || 'center center';
		backgroundStyle.backgroundRepeat = 'no-repeat';
	}

	const spacingStyle = {};
	if (spacing?.paddingTop) spacingStyle.paddingTop = spacing.paddingTop;
	if (spacing?.paddingRight) spacingStyle.paddingRight = spacing.paddingRight;
	if (spacing?.paddingBottom) spacingStyle.paddingBottom = spacing.paddingBottom;
	if (spacing?.paddingLeft) spacingStyle.paddingLeft = spacing.paddingLeft;
	if (spacing?.marginTop) spacingStyle.marginTop = spacing.marginTop;
	if (spacing?.marginRight) spacingStyle.marginRight = spacing.marginRight;
	if (spacing?.marginBottom) spacingStyle.marginBottom = spacing.marginBottom;
	if (spacing?.marginLeft) spacingStyle.marginLeft = spacing.marginLeft;

	const blockProps = useBlockProps({
		...wrapperProps,
		className: classNames(wrapperProps?.className, 'c-explore'),
		style: { ...wrapperProps?.style, ...backgroundStyle, ...spacingStyle },
	});

	/**
	 * Update a single heading field.
	 *
	 * @param {string} key   Heading attribute key.
	 * @param {*}      value New value.
	 */
	const updateHeading = (key, value) => {
		setAttributes({ heading: { ...heading, [key]: value } });
	};

	/**
	 * Update a single field of a single feature.
	 *
	 * @param {number} index Feature index.
	 * @param {string} key   Feature attribute key.
	 * @param {*}      value New value.
	 */
	const updateFeature = (index, key, value) => {
		const newFeatures = features.map((feature, i) =>
			i === index ? { ...feature, [key]: value } : feature
		);
		setAttributes({ features: newFeatures });
	};

	/**
	 * Append a new empty feature row.
	 */
	const addFeature = () => {
		setAttributes({
			features: [...features, { title: __('New feature', 'dstheme'), description: '' }],
		});
	};

	/**
	 * Remove a feature row.
	 *
	 * @param {number} index Feature index to remove.
	 */
	const removeFeature = (index) => {
		setAttributes({ features: features.filter((_, i) => i !== index) });
	};

	/**
	 * Update the primary image from a selected attachment.
	 *
	 * @param {Object} media Selected media object from MediaUpload.
	 */
	const updateImage = (media) => {
		setAttributes({
			images: [
				{
					media: {
						primaryType: 'image',
						imagePrimary: {
							id: media?.id || '',
							url: media?.url || '',
							alt: media?.alt || '',
							size: 'full',
						},
					},
				},
			],
		});
	};

	/**
	 * Remove the primary image.
	 */
	const removeImage = () => {
		setAttributes({ images: [{ media: {} }] });
	};

	/**
	 * Update a single field of the background settings.
	 *
	 * @param {string} key   Background attribute key.
	 * @param {*}      value New value.
	 */
	const updateBackground = (key, value) => {
		setAttributes({ background: { ...background, [key]: value } });
	};

	/**
	 * Update the background image or mobile image from a selected attachment.
	 *
	 * @param {string} key   Either 'image' or 'imageMobile'.
	 * @param {Object} media Selected media object from MediaUpload.
	 */
	const updateBackgroundImage = (key, media) => {
		updateBackground(key, {
			id: media?.id || '',
			url: media?.url || '',
			alt: media?.alt || '',
		});
	};

	/**
	 * Remove the background image or mobile image.
	 *
	 * @param {string} key Either 'image' or 'imageMobile'.
	 */
	const removeBackgroundImage = (key) => {
		updateBackground(key, { id: '', url: '', alt: '' });
	};

	/**
	 * Update a single field of the spacing settings.
	 *
	 * @param {string} key   Spacing attribute key.
	 * @param {*}      value New value.
	 */
	const updateSpacing = (key, value) => {
		setAttributes({ spacing: { ...spacing, [key]: value } });
	};

	const imageUrl = images?.[0]?.media?.imagePrimary?.url || '';
	const imageId = images?.[0]?.media?.imagePrimary?.id || undefined;

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Background', 'dstheme')} initialOpen={false}>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Background Type', 'dstheme')}
							value={background?.type || 'none'}
							options={[
								{ label: __('None', 'dstheme'), value: 'none' },
								{ label: __('Color', 'dstheme'), value: 'color' },
								{ label: __('Image', 'dstheme'), value: 'image' },
							]}
							onChange={(value) => updateBackground('type', value)}
						/>
					</PanelRow>

					{background?.type === 'color' && (
						<PanelRow>
							<div className="c-explore__panel-item">
								<p className="c-explore__panel-label">
									{__('Background Color', 'dstheme')}
								</p>
								<ColorPicker
									color={background?.color || '#1f7a4d'}
									onChange={(value) => updateBackground('color', value)}
									enableAlpha
								/>
							</div>
						</PanelRow>
					)}

					{background?.type === 'image' && (
						<>
							<PanelRow>
								<div className="c-explore__panel-item">
									<p className="c-explore__panel-label">{__('Image', 'dstheme')}</p>
									<div className="c-explore__panel-controls">
										{background?.image?.url && (
											<img
												className="c-explore__panel-preview"
												src={background.image.url}
												alt=""
											/>
										)}
										<MediaUploadCheck>
											<MediaUpload
												onSelect={(media) => updateBackgroundImage('image', media)}
												allowedTypes={['image']}
												value={background?.image?.id}
												render={({ open }) => (
													<Button
														variant="secondary"
														icon={background?.image?.url ? upload : imageIcon}
														onClick={open}
													>
														{background?.image?.url
															? __('Replace Image', 'dstheme')
															: __('Add Image', 'dstheme')}
													</Button>
												)}
											/>
										</MediaUploadCheck>
										{background?.image?.url && (
											<Button
												variant="tertiary"
												isDestructive
												onClick={() => removeBackgroundImage('image')}
											>
												{__('Remove', 'dstheme')}
											</Button>
										)}
									</div>
								</div>
							</PanelRow>

							<PanelRow>
								<div className="c-explore__panel-item">
									<p className="c-explore__panel-label">
										{__('Mobile Image', 'dstheme')}
									</p>
									<div className="c-explore__panel-controls">
										{background?.imageMobile?.url && (
											<img
												className="c-explore__panel-preview"
												src={background.imageMobile.url}
												alt=""
											/>
										)}
										<MediaUploadCheck>
											<MediaUpload
												onSelect={(media) => updateBackgroundImage('imageMobile', media)}
												allowedTypes={['image']}
												value={background?.imageMobile?.id}
												render={({ open }) => (
													<Button
														variant="secondary"
														icon={background?.imageMobile?.url ? upload : imageIcon}
														onClick={open}
													>
														{background?.imageMobile?.url
															? __('Replace Image', 'dstheme')
															: __('Add Image', 'dstheme')}
													</Button>
												)}
											/>
										</MediaUploadCheck>
										{background?.imageMobile?.url && (
											<Button
												variant="tertiary"
												isDestructive
												onClick={() => removeBackgroundImage('imageMobile')}
											>
												{__('Remove', 'dstheme')}
											</Button>
										)}
									</div>
								</div>
							</PanelRow>

							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={__('Media Fit', 'dstheme')}
									value={background?.mediaFit || 'cover'}
									options={MEDIA_FIT_OPTIONS}
									onChange={(value) => updateBackground('mediaFit', value)}
								/>
							</PanelRow>

							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={__('Media Position', 'dstheme')}
									value={background?.mediaPosition || 'center center'}
									options={MEDIA_POSITION_OPTIONS}
									onChange={(value) => updateBackground('mediaPosition', value)}
								/>
							</PanelRow>

							<PanelRow>
								<ToggleControl
									__nextHasNoMarginBottom
									label={__('Disable Lazy Load?', 'dstheme')}
									checked={!!background?.disableLazyLoad}
									onChange={(value) => updateBackground('disableLazyLoad', value)}
								/>
							</PanelRow>

							<PanelRow>
								<RangeControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={__('Overlay Opacity', 'dstheme')}
									value={background?.overlayOpacity ?? 0}
									onChange={(value) => updateBackground('overlayOpacity', value)}
									min={0}
									max={100}
								/>
							</PanelRow>

							<PanelRow>
								<div className="c-explore__panel-item">
									<p className="c-explore__panel-label">
										{__('Overlay Color', 'dstheme')}
									</p>
									<ColorPicker
										color={background?.overlayColor || '#000000'}
										onChange={(value) => updateBackground('overlayColor', value)}
										enableAlpha
									/>
								</div>
							</PanelRow>
						</>
					)}
				</PanelBody>

				<PanelBody title={__('Spacing', 'dstheme')} initialOpen={false}>
					<p className="c-explore__panel-label">{__('Padding', 'dstheme')}</p>
					<div className="c-explore__spacing-grid">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Top', 'dstheme')}
							value={spacing?.paddingTop || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('paddingTop', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Right', 'dstheme')}
							value={spacing?.paddingRight || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('paddingRight', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Bottom', 'dstheme')}
							value={spacing?.paddingBottom || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('paddingBottom', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Left', 'dstheme')}
							value={spacing?.paddingLeft || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('paddingLeft', value)}
						/>
					</div>

					<p className="c-explore__panel-label">{__('Margin', 'dstheme')}</p>
					<div className="c-explore__spacing-grid">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Top', 'dstheme')}
							value={spacing?.marginTop || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('marginTop', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Right', 'dstheme')}
							value={spacing?.marginRight || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('marginRight', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Bottom', 'dstheme')}
							value={spacing?.marginBottom || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('marginBottom', value)}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Left', 'dstheme')}
							value={spacing?.marginLeft || ''}
							placeholder="0px"
							onChange={(value) => updateSpacing('marginLeft', value)}
						/>
					</div>
				</PanelBody>

				<PanelBody title={__('Media Content', 'dstheme')} initialOpen={true}>
					<PanelRow>
						<div className="c-explore__panel-item">
							<p className="c-explore__panel-label">{__('Image', 'dstheme')}</p>
							<div className="c-explore__panel-controls">
								{imageUrl && (
									<img className="c-explore__panel-preview" src={imageUrl} alt="" />
								)}
								<MediaUploadCheck>
									<MediaUpload
										onSelect={(media) => updateImage(media)}
										allowedTypes={['image']}
										value={imageId}
										render={({ open }) => (
											<Button
												variant="secondary"
												icon={imageUrl ? upload : imageIcon}
												onClick={open}
											>
												{imageUrl
													? __('Replace Image', 'dstheme')
													: __('Add Image', 'dstheme')}
											</Button>
										)}
									/>
								</MediaUploadCheck>
								{imageUrl && (
									<Button variant="tertiary" isDestructive onClick={removeImage}>
										{__('Remove', 'dstheme')}
									</Button>
								)}
							</div>
						</div>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				{background?.type === 'image' && background?.image?.url && background?.overlayOpacity > 0 && (
					<span
						className="c-explore__bg-overlay"
						aria-hidden="true"
						style={{
							backgroundColor: background?.overlayColor || '#000000',
							opacity: (background?.overlayOpacity || 0) / 100,
						}}
					/>
				)}

				<div className="c-explore__inner">
					<div className="c-explore__content">
						<RichText
							tagName="h2"
							className="c-explore__title"
							value={heading.title}
							onChange={(value) => updateHeading('title', value)}
							placeholder={__('Enter heading…', 'dstheme')}
							allowedFormats={[]}
						/>
						<RichText
							tagName="p"
							className="c-explore__subtitle"
							value={heading.subtitle}
							onChange={(value) => updateHeading('subtitle', value)}
							placeholder={__('Enter subtitle…', 'dstheme')}
							allowedFormats={[]}
						/>

						<div className="c-explore__card">
							{features.map((feature, index) => (
								<div className="c-explore__feature" key={index}>
									<RichText
										tagName="h3"
										className="c-explore__feature-title"
										value={feature.title}
										onChange={(value) => updateFeature(index, 'title', value)}
										placeholder={__('Feature title…', 'dstheme')}
										allowedFormats={[]}
									/>
									<RichText
										tagName="p"
										className="c-explore__feature-description"
										value={feature.description}
										onChange={(value) => updateFeature(index, 'description', value)}
										placeholder={__('Feature description…', 'dstheme')}
										allowedFormats={['core/bold', 'core/italic']}
									/>

									{features.length > 1 && (
										<Button
											className="c-explore__feature-remove"
											icon={trash}
											label={__('Remove feature', 'dstheme')}
											onClick={() => removeFeature(index)}
										/>
									)}
								</div>
							))}

							<Button
								variant="primary"
								icon={plus}
								onClick={addFeature}
								className="c-explore__feature-add"
							>
								{__('Add feature', 'dstheme')}
							</Button>
						</div>
					</div>

					<div className="c-explore__media">
						<div className="c-explore__image -only">
							{imageUrl ? (
								<img src={imageUrl} alt={images?.[0]?.media?.imagePrimary?.alt || ''} />
							) : (
								<div className="c-explore__empty">
									{__('No image selected — use the Media Content panel', 'dstheme')}
								</div>
							)}
						</div>
					</div>
				</div>

				<div className="c-explore__decoration-toggle">
					<Button
						variant="secondary"
						isPressed={!!showDecoration}
						onClick={() => setAttributes({ showDecoration: !showDecoration })}
					>
						{showDecoration
							? __('Hide skyline decoration', 'dstheme')
							: __('Show skyline decoration', 'dstheme')}
					</Button>
				</div>
			</div>
		</>
	);
};
