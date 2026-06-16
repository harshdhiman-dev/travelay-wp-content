const { select, subscribe, dispatch } = wp.data;

class DSPageTemplateSwitcher {

	templatesAllowedPostBlocks = ['templates/template-simple-text.php'];
	disabledCategories = ['yoast-structured-data-blocks']; //disabled for all templates
	disabledBlocks = ['core/cover', 'yoast/how-to-block', 'yoast/faq-block']; //disabled for all templates

	replaceMap = {
		'default': {
			'core/paragraph': 'acf/wysiwyg',
			'core/heading': 'acf/content-simple-1',
		},
		'templates/template-simple-text.php': {
			'acf/wysiwyg': 'core/paragraph',
			'acf/content-simple-1': 'core/heading',
		},
	};

	constructor () {
		this.template = null;
		this.initCategories = null;
		this.initBlocks = null;
	}

	init () {

		subscribe( () => {

			const newTemplate = select( 'core/editor' ).getEditedPostAttribute( 'template' );

			if ( newTemplate !== undefined && this.template === null ) {
				this.template = newTemplate;
			}

			if ( newTemplate !== undefined && newTemplate !== this.template ) {
				this.template = newTemplate;
				this.initCategories = this.initCategories || select( 'core/blocks' ).getCategories();
				this.initBlocks = this.initBlocks || select( 'core/blocks' ).getBlockTypes();
				this.changeAllowedBlocks();
			}

		} );
	}

	changeAllowedBlocks () {
		let allowedBlocks = [];
		let allowedCategories = [];

		if ( -1 === this.templatesAllowedPostBlocks.indexOf( this.template ) ) {
			//do for default
			this.initCategories.forEach( ( category ) => {
				if ( -1 !== this.disabledCategories.indexOf( category.slug ) ) {
					return;
				}

				if ( -1 !== category.slug.indexOf( 'ds-' ) ) {
					allowedCategories.push( category );
				}
			} );

			this.initBlocks.forEach( ( blockType ) => {
				if ( -1 !== this.disabledBlocks.indexOf( blockType.name ) ) {
					return;
				}

				if ( -1 !== blockType.name.indexOf( 'acf' ) || -1 !== blockType.name.indexOf( 'core/block' ) ) {
					allowedBlocks.push( blockType );
				}
			} );
		} else {
			//do for wrapper template
			this.initCategories.forEach( ( category ) => {
				if ( -1 !== this.disabledCategories.indexOf( category.slug ) ) {
					return;
				}

				if ( -1 !== category.slug.indexOf( 'ds-separators' ) || -1 === category.slug.indexOf( 'ds-' ) ) {
					allowedCategories.push( category );
				}
			} );

			this.initBlocks.forEach( ( blockType ) => {
				if ( -1 !== this.disabledBlocks.indexOf( blockType.name ) ) {
					return;
				}

				if ( -1 !== blockType.name.indexOf( 'list' ) || -1 !== blockType.name.indexOf( 'cta' ) || -1 === blockType.name.indexOf( 'acf' ) ) {
					allowedBlocks.push( blockType );
				}
			} );
		}

		let currentSettings = select( 'core/editor' ).getEditorSettings();
		//important! need to use currentSettings.allowedBlockTypes initial array/object-array
		currentSettings.allowedBlockTypes.splice( 0, currentSettings.allowedBlockTypes.length );

		allowedBlocks.forEach( ( blockType ) => {
			currentSettings.allowedBlockTypes.push( blockType.name );
		} );

		if ( allowedCategories.length && currentSettings.allowedBlockTypes.length && allowedBlocks.length ) {

			dispatch( 'core/editor' ).updateEditorSettings( currentSettings );//set allowed types
			dispatch( 'core/blocks' ).setCategories( allowedCategories );//set categories
			dispatch( 'core/blocks' ).addBlockTypes( allowedBlocks );//set blocks

		}

		let newSettings = select( 'core/editor' ).getEditorSettings();
		let blocksToRemove = [];

		let blocksToReplace = [];
		let blocksForReplace = [];

		let templateName = this.template.length === 0 ? 'default' : this.template;

		const editorBlocks = select( 'core/editor' ).getEditorBlocks();

		editorBlocks.forEach( ( block ) => {
			if ( this.replaceMap[templateName].hasOwnProperty( block.name ) ) {// name is key
				blocksToReplace.push( block.clientId );//copy to  replace
				let newBlock = wp.blocks.createBlock( this.replaceMap[templateName][block.name],
					this.serializeData( templateName, block.name, block.attributes ), block.innerBlocks );// getBlockType, create new block and add to replacements
				blocksForReplace.push( newBlock );

			} else if ( -1 === newSettings.allowedBlockTypes.indexOf( block.name ) ) {
				blocksToRemove.push( block.clientId );
			}
		} );

		//replace
		blocksToReplace.forEach( ( clientId, index ) => {
			dispatch( 'core/block-editor' ).replaceBlock( clientId, blocksForReplace[index] );
		});

		//remove
		dispatch( 'core/block-editor' ).removeBlocks( blocksToRemove, false );

	}

	serializeData( template, type, attr) {
		let dataAttr = {};

		if ( template === 'default' ) {
			dataAttr.data = {};
			switch ( type ) {
				case 'core/paragraph':
					dataAttr.data.field_group_wysiwyg_editor = attr.content;
					break;
				case 'core/heading':
					dataAttr.data['field_group_content-simple-1_title'] = attr.content;
					break;
			}
		}

		if ( template === 'templates/template-simple-text.php' ) {
			switch ( type ) {
				case 'acf/wysiwyg':
					dataAttr.content = attr.data.field_group_wysiwyg_editor;
					break;
				case 'acf/content-simple-1':
					dataAttr.content = attr.data['field_group_content-simple-1_title'];
					break;
			}
		}

		return dataAttr;
	}
}

new DSPageTemplateSwitcher().init();