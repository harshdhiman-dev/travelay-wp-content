<?php
// phpcs:ignoreFile
class DS_Module_column extends DS_AbstractModule {

	public $name = 'column';

	public $title = 'Column container (OLD)';

	protected $description = 'Simple wrapper, can be used as column inside wrapper module';

	protected $category = 'ds-layouts';

	protected $icon = 'columns';

	protected $keywords = array( 'content', 'column', 'wrapper' );
	protected bool $supportInnerBlocks = true;
}
