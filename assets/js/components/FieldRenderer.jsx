/**
 * Maps field.type to the appropriate component.
 * Supports built-in types and custom types registered via registerFieldType().
 */

import TextField from './fields/TextField.jsx';
import NumberField from './fields/NumberField.jsx';
import PasswordField from './fields/PasswordField.jsx';
import ToggleField from './fields/ToggleField.jsx';
import SelectField from './fields/SelectField.jsx';
import UnitField from './fields/UnitField.jsx';
import TokenListField from './fields/TokenListField.jsx';
import ColorField from './fields/ColorField.jsx';
import CodeField from './fields/CodeField.jsx';

const builtinTypes = {
	text: TextField,
	number: NumberField,
	password: PasswordField,
	toggle: ToggleField,
	select: SelectField,
	unit: UnitField,
	'token-list': TokenListField,
	color: ColorField,
	code: CodeField,
};

const FieldRenderer = ( { field, value, onChange, disabled } ) => {
	// Check for custom field types first.
	const customTypes = window.MilliSettings?.customFieldTypes || {};
	const Component = customTypes[ field.type ] || builtinTypes[ field.type ];

	if ( ! Component ) {
		return null;
	}

	return (
		<Component
			field={ field }
			value={ value }
			onChange={ onChange }
			disabled={ disabled }
		/>
	);
};

export default FieldRenderer;
