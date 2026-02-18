import { ColorPicker } from '@wordpress/components';
import { LabelWithTooltip } from '../LabelWithTooltip.jsx';

const ColorField = ( { field, value, onChange, disabled } ) => (
	<div>
		{ field.tooltip ? (
			<LabelWithTooltip label={ field.label } tooltip={ field.tooltip } />
		) : (
			<span>{ field.label }</span>
		) }
		<ColorPicker
			color={ value || '#000000' }
			onChange={ onChange }
			enableAlpha={ field.enableAlpha ?? false }
		/>
	</div>
);

export default ColorField;
