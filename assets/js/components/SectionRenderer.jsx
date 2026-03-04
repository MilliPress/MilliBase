/**
 * Renders a PanelBody with grouped fields from a section definition.
 */

import { createElement } from '@wordpress/element';
import { PanelBody, Flex, FlexItem } from '@wordpress/components';
import FieldRenderer from './FieldRenderer.jsx';
import { useSettings } from './SettingsProvider.jsx';
import evaluateCondition from '../utils/evaluateCondition.js';

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
	const resolvedSettings = status?.settings?.resolved || {};

	const renderField = ( field ) => {
		const parts = field.key.split( '.' );
		const module = parts[ 0 ];
		const key = parts[ 1 ];
		const disabled = settings?.[ module ]
			? ! ( key in settings[ module ] )
			: false;

		// For disabled fields (e.g. constant-defined), show the resolved
		// runtime value from the status API instead of the schema default.
		const value = disabled
			? ( resolvedSettings?.[ module ]?.[ key ] ?? field.default )
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
		// Evaluate against resolved settings so constant-defined values
		// (absent from editable `settings`) are taken into account.
		if ( field.hide && evaluateCondition( field.hide, resolvedSettings ) ) {
			return false;
		}
		if ( field.show && ! evaluateCondition( field.show, resolvedSettings ) ) {
			return false;
		}
		return true;
	};

	const visibleFields = ( section.fields || [] ).filter( isFieldVisible );
	const rows = groupFieldsIntoRows( visibleFields );

	return (
		<PanelBody
			title={ section.title }
			initialOpen={ section.initial_open !== false }
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
