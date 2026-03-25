import { ColorPicker } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const ColorField = ( { field, value, onChange, disabled } ) => (
	<div>
		<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
		<ColorPicker
			color={ value || '#000000' }
			onChange={ onChange }
			enableAlpha={ field.enableAlpha ?? false }
		/>
	</div>
);

export default ColorField;
