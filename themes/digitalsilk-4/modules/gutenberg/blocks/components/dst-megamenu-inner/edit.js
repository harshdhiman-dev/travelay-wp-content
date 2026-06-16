/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import classnames from 'classnames';

export const BlockEdit = (props) => {
	const { attributes, setAttributes } = props;
	const blockProps = useBlockProps({
		className: classnames('megamenu__inner-wrapper'),
	});
	const innerBlocksProps = useInnerBlocksProps({}, {});

	return (
		<div {...blockProps}>
			<div {...innerBlocksProps} />
		</div>
	);
};
