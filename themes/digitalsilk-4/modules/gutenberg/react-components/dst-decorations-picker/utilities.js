import { useEffect } from '@wordpress/element';
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

// Create a default decorations object with the correct structure
export const createDecorationsObject = ( type, overrides = {})  => {
    const className = type === 'custom' ? 'my-custom-class-name' : '';
    return {
        id: uuidv4(),
        type,
        className,
        media : {},
        position: {
            desktop: {
                x: 0.5,
                y: 0.5
            },
            tablet: {
                x: 0.5,
                y: 0.5
            },
            mobile: {
                x: 0.5,
                y: 0.5
            }
        },
        display: {
            desktop: true,
            tablet: true,
            mobile: true
        },
        size: {
            desktop: {
                width: '',
                height: ''
            },
            tablet: {
                width: '',
                height: ''
            },
            mobile: {
                width: '',
                height: ''
            },
        },
        ...overrides,
    }
};

/**
 * Converts a decoration object into inline CSS variables with separate left/top percentages,
 * display, width, and height values per device.
 *
 * @param {Object} decoration - Single decoration object.
 * @return {Object} Inline style object.
 */
export const getDecorationStyles = ( decoration ) => {
	const style = {};

	[ 'desktop', 'tablet', 'mobile' ].forEach( ( device ) => {
		const pos = decoration.position?.[ device ] || { x: 0.5, y: 0.5 };
		const display = decoration.display?.[ device ];
		const size = decoration.size?.[ device ] || {};

		// Position
		const left = ( pos.x * 100 ).toFixed(2);
		const top = ( pos.y * 100 ).toFixed(2);
		style[ `--c-decoration-position-${ device }-left` ] = `${ left }%`;
		style[ `--c-decoration-position-${ device }-top` ] = `${ top }%`;

		// Display
		style[ `--c-decoration-display-${ device }` ] = display === false ? 'none' : 'block';

		// Width / Height
		if ( size.width ) {
			style[ `--c-decoration-width-${ device }` ] = size.width;
		}
		if ( size.height ) {
			style[ `--c-decoration-height-${ device }` ] = size.height;
		}
	} );

	return style;
};
