import { useEffect } from '@wordpress/element';

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