/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, CheckboxControl, TextControl } from '@wordpress/components';
import { DstIconPicker, ClientLockControl } from '../../../react-components';

export const BlockEdit = (props) => {
	const { attributes, setAttributes, wrapperProps } = props;
	const { icon, iconSet, size, placeholder } = attributes;
	const blockProps = useBlockProps({
		...wrapperProps
	});

	// List all of the available icon sets.
	// This can later be moved to a global setting, where we pull the icon sets from the theme.
	const availableIconSets = [ 'theme', 'social', 'buttons', 'general' ];

	/*
	* Handle the change of an icon set.
	*
	* @param {string} setName - The name of the icon set.
	* @param {boolean} isChecked - Whether the icon set is checked or not.
	* @return {void}
	*/
	const handleIconSetChange = ( setName, isChecked ) => {
		const updatedSets = isChecked
			? [
				...iconSet,
				setName
			] : iconSet.filter(
				(set) => set !== setName
			);

		// Ensure at least one icon set remains selected
		if ( updatedSets.length === 0 ) {
			return;
		}

		setAttributes({ iconSet: updatedSets });
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Icon Settings' ) } initialOpen={ true }>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Icon Size' ) }
						value={ size }
						onChange={ ( newSize ) => setAttributes({ size: newSize }) }
						min={ 10 }
						max={ 300 }
					/>
					<ClientLockControl>
					<hr />
					<fieldset>
						<legend
							style={
								{
									marginBottom: '1em',
									fontSize: '11px',
									fontWeight: '500',
									textTransform: 'uppercase',
									fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
								}
							}
						>
							{ __( 'ICON SETS' ) }
						</legend>
						
							{
								availableIconSets.map(
									( setName ) => (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={ setName }
											label={ setName.charAt(0).toUpperCase() + setName.slice(1) }
											checked={ iconSet.includes(setName) }
											onChange={ (isChecked) => handleIconSetChange(setName, isChecked) }
										/>
									)
								)
							}
					</fieldset>
					<hr />
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Placeholder' ) }
						value={ placeholder }
						onChange={ ( newPlaceholder ) => setAttributes({ placeholder: newPlaceholder }) }
					/>
					</ClientLockControl>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<DstIconPicker
					icon={ icon }
					onChange={ ( newIcon ) => setAttributes({ icon: newIcon }) }
					size={ size }
					iconSet={ iconSet }
					placeholder={ placeholder }
				/>
			</div>
		</>
	);
};
