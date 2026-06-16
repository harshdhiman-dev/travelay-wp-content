import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Modal,
	Flex,
	FlexBlock,
	Button,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption
} from '@wordpress/components';

export const HeadingInspectorControls = ({ blockProps, setToggleReadMore }) => {
	const { attributes, setAttributes } = blockProps;
	const {
		description_hidden_button,
		description_hidden_button_less,
		showReadMore,
		showPretitle,
		showTitle,
		showSubtitle,
		showDescription,
		moduleVariant
	} = attributes;

	// Set modal states.
	const [ modalState, setModalState ] = useState(
		{
			open: false,
			attributeToHide: null,
			attributeToDelete: null,
		}
	);

	// Show confirmation modal when toggling values.
	// This is to prevent the user from accidentally removing the content.
	const toggleValues = (
		{
			newValue,
			attributeToHide,
			attributeToDelete
		}
	) => {
		if ( newValue ) {
			setAttributes( { [attributeToHide]: true } );
		} else {
			// For description field, always show the warning
			// eslint-disable-next-line no-lonely-if
			if (attributeToDelete === 'description') {
				setModalState(
					{
						open: true,
						attributeToHide,
						attributeToDelete
					}
				);
			} else {
				// For other fields, check if they have content
				const fieldContent = attributes[attributeToDelete];

				// If the field has content, show the warning modal
				if (fieldContent && fieldContent.trim() !== '') {
					// Open the modal and remember which fields are affected
					setModalState(
						{
							open: true,
							attributeToHide,
							attributeToDelete
						}
					);
				} else {
					// If no content, just disable the field without warning
					setAttributes({
						[attributeToHide]: false,
					});
				}
			}
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ showPretitle ? __('Pretitle') : __('Pretitle') }
						onChange={
							(newValue) => toggleValues(
								{
									newValue,
									attributeToHide: 'showPretitle',
									attributeToDelete: 'pretitle'
								}
							)
						}
						checked={showPretitle}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ showTitle ? __('Title') : __('Title') }
						onChange={
							(newValue) => toggleValues(
								{
									newValue,
									attributeToHide: 'showTitle',
									attributeToDelete: 'title'
								}
							)
						}
						checked={showTitle}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ showSubtitle ? __('Subtitle') : __('Subtitle') }
						onChange={
							(newValue) => toggleValues(
								{
									newValue,
									attributeToHide: 'showSubtitle',
									attributeToDelete: 'subtitle'
								}
							)
						}
						checked={showSubtitle}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ showDescription ? __('Additional Content') : __('Additional Content') }
						onChange={
							(newValue) => toggleValues(
								{
									newValue,
									attributeToHide: 'showDescription',
									attributeToDelete: 'description'
								}
							)
						}
						checked={showDescription}
					/>
				</PanelBody>
				{
					showDescription && (
						<PanelBody title={__('Additional Settings')} initialOpen={false}>
							<ToggleControl
								__nextHasNoMarginBottom
								label={__('Show Read More')}
								onChange={
									(newValue) => {
										setAttributes({ showReadMore: newValue })
										setToggleReadMore(newValue)
									}
								}
								checked={showReadMore}
							/>
							{
								showReadMore && (
									<>
										<TextControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={__('Read More Text')}
											value={description_hidden_button}
											onChange={(newValue) => setAttributes({description_hidden_button: newValue})}
											placeholder={__('Read More...')}
										/>
										<TextControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={__('Read Less Text')}
											value={description_hidden_button_less}
											onChange={(newValue) => setAttributes({description_hidden_button_less: newValue})}
											placeholder={__('Read Less...')}
										/>
									</>
								)
							}
						</PanelBody>
					)
				}
				<PanelBody title={__('Heading Variants')} initialOpen={false}>
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
							value="heading-v1"
							label={__('Variant 1')}
						/>
						<ToggleGroupControlOption
							value="heading-v2"
							label={__('Variant 2')}
						/>
						<ToggleGroupControlOption
							value="heading-v3"
							label={__('Variant 3')}
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			{
				modalState.open && (
					<Modal
						title={ __('Are you sure?') }
						onRequestClose={ () => setModalState({ open: false }) }
						className='c-heading__modal'
					>
						<p>
							{ __('Warning: Disabling this field will permanently delete its content.') }
						</p>
						<Flex justify="flex-start">
							<FlexBlock>
								<Button
									variant='primary'
									isDestructive
									onClick={ () => {
										setAttributes({
											[modalState.attributeToHide]: false,
										});
										if ( modalState.attributeToDelete ) {
											setAttributes({
												[modalState.attributeToDelete]: '',
											});
										}
										setModalState({ open: false });
									}}
								>
									{ __('Yes') }
								</Button>
							</FlexBlock>
							<FlexBlock>
								<Button
									variant='secondary'
									onClick={ () => setModalState({ open: false }) }
								>
									{ __('No') }
								</Button>
							</FlexBlock>
						</Flex>
					</Modal>
				)
			}
		</>
	);
};
