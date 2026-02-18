import { SelectControl } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const SelectField = ( { field, value, onChange, disabled } ) => (
	<SelectControl
		__next40pxDefaultSize
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
		options={ ( field.options || [] ).map( ( opt ) => ( {
			label: opt.label,
			value: opt.value,
		} ) ) }
	/>
);

export default SelectField;
