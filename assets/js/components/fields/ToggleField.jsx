import { ToggleControl } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const ToggleField = ( { field, value, onChange, disabled } ) => (
	<ToggleControl
		label={
			field.tooltip ? (
				<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
			) : (
				field.label
			)
		}
		checked={ !! value }
		disabled={ disabled }
		onChange={ onChange }
	/>
);

export default ToggleField;
