/**
 * Renders a PanelBody with grouped fields from a section definition.
 */

import { useState, useMemo } from '@wordpress/element';
import { PanelBody, Flex, FlexItem, FormToggle } from '@wordpress/components';
import Badge from './Badge.jsx';
import Intro from './Intro.jsx';
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

const SectionRenderer = ( { section, accordion, accordionOpen, onAccordionToggle } ) => {
	const context = useSettings();
	const { status, settings, updateSetting } = context;
	const constants = status?.settings?.constants || {};

	// Effective runtime values: editable settings overlaid with constant
	// overrides, plus status under the `status` namespace. Shared by every
	// field-level condition (show/hide/lock) so they all evaluate
	// against identical data. Computed once per render, not once per field.
	const effective = useMemo( () => {
		const merged = { ...settings, status };
		for ( const [ mod, vals ] of Object.entries( constants ) ) {
			merged[ mod ] = { ...merged[ mod ], ...vals };
		}
		return merged;
	}, [ settings, status, constants ] );

	// Active-toggle configuration.
	const active = section.active || null;
	let activeModule, activeKey, isActive;
	let activeConstant = false;
	if ( active ) {
		const activeParts = active.key.split( '.' );
		activeModule = activeParts[ 0 ];
		activeKey = activeParts[ 1 ];
		// Pinned by a wp-config constant — same authoritative check as fields.
		activeConstant =
			!! constants?.[ activeModule ] &&
			activeKey in constants[ activeModule ];
		isActive = activeConstant
			? ( constants[ activeModule ][ activeKey ] ?? active.default )
			: ( settings?.[ activeModule ]?.[ activeKey ] ?? active.default );
	}

	// Symmetric with field `lock`: a constant pin or a truthy `lock`
	// condition makes the active toggle read-only.
	const activeLocked =
		activeConstant ||
		!! ( active?.lock && evaluateCondition( active.lock, effective ) );

	const renderField = ( field ) => {
		// Symmetric with show/hide: a truthy `lock` condition makes
		// the field read-only. Applies to every field type.
		const conditionDisabled = !! (
			field.lock && evaluateCondition( field.lock, effective )
		);

		// Buttons have no module/key lookup and no value/onChange contract.
		if ( field.type === 'button' ) {
			return (
				<FieldRenderer
					key={ field.key }
					field={ field }
					value={ undefined }
					onChange={ () => {} }
					disabled={ !! ( active && ! isActive ) || conditionDisabled }
				/>
			);
		}

		const parts = field.key.split( '.' );
		const module = parts[ 0 ];
		const key = parts[ 1 ];
		// A field is pinned by a wp-config constant when its module/key
		// appears in the status `constants` slice. This is the authoritative
		// source: `settings` re-overlays constant values on read, so the
		// key's presence there can't distinguish pinned from stored.
		const constantDisabled =
			!! constants?.[ module ] && key in constants[ module ];

		// Fields are disabled when defined by a constant, when the
		// section's active toggle is off, or when `lock` matches.
		const disabled =
			constantDisabled || ( active && ! isActive ) || conditionDisabled;

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
	// Status evaluation.
	const statusConfig = section.status;
	const hasStatus = statusConfig?.key != null;
	const isOk = hasStatus
		? resolveDotPath( status, statusConfig.key ) === statusConfig.ok
		: true;

	// The label for the current state; an empty label hides the badge, so a
	// section can show e.g. "Connected" without a counterpart in the other state.
	let statusBadgeLabel = '';
	if ( hasStatus && statusConfig.badge ) {
		statusBadgeLabel =
			( isOk ? statusConfig.badge.ok : statusConfig.badge.error ) || '';
	}

	// Active-toggle element for section header.
	const activeToggleElement = active ? (
		<span
			onClick={ ( e ) => e.stopPropagation() }
			onKeyDown={ ( e ) => e.stopPropagation() }
			role="presentation"
		>
			<FormToggle
				checked={ isActive }
				disabled={ activeLocked }
				onChange={ () => {
					if ( activeLocked ) {
						return;
					}
					const next = ! isActive;
					updateSetting( activeModule, activeKey, next );
					if ( next ) {
						if ( accordion && onAccordionToggle ) {
							onAccordionToggle( section.id, true );
						} else {
							setIsOpen( true );
						}
					}
				} }
			/>
		</span>
	) : null;

	// Build a custom title element when status, active toggle, or badge is configured.
	const title = ( hasStatus || active || section.badge ) ? (
		<span style={ { display: 'inline-flex', alignItems: 'center', gap: '8px', width: '100%' } }>
			{ activeToggleElement }
			<span>{ section.title }</span>
			{ section.badge && (
				<Badge tone={ section.badge.tone }>
					{ section.badge.label }
				</Badge>
			) }
			{ !! statusBadgeLabel && (
				<Badge tone={ isOk ? 'ok' : 'error' }>
					{ statusBadgeLabel }
				</Badge>
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

	// Controlled state for sections with active toggle — auto-opens
	// the panel when the toggle is switched on.
	const [ isOpen, setIsOpen ] = useState( initialOpen );

	const renderContent = () => (
		<>
			{ section.intro && (
				<Intro
					text={ section.intro }
					render={ ( content ) => (
						<div className="millibase-section-intro">
							{ content }
						</div>
					) }
				/>
			) }
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

	// Determine panel controlled/uncontrolled props.
	// Accordion mode and active-toggle sections both use controlled state.
	let panelProps;
	if ( accordion ) {
		panelProps = {
			opened: accordionOpen,
			onToggle: () => onAccordionToggle( section.id, ! accordionOpen ),
		};
	} else if ( active ) {
		panelProps = { opened: isOpen, onToggle: () => setIsOpen( ! isOpen ) };
	} else {
		panelProps = { initialOpen };
	}

	return (
		<PanelBody
			title={ title }
			{ ...panelProps }
			className={ active && ! isActive ? 'millibase-section-disabled' : undefined }
		>
			{ renderContent() }
		</PanelBody>
	);
};

export default SectionRenderer;
