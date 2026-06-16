import { useEffect } from '@wordpress/element';
import { select, useSelect, dispatch } from '@wordpress/data';

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
    return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Handle all of the default icon settings.
 *
 * @param {Object} props            Function properties.
 * @param {Object} props.blockProps The original block properties.
 */
export const useHandleDefaultIcons = (
    {
        blockProps
    }
) => {
	const { attributes, setAttributes, clientId } = blockProps;
	const {
		btnType,
		iconType,
        hasPopup,
	} = attributes;
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
                setAttributes(
                    {
                        hasIcon: btnDefaults.hasIcon,
                        iconValue: btnDefaults.value,
                        iconRevesed: btnDefaults.isReversed,
                        iconPosition: btnDefaults.position,
                    }
                );
            }
            if ( 'none' === iconType ) {
                setAttributes(
                    {
                        hasIcon: false,
                    }
                );
            }
            if ( 'custom' === iconType ) {
                setAttributes(
                    {
                        hasIcon: true,
                    }
                );
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
                            setAttributes(
                                {
                                    hasIcon: true,
                                    iconValue: btnDefaults.link.value,
                                    iconRevesed: false,
                                    iconPosition: 'row'
                                }
                            );
                        }
                    } else if ( btnType === 'primary' || btnType === 'secondary' ) {
                        setAttributes(
                            {
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

	// Remove all of the inner blocks, when we remove the popup.
	useEffect(
		() => {
			if ( ! hasPopup ) {
				// Remove all inner blocks
				dispatch('core/block-editor').replaceInnerBlocks(clientId, []);
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ hasPopup]
	);
}

/**
 * Normalize an internal link by removing the site URL and keeping only the relative path.
 *
 * NOTE: This is no longer used in the block, but kept for reference.
 *
 * @param {Object}   link          - The link object from attributes.
 * @param {Function} setAttributes - Function to update the block attributes.
 */
export const normalizeInternalLink = (link, setAttributes) => {
	if ( ! link || ! link.url ) {
		return;
	}

	const urlValue = link.url.trim();

	// Special case: hash-only links
	if ( '#' === urlValue || urlValue.startsWith( '#' ) ) {
		setAttributes( {
			link: {
				...link,
				url: urlValue,
			},
		} );
		return;
	}

	// Regex to detect if the link looks like a domain (but without scheme)
	const isLikelyExternalDomain = /^[a-z0-9.-]+\.[a-z]{2,}(\/.*)?$/i.test( urlValue );

	if ( isLikelyExternalDomain ) {
		// Leave it untouched
		setAttributes( {
			link: {
				...link,
				url: urlValue,
			},
		} );
		return;
	}

	try {
		const siteOrigin = window.location.origin;
		const fullUrl = new URL( urlValue, siteOrigin );

		const isSameHost = fullUrl.origin === siteOrigin;

		if ( isSameHost ) {
			let relativePath = fullUrl.pathname + fullUrl.search + fullUrl.hash;

			if ( relativePath !== '/' && relativePath.endsWith( '/' ) ) {
				relativePath = relativePath.slice( 0, -1 );
			}
			if ( '' === relativePath ) {
				relativePath = '/';
			}

			setAttributes( {
				link: {
					...link,
					url: relativePath,
				},
			} );
		} else {
			// External: leave as-is
			setAttributes( {
				link: {
					...link,
					url: urlValue,
				},
			} );
		}
	} catch ( error ) {
		// Fallback: keep the original value
		setAttributes( {
			link: {
				...link,
				url: urlValue,
			},
		} );
		// eslint-disable-next-line no-console
		console.warn( 'Invalid URL detected, left as-is:', link.url );
	}
};
