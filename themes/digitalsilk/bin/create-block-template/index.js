/**
 * External dependencies
 */
const { join } = require('path');

module.exports = {
	defaultValues: {
		slug: 'example-block',
		category: 'text',
		title: 'Example Block',
		description: 'Example Block',
		attributes: {},
		supports: {
			html: false,
		},
		customBlockJSON: {
			textdomain: 'dstheme',
		},
		namespace: 'ds-blocks',
		wpScripts: true,
		wpEnv: false,
		version: false,
		folderName: './modules/gutenberg/blocks/custom/example-block',
		render: 'file:./render.php',
		editorStyle: false,
		style: 'file:./style-index.css',
		example: {},
		apiVersion: 3,
	},
	variants: {
		default: {},
		innerBlocks: {},
		withViewScript: {
			viewScript: 'file:./view.js',
		},
	},
	blockTemplatesPath: join(__dirname, 'block-templates'),
};
