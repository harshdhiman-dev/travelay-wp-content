import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import {
	ToolbarGroup,
	ToolbarButton,
    ToolbarDropdownMenu,
	Flex,
    Icon,
} from '@wordpress/components';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
    resizeCornerNE,
    lock,
    unlock,
} from '@wordpress/icons';
import { capitalizeFirstLetter } from './utilities';

export const ButtonToolbar = (
    {
        value = {},
        onChange,
    }
) => {
    const { btnType, btnSize, iconType } = value;

    return (
        <>
            { /* Inline block controls */}
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarDropdownMenu
                        icon={ () => (
                            <Flex gap={1}>
                                <Icon icon='admin-appearance' />
	                            <span>{capitalizeFirstLetter( btnType )}</span>
                            </Flex>

                        ) }
                        label={__( 'Button Style' )}
                        controls={ [
                            {
                                title: __( 'Primary' ),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnType: 'primary'
                                    }
                                ),
                                isDisabled: btnType === 'primary',
                            },
                            {
                                title: __( 'Secondary' ),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnType: 'secondary'
                                    }
                                ),
                                isDisabled: btnType === 'secondary',
                            },
                            {
                                title: __( 'Link' ),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnType: 'link'
                                    }
                                ),
                                isDisabled: btnType === 'link',
                            },
                        ] }
                    />
                </ToolbarGroup>
	            <ToolbarGroup>
                    <ToolbarDropdownMenu
	                    icon={ () => (
		                    <Flex gap={1}>
			                    <Icon icon={resizeCornerNE} />
			                    <span>{capitalizeFirstLetter( btnSize )}</span>
		                    </Flex>
	                    ) }
                        label={__('Size')}
                        controls={ [
                            {
                                title: __('Small'),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnSize: 'small'
                                    }
                                ),
                                isDisabled: btnSize === 'small',
                            },
                            {
                                title: __('Default'),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnSize: 'default'
                                    }
                                ),
                                isDisabled: btnSize === 'default',
                            },
                            {
                                title: __('Large'),
                                onClick: () => onChange(
                                    {
                                        ...value,
                                        btnSize: 'large'
                                    }
                                ),
                                isDisabled: btnSize === 'large',
                            },
                        ] }
                    />
                </ToolbarGroup>
                {
                    iconType !== 'none' && (
                        <ToolbarGroup>
                            <ToolbarButton
                                icon={
                                    () => (
                                        <Flex gap={1}>
	                                        <Icon icon={iconType === 'custom' ? unlock : lock} />
	                                        <span>{__('Change Icon')}</span>
                                        </Flex>
                                    )
                                }
                                label={ iconType === 'custom' ? __('Reset Icon Settings') : __('Unlock Icon Editing') }
                                isActive={ iconType === 'custom' }
                                onClick={
                                    () => {
                                        onChange(
                                            {
                                                ...value,
                                                iconType: iconType === 'custom' ? 'default' : 'custom'
                                            }
                                        );
                                    }
                                }
                                style={{width: 'auto'}}
                            />
                        </ToolbarGroup>
                    )
                }
            </BlockControls>
        </>
    );
};
