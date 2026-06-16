/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { DstMedia } from '../../../react-components';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { media } = attributes;
	const blockProps = useBlockProps(
		{
			...wrapperProps
		}
	);
	const hasMedia = media && media?.primaryType;

	return (
		<div {...blockProps}>
			<DstMedia
				value={media}
				onChange={(newMedia) => setAttributes({ media: newMedia })}
				panelOpened={hasMedia}
			/>
		</div>
	);
};
