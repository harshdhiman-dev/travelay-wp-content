import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Modal,
	Flex,
	FlexBlock,
	Button,
} from '@wordpress/components';

export const HeadingInspectorControls = (
	{
		value,
		onChange
	}
) => {
	const {
		showPretitle,
		showTitle,
		showSubtitle,
		showDescription
	} = value;

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
			onChange(
				{
					...value,
					[attributeToHide]: true
				}
			);
		} else {
			// Check if fields have content
			const fieldContent = value[attributeToDelete];

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
				onChange({
					...value,
					[attributeToHide]: false,
				});
			}
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Heading Settings')}>
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
									onClick={
										() => {
											const newValues = {
												...value,
												[modalState.attributeToHide]: false,
												[modalState.attributeToDelete]: '',
											};
										
											onChange(newValues);
											setModalState({ open: false });
										}
									}
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
