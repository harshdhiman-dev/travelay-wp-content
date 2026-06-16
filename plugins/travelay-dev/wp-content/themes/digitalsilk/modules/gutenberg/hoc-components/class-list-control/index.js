/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { getBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	Flex,
	__experimentalTruncate as Truncate,
} from '@wordpress/components';
import { ClientLockControl } from '../../react-components';
import classnames from 'classnames';

/**
 * Register the `class` attribute for blocks supporting dsClassList.
 *
 * @param {Object} settings Block settings.
 * @return {Object} Modified settings.
 */
const registerDsClassAttribute = ( settings ) => {
	if ( settings.supports?.dsClassList ) {
		settings.attributes = {
			class: {
				type: 'string',
				default: '',
			},
			...settings.attributes,
		};
	}
	return settings;
};

addFilter(
	'blocks.registerBlockType',
	'ds-block-filters/register-ds-classlist-class-attr',
	registerDsClassAttribute
);

/**
 * HOC: Adds custom class list controls to eligible blocks.
 *
 * @param {Function} BlockEdit Original block edit component.
 * @return {Function} Wrapped component.
 */
const withDsClassListControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const { attributes, setAttributes, name, wrapperProps = {} } = props;
			const blockType = getBlockType( name );

			if ( ! blockType?.supports?.dsClassList?.length ) {
				return <BlockEdit { ...props } />;
			}

			const { class: classAttr = '' } = attributes;
			const availableClasses = blockType.supports.dsClassList;

			// Collect default classes from config
			const defaultClasses = availableClasses
				.filter( ( item ) => item.isDefault )
				.map( ( item ) => item.name );

			/**
			 * Ensure default classes are always present.
			 */
			useEffect(
				() => {
					const current = classAttr.split( ' ' ).filter( Boolean );
					const updated = Array.from( new Set( [ ...current, ...defaultClasses ] ) );

					if ( updated.join( ' ' ) !== classAttr.trim() ) {
						setAttributes( { class: updated.join( ' ' ) } );
					}
				},
				// eslint-disable-next-line react-hooks/exhaustive-deps
				[ classAttr, defaultClasses ]
			);

			/**
			 * Toggle a class (only if not default).
			 *
			 * @param {string} className Class to toggle.
			 */
			const handleToggleClass = ( className ) => {
				if ( defaultClasses.includes( className ) ) {
					return;
				}

				const current = classAttr.split( ' ' ).filter( Boolean );
				const isActive = current.includes( className );
				const updated = isActive
					? current.filter( ( cls ) => cls !== className )
					: [ ...current, className ];

				setAttributes( { class: updated.join( ' ' ) } );
			};

			const currentClasses = classAttr.split( ' ' ).filter( Boolean );

			// Inject class into wrapperProps
			const updatedWrapperProps = {
				...wrapperProps,
				className: classnames( wrapperProps.className, classAttr ),
			};

			return (
				<>
					<ClientLockControl>
						<InspectorControls group="styles">
							<PanelBody
								title={ __( 'Additional Custom Classes', 'dstheme' ) }
								className="dst-custom-settings"
							>
								<Flex
									justify="flex-start"
									wrap
									style={ {
										border: '1px solid #757575',
										'--item-gap': '3px',
										gap: 'var(--item-gap)',
										padding: 'var(--item-gap)',
									} }
								>
									{ availableClasses.map( ( item ) => {
										const { name: className, label, isDefault = false } = item;
										const isActive = currentClasses.includes( className );

										return (
											<Button
												__next40pxDefaultSize
												key={ className }
												variant="tertiary"
												size="default"
												isPressed={ isActive }
												disabled={ isDefault }
												onClick={ () => handleToggleClass( className ) }
												showTooltip
												style={ {
													textTransform: 'capitalize',
													'--wp-admin-theme-color': '#757575',
													borderRadius: '0',
													width: 'calc( 50% - calc( var(--item-gap) / 2 ) )',
													justifyContent: 'center',
													backgroundColor: isDefault ? '#000' : undefined,
													color: isDefault ? '#fff' : undefined,
												} }
											>
												<Truncate limit={ 25 } numberOfLines={ 1 } ellipsizeMode="tail">
													{ label }
												</Truncate>
											</Button>
										);
									} ) }
								</Flex>
							</PanelBody>
						</InspectorControls>
					</ClientLockControl>

					<BlockEdit { ...props } wrapperProps={ updatedWrapperProps } />
				</>
			);
		};
	},
	'withDsClassListControls'
);

addFilter(
	'editor.BlockEdit',
	'ds-block-filters/with-ds-classlist-controls',
	withDsClassListControls
);
