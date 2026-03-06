/**
 * Evaluate a show/hide condition tuple against current settings.
 *
 * Supports:
 * - 2-tuple: [field, value]  → equality (or glob match if value contains *)
 * - 3-tuple: [field, operator, value] → operator comparison
 *
 * Operators: =, !=, >, >=, <, <=
 * Glob: * in string values acts as a positional wildcard.
 *
 * @param {Array}  rule     The condition tuple.
 * @param {Object} settings Nested settings object.
 * @return {boolean}
 */
const OPERATORS = new Set( [ '!=', '>', '>=', '<', '<=' ] );

const resolveDotPath = ( obj, path ) => {
	const parts = path.split( '.' );
	let current = obj;
	for ( const part of parts ) {
		if ( current == null || typeof current !== 'object' ) {
			return undefined;
		}
		current = current[ part ];
	}
	return current;
};

const globMatch = ( value, pattern ) => {
	if ( typeof value !== 'string' ) {
		return false;
	}
	const segments = pattern.split( '*' );

	// Single * at start: endsWith check.
	if ( segments.length === 2 && segments[ 0 ] === '' ) {
		return value.endsWith( segments[ 1 ] );
	}
	// Single * at end: startsWith check.
	if ( segments.length === 2 && segments[ 1 ] === '' ) {
		return value.startsWith( segments[ 0 ] );
	}

	// General glob: each segment must appear in order.
	let pos = 0;
	for ( let i = 0; i < segments.length; i++ ) {
		const seg = segments[ i ];
		if ( i === 0 ) {
			// First segment must be at the start.
			if ( ! value.startsWith( seg ) ) {
				return false;
			}
			pos = seg.length;
		} else if ( i === segments.length - 1 ) {
			// Last segment must be at the end.
			if ( ! value.endsWith( seg ) ) {
				return false;
			}
		} else {
			const idx = value.indexOf( seg, pos );
			if ( idx === -1 ) {
				return false;
			}
			pos = idx + seg.length;
		}
	}
	return true;
};

const matchValue = ( actual, expected ) => {
	if ( typeof expected === 'string' && expected.includes( '*' ) ) {
		return globMatch( actual, expected );
	}
	return actual === expected;
};

const evaluateCondition = ( rule, settings ) => {
	if ( ! Array.isArray( rule ) || rule.length < 2 ) {
		return true;
	}

	let field, operator, expected;

	if ( rule.length === 2 ) {
		[ field, expected ] = rule;
		operator = '=';
	} else {
		[ field, operator, expected ] = rule;
	}

	const actual = resolveDotPath( settings, field );

	switch ( operator ) {
		case '=':
			return matchValue( actual, expected );
		case '!=':
			return ! matchValue( actual, expected );
		case '>':
			return Number( actual ) > Number( expected );
		case '>=':
			return Number( actual ) >= Number( expected );
		case '<':
			return Number( actual ) < Number( expected );
		case '<=':
			return Number( actual ) <= Number( expected );
		default:
			return true;
	}
};

export { resolveDotPath };
export default evaluateCondition;
