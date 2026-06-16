import {__} from '@wordpress/i18n';
import {useState, useEffect} from '@wordpress/element';
import {
	ColorIndicator,
	FocalPointPicker,
	ToggleControl,
	__experimentalUnitControl as UnitControl,
	__experimentalZStack as ZStack,
	Flex,
	FlexItem,
	TabPanel,
	TextControl,
	Disabled,
	DropdownMenu,
} from '@wordpress/components';
import {createDecorationsObject} from './utilities';
import {desktop, tablet, mobile, image, brush} from '@wordpress/icons';
import {
	DndContext,
	closestCenter,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors
} from '@dnd-kit/core';
import {
	SortableContext,
	arrayMove,
	verticalListSortingStrategy
} from '@dnd-kit/sortable';
import {
	SortableItem
} from './SortableItem';
import {v4 as uuidv4} from 'uuid';

/**
 * Render a ColorIndicator with an image as background.
 *
 * @param {Object} props
 * @param {string} props.imageUrl - The URL of the image to display.
 * @param {string} props.itemKey  - The key for the ColorIndicator.
 */
const ImageColorIndicator = (
	{
		imageUrl,
		itemKey,
	}
) => (
	<ColorIndicator
		key={itemKey}
		style={{
			backgroundImage: `url(${imageUrl})`,
			backgroundSize: 'cover',
			backgroundPosition: 'center',
			width: '24px',
			height: '24px',
		}}
	/>
);

/**
 * Render a list of ColorIndicators as background image previews.
 *
 * @param {Array} items - The array of background image objects.
 * @return {JSX.Element|null} - The rendered ColorIndicators or null if no items are provided.
 */
export const renderBackgroundIndicators = (items = []) => {
	if (!Array.isArray(items) || items.length === 0) {
		return <ColorIndicator/>;
	}

	return (
		<ZStack offset={20} isLayered style={{width: 'auto'}}>
			{items.map((item, index) => {
				let imageUrl = item?.media?.type === 'video'
					? item?.media?.icon
					: item?.media?.sizes?.thumbnail?.url || item?.media?.url;

				if (!imageUrl && item?.className) {
					imageUrl = '/wp-includes/images/media/interactive.svg';
				}

				if (!imageUrl) {
					return (
						<ColorIndicator key={item?.id || index}/>
					);
				}

				return (
					<ImageColorIndicator
						imageUrl={imageUrl}
						key={item?.id || index}
					/>
				);
			})}
		</ZStack>
	);
};

/**
 * NewBackgroundImageButton Component
 *
 * @param {Object}   props
 * @param {Function} props.openMediaUploader - Function to open the media uploader.
 * @param {Function} props.onChange          - Function to update the value.
 * @param {Array}    props.value             - Array of background image objects.
 */
export const NewBackgroundImageButton = ({openMediaUploader, onChange, value}) => {
	return (
		<>
			<DropdownMenu
				label={__('Add Decoration')}
				icon={() => (
					<p>{__('Add Decoration')}</p>
				)}
				className='dst-decorations-dropdown'
				toggleProps={
					{
						style: {
							'--wp-admin-theme-color': '#d8d8d8',
							color: '#1e1e1e',
							width: '100%',
							textAlign: 'center',
							padding: '6px 12px',
						},
					}
				}
				popoverProps={
					{
						offset: 0,
						variant: 'toolbar',
						className: 'dst-decorations-popover'
					}
				}
				controls={[
					{
						title: __('Add Image'),
						icon: image,
						onClick: () => {
							openMediaUploader();
						}
					},
					{
						title: __('Add Custom'),
						icon: brush,
						onClick: () => {
							const newItem = createDecorationsObject('custom', {id: uuidv4()});
							onChange([...value, newItem]);
						}
					},
				]}
			/>
		</>
	);
};

/**
 * Decorations Popover Component
 *
 * @param {Object}   props
 * @param {Array}    props.value             - Array of background image objects.
 * @param {Function} props.onChange          - Function to update the value.
 * @param {Function} props.openMediaUploader - Function to open the media uploader.
 *
 * @return {JSX.Element|null} - The rendered popover or null if no value is provided.
 */
export const DecorationsPopover = (
	{
		value = [],
		onChange,
		openMediaUploader,
	}
) => {
	// Set active image state.
	const [activeImage, setActiveImage] = useState(() => value?.[0]?.id || null);

	const sensors = useSensors(
		useSensor(PointerSensor),
		useSensor(KeyboardSensor)
	);

	const handleDragEnd = (event) => {
		const {active, over} = event;
		if (active.id !== over.id) {
			const oldIndex = value.findIndex((item) => item.id === active.id);
			const newIndex = value.findIndex((item) => item.id === over.id);

			const reordered = arrayMove(value, oldIndex, newIndex);
			onChange(reordered);
		}
	};

	// Set the active image to the first one if it doesn't exist in the value array.
	useEffect(
		() => {
			if (!value.some((item) => item.id === activeImage) && value.length) {
				setActiveImage(value[0].id);
			}
		},
		[value, activeImage]
	);

	const updateItem = (id, updatedFields) => {
		const newItems = value.map((item) =>
			item.id === id ? {...item, ...updatedFields} : item
		);
		onChange(newItems);
	};

	const updateFocalPoint = (newFocal, item, tab) => {
		const updatedFocal = {
			...item.position,
			[tab.name]: newFocal,
		};

		// Apply inheritance based on tab
		if (tab.name === 'desktop') {
			updatedFocal.tablet = newFocal;
			updatedFocal.mobile = newFocal;
		} else if (tab.name === 'tablet') {
			updatedFocal.mobile = newFocal;
		}

		updateItem(item.id, {position: updatedFocal});
	}

	return (
		<div style={{width: '300px', padding: '12px'}}>
			<DndContext
				sensors={sensors}
				collisionDetection={closestCenter}
				onDragEnd={handleDragEnd}
			>
				<SortableContext
					items={value.map((item) => item.id)}
					strategy={verticalListSortingStrategy}
				>
					<div className="listing-area">
						{value.map((item) => (
							<SortableItem
								key={item.id}
								item={item}
								isActive={item.id === activeImage}
								onClick={() => setActiveImage(item.id)}
								onDelete={() => onChange(value.filter((i) => i.id !== item.id))}
							/>
						))}
					</div>
				</SortableContext>
			</DndContext>
			<NewBackgroundImageButton
				openMediaUploader={openMediaUploader}
				onChange={onChange}
				value={value}
			/>
			<div className='active-area'>
				{value
					.filter((item) => item.id === activeImage)
					.map((item) => (
						<div key={item.id} style={{marginBottom: '2rem'}}>
							{
								item?.type === 'custom' && (
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										onChange={
											(className) => {
												updateItem(item.id, {className});
											}
										}
										value={item?.className || ''}
										help={__('Custom decorations depend on css styling of the classes.')}
									/>
								)
							}
							{(item.media?.id || item?.type === 'custom') && (
								<TabPanel
									tabs={[
										{name: 'desktop', title: __('Desktop'), icon: desktop},
										{name: 'tablet', title: __('Tablet'), icon: tablet},
										{name: 'mobile', title: __('Mobile'), icon: mobile},
									]}
								>
									{(tab) => {
										const currentFocal = item?.position?.[tab.name] || {x: 0.5, y: 0.5};
										const currentSize = item?.size?.[tab.name] || {};
										const currentDisplay = item?.display?.[tab.name];
										const isVisible = typeof currentDisplay === 'boolean' ? currentDisplay : true;
										return (
											<>
												<br/>
												<ToggleControl
													__nextHasNoMarginBottom
													label={`${__('Show on')} ${tab.name} ?`}
													checked={isVisible}
													onChange={(display) => {
														const updatedDisplay = {
															...item.display,
															[tab.name]: display,
														};

														updateItem(item.id, {display: updatedDisplay});
													}}
												/>
												<br/>
												<Disabled
													isDisabled={!isVisible}
													style={
														{
															opacity: (!isVisible) ? 0.35 : 1,
														}
													}
												>
													<FocalPointPicker
														__nextHasNoMarginBottom
														className='dst-svg-focal-point-picker'
														label={__('Focal Point')}
														url={item?.media?.url}
														value={currentFocal}
														onDrag={(newFocal) => updateFocalPoint(newFocal, item, tab)}
														onChange={(newFocal) => updateFocalPoint(newFocal, item, tab)}
													/>
													<br/>
													<Flex>
														<FlexItem>
															<UnitControl
																__next40pxDefaultSize
																label={__('Width')}
																placeholder={__('Auto')}
																value={currentSize?.width || ''}
																onChange={(width) => {
																	const updatedSize = {
																		...item.size,
																		[tab.name]: {
																			...(item.size?.[tab.name] || {}),
																			width,
																		},
																	};

																	// Inheritance logic
																	if (tab.name === 'desktop') {
																		updatedSize.tablet = {
																			...updatedSize.tablet,
																			width
																		};
																		updatedSize.mobile = {
																			...updatedSize.mobile,
																			width
																		};
																	} else if (tab.name === 'tablet') {
																		updatedSize.mobile = {
																			...updatedSize.mobile,
																			width
																		};
																	}

																	updateItem(item.id, {size: updatedSize});
																}}
															/>
														</FlexItem>
														<FlexItem>
															<UnitControl
																__next40pxDefaultSize
																label={__('Height')}
																placeholder={__('Auto')}
																value={currentSize?.height || ''}
																onChange={(height) => {
																	const updatedSize = {
																		...item.size,
																		[tab.name]: {
																			...(item.size?.[tab.name] || {}),
																			height,
																		},
																	};

																	// Inheritance logic
																	if (tab.name === 'desktop') {
																		updatedSize.tablet = {
																			...updatedSize.tablet,
																			height
																		};
																		updatedSize.mobile = {
																			...updatedSize.mobile,
																			height
																		};
																	} else if (tab.name === 'tablet') {
																		updatedSize.mobile = {
																			...updatedSize.mobile,
																			height
																		};
																	}

																	updateItem(item.id, {size: updatedSize});
																}}
															/>
														</FlexItem>
														<FlexItem>
															<UnitControl
																__next40pxDefaultSize
																label={__('Rotate')}
																units={[{value: 'deg', label: 'deg', default: 0}]}
																value={item.rotate || ''}
																onChange={(rotate) => {
																	updateItem(item.id, {rotate: rotate});
																}}
															/>
														</FlexItem>
													</Flex>
												</Disabled>
											</>
										);
									}}
								</TabPanel>
							)}
						</div>
					))
				}
			</div>
		</div>
	);
};
