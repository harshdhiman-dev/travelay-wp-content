import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
// eslint-disable-next-line import/no-extraneous-dependencies
import { column as icon } from '@wordpress/icons';
import './style.scss';

import { BlockEdit } from './edit';
import metadata from './block.json';

registerBlockType(metadata, {
	icon,
	edit: BlockEdit,
	save: () => <InnerBlocks.Content />,
});
