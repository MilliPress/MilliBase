/**
 * Renders a PanelBody with grouped fields from a section definition.
 */

import { createElement } from '@wordpress/element';
import { PanelBody, Flex, FlexItem } from '@wordpress/components';
import FieldRenderer from './FieldRenderer.jsx';
import { useSettings } from './SettingsProvider.jsx';
import evaluateCondition, { resolveDotPath } from '../utils/evaluateCondition.js';

/**
 * Group fields into rows based on the `inline` flag.
 *
 * A field without `inline` starts a new row.
 * A field with `inline: true` joins the previous row.
 *
 * Returns an array where each entry is an array of one or more fields.
 */
const groupFieldsIntoRows = ( fields ) => {
	const rows = [];

	for ( const field of fields ) {
		if ( field.inline && rows.length > 0 ) {
			rows[ rows.length - 1 ].push( field );
		} else {
			rows.push( [ field ] );
		}
	}

	return rows;
};

const SectionRenderer = ( { section } ) => {
	const context = useSettings();
	const { status, settings, updateSetting } = context;
	const constants = status?.settings?.constants || {};

	const renderField = ( field ) => {
		const parts = field.key.split( '.' );
		const module = parts[ 0 ];
		const key = parts[ 1 ];
		const disabled = settings?.[ module ]
			? ! ( key in settings[ module ] )
			: false;

		// For constant-defined fields, show the constant value
		// from the status API instead of the schema default.
		const value = disabled
			? ( constants?.[ module ]?.[ key ] ?? field.default )
			: ( settings?.[ module ]?.[ key ] ?? field.default );

		return (
			<FieldRenderer
				key={ field.key }
				field={ field }
				value={ value }
				onChange={ ( newValue ) =>
					updateSetting( module, key, newValue )
				}
				disabled={ disabled }
			/>
		);
	};

	const isFieldVisible = ( field ) => {
		// Merge editable settings with constant overrides so that
		// hide/show conditions reflect the effective runtime values.
		const effective = { ...settings };
		for ( const [ mod, vals ] of Object.entries( constants ) ) {
			effective[ mod ] = { ...effective[ mod ], ...vals };
		}
		if ( field.hide && evaluateCondition( field.hide, effective ) ) {
			return false;
		}
		if ( field.show && ! evaluateCondition( field.show, effective ) ) {
			return false;
		}
		return true;
	};

	const visibleFields = ( section.fields || [] ).filter( isFieldVisible );
	const rows = groupFieldsIntoRows( visibleFields );

	// Status indicator evaluation.
	const statusConfig = section.status;
	const hasStatus = statusConfig?.key != null;
	const isOk = hasStatus
		? resolveDotPath( status, statusConfig.key ) === statusConfig.ok
		: true;

	const indicatorColor = isOk ? '#00a32a' : '#d63638';

	// Build a custom title element when status is configured.
	const title = hasStatus ? (
		<span style={ { display: 'inline-flex', alignItems: 'center', gap: '8px' } }>
			{ statusConfig.indicator === true && (
				<span
					style={ {
						display: 'inline-block',
						width: '8px',
						height: '8px',
						borderRadius: '50%',
						backgroundColor: indicatorColor,
						flexShrink: 0,
					} }
				/>
			) }
			<span>{ section.title }</span>
			{ statusConfig.badge && (
				<span
					style={ {
						fontSize: '11px',
						lineHeight: '1',
						padding: '4px 8px',
						borderRadius: '9999px',
						backgroundColor: isOk ? '#e3f5e1' : '#fcecec',
						color: indicatorColor,
						fontWeight: 500,
					} }
				>
					{ isOk ? statusConfig.badge.ok : statusConfig.badge.error }
				</span>
			) }
		</span>
	) : section.title;

	const openPref = section.open;
	let initialOpen;
	if ( openPref === 'error' ) {
		initialOpen = ! isOk;
	} else if ( openPref === 'ok' ) {
		initialOpen = isOk;
	} else {
		initialOpen = openPref !== false;
	}

	return (
		<PanelBody
			title={ title }
			initialOpen={ initialOpen }
		>
			{ section.intro && ( () => {
				const CustomDesc =
					window.MilliBase?.customComponents?.[ section.intro ];
				return CustomDesc
					? createElement( CustomDesc, context )
					: <p className="millibase-section-intro">{ section.intro }</p>;
			} )() }
			<Flex direction="column" gap="4">
				{ rows.map( ( row ) => {
					// Single field — render directly without a wrapper.
					if ( row.length === 1 ) {
						return renderField( row[ 0 ] );
					}

					// Multi-field row — render side-by-side.
					return (
						<Flex
							key={ row.map( ( f ) => f.key ).join( '-' ) }
							justify="start"
							align="flex-start"
							gap="4"
						>
							{ row.map( ( field ) => (
								<FlexItem
									key={ field.key }
									isBlock={ ! field.width }
									style={
										field.width
											? { width: field.width }
											: undefined
									}
								>
									{ renderField( field ) }
								</FlexItem>
							) ) }
						</Flex>
					);
				} ) }
			</Flex>
		</PanelBody>
	);
};

export default SectionRenderer;
