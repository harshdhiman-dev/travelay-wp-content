/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InnerBlocks, RichText } from '@wordpress/block-editor';

export const BlockEdit = (props) => {
	const { attributes, setAttributes } = props;
	const { title } = attributes;
	const blockProps = useBlockProps(
		{
			className: 'dst-accordion__item',
		}
	);
	const innerBlocksProps  = useInnerBlocksProps(
		{
			className: 'block-editor-block-list__layout dst-accordion__content',
		},
		{
			template: [
				[
					'core/paragraph', {
						content: __('This is the content of the accordion item. You can add any blocks you like here.'),
					}
				],
			],
			renderAppender: () => <InnerBlocks.ButtonBlockAppender />,
		}
	);

	return (
		<details {...blockProps}>
			<summary className="dst-accordion__title">
				<RichText
					tagName="span"
					value={title}
					onChange={(value) => setAttributes({ title: value })}
					placeholder={__('Please enter a title for this accordion item')}
				/>
			</summary>
			<div {...innerBlocksProps} />
		</details>
	);
};
