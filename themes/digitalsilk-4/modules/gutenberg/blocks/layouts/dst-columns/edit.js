/**
 * WordPress dependencies
 */
import {useState, useEffect, useRef} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	ToggleControl,
	RangeControl,
	Button,
	ButtonGroup,
	Modal,
	ToolbarGroup,
	ToolbarButton,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import {
	InspectorControls,
	useInnerBlocksProps,
	__experimentalBlockVariationPicker as BlockVariationPicker,
	useBlockProps,
	store as blockEditorStore,
	HeightControl,
	BlockControls,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock, createBlocksFromInnerBlocksTemplate, store as blocksStore } from '@wordpress/blocks';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
	justifyTop,
	justifyCenterVertical,
	justifyBottom,
	grid,
	alignLeft,
	alignCenter,
	alignRight,
} from '@wordpress/icons';
import {
	ClientLockControl,
	DstBackgroundColorPicker,
	DstBackgroundImagePicker,
	DstBackgroundImageRender,
	DstDecorationsPicker,
	DstDecorationsRender
} from '../../../react-components';
import classnames from 'classnames';

const DEFAULT_BLOCK = {
	name: "ds-blocks/ds-column",
	attributes: {
	  columnSpan: 6, // Default to full width for a single column
	},
}

/**
 * Inspector controls for the Columns block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.clientId      Block client ID.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block attributes setter.
 *
 * @return {WPElement} Inspector controls element.
 */
const ColumnInspectorControls = (
	{
		clientId,
		attributes,
		setAttributes
	}
) => {
	const { verticalAlign, gap, reverseMobile, tabletCount, mobileCount, textAlign = '', textAlignMobile = '', isMultirow = false, desktopColumnsPerRow } = attributes;
	const { count, InsertMobileBlock, minCount } = useSelect(
		(select) => {
			const { canInsertBlockType, canRemoveBlock, getBlocks, getBlockCount } = select(blockEditorStore);
			const innerBlocks = getBlocks(clientId);

			// Get the indexes of columns for which removal is prevented.
			// The highest index will be used to determine the minimum column count.
			const preventRemovalBlockIndexes = innerBlocks.reduce(
				(acc, block, index) => {
					if ( ! canRemoveBlock(block.clientId)) {
						acc.push(index);
					}
					return acc;
				},
				[]
			);

			return {
				count: getBlockCount(clientId),
				InsertMobileBlock: canInsertBlockType('ds-blocks/ds-column', clientId),
				minCount: Math.max(...preventRemovalBlockIndexes) + 1,
			};
		},
		[clientId],
	);
	const { getBlocks } = useSelect(blockEditorStore);
	const { replaceInnerBlocks } = useDispatch(blockEditorStore);
	const [showModal, setShowModal] = useState(false);
	const pendingUpdate = useRef(null);

	useEffect(() => {
		if (count !== attributes.count) {
			setAttributes({ count });
		}
	}, [count, attributes.count, setAttributes]);

	/**
	 * Updates the column count, including necessary revisions to child Column
	 * blocks to grant required or redistribute available space.
	 *
	 * @param {number} previousColumns Previous column count.
	 * @param {number} newColumns      New column count.
	 */
	function updateColumns( previousColumns, newColumns ) {
		let innerBlocks = getBlocks(clientId)

		const isAddingColumn = newColumns > previousColumns;

		if (isAddingColumn) {
			// Add new columns with default span (1), keep existing spans
			const newlyAddedColumns = newColumns - previousColumns;
			const newBlocks = Array.from({ length: newlyAddedColumns }).map(() =>
				createBlock('ds-blocks/ds-column', { columnSpan: 1 })
			);
			innerBlocks = [...innerBlocks, ...newBlocks];
		} else if (newColumns < previousColumns) {
			// Check for content in columns that would be removed
			const removedBlocks = innerBlocks.slice(newColumns);
			const columnsWithContent = removedBlocks.filter(
				block => (block.innerBlocks && block.innerBlocks.length > 0)
			);
			if (columnsWithContent.length > 0) {
				setShowModal(true);
				pendingUpdate.current = { previousColumns, newColumns };
				return; // Abort column reduction until user confirms
			}
			// Remove columns from the end, keep existing spans
			innerBlocks = innerBlocks.slice(0, newColumns);
		}

		// Set desktop tablet column counts
		setAttributes(
			{
				desktopColumnsPerRow: newColumns,
				tabletCount: newColumns
			}
		);

		replaceInnerBlocks(clientId, innerBlocks)
	}

	function handleModalConfirm() {
		const { newColumns } = pendingUpdate.current || {};
		let innerBlocks = getBlocks(clientId);
		innerBlocks = innerBlocks.slice(0, newColumns);
		replaceInnerBlocks(clientId, innerBlocks);
		setShowModal(false);
		pendingUpdate.current = null;
	}

	function handleModalCancel() {
		setShowModal(false);
		pendingUpdate.current = null;
	}

	return (
		<>
			<PanelBody>
				{ InsertMobileBlock && (
					<>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Columns (Desktop View)')}
							value={count}
							onChange={(value) => updateColumns(count, value)}
							isBlock
						>
							{(() => {
								return Array.from({ length: 6 }, (_, i) => {
									const val = i + 1;
									return (
										<ToggleGroupControlOption
											key={val}
											value={val}
											label={val.toString()}
											disabled={val < minCount}
										/>
									);
								});
							})()}
						</ToggleGroupControl>

						<ToggleControl
							__nextHasNoMarginBottom
							label={__('Enable multi-row columns')}
							checked={isMultirow}
							onChange={
								(value) => {
									setAttributes(
										{
											isMultirow: value,
											desktopColumnsPerRow: count,
										}
									)
								}
							}
							help={__('When enabled, columns will wrap to multiple rows based on columns per row setting')}
						/>

						{isMultirow && (
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={__('Columns Per Row (Desktop)')}
								value={desktopColumnsPerRow}
								onChange={
									(value) => setAttributes(
										{
											desktopColumnsPerRow: value,
											tabletCount: value,
										}
									)
								}
								min={1}
								max={count > 0 ? Math.min(6, count) : 6}
								className='ds-columns-range-control-custom-reset'
								afterIcon={
									() => (
										<Button
											variant='link'
											isDestructive
											disabled={desktopColumnsPerRow === count}
											onClick={() => setAttributes({ desktopColumnsPerRow: count, tabletCount: count })}
											style={{ marginBottom: '0.75rem' }}
										>
											{__('Reset')}
										</Button>
									)
								}
								help={__('Number of columns to display per row in multi-row layout.')}
							/>
						)}
						<div style={{ marginBottom: '1em' }}>
							<HeightControl
								label={__('Gap Between Columns')}
								value={gap ?? 0}
								onChange={
									(newValue) => {
										setAttributes({ gap: newValue });
									}
								}
							/>
						</div>
						<hr/>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Columns Vertical Alignment')}
							onChange={ (newAlign) => setAttributes( { verticalAlign: newAlign } ) }
							value={verticalAlign}
							isAdaptiveWidth
							isDeselectable
						>
							<ToggleGroupControlOptionIcon
								label={__('Top')}
								value='start'
								icon={justifyTop}
							/>
							<ToggleGroupControlOptionIcon
								label={__('Center')}
								value='center'
								icon={justifyCenterVertical}
							/>
							<ToggleGroupControlOptionIcon
								label={__('Bottom')}
								value='end'
								icon={justifyBottom}
							/>
						</ToggleGroupControl>

						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Global Text Alignment')}
							value={textAlign}
							onChange={value => setAttributes({ textAlign: value })}
							isBlock
							isAdaptiveWidth
							isDeselectable
						>
							<ToggleGroupControlOptionIcon
								label={__('Left')}
								value="left"
								icon={alignLeft}
							/>
							<ToggleGroupControlOptionIcon
								label={__('Center')}
								value="center"
								icon={alignCenter}
							/>
							<ToggleGroupControlOptionIcon
								label={__('Right')}
								value="right"
								icon={alignRight}
							/>
						</ToggleGroupControl>
					</>
				)}
			</PanelBody>

			{ InsertMobileBlock && (
				<PanelBody title={__('Responsive Settings')} initialOpen={false}>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Columns per row (Tablet)')}
						value={tabletCount}
						onChange={(value) => setAttributes({ tabletCount: value })}
						min={1}
						max={count > 0 ? Math.min(6, count) : 6}
						className='ds-columns-range-control-custom-reset'
						afterIcon={
							() => (
								<Button
									variant='link'
									isDestructive
									disabled={desktopColumnsPerRow === tabletCount}
									onClick={() => setAttributes({ tabletCount: desktopColumnsPerRow })}
									style={{ marginBottom: '0.75rem' }}
								>
									{__('Reset')}
								</Button>
							)
						}
					/>

					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Columns per row (Mobile)')}
						value={mobileCount}
						onChange={(value) => setAttributes({ mobileCount: value })}
						min={1}
						max={6}
						className='ds-columns-range-control-custom-reset'
						afterIcon={
							() => (
								<Button
									variant='link'
									isDestructive
									disabled={ parseInt(mobileCount) === 1}
									onClick={() => setAttributes({ mobileCount: 1 })}
									style={{ marginBottom: '0.75rem' }}
								>
									{__('Reset')}
								</Button>
							)
						}
					/>

					<ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
						label={__('Global Text Alignment (Mobile)')}
						value={textAlignMobile}
						onChange={(value) => setAttributes({ textAlignMobile: value })}
						isBlock
						isAdaptiveWidth
						isDeselectable
					>
						<ToggleGroupControlOptionIcon
							label={__('Left (Mobile)')}
							value="left"
							icon={alignLeft}
						/>
						<ToggleGroupControlOptionIcon
							label={__('Center (Mobile)')}
							value="center"
							icon={alignCenter}
						/>
						<ToggleGroupControlOptionIcon
							label={__('Right (Mobile)')}
							value="right"
							icon={alignRight}
						/>
					</ToggleGroupControl>

					<ToggleControl
						__nextHasNoMarginBottom
						label={__('Reverse Columns on Mobile')}
						checked={reverseMobile}
						onChange={(value) => setAttributes({ reverseMobile: value })}
					/>
				</PanelBody>
			)}
			{showModal && (
				<Modal
					title={__('Remove Columns with Content?')}
					onRequestClose={handleModalCancel}
					className="ds-columns-modal"
					size='small'
				>
					<p>
						{__('Reducing the number of columns will remove columns that contain content. This action cannot be undone. Do you want to proceed?')}
					</p>
					<ButtonGroup style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '16px' }}>
						<Button
							variant='primary'
							isDestructive
							onClick={handleModalConfirm}
						>
							{__('Remove')}
						</Button>
						<Button
							variant='secondary'
							onClick={handleModalCancel}
						>
							{__('Cancel')}
						</Button>
					</ButtonGroup>
				</Modal>
			)}
		</>
	);
};


/**
 * Columns block edit component.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block attributes setter.
 * @param {string}   props.clientId      Block client ID.
 * @param {Object}   props.wrapperProps  Wrapper props.
 *
 * @return {WPElement} Columns block edit element.
 */
const ColumnsEditContainer = (
	props
) => {
	const { attributes, setAttributes, clientId, wrapperProps } = props;
	const [bordersEnabled, setBordersEnabled] = useState(false);
	const {
		templateLock,
		verticalAlign,
		gap,
		reverseMobile,
		tabletCount,
		mobileCount,
		textAlign = '',
		textAlignMobile = '',
		isMultirow = false,
		desktopColumnsPerRow,
		moduleVariant = '',
		backgroundColor,
		backgroundImage: savedBackgroundImage,
		decorations: savedDecorations
	} = attributes;

	const [backgroundImage, setBackgroundImage] = useState(savedBackgroundImage || []);
	const [decorations, setDecorations] = useState(savedDecorations || []);

	// Sync backgroundImage and decorations with attributes
	useEffect(() => { setAttributes({ backgroundImage }); }, [backgroundImage]);
	useEffect(() => { setAttributes({ decorations }); }, [decorations]);

	// Get information about child columns
	const { innerBlocks } = useSelect(
		(select) => {
			const { getBlocks } = select(blockEditorStore);
			return {
				innerBlocks: getBlocks(clientId)
			};
		},
		[clientId]
	);

	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classnames(
				wrapperProps.className,
				'ds-columns',
				{
					'has-borders-enabled': bordersEnabled,
					'ds-columns-is-multirow': isMultirow,
				},
				moduleVariant
			),
		}
	);

	// Add inline styles for wrapperProps if gap is set.
	if (gap) {
		blockProps.style = {
			...blockProps.style,
			'--ds-row-gap': `${gap}`,
		};
	}

	// Desktop CSS variables
	if (typeof desktopColumnsPerRow === 'number' && desktopColumnsPerRow > 0) {
		const desktopGridColumns = Array.from({ length: desktopColumnsPerRow }, (_, i) => {
			const innerBlock = innerBlocks[i];
			const colDesktop = innerBlock && innerBlock.attributes && typeof innerBlock.attributes.columnSpan === 'number' && innerBlock.attributes.columnSpan > 0
				? innerBlock.attributes.columnSpan
				: 1;
			return `var(--col${i + 1}_desktop-fr, ${colDesktop}fr)`;
		}).join(' ');
		blockProps.style['--grid-template-columns'] = desktopGridColumns;
		blockProps.style['--ds-columns-count'] = desktopColumnsPerRow;
		if (innerBlocks && innerBlocks.length) {
			innerBlocks.slice(0, desktopColumnsPerRow).forEach((block, index) => {
				const colDesktop = typeof block.attributes.columnSpan === 'number' && block.attributes.columnSpan > 0
					? block.attributes.columnSpan
					: 1;
				blockProps.style[`--col${index + 1}_desktop-fr`] = `${colDesktop}fr`;
			});
		}
	}

	// Tablet CSS variables
	if (typeof tabletCount === 'number' && tabletCount > 0) {
		const tabletGridColumns = Array.from({ length: tabletCount }, (_, i) => {
			const innerBlock = innerBlocks[i];
			const colTablet = innerBlock && innerBlock.attributes && typeof innerBlock.attributes.columnSpanTablet === 'number' && innerBlock.attributes.columnSpanTablet > 0
				? innerBlock.attributes.columnSpanTablet
				: 1;
			return `var(--col${i + 1}_tablet-fr, ${colTablet}fr)`;
		}).join(' ');
		blockProps.style['--grid-template-columns_tablet'] = tabletGridColumns;
		if (innerBlocks && innerBlocks.length) {
			innerBlocks.slice(0, tabletCount).forEach((block, index) => {
				const colTablet = typeof block.attributes.columnSpanTablet === 'number' && block.attributes.columnSpanTablet > 0
					? block.attributes.columnSpanTablet
					: 1;
				blockProps.style[`--col${index + 1}_tablet-fr`] = `${colTablet}fr`;
			});
		}
	}

	// Mobile CSS variables
	if (typeof mobileCount === 'number' && mobileCount > 0) {
		const mobileGridColumns = Array.from({ length: mobileCount }, (_, i) => {
			const innerBlock = innerBlocks[i];
			const colMobile = innerBlock && innerBlock.attributes && typeof innerBlock.attributes.columnSpanMobile === 'number' && innerBlock.attributes.columnSpanMobile > 0
				? innerBlock.attributes.columnSpanMobile
				: 1;
			return `var(--col${i + 1}_mobile-fr, ${colMobile}fr)`;
		}).join(' ');
		blockProps.style['--grid-template-columns_mobile'] = mobileGridColumns;
		blockProps.style['--ds-columns-count_mobile'] = mobileCount;
		if (innerBlocks && innerBlocks.length) {
			innerBlocks.slice(0, mobileCount).forEach((block, index) => {
				const colMobile = typeof block.attributes.columnSpanMobile === 'number' && block.attributes.columnSpanMobile > 0
					? block.attributes.columnSpanMobile
					: 1;
				blockProps.style[`--col${index + 1}_mobile-fr`] = `${colMobile}fr`;
			});
		}
	}

	const innerBlocksProps = useInnerBlocksProps(
		{
			...blockProps,
			className: classnames(
				'ds-row',
				verticalAlign ? `items-${verticalAlign}` : '',
				reverseMobile ? 'reverse-mobile' : '',
				textAlign ? `text-${textAlign}` : '',
				textAlignMobile ? `text-${textAlignMobile}-mobile` : '',
			),
		},
		{
			defaultBlock: DEFAULT_BLOCK,
			directInsert: true,
			orientation: 'horizontal',
			renderAppender: false,
			templateLock,
		},
	);

	return (
		<>
			<ClientLockControl>
				<InspectorControls group="styles">
					<PanelBody title={__('Background & Decorations')} initialOpen={true}>
						<DstBackgroundColorPicker
							label={__('Background Color')}
							value={backgroundColor}
							onChange={val => setAttributes({ backgroundColor: val })}
						/>
						<DstBackgroundImagePicker
							label={__('Background Media')}
							value={backgroundImage}
							onChange={setBackgroundImage}
						/>
						<DstDecorationsPicker
							label={__('Decorative Elements')}
							value={decorations}
							onChange={setDecorations}
						/>
					</PanelBody>
				</InspectorControls>
				<InspectorControls>
					<ColumnInspectorControls
						clientId={clientId}
						attributes={attributes}
						setAttributes={setAttributes}
					/>
					<PanelBody title={__('Column Variants')} initialOpen={false}>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('')}
							value={moduleVariant}
							onChange={(newVariant) => setAttributes({ moduleVariant: newVariant })}
							isBlock
						>
							<ToggleGroupControlOption
								value=""
								label={__('Default')}
							/>
							<ToggleGroupControlOption
								value="column-v1"
								label={__('Variant 1')}
							/>
							<ToggleGroupControlOption
								value="column-v2"
								label={__('Variant 2')}
							/>
						</ToggleGroupControl>
					</PanelBody>
				</InspectorControls>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon={grid}
							label={bordersEnabled ? __('Turn off borders') : __('Turn on borders')}
							isPressed={bordersEnabled}
							className="dst-columns__border-toggle"
							onClick={() => setBordersEnabled((prev) => !prev)}
						/>
					</ToolbarGroup>
				</BlockControls>
			</ClientLockControl>

			<div {...blockProps} style={{ ...blockProps.style, background: backgroundColor || undefined }}>
				<DstDecorationsRender value={decorations} />
				<DstBackgroundImageRender value={backgroundImage} />
				<div {...innerBlocksProps} />
			</div>
		</>
	);
};

/**
 * Placeholder component for the Columns block.
 *
 * @param {Object}   props               Component props.
 * @param {string}   props.clientId      Block client ID.
 * @param {string}   props.name          Block name.
 * @param {Function} props.setAttributes Block attributes setter.
 *
 * @return {WPElement} Columns block placeholder element.
 */
const Placeholder = (
	{
		clientId,
		name,
		setAttributes
	}
) => {
	const { blockType, defaultVariation, variations } = useSelect(
		(select) => {
			const { getBlockVariations, getBlockType, getDefaultBlockVariation } = select(blocksStore);
			return {
				blockType: getBlockType(name),
				defaultVariation: getDefaultBlockVariation(name, 'block'),
				variations: getBlockVariations(name, 'block'),
			};
		},
		[name],
	);
	const { replaceInnerBlocks } = useDispatch( blockEditorStore );
	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<BlockVariationPicker
				icon={blockType?.icon?.src}
				label={blockType?.title}
				variations={variations}
				instructions={__('Divide into columns. Select a layout:')}
				onSelect={
					(nextVariation = defaultVariation) => {
						if (nextVariation.attributes) {
							setAttributes(nextVariation.attributes);
						}
						if (nextVariation.innerBlocks) {
							// Create blocks from the template
							const innerBlocks = createBlocksFromInnerBlocksTemplate(nextVariation.innerBlocks);

							// Replace inner blocks
							replaceInnerBlocks(
								clientId,
								innerBlocks,
								true,
							);
						}
					}
				}
				allowSkip
			/>
		</div>
	);
};

export const BlockEdit = (props) => {
	const { clientId } = props;
	const hasInnerBlocks = useSelect(
		(select) => select(blockEditorStore).getBlocks(clientId).length > 0,
		[clientId]
	);
	const Component = hasInnerBlocks ? ColumnsEditContainer : Placeholder;

	return <Component {...props} wrapperProps={props.wrapperProps} />;
};
