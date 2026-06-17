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
	const { heading, items, background } = attributes;

	/**
	 * Build the live preview inline style for the background, mirrored on the frontend in render.php.
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

	const blockProps = useBlockProps({
		...wrapperProps,
		className: classNames(wrapperProps?.className, 'c-destination-grid'),
		style: { ...wrapperProps?.style, ...backgroundStyle },
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
	 * Update a single field of a single item.
	 *
	 * @param {number} index Item index.
	 * @param {string} key   Item attribute key.
	 * @param {*}      value New value.
	 */
	const updateItem = (index, key, value) => {
		const newItems = items.map((item, i) => (i === index ? { ...item, [key]: value } : item));
		setAttributes({ items: newItems });
	};

	/**
	 * Update an item's media from a selected attachment.
	 *
	 * @param {number} index Item index.
	 * @param {Object} media Selected media object from MediaUpload.
	 */
	const updateItemMedia = (index, media) => {
		updateItem(index, 'media', {
			primaryType: 'image',
			imagePrimary: {
				id: media?.id || '',
				url: media?.url || '',
				alt: media?.alt || '',
				size: 'full',
			},
		});
	};

	/**
	 * Remove an item's media.
	 *
	 * @param {number} index Item index.
	 */
	const removeItemMedia = (index) => {
		updateItem(index, 'media', {});
	};

	/**
	 * Append a new empty item to the grid.
	 */
	const addItem = () => {
		setAttributes({
			items: [...items, { media: {}, label: __('New destination', 'dstheme') }],
		});
	};

	/**
	 * Remove an item from the grid.
	 *
	 * @param {number} index Item index to remove.
	 */
	const removeItem = (index) => {
		setAttributes({ items: items.filter((_, i) => i !== index) });
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
							<div className="c-destination-grid__panel-item">
								<p className="c-destination-grid__panel-label">
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
								<div className="c-destination-grid__panel-item">
									<p className="c-destination-grid__panel-label">
										{__('Image', 'dstheme')}
									</p>
									<div className="c-destination-grid__panel-controls">
										{background?.image?.url && (
											<img
												className="c-destination-grid__panel-preview"
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
								<div className="c-destination-grid__panel-item">
									<p className="c-destination-grid__panel-label">
										{__('Mobile Image', 'dstheme')}
									</p>
									<div className="c-destination-grid__panel-controls">
										{background?.imageMobile?.url && (
											<img
												className="c-destination-grid__panel-preview"
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
								<div className="c-destination-grid__panel-item">
									<p className="c-destination-grid__panel-label">
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

				<PanelBody title={__('Media Content', 'dstheme')} initialOpen={true}>
					{items.map((item, index) => {
						const imageUrl = item?.media?.imagePrimary?.url || '';
						const imageId = item?.media?.imagePrimary?.id || undefined;

						return (
							<PanelRow key={index} className="c-destination-grid__panel-row">
								<div className="c-destination-grid__panel-item">
									<p className="c-destination-grid__panel-label">
										{__('Image', 'dstheme')} {index + 1}
										{item.label ? ` — ${item.label}` : ''}
									</p>

									<div className="c-destination-grid__panel-controls">
										{imageUrl && (
											<img
												className="c-destination-grid__panel-preview"
												src={imageUrl}
												alt=""
											/>
										)}

										<MediaUploadCheck>
											<MediaUpload
												onSelect={(media) => updateItemMedia(index, media)}
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
											<Button
												variant="tertiary"
												isDestructive
												onClick={() => removeItemMedia(index)}
											>
												{__('Remove', 'dstheme')}
											</Button>
										)}
									</div>
								</div>
							</PanelRow>
						);
					})}
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				{background?.type === 'image' && background?.image?.url && background?.overlayOpacity > 0 && (
					<span
						className="c-destination-grid__bg-overlay"
						aria-hidden="true"
						style={{
							backgroundColor: background?.overlayColor || '#000000',
							opacity: (background?.overlayOpacity || 0) / 100,
						}}
					/>
				)}

				<div className="c-destination-grid__heading">
					<div className="c-destination-grid__heading-controls">
						<Button
							variant="secondary"
							isPressed={!!heading.showDecoration}
							onClick={() => updateHeading('showDecoration', !heading.showDecoration)}
						>
							{heading.showDecoration
								? __('Hide decoration', 'dstheme')
								: __('Show decoration', 'dstheme')}
						</Button>
					</div>

					{heading.showDecoration && (
						<span className="c-destination-grid__decoration" aria-hidden="true">
							<img
								src="https://www.flytravelay.com/wp-content/uploads/2026/06/Group-219.png"
								alt=""
							/>
						</span>
					)}

					<RichText
						tagName="h2"
						className="c-destination-grid__title"
						value={heading.title}
						onChange={(value) => updateHeading('title', value)}
						placeholder={__('Enter heading…', 'dstheme')}
						allowedFormats={[]}
					/>
				</div>

				<div className="c-destination-grid__items">
					{items.map((item, index) => {
						const imageUrl = item?.media?.imagePrimary?.url || '';

						return (
							<div
								className={classNames('c-destination-grid__item', `-item-${index + 1}`)}
								key={index}
							>
								<div className="c-destination-grid__placeholder">
									{imageUrl ? (
										<img src={imageUrl} alt={item?.media?.imagePrimary?.alt || ''} />
									) : (
										<div className="c-destination-grid__empty">
											{__('No image selected — use the Media Content panel', 'dstheme')}
										</div>
									)}
								</div>

								<RichText
									tagName="span"
									className="c-destination-grid__label"
									value={item.label}
									onChange={(value) => updateItem(index, 'label', value)}
									placeholder={__('Label…', 'dstheme')}
									allowedFormats={[]}
								/>

								{items.length > 1 && (
									<Button
										className="c-destination-grid__remove"
										icon={trash}
										label={__('Remove item', 'dstheme')}
										onClick={() => removeItem(index)}
									/>
								)}
							</div>
						);
					})}
				</div>

				<Button
					variant="primary"
					icon={plus}
					onClick={addItem}
					className="c-destination-grid__add"
				>
					{__('Add destination', 'dstheme')}
				</Button>
			</div>
		</>
	);
};