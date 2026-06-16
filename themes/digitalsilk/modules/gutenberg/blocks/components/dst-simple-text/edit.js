/**
 * WordPress dependencies
 */
import { useBlockProps, InnerBlocks, useInnerBlocksProps } from '@wordpress/block-editor';
import classNames from 'classnames';

export const BlockEdit = ( props ) => {
	const { wrapperProps } = props;
	
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames( wrapperProps?.className, 'dst-simple-text' ),
		}
	);
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'dst-simple-text__inner is-wysiwyg'
		},
		{
			allowedBlocks: [
				'core/paragraph',
				'core/list',
				'core/heading',
				'core/quote',
				'core/table',
				'core/freeform',
			],
			template: [
				['core/paragraph', { placeholder: 'Enter your text here...' }],
			],
			renderAppender: () => (
				<div className="ds-simple-text-appender">
					<InnerBlocks.DefaultBlockAppender />
				</div>
			),
		}
	);

	return (
		<div {...blockProps}>
			<div {...innerBlocksProps} />
		</div>
	);
};
