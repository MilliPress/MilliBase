import { TextareaControl } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const CodeField = ( { field, value, onChange, disabled } ) => (
	<TextareaControl
		label={
			field.tooltip ? (
				<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
			) : (
				field.label
			)
		}
		value={ value ?? '' }
		disabled={ disabled }
		onChange={ onChange }
		rows={ field.rows || 6 }
		className="millisettings-code-field"
	/>
);

export default CodeField;
