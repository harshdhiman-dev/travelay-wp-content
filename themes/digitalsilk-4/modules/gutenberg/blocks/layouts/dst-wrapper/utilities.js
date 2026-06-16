// components/utilities.js
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * useDebouncedAttributeSync
 *
 * Syncs a local state to a block attribute with debouncing.
 *
 * @param {any}      value         - The local state value to watch.
 * @param {Function} setAttributes - Function to update block attributes.
 * @param {string}   attributeName - The name of the attribute to update.
 * @param {number}   delay         - Debounce delay in ms (default: 300).
 * @return {boolean} isUpdating - Whether the value is currently being debounced.
 */
export const useDebouncedAttributeSync = ( value, setAttributes, attributeName, delay = 300 ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );
	const debounceTimer = useRef();

	useEffect(
		() => {
			setIsUpdating( true );
			clearTimeout( debounceTimer.current );

			debounceTimer.current = setTimeout( () => {
				setAttributes( { [attributeName]: value } );
				setIsUpdating( false );
			}, delay );

			return () => clearTimeout( debounceTimer.current );
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ value ]
	);

	return isUpdating;
};
