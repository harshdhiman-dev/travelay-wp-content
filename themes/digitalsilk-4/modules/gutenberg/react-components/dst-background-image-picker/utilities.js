import { useEffect } from '@wordpress/element';
import { ColorIndicator } from '@wordpress/components';
import { v4 as uuidv4 } from 'uuid';

/**
 * Hook that triggers a callback if the user clicks outside the ref element.
 *
 * @param {Object}   refs    - React ref to the target element.
 * @param {Function} handler - Callback to execute when outside click occurs.
 */
export const useClickOutside = (refs, handler) => {
    useEffect(
        () => {
            const listener = (event) => {
                if (
                    refs.some(ref => ref.current && ref.current.contains(event.target))
                ) {
                    return;
                }
                handler();
            };

            document.addEventListener('mouseup', listener);
            document.addEventListener('touchend', listener);

            return () => {
                document.removeEventListener('mouseup', listener);
                document.removeEventListener('touchend', listener);
            };
        },
        [ refs, handler ]
    );
};

/**
 * Returns the proper preview image URL for a given media object.
 *
 * @param {Object} media Media object.
 * @return {string|undefined} URL to use.
 */
const getMediaPreviewUrl = (media) => {
	if ( ! media) {
        return;
    }
	if ( media.type === 'video' ) {
        return media.icon;
    }
	return media?.sizes?.thumbnail?.url || media?.url;
};

/**
 * Generates <ColorIndicator> elements for desktop/mobile background image items.
 * Avoids duplicate indicators if both breakpoints use the same media.
 *
 * @param {Object}        item               Background image item with `desktop` and `mobile` keys.
 * @param {string|number} keyPrefix          Unique prefix for keys (e.g. item.id or index).
 * @param {Function}      IndicatorComponent Optional custom component instead of <ColorIndicator>
 * @return {JSX.Element[]} List of image indicator components.
 */
export const generateResponsiveIndicators = (
	item,
	keyPrefix,
	IndicatorComponent = ( { imageUrl, itemKey } ) => (
		<ColorIndicator
			key={ itemKey }
			style={{
				backgroundImage: `url(${ imageUrl })`,
				backgroundSize: 'cover',
				backgroundPosition: 'center',
				width: '24px',
				height: '24px',
			}}
		/>
	)
) => {
	const desktop = item?.desktop?.media;
	const mobile = item?.mobile?.media;

	const desktopId = desktop?.id;
	const mobileId = mobile?.id;

	const indicators = [];

	if ( desktopId && desktopId === mobileId ) {
		const url = getMediaPreviewUrl( desktop );
		if ( url ) {
			indicators.push(
				IndicatorComponent({ imageUrl: url, itemKey: `${keyPrefix}-single` })
			);
		}
	} else {
		const desktopUrl = getMediaPreviewUrl( desktop );
		const mobileUrl = getMediaPreviewUrl( mobile );

		if ( desktopUrl ) {
			indicators.push(
				IndicatorComponent({ imageUrl: desktopUrl, itemKey: `${keyPrefix}-desktop` })
			);
		}

		if ( mobileUrl ) {
			indicators.push(
				IndicatorComponent({ imageUrl: mobileUrl, itemKey: `${keyPrefix}-mobile` })
			);
		}
	}

	return indicators;
};

/**
 * Compare if mobile and desktop media objects are different by ID.
 *
 * @param {Object} item - Background image item with desktop/mobile media.
 * @return {boolean} True if they differ, false if they’re the same or one is missing.
 */
export const isMobileMediaDifferent = ( item ) => {
	const desktopId = item?.desktop?.media?.id;
	const mobileId = item?.mobile?.media?.id;

	// If either is missing, we treat them as different
	if ( ! desktopId || ! mobileId ) {
		return true;
	}

	return desktopId !== mobileId;
};

/**
 * Clean up a full media object and return only essential fields.
 *
 * @param {Object} media - Full media object from WordPress Media Library.
 * @return {Object} Cleaned media object with only necessary properties.
 */
export const cleanMediaObject = ( media ) => {
	if ( ! media || typeof media !== 'object' ) {
		return {};
	}

	const {
		id,
		title,
		filename,
		url,
		alt,
		mime,
		type,
		icon,
		height,
		width,
		sizes,
	} = media;

	return {
		id,
		title,
		filename,
		url,
		alt,
		mime,
		type,
		icon,
		height,
		width,
		sizes,
	};
};

// Create a default background image object with the correct structure
export const createBackgroundImageObject = ( desktopMedia = {}, mobileMedia = {}, overrides = {} ) => ({
    id: uuidv4(),
    desktop: {
        media: desktopMedia,
        fixed: false,
        focal: {
            x: 0.5,
            y: 0.5,
        },
        size: 'cover',
        width: 'auto',
    },
    mobile: {
        media: mobileMedia,
        fixed: false,
        focal: {
            x: 0.5,
            y: 0.5,
        },
        size: 'cover',
        width: 'auto',
    },
    lazy: true,
    ...overrides,
});