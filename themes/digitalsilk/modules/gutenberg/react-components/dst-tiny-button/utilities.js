import { useEffect } from '@wordpress/element';
import { select, useSelect } from '@wordpress/data';

/**
 * Retrieves all registered RichText formats, excluding "core/link".
 *
 * @return {string[]} List of allowed format types.
 */
export const allowedFormats = () => {
	const allFormats = select('core/rich-text').getFormatTypes();

	// Filter out "core/link" and return only allowed formats
	return allFormats
		.map((format) => format.name)
		.filter((formatName) => formatName !== 'core/link');
};

/**
 * Capitalizes the first letter of a string.
 *
 * @param {string} str The string to capitalize.
 */
export const capitalizeFirstLetter = (str) => {
    if ( !str || typeof str !== 'string' || str.length === 0 ) {
        return str; // Return the original string if it's empty or not a string
    }
    return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Handle all of the default icon settings.
 *
 * @param {Object}   props          Function properties.
 * @param {Object}   props.value    The current block attributes.
 * @param {Function} props.onChange Function to update the block attributes.
 */
export const useHandleDefaultIcons = (
    {
        value = {},
        onChange,
    }
) => {
	const {
		btnType,
		iconType,
	} = value;
    // Extract button default settings.
    const btnDefaults = useSelect(
        // eslint-disable-next-line no-shadow
        (select) => select('core/block-editor').getSettings()?.__experimentalFeatures?.btnDefaults,
        []
    );

    // if iconType is "default", pre-populate attributes with the default values.
    useEffect(
        () => {
            if ( iconType === 'default' && btnDefaults ) {
				onChange(
					{
						...value,
						hasIcon: btnDefaults.hasIcon,
						iconValue: btnDefaults.value,
						iconRevesed: btnDefaults.isReversed,
						iconPosition: btnDefaults.position,
					}
				)
            }
            if ( 'none' === iconType ) {
				onChange(
					{
						...value,
						hasIcon: false,
					}
				)
            }
            if ( 'custom' === iconType ) {
				onChange(
					{
						...value,
						hasIcon: true,
					}
				)
            }
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ iconType ]
    );

    // If the iconType is "default", and btnType is a link, set iconType to "none"
    useEffect(
        () => {
            if ( btnDefaults ) {
                if ( iconType === 'default' ) {
                    if ( btnType === 'link' ) {
                        if ( btnDefaults.link.hasIcon ) {
							onChange(
								{
									...value,
									hasIcon: true,
									iconValue: btnDefaults.link.value,
									iconRevesed: false,
									iconPosition: 'row',
								}
							)
                        }
                    } else if ( btnType === 'primary' || btnType === 'secondary' ) {
                        onChange(
                            {
                                ...value,
                                hasIcon: btnDefaults.hasIcon,
                                iconValue: btnDefaults.value,
                                iconRevesed: btnDefaults.isReversed,
                                iconPosition: btnDefaults.position,
                            }
                        );
                    }
                }
            }
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ btnType, iconType ]
    );
}
