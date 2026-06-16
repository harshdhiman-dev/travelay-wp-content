/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState, useEffect } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
	RichText,
	LinkControl,
} from '@wordpress/block-editor';
import { useSelect, dispatch } from '@wordpress/data';
import { Popover, Icon } from '@wordpress/components';
import { DstIconPicker } from '../../../react-components';
import { allowedFormats, useHandleDefaultIcons } from './utilities';
import { ButtonToolbar } from './toolbar';
import { ButtonInspector } from './inspector';
import { dimboxClose } from './icons';
import classNames from 'classnames';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, clientId, wrapperProps } = props;
	const {
		text,
		link,
		btnType,
		btnSize,
		iconType,
		hasIcon,
		iconPosition,
		iconValue,
		iconRevesed,
		hasPopup,
	} = attributes;

	// Build the class names for the button.
	const additionalClasses = classNames(
		'c-btn',
		`-${btnType}`,
		`-${btnSize}`,
		`-editor-icon-type-${iconType}`,
		{
			'has-icon': hasIcon,
			'icon-right': hasIcon && iconPosition === 'row',
			'icon-left': hasIcon && iconPosition === 'row-reverse',
		}
	);

	// Anchor reference for popover positioning
	const buttonRef = useRef();
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames( wrapperProps?.className, additionalClasses ),
			ref: buttonRef,
		}
	);
	// Create our inner blocks structure, for the popup part.
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{
			className: 'dimbox-container close-on-overlay-click dimbox-loaded show wp-block-ds-blocks-c-btn__popup',
			onClick: (event) => {
				const dimboxContainer = event.currentTarget;
				const dimboxContent = event.target.closest('.dimbox-content');

				// Skip closing if clicking inside .dimbox-content or outside .dimbox-container
				if (dimboxContent || !dimboxContainer.contains(event.target)) {
					return;
				}

				setPopupOpen(false); // Close only if clicked outside .dimbox-content but inside .dimbox-container
			}
		},
		{
			template: [
				[
					'core/paragraph',
					{
						placeholder: 'Type in your description here...'
					}
				]
			],
			templateLock: false,
			renderAppender: () => (
				<InnerBlocks.DefaultBlockAppender />
			),
		}
	);

	// Check if this block is selected
	const isSelected = useSelect(
		(select) => select('core/block-editor').getSelectedBlockClientId() === clientId,
		[clientId]
	);

	// Create a popup state.
	const [ popupOpen, setPopupOpen ] = useState(false);
	const [ firstClick, setFirstClick ] = useState(false);

	// Make sure this runs once the component is selected.
	useEffect(
		() => {
			if (popupOpen) {
				// Deselect the block when the popup is opened
				dispatch('core/block-editor').clearSelectedBlock();
				setFirstClick(true);
			} else if ( firstClick ) {
				// Re-select the block when the popup is closed
				dispatch('core/block-editor').selectBlock(clientId);
			} else {
				setFirstClick(false);
			}
		},
		[ popupOpen, firstClick, setFirstClick, clientId ]
	);


	// Ensure link is an object or null
	const linkValue = link && link.url ? link : null;

	useHandleDefaultIcons({ blockProps: props });

	return (
		<>
			<ButtonInspector
				blockProps={props}
				popupOpen={popupOpen}
				setPopupOpen={setPopupOpen}
			/>
			<ButtonToolbar
				blockProps={props}
			/>

			<span
				{...blockProps}
			>
				<RichText
					placeholder={__('Add text...', 'dstheme')}
					className='c-btn__txt'
					value={text}
					onChange={(newText) => setAttributes({ text: newText })}
					tagName="span"
					allowedFormats={allowedFormats()}
				/>
				{
					hasIcon && (
						<DstIconPicker
							icon={iconValue}
							onChange={(newIcon) => setAttributes({ iconValue: newIcon })}
							iconSet = { ['theme', 'buttons'] }
							placeholder={__('Icon')}
							disabled={iconType === 'default'}
							className={`c-btn__ico ${iconRevesed && iconValue ? 'icon-reversed' : ''}`}
						/>
					)
				}
			</span>

			{ isSelected && (
				<Popover
					position="bottom center"
					anchor={buttonRef.current}
				>
					<LinkControl
						key={clientId}
						value={linkValue}
						onChange={(newLink) => {
							setAttributes({ link: newLink });
						}}
						onRemove={() => {
							setAttributes({ link: null });
						}}
						settings={[
							{
								id: 'opensInNewTab',
								title: __('Open in new tab', 'dstheme'),
								isShownByDefault: true,
							},
							{
								id: 'nofollow',
								title: __('Mark as nofollow', 'dstheme'),
								isShownByDefault: true,
							},
						]}
					/>
				</Popover>
			)}
			{
				hasPopup && popupOpen && (
					<div {...innerBlocksProps}>
						<div className='dimbox-content'>
							<div className="dimbox-inline-content">
								{ children }
							</div>
							<div className="dimbox-buttons">
								<button
									className="dimbox-btn-close"
									onClick={() => setPopupOpen(false)}
								>
									<Icon icon={dimboxClose} />
								</button>
							</div>
						</div>
					</div>
				)
			}
		</>
	);
};
