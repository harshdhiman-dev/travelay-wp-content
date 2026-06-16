import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from "@wordpress/block-editor";
import {
	RangeControl,
	PanelBody,
	__experimentalBoxControl as BoxControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption
} from "@wordpress/components";
import './editor.scss';
import { ListToolbar } from './toolbar';
import { ListStyles } from './styles';
import { DstRangeUnits} from "../../../react-components";
import classnames from 'classnames';

export default function Edit(props) {
	const { attributes, setAttributes, wrapperProps } = props;
	const {
		colCount,
		gapBetween,
		alignment,
		iconsSize,
		subtitleSize,
		heroTextSize,
		showHeroText,
		colorHero,
		colorTitle,
		colorSubtitle,
		showIcons,
		colorIcon,
		gapBetweenContent,
		titleSize,
		titleLineHeight,
		titleTransform,
		titleWeight,
		itemPadding,
		hasVerticalBorder,
		hasHorizontalBorder,
		moduleVariant,
		iconAlignment,
	} = attributes;
	const blockProps = useBlockProps(
		{
			...wrapperProps,
		}
	);
	// Add inline styles for wrapperProps if is set.
	blockProps.style = {
		...blockProps.style,
		"--dst-list__col": colCount,
		"--dst-list__gap": gapBetween,
		"--dst-list__direction": alignment || "row",
		"--dst-list__item-padding": itemPadding ? `${itemPadding.top || '0rem'} ${itemPadding.right || '0rem'} ${itemPadding.bottom || '0rem'} ${itemPadding.left || '0rem'}` : undefined,
		"--dst-list__media-size": ( iconsSize ) ? iconsSize : undefined,
		"--dst-list__media-align": ( showIcons ) ? iconAlignment : undefined,
		"--dst-list__subtitle-size": subtitleSize,
		"--dst-list__title-size": titleSize,
		"--dst-list__title-lh": titleLineHeight,
		"--dst-list__title-transform": titleTransform,
		"--dst-list__title-weight": titleWeight,
		"--dst-list__title-color": colorTitle,
		"--dst-list__subtitle-color": colorSubtitle,
		"--dst-list__hero-size": ( showHeroText ) ? heroTextSize : undefined,
		"--dst-list__hero-color": ( showHeroText ) ? colorHero : undefined,
		"--dst-list__media-color": ( showIcons ) ? colorIcon : undefined,
		"--dst-list__content-gap": ( showIcons || showHeroText ) ? gapBetweenContent : undefined,
	};

	const { className, ...innerBlocksProps } = useInnerBlocksProps(
		blockProps,
		{
			template: [
				[ 'ds-blocks/c-list-item', {} ],
				[ 'ds-blocks/c-list-item', {} ],
				[ 'ds-blocks/c-list-item', {} ],
			],
			allowedBlocks: ["ds-blocks/c-list-item"]
		}
	);

	// Create classes for borders
	const borderClasses = classnames(
		{
			'has-vertical-border': hasVerticalBorder,
			'has-horizontal-border': hasHorizontalBorder,
		}
	)

	return (
		<>
			<ListToolbar blockProps={props} />
			<ListStyles blockProps={props} />
			<InspectorControls group='settings'>
				<PanelBody className="dst-list-settings-panel">
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						className="dst-list-columns"
						min={1}
						max={10}
						label={__('List Item Columns')}
						value={colCount}
						onChange={(newValue) => {
							setAttributes({
								colCount: newValue,
							});
						}}
					/>
					<DstRangeUnits
						label={__('List Items - Vertical Gap')}
						value={gapBetween}
						onChange={(newValue) => {
							setAttributes({
								gapBetween: newValue,
							});
						}}
					/>
					<BoxControl
						label={__('List Item - Inner Padding')}
						values={attributes.itemPadding || { top: '0rem', right: '0rem', bottom: '0rem', left: '0rem' }}
						onChange={(newValue) => {
							setAttributes({
								itemPadding: newValue,
							});
						}}
						units={[
							{ value: 'rem', label: 'rem', default: 0 },
							{ value: 'px', label: 'px', default: 0 },
							{ value: 'em', label: 'em', default: 0 },
							{ value: '%', label: '%', default: 0 },
						]}
						defaultUnit="rem"
					/>
					{
						(showIcons || showHeroText) && (
							<>
								<DstRangeUnits
									label={__('Space Between Hero and Text')}
									value={gapBetweenContent}
									onChange={(newValue) => {
										setAttributes({
											gapBetweenContent: newValue,
										});
									}}
								/>
								{showIcons && (
									<>
										<DstRangeUnits
											label={__('Icon Area Size')}
											value={iconsSize}
											onChange={(newValue) => {
												setAttributes({
													iconsSize: newValue,
												});
											}}
										/>
										<ToggleGroupControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={__('Icon Alignment')}
											value={iconAlignment}
											onChange={(newValue) => setAttributes({ iconAlignment: newValue })}
											isBlock
										>
											<ToggleGroupControlOption
												value="flex-start"
												label={__('Start')}
											/>
											<ToggleGroupControlOption
												value="center"
												label={__('Center')}
											/>
											<ToggleGroupControlOption
												value="flex-end"
												label={__('End')}
											/>
										</ToggleGroupControl>
									</>
								)}
							</>
						)
					}
				</PanelBody>
				<PanelBody title={__('List Variants')} initialOpen={false}>
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
							value="list-v1"
							label={__('Variant 1')}
						/>
						<ToggleGroupControlOption
							value="list-v2"
							label={__('Variant 2')}
						/>
						<ToggleGroupControlOption
							value="list-v3"
							label={__('Variant 3')}
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<ul className={`${className} is-${alignment} dst-list ${borderClasses} ${moduleVariant}`} {...innerBlocksProps} />
		</>
	)
}
