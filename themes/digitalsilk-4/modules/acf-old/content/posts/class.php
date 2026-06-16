<?php
// phpcs:ignoreFile

class DS_Module_posts extends DS_AbstractModule {

	public $name = 'posts';

	public $title = 'Posts';

	protected $description = 'Listing of blog posts';

	protected $category = 'ds-content';

	protected $icon = 'images-alt';

	protected $keywords = array( 'posts', 'grid' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'posts_content_acc', array( 'label' => 'Posts Content' ) ),
			DS_Field::button_group(
				'query_type',
				array(
					'label'         => 'Query type',
					'choices'       => array(
						'recent' => 'recent',
						'manual' => 'manual',
					),
					'default_value' => 'recent',
				)
			),
			DS_Field::relationship(
				'posts_list',
				array(
					'label'             => 'Posts',
					'post_type'         => 'post',
					'filters'           => array( 'search' ),
					'elements'          => array( 'featured_image' ),
					'return_format'     => 'id',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'query_type',
								'operator'  => '==',
								'value'     => 'manual',
							),
						),
					),
				)
			),
			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'background_styles_tab',
				array(
					'label'     => 'Background Styles',
					'placement' => 'left',
				)
			),
			DS_Field_Background::get(),
			DS_Field_ModuleDecorations::get(),
			DS_Field::tab(
				'effects_tab',
				array(
					'label'     => 'Effects',
					'placement' => 'left',
				)
			),
			DS_Field_ModuleEffects::get(),
			DS_Field::tab(
				'component_settings_tab',
				array(
					'label'     => 'Component Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field::group(
				'component_settings',
				array(
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_ComponentType::get(),
						DS_Field::true_false(
							'show_date',
							array(
								'label' => 'Show Post Date?',
								'ui'    => 1,
							)
						),
					),
				),
				true
			),
			DS_Field::tab(
				'layout_settings_tab',
				array(
					'label'     => 'Layout Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field::group(
				'layout_settings',
				array(
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_Container::get(),
						DS_Field_ComponentGap::get(),
						DS_Field_Columns::get( 6, 'card_columns', array( 'default_value' => 3 ) ),
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
