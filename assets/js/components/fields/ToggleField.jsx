import { ToggleControl } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const ToggleField = ( { field, value, onChange, disabled } ) => (
	<ToggleControl
		label={ <LabelWithTooltip label={ field.label } tooltip={ field.tooltip } /> }
		checked={ !! value }
		disabled={ disabled }
		onChange={ onChange }
	/>
);

export default ToggleField;
