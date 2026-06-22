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
	mo: 2592000,
	y: 31536000,
};

/**
 * Maps a field's `save` base to its unit key in UNIT_MULTIPLIERS.
 */
const SAVE_UNIT_KEYS = {
	seconds: 's',
	minutes: 'm',
	hours: 'h',
	days: 'd',
	weeks: 'w',
	months: 'mo',
	years: 'y',
};

/**
 * Convert a value in seconds to the best display number and unit.
 *
 * @return {{ number: number, unit: string }}
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
			return { number: seconds / multiplier, unit };
		}
	}

	return { number: seconds, unit: unitValues[ 0 ] || 's' };
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

	const base = UNIT_MULTIPLIERS[ SAVE_UNIT_KEYS[ field.save ] ];
	const display = base
		? secondsToDisplay( ( value || 0 ) * base, units )
		: { number: value || 0, unit: units[ 0 ]?.value || 's' };

	return (
		<UnitControl
			__next40pxDefaultSize
			label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
			help={ field.help }
			disabled={ disabled }
			value={ `${ display.number }${ display.unit }` }
			onChange={ ( combinedValue ) => {
				if ( base ) {
					onChange( displayToSeconds( combinedValue ) / base );
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
