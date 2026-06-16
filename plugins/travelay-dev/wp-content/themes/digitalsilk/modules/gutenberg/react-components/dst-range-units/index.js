import { useState, useEffect } from '@wordpress/element';
import {
    __experimentalUnitControl as UnitControl,
    RangeControl,
    Flex,
    FlexBlock,
    BaseControl
} from '@wordpress/components';

/**
 * DstRangeUnits Component
 *
 * This component allows users to input a numeric value combined with a unit (e.g., 20px, 5em),
 * through both a UnitControl (text input) and a RangeControl (slider).
 * It synchronizes manual input and slider control automatically, respecting min/max values per unit.
 *
 * If no units or minMax arrays are provided, it falls back to default units (px, em, rem, %, vmin, vmax, vw, vh)
 * and default min/max constraints.
 *
 * Example usage:
 * import { DstRangeUnits } from '../../react-components/';
 *
 * <DstRangeUnits
 *  label={__('Padding', 'textdomain')}
 *  value={padding}
 *  onChange={(newPadding) => setAttributes({ padding: newPadding })}
 *  units={['px', 'em', 'rem', '%']}
 *  minMax={[
 *    { px: { min: 0, max: 500 } },
 *    { em: { min: 0, max: 50 } },
 *    { rem: { min: 0, max: 30 } },
 *    { '%': { min: 0, max: 100 } }
 *  ]}
 * />
 *
 * @param {Object}   props
 * @param {string}   props.label    - Label for the control.
 * @param {string}   props.value    - Current value (e.g., "20px").
 * @param {Function} props.onChange - Function to call when value changes.
 * @param {Array}    [props.units]  - Optional array of allowed units.
 * @param {Array}    [props.minMax] - Optional array of min/max rules per unit.
 * @return {JSX.Element} The component.
 */
export const DstRangeUnits = ( { label, value, onChange, units = [], minMax = [] } ) => {
	const defaultUnits = [ 'px', 'rem', '%', 'vmin', 'vmax' ];
	const defaultMinMax = [
		{ px: { min: 0, max: 150 } },
		{ rem: { min: 0, max: 15 } },
		{ '%': { min: 0, max: 100 } },
		{ vmin: { min: 0, max: 100 } },
		{ vmax: { min: 0, max: 100 } },
	];

	const availableUnits = units.length ? units : defaultUnits;
	const availableMinMax = minMax.length ? minMax : defaultMinMax;

	const [ numericValue, setNumericValue ] = useState( 0 );
	const [ currentUnit, setCurrentUnit ] = useState( availableUnits[0] );
	const [ currentMin, setCurrentMin ] = useState( 0 );
	const [ currentMax, setCurrentMax ] = useState( 100 );

	// Parse numeric part and unit from a value like "50px"
    const parseValueAndUnit = ( valueString ) => {
        if ( valueString === '' || valueString === undefined || valueString === null ) {
            return { number: '', unit: availableUnits[0] };
        }

        const match = valueString.match( /^(-?\d*\.?\d*)([a-z%]*)$/i );
        if ( match ) {
            return {
                number: match[1] === '' ? '' : parseFloat( match[1] ),
                unit: match[2] || availableUnits[0],
            };
        }
        return { number: '', unit: availableUnits[0] };
    };

	// Get min/max values for a unit
	const getMinMaxForUnit = ( unit ) => {
		const match = availableMinMax.find( ( item ) => Object.keys( item )[0] === unit );
		if ( match ) {
			return match[ unit ];
		}
		return { min: 0, max: 100 };
	};

	// Sync state from external value
	useEffect(
        () => {
            const { number, unit } = parseValueAndUnit( value );
            const { min, max } = getMinMaxForUnit( unit );

            setNumericValue( number );
            setCurrentUnit( unit );
            setCurrentMin( min );
            setCurrentMax( max );
	    },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ value, availableMinMax ]
    );

	// Handle UnitControl changes
    const handleUnitControlChange = ( newValue ) => {
        const { number, unit } = parseValueAndUnit( newValue );
        const { min, max } = getMinMaxForUnit( unit );

        let clampedValue = number;

        // If the unit changed, re-clamp the value immediately
        if ( unit !== currentUnit ) {
            if ( clampedValue < min ) {
                clampedValue = min;
            }
            if ( clampedValue > max ) {
                clampedValue = max;
            }
        } else {
            // Same unit: still clamp just in case
            if ( clampedValue < currentMin ) {
                clampedValue = currentMin;
            }
            if ( clampedValue > currentMax ) {
                clampedValue = currentMax;
            }
        }

        setNumericValue( clampedValue );
        setCurrentUnit( unit );
        setCurrentMin( min );
        setCurrentMax( max );

        onChange( `${ clampedValue }${ unit }` );
    };


	// Handle RangeControl changes
    const handleRangeChange = ( newValue ) => {
        if ( numericValue === '' ) {
            // If value was empty and user starts dragging the slider
            // Assume 0 as starting point
            setNumericValue( newValue );
            onChange( `${ newValue }${ currentUnit }` );
            return;
        }

        setNumericValue( newValue );
        onChange( `${ newValue }${ currentUnit }` );
    };

	return (
        <BaseControl
            __nextHasNoMarginBottom
            id={null}
            label={ label }
            className="ds-unit-control"
        >
            <Flex gap={4}>
	            <FlexBlock>
		            <RangeControl
			            __next40pxDefaultSize
			            __nextHasNoMarginBottom
			            value={ numericValue === '' ? 0 : numericValue }
			            onChange={ handleRangeChange }
			            min={ currentMin }
			            max={ currentMax }
			            withInputField={ false }
		            />
	            </FlexBlock>
	            <FlexBlock style={ { maxWidth: '90px' } }>
                    <UnitControl
                        __next40pxDefaultSize
                        value={ `${ numericValue }${ currentUnit }` }
                        onChange={ handleUnitControlChange }
                        units={ availableUnits.map( ( unit ) => ( { value: unit, label: unit } ) ) }
                    />
                </FlexBlock>
            </Flex>
        </BaseControl>
	);
};
