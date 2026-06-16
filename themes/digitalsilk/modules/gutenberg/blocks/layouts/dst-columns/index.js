import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
// eslint-disable-next-line import/no-extraneous-dependencies
import { columns as icon } from '@wordpress/icons';
import './style.scss';
import './editor.scss';

import { BlockEdit } from './edit';
import variations from './variations';
import metadata from './block.json';

registerBlockType(metadata, {
	icon,
	variations,
	edit: BlockEdit,
	save: () => <InnerBlocks.Content />,
});
