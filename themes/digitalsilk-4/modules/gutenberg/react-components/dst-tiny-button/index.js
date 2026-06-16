/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	RichText,
	LinkControl,
} from '@wordpress/block-editor';
import { Popover } from '@wordpress/components';
import { DstIconPicker } from '../icon-picker';
import { allowedFormats, useHandleDefaultIcons } from './utilities';
import { ButtonToolbar } from './toolbar';
import { ButtonInspector } from './inspector';
import classNames from 'classnames';

export const DstTinyButton = (
    {
        value = {},
        onChange,
        clientId,
        showToolbars = true,
        showInspectorControls = true,
        readOnly = false,
    }
) => {
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
	} = value;

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

    // Check if this block is selected
    const isSelected = useSelect(
        (select) => select('core/block-editor').getSelectedBlockClientId() === clientId,
        [clientId]
    );


	// Ensure link is an object or null
	const linkValue = link && link.url ? link : null;

	useHandleDefaultIcons(
        {
            value,
            onChange
        }
    );

	return (
		<>
            {
                showInspectorControls && (
                    <ButtonInspector
                        value={value}
                        onChange={onChange}
                    />
                )
            }
            {
                showToolbars && (
                    <ButtonToolbar
                        value={value}
                        onChange={onChange}
                    />
                )
            }
            <span
                className={additionalClasses}
                ref={buttonRef}
            >
                {
                    readOnly ? (
                        <span className='c-btn__txt'>
                            {text}
                        </span>
                    ) : (
                        <RichText
                            placeholder={__('Add text...')}
                            className='c-btn__txt'
                            value={text}
                            onChange={
                                (newText) => {
                                    onChange(
                                        {
                                            ...value,
                                            text: newText
                                        }
                                    )
                                }
                            }
                            tagName="span"
                            allowedFormats={allowedFormats()}
                        />
                    )
                }
                    {
                        hasIcon && (
                            <DstIconPicker
                                icon={iconValue}
                                onChange={(newIcon) => {
                                    onChange(
                                        {
                                            ...value,
                                            iconValue: newIcon
                                        }
                                    );
                                }}
                                iconSet = { ['theme', 'buttons'] }
                                placeholder={__('Icon')}
                                disabled={iconType === 'default'}
                                className={`c-btn__ico ${iconRevesed && iconValue ? 'icon-reversed' : ''}`}
                            />
                        )
                    }
            </span>

			{ ( isSelected && ! readOnly && showInspectorControls && showToolbars ) && (
				<Popover
					position="bottom center"
					anchor={buttonRef.current}
				>
					<LinkControl
						key={clientId}
						value={linkValue}
						onChange={(newLink) => {
                            onChange(
                                {
                                    ...value,
                                    link: newLink
                                }
                            );
						}}
						onRemove={() => {
                            onChange(
                                {
                                    ...value,
                                    link: null
                                }
                            );
						}}
						settings={[
							{
								id: 'opensInNewTab',
								title: __('Open in new tab'),
								isShownByDefault: true,
							},
							{
								id: 'nofollow',
								title: __('Mark as nofollow'),
								isShownByDefault: true,
							},
						]}
					/>
				</Popover>
			)}
		</>
	);
};
