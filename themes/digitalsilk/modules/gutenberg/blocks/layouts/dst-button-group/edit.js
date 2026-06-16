import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InnerBlocks,
	InspectorControls,
	BlockControls,
} from "@wordpress/block-editor";
import {
	RangeControl,
	PanelBody,
	ToolbarGroup,
	ToolbarButton,
    ToolbarDropdownMenu,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
} from "@wordpress/components";
// eslint-disable-next-line import/no-extraneous-dependencies
import { alignCenter, alignLeft, alignRight, arrowDown, arrowRight } from '@wordpress/icons';
import classNames from 'classnames';
import './editor.scss';

export default function Edit(props) {
	const { attributes, setAttributes, wrapperProps } = props;
	const { alignment, gapBetween, justifyContent, justifyContentMobile } = attributes;
	const blockProps = useBlockProps(
		{
			...wrapperProps,
			className: classNames( wrapperProps?.className, `dst-button-group c-block_btn is-${alignment}` ),
		}
	);
	blockProps.style = {
		...blockProps.style,
		"--gap": gapBetween + 'px',
		"--v-align": justifyContent || "center",
		"--v-align-mobile": justifyContentMobile || justifyContent,
	};

	const innerBlocksProps = useInnerBlocksProps(
		blockProps,
		{
			template: [
				[
					'ds-blocks/c-btn',
					{
						btnType: 'primary',
						btnSize: 'default',
						text: __('Primary Button'),
						link: {
							url: '#',
						}
					}
				],
			],
			allowedBlocks: [ 'ds-blocks/c-btn' ],
			templateLock: false,
			renderAppender: () => (
				<div className='dst-button-group-appender'>
					<InnerBlocks.ButtonBlockAppender />
				</div>
			),
		}
	);

	// Map direction icons to their components
	const directionIcons = {
		horizontal: arrowRight,
		vertical: arrowDown,
	};

	// Map alignment icons to their components
	const alignmentIcons = {
		'flex-start': alignLeft,
		center: alignCenter,
		'flex-end': alignRight,
	};


	return (
		<>
			<InspectorControls>
				<PanelBody>
					<ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
						label={__('Direction')}
						value={alignment}
						onChange={(newValue) => setAttributes({ alignment: newValue })}
					>
						<ToggleGroupControlOptionIcon
							value="horizontal"
							icon={arrowRight}
							label={__('Horizontal')}
						/>
						<ToggleGroupControlOptionIcon
							value="vertical"
							icon={arrowDown}
							label={__('Vertical')}
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
						label={__('Alignment')}
						value={justifyContent}
						onChange={(newValue) => {
							setAttributes({
								justifyContent: newValue,
								justifyContentMobile: newValue
							})
						}}
					>
						<ToggleGroupControlOptionIcon
							value="flex-start"
							label={__('Left')}
							icon={alignLeft}
						/>
						<ToggleGroupControlOptionIcon
							value="center"
							label={__('Center')}
							icon={alignCenter}
						/>
						<ToggleGroupControlOptionIcon
							value="flex-end"
							label={__('Right')}
							icon={alignRight}
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
						label={__('Mobile Alignment')}
						value={justifyContentMobile || justifyContent}
						onChange={(newValue) => {
							setAttributes({
								justifyContentMobile: newValue
							})
						}}
					>
						<ToggleGroupControlOptionIcon
							value="flex-start"
							label={__('Left')}
							icon={alignLeft}
						/>
						<ToggleGroupControlOptionIcon
							value="center"
							label={__('Center')}
							icon={alignCenter}
						/>
						<ToggleGroupControlOptionIcon
							value="flex-end"
							label={__('Right')}
							icon={alignRight}
						/>
					</ToggleGroupControl>
					<RangeControl
						min={0}
						max={100}
						label={__('Gap Between Buttons')}
						value={gapBetween}
						onChange={(newValue) => setAttributes({ gapBetween: newValue})}
					/>
				</PanelBody>
			</InspectorControls>
            <BlockControls>
                <ToolbarGroup>
					<ToolbarButton
						icon={directionIcons[alignment]}
						title={__('Direction')}
						onClick={() => {
							setAttributes({
								alignment: alignment === 'horizontal' ? 'vertical' : 'horizontal'
							})
						}}
					/>
					<ToolbarDropdownMenu
						icon={alignmentIcons[justifyContent]}
						label={__('Alignment')}
						controls={ [
							{
								title: __('Left'),
								icon: alignLeft,
								onClick: () => setAttributes({ justifyContent: 'flex-start' }),
								isDisabled: justifyContent === 'flex-start',
							},
							{
								title: __('Center'),
								icon: alignCenter,
								onClick: () => setAttributes({ justifyContent: 'center' }),
								isDisabled: justifyContent === 'center',
							},
							{
								title: __('Right'),
								icon: alignRight,
								onClick: () => setAttributes({ justifyContent: 'flex-end' }),
								isDisabled: justifyContent === 'flex-end',
							},
						] }
					/>
                </ToolbarGroup>
            </BlockControls>
			<div {...innerBlocksProps} />
		</>
	)
}
