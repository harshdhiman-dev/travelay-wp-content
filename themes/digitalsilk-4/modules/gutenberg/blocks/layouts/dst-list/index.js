import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { list } from '@wordpress/icons';
import './style.scss';

/**
 * Internal dependencies
 */
import Edit from './edit.js';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 */
registerBlockType( metadata.name, {
	edit: Edit,
	icon: list,
	save: () => <InnerBlocks.Content />,
} );
