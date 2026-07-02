import { registerBlockType } from '@wordpress/blocks';
import { BlockEdit } from './edit';
import metadata from './block.json';

import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: BlockEdit,
	save: () => null,
} );
