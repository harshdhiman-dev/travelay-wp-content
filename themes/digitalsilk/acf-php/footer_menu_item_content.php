<?php

if ( function_exists( 'acf_add_local_field_group' ) ) :
	acf_add_local_field_group(
		array(
			'key'                   => 'group_6112b11b748ab',
			'title'                 => 'Footer Menu Item Content',
			'fields'                => array(
				array(
					'key'               => 'field_6112b15e127ac',
					'label'             => 'Icon',
					'name'              => 'icon',
					'type'              => 'image',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'ds_asset_type'     => '',
					'return_format'     => 'array',
					'preview_size'      => 'medium',
					'library'           => 'all',
					'min_width'         => '',
					'min_height'        => '',
					'min_size'          => '',
					'max_width'         => '',
					'max_height'        => '',
					'max_size'          => '',
					'mime_types'        => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/footer-menu',
					),
				),
				array(
					array(
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/footer-menu-2',
					),
				),
				array(
					array(
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/footer-menu-3',
					),
				),
				array(
					array(
						'param'    => 'nav_menu_item',
						'operator' => '==',
						'value'    => 'location/footer-menu-4',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
		)
	);
endif;
