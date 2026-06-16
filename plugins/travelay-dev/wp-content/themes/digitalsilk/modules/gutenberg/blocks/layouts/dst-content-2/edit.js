/**
 * WordPress dependencies
 */
import { useRef } from '@wordpress/element';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { ResizableBox } from '@wordpress/components';
import { DstMedia } from '../../../react-components';
import { Content2InspectorControls } from './inspector';
import classNames from 'classnames';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		media,
		columnsOrder,
		columnsOrderMobile,
		columnsGap,
		contentRatio,
		isVertical,
		textPaddingLeftDesktop,
		textPaddingLeftMobile,
		textPaddingRightDesktop,
		textPaddingRightMobile,
		textYAlign,
		mediaXAlign,
		mediaYAlign,
	} = attributes;

	// Block wrapper classes
	const topBlockClasses = classNames(
		'm-block  m-dcbl',
		wrapperProps?.className
	);

	const blockProps = useBlockProps({
		...wrapperProps,
		className: topBlockClasses,
	});

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'c-block__inner block-editor-block-list__layout',
		},
		{
			template: [
				['ds-blocks/c-heading', {}],
				['ds-blocks/button-group', {}],
			],
		}
	);

	// Resizable box requires a ref to the container to calculate width.
	const containerRef = useRef();
	const containerWidth = containerRef.current?.offsetWidth || 1000;
	const textColumnWidth = contentRatio
		? Math.round((contentRatio / 100) * containerWidth)
		: Math.round(containerWidth / 2);

	// Set resize box direction
	let resizeBoxDirecton = columnsOrder === 'order-reverse' ? { left: true } : { right: true };
	if ( isVertical ) {
		resizeBoxDirecton = {};
	}

	// Set c-block styles and classes.
	const cBlockStyles = {
		'--columns-gap': columnsGap || '0px',
	};
	const cBlockClasses = classNames(
		'c-block',
		{
			'is-vertical': isVertical,
			[columnsOrder]: columnsOrder,
			[columnsOrderMobile]: columnsOrderMobile && columnsOrderMobile !== '',
		}
	);

	// Set c-block__text styles and classes.
	const cBlockTextClasses = classNames(
		'c-block__text',
		{
			[textYAlign]: textYAlign,
		}
	);
	const cBlockTextStyles = {
		'--space-left': textPaddingLeftDesktop || undefined,
		'--space-left-m': textPaddingLeftMobile || undefined,
		'--space-right': textPaddingRightDesktop || undefined,
		'--space-right-m': textPaddingRightMobile || undefined,
	};

	// Set c-block__media styles and classes.
	const cBlockMediaClasses = classNames(
		'c-block__media',
		{
			[mediaXAlign]: mediaXAlign,
			[mediaYAlign]: mediaYAlign,
		}
	);

	return (
		<>
			<Content2InspectorControls blockProps={props} />
			<div {...blockProps}>
				<div className="m-block__container">
					<div className="l-dcbl">
						<div className={cBlockClasses} style={cBlockStyles} ref={containerRef}>
							<ResizableBox
								size={{ width: textColumnWidth }}
								minWidth={100}
								maxWidth={containerWidth - 100}
								enable={resizeBoxDirecton}
								onResizeStop={(event, direction, elt) => {
									const newWidth = elt.offsetWidth;
									const newRatio = Math.round((newWidth / containerRef.current.offsetWidth) * 100);
									setAttributes({ contentRatio: newRatio });
								}}
								className={cBlockTextClasses}
								style={cBlockTextStyles}
							>
								<div {...innerBlocksProps} />
							</ResizableBox>

							<div className={cBlockMediaClasses}>
								<DstMedia
									value={media}
									onChange={(newMedia) => setAttributes({ media: newMedia })}
									panelOpened={false}
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
