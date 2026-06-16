<?php
// phpcs:ignoreFile
class DS_Module_example extends DS_AbstractModule {

	protected $feature = 'example_feature';

	public $name = 'example';

	public $title = 'Example';

	protected $description = 'descriptions for gutenberg module';

	protected $category = 'ds-test';

	protected $icon = 'editor-help';

	protected $keywords = [ 'example', 'accordion', 'slider', 'image' ];

	/**
	 * If a module needs a specific library to be loaded add it in here - can be empty/removed if not used
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'example', get_template_directory_uri() . '/assets/vendors/example/example.min.css' );
		wp_enqueue_script( 'example', get_template_directory_uri() . '/assets/vendors/example/example.min.js', array(), true, true );
	}

    /**
     * Register ACF group field as migrated php array
     */
	public function add_acf_fields(): void{
	}

	/**
	 * Register ACF group field for gutenberg block - can be empty/removed if not used
	 *
	 * In some cases a field show be hidden for non-super admin user so additional argument
	 * should be passed like for Layout Settings in the example below
	 */
	public function add_composer_fields(): array {
		return [
			DS_Field::accordion( 'example_content_ac', [ 'label' => 'Example Content' ] ),
			DS_Field::repeater(
                'example_content',
                [
					'label'        => 'Example Content',
					'button_label' => 'Add Example',
					'layout'       => 'block',
					'sub_fields'   => [
						DS_Field::image( 'main_image', [ 'label' => 'Main Image' ] ),
						DS_Field::image( 'front_image', [ 'label' => 'Front Image' ] ),
						DS_Field::text( 'title', [ 'label' => 'Title' ] ),
						DS_Field::wysiwyg( 'description', [ 'label' => 'Description' ] ),
						DS_Field_CTAList::get(),
					],
                ]
            ),
			DS_Field::tab(
                'layout_settings_tab',
                [
					'label'     => 'Layout Settings',
					'placement' => 'left',
                ],
                true
            ),
			DS_Field::group(
                'layout_settings',
                [
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field_Container::get(),
						DS_Field_Columns::get( 5, 'columns', [ 'label' => 'Columns' ] ),
						DS_Field_ComponentGap::get( 'card_gap', [ 'label' => 'Gap (px)' ] ),
						DS_Field_ModuleGap::get(),
					],
                ],
                true
            ),
		];
	}
}
