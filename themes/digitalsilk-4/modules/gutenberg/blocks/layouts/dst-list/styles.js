import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useSettings,
	LineHeightControl,
} from "@wordpress/block-editor";
import {
	PanelBody,
	FontSizePicker,
	BaseControl,
	Flex,
	FlexItem,
	ToggleControl,
	SelectControl,
} from "@wordpress/components";
import './editor.scss';
import { isClientLocked, DstColorPicker } from '../../../react-components';

export const ListStyles = (
	{
		blockProps,
	}
) => {
	const { attributes, setAttributes } = blockProps;
	const { showSubtitle, subtitleSize, heroTextSize, showHeroText, colorHero, colorSubtitle, showIcons, colorIcon, titleSize, titleLineHeight, colorTitle, titleTransform, titleWeight, hasVerticalBorder, hasHorizontalBorder } = attributes;

	// Extract font sizes from theme settings.
	const [ fontSizes ] = useSettings( 'typography.fontSizes' );

	// Don't show these controls to the clients.
	if ( isClientLocked() ) {
		return null;
	}
	return (
		<InspectorControls group='styles'>
			<PanelBody
				title={ __( 'Custom Typography' ) }
				initialOpen={ true }
				className="dst-list-typography-panel"
			>
				<BaseControl
					__nextHasNoMarginBottom
					id={null}
					label={ __( 'Main Text' ) }
				>
					<FontSizePicker
						__next40pxDefaultSize
						fontSizes={ fontSizes }
						value={ titleSize }
						onChange={ ( value ) => {
							setAttributes( { titleSize: value } );
						} }
						withSlider
						withReset
					/>
				</BaseControl>
				<Flex gap={2} align="start" className="dst-list-typography">
					<FlexItem style={ { width: '33%' } }>
						<LineHeightControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							__unstableInputWidth='120px'
							value={ titleLineHeight }
							onChange={ ( value ) => {
								setAttributes( { titleLineHeight: value } );
							} }
						/>
					</FlexItem>
					<FlexItem style={ { width: '33%' } }>
						<SelectControl
							label={ __( 'Text Transform' ) }
							value={ titleTransform }
							options={ [
								{ label: __( 'None' ), value: '' },
								{ label: __( 'Uppercase' ), value: 'uppercase' },
								{ label: __( 'Lowercase' ), value: 'lowercase' },
								{ label: __( 'Capitalize' ), value: 'capitalize' },
							] }
							onChange={ ( value ) => {
								setAttributes( { titleTransform: value } );
							} }
						/>
					</FlexItem>
					<FlexItem style={ { width: '33%' } }>
						<SelectControl
							label={ __( 'Font Weight' ) }
							value={ titleWeight }
							options={ [
								{ label: __( 'Default' ), value: '' },
								{ label: __( 'Normal (400)' ), value: '400' },
								{ label: __( 'Medium (500)' ), value: '500' },
								{ label: __( 'Semi-Bold (600)' ), value: '600' },
								{ label: __( 'Bold (700)' ), value: '700' },
								{ label: __( 'Extra-Bold (800)' ), value: '800' },
							] }
							onChange={ ( value ) => {
								setAttributes( { titleWeight: value } );
							} }
						/>
					</FlexItem>
				</Flex>
				<br/>
				<DstColorPicker
					onChange={ (value) => setAttributes({ colorTitle: value}) }
					label={ __( 'Main Text Color' ) }
					value={colorTitle}
				/>
				{
					showSubtitle && (
						<>
							<BaseControl
								__nextHasNoMarginBottom
								id={null}
								label={ __( 'Additional Text' ) }
							>
								<FontSizePicker
									__next40pxDefaultSize
									fontSizes={ fontSizes }
									value={ subtitleSize }
									onChange={ ( value ) => {
										setAttributes( { subtitleSize: value } );
									} }
									withSlider
									withReset
								/>
							</BaseControl>
							<DstColorPicker
								onChange={ (value) => setAttributes({ colorSubtitle: value}) }
								label={ __( 'Additional Text Color' ) }
								value={colorSubtitle}
							/>
						</>
					)
				}
				{
					showHeroText && (
						<>
							<BaseControl
								__nextHasNoMarginBottom
								id={null}
								label={ __( 'Hero Text' ) }
							>
								<FontSizePicker
									__next40pxDefaultSize
									fontSizes={ fontSizes }
									value={ heroTextSize }
									onChange={ ( value ) => {
										setAttributes( { heroTextSize: value } );
									} }
									withSlider
									withReset
								/>
							</BaseControl>
							<DstColorPicker
								onChange={ (value) => setAttributes({ colorHero: value}) }
								label={ __( 'Hero Text Color' ) }
								value={colorHero}
							/>
						</>
					)
				}
				{
					showIcons && (
						<>
							<DstColorPicker
								onChange={ (value) => setAttributes({ colorIcon: value}) }
								label={ __( 'Icon Color' ) }
								value={colorIcon}
							/>
						</>
					)
				}
			</PanelBody>
			<PanelBody
				title={ __( 'Border Settings' ) }
				initialOpen={ true }
				className="dst-list-border-panel"
			>
				<ToggleControl
					label={ __( 'Vertical Borders' ) }
					checked={ hasVerticalBorder }
					onChange={ () => setAttributes({ hasVerticalBorder: ! hasVerticalBorder }) }
				/>
				<ToggleControl
					label={ __( 'Horizontal Borders' ) }
					checked={ hasHorizontalBorder }
					onChange={ () => setAttributes({ hasHorizontalBorder: ! hasHorizontalBorder }) }
				/>
			</PanelBody>
		</InspectorControls>
	)
}
