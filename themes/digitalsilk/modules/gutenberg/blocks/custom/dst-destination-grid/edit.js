/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { plus, trash } from '@wordpress/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { DstMedia } from '../../../react-components';

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
				{items.map((item, index) => (
					<div
						className={classNames('c-destination-grid__item', `-item-${index + 1}`)}
						key={index}
					>
						<div className="c-destination-grid__placeholder">
							<DstMedia
								value={item.media}
								onChange={(newMedia) => updateItem(index, 'media', newMedia)}
								panelOpened={false}
							/>
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
				))}
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
	);
};
