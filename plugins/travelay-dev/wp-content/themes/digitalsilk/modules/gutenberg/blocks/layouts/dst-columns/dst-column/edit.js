/**
 * WordPress dependencies
 */
import { useState, useRef } from '@wordpress/element';
import {
	InnerBlocks,
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToolbarDropdownMenu,
	ToolbarGroup,
	RangeControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalAlignmentMatrixControl as AlignmentMatrixControl,
	Popover,
	Flex,
	Icon,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { sprintf, __ } from '@wordpress/i18n';
// eslint-disable-next-line import/no-extraneous-dependencies
import {
	desktop,
	tablet
} from '@wordpress/icons';
import { ClientLockControl } from '../../../../react-components';
import classnames from 'classnames';

/**
 * Column block controls.
 *
 * @param {Object} props                  - Component props.
 * @param {Object} props.setAttributes    - Function to set block attributes.
 * @param {number} props.columnSpan       - The number of columns the block spans.
 * @param {number} props.columnSpanTablet - The number of columns the block spans on tablet.
 * @param {string} props.alignVertical    - The vertical alignment of the block.
 * @param {string} props.alignHorizontal  - The horizontal alignment of the block.
 *
 * @return {JSX.Element} Column block controls.
 */
const ColumnBlockControls = (
	{
		setAttributes,
		columnSpan,
		columnSpanTablet,
		alignVertical,
		alignHorizontal
	}
) => {
	// Set popover states.
	const [ popoverVisibleDesktop, setPopoverVisibleDesktop ] = useState(false);
	const [ popoverVisibleTablet, setPopoverVisibleTablet ] = useState(false);
	// Set references to the buttons.
	const popRef = useRef();
	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={ () => (
							<Flex gap={1}>
								<Icon icon='columns' />
								<span>{__('Column Width')}</span>
							</Flex>

						) }
						label={__('Width')}
						ref={popRef}
						controls={
							[
								{
									title: __( 'Desktop -' ) + Math.round((columnSpan / 6) * 100) + '% column width ( ' + columnSpan + '/6 )',
									icon: desktop,
									onClick: () => setPopoverVisibleDesktop(true),
								},
								{
									title: __( 'Tablet -' ) + Math.round((columnSpanTablet / 6) * 100) + '% column width ( ' + columnSpanTablet + '/6 )',
									icon: tablet,
									onClick: () => setPopoverVisibleTablet(true),
								}
							]
						}
					/>
				</ToolbarGroup>
				<ToolbarGroup>
					<BlockAlignmentMatrixControl
						value={
							alignVertical && alignHorizontal
								? `${alignVertical} ${alignHorizontal}`
								: ''
						}
						onChange={value => {
							const [vertical, horizontal] = value.split(' ');
							setAttributes({ alignVertical: vertical, alignHorizontal: horizontal });
						}}
					/>
				</ToolbarGroup>
			</BlockControls>
			{/* Popover for the desktop columns */}
			{popoverVisibleDesktop && (
				<Popover
					position="bottom center"
					onClose={() => setPopoverVisibleDesktop(false)}
					anchor={popRef.current}
					variant="toolbar"
				>
					<div style={
						{
							width: '224px',
							padding: '1em',
						}
					}>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Desktop Columns')}
							value={columnSpan}
							onChange={(newValue) => setAttributes({ columnSpan: parseFloat(newValue) })}
							isAdaptiveWidth
						>
							{[...Array(6)].map((_, index) => {
								const value = index + 1;
								return (
									<ToggleGroupControlOption
										key={value}
										label={value}
										value={value}
									/>
								);
							})}
						</ToggleGroupControl>
					</div>
				</Popover>
			)}
			{/* Popover for the tablet columns */}
			{popoverVisibleTablet && (
				<Popover
					position="bottom center"
					onClose={() => setPopoverVisibleTablet(false)}
					anchor={popRef.current}
					variant="toolbar"
				>
					<div style={
						{
							width: '224px',
							padding: '1em',
						}
					}>
						<ToggleGroupControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={__('Tablet Columns')}
							value={columnSpanTablet}
							onChange={(newValue) => setAttributes({ columnSpanTablet: parseInt(newValue, 10) })}
							isAdaptiveWidth
						>
							{[...Array(6)].map((_, index) => {
								const value = index + 1;
								return (
									<ToggleGroupControlOption
										key={value}
										label={value}
										value={value}
									/>
								);
							})}
						</ToggleGroupControl>
					</div>
				</Popover>
			)}
		</>
	);
};

/**
 * Column inspector controls.
 *
 * @param {Object} props                  - Component props.
 * @param {number} props.columnSpan       - The number of columns the block spans.
 * @param {number} props.columnSpanTablet - The number of columns the block spans on tablet.
 * @param {string} props.alignVertical    - The vertical alignment of the block.
 * @param {string} props.alignHorizontal  - The horizontal alignment of the block.
 * @param {Object} props.setAttributes    - Function to set block attributes.
 *
 * @return {JSX.Element} Column inspector controls.
 */
const ColumnInspectorControls = (
	{
		columnSpan,
		columnSpanTablet,
		columnMinHeight,
		columnMinHeightMobile,
		alignVertical = '',
		alignHorizontal = '',
		setAttributes
	}
) => {
	return (
		<>
			<PanelBody>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={__('Desktop Column Width')}
					value={columnSpan}
					onChange={(newValue) => setAttributes({ columnSpan: parseFloat(newValue) })}
					min={0.25}
					max={6}
					step={0.25}
				/>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={__('Tablet Column Width')}
					value={columnSpanTablet}
					onChange={(newValue) => setAttributes({ columnSpanTablet: parseFloat(newValue) })}
					min={0.25}
					max={6}
					step={0.25}
				/>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={__('Column Min Height (px)')}
					value={columnMinHeight}
					onChange={(newValue) => setAttributes({ columnMinHeight: parseFloat(newValue) })}
					min={0}
					max={1000}
					step={5}
				/>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={__('Column Min Height Mobile (px)')}
					value={columnMinHeightMobile}
					onChange={(newValue) => setAttributes({ columnMinHeightMobile: parseFloat(newValue) })}
					min={0}
					max={500}
					step={5}
				/>
			</PanelBody>
			<PanelBody>
				<h3>{__('Alignment')}</h3>
				<AlignmentMatrixControl
					label={__('Alignment')}
					value={
						alignVertical && alignHorizontal
							? `${alignVertical} ${alignHorizontal}`
							: ''
					}
					onChange={value => {
						const [vertical, horizontal] = value.split(' ');
						setAttributes({ alignVertical: vertical, alignHorizontal: horizontal });
					}}
					isDeselectable
				/>
			</PanelBody>
		</>
	);
};

/**
 * Column block edit component.
 *
 * @param {Object} props               - Component props.
 * @param {Object} props.attributes    - Block attributes.
 * @param {Object} props.setAttributes - Function to set block attributes.
 * @param {string} props.clientId      - Block client ID.
 *
 * @return {JSX.Element} Column block edit component.
 */
export const BlockEdit = (props) => {
	const { attributes, setAttributes, clientId, wrapperProps } = props;
	const { templateLock, allowedBlocks, columnSpan, columnSpanTablet, columnMinHeight, columnMinHeightMobile, alignVertical, alignHorizontal } = attributes;

	const { columnsIds, hasChildBlocks } = useSelect(
		(select) => {
			const { getBlockOrder, getBlockRootClientId } = select(blockEditorStore);

			const rootId = getBlockRootClientId(clientId);

			return {
				hasChildBlocks: getBlockOrder(clientId).length > 0,
				rootClientId: rootId,
				columnsIds: getBlockOrder(rootId),
			};
		},
		[clientId],
	);

	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classnames(
				'ds-column',
				// eslint-disable-next-line no-nested-ternary
				alignHorizontal === 'left' ? 'items-start' : alignHorizontal === 'center' ? 'items-center' : alignHorizontal === 'right' ? 'items-end' : '',
				// eslint-disable-next-line no-nested-ternary
				alignVertical === 'top' ? 'flex-left' : alignVertical === 'center' ? 'flex-center' : alignVertical === 'bottom' ? 'flex-right' : '',
			),
		}
	);

	const columnsCount = columnsIds.length;
	const currentColumnPosition = columnsIds.indexOf(clientId) + 1;

	const label = sprintf(
		/* translators: 1: Block label (i.e. "Block: Column"), 2: Position of the selected block, 3: Total number of sibling blocks of the same type */
		__('%1$s (%2$d of %3$d)'),
		blockProps['aria-label'],
		currentColumnPosition,
		columnsCount,
	);

	// Set frontend style for tablet column width
	blockProps.style = blockProps.style || {};
	if (typeof columnSpanTablet === 'number' && columnSpanTablet > 0 && columnSpanTablet !== 6) {
		blockProps.style[`--col${currentColumnPosition}_tablet-fr`] = `${columnSpanTablet}fr`;
	}

	if (typeof columnHeight === 'number' && columnHeight > 0 ) {
		blockProps.style['--column-min-height'] = `${columnHeight}px`;
	}

	if (typeof columnHeightMobile === 'number' && columnHeightMobile > 0 ) {
		blockProps.style['--column-min-height-mobile'] = `${columnHeightMobile}px`;
	}

	const innerBlocksProps = useInnerBlocksProps(
		{
			...blockProps,
			'aria-label': label
		},
		{
			templateLock,
			allowedBlocks,
			renderAppender: hasChildBlocks ? undefined : InnerBlocks.ButtonBlockAppender,
		},
	);

	return (
		<>
			<ClientLockControl>
				<BlockControls>
					<ColumnBlockControls
						setAttributes={setAttributes}
						columnSpan={columnSpan}
						columnSpanTablet={columnSpanTablet}
						alignVertical={alignVertical}
						alignHorizontal={alignHorizontal}
					/>
				</BlockControls>
				<InspectorControls>
					<ColumnInspectorControls
						columnSpan={columnSpan}
						columnSpanTablet={columnSpanTablet}
						alignVertical={alignVertical}
						alignHorizontal={alignHorizontal}
						setAttributes={setAttributes}
						columnMinHeight={columnMinHeight}
						columnMinHeightMobile={columnMinHeightMobile}
					/>
				</InspectorControls>
			</ClientLockControl>
			<div {...innerBlocksProps} />
		</>
	);
};
