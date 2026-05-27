/**
 * Maps field.type to the appropriate component.
 * Supports built-in types and custom types registered via registerFieldType().
 */

import TextField from './fields/TextField.jsx';
import NumberField from './fields/NumberField.jsx';
import PasswordField from './fields/PasswordField.jsx';
import KeyField from './fields/KeyField.jsx';
import ToggleField from './fields/ToggleField.jsx';
import SelectField from './fields/SelectField.jsx';
import UnitField from './fields/UnitField.jsx';
import TokenListField from './fields/TokenListField.jsx';
import ColorField from './fields/ColorField.jsx';
import CodeField from './fields/CodeField.jsx';
import ButtonField from './fields/ButtonField.jsx';
import renderHelpText from '../utils/renderHelpText.jsx';

const builtinTypes = {
	text: TextField,
	number: NumberField,
	password: PasswordField,
	key: KeyField,
	toggle: ToggleField,
	select: SelectField,
	unit: UnitField,
	'token-list': TokenListField,
	color: ColorField,
	code: CodeField,
	button: ButtonField,
};

const FieldRenderer = ( { field, value, onChange, disabled } ) => {
	// Check for custom field types first.
	const customTypes = window.MilliBase?.customFieldTypes || {};
	const Component = customTypes[ field.type ] || builtinTypes[ field.type ];

	if ( ! Component ) {
		return null;
	}

	// Pre-process `field.help` for built-ins so plugin authors can write
	// `[label](url)` inline. Custom field types receive the raw string —
	// their `help` contract is theirs to define.
	const isBuiltin = !! builtinTypes[ field.type ];
	const fieldForRender =
		isBuiltin && field.help
			? { ...field, help: renderHelpText( field.help ) }
			: field;

	return (
		<Component
			field={ fieldForRender }
			value={ value }
			onChange={ onChange }
			disabled={ disabled }
		/>
	);
};

export default FieldRenderer;
