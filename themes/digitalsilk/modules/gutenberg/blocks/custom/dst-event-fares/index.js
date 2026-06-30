import { registerBlockType } from '@wordpress/blocks';
import { BlockEdit } from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: BlockEdit,
	save: () => null,
} );
