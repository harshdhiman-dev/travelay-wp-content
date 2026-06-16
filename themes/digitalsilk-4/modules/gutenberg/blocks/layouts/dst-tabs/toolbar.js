import {useState} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {BlockControls, store as blockEditorStore} from '@wordpress/block-editor';
import {createBlock} from '@wordpress/blocks';
import {
	Button,
	Icon,
	ToolbarGroup,
	ToolbarButton,
	ToolbarDropdownMenu,
	Modal,
	Flex,
	FlexBlock,
} from '@wordpress/components';
import {dispatch} from '@wordpress/data';
import {
	trash,
	seen,
	unseen,
	arrowDown,
	arrowUp,
	arrowLeft,
	arrowRight,
	addCard,
	formatIndent,
	formatListNumbered,
	handle
} from '@wordpress/icons';
import {tabLeft, tabCenter, tabFull, tabInvertedLeft, tabInvertedCenter, tabInvertedFull, tabVertical} from './icons';

export const TabsToolbar = (
	{
		blockProps,
		selectedTab,
		updateIconLayout,
		selectedTabIndex
	}
) => {
	const {attributes, setAttributes, clientId} = blockProps;
	const {tabsStyle, showDescription, isNumberedLabels} = attributes;

	// Confirmation modal opened state
	const [isModalOpen, setIsModalOpen] = useState(false);

	// Define the icon mapping for heading levels
	const tabLayoutIcons = {
		'horizontal': tabLeft,
		'horizontal-centered': tabCenter,
		'horizontal-full': tabFull,
		'horizontal-inverted': tabInvertedLeft,
		'horizontal-inverted-centered': tabInvertedCenter,
		'horizontal-inverted-full': tabInvertedFull,
		'vertical': tabVertical,
	};

	// Get the appropriate icon based on the current tag, default to `heading` if undefined
	const currentTabLayoutIcon = tabLayoutIcons[tabsStyle] || tabLeft;

	const tabIconPositionIcons = {
		'left': arrowLeft,
		'right': arrowRight,
		'top': arrowUp,
		'bottom': arrowDown,
	}
	const currentTabIconPositionIcon = tabIconPositionIcons[selectedTab?.iconLayout] || arrowLeft;

	return (
		<>
			{ /* Inline block controls */}
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={() => (
							<Flex gap={2}>
								{__('Layout')}
								<Icon icon={currentTabLayoutIcon}/>
							</Flex>
						)}
						label={__('Tab Layout')}
						controls={
							[
								{
									title: __('Left'),
									icon: tabLeft,
									onClick: () => setAttributes({tabsStyle: 'horizontal'}),
								},
								{
									title: __('Centered'),
									icon: tabCenter,
									onClick: () => setAttributes({tabsStyle: 'horizontal-centered'}),
								},
								{
									title: __('Full'),
									icon: tabFull,
									onClick: () => setAttributes({tabsStyle: 'horizontal-full'}),
								},
								{
									title: __('Inverted Left'),
									icon: tabInvertedLeft,
									onClick: () => setAttributes({tabsStyle: 'horizontal-inverted'}),
								},
								{
									title: __('Inverted Center'),
									icon: tabInvertedCenter,
									onClick: () => setAttributes({tabsStyle: 'horizontal-inverted-centered'}),
								},
								{
									title: __('Inverted Full'),
									icon: tabInvertedFull,
									onClick: () => setAttributes({tabsStyle: 'horizontal-inverted-full'}),
								},
								{
									title: __('Vertical'),
									icon: tabVertical,
									onClick: () => setAttributes({tabsStyle: 'vertical'}),
								},
							]
						}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					{
						selectedTabIndex !== null && selectedTab && (
							<>
								<ToolbarButton
									icon={
										() => (
											<Flex gap={2}>
												{__('Icon')}
												{
													!selectedTab?.iconLayout || selectedTab?.iconLayout === 'none' ? (
														<Icon icon={seen}/>
													) : (
														<Icon icon={unseen}/>
													)
												}
											</Flex>
										)
									}
									label={__('Icon')}
									onClick={
										() => {
											if (!selectedTab?.iconLayout || selectedTab?.iconLayout === 'none') {
												updateIconLayout(selectedTabIndex, 'left')
											} else {
												updateIconLayout(selectedTabIndex, 'none')
											}
										}
									}
									style={{width: 'auto'}}
								/>
								{
									selectedTab?.iconLayout && selectedTab?.iconLayout !== 'none' && (
										<ToolbarDropdownMenu
											icon={
												() => (
													<Flex gap={2}>
														{__('Position')}
														<Icon icon={currentTabIconPositionIcon}/>
													</Flex>
												)
											}
											label={__('Icon Position')}
											controls={
												[
													{
														title: __('Left'),
														icon: arrowLeft,
														onClick: () => updateIconLayout(selectedTabIndex, 'left'),
													},
													{
														title: __('Bottom'),
														icon: arrowDown,
														onClick: () => updateIconLayout(selectedTabIndex, 'bottom'),
													},
													{
														title: __('Right'),
														icon: arrowRight,
														onClick: () => updateIconLayout(selectedTabIndex, 'right'),
													},
													{
														title: __('top'),
														icon: arrowUp,
														onClick: () => updateIconLayout(selectedTabIndex, 'top'),
													},
												]
											}
										/>
									)
								}
							</>
						)
					}
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={
							() => (
								<>
									<Icon icon={formatIndent}/>
									<span>{showDescription ? __('Hide Desc') : __('Show Desc')}</span>
								</>
							)
						}
						label={showDescription ? __('Showed Description') : __('Hidden Description')}
						onClick={() => setAttributes({showDescription: !showDescription})}
						style={{width: 'auto'}}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={
							() => (
								<>
									<Icon icon={isNumberedLabels ? handle : formatListNumbered}/>
								</>
							)
						}
						label={isNumberedLabels ?__('List of labels'):__('Numbered list of labels')}
						onClick={() => setAttributes({isNumberedLabels: !isNumberedLabels})}
						style={{width: 'auto'}}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<ToolbarButton
						icon={
							() => (
								<Flex gap={2}>
									{__('Add New Tab')}
									<Icon icon={addCard}/>
								</Flex>
							)
						}
						label={__('Add Tab')}
						onClick={() => {
							const newBlock = createBlock('ds-blocks/ds-tab');
							dispatch(blockEditorStore).insertBlock(
								newBlock,
								undefined,
								clientId
							);
							dispatch(blockEditorStore).selectBlock(newBlock.clientId);
						}}
						style={{width: 'auto'}}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					{
						selectedTab && selectedTab.id && (
							<ToolbarButton
								icon={
									() => (
										<Flex gap={2}>
											{__('Remove Tab')}
											<Icon icon={trash}/>
										</Flex>
									)
								}
								label={__('Remove Tab')}
								isDestructive
								onClick={
									() => {
										setIsModalOpen(selectedTab.id);
									}
								}
								style={{width: 'auto'}}
							/>
						)
					}
				</ToolbarGroup>
			</BlockControls>
			{
				isModalOpen && (
					<Modal
						title={__('Remove Tab')}
						onRequestClose={() => setIsModalOpen(false)}
						className='dst-confirmation-modal'
					>
						<p>
							{__('Are you sure you want to remove this tab?')}<br/>
							{__('This action cannot be undone.')}
						</p>
						<Flex justify="flex-start">
							<FlexBlock>
								<Button
									variant="primary"
									isDestructive
									onClick={
										() => {
											dispatch(blockEditorStore).removeBlock(selectedTab.id);
											setIsModalOpen(false);
										}
									}
								>
									{__('Remove')}
								</Button>
							</FlexBlock>
							<FlexBlock>
								<Button
									variant="secondary"
									onClick={() => setIsModalOpen(false)}
								>
									{__('Cancel')}
								</Button>
							</FlexBlock>
						</Flex>
					</Modal>
				)
			}
		</>
	);
};
