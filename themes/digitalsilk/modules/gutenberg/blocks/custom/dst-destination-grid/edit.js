/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText, InspectorControls, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, PanelBody, PanelRow } from '@wordpress/components';
import { plus, trash, upload, image as imageIcon } from '@wordpress/icons';
import classNames from 'classnames';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { heading, items } = attributes;

	const blockProps = useBlockProps({
		...wrapperProps,
		className: classNames(wrapperProps?.className, 'c-destination-grid'),
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

	return (
		<>
			<InspectorControls>
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