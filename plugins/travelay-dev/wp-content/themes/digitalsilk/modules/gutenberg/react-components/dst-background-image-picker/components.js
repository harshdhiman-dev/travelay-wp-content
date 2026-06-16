import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
    ColorIndicator,
    Flex,
    FlexBlock,
    FocalPointPicker,
	ToggleControl,
    __experimentalUnitControl as UnitControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
    __experimentalZStack as ZStack,
    Button,
    TabPanel,
    BaseControl,
} from '@wordpress/components';
import { generateResponsiveIndicators, isMobileMediaDifferent } from './utilities';
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

/**
 * Render a list of ColorIndicators as background image previews.
 *
 * @param {Array} items - The array of background image objects.
 * @return {JSX.Element|null} - The rendered ColorIndicators or null if no items are provided.
 */
export const renderBackgroundIndicators = ( items = [] ) => {
	if ( ! Array.isArray( items ) || items.length === 0 ) {
		return <ColorIndicator />;
	}

	return (
		<ZStack offset={20} isLayered style={ { width: 'auto' } }>
			{ items.flatMap( ( item, index ) =>
				generateResponsiveIndicators( item, item?.id || index )
			) }
		</ZStack>
	);
};

/**
 * NewBackgroundImageButton Component
 *
 * @param {Object}   props
 * @param {Function} props.openMediaUploader - Function to open the media uploader.
 * @param {Function} props.setUploadMode     - Function to set upload mode before opening.
 */
const NewBackgroundImageButton = ( { openMediaUploader, setUploadMode } ) => {
	return (
		<Button
			__next40pxDefaultSize
			onClick={ () => {
				setUploadMode('add');
				openMediaUploader();
			} }
			onMouseDown={ ( e ) => e.preventDefault() }
			style={ {
				marginBottom: '2rem',
				width: '100%',
				justifyContent: 'center',
				border: '1px solid #dedede',
			} }
		>
			{ __( 'Add New Media' ) }
		</Button>
	);
};

/**
 * ChangeMobileMediaButton Component
 *
 * Combines the "Reset Mobile Media" and "Change Mobile Media" buttons
 * into one unified component.
 *
 * @param {Object}   props
 * @param {Object}   props.item               - Background image item.
 * @param {Function} props.updateItem         - Function to update the item.
 * @param {Function} props.setMediaUploadMode - Function to switch upload mode.
 * @param {Function} props.setTargetItemId    - Function to set which item to update.
 * @param {Function} props.openMediaUploader  - Function to open the upload dialog.
 */
const ChangeMobileMediaButton = ({
	item,
	updateItem,
	setMediaUploadMode,
	setTargetItemId,
	openMediaUploader,
}) => {
	const shouldShowReset = isMobileMediaDifferent(item);

	return (
		<div style={{ marginBottom: '2rem' }}>
			<Button
				__next40pxDefaultSize
				variant="secondary"
				style={ {
					justifyContent: 'center',
					width: '100%',
				} }
				onClick={ () => {
					setMediaUploadMode('replace_mobile');
					setTargetItemId(item.id);
					openMediaUploader();
				} }
			>
				{ __('Change Mobile Media') }
			</Button>
			{ shouldShowReset && (
				<Button
					__next40pxDefaultSize
					variant="tertiary"
					size='small'
                    isDestructive
					style={ {
						width: '100%',
                        justifyContent: 'center',
						marginBottom: '0.75rem',
					} }
					onClick={ () => {
						updateItem(item.id, {
							mobile: {
								...item.mobile,
								media: item.desktop?.media,
							},
						});
					} }
				>
					{ __('Reset Mobile Media') }
				</Button>
			) }
		</div>
	);
};


/**
 * BackgroundImagePopover Component
 *
 * @param {Object}   props
 * @param {Array}    props.value              - Array of background image objects.
 * @param {Function} props.onChange           - Function to update the value.
 * @param {Function} props.openMediaUploader  - Function to open the media uploader.
 * @param {Function} props.setMediaUploadMode - Function to set the media upload mode.
 * @param {Function} props.setTargetItemId    - Function to set the target item ID.
 *
 * @return {JSX.Element|null} - The rendered popover or null if no value is provided.
 */
export const BackgroundImagePopover = (
    {
        value = [],
        onChange,
        openMediaUploader,
        setMediaUploadMode,
        setTargetItemId,
    }
) => {
    // Set active image state.
    const [ activeImage, setActiveImage ] = useState( () => value?.[0]?.id || null );

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor)
    );
    
    const handleDragEnd = (event) => {
        const { active, over } = event;
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
            if ( ! value.some( (item) => item.id === activeImage ) && value.length ) {
                setActiveImage( value[0].id );
            }
        },
        [ value, activeImage ]
    );

    // Update a single item in the value array.
    const updateItem = ( id, updatedFields ) => {
        const newItems = value.map( ( item ) =>
            item.id === id ? { ...item, ...updatedFields } : item
        );
        onChange( newItems );
    };

    // Create a function to update the tab (desktop/mobile) of an item.
    const createTabUpdater = ( tabName, updateFn ) => (id, updatedFields) => {
        const itemToUpdate = value.find((item) => item.id === id);
    
        if (!itemToUpdate) {
            return;
        }
    
        const updatedItem = {
            ...itemToUpdate,
            [tabName]: {
                ...itemToUpdate[tabName],
                ...updatedFields,
            },
        };
    
        // If updating "desktop", also update "mobile" with the same fields
        if (tabName === 'desktop') {
            updatedItem.mobile = {
                ...itemToUpdate.mobile,
                ...updatedFields,
            };
        }
    
        updateFn(id, updatedItem);
    };

    // Filter the array to only have the active image.
    const activeItemArray = value.filter((item) => item.id === activeImage);

	return (
		<div style={ { width: '300px', padding: '12px' } }>
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
                openMediaUploader={ openMediaUploader }
                setUploadMode={ () => {
                    setMediaUploadMode('add');
                    setTargetItemId(null);
                } }
            />
            <div className='active-area'>
            {
                activeItemArray && activeItemArray.length && activeItemArray.map(
                    ( item ) => (
                        <div key={ item.id } style={ { marginBottom: '2rem' } }>
                            <TabPanel
                                tabs={[
                                    { name: 'desktop', title: __('Desktop') },
                                    { name: 'mobile', title: __('Mobile') },
                                ]}
                            >
                                {(tab) => {
                                    const currentViewport = item[tab.name];
                                    const updateTab = createTabUpdater( tab.name, updateItem );

                                    return (
                                        <>
                                            {
                                                currentViewport?.media?.url && (
                                                    <FocalPointPicker
                                                        __nextHasNoMarginBottom
                                                        className='dst-svg-focal-point-picker'
                                                        url={currentViewport.media.url}
                                                        value={currentViewport.focal}
                                                        onDrag={(newFocal) => {
                                                            updateTab( item.id, { focal: newFocal } );
                                                        }}
                                                    />
                                                )
                                            }
                                            <br/>
                                            {
                                                tab.name === 'mobile' && (
                                                    <ChangeMobileMediaButton
                                                        item={ item }
                                                        updateItem={ updateItem }
                                                        setMediaUploadMode={ setMediaUploadMode }
                                                        setTargetItemId={ setTargetItemId }
                                                        openMediaUploader={ openMediaUploader }
                                                    />
                                                )
                                            }
                                            <ToggleGroupControl
                                                __next40pxDefaultSize
                                                __nextHasNoMarginBottom
                                                isBlock
                                                label={__('Size')}
                                                value={currentViewport.size}
                                                onChange={(size) => {
                                                    updateTab(item.id, {
                                                        size,
                                                        width: size === 'cover' ? 'auto' : currentViewport.width,
                                                    });
                                                }}
                                            >
                                                <ToggleGroupControlOption label={__('Cover')} value="cover" />
                                                <ToggleGroupControlOption label={__('Contain')} value="contain" />
                                                <ToggleGroupControlOption label={__('Fill')} value="fill" />
                                                <ToggleGroupControlOption label={__('None')} value="none" />
                                            </ToggleGroupControl>
                                            <br />
                                            <BaseControl
                                                __nextHasNoMarginBottom
                                                label={__('Width')}
                                                id={null}
                                            >
                                                <Flex>
                                                    <FlexBlock>
                                                        <UnitControl
                                                            __next40pxDefaultSize
                                                            value={currentViewport.width}
                                                            placeholder={__('Auto')}
                                                            onChange={(width) => {
                                                                updateTab(item.id, { width });
                                                            }}
                                                            disabled={currentViewport.size === 'cover'}
                                                        />
                                                    </FlexBlock>
                                                    <FlexBlock>
                                                        <ToggleControl
                                                            __nextHasNoMarginBottom
                                                            label={__('Fixed?')}
                                                            checked={!!currentViewport.fixed}
                                                            onChange={(fixed) => {
                                                                updateTab(item.id, { fixed });
                                                            }}
                                                        />
                                                    </FlexBlock>
                                                </Flex>
                                            </BaseControl>
                                        </>
                                    );
                                }}
                            </TabPanel>
                            <br />
                            <ToggleControl
                                __nextHasNoMarginBottom
                                label={ __( 'Lazy Load' ) }
                                checked={ !!item.lazy }
                                onChange={ ( lazy ) => updateItem( item.id, { lazy } ) }
                            />
                        </div>
                    )
                )
            }
            </div>
		</div>
	);
};
