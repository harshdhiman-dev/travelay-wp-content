<?php
/**
 * Navigation Module Class
 */
class DS_Module_Navigation extends DS_AbstractModule {

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name = 'navigation';

	/**
	 * Title
	 *
	 * @var string
	 */
	public $title = 'Side Navigation';

	/**
	 * Desc
	 *
	 * @var string
	 */
	protected $description = 'Side navigation with anchor links or links';

	/**
	 * Category
	 *
	 * @var string
	 */
	protected $category = 'ds-widgets';

	/**
	 * Icon
	 *
	 * @var string
	 */
	protected $icon = 'admin-links';

	/**
	 * Keywords
	 *
	 * @var string
	 */
	protected $keywords = array( 'navigation', 'side', 'link', 'anchor' );

	/**
	 * Add Composer Fields
	 */
	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_ac', array( 'label' => 'Content' ) ),
			DS_Field::repeater(
				'anchor_navigation',
				array(
					'label'        => 'Anchor Navigation',
					'button_label' => 'Add Nav Item',
					'layout'       => 'block',
					'sub_fields'   => array(
						DS_Field::text( 'label', array( 'label' => 'Label' ) ),
						DS_Field::radio(
							'type',
							array(
								'label'         => 'Type',
								'choices'       => array(
									'block' => 'Block',
									'link'  => 'Link',
								),
								'default_value' => 'block',
								'layout'        => 'horizontal',
							)
						),
						DS_Field_ContentAvailableBlocks::get(
							'anchor_available_blocks',
							array(
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'block',
										),
									),
								),
							)
						),
						DS_Field::link(
							'link',
							array(
								'label'             => 'Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'link',
										),
									),
								),
							)
						),
					),
				)
			),
			DS_Field::accordion( 'advanced_settings_ac', array( 'label' => 'Advanced Settings' ) ),
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
						DS_Field::button_group(
							'style',
							array(
								'label'         => 'Style',
								'choices'       => array(
									'v1' => 'v1',
									'v2' => 'v2',
									'v3' => 'v3',
								),
								'default_value' => 'v1',
							)
						),
						DS_Field::button_group(
							'orientation',
							array(
								'label'         => 'Orientation',
								'choices'       => array(
									'vertical'   => 'vertical',
									'horizontal' => 'horizontal',
								),
								'default_value' => 'vertical',
							)
						),
						DS_Field::button_group(
							'position',
							array(
								'label'         => 'Position',
								'choices'       => array(
									'left'  => 'left',
									'right' => 'right',
								),
								'default_value' => 'left',
							)
						),
						DS_Field::true_false(
							'add_icon',
							array(
								'label' => 'Add Icon',
								'ui'    => 1,
							)
						),
						DS_Field::button_group(
							'icon_position',
							array(
								'label'             => 'Icon Position',
								'choices'           => array(
									'left'  => 'left',
									'right' => 'right',
								),
								'default_value'     => 'left',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'add_icon',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::image(
							'icon',
							array(
								'label'             => 'Icon',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'add_icon',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
					),
				),
				true
			),
		);
	}
}
