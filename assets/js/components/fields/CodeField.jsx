import { TextareaControl } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const CodeField = ( { field, value, onChange, disabled } ) => (
	<TextareaControl
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		value={ value ?? '' }
		disabled={ disabled }
		onChange={ onChange }
		rows={ field.rows || 6 }
		className="millibase-code-field"
	/>
);

export default CodeField;
