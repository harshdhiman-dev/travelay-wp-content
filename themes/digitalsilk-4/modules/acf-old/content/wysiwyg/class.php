<?php
// phpcs:ignoreFile

class DS_Module_wysiwyg extends DS_AbstractModule {

	public $name = 'wysiwyg';

	public $title = 'WYSIWYG Content Editor';

	protected $description = 'WYSIWYG Content Editor, adding simple content block to the page';

	protected $category = 'ds-content';

	protected $icon = 'editor-paste-word';

	protected $keywords = array( 'wysiwyg', 'editor', 'content', 'text' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'wysiwyg_ac', array( 'label' => 'WYSIWYG' ) ),
			DS_Field::wysiwyg( 'editor', array( 'label' => 'Editor' ) ),
		);
	}
}
