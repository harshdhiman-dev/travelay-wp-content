import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch embed HTML from an oEmbed endpoint for a given URL.
 *
 * @param {string} url The external video URL.
 * @return {Promise<string>} Promise resolving with the embed HTML.
 */
export const getEmbedHTML = ( url ) => {
	const endpoint = `/oembed/1.0/proxy?url=${ encodeURIComponent( url ) }`;
	return apiFetch( { path: endpoint } ).then( ( response ) => {
		if ( response && response.html ) {
			return response.html;
		}
		throw new Error( 'No embed HTML returned.' );
	} );
};

/**
 * Extract the `src` attribute from an iframe string.
 *
 * @param {string} iframeString The iframe HTML string.
 * @return {string|null} The extracted `src` value or `null` if not found.
 */
export const extractIframeSrc = ( iframeString ) => {
    const match = iframeString.match(/<iframe[^>]+src=["']([^"']+)["']/i);
    return match ? match[1] : null;
};

/**
 * Create a structured media object from a media item.
 *
 * @param {Object} media The media object returned by the media uploader.
 * @return {Object} The structured media object with additional metadata.
 */
export const createMediaObject = ( media ) => {
    const mediaObject = {
        id: media.id,
        url: media.url,
        alt: media.alt || '',
    };

    // Only add sizes and size properties for images (not videos).
    if ( media.sizes ) {
        mediaObject.sizes = media.sizes;
        mediaObject.size = media.size || 'full';
    }

    return mediaObject;
};


/**
 * Dynamically update a key in the media payload object.
 *
 * @param {string} keyToModify The key in the payload to update (e.g., 'primaryType').
 * @param {any}    valueToSet  The value to set for the key.
 * @param {Object} wholeObject The entire existing object to update.
 * @return {Object}               The updated payload object.
 */
export const buildMediaPayload = ( keyToModify, valueToSet, wholeObject = {} ) => {
	return {
		...wholeObject, // Spread the original object
		[keyToModify]: valueToSet, // Dynamically update the specified key
	};
};
