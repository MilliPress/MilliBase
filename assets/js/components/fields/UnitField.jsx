import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

/**
 * Unit multipliers for converting to/from seconds.
 */
const UNIT_MULTIPLIERS = {
	s: 1,
	m: 60,
	h: 3600,
	d: 86400,
	w: 604800,
};

/**
 * Convert a value in seconds to the best display unit.
 */
const secondsToDisplay = ( seconds, units ) => {
	const unitValues = units.map( ( u ) => u.value );

	// Try from largest unit to smallest to find the best fit.
	const sorted = [ ...unitValues ].sort(
		( a, b ) =>
			( UNIT_MULTIPLIERS[ b ] || 1 ) - ( UNIT_MULTIPLIERS[ a ] || 1 )
	);

	for ( const unit of sorted ) {
		const multiplier = UNIT_MULTIPLIERS[ unit ] || 1;
		if ( seconds % multiplier === 0 ) {
			return `${ seconds / multiplier }${ unit }`;
		}
	}

	return `${ seconds }${ unitValues[ 0 ] || 's' }`;
};

/**
 * Convert a combined value string (e.g. "24h") back to seconds.
 */
const displayToSeconds = ( combinedValue ) => {
	const numValue = parseFloat( combinedValue );
	const unit = combinedValue.replace( numValue, '' );
	const multiplier = UNIT_MULTIPLIERS[ unit ] || 1;

	return numValue * multiplier;
};

const UnitField = ( { field, value, onChange, disabled } ) => {
	const units = field.units || [
		{ value: 's', label: 'Seconds' },
		{ value: 'm', label: 'Minutes' },
		{ value: 'h', label: 'Hours' },
		{ value: 'd', label: 'Days' },
	];

	const storeAsSeconds = field.store_as === 'seconds';
	const displayValue = storeAsSeconds
		? secondsToDisplay( value || 0, units )
		: `${ value || 0 }${ units[ 0 ]?.value || 's' }`;

	return (
		<UnitControl
			__next40pxDefaultSize
			label={
				field.tooltip ? (
					<LabelWithTooltip
						label={ field.label }
						tooltip={ field.tooltip }
					/>
				) : (
					field.label
				)
			}
			disabled={ disabled }
			value={ displayValue }
			onChange={ ( combinedValue ) => {
				if ( storeAsSeconds ) {
					onChange( displayToSeconds( combinedValue ) );
				} else {
					onChange( parseFloat( combinedValue ) );
				}
			} }
			min={ field.min || 0 }
			units={ units }
		/>
	);
};

export default UnitField;
