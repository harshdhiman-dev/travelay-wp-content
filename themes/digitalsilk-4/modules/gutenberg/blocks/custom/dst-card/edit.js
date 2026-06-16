/* eslint-disable jsx-a11y/no-static-element-interactions */
/* eslint-disable jsx-a11y/click-events-have-key-events */
/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { DstTinyHeading, DstTinyButton, DstMedia } from '../../../react-components'
import classNames from 'classnames';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps, clientId } = props;
	const { heading, button, media } = attributes;
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames( wrapperProps?.className, 'c-card' ),
		}
	);

	const [ componentSelected, setComponentSelected ] = useState( 'media' );

	return (
		<article {...blockProps}>
			<div
				className="c-card__media"
				onClick={() => setComponentSelected('media')}
			>
				<DstMedia
					value={media}
					onChange={(newValue) => setAttributes({ media: newValue })}
					showToolbars={ 'media' === componentSelected }
					showInspectorControls={ 'media' === componentSelected }
				/>
			</div>
			<div className="c-card__content" >
				<div className="c-card__title" onClick={() => setComponentSelected('heading')}>
					<DstTinyHeading
						value={heading}
						onChange={(newValue) => setAttributes({ heading: newValue })}
						showToolbars={ 'heading' === componentSelected }
						showInspectorControls={'heading' === componentSelected}
						readOnly={false}
					/>
				</div>
				<div className="c-card__btn" onClick={() => setComponentSelected('button')}>
					<DstTinyButton
						value={button}
						onChange={(newValue) => setAttributes({ button: newValue })}
						clientId={clientId}
						showToolbars={ 'button' === componentSelected }
						showInspectorControls={ 'button' === componentSelected }
						readOnly={false}
					/>
				</div>
			</div>
		</article>
	);
};
