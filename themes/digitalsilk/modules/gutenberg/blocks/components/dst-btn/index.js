import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import './editor.scss';

import { BlockEdit } from './edit';
import metadata from './block.json';

registerBlockType(metadata.name, {
	edit: BlockEdit,
	save: () => <InnerBlocks.Content />,
});
