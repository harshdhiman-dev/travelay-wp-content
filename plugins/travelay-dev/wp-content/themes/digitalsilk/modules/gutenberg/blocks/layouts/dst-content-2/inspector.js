import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	BaseControl,
	ToggleControl,
	Flex,
	FlexBlock,
	Icon,
	RangeControl,
	TabPanel,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import {
	pullRight,
	pullLeft,
	justifyTop,
	justifyCenterVertical,
	justifyBottom,
	justifyLeft,
	justifyRight,
	justifyCenter,
	desktop as desktopIcon,
	mobile as mobileIcon
} from '@wordpress/icons';
import { ClientLockControl, isClientLocked, DstRangeUnits } from '../../../react-components';

export const Content2InspectorControls = ({ blockProps }) => {
	const { attributes, setAttributes } = blockProps;
	const {
		columnsOrder,
		columnsOrderMobile,
		contentRatio,
		columnsGap,
		isVertical,
		textPaddingLeftDesktop,
		textPaddingLeftMobile,
		textPaddingRightDesktop,
		textPaddingRightMobile,
		textYAlign,
		mediaXAlign,
		mediaYAlign,
	} = attributes;

    const isClient = isClientLocked();

    let units = [
        { value: 'px', label: 'px', default: 0 },
        { value: 'rem', label: 'rem', default: 0 },
        { value: '%', label: '%', default: 10 },
        { value: 'vmin', label: 'vmin', default: 0 },
        { value: 'vmax', label: 'vmax', default: 0 },
        { value: 'vw', label: 'vw', default: 0 },
        { value: 'vh', label: 'vh', default: 0 },
    ];
    if ( isClient ) {
        units = [
            { value: 'px', label: 'px', default: 0 },
            { value: 'rem', label: 'rem', default: 0 },
        ];
    }

	return (
		<>
			<InspectorControls>
				<ClientLockControl>
					<PanelBody title={__('General Layout')}>
						<TabPanel
							className="ds-responsive-styles-tabs"
							tabs={[
								{
									name: 'desktop',
									title: (
										<Flex align='center' gap={2}>
											<Icon icon={desktopIcon} />
											<span>{__('Desktop')}</span>
										</Flex>
									),
								},
								{
									name: 'mobile',
									title: (
										<Flex align='center' gap={2}>
											<Icon icon={mobileIcon} />
											<span>{__('Mobile')}</span>
										</Flex>
									),
								}
							]}
						>
							{(tab) => (
								'desktop' === tab.name ? (
									<>
										<ToggleGroupControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={__('Content Order')}
											value={columnsOrder}
											onChange={(newOrder) => setAttributes({ columnsOrder: newOrder })}
											isBlock
										>
											<ToggleGroupControlOption
												value="order-default"
												label={
													<Flex>
														<Icon icon={pullRight} />
														<FlexBlock>
															{__('Text First')}
														</FlexBlock>
													</Flex>
												}
											/>
											<ToggleGroupControlOption
												value="order-reverse"
												label={
													<Flex>
														<Icon icon={pullLeft} />
														<FlexBlock>
															{__('Media First')}
														</FlexBlock>
													</Flex>
												}
											/>
										</ToggleGroupControl>

										<RangeControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											initialPosition={50}
											value={contentRatio}
											label={__('Content Ratio')}
											max={100}
											min={0}
											onChange={(newValue) => setAttributes({ contentRatio: newValue })}
										/>
										<DstRangeUnits
											label={__('Columns Gap')}
											value={columnsGap}
											onChange={(newValue) => setAttributes({ columnsGap: newValue })}
										/>

										<hr />

										<ToggleControl
											__nextHasNoMarginBottom
											label={__('Vertical Layout')}
											checked={isVertical}
											onChange={(newValue) => setAttributes({ isVertical: newValue })}
										/>
									</>
								) : (
									<>
										<ToggleGroupControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={__('Mobile Content Order')}
											value={columnsOrderMobile}
											onChange={(newOrder) => setAttributes({ columnsOrderMobile: newOrder })}
											isBlock
										>
											<ToggleGroupControlOption
												value="order-default-mobile"
												label={
													<Flex>
														<Icon icon={pullRight} />
														<FlexBlock>
															{__('Text First')}
														</FlexBlock>
													</Flex>
												}
											/>
											<ToggleGroupControlOption
												value="order-reverse-mobile"
												label={
													<Flex>
														<Icon icon={pullLeft} />
														<FlexBlock>
															{__('Media First')}
														</FlexBlock>
													</Flex>
												}
											/>
										</ToggleGroupControl>
									</>
								)
							)}
						</TabPanel>
					</PanelBody>
				</ClientLockControl>
				<PanelBody title={__('Text Layout')} initialOpen={false}>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Vertical Alignment')}
						value={textYAlign}
						onChange={(newValue) => setAttributes({ textYAlign: newValue })}
						isAdaptiveWidth
					>
						<ToggleGroupControlOptionIcon
							value="align-top"
							icon={justifyTop}
							label={__('Top')}
						/>
						<ToggleGroupControlOptionIcon
							value="align-center"
							icon={justifyCenterVertical}
							label={__('Center')}
						/>
						<ToggleGroupControlOptionIcon
							value="align-bottom"
							icon={justifyBottom}
							label={__('Bottom')}
						/>
					</ToggleGroupControl>
					<BaseControl
						id={false}
						label={__('Padding Left')}
					>
						<Flex>
							<FlexBlock>
								<UnitControl
									__next40pxDefaultSize
									label={__('Desktop')}
									value={textPaddingLeftDesktop}
									onChange={
										(newValue) => setAttributes({ textPaddingLeftDesktop: newValue })
									}
									units={units}
								/>
							</FlexBlock>
							<FlexBlock>
								<UnitControl
									__next40pxDefaultSize
									label={__('Mobile')}
									value={textPaddingLeftMobile}
									onChange={
										(newValue) => setAttributes({ textPaddingLeftMobile: newValue })
									}
									units={units}
								/>
							</FlexBlock>
						</Flex>
					</BaseControl>
					<BaseControl
						id={false}
						label={__('Padding Right')}
					>
						<Flex>
							<FlexBlock>
								<UnitControl
									__next40pxDefaultSize
									label={__('Desktop')}
									value={textPaddingRightDesktop}
									onChange={
										(newValue) => setAttributes({ textPaddingRightDesktop: newValue })
									}
									units={units}
								/>
							</FlexBlock>
							<FlexBlock>
								<UnitControl
									__next40pxDefaultSize
									label={__('Mobile')}
									value={textPaddingRightMobile}
									onChange={
										(newValue) => setAttributes({ textPaddingRightMobile: newValue })
									}
									units={units}
								/>
							</FlexBlock>
						</Flex>
					</BaseControl>
				</PanelBody>
				<PanelBody title={__('Media Layout')} initialOpen={false}>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Vertical Alignment')}
						value={mediaYAlign}
						onChange={(newValue) => setAttributes({ mediaYAlign: newValue })}
						isAdaptiveWidth
					>
						<ToggleGroupControlOptionIcon
							value="media-justify-top"
							icon={justifyTop}
							label={__('Top')}
						/>
						<ToggleGroupControlOptionIcon
							value="media-justify-center"
							icon={justifyCenterVertical}
							label={__('Center')}
						/>
						<ToggleGroupControlOptionIcon
							value="media-justify-bottom"
							icon={justifyBottom}
							label={__('Bottom')}
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__('Horizontal Alignment')}
						value={mediaXAlign}
						onChange={(newValue) => setAttributes({ mediaXAlign: newValue })}
						isAdaptiveWidth
					>
						<ToggleGroupControlOptionIcon
							value="media-to-left"
							icon={justifyLeft}
							label={__('Left')}
						/>
						<ToggleGroupControlOptionIcon
							value="media-to-center"
							icon={justifyCenter}
							label={__('Center')}
						/>
						<ToggleGroupControlOptionIcon
							value="media-to-right"
							icon={justifyRight}
							label={__('Right')}
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
