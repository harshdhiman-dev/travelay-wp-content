/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import { BlockEdit } from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: BlockEdit,
	save: () => null,
} );
