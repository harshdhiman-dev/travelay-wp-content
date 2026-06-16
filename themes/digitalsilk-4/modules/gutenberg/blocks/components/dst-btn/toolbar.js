import {__} from '@wordpress/i18n';
import {BlockControls} from '@wordpress/block-editor';
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
import {capitalizeFirstLetter} from './utilities';

export const ButtonToolbar = (
	{
		blockProps,
	}
) => {
	const {attributes, setAttributes} = blockProps;
	const {btnType, btnSize, iconType} = attributes;

	return (
		<>
			{ /* Inline block controls */}
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={() => (
							<Flex gap={1}>
								<Icon icon='admin-appearance'/>
								<span>{capitalizeFirstLetter(btnType)}</span>
							</Flex>

						)}
						label={__('Button Style')}
						controls={
							["primary", "secondary", "link", "primary-inverted","primary-inverted-light", "secondary-inverted"].map(typeBtn => ({
								title: __(typeBtn.charAt(0).toUpperCase() + typeBtn.slice(1)),
								onClick: () => setAttributes({btnType: typeBtn}),
								isDisabled: btnType === typeBtn,
							}))
						}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={() => (
							<Flex gap={1}>
								<Icon icon={resizeCornerNE}/>
								<span>{capitalizeFirstLetter(btnSize)}</span>
							</Flex>
						)}
						label={__('Size')}
						controls={[
							{
								title: __('Small'),
								onClick: () => setAttributes({btnSize: 'small'}),
								isDisabled: btnSize === 'small',
							},
							{
								title: __('Default'),
								onClick: () => setAttributes({btnSize: 'default'}),
								isDisabled: btnSize === 'default',
							},
							{
								title: __('Large'),
								onClick: () => setAttributes({btnSize: 'large'}),
								isDisabled: btnSize === 'large',
							},
						]}
					/>
				</ToolbarGroup>
				{
					iconType !== 'none' && (
						<ToolbarGroup>
							<ToolbarButton
								icon={
									() => (
										<Flex gap={1}>
											<Icon icon={iconType === 'custom' ? unlock : lock}/>
											<span>{__('Change Icon')}</span>
										</Flex>
									)
								}
								label={iconType === 'custom' ? __('Reset Icon Settings') : __('Unlock Icon Editing')}
								isActive={iconType === 'custom'}
								onClick={
									() => {
										setAttributes({iconType: iconType === 'custom' ? 'default' : 'custom'});
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
