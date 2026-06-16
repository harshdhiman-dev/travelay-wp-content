/* eslint-disable jsx-a11y/click-events-have-key-events */
import {__} from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	RichText,
	ColorPalette,
	InspectorControls,
	useSettings,
	InnerBlocks,
} from '@wordpress/block-editor';
import {RenderThemeIcon} from './utilities';
import {DstIconPicker} from '../../../react-components';
import {useSelect} from '@wordpress/data';
import {useState, useEffect, useRef, useMemo} from '@wordpress/element';
import {
	PanelBody,
	BaseControl,
	ToggleControl,
	RangeControl,
	__experimentalUnitControl as UnitControl,
	TabPanel,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalHeading as Heading
} from '@wordpress/components';
import {getBlockDefaultClassName} from '@wordpress/blocks';
import classnames from 'classnames';
import {
	TabStylesPanel,
	LabelStylesPanel,
	LabelActiveStylesPanel,
	ArrowsStylesPanel,
	ArrowsHoverStylesPanel,
	AnimationsStylesPanel
} from './components/inspector-controls/inspector-control-styles';
import {getBlockStyles} from './components/helpers/block-styles';
import {TabsToolbar} from './toolbar';

// Create a custom block appender, to be inserted to our inner blocks.
const tabsAppender = () => (
	<div className="wp-block-ds-theme-ds-tabs__appender">
		<InnerBlocks.ButtonBlockAppender/>
	</div>
);

export const BlockEdit = (props) => {
	const {attributes, setAttributes, clientId, name, wrapperProps} = props;
	const {
		tabsStyle,
		tabItem,
		blockSelectedIndex,
		tabArrows,
		tabStyles,
		tabAccordion,
		tabDropdown,
		isActiveSelected,
		anchor,
		moduleVariant,
		showDescription,
		isNumberedLabels,
		showLabelsTitle,
		labelsTitle,
	} = attributes;
	const {count: tabCount, clientIds} = useSelect(
		(select) => {
			const {getBlock, getBlocks} = select('core/block-editor');
			const block = getBlock(clientId);
			if (!block) {
				return {count: 0, clientIds: []};
			}
			const innerBlocks = getBlocks(clientId);
			const allClientIds = innerBlocks.map(innerBlock => innerBlock.clientId);
			return {
				count: innerBlocks.length,
				clientIds: allClientIds,
			};
		},
		[clientId]
	);
	const blockName = getBlockDefaultClassName(name);
	const blockClassnames = classnames(
		`is-style-${tabsStyle}`,
		{
			'is-accordion-mobile': tabAccordion,
			'is-dropdown-mobile': tabDropdown,
			'has-tab-arrows': tabArrows,
		},
		moduleVariant
	);
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classnames(wrapperProps?.className, blockClassnames),
			id: anchor,
		}
	);
	// Add inline styles ( if aplicable )
	const blockInlineStyles = getBlockStyles(tabStyles);
	if (blockInlineStyles) {
		blockProps.style = {
			...blockProps.style,
			...blockInlineStyles,
		};
	}

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: `${blockName}__panels`,
		},
		{
			allowedBlocks: ['ds-blocks/ds-tab'],
			template: [
				['ds-blocks/ds-tab'],
				['ds-blocks/ds-tab']
			],
			renderAppender: tabsAppender,
		}
	);

	/**
	 * Set states of selected block indexes and tabs.
	 */
	const [selectedIndex, setSelectedIndex] = useState(null);
	const [selectedTab, setSelectedTab] = useState({});

	/**
	 * Use colors and font sizes from theme settings.
	 */
	const [colors] = useSettings('color.palette', 'typography.fontSizes');

	/**
	 * Run on page load.
	 */
	useEffect(
		() => {
			if (blockSelectedIndex !== null && blockSelectedIndex !== undefined) {
				setSelectedIndex(blockSelectedIndex);
			}
			if (!tabStyles) {
				setAttributes({tabStyles: {}});
			}
			if (!tabsStyle) {
				setAttributes({tabsStyle: 'horizontal'})
			}
		},
		// Empty dependencies, so it runs only once, on page load.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[]
	);

	/**
	 * Update tabItem to include child block ID's,
	 * and cleanup any leftover tab items ( from old blocks )
	 */
	useEffect(
		() => {
			// Only update tabItem if it is not already initialized correctly
			const updatedTabItem = {};
			let needsUpdate = false;

			clientIds.forEach(
				(id, index) => {
					const tabIndex = index + 1;
					if (!tabItem[tabIndex] || tabItem[tabIndex].id !== id) {
						updatedTabItem[tabIndex] = {...tabItem[tabIndex], id};
						needsUpdate = true;
					} else {
						updatedTabItem[tabIndex] = tabItem[tabIndex];
					}
				}
			);

			// Check if there are any invalid tabItems that need to be removed
			const tabItemKeys = Object.keys(tabItem);
			tabItemKeys.forEach(
				(key) => {
					const tabIndex = parseInt(key, 10);
					if (!clientIds.includes(tabItem[tabIndex]?.id)) {
						needsUpdate = true;
					}
				}
			);

			if (needsUpdate) {
				setAttributes({tabItem: updatedTabItem});
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[clientIds, tabItem]
	);

	// Update selected tab state when selectedIndex changes
	useEffect(
		() => {
			if (selectedIndex !== null) {
				setSelectedTab(tabItem[`${selectedIndex + 1}`] || {});
			}
		},
		[selectedIndex, tabItem]
	);

	// Handle our dropdown change.
	const handleDropdownChange = (event) => {
		const index = parseInt(event.target.value, 10);
		setSelectedIndex(index);
		setAttributes({blockSelectedIndex: index});
	};

	// Update tabItem text.
	const updateTabItemContent = (index, content) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				content,
			}
		};
		setAttributes({tabItem: newTabItem});
	};
	// Update tabItem text.
	const updateTabItemDescription = (index, description) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				description,
			}
		};
		setAttributes({tabItem: newTabItem});
	};

	// Update tabItem icon.
	const updateTabItemIcon = (index, icon) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				icon,
			}
		};
		setAttributes({tabItem: newTabItem});
		setSelectedTab({...selectedTab, icon});
	};

	// Update tabItem iconLayout.
	const updateTabItemIconLayout = (index, iconLayout) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				iconLayout,
			}
		};
		setAttributes({tabItem: newTabItem});
		setSelectedTab({...selectedTab, iconLayout});
	};

	// Update tabItem iconSize.
	const updateTabItemIconSize = (index, iconSize) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				iconSize,
			}
		};
		setAttributes({tabItem: newTabItem});
		setSelectedTab({...selectedTab, iconSize});
	};

	// Update tabItem iconColor.
	const updateTabItemIconColor = (index, iconColor) => {
		const currentIndex = index + 1;
		const newTabItem = {
			...tabItem,
			[currentIndex]: {
				...tabItem[currentIndex],
				iconColor,
			}
		};
		setAttributes({tabItem: newTabItem});
		setSelectedTab({...selectedTab, iconColor});
	};

	// On item focus, change current index.
	const onFocusClick = (index) => {
		setSelectedIndex(index);
		setAttributes({blockSelectedIndex: index});
	};

	// Refs for the tab items
	const tabRefs = useRef([]);

	// On arrow next click.
	const onNextClick = () => {
		if (selectedIndex !== null) {
			const nextIndex = (selectedIndex + 1) % tabCount;
			setSelectedIndex(nextIndex);
			setAttributes({blockSelectedIndex: nextIndex});
			// Scroll the label into view.
			tabRefs.current[nextIndex]?.scrollIntoView(
				{
					behavior: 'smooth',
					block: 'nearest',
					inline: 'start'
				}
			);
		}
	};

	// On arrow previous click.
	const onPreviousClick = () => {
		if (selectedIndex !== null) {
			const previousIndex = (selectedIndex - 1 + tabCount) % tabCount;
			setSelectedIndex(previousIndex);
			setAttributes({blockSelectedIndex: previousIndex});
			// Scroll the label into view.
			tabRefs.current[previousIndex]?.scrollIntoView(
				{
					behavior: 'smooth',
					block: 'nearest',
					inline: 'start'
				}
			);
		}
	};

	/* Update our tab styles */
	const updateTabStyles = (key, value) => {
		const newTabStyles = {...tabStyles, [key]: value};
		setAttributes({tabStyles: newTabStyles});
	};

	/**
	 * Bulk removes tab styles.
	 *
	 * @param {Array<string>} stylesToRemove - Array of style keys to remove.
	 */
	const removeTabStyles = (stylesToRemove) => {
		const newTabStyles = {...tabStyles};

		stylesToRemove.forEach((property) => {
			if (newTabStyles[property] !== undefined) {
				delete newTabStyles[property];
			}
		});

		setAttributes({tabStyles: newTabStyles});
	};

	useEffect(
		() => {
			// If there are no tab arrows, remove related tab styles.
			if (!tabArrows) {
				removeTabStyles(['scrollerItemsNumber', 'scrollerMobileItemsNumber', 'scrollerWidth', 'scrollerMobileWidth']);
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[tabArrows]
	);

	/**
	 * Tab items loop.
	 */
	const tabItems = useMemo(
		() => {
			return [
				...Array(tabCount)].map(
				(_, index) => {
					const currentTab = tabItem[`${index + 1}`] || {};
					const currentKey = currentTab.id || (clientId + index);
					const isActive = selectedIndex === index;
					const labelClassnames = classnames(
						`${blockName}__label`,
						{
							'--active': selectedIndex === index,
							[`--icon-${currentTab.iconLayout}`]: currentTab.iconLayout && currentTab.iconLayout !== 'none',
						}
					);

					return (
						<div
							role="button"
							tabIndex={0}
							key={currentKey}
							className={labelClassnames}
							onClick={() => onFocusClick(index)}
							ref={
								(el) => {
									tabRefs.current[index] = el;
									return null;
								}
							}
						>
							{
								isNumberedLabels && (
									<span className={`${blockName}__index`}>{index + 1}</span>
								)
							}
							{
								(currentTab.iconLayout === 'left' || currentTab.iconLayout === 'top') && (
									<span
										className={`${blockName}__icon`}
										style={
											currentTab.iconColor && {
												color: currentTab.iconColor,
											}
										}
									>
                                        <DstIconPicker
											icon={currentTab.icon}
											size={currentTab.iconSize || '1em'}
											onChange={(newIcon) => updateTabItemIcon(index, newIcon)}
											disabled={!isActive}
											placeholder={`${index + 1} ${__('Tab Icon')}`}
											key={currentKey}
										/>
                                    </span>
								)
							}
							<div className={`${blockName}__text-wrapper`}>
								<RichText
									tagName="span"
									className={`${blockName}__text`}
									placeholder={`Tab ${index + 1} Title`}
									value={currentTab.content || ''}
									onChange={(content) => updateTabItemContent(index, content)}
								/>
								{
									showDescription && (
										<RichText
											tagName="span"
											className={`${blockName}__description`}
											placeholder={`Tab ${index + 1} Description`}
											value={currentTab.description || ''}
											onChange={(description) => updateTabItemDescription(index, description)}
										/>
									)
								}
							</div>

							{
								(currentTab.iconLayout === 'right' || currentTab.iconLayout === 'bottom') && (
									<span
										className={`${blockName}__icon`}
										style={
											currentTab.iconColor && {
												color: currentTab.iconColor,
											}
										}
									>
                                        <DstIconPicker
											icon={currentTab.icon}
											size={currentTab.iconSize || '1em'}
											onChange={(newIcon) => updateTabItemIcon(index, newIcon)}
											disabled={!isActive}
											key={currentKey}
										/>
                                    </span>
								)
							}
						</div>
					);
				}
			);
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[tabItem, tabCount, selectedIndex, clientId, isNumberedLabels, showDescription]
	);

	return (
		<>
			<TabsToolbar
				blockProps={props}
				selectedTab={selectedTab}
				updateIconLayout={updateTabItemIconLayout}
				selectedTabIndex={selectedIndex}
			/>
			<InspectorControls group='settings'>
				<PanelBody>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={showLabelsTitle}
						help={__('Show Labels Title')}
						label={__('Enable Labels Title')}
						onChange={(value) => setAttributes({showLabelsTitle: value})}
					/>
				</PanelBody>
				<PanelBody>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={tabArrows}
						help={__('Show arrows for horizontal tab scrolling and navigation.')}
						label={__('Enable Tab Navigation Arrows?')}
						onChange={(value) => setAttributes({tabArrows: value})}
					/>
					{
						tabArrows && (
							<h3 style={{marginBottom: '-1.5em'}}>{__('Arrow Settings')}</h3>
						)
					}
				</PanelBody>
				{
					tabArrows && (
						<>
							<TabPanel
								className="ds-tabs-scroller-settings"
								activeClass="is-active"
								tabs={[
									{name: 'desktop', title: __('Desktop'), className: 'ds-scroller-tab'},
									{name: 'mobile', title: __('Mobile'), className: 'ds-scroller-tab'},
								]}
							>
								{(tab) => {
									if (tab.name === 'desktop') {
										return (
											<PanelBody>
												<RangeControl
													label={__('Visible Labels')}
													value={tabStyles?.scrollerItemsNumber || 4}
													onChange={(value) => updateTabStyles('scrollerItemsNumber', value)}
													min={1}
													max={6}
													step={1}
													beforeIcon="desktop"
												/>
												<UnitControl
													label={__('Track Size')}
													value={tabStyles?.scrollerWidth || '600px'}
													onChange={(value) => updateTabStyles('scrollerWidth', value)}
													units={[
														{value: 'px', label: 'px', default: 16},
														{value: 'rem', label: 'rem', default: 1.5},
														{value: 'em', label: 'em', default: 1},
													]}
													__nextHasNoMarginBottom
												/>
											</PanelBody>
										);
									}
									if (tab.name === 'mobile') {
										return (
											<PanelBody>
												<RangeControl
													label={__('Visible Labels')}
													help={__('Set a total number of visible labels on both mobile and desktop')}
													value={tabStyles?.scrollerMobileItemsNumber || 1}
													onChange={(value) => updateTabStyles('scrollerMobileItemsNumber', value)}
													min={1}
													max={6}
													step={1}
													beforeIcon="smartphone"
												/>
												<UnitControl
													label={__('Track Size')}
													value={tabStyles?.scrollerMobileWidth || '240px'}
													onChange={(value) => updateTabStyles('scrollerMobileWidth', value)}
													units={[
														{value: 'px', label: 'px', default: 16},
														{value: 'rem', label: 'rem', default: 1.5},
														{value: 'em', label: 'em', default: 1},
													]}
													__nextHasNoMarginBottom
												/>
											</PanelBody>
										);
									}
								}}
							</TabPanel>
						</>
					)
				}
				<PanelBody>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={isActiveSelected}
						help={__('If enabled, the tab selected in the block editor will be displayed as the active tab on the front-end of the website. If this option is disabled, the first tab will be shown as the default active tab.')}
						label={__('Use selected tab as a current tab?')}
						onChange={(value) => setAttributes({isActiveSelected: value})}
					/>
				</PanelBody>
				<PanelBody>
					<h3>{__('Responsive Transforms')}</h3>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={tabAccordion}
						help={__('If selected, tabs will transform into an accordion on mobile')}
						label={__('Transform to accordion?')}
						onChange={(value) => {
							setAttributes({tabAccordion: value})
							if (value) {
								setAttributes({tabDropdown: undefined});
							}
						}}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={tabDropdown}
						help={__('Enable this option to use a dropdown menu for tab navigation on mobile devices. When activated, the tabs will be replaced by a dropdown selector, allowing users to choose a tab from a list.')}
						label={__('Use dropdown on mobile?')}
						onChange={(value) => {
							setAttributes({tabDropdown: value})
							if (value) {
								setAttributes({tabAccordion: undefined});
							}
						}}
					/>
				</PanelBody>
				<PanelBody title={__('Tabs Variants')} initialOpen={false}>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('')}
						value={moduleVariant}
						onChange={(newVariant) => setAttributes({moduleVariant: newVariant})}
						isBlock
					>
						<ToggleGroupControlOption
							value=""
							label={__('Default')}
						/>
						<ToggleGroupControlOption
							value="tabs-v1"
							label={__('Variant 1')}
						/>
						<ToggleGroupControlOption
							value="tabs-v2"
							label={__('Variant 2')}
						/>
						<ToggleGroupControlOption
							value="tabs-v3"
							label={__('Variant 3')}
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<TabPanel
					tabs={
						Object.entries(tabItem)
							.filter(([, value]) => value.iconLayout && value.iconLayout !== 'none')
							.map(
								([key]) => (
									{
										name: `tab-${key}`,
										title: `${__('Tab')} ${key} ${__('Icon')}`,
										className: 'ds-tabs-inspector-tab',
									}
								)
							)
					}
				>
					{
						({name: tabKey}) => {
							const tabIndex = tabKey.replace('tab-', '');
							const currentTab = tabItem[tabIndex] || {};
							return (
								<PanelBody>
									<UnitControl
										label={__('Icon Size')}
										value={currentTab.iconSize || '1em'}
										onChange={(newSize) => updateTabItemIconSize(parseInt(tabIndex, 10) - 1, newSize)}
										__next40pxDefaultSize
									/>
									<BaseControl id={null} label={__('Icon Color')}>
										<ColorPalette
											colors={colors}
											value={currentTab.iconColor || ''}
											onChange={(newColor) => updateTabItemIconColor(parseInt(tabIndex, 10) - 1, newColor)}
										/>
									</BaseControl>
								</PanelBody>
							);
						}
					}
				</TabPanel>
			</InspectorControls>
			<InspectorControls group='styles'>
				<LabelStylesPanel attributes={attributes} setAttributes={setAttributes}/>
				<LabelActiveStylesPanel attributes={attributes} setAttributes={setAttributes}/>
				<TabStylesPanel attributes={attributes} setAttributes={setAttributes}/>
				<ArrowsStylesPanel attributes={attributes} setAttributes={setAttributes}/>
				<ArrowsHoverStylesPanel attributes={attributes} setAttributes={setAttributes}/>
				<AnimationsStylesPanel attributes={attributes} setAttributes={setAttributes}/>
			</InspectorControls>
			<div {...blockProps}>
				<div className={`${blockName}__inner`}>
					<div className={`${blockName}__labels`}>
						{
							showLabelsTitle && (
								<RichText
									tagName="h2"
									className={`${blockName}__labels-title`}
									placeholder={`Labels Title`}
									value={labelsTitle}
									onChange={(value) => setAttributes({labelsTitle: value})}
								/>
							)
						}
						{
							tabArrows && (
								<>
									<div className={`${blockName}__arrows`}>
                                        <span role="button" tabIndex={0} className={`${blockName}__arrow --previous`}
											  onClick={() => onPreviousClick()}>
                                            <RenderThemeIcon icon='tabs-arrow'/>
                                        </span>
									</div>
								</>
							)
						}
						{tabArrows ? (
							<div className={`${blockName}__scroller`}>
								<div className={`${blockName}__track`}>
									{tabItems}
								</div>
							</div>
						) : (
							tabItems
						)}
						{
							tabArrows && (
								<>
									<div className={`${blockName}__arrows`}>
                                        <span role="button" tabIndex={0} className={`${blockName}__arrow --next`}
											  onClick={() => onNextClick()}>
                                            <RenderThemeIcon icon='tabs-arrow'/>
                                        </span>
									</div>
								</>
							)
						}
					</div>
					{tabDropdown && (
						<select className={`${blockName}__dropdown`} onChange={handleDropdownChange}
								value={selectedIndex !== null ? selectedIndex : ''}>
							{Object.keys(tabItem).map((key, index) => (
								<option key={key.id || (clientId + index)} value={index}>
									{tabItem[key].content || `Tab ${index + 1}`}
								</option>
							))}
						</select>
					)}
					<div {...innerBlocksProps} />
				</div>
			</div>
		</>
	);
};
