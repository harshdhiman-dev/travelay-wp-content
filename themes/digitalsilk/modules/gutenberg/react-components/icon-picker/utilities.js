import { useEffect } from '@wordpress/element';

/**
 * Hook that triggers a callback if the user clicks outside the ref element.
 *
 * @param {Object}   ref     - React ref to the target element.
 * @param {Function} handler - Callback to execute when outside click occurs.
 */
export const useClickOutside = (ref, handler) => {
	useEffect(
        () => {
            const listener = (event) => {
                if ( ! ref.current || ref.current.contains(event.target) ) {
                    return;
                }
                handler();
            };

            document.addEventListener('mousedown', listener);
            document.addEventListener('touchstart', listener);

            return () => {
                document.removeEventListener('mousedown', listener);
                document.removeEventListener('touchstart', listener);
            };
	    },
        [ref, handler]
    );
};
