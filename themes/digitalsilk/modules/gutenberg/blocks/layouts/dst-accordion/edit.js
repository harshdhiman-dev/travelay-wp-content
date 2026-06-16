/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';
import classNames from 'classnames';

export const BlockEdit = (props) => {
	const { wrapperProps } = props;
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames(
				'dst-accordion',
				wrapperProps?.className
			),
		}
	);
	const innerBlocksProps  = useInnerBlocksProps(
		{
			className: 'block-editor-block-list__layout dst-accordion__inner',
		},
		{
			template: [
				[ 'ds-blocks/c-accordion-item', {} ],
				[ 'ds-blocks/c-accordion-item', {} ],
				[ 'ds-blocks/c-accordion-item', {} ],
			],
			allowedBlocks: ["ds-blocks/c-accordion-item"],
			renderAppender: () => <InnerBlocks.ButtonBlockAppender />,
		}
	);

	return (
		<div {...blockProps}>
			<div {...innerBlocksProps} />
		</div>
	);
};
