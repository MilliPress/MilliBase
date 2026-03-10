/**
 * Renders a PanelBody with grouped fields from a section definition.
 */

import { createElement } from '@wordpress/element';
import { PanelBody, Flex, FlexItem, FormToggle } from '@wordpress/components';
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

	// Active-toggle configuration.
	const active = section.active || null;
	let activeModule, activeKey, isActive;
	if ( active ) {
		const activeParts = active.key.split( '.' );
		activeModule = activeParts[ 0 ];
		activeKey = activeParts[ 1 ];
		isActive = settings?.[ activeModule ]?.[ activeKey ] ?? active.default;
	}

	const renderField = ( field ) => {
		const parts = field.key.split( '.' );
		const module = parts[ 0 ];
		const key = parts[ 1 ];
		const constantDisabled = settings?.[ module ]
			? ! ( key in settings[ module ] )
			: false;

		// Fields are disabled when defined by a constant OR when
		// the section's active toggle is off.
		const disabled = constantDisabled || ( active && ! isActive );

		// For constant-defined fields, show the constant value
		// from the status API instead of the schema default.
		const value = constantDisabled
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

	// Active-toggle element for section header.
	const activeToggleElement = active ? (
		<span
			onClick={ ( e ) => e.stopPropagation() }
			onKeyDown={ ( e ) => e.stopPropagation() }
			role="presentation"
		>
			<FormToggle
				checked={ isActive }
				onChange={ () =>
					updateSetting( activeModule, activeKey, ! isActive )
				}
			/>
		</span>
	) : null;

	// Build a custom title element when status or active toggle is configured.
	const title = ( hasStatus || active ) ? (
		<span style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', width: '100%' } }>
			{ activeToggleElement }
			{ hasStatus && statusConfig.indicator === true && (
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
			{ hasStatus && statusConfig.badge && (
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

	// Panel open/close logic.
	const openPref = section.open;
	let initialOpen;
	if ( openPref === 'error' ) {
		initialOpen = ! isOk;
	} else if ( openPref === 'ok' ) {
		initialOpen = isOk;
	} else {
		initialOpen = openPref !== false;
	}

	const renderContent = () => (
		<>
			{ section.intro && ( () => {
				const CustomDesc =
					window.MilliBase?.customComponents?.[ section.intro ];
				return CustomDesc
					? createElement( CustomDesc, context )
					: <p className="millibase-section-intro">{ section.intro }</p>;
			} )() }
			<Flex direction="column" gap="4">
				{ rows.map( ( row ) => {
					if ( row.length === 1 ) {
						return renderField( row[ 0 ] );
					}
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
		</>
	);

	// All sections use uncontrolled PanelBody — sections with an active
	// toggle stay collapsible so users can preview disabled fields.
	return (
		<PanelBody
			title={ title }
			initialOpen={ initialOpen }
			className={ active && ! isActive ? 'millibase-section-disabled' : undefined }
		>
			{ renderContent() }
		</PanelBody>
	);
};

export default SectionRenderer;
